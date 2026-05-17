<?php
/**
 * Action PHP - Validation de la Réservation (Calculs & Sécurités Backend)
 * Emplacement : src/actions/valider_sejour.php
 */

require_once __DIR__ . '/../includes/init.php';

require_login("../index.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/reservation.php");
    exit();
}

$user_id = $_SESSION['id_user'];
$panier = $_SESSION['panier'] ?? [];

// 1. COLLECTE DES DONNÉES ENVOYÉES
$date_debut  = trim($_POST['date_debut'] ?? '');
$nom_groupe  = trim($_POST['nom_groupe'] ?? '');
$voyageurs_selectionnes = $_POST['voyageurs_selectionnes'] ?? []; // Array d'ID clients qui participent
$formules    = $_POST['formules'] ?? [];    // associative : [id_client => type_formule]
$affectations = $_POST['affectations'] ?? []; // associative : [id_client => num_chambre]

// Vérification de panier
if (empty($panier)) {
    $_SESSION['error'] = "Votre panier de sélection est vide.";
    header("Location: ../pages/recherche.php");
    exit();
}

if (empty($date_debut) || empty($nom_groupe)) {
    $_SESSION['error'] = "Veuillez renseigner la date de début de votre séjour ainsi que votre groupe.";
    header("Location: ../pages/reservation.php");
    exit();
}

if (empty($voyageurs_selectionnes)) {
    $_SESSION['error'] = "Veuillez cocher au moins un participant pour partir au séjour.";
    header("Location: ../pages/reservation.php");
    exit();
}

try {
    // 2. VALIDATIONS ET LOGIQUE MÉTIER STRICTE

    // A. Validation de la date (doit débuter obligatoirement un Samedi)
    $day_of_week = date('N', strtotime($date_debut)); // 6 = Samedi
    if ($day_of_week != 6) {
        throw new Exception("La date de début du séjour doit obligatoirement être un Samedi !");
    }

    // Calcul de la fin de séjour (Samedi suivant)
    $date_fin = date('Y-m-d', strtotime($date_debut . ' + 7 days'));

    // B. Groupement des occupants par chambre du panier & validation de l'assignation
    $chambres_occupants = [];
    foreach ($panier as $num_chambre) {
        $chambres_occupants[$num_chambre] = [];
    }

    foreach ($voyageurs_selectionnes as $v_id) {
        $v_id = intval($v_id);
        $assigned_room = isset($affectations[$v_id]) ? intval($affectations[$v_id]) : 0;

        if ($assigned_room <= 0 || !in_array($assigned_room, $panier)) {
            // Un participant est coché pour partir mais n'a pas été placé dans une chambre valide du panier
            $stmtClientName = $pdo->prepare("SELECT nom, prenom FROM client WHERE id_client = ?");
            $stmtClientName->execute([$v_id]);
            $client_row = $stmtClientName->fetch(PDO::FETCH_ASSOC);
            $full_name = $client_row ? ($client_row['prenom'] . ' ' . $client_row['nom']) : "ID #$v_id";
            throw new Exception("Veuillez assigner une chambre valide de votre panier à {$full_name}.");
        }

        $chambres_occupants[$assigned_room][] = $v_id;
    }

    // C. Validation des capacités maximales des lits par chambre réservée
    $stmtClient  = $pdo->prepare("SELECT date_naissance FROM client WHERE id_client = ?");
    $stmtCapacity = $pdo->prepare("SELECT nb_lits FROM chambre WHERE num_chambre = ?");

    foreach ($chambres_occupants as $num_chambre => $occupants) {
        if (empty($occupants)) {
            continue; // Chambre du panier laissée vide (sera comptée avec pénalité totale)
        }

        $stmtCapacity->execute([$num_chambre]);
        $nb_lits = intval($stmtCapacity->fetchColumn());

        // Compter uniquement les occupants qui occupent réellement un lit (âge >= 2 ans)
        $lits_occupes = 0;
        foreach ($occupants as $id_client) {
            $stmtClient->execute([$id_client]);
            $dob = $stmtClient->fetchColumn();
            
            $age = calculer_age($dob);
            if ($age >= 2) {
                $lits_occupes++;
            }
        }

        if ($lits_occupes > $nb_lits) {
            throw new Exception("La capacité maximale de la Chambre n°{$num_chambre} ({$nb_lits} lits max) est dépassée.");
        }
    }

    // 3. RECUPERATION DES PRIX DE BASE DEPUIS FORMULE
    $stmtFormules = $pdo->query("SELECT type_formule, prix_base FROM formule");
    $formule_prices = $stmtFormules->fetchAll(PDO::FETCH_KEY_PAIR);

    // 4. EXÉCUTION DE LA TRANSACTION ATOMIQUE (POSTGRESQL)
    $pdo->beginTransaction();

    // ÉTAPE A : Insertion globale dans la table reservation
    $stmtInsertRes = $pdo->prepare("
        INSERT INTO reservation (date_debut, date_fin, nom_groupe) 
        VALUES (:debut, :fin, :groupe)
    ");
    $stmtInsertRes->execute([
        'debut'  => $date_debut,
        'fin'    => $date_fin,
        'groupe' => $nom_groupe
    ]);
    
    // Récupération de l'identifiant de la réservation
    $id_reservation = $pdo->lastInsertId();

    // Requêtes préparées réutilisables pour optimiser les performances de la transaction
    $stmtReserver = $pdo->prepare("
        INSERT INTO reserver (id_client, id_reservation, num_chambre, type_formule, occupe_lit, formule_prix_final)
        VALUES (:id_client, :id_res, :num_chambre, :type, :occupe, :prix)
    ");

    $stmtFacturation = $pdo->prepare("
        INSERT INTO facturation (montant_total, id_reservation, num_chambre)
        VALUES (:total, :id_res, :num_chambre)
    ");

    // ÉTAPE B : Traitement de chaque chambre du panier
    foreach ($chambres_occupants as $num_chambre => $assigned_clients) {
        
        $stmtCapacity->execute([$num_chambre]);
        $nb_lits = intval($stmtCapacity->fetchColumn());

        $lits_occupes = 0;
        $prix_total_formules_chambre = 0;

        foreach ($assigned_clients as $id_client) {
            $stmtClient->execute([$id_client]);
            $dob = $stmtClient->fetchColumn();

            $age = calculer_age($dob);
            
            // Un bébé de moins de 2 ans n'occupe pas de lit à la station
            $occupe_lit = ($age >= 2);
            if ($occupe_lit) {
                $lits_occupes++;
            }

            // Récupération et calcul du tarif adapté (Enfant -20%, Bébé Gratuit)
            $type_formule = $formules[$id_client] ?? '';
            if (empty($type_formule)) {
                throw new Exception("Une formule valide doit être associée pour chaque voyageur.");
            }

            $prix_base = $formule_prices[$type_formule] ?? 0;
            $prix_final = calculer_prix_indiv($prix_base, $dob);
            
            $prix_total_formules_chambre += $prix_final;

            // Insertion de la liaison d'occupation
            $stmtReserver->execute([
                'id_client'   => $id_client,
                'id_res'      => $id_reservation,
                'num_chambre' => $num_chambre,
                'type'        => $type_formule,
                'occupe'      => $occupe_lit ? 'true' : 'false',
                'prix'        => $prix_final
            ]);
        }

        // ÉTAPE C : Calcul de la facturation par hébergement (Pénalité de 150 € par lit libre)
        $lits_vides = $nb_lits - $lits_occupes;
        $montant_chambre = $prix_total_formules_chambre + ($lits_vides * 150);

        // Insertion du reçu de facturation
        $stmtFacturation->execute([
            'total'       => $montant_chambre,
            'id_res'      => $id_reservation,
            'num_chambre' => $num_chambre
        ]);
    }

    // ÉTAPE D : Validation finale et commit atomique
    $pdo->commit();

    // Vider le panier
    unset($_SESSION['panier']);

    $_SESSION['success'] = "Félicitations ! Votre réservation de séjour à la station Zarza-Ski a été enregistrée avec succès.";
    header("Location: ../pages/mes_reservations.php");
    exit();

} catch (Exception $e) {
    // Annulation complète des écritures
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = "Erreur de validation de votre séjour : " . $e->getMessage();
    header("Location: ../pages/reservation.php");
    exit();
}