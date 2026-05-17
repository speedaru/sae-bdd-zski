<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/header.php';

$v = $_GET['vue'] ?? '';
$b = $_GET['batiment'] ?? '';
$l = $_GET['nb_lits'] ?? '';

$sql = "SELECT * FROM chambre WHERE 1=1"; 
$parametres = [];

if ($v != "") {
    $sql = $sql . " AND type_vue = ?";
    $parametres[] = $v;
}

if ($b != "") {
    $sql = $sql . " AND batiment = ?";
    $parametres[] = $b;
}

if ($l != "") {
    $sql = $sql . " AND nb_lits >= ?";
    $parametres[] = intval($l);
}

$sql = $sql . " ORDER BY num_chambre ASC";
$prepa = $pdo->prepare($sql);
$prepa->execute($parametres);
$resultats = $prepa->fetchAll();
?>

<link rel="stylesheet" type="text/css" href="../css/recherche.css">

<main class="conteneur-recherche">
    <h1>Rechercher une chambre</h1>

    <div class="bloc-filtres">
        <form method="GET" action="recherche.php">
            <div class="groupe-champ">
                <label><b>Vue :</b></label>
                <select name="vue">
                    <option value="">Peu importe</option>
                    <option value="pistes" <?php if ($v == 'pistes') echo 'selected'; ?>>Pistes</option>
                    <option value="parking" <?php if ($v == 'parking') echo 'selected'; ?>>Parking</option>
                </select>
            </div>

            <div class="groupe-champ">
                <label><b>Bâtiment :</b></label>
                <select name="batiment">
                    <option value="">Tous</option>
                    <option value="A" <?php if ($b == 'A') echo 'selected'; ?>>A</option>
                    <option value="B" <?php if ($b == 'B') echo 'selected'; ?>>B</option>
                </select>
            </div>

            <div class="groupe-champ">
                <label><b>Nombre de lits :</b></label>
                <input type="number" name="nb_lits" min="1" value="<?php echo htmlspecialchars($l); ?>">
            </div>

            <div class="groupe-bouton">
                <input type="submit" value="Filtrer les résultats" class="btn-filtrer">
            </div>
        </form>
    </div>

    <br>

    <table class="table-resultats" border="1" cellpadding="12" cellspacing="0">
        <thead>
            <tr bgcolor="#CCCCCC">
                <th>Numéro</th>
                <th>Bâtiment</th>
                <th>Vue</th>
                <th>Lits</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($resultats) > 0): ?>
                <?php foreach ($resultats as $chambre): ?>
                    <tr>
                        <td align="center"><b>Chambre <?php echo $chambre['num_chambre']; ?></b></td>
                        <td align="center"><?php echo htmlspecialchars($chambre['batiment']); ?></td>
                        <td>Vue sur <?php echo htmlspecialchars($chambre['type_vue']); ?></td>
                        <td align="center"><?php echo $chambre['nb_lits']; ?> lits</td>
                        <td align="center">
                            <a href="details.php?id=<?php echo $chambre['num_chambre']; ?>" class="lien-voir">Voir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" align="center"><i>Aucune chambre trouvée</i></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>

<?php require_once '../includes/footer.php'; ?>