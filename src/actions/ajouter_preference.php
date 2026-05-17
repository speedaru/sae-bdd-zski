<?php
/**
 * Action PHP - Ajouter Préférence - Zarza-Ski
 * Emplacement : src/actions/ajouter_preference.php
 */

require_once __DIR__ . '/../includes/init.php';

require_login("../index.php");

// Récupère l'URL de redirection si elle existe, sinon retourne à la page par défaut
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

// 1. SÉCURITÉ : Vérification de l'auto-préférence
if ($emetteur === $recepteur) {
    $_SESSION['error'] = "Un skieur ne peut pas définir de préférence envers lui-même.";
    header($redirect_location);
    exit();
}

if ($emetteur <= 0 || $recepteur <= 0 || empty($niveau)) {
    $_SESSION['error'] = "Veuillez configurer correctement l'affinité.";
    header($redirect_location);
    exit();
}

try {
    // 2. SÉCURITÉ : Vérifier que les deux voyageurs appartiennent bien au carnet de l'utilisateur connecté
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
        $_SESSION['error'] = "Action non autorisée : Les skieurs impliqués doivent faire partie de votre tribu.";
        header($redirect_location);
        exit();
    }

    // 3. SÉCURITÉ : Vérifier si la relation de cohabitation existe déjà (Doublons de clé primaire)
    $stmtCheckDup = $pdo->prepare("SELECT 1 FROM preference WHERE id_client = ? AND id_client_1 = ?");
    $stmtCheckDup->execute([$emetteur, $recepteur]);
    if ($stmtCheckDup->fetch()) {
        $_SESSION['error'] = "Cette relation d'affinité existe déjà entre ces deux voyageurs.";
        header($redirect_location);
        exit();
    }

    // 4. INSERTION DANS LA TABLE PREFERENCE
    $stmtInsert = $pdo->prepare("
        INSERT INTO preference (id_client, id_client_1, niveau_preference) 
        VALUES (:emetteur, :recepteur, :niveau)
    ");
    $stmtInsert->execute([
        'emetteur'  => $emetteur,
        'recepteur' => $recepteur,
        'niveau'    => $niveau
    ]);

    $_SESSION['success'] = "La préférence de cohabitation a été ajoutée avec succès !";

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur technique de création : " . $e->getMessage();
}

header($redirect_location);
exit();