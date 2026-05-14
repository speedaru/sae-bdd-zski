<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['id_client'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id_client = $_SESSION['id_client'];

// Jointure entre l'attribution et la chambre pour avoir les infos complètes
$query = "SELECT ac.debut, c.num_chambre, c.batiment, c.etage, c.type_vue 
          FROM attribution_chambre ac
          JOIN chambre c ON ac.num_chambre = c.num_chambre
          WHERE ac.id_client = ?
          ORDER BY ac.debut DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$id_client]);
$reservations = $stmt->fetchAll();
?>

<main>
    <h1>Mes Réservations Zarza-Ski</h1>

    <?php if (count($reservations) > 0): ?>
        <table border="1">
            <thead>
                <tr>
                    <th>Semaine du</th>
                    <th>Chambre</th>
                    <th>Bâtiment</th>
                    <th>Étage</th>
                    <th>Vue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $res): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($res['debut']); ?></td>
                        <td>n°<?php echo htmlspecialchars($res['num_chambre']); ?></td>
                        <td><?php echo htmlspecialchars($res['batiment']); ?></td>
                        <td><?php echo htmlspecialchars($res['etage']); ?></td>
                        <td><?php echo htmlspecialchars($res['type_vue']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucune réservation trouvée. <a href="recherche.php">Voir le catalogue</a></p>
    <?php endif; ?>
</main>

<?php require_once '../includes/footer.php'; ?>{\rtf1}