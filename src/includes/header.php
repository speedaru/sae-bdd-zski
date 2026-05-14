<?php
/**
 * Header Global - Zarza-Ski
 * Gère l'affichage dynamique des menus et l'ouverture de session.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarza-Ski - Gestion Station</title>
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Style personnalisé (on utilise le chemin absolu pour éviter les erreurs de sous-dossiers) -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/index.php">
                <i class="fas fa-mountain me-2"></i>Zarza-Ski
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="topNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="/index.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="/pages/recherche.php">Réserver</a></li>
                </ul>

                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if (isset($_SESSION['id_user'])): ?>
                        <!-- Menu Utilisateur Connecté -->
                        <li class="nav-item me-3">
                            <span class="navbar-text text-light">
                                <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm me-2" href="/pages/espace_client.php">Mon Espace</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-danger btn-sm" href="/auth/logout.php" title="Déconnexion">
                                <i class="fas fa-power-off"></i>
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Menu Visiteur -->
                        <li class="nav-item"><a class="nav-link" href="/auth/login.php">Connexion</a></li>
                        <li class="nav-item">
                            <a class="btn btn-outline-light btn-sm ms-lg-2" href="/auth/register.php">Inscription</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </header>
    <!-- Le container principal est ouvert ici et fermé dans footer.php -->
    <main class="container py-4">
