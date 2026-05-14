<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/header.php';

$id_client = $_SESSION['id_client'];

if (!isset($_SESSION['id_user'])) {
    $login_redirect = "../auth/login.php?redirect=" . urlencode(__DIR__ . "mes_reservations.php");
    header('Location: ' . $login_redirect);
    exit();
}

// Requête avec jointures simples entre les 3 tables pour récupérer toutes les infos
$query = "SELECT reservation.date_debut, reserver.num_chambre, reserver.nom_groupe, reserver.formule_prix_final, chambre.batiment
        FROM reserver
        JOIN reservation ON reserver.id_reservation = reservation.id_reservation
        JOIN chambre ON reserver.num_chambre = chambre.num_chambre
        WHERE reserver.id_client = ?
        ORDER BY reservation.date_debut DESC";

$requete = $pdo->prepare($query);
$requete->execute([$id_client]);
$liste_reservations = $requete->fetchAll();
?>

<main>
    <h1>Mes réservations effectuées</h1>

    <?php if (count($liste_reservations) > 0): ?>
        <table border="1">
            <thead>
                <tr>
                    <th>Semaine du</th>
                    <th>N° Chambre</th>
                    <th>Bâtiment</th>
                    <th>Nom du groupe</th>
                    <th>Tarif payé</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($liste_reservations as $ligne): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ligne['date_debut']); ?></td>
                        <td>Chambre <?php echo htmlspecialchars($ligne['num_chambre']); ?></td>
                        <td>Bâtiment <?php echo htmlspecialchars($ligne['batiment']); ?></td>
                        <td><?php echo htmlspecialchars($ligne['nom_groupe']); ?></td>
                        <td><?php echo htmlspecialchars($ligne['formule_prix_final']); ?> €</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Vous n'avez pas encore de réservations enregistrées sur Zarza-Ski.</p>
    <?php endif; ?>

    <p style="margin-top: 20px;">
        <a href="recherche.php">Retourner au catalogue des chambres</a>
    </p>
</main>

<?php require_once '../includes/footer.php'; ?>
