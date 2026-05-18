<?php
require_once __DIR__ . '/../includes/init.php';

// protection d'acces
if (!isset($_SESSION['id_user'])) {
    header("Location: /auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/groupes.php");
    exit();
}

$user_id = $_SESSION['id_user'];
$ancien_nom = trim($_POST['ancien_nom_groupe'] ?? '');
$nouveau_nom = trim($_POST['nom_groupe'] ?? '');

if (empty($ancien_nom) || empty($nouveau_nom)) {
    $_SESSION['error'] = "Le nom du groupe ne peut pas être vide.";
    header("Location: ../pages/groupes.php");
    exit();
}

if (strlen($nouveau_nom) > 48) {
    $_SESSION['error'] = "Le nom du groupe ne doit pas depasser 48 caracteres.";
    header("Location: ../pages/groupes.php");
    exit();
}

try {
    $pdo->beginTransaction();

    // verifier que l'utilisateur est bien le proprietaire de l'ancien groupe
    $stmtCheck = $pdo->prepare("SELECT 1 FROM groupe WHERE nom_groupe = :ancien AND id_user = :id_user");
    $stmtCheck->execute([
        'ancien' => $ancien_nom,
        'id_user' => $user_id
    ]);

    if (!$stmtCheck->fetch()) {
        throw new Exception("Action non autorisee ou groupe inexistant.");
    }

    // update du groupe, la modification va cascader sur les reservations grace a on update cascade
    $stmtUpdate = $pdo->prepare("UPDATE groupe SET nom_groupe = :nouveau WHERE nom_groupe = :ancien AND id_user = :id_user");
    $stmtUpdate->execute([
        'nouveau' => $nouveau_nom,
        'ancien'  => $ancien_nom,
        'id_user' => $user_id
    ]);

    $pdo->commit();
    $_SESSION['success'] = "Le groupe '" . h($ancien_nom) . "' a ete renomme en '" . h($nouveau_nom) . "' avec succes !";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // nom deja utilise par un autre groupe
    if ($e instanceof PDOException && $e->getCode() === '23505') {
        $_SESSION['error'] = "Le nom de groupe '" . h($nouveau_nom) . "' est dejà utilise.";
    } else {
        $_SESSION['error'] = $e->getMessage();
    }
}

header("Location: ../pages/groupes.php");
exit();
