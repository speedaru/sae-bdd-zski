<?php
require_once __DIR__ . '/../includes/init.php';

require_login("../index.php");

// recupere l'url de redirection si elle existe, sinon retourne à la page par defaut
$redirect_target = isset($_GET['redirect']) ? trim($_GET['redirect']) : '../pages/preferences.php';
$redirect_target = sanitize_redirect_url($redirect_target);
$redirect_location = "Location: " . $redirect_target;

$user_id   = $_SESSION['id_user'];
$emetteur  = isset($_GET['id_client']) ? intval($_GET['id_client']) : 0;
$recepteur = isset($_GET['id_client_1']) ? intval($_GET['id_client_1']) : 0;

if ($emetteur <= 0 || $recepteur <= 0) {
    $_SESSION['error'] = "Identifiants d'affinite manquants.";
    header($redirect_location);
    exit();
}

try {
    // verifier que la relation appartient bien au carnet de l'utilisateur connecte
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
        $_SESSION['error'] = "Action non autorisee : Les skieurs de la relation ne vous appartiennent pas.";
        header($redirect_location);
        exit();
    }

    // suppression de la ligne dans la table preference
    $stmtDelete = $pdo->prepare("DELETE FROM preference WHERE id_client = :emetteur AND id_client_1 = :recepteur");
    $stmtDelete->execute([
        'emetteur'  => $emetteur,
        'recepteur' => $recepteur
    ]);

    $_SESSION['success'] = "L'affinite a ete supprimee de vos preferences.";

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur technique de suppression : " . $e->getMessage();
}

header($redirect_location);
exit();
