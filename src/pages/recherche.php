<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

// On commence avec une requête de base pour voir toutes les chambres
$sql_recherche = "SELECT * FROM chambre WHERE 1=1";
$filtres = [];

// Si l'utilisateur a choisi une vue précise dans le menu
if (!empty($_GET['vue'])) {
    $sql_recherche = $sql_recherche . " AND type_vue = ?";
    $filtres[] = $_GET['vue'];
}

// Si l'utilisateur a choisi un bâtiment précis
if (!empty($_GET['batiment'])) {
    $sql_recherche = $sql_recherche . " AND batiment = ?";
    $filtres[] = $_GET['batiment'];
}

// Si l'utilisateur veut un minimum de lits
if (!empty($_GET['nb_lits'])) {
    $sql_recherche = $sql_recherche . " AND nb_lits >= ?";
    $filtres[] = intval($_GET['nb_lits']);
}

// On prépare et on exécute la recherche avec nos filtres
$preparation = $pdo->prepare($sql_recherche);
$preparation->execute($filtres);
$liste_des_chambres = $preparation->fetchAll();
?>

<main>
    <h1>Trouver mon hébergement Zarza-Ski</h1>
    
    <section style="background: #f4f4f4; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <form method="GET" action="recherche.php">
            
            <label>Vue souhaitée :</label>
            <select name="vue">
                <option value="">Peu importe</option>
                <option value="pistes" <?php if(isset($_GET['vue']) && $_GET['vue'] == 'pistes') echo 'selected'; ?>>Vue sur les pistes</option>
                <option value="parking" <?php if(isset($_GET['vue']) && $_GET['vue'] == 'parking') echo 'selected'; ?>>Vue sur le parking</option>
            </select>

            <label>Bâtiment :</label>
            <select name="batiment">
                <option value="">Tous les bâtiments</option>
                <option value="A" <?php if(isset($_GET['batiment']) && $_GET['batiment'] == 'A') echo 'selected'; ?>>Bâtiment A</option>
                <option value="B" <?php if(isset($_GET['batiment']) && $_GET['batiment'] == 'B') echo 'selected'; ?>>Bâtiment B</option>
            </select>

            <label>Nombre de lits :</label>
            <input type="number" name="nb_lits" min="1" value="<?php echo isset($_GET['nb_lits']) ? htmlspecialchars($_GET['nb_lits']) : ''; ?>">

            <button type="submit">Lancer la recherche</button>
        </form>
    </section>

    <table border="1" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #eee;">
                <th>Numéro</th>
                <th>Bâtiment</th>
                <th>Étage</th>
                <th>Vue</th>
                <th>Capacité</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($liste_des_chambres) > 0): ?>
                <?php foreach ($liste_des_chambres as $une_chambre): ?>
                    <tr>
                        <td>n°<?php echo htmlspecialchars($une_chambre['num_chambre']); ?></td>
                        <td><?php echo htmlspecialchars($une_chambre['batiment']); ?></td>
                        <td><?php echo htmlspecialchars($une_chambre['etage']); ?></td>
                        <td><?php echo htmlspecialchars($une_chambre['type_vue']); ?></td>
                        <td><?php echo htmlspecialchars($une_chambre['nb_lits']); ?> lits</td>
                        <td>
                            <a href="details.php?id=<?php echo $une_chambre['num_chambre']; ?>">
                                Voir la fiche
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">
                        Aucune chambre ne correspond à vos critères.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>

<?php require_once '../includes/footer.php'; ?>