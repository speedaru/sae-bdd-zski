<?php
/**
 * Accueil Espace Client - Zarza-Ski
 * Version épurée et académique
 * Emplacement : src/pages/tableau_de_bord.php
 */
require_once __DIR__ . '/../includes/header.php';

require_login("../pages/espace_client.php");
?>

<div class="dashboard-container">
    <!-- Section de navigation de gauche (Sidebar) -->
    <div class="dashboard-sidebar">
        <?php include __DIR__ . '/../includes/sidebar_client.php'; ?>
    </div>

    <!-- Section de contenu principale de droite -->
    <div class="dashboard-content">
        <div class="dashboard-welcome-box">
            <h2>Bienvenue dans votre espace, <?php echo htmlspecialchars($_SESSION['username']); ?> !</h2>
            <p class="subtitle">Gérez vos options de séjour et votre fiche personnelle depuis ce tableau de bord.</p>
        </div>

        <div class="dashboard-grid">
            <!-- Option Fiche Skieur / Profil -->
            <div class="dashboard-option-card">
                <h3>Mon Profil Skieur</h3>
                <p>Gérez vos informations personnelles, vos mensurations de sécurité et votre niveau de ski.</p>
                <a href="profil.php" class="btn-action">Modifier mon profil</a>
            </div>
            
            <!-- Option Historique des Séjours -->
            <div class="dashboard-option-card">
                <h3>Mes Séjours</h3>
                <p>Consultez l'historique de vos réservations ou modifiez vos préférences d'hébergement.</p>
                <a href="mes_reservations.php" class="btn-action">Voir mes séjours</a>
            </div>
        </div>

        <!-- Alerte d'avertissement si le profil skieur est manquant -->
        <?php if (!isset($_SESSION['id_client']) || empty($_SESSION['id_client'])): ?>
            <div class="dashboard-alert">
                <strong>Attention :</strong> Votre profil skieur n'est pas encore complété. 
                <a href="profil.php" class="alert-link">Cliquez ici pour remplir vos informations</a> afin de pouvoir réserver.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>