<?php
/**
 * Navigation latérale de l'espace client
 */
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="list-group shadow-sm mb-4">
    <div class="list-group-item bg-light fw-bold text-muted small text-uppercase py-3">
        Navigation Client
    </div>
    
    <a href="/pages/tableau_de_bord.php" 
       class="list-group-item list-group-item-action <?php echo ($current_page == 'tableau_de_bord.php') ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
    </a>
    
    <a href="/pages/profil.php" 
       class="list-group-item list-group-item-action <?php echo ($current_page == 'profil.php') ? 'active' : ''; ?>">
        <i class="fas fa-user-edit me-2"></i>Mon Profil Skieur
    </a>
    
    <a href="/pages/carnet.php" 
       class="list-group-item list-group-item-action <?php echo ($current_page == 'carnet.php') ? 'active' : ''; ?>">
        <i class="fas fa-book me-2"></i>Carnet de Voyageurs
    </a>
    
    <a href="/pages/groupes.php" 
       class="list-group-item list-group-item-action <?php echo ($current_page == 'groupes.php') ? 'active' : ''; ?>">
        <i class="fas fa-users-cog me-2"></i>Mes Groupes
    </a>
    
    <a href="/pages/mes_reservations.php" 
       class="list-group-item list-group-item-action <?php echo ($current_page == 'mes_reservations.php') ? 'active' : ''; ?>">
        <i class="fas fa-skiing me-2"></i>Mes Réservations
    </a>
    
    <a href="/pages/preferences.php" 
       class="list-group-item list-group-item-action <?php echo ($current_page == 'preferences.php') ? 'active' : ''; ?>">
        <i class="fas fa-heart me-2"></i>Préférences séjour
    </a>
    
    <a href="/auth/logout.php" class="list-group-item list-group-item-action text-danger mt-2">
        <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
    </a>
    
</div>
