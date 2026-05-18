<?php
/**
 * Action PHP - Ajouter Voyageur au Carnet
 * Emplacement : src/actions/ajouter_voyageur.php
 */

require_once __DIR__ . '/../includes/init.php';

// Détection de la nature de la requête
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

if (!is_logged_in()) {
    $msg = "Veuillez vous connecter pour ajouter un proche.";
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }
    $_SESSION['error'] = $msg;
    header("Location: ../auth/login.php");
    exit();
}

// Récupère l'URL de redirection si elle existe, sinon retourne à la page par défaut
$redirect_target = isset($_GET['redirect']) ? trim($_GET['redirect']) : '../pages/carnet.php';
$redirect_target = sanitize_redirect_url($redirect_target);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . $redirect_target);
    exit();
}

$user_id = $_SESSION['id_user'];

// Récupération et nettoyage des données POST
$nom = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$date_naissance = trim($_POST['date_naissance'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');
$num_tel = trim($_POST['num_tel'] ?? '');
$niveau_ski = $_POST['niveau_ski'] ?? 'débutant';
$taille = floatval($_POST['taille'] ?? 0);
$poids = intval($_POST['poids'] ?? 0);
$pointure = floatval($_POST['pointure'] ?? 0);

// Validation
if (empty($nom) || empty($prenom) || empty($date_naissance) || empty($adresse) || empty($num_tel) || $taille <= 0 || $poids <= 0 || $pointure <= 0) {
    $msg = "Veuillez remplir correctement tous les champs obligatoires.";
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }
    $_SESSION['error'] = $msg;
    header("Location: " . $redirect_target);
    exit();
}

try {
    // TRANSACTION POSTGRESQL de sécurité
    $pdo->beginTransaction();

    // Insertion du voyageur avec sa date de naissance
    $sqlClient = "INSERT INTO client (nom, prenom, date_naissance, adresse, num_tel, niveau_ski, taille, poids, pointure) 
                  VALUES (:nom, :prenom, :date_naissance, :adresse, :num_tel, :niveau_ski, :taille, :poids, :pointure)";
                  
    $stmtClient = $pdo->prepare($sqlClient);
    $stmtClient->execute([
        'nom' => $nom,
        'prenom' => $prenom,
        'date_naissance' => $date_naissance,
        'adresse' => $adresse,
        'num_tel' => $num_tel,
        'niveau_ski' => $niveau_ski,
        'taille' => $taille,
        'poids' => $poids,
        'pointure' => $pointure
    ]);

    $id_client_cree = $pdo->lastInsertId();

    // Liaison dans la table d'association d'adresses
    $sqlLiaison = "INSERT INTO gestion_voyageurs (id_user, id_client) VALUES (:id_user, :id_client)";
    $stmtLiaison = $pdo->prepare($sqlLiaison);
    $stmtLiaison->execute([
        'id_user' => $user_id,
        'id_client' => $id_client_cree
    ]);

    $pdo->commit();

    $msg = "Le membre " . h($prenom) . " " . h($nom) . " a été ajouté à votre tribu !";

    // Envoi des métadonnées complètes au frontend AJAX
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'id_client' => $id_client_cree,
            'nom' => $nom,
            'prenom' => $prenom,
            'date_naissance' => $date_naissance,
            'message' => $msg
        ]);
        exit();
    }

    $_SESSION['success'] = $msg;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $msg = "Erreur technique lors de l'enregistrement de votre proche.";
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }
    
    $_SESSION['error'] = $msg;
}

header("Location: " . $redirect_target);
exit();