<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$id_chambre = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Récupération de la chambre
$stmt = $pdo->prepare("SELECT * FROM chambre WHERE num_chambre = ?");
$stmt->execute([$id_chambre]);
$chambre = $stmt->fetch();

// Récupération de toutes les formules de prix
$stmt_formules = $pdo->query("SELECT * FROM formule");
$formules = $stmt_formules->fetchAll();

if (!$chambre) {
    echo "<main><p>Chambre introuvable.</p></main>";
    require_once '../includes/footer.php';
    exit;
}
?>

<main>
    <h1>Détails de la chambre n°<?php echo htmlspecialchars($chambre['num_chambre']); ?></h1>
    
    <div class="infos-techniques">
        <p>Bâtiment <?php echo htmlspecialchars($chambre['batiment']); ?> - Étage <?php echo htmlspecialchars($chambre['etage']); ?></p>
        <p>Superficie : <?php echo htmlspecialchars($chambre['superficie']); ?> m² (<?php echo htmlspecialchars($chambre['nb_lits']); ?> lits)</p>
        <p>Vue : <?php echo htmlspecialchars($chambre['type_vue']); ?> / Balcon : <?php echo $chambre['balcon_present'] ? 'Oui' : 'Non'; ?></p>
    </div>

    <h3>Tarifs par formule (prix de base)</h3>
    <table border="1">
        <tr><th>Formule</th><th>Prix</th></tr>
        <?php foreach ($formules as $f): ?>
            <tr>
                <td><?php echo htmlspecialchars($f['type_formule']); ?></td>
                <td><?php echo htmlspecialchars($f['prix_base']); ?> €</td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div style="margin-top:20px;">
        <a href="recherche.php">Retour</a>
        <a href="reservation.php?id=<?php echo $id_chambre; ?>">Réserver maintenant</a>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>