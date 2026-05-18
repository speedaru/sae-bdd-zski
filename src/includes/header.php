<?php
/**
 * Header Global - Zarza-Ski
 * Gère l'affichage dynamique des menus et l'ouverture de session - Version Académique Épurée.
 * Emplacement : src/includes/header.php
 */
require_once 'init.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarza-Ski - <?php echo ucfirst(str_replace('_', ' ', $current_page)); ?></title>

    <!-- CSS COMMUN ÉPURÉ SANS FRAMEWORKS -->
    <link rel="stylesheet" href="/assets/css/common.css"> 
    <link rel="stylesheet" href="/assets/css/header.css"> 

    <!-- CSS DYNAMIQUE (Chargé uniquement si le fichier existe pour cette page) -->
    <?php 
    $page_css_path = $base_url . "assets/css/pages/{$current_page}.css";
    if (file_exists($page_css_path)): ?>
        <link rel="stylesheet" href="<?php echo $page_css_path; ?>">
    <?php endif; ?>
</head>

<body class="page-<?php echo $current_page; ?>">

    <header class="academic-header">
        <!-- Section Gauche : Marque et Liens de Navigation -->
        <div class="header-left">
            <a class="header-brand" href="<?php echo $base_url; ?>index.php">
                ▲ Zarza-Ski
            </a>
            <nav class="header-nav-container">
                <ul class="header-nav">
                    <li><a class="header-nav-link <?php echo $current_page == 'index' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>index.php">Accueil</a></li>
                    <li><a class="header-nav-link <?php echo $current_page == 'recherche' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>pages/recherche.php">Réserver</a></li>
                    <li><a class="header-nav-link <?php echo $current_page == 'vues' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>pages/vues.php">Vues</a></li>
                </ul>
            </nav>
        </div>

        <!-- Section Droite : Panier et Espace Client -->
        <div class="header-right">
            <?php if (isset($_SESSION['id_user'])): ?>
                <!-- Affichage du Panier -->
                <a class="header-cart-btn" href="<?php echo $base_url; ?>pages/reservation.php">
                    🛒 Mon Panier
                    <?php 
                    $panier_count = get_panier_count();
                    if ($panier_count > 0): 
                    ?>
                        <span class="header-cart-badge">
                            <?php echo $panier_count; ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Dropdown Espace Client en CSS Pur -->
                <div class="header-user-dropdown">
                    <button class="header-dropdown-trigger">
                        👤 <?php echo h($_SESSION['username']); ?> <span class="arrow">&#9662;</span>
                    </button>
                    <ul class="header-dropdown-menu">
                        <li><a href="<?php echo $base_url; ?>pages/tableau_de_bord.php">Mon espace client</a></li>
                        <li class="separator"></li>
                        <li><a class="logout-action" href="<?php echo $base_url; ?>auth/logout.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Déconnexion</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <!-- Liens de connexion anonyme -->
                <a class="header-link-login" href="<?php echo $base_url; ?>auth/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Connexion</a>
                <a class="header-btn-register" href="<?php echo $base_url; ?>auth/register.php">Inscription</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Conteneur principal de page -->
    <main class="academic-main-container">