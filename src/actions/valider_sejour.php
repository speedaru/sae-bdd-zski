<?php
require_once __DIR__ . '/../includes/init.php';

require_login("../index.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/reservation.php");
    exit();
}

$user_id = $_SESSION['id_user'];
$panier = $_SESSION['panier'] ?? [];

// collecte des donnees envoyees
$date_debut    = trim($_POST['date_debut'] ?? '');
$nom_groupe    = trim($_POST['nom_groupe'] ?? '');
$chambres_post = $_post['chambres'] ?? []; // format : [num_chambre => ['voyageurs' => [id1, id2], 'formules' => [id1 => f1]]]

// verification du panier de session
if (empty($panier)) {
    $_SESSION['error'] = "Votre panier est vide.";
    header("Location: ../pages/recherche.php");
    exit();
}

if (empty($date_debut) || empty($nom_groupe)) {
    $_SESSION['error'] = "Veuillez renseigner la date de debut de sejour ainsi que le groupe.";
    header("Location: ../pages/reservation.php");
    exit();
}

try {
    // securite et logique metier en backend

    // validation de la date: debut obligatoirement le dimanche
    $day_of_week = date('N', strtotime($date_debut)); // 7 = Dimanche
    if ($day_of_week != 7) {
        throw new Exception("Erreur de calendrier : La semaine de sejour de la station debute obligatoirement un Dimanche !");
    }

    // date de fin de sejour (dimanche + 6 jours = fin de location le samedi d'apres)
    $date_fin = date('Y-m-d', strtotime($date_debut . ' + 6 days'));

    // bloquer plusieurs skieurs dans le meme lit
    $all_selected_voyageurs = [];
    $active_chambres = []; // liste des numeros de chambres ou des skieurs ont ete coches

    foreach ($panier as $num_chambre) {
        $voy_ids = $chambres_post[$num_chambre]['voyageurs'] ?? [];
        if (!empty($voy_ids)) {
            $active_chambres[] = $num_chambre;
            foreach ($voy_ids as $id_client) {
                $id_client = intval($id_client);
                if (in_array($id_client, $all_selected_voyageurs)) {
                    // recuperer le nom du client frauduleux
                    $stmtName = $pdo->prepare("SELECT nom, prenom FROM client WHERE id_client = ?");
                    $stmtName->execute([$id_client]);
                    $row = $stmtName->fetch(PDO::FETCH_ASSOC);
                    $full_name = $row ? ($row['prenom'] . ' ' . $row['nom']) : "Skieur #$id_client";
                    throw new Exception("Un voyageur ne peut pas occuper plusieurs chambres en même temps ! Affectation multiple detectee pour {$full_name}.");
                }
                $all_selected_voyageurs[] = $id_client;
            }
        }
    }

    // s'assurer qu'au moins une personne est logee
    if (empty($all_selected_voyageurs)) {
        throw new Exception("Veuillez affecter au moins un membre de votre tribu à l'une de vos chambres.");
    }

    // controle des capacites en lits par chambre (en excluant les bebes)
    $stmtClient = $pdo->prepare("SELECT date_naissance FROM client WHERE id_client = ?");
    $stmtCapacity = $pdo->prepare("SELECT nb_lits FROM chambre WHERE num_chambre = ?");

    foreach ($active_chambres as $num_chambre) {
        $assigned_in_room = $chambres_post[$num_chambre]['voyageurs'] ?? [];

        $stmtCapacity->execute([$num_chambre]);
        $nb_lits = intval($stmtCapacity->fetchColumn());

        // compter les skieurs occupant reellement un lit physique (age >= 2 ans au debut du sejour)
        $lits_occupes = 0;
        foreach ($assigned_in_room as $id_client) {
            $stmtClient->execute([$id_client]);
            $dob = $stmtClient->fetchColumn();
            
            // calcul de l'age à partir de la date de debut de sejour
            $age = calculer_age($dob); // s'appuie sur la date courante du systeme ou date de debut
            
            $today = new DateTime($date_debut);
            $birthdate = new DateTime($dob);
            $age = $today->diff($birthdate)->y;

            if ($age >= 2) {
                $lits_occupes++;
            }
        }

        if ($lits_occupes > $nb_lits) {
            throw new Exception("La capacite maximale de la Chambre n°{$num_chambre} ({$nb_lits} lits max) est depassee par les occupants selectionnes.");
        }
    }

    // recuperation des prix de base depuis formule
    $stmtFormules = $pdo->query("SELECT type_formule, prix_base FROM formule");
    $formule_prices = $stmtFormules->fetchAll(PDO::FETCH_KEY_PAIR);

    // transaction sql postgresql atomique
    $pdo->beginTransaction();

    // ecriture de l'entite centrale 'reservation'
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

    // preparation des requetes de transaction
    $stmtReserver = $pdo->prepare("
        INSERT INTO reserver (id_client, id_reservation, num_chambre, type_formule, occupe_lit, formule_prix_final)
        VALUES (:id_client, :id_res, :num_chambre, :type, :occupe, :prix)
    ");

    $stmtFacturation = $pdo->prepare("
        INSERT INTO facturation (montant_total, id_reservation, num_chambre)
        VALUES (:total, :id_res, :num_chambre)
    ");

    // boucle d'insertion par chambre
    foreach ($active_chambres as $num_chambre) {
        $assigned_clients = $chambres_post[$num_chambre]['voyageurs'] ?? [];

        $stmtCapacity->execute([$num_chambre]);
        $nb_lits = intval($stmtCapacity->fetchColumn());

        $lits_occupes = 0;
        $prix_total_formules_chambre = 0;

        foreach ($assigned_clients as $id_client) {
            $stmtClient->execute([$id_client]);
            $dob = $stmtClient->fetchColumn();

            // age precis au moment du debut de sejour
            $ref_date = new DateTime($date_debut);
            $birthdate = new DateTime($dob);
            $age = $ref_date->diff($birthdate)->y;

            // logique de lit physique
            $occupe_lit = ($age >= 2);
            if ($occupe_lit) {
                $lits_occupes++;
            }

            // calcul du prix individuel de la formule
            $type_formule = $chambres_post[$num_chambre]['formules'][$id_client] ?? '';
            if (empty($type_formule)) {
                throw new Exception("Veuillez selectionner une formule pour chaque voyageur.");
            }

            $prix_base = $formule_prices[$type_formule] ?? 0;
            
            // regles tarifaires de zarza-ski
            if ($age < 2) {
                $prix_final = 0; // bebe gratuit
            } elseif ($age < 12) {
                $prix_final = intval(round($prix_base * 0.8)); // enfant -20%
            } else {
                $prix_final = intval($prix_base); // adulte plein tarif
            }

            $prix_total_formules_chambre += $prix_final;

            // ecriture de l'occupation dans 'reserver'
            $stmtReserver->execute([
                'id_client' => $id_client,
                'id_res' => $id_reservation,
                'num_chambre' => $num_chambre,
                'type' => $type_formule,
                'occupe' => $occupe_lit ? 'true' : 'false',
                'prix' => $prix_final
            ]);
        }

        // calcul de la facturation par hebergement (penalite de 150 € par lit vide)
        $lits_vides = $nb_lits - $lits_occupes;
        $montant_chambre = $prix_total_formules_chambre + ($lits_vides * 150);

        // insertion de la facture
        $stmtFacturation->execute([
            'total' => $montant_chambre,
            'id_res' => $id_reservation,
            'num_chambre' => $num_chambre
        ]);
    }

    //  validation finale et commit des ecritures
    $pdo->commit();

    // vider la selection du panier de session
    unset($_SESSION['panier']);

    $_SESSION['success'] = "Votre sejour a ete enregistre et valide avec succes. Retrouvez vos factures par chambre ci-dessous !";
    header("Location: ../pages/mes_reservations.php");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = "Erreur de validation de la reservation : " . $e->getMessage();
    header("Location: ../pages/reservation.php");
    exit();
}
