<?php
/**
 * Action PHP - Ajouter une chambre au panier
 * Emplacement : src/actions/ajouter_panier.php
 */

require_once __DIR__ . '/../includes/init.php';

// Protection d'accès de base (il est recommandé d'être connecté)
if (!isset($_SESSION['id_user'])) {
    $_SESSION['error'] = "Vezillez vous connecter pour ajouter une chambre à votre panier.";
    header("Location: /auth/login.php");
    exit();
}

$id_chambre = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_chambre <= 0) {
    $_SESSION['error'] = "Chambre invalide.";
    header("Location: ../pages/recherche.php");
    exit();
}

try {
    // SÉCURITÉ : Vérifier si la chambre existe bien en base
    $stmt = $pdo->prepare("SELECT 1 FROM chambre WHERE num_chambre = ?");
    $stmt->execute([$id_chambre]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "Cette chambre n'existe pas.";
        header("Location: ../pages/recherche.php");
        exit();
    }

    // Initialisation du panier en session s'il n'existe pas
    if (!isset($_SESSION['panier']) || !is_array($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }

    // Ajout de la chambre si elle n'est pas déjà présente
    if (!in_array($id_chambre, $_SESSION['panier'])) {
        $_SESSION['panier'][] = $id_chambre;
        $_SESSION['success'] = "Chambre n°{$id_chambre} ajoutée avec succès à votre sélection.";
    } else {
        $_SESSION['info'] = "La chambre n°{$id_chambre} est déjà dans votre panier.";
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Une erreur technique est survenue.";
}

// Redirection vers la page précédente ou par défaut vers la recherche
$referer = $_SERVER['HTTP_REFERER'] ?? '../pages/recherche.php';
header("Location: " . $referer);
exit();