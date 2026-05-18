<?php
require_once __DIR__ . '/../includes/init.php';

// protection d'acces
if (!isset($_SESSION['id_user'])) {
    header("Location: /auth/login.php");
    exit();
}

$user_id = $_SESSION['id_user'];
$id_reservation = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_reservation <= 0) {
    $_SESSION['error'] = "Identifiant de reservation invalide.";
    header("Location: ../pages/mes_reservations.php");
    exit();
}

try {
    // verifier si la reservation existe et appartient bien à un groupe detenu par l'utilisateur connecte
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
        $_SESSION['error'] = "Action non autorisee : cette reservation ne vous appartient pas.";
        header("Location: ../pages/mes_reservations.php");
        exit();
    }

    // transaction postgresql suppression propre
    $pdo->beginTransaction();

    // grace aux cles de contraintes on delete cascade definies dans dump1.sql
    // les entrees correspondantes dans la table reserver sont nettoyees
    // les factures associees dans la table facturation sont nettoyees
    $stmtDelete = $pdo->prepare("DELETE FROM reservation WHERE id_reservation = ?");
    $stmtDelete->execute([$id_reservation]);

    $pdo->commit();
    $_SESSION['success'] = "La reservation a ete annulee avec succes. L'integralite des factures a ete creditee.";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = "Erreur technique lors de l'annulation du sejour : " . $e->getMessage();
}

header("Location: ../pages/mes_reservations.php");
exit();
