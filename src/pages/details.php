<?php
// Inclusion de la connexion et des éléments communs avec les bons chemins relatifs
require_once '../includes/db.php';
require_once '../includes/header.php';

// Récupération de l'identifiant de la chambre depuis l'URL
$id_chambre = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Requête préparée pour récupérer toutes les caractéristiques de la chambre
$stmt = $pdo->prepare("SELECT * FROM chambre WHERE num_chambre = ?");
$stmt->execute([$id_chambre]);
$chambre = $stmt->fetch();

// Vérification si la chambre existe dans la base sae_ski_db
if (!$chambre) {
    echo "<main><p>Erreur : Cette chambre n'existe pas dans notre catalogue.</p>";
    echo "<a href='recherche.php'>Retour à la recherche</a></main>";
    require_once '../includes/footer.php';
    exit;
}
?>

<main>
    <h1>Détails de la chambre n°<?php echo htmlspecialchars($chambre['num_chambre']); ?></h1>
    
    <div class="fiche-chambre">
        <ul>
            <li><strong>Bâtiment :</strong> <?php echo htmlspecialchars($chambre['batiment']); ?></li>
            <li><strong>Étage :</strong> <?php echo htmlspecialchars($chambre['etage']); ?></li>
            <li><strong>Nombre de lits :</strong> <?php echo htmlspecialchars($chambre['nb_lits']); ?></li>
            <li><strong>Superficie :</strong> <?php echo htmlspecialchars($chambre['superficie']); ?> m²</li>
            <li><strong>Type de vue :</strong> <?php echo htmlspecialchars($chambre['type_vue']); ?></li>
            <li><strong>Balcon :</strong> <?php echo $chambre['balcon_present'] ? 'Oui' : 'Non'; ?></li>
        </ul>
    </div>

    <div class="actions">
        <a href="recherche.php" class="btn-retour">Retour au catalogue</a>
        <a href="reservation.php?id=<?php echo $chambre['num_chambre']; ?>" class="btn-reserver">Réserver ce séjour</a>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>