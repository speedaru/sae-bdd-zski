<?php
/**
 * Action PHP - Création de groupe - Zarza-Ski
 * Emplacement : src/actions/ajouter_groupe.php
 */

require_once __DIR__ . '/../includes/init.php';

require_login("../index.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/groupes.php");
    exit();
}

$user_id = $_SESSION['id_user'];
$nom_groupe = trim($_POST['nom_groupe'] ?? '');

if (empty($nom_groupe)) {
    $_SESSION['error'] = "Le nom du groupe ne peut pas être vide.";
    header("Location: ../pages/groupes.php");
    exit();
}

if (strlen($nom_groupe) > 48) {
    $_SESSION['error'] = "Le nom du groupe ne doit pas dépasser 48 caractères.";
    header("Location: ../pages/groupes.php");
    exit();
}

try {
    // Insertion SQL du nouveau groupe
    $stmt = $pdo->prepare("INSERT INTO groupe (nom_groupe, id_user) VALUES (:nom, :id_user)");
    $stmt->execute([
        'nom' => $nom_groupe,
        'id_user' => $user_id
    ]);

    $_SESSION['success'] = "Le groupe '" . h($nom_groupe) . "' a été créé avec succès !";

} catch (PDOException $e) {
    // Code PostgreSQL 23505 = Violation d'unicité (le groupe existe déjà)
    if ($e->getCode() === '23505') {
        $_SESSION['error'] = "Le groupe '" . h($nom_groupe) . "' existe déjà au sein de la station.";
    } else {
        $_SESSION['error'] = "Une erreur technique s'est produite lors de la création.";
    }
}

header("Location: ../pages/groupes.php");
exit();