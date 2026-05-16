<?php
/**
 * Action PHP - Supprimer un voyageur du carnet
 * Emplacement : src/actions/supprimer_voyageur.php
 * Supprime de manière sécurisée le lien d'un voyageur de la tribu (conserve la fiche client pour l'historique).
 */

require_once __DIR__ . '/../includes/init.php';

require_login("../index.php");

$user_id = $_SESSION['id_user'];
$client_id_to_delete = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($client_id_to_delete <= 0) {
    $_SESSION['error'] = "Identifiant de voyageur invalide.";
    header("Location: ../pages/carnet.php");
    exit();
}

// 2. SÉCURITÉ : Empêcher un utilisateur de supprimer son propre profil skieur principal
if (isset($_SESSION['id_client']) && $client_id_to_delete === intval($_SESSION['id_client'])) {
    $_SESSION['error'] = "Vous ne pouvez pas supprimer votre propre profil skieur principal.";
    header("Location: ../pages/carnet.php");
    exit();
}

try {
    // 3. SÉCURITÉ : Vérifier si ce voyageur appartient bien au carnet d'adresses de cet utilisateur
    $stmtCheck = $pdo->prepare("SELECT 1 FROM gestion_voyageurs WHERE id_user = :id_user AND id_client = :id_client");
    $stmtCheck->execute([
        'id_user' => $user_id,
        'id_client' => $client_id_to_delete
    ]);
    
    if (!$stmtCheck->fetch()) {
        $_SESSION['error'] = "Action non autorisée : ce voyageur ne fait pas partie de votre tribu.";
        header("Location: ../pages/carnet.php");
        exit();
    }

    // Suppression du lien d'appartenance dans la table de pivot
    $stmtDeleteLink = $pdo->prepare("DELETE FROM gestion_voyageurs WHERE id_user = :id_user AND id_client = :id_client");
    $stmtDeleteLink->execute([
        'id_user' => $user_id,
        'id_client' => $client_id_to_delete
    ]);

    $_SESSION['success'] = "Le voyageur a été supprimé de votre tribu avec succès.";

} catch (PDOException $e) {
    // Annulation de la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = "Erreur technique lors de la suppression : " . $e->getMessage();
}

// Redirection vers le carnet
header("Location: ../pages/carnet.php");
exit();