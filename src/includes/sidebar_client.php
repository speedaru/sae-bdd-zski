<?php
/**
 * Navigation latérale de l'espace client - Structure de tableau classique
 * Emplacement : src/includes/sidebar_client.php
 */
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Liaison de la feuille de style de la navigation client -->
<link rel="stylesheet" href="/assets/css/sidebar_client.css">

<table class="sidebar-table">
    <thead>
        <tr>
            <th>Navigation Client</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="<?php echo ($current_page == 'tableau_de_bord.php') ? 'active-cell' : ''; ?>">
                <a href="/pages/tableau_de_bord.php">Tableau de bord</a>
            </td>
        </tr>
        <tr>
            <td class="<?php echo ($current_page == 'profil.php') ? 'active-cell' : ''; ?>">
                <a href="/pages/profil.php">Mon Profil Skieur</a>
            </td>
        </tr>
        <tr>
            <td class="<?php echo ($current_page == 'carnet.php') ? 'active-cell' : ''; ?>">
                <a href="/pages/carnet.php">Carnet de Voyageurs</a>
            </td>
        </tr>
        <tr>
            <td class="<?php echo ($current_page == 'groupes.php') ? 'active-cell' : ''; ?>">
                <a href="/pages/groupes.php">Mes Groupes</a>
            </td>
        </tr>
        <tr>
            <td class="<?php echo ($current_page == 'mes_reservations.php') ? 'active-cell' : ''; ?>">
                <a href="/pages/mes_reservations.php">Mes Réservations</a>
            </td>
        </tr>
        <tr>
            <td class="<?php echo ($current_page == 'preferences.php') ? 'active-cell' : ''; ?>">
                <a href="/pages/preferences.php">Préférences séjour</a>
            </td>
        </tr>
        <tr>
            <td class="logout-cell">
                <a href="/auth/logout.php" class="logout-link">Déconnexion</a>
            </td>
        </tr>
    </tbody>
</table>