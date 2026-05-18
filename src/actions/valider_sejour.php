<?php
require_once __DIR__ . '/../includes/init.php';

require_login("../index.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/reservation.php");
    exit();
}

$user_id = $_SESSION['id_user'];
$panier = $_SESSION['panier'] ?? [];

// collecte des données envoyées (correction : $_post en majuscules)
$date_debut    = trim($_POST['date_debut'] ?? '');
$nom_groupe    = trim($_POST['nom_groupe'] ?? '');
$chambres_post = $_POST['chambres'] ?? []; // Format : [num_chambre => ['voyageurs' => [id1, id2], 'formules' => [id1 => f1]]]

// Vérification du panier de session
if (empty($panier)) {
    $_SESSION['error'] = "Votre panier est vide.";
    header("Location: ../pages/recherche.php");
    exit();
}

if (empty($date_debut) || empty($nom_groupe)) {
    $_SESSION['error'] = "Veuillez renseigner la date de début de séjour ainsi que le groupe.";
    header("Location: ../pages/reservation.php");
    exit();
}

try {
    // SÉCURITÉ ET LOGIQUE MÉTIER EN BACKEND

    // Validation de la date: début obligatoirement le dimanche
    $day_of_week = date('N', strtotime($date_debut)); // 7 = Dimanche
    if ($day_of_week != 7) {
        throw new Exception("Erreur de calendrier : La semaine de séjour de la station débute obligatoirement un Dimanche !");
    }

    // Date de fin de séjour (dimanche + 6 jours = fin de location le samedi d'après)
    $date_fin = date('Y-m-d', strtotime($date_debut . ' + 6 days'));

    // Contrôle anti-ubiquité : Bloquer plusieurs skieurs dans le même lit
    $all_selected_voyageurs = [];

    // On itère sur toutes les chambres du panier
    foreach ($panier as $num_chambre) {
        $voy_ids = $chambres_post[$num_chambre]['voyageurs'] ?? [];
        if (!empty($voy_ids)) {
            foreach ($voy_ids as $id_client) {
                $id_client = intval($id_client);
                if (in_array($id_client, $all_selected_voyageurs)) {
                    // Récupérer le nom du client frauduleux
                    $stmtName = $pdo->prepare("SELECT nom, prenom FROM client WHERE id_client = ?");
                    $stmtName->execute([$id_client]);
                    $row = $stmtName->fetch(PDO::FETCH_ASSOC);
                    $full_name = $row ? ($row['prenom'] . ' ' . $row['nom']) : "Skieur #$id_client";
                    throw new Exception("Un voyageur ne peut pas occuper plusieurs chambres en même temps ! Affectation multiple détectée pour {$full_name}.");
                }
                $all_selected_voyageurs[] = $id_client;
            }
        }
    }

    // S'assurer qu'au moins une personne est logée dans tout le panier (Correction de logique)
    if (empty($all_selected_voyageurs)) {
        throw new Exception("Veuillez affecter au moins un membre de votre tribu à l'une de vos chambres.");
    }

    // Contrôle des capacités en lits par chambre (en excluant les bébés)
    $stmtClient = $pdo->prepare("SELECT date_naissance FROM client WHERE id_client = ?");
    $stmtCapacity = $pdo->prepare("SELECT nb_lits FROM chambre WHERE num_chambre = ?");

    // On vérifie le panier complet
    foreach ($panier as $num_chambre) {
        $assigned_in_room = $chambres_post[$num_chambre]['voyageurs'] ?? [];

        $stmtCapacity->execute([$num_chambre]);
        $nb_lits = intval($stmtCapacity->fetchColumn());

        // Compter les skieurs occupant réellement un lit physique (âge >= 2 ans au début du séjour)
        $lits_occupes = 0;
        foreach ($assigned_in_room as $id_client) {
            $stmtClient->execute([$id_client]);
            $dob = $stmtClient->fetchColumn();
            
            $today = new DateTime($date_debut);
            $birthdate = new DateTime($dob);
            $age = $today->diff($birthdate)->y;

            if ($age >= 2) {
                $lits_occupes++;
            }
        }

        if ($lits_occupes > $nb_lits) {
            throw new Exception("La capacité maximale de la Chambre n°{$num_chambre} ({$nb_lits} lits max) est dépassée par les occupants sélectionnés.");
        }
    }

    // Récupération des prix de base depuis formule
    $stmtFormules = $pdo->query("SELECT type_formule, prix_base FROM formule");
    $formule_prices = $stmtFormules->fetchAll(PDO::FETCH_KEY_PAIR);

    // TRANSACTION SQL POSTGRESQL ATOMIQUE
    $pdo->beginTransaction();

    // Écriture de l'entité centrale 'reservation'
    $stmtRes = $pdo->prepare("
        INSERT INTO reservation (date_debut, date_fin, nom_groupe) 
        VALUES (:debut, :fin, :groupe)
    ");
    $stmtRes->execute([
        'debut'  => $date_debut,
        'fin'    => $date_fin,
        'groupe' => $nom_groupe
    ]);
    
    $id_reservation = $pdo->lastInsertId();

    // Préparation des requêtes de transaction
    $stmtReserver = $pdo->prepare("
        INSERT INTO reserver (id_client, id_reservation, num_chambre, type_formule, occupe_lit, formule_prix_final)
        VALUES (:id_client, :id_res, :num_chambre, :type, :occupe, :prix)
    ");

    $stmtFacturation = $pdo->prepare("
        INSERT INTO facturation (montant_total, id_reservation, num_chambre)
        VALUES (:total, :id_res, :num_chambre)
    ");

    // Boucle d'insertion par chambre (sur TOUT LE PANIER pour s'assurer que les chambres vides soient sanctionnées)
    foreach ($panier as $num_chambre) {
        $assigned_clients = $chambres_post[$num_chambre]['voyageurs'] ?? [];

        $stmtCapacity->execute([$num_chambre]);
        $nb_lits = intval($stmtCapacity->fetchColumn());

        $lits_occupes = 0;
        $prix_total_formules_chambre = 0;

        foreach ($assigned_clients as $id_client) {
            $stmtClient->execute([$id_client]);
            $dob = $stmtClient->fetchColumn();

            // Âge précis au moment du début de séjour
            $ref_date = new DateTime($date_debut);
            $birthdate = new DateTime($dob);
            $age = $ref_date->diff($birthdate)->y;

            // Logique de lit physique
            $occupe_lit = ($age >= 2);
            if ($occupe_lit) {
                $lits_occupes++;
            }

            // Calcul du prix individuel de la formule
            $type_formule = $chambres_post[$num_chambre]['formules'][$id_client] ?? '';
            if (empty($type_formule)) {
                throw new Exception("Veuillez sélectionner une formule pour chaque voyageur.");
            }

            $prix_base = $formule_prices[$type_formule] ?? 0;
            
            // Règles tarifaires de zarza-ski
            if ($age < 2) {
                $prix_final = 0; // Bébé gratuit
            } elseif ($age < 12) {
                $prix_final = intval(round($prix_base * 0.8)); // Enfant -20%
            } else {
                $prix_final = intval($prix_base); // Adulte plein tarif
            }

            $prix_total_formules_chambre += $prix_final;

            // Écriture de l'occupation dans 'reserver'
            $stmtReserver->execute([
                'id_client'   => $id_client,
                'id_res'      => $id_reservation,
                'num_chambre' => $num_chambre,
                'type'        => $type_formule,
                'occupe'      => $occupe_lit ? 'true' : 'false',
                'prix'        => $prix_final
            ]);
        }

        // Calcul de la facturation par hébergement (pénalité de 150 € par lit vide par rapport à la capacité de la chambre)
        $lits_vides = $nb_lits - $lits_occupes;
        $montant_chambre = $prix_total_formules_chambre + ($lits_vides * 150);

        // Insertion de la facture (Générée même si la chambre est vide ! La pénalité s'appliquera sur tous les lits)
        $stmtFacturation->execute([
            'total'       => $montant_chambre,
            'id_res'      => $id_reservation,
            'num_chambre' => $num_chambre
        ]);
    }

    // Validation finale et commit des écritures
    $pdo->commit();

    // Vider la sélection du panier de session
    unset($_SESSION['panier']);

    $_SESSION['success'] = "Votre séjour a été enregistré et validé avec succès. Retrouvez vos factures par chambre ci-dessous !";
    header("Location: ../pages/mes_reservations.php");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = "Erreur de validation de la réservation : " . $e->getMessage();
    header("Location: ../pages/reservation.php");
    exit();
}
