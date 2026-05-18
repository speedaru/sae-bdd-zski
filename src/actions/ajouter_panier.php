<?php
require_once __DIR__ . '/../includes/init.php';

require_login(add_current_url_with_args());

$redirect_target = isset($_GET['redirect']) ? trim($_GET['redirect']) : '../index.php';
$redirect_target = sanitize_redirect_url($redirect_target);
$_SESSION['redirect_target'] = $redirect_target;

$id_chambre = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_chambre <= 0) {
    $_SESSION['error'] = "Chambre invalide.";
    header("Location: ../pages/recherche.php");
    exit();
}

try {
    // verifier si la chambre existe bien en base
    $stmt = $pdo->prepare("SELECT 1 FROM chambre WHERE num_chambre = ?");
    $stmt->execute([$id_chambre]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "Cette chambre n'existe pas.";
        header("Location: ../pages/recherche.php");
        exit();
    }

    // initialisation du panier s'il existe pas
    if (!isset($_SESSION['panier']) || !is_array($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }

    // ajout de la chambre si elle est pas deja presente
    if (!in_array($id_chambre, $_SESSION['panier'])) {
        $_SESSION['panier'][] = $id_chambre;
        $_SESSION['success'] = "Chambre n°{$id_chambre} ajoutee avec succes à votre selection.";
    } else {
        $_SESSION['info'] = "La chambre n°{$id_chambre} est dejà dans votre panier.";
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Une erreur technique est survenue.";
}

// redirection vers la page precedente
header("Location: " . $redirect_target);
exit();
