<?php
/**
 * Action PHP - Suppression de groupe - Zarza-Ski
 * Emplacement : src/actions/supprimer_groupe.php
 */

require_once __DIR__ . '/../includes/init.php';

require_login("../index.php");

$user_id = $_SESSION['id_user'];
$nom_groupe = isset($_GET['nom']) ? trim($_GET['nom']) : '';

if (empty($nom_groupe)) {
    $_SESSION['error'] = "Identifiant de groupe manquant.";
    header("Location: ../pages/groupes.php");
    exit();
}

try {
    // SÉCURITÉ : Vérifier que l'utilisateur connecté possède bien ce groupe
    $stmtCheck = $pdo->prepare("SELECT 1 FROM groupe WHERE nom_groupe = :nom AND id_user = :id_user");
    $stmtCheck->execute([
        'nom' => $nom_groupe,
        'id_user' => $user_id
    ]);

    if (!$stmtCheck->fetch()) {
        $_SESSION['error'] = "Action non autorisée : ce groupe ne vous appartient pas.";
        header("Location: ../pages/groupes.php");
        exit();
    }

    // Suppression physique (Cascade PostgreSQL configurée sur ON DELETE CASCADE dans dump1.sql)
    $stmtDelete = $pdo->prepare("DELETE FROM groupe WHERE nom_groupe = :nom AND id_user = :id_user");
    $stmtDelete->execute([
        'nom' => $nom_groupe,
        'id_user' => $user_id
    ]);

    $_SESSION['success'] = "Le groupe '" . h($nom_groupe) . "' et toutes ses dépendances ont été supprimés.";

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur technique lors de la suppression : " . $e->getMessage();
}

header("Location: ../pages/groupes.php");
exit();