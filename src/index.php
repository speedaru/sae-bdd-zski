<?php
// connexion a la db
require_once 'includes/db.php';

// inclusion du header
include_once 'includes/header.php';

// requete de test
$query = $pdo->query("SELECT COUNT(*) FROM chambre");
$nbChambres = $query->fetchColumn();
?>

<h1>Bienvenue sur Zarza-Ski !</h1>
<p>Connexion réussie : Il y a actuellement <?php echo $nbChambres; ?> chambres en base.</p>

<?php 
// inclusion du footer
include_once 'includes/footer.php'; 
?>
