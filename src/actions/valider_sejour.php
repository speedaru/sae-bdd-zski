<?php
/**
 * Action PHP - Validation de la Réservation (Transaction Complexe)
 * Emplacement : src/actions/valider_sejour.php
 */

require_once __DIR__ . '/../includes/init.php';

// Protection d'accès
if (!isset($_SESSION['id_user'])) {
    header("Location: /auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/reservation.php");
    exit();
}

$user_id = $_SESSION['id_user'];
$panier = $_SESSION['panier'] ?? [];

// 1. COLLECTE ET VÉRIFICATION INITIALE DES DONNÉES
$date_debut  = trim($_POST['date_debut'] ?? '');
$nom_groupe  = trim($_POST['nom_groupe'] ?? '');
$assignments = $_POST['assignments'] ?? []; // format: [num_chambre => [id_client1, id_client2]]
$formules    = $_POST['formules'] ?? [];    // format: [num_chambre => [id_client => type_formule]]

if (empty($panier)) {
    $_SESSION['error'] = "Votre panier est vide.";
    header("Location: ../pages/recherche.php");
    exit();
}

if (empty($date_debut) || empty($nom_groupe) || empty($assignments)) {
    $_SESSION['error'] = "Veuillez configurer entièrement votre séjour et affecter les chambres.";
    header("Location: ../pages/reservation.php");
    exit();
}

// 2. SÉCURITÉ METIER : Validation de la date de début (doit être un Samedi)
$day_of_week = date('N', strtotime($date_debut)); // 1 (Lundi) à 7 (Dimanche)
if ($day_of_week != 6) {
    $_SESSION['error'] = "Erreur : La date de début du séjour doit obligatoirement être un Samedi !";
    header("Location: ../pages/reservation.php");
    exit();
}

// Calcul de la date de fin de séjour (date_debut + 7 jours)
$date_fin = date('Y-m-d', strtotime($date_debut . ' + 7 days'));

try {
    // 3. RECUPERATION DES TARIFS DE BASE DEPUIS FORMULE
    $stmtFormules = $pdo->query("SELECT type_formule, prix_base FROM formule");
    $formule_prices = $stmtFormules->fetchAll(PDO::FETCH_KEY_PAIR);

    // 4. DEBUT DE LA TRANSACTION COMPLEXE POSTGRESQL (Niveau d'isolation par défaut)
    $pdo->beginTransaction();

    // Étape A : Insertion de l'entité globale 'reservation'
    $stmtRes = $pdo->prepare("
        INSERT INTO reservation (date_debut, date_fin, nom_groupe) 
        VALUES (:debut, :fin, :groupe)
    ");
    $stmtRes->execute([
        'debut'  => $date_debut,
        'fin'    => $date_fin,
        'groupe' => $nom_groupe
    ]);
    
    // Récupération de l'ID généré par la séquence SERIAL
    $id_reservation = $pdo->lastInsertId();

    // Requêtes préparées réutilisables
    $stmtChambre = $pdo->prepare("SELECT nb_lits FROM chambre WHERE num_chambre = ?");
    $stmtClient  = $pdo->prepare("SELECT date_naissance FROM client WHERE id_client = ?");
    
    $stmtReserver = $pdo->prepare("
        INSERT INTO reserver (id_client, id_reservation, num_chambre, type_formule, occupe_lit, formule_prix_final)
        VALUES (:id_client, :id_res, :num_chambre, :type, :occupe, :prix)
    ");

    $stmtFacturation = $pdo->prepare("
        INSERT INTO facturation (montant_total, date_emission, id_reservation, num_chambre)
        VALUES (:total, CURRENT_DATE, :id_res, :num_chambre)
    ");

    // Étape B : Boucle de traitement par chambre du panier
    foreach ($panier as $num_chambre) {
        $assigned_clients = $assignments[$num_chambre] ?? [];

        // Récupérer la capacité de la chambre
        $stmtChambre->execute([$num_chambre]);
        $nb_lits = intval($stmtChambre->fetchColumn());

        $lits_occupes = 0;
        $prix_total_formules_chambre = 0;

        // Traitement de chaque voyageur affecté à cette chambre
        foreach ($assigned_clients as $id_client) {
            // Récupérer la date de naissance pour le calcul des tarifs métiers
            $stmtClient->execute([$id_client]);
            $date_naissance = $stmtClient->fetchColumn();
            
            $age = calculer_age($date_naissance);
            
            // Règle d'occupation du lit
            $occupe_lit = ($age >= 2); // Un bébé (<2 ans) ne compte pas pour l'occupation physique des lits
            
            if ($occupe_lit) {
                $lits_occupes++;
            }

            // Récupération du prix brut de la formule choisie
            $type_formule = $formules[$num_chambre][$id_client] ?? '';
            if (empty($type_formule)) {
                throw new Exception("Une formule doit être sélectionnée pour chaque voyageur.");
            }
            
            $prix_base = $formule_prices[$type_formule] ?? 0;
            
            // Calcul du prix individuel adapté selon les remises de la station
            $prix_final = calculer_prix_indiv($prix_base, $date_naissance);
            $prix_total_formules_chambre += $prix_final;

            // Insertion dans la table d'occupation des lits 'reserver'
            $stmtReserver->execute([
                'id_client'   => $id_client,
                'id_res'      => $id_reservation,
                'num_chambre' => $num_chambre,
                'type'        => $type_formule,
                'occupe'      => $occupe_lit ? 'true' : 'false',
                'prix'        => $prix_final
            ]);
        }

        // SÉCURITÉ TECHNIQUE : Contrôle de débordement de capacité
        if ($lits_occupes > $nb_lits) {
            throw new Exception("Capacité dépassée : la chambre n°{$num_chambre} ne contient que {$nb_lits} lits physiques.");
        }

        // Étape C : Calcul de la facturation pour la chambre
        $lits_vides = $nb_lits - $lits_occupes;
        $total_chambre_facture = $prix_total_formules_chambre + ($lits_vides * 150);

        // Insertion du reçu de facturation par chambre
        $stmtFacturation->execute([
            'total'       => $total_chambre_facture,
            'id_res'      => $id_reservation,
            'num_chambre' => $num_chambre
        ]);
    }

    // Étape D : Validation finale et commit atomique
    $pdo->commit();

    // Nettoyage complet du panier
    unset($_SESSION['panier']);

    $_SESSION['success'] = "Félicitations ! Votre séjour à la station Zarza-Ski est validé. Retrouvez vos factures ci-dessous.";
    header("Location: ../pages/mes_reservations.php");
    exit();

} catch (Exception $e) {
    // Annulation immédiate en cas d'erreur de transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = "Erreur de validation de la réservation : " . $e->getMessage();
    header("Location: ../pages/reservation.php");
    exit();
}