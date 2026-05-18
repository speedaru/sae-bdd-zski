<?php
/**
 * header global
 * gere l'affichage dynamique des menus et l'ouverture de session - version academique epuree.
 */
require_once 'init.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarza-Ski - <?php echo ucfirst(str_replace('_', ' ', $current_page)); ?></title>

    <link rel="stylesheet" href="/assets/css/common.css"> 
    <link rel="stylesheet" href="/assets/css/header.css"> 

    <!-- css dynamique -->
    <?php 
    $page_css_path = $base_url . "assets/css/pages/{$current_page}.css";
    if (file_exists($page_css_path)): ?>
        <link rel="stylesheet" href="<?php echo $page_css_path; ?>">
    <?php endif; ?>
</head>

<body class="page-<?php echo $current_page; ?>">

    <header class="academic-header">
        <!-- section gauche: marque et liens de navigation -->
        <div class="header-left">
            <a class="header-brand" href="<?php echo $base_url; ?>index.php">
                ▲ Zarza-Ski
            </a>
            <nav class="header-nav-container">
                <ul class="header-nav">
                    <li><a class="header-nav-link <?php echo $current_page == 'index' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>index.php">Accueil</a></li>
                    <li><a class="header-nav-link <?php echo $current_page == 'recherche' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>pages/recherche.php">Reserver</a></li>
                    <li><a class="header-nav-link <?php echo $current_page == 'vues' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>pages/vues.php">Vues</a></li>
                </ul>
            </nav>
        </div>

        <!-- section droite: panier et espace client -->
        <div class="header-right">
            <?php if (isset($_SESSION['id_user'])): ?>
                <!-- affichage du panier -->
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

                <!-- dropdown espace client -->
                <div class="header-user-dropdown">
                    <button class="header-dropdown-trigger">
                        👤 <?php echo h($_SESSION['username']); ?> <span class="arrow">&#9662;</span>
                    </button>
                    <ul class="header-dropdown-menu">
                        <li><a href="<?php echo $base_url; ?>pages/tableau_de_bord.php">Mon espace client</a></li>
                        <li class="separator"></li>
                        <li><a class="logout-action" href="<?php echo $base_url; ?>auth/logout.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Deconnexion</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <!-- liens de connexion anonyme -->
                <a class="header-link-login" href="<?php echo $base_url; ?>auth/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Connexion</a>
                <a class="header-btn-register" href="<?php echo $base_url; ?>auth/register.php">Inscription</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- conteneur principal de page -->
    <main class="academic-main-container">
