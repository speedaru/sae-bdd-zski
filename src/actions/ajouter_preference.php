<?php
require_once __DIR__ . '/../includes/init.php';

require_login("../index.php");

$redirect_target = isset($_GET['redirect']) ? trim($_GET['redirect']) : '../pages/preferences.php';
$redirect_target = sanitize_redirect_url($redirect_target);
$redirect_location = "Location: " . $redirect_target;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header($redirect_location);
    exit();
}

$user_id = $_SESSION['id_user'];
$emetteur  = isset($_POST['id_client']) ? intval($_POST['id_client']) : 0;
$recepteur = isset($_POST['id_client_1']) ? intval($_POST['id_client_1']) : 0;
$niveau    = trim($_POST['niveau_preference'] ?? '');

// verification de l'auto-preference
if ($emetteur === $recepteur) {
    $_SESSION['error'] = "Un skieur ne peut pas definir de preference envers lui-même.";
    header($redirect_location);
    exit();
}

if ($emetteur <= 0 || $recepteur <= 0 || empty($niveau)) {
    $_SESSION['error'] = "Veuillez configurer correctement l'affinite.";
    header($redirect_location);
    exit();
}

try {
    // verifier que les deux voyageurs appartiennent bien au carnet de l'utilisateur connecte
    $stmtCheckTribu = $pdo->prepare("
        SELECT COUNT(*) 
        FROM gestion_voyageurs 
        WHERE id_user = :id_user AND id_client IN (:emetteur, :recepteur)
    ");
    $stmtCheckTribu->execute([
        'id_user'   => $user_id,
        'emetteur'  => $emetteur,
        'recepteur' => $recepteur
    ]);
    
    if (intval($stmtCheckTribu->fetchColumn()) < 2) {
        $_SESSION['error'] = "Action non autorisee : Les skieurs impliques doivent faire partie de votre tribu.";
        header($redirect_location);
        exit();
    }

    // verifier si la relation de cohabitation existe dejà (doublons de cle primaire)
    $stmtCheckDup = $pdo->prepare("SELECT 1 FROM preference WHERE id_client = ? AND id_client_1 = ?");
    $stmtCheckDup->execute([$emetteur, $recepteur]);
    if ($stmtCheckDup->fetch()) {
        $_SESSION['error'] = "Cette relation d'affinite existe dejà entre ces deux voyageurs.";
        header($redirect_location);
        exit();
    }

    // insertion dans la table preference
    $stmtInsert = $pdo->prepare("
        INSERT INTO preference (id_client, id_client_1, niveau_preference) 
        VALUES (:emetteur, :recepteur, :niveau)
    ");
    $stmtInsert->execute([
        'emetteur'  => $emetteur,
        'recepteur' => $recepteur,
        'niveau'    => $niveau
    ]);

    $_SESSION['success'] = "La preference de cohabitation a ete ajoutee avec succes !";

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur technique de creation : " . $e->getMessage();
}

header($redirect_location);
exit();
