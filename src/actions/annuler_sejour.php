<?php
/**
 * Action PHP - Annulation complète d'un séjour - Zarza-Ski
 * Emplacement : src/actions/annuler_sejour.php
 */

require_once __DIR__ . '/../includes/init.php';

// Protection d'accès
if (!isset($_SESSION['id_user'])) {
    header("Location: /auth/login.php");
    exit();
}

$user_id = $_SESSION['id_user'];
$id_reservation = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_reservation <= 0) {
    $_SESSION['error'] = "Identifiant de réservation invalide.";
    header("Location: ../pages/mes_reservations.php");
    exit();
}

try {
    // SÉCURITÉ : Vérifier si la réservation existe et appartient bien à un groupe détenu par l'utilisateur connecté
    $stmtCheck = $pdo->prepare("
        SELECT 1 
        FROM reservation r
        INNER JOIN groupe g ON r.nom_groupe = g.nom_groupe
        WHERE r.id_reservation = :id_res AND g.id_user = :id_user
    ");
    $stmtCheck->execute([
        'id_res'  => $id_reservation,
        'id_user' => $user_id
    ]);

    if (!$stmtCheck->fetch()) {
        $_SESSION['error'] = "Action non autorisée : cette réservation ne vous appartient pas.";
        header("Location: ../pages/mes_reservations.php");
        exit();
    }

    // TRANSACTION POSTGRESQL : Suppression propre
    $pdo->beginTransaction();

    // Grâce aux clés de contraintes ON DELETE CASCADE définies dans dump1.sql :
    // - Les entrées correspondantes dans la table 'reserver' sont nettoyées
    // - Les factures associées dans la table 'facturation' sont nettoyées
    $stmtDelete = $pdo->prepare("DELETE FROM reservation WHERE id_reservation = ?");
    $stmtDelete->execute([$id_reservation]);

    $pdo->commit();
    $_SESSION['success'] = "La réservation a été annulée avec succès. L'intégralité des factures a été créditée.";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = "Erreur technique lors de l'annulation du séjour : " . $e->getMessage();
}

header("Location: ../pages/mes_reservations.php");
exit();