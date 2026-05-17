<?php
/**
 * Action PHP - Modifier Préférence - Zarza-Ski
 * Emplacement : src/actions/modifier_preference.php
 */

require_once __DIR__ . '/../includes/init.php';

// Récupère l'URL de redirection si elle existe, sinon retourne à la page par défaut
$redirect_target = isset($_GET['redirect']) ? trim($_GET['redirect']) : '../pages/preferences.php';
$redirect_target = sanitize_redirect_url($redirect_target);
$redirect_location = "Location: " . $redirect_target;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header($redirect_location);
    exit();
}

$user_id   = $_SESSION['id_user'];
$emetteur  = isset($_POST['id_client']) ? intval($_POST['id_client']) : 0;
$recepteur = isset($_POST['id_client_1']) ? intval($_POST['id_client_1']) : 0;
$niveau    = trim($_POST['niveau_preference'] ?? '');

if ($emetteur <= 0 || $recepteur <= 0 || empty($niveau)) {
    $_SESSION['error'] = "Informations d'affinité incomplètes.";
    header($redirect_location);
    exit();
}

try {
    // SÉCURITÉ : Vérifier que les deux voyageurs appartiennent bien au carnet de l'utilisateur connecté
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
        $_SESSION['error'] = "Action non autorisée : Les skieurs de la relation ne vous appartiennent pas.";
        header($redirect_location);
        exit();
    }

    // MISE À JOUR DU NIVEAU DE PRÉFÉRENCE
    $stmtUpdate = $pdo->prepare("
        UPDATE preference 
        SET niveau_preference = :niveau 
        WHERE id_client = :emetteur AND id_client_1 = :recepteur
    ");
    $stmtUpdate->execute([
        'niveau'    => $niveau,
        'emetteur'  => $emetteur,
        'recepteur' => $recepteur
    ]);

    $_SESSION['success'] = "L'affinité a été mise à jour avec succès.";

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de modification : " . $e->getMessage();
}

header($redirect_location);
exit();