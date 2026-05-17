<?php
/**
 * Header Global - Zarza-Ski
 * Gère l'affichage dynamique des menus et l'ouverture de session.
 */
require_once 'init.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarza-Ski - <?php echo ucfirst(str_replace('_', ' ', $current_page)); ?></title>

    <!-- CSS COMMUN (Framework + Common) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/common.css"> 

    <!-- CSS DYNAMIQUE (Chargé uniquement si le fichier existe pour cette page) -->
    <?php 
    $page_css_path = $base_url . "assets/css/pages/{$current_page}.css";
    if (file_exists($page_css_path)): ?>
        <link rel="stylesheet" href="<?php echo $page_css_path; ?>">
    <?php endif; ?>
</head>

<body class="page-<?php echo $current_page; ?>">

    <header class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo $base_url; ?>index.php">
                <i class="fas fa-mountain me-2"></i>Zarza-Ski
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="topNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'index' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'recherche' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>pages/recherche.php">Réserver</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'vues' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>pages/vues.php">Vues</a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if (isset($_SESSION['id_user'])): ?>
                        <!-- NOUVEAU : Affichage dynamique du Panier -->
                        <li class="nav-item me-2">
                            <a class="btn btn-outline-light btn-sm position-relative px-3" href="<?php echo $base_url; ?>pages/reservation.php">
                                <i class="fas fa-shopping-cart me-1"></i> Mon Panier
                                <?php 
                                $panier_count = get_panier_count();
                                if ($panier_count > 0): 
                                ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $panier_count; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i> <?php echo h($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="<?php echo $base_url; ?>pages/tableau_de_bord.php">Mon espace client</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo $base_url; ?>auth/logout.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Déconnexion</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $base_url; ?>auth/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Connexion</a></li>
                        <li class="nav-item"><a class="btn btn-primary btn-sm ms-2" href="<?php echo $base_url; ?>auth/register.php">Inscription</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </header>
    <main class="container py-4">