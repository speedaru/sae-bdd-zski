<?php
require_once 'includes/db.php';

// requete de test
$query = $pdo->query("SELECT COUNT(*) FROM chambre");
$nbChambres = $query->fetchColumn();
?>

<!DOCTYPE html>
<html>
<head><title>Zarza-Ski Test</title></head>
<body>
    <h1>Bienvenue sur Zarza-Ski !</h1>
    <p>Connexion réussie : Il y a actuellement <?php echo $nbChambres; ?> chambres en base.</p>
</body>
</html>
