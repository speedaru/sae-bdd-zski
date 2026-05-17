<?php
/**
 * Action PHP - Création de groupe - Zarza-Ski
 * Emplacement : src/actions/ajouter_groupe.php
 */

require_once __DIR__ . '/../includes/init.php';

// Détection de la nature de la requête (AJAX ou Standard)
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

if (!is_logged_in()) {
    $msg = "Veuillez vous connecter pour créer un groupe.";
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
$redirect_target = isset($_GET['redirect']) ? trim($_GET['redirect']) : '../pages/groupes.php';
$redirect_target = sanitize_redirect_url($redirect_target);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . $redirect_target);
    exit();
}

$user_id = $_SESSION['id_user'];
$nom_groupe = trim($_POST['nom_groupe'] ?? '');

if (empty($nom_groupe)) {
    $msg = "Le nom du groupe ne peut pas être vide.";
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }
    $_SESSION['error'] = $msg;
    header("Location: " . $redirect_target);
    exit();
}

if (strlen($nom_groupe) > 48) {
    $msg = "Le nom du groupe ne doit pas dépasser 48 caractères.";
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
    // Insertion SQL du nouveau groupe
    $stmt = $pdo->prepare("INSERT INTO groupe (nom_groupe, id_user) VALUES (:nom, :id_user)");
    $stmt->execute([
        'nom' => $nom_groupe,
        'id_user' => $user_id
    ]);

    $msg = "Le groupe '" . h($nom_groupe) . "' a été créé avec succès !";
    
    // Si la requête est en AJAX, on retourne du JSON
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'nom_groupe' => $nom_groupe, 
            'message' => $msg
        ]);
        exit();
    }

    $_SESSION['success'] = $msg;

} catch (PDOException $e) {
    // Code PostgreSQL 23505 = Violation d'unicité (le groupe existe déjà)
    if ($e->getCode() === '23505') {
        $msg = "Le groupe '" . h($nom_groupe) . "' existe déjà au sein de la station.";
    } else {
        $msg = "Une erreur technique s'est produite lors de la création.";
    }

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }

    $_SESSION['error'] = $msg;
}

header("Location: " . $redirect_target);
exit();