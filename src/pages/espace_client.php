<?php
/**
 * Accueil Espace Client - Zarza-Ski
 * Sert de conteneur principal pour les fonctionnalités membres.
 */
session_start();

// Protection de l'accès
if (!isset($_SESSION['id_user'])) {
    header("Location: /auth/login.php?redirect=" . urlencode("/pages/espace_client.php"));
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mt-4">
    <!-- Sidebar latérale -->
    <div class="col-md-3">
        <?php include __DIR__ . '/../includes/sidebar_client.php'; ?>
    </div>

    <!-- Contenu central (Dashboard) -->
    <div class="col-md-9">
        <div class="card shadow-sm border-0 p-4">
            <h2 class="mb-4">Bienvenue dans votre espace, <?php echo htmlspecialchars($_SESSION['username']); ?> !</h2>
            
            <div class="row g-4">
                <!-- Carte Profil -->
                <div class="col-sm-6">
                    <div class="card h-100 border-start border-primary border-4 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-id-card me-2"></i>Mon Profil</h5>
                            <p class="card-text text-muted">Gérez vos informations personnelles et votre niveau de ski.</p>
                            <a href="profil.php" class="btn btn-sm btn-primary">Modifier</a>
                        </div>
                    </div>
                </div>
                
                <!-- Carte Réservations -->
                <div class="col-sm-6">
                    <div class="card h-100 border-start border-success border-4 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-calendar-alt me-2"></i>Mes Séjours</h5>
                            <p class="card-text text-muted">Consultez l'historique de vos réservations à la station.</p>
                            <a href="mes_reservations.php" class="btn btn-sm btn-success">Voir mes séjours</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message d'alerte si profil incomplet -->
            <?php if (!isset($_SESSION['id_client']) || empty($_SESSION['id_client'])): ?>
                <div class="alert alert-warning mt-4 shadow-sm" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Votre profil skieur n'est pas encore complété. 
                    <a href="profil.php" class="alert-link">Cliquez ici pour remplir vos informations</a> afin de pouvoir réserver.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
