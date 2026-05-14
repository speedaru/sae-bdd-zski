<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Zarza-Ski - Gestion Station</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <strong>Zarza-Ski</strong>
            </div>
            <ul class="nav-links">
                <li><a href="/index.php">Accueil</a></li>
                <li><a href="/pages/recherche.php">Rechercher une chambre</a></li>
                <?php if (isset($_SESSION['id_user'])): ?>
                    <li><a href="/pages/mes_reservations.php">Mes Réservations</a></li>
                    
                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'gestionnaire'): ?>
                        <li><a href="/pages/admin_tarifs.php">Gestion Tarifs</a></li>
                    <?php endif; ?>
                    
                    <li class="auth-link">
                        <a href="/auth/logout.php">Déconnexion (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
                    </li>
                <?php else: ?>
                    <li class="auth-link"><a href="/auth/login.php">Connexion</a></li>
                    <li><a href="/auth/register.php">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main class="container">