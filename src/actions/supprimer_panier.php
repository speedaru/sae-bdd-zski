<?php
require_once __DIR__ . '/../includes/init.php';

// verification de connexion
if (!isset($_SESSION['id_user'])) {
    header("Location: /auth/login.php");
    exit();
}

$id_chambre = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_chambre > 0 && isset($_SESSION['panier']) && is_array($_SESSION['panier'])) {
    // recherche et suppression de l'identifiant dans le tableau
    $key = array_search($id_chambre, $_SESSION['panier']);
    if ($key !== false) {
        unset($_SESSION['panier'][$key]);
        // Réindexation propre du tableau
        $_SESSION['panier'] = array_values($_SESSION['panier']);
        $_SESSION['success'] = "Chambre n°{$id_chambre} retirée de votre sélection.";
    }
}

// redirection vers la page precedente ou vers l'affichage du panier
$referer = $_SERVER['HTTP_REFERER'] ?? '../pages/reservation.php';
header("Location: " . $referer);
exit();
