<?php
// Inclusion de la connexion avec le bon chemin relatif
require_once '../includes/db.php';
require_once '../includes/header.php';

// Initialisation de la requête de base sur la table chambre
$query = "SELECT * FROM chambre WHERE 1=1";
$params = [];

// Construction dynamique selon les filtres du sujet Zarza-Ski
if (!empty($_GET['vue'])) {
    $query .= " AND type_vue = ?";
    $params[] = $_GET['vue'];
}
if (!empty($_GET['batiment'])) {
    $query .= " AND batiment = ?";
    $params[] = $_GET['batiment'];
}
if (!empty($_GET['nb_lits'])) {
    $query .= " AND nb_lits >= ?";
    $params[] = intval($_GET['nb_lits']);
}

// Exécution via PDO pour récupérer les données du dump
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$chambres = $stmt->fetchAll();
?>

<main>
    <h1>Rechercher un hébergement</h1>
    
    <form method="GET" action="recherche.php">
        <label for="vue">Vue :</label>
        <select name="vue" id="vue">
            <option value="">Toutes</option>
            <option value="pistes">Sur les pistes</option>
            <option value="parking">Sur le parking</option>
        </select>
        
        <label for="batiment">Bâtiment :</label>
        <select name="batiment" id="batiment">
            <option value="">Tous</option>
            <option value="A">Bâtiment A</option>
            <option value="B">Bâtiment B</option>
        </select>
        
        <label for="nb_lits">Nombre de lits min. :</label>
        <input type="number" name="nb_lits" id="nb_lits" min="1" value="<?php echo isset($_GET['nb_lits']) ? htmlspecialchars($_GET['nb_lits']) : ''; ?>">
        
        <button type="submit">Filtrer</button>
    </form>

    <table border="1">
        <thead>
            <tr>
                <th>N° Chambre</th>
                <th>Bâtiment</th>
                <th>Étage</th>
                <th>Vue</th>
                <th>Lits</th>
                <th>Détails</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($chambres): ?>
                <?php foreach ($chambres as $chambre): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($chambre['num_chambre']); ?></td>
                        <td><?php echo htmlspecialchars($chambre['batiment']); ?></td>
                        <td><?php echo htmlspecialchars($chambre['etage']); ?></td>
                        <td><?php echo htmlspecialchars($chambre['type_vue']); ?></td>
                        <td><?php echo htmlspecialchars($chambre['nb_lits']); ?></td>
                        <td><a href="details.php?id=<?php echo $chambre['num_chambre']; ?>">Consulter</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Aucun résultat pour ces critères.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>

<?php require_once '../includes/footer.php'; ?>