<?php
session_start();
require_once '../includes/db.php';

// Sécurité : l'utilisateur doit être connecté pour réserver
if (!isset($_SESSION['id_client'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id_chambre = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_client = $_SESSION['id_client'];

// Récupération des données pour les menus déroulants
$semaines = $pdo->query("SELECT * FROM semaine")->fetchAll();
$formules = $pdo->query("SELECT * FROM formule")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_debut = $_POST['semaine'];
    $type_formule = $_POST['formule'];

    try {
        $pdo->beginTransaction();

        // Vérification de disponibilité avec verrouillage (SELECT FOR UPDATE)
        $stmt_verif = $pdo->prepare("SELECT 1 FROM attribution_chambre 
                                     WHERE num_chambre = ? AND debut = ? FOR UPDATE");
        $stmt_verif->execute([$id_chambre, $date_debut]);

        if ($stmt_verif->fetch()) {
            throw new Exception("Cette chambre est déjà réservée pour cette période.");
        }

        // Insertion dans sejour (Lien Client - Groupe - Semaine)
        // Note : On suppose ici un groupe par défaut ou récupéré en amont
        $stmt_sejour = $pdo->prepare("INSERT INTO sejour (id_client, nom_groupe, debut) VALUES (?, ?, ?)");
        $stmt_sejour->execute([$id_client, 'Individuel', $date_debut]);

        // Insertion dans attribution_chambre
        $stmt_attr = $pdo->prepare("INSERT INTO attribution_chambre (id_client, num_chambre, debut) VALUES (?, ?, ?)");
        $stmt_attr->execute([$id_client, $id_chambre, $date_debut]);

        $pdo->commit();
        $message_success = "Votre réservation a été enregistrée avec succès !";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message_error = $e->getMessage();
    }
}

require_once '../includes/header.php';
?>

<main>
    <h1>Finaliser ma réservation</h1>
    <p>Chambre sélectionnée : n°<?php echo $id_chambre; ?></p>

    <?php if (isset($message_success)) echo "<p style='color:green'>$message_success</p>"; ?>
    <?php if (isset($message_error)) echo "<p style='color:red'>$message_error</p>"; ?>

    <form method="POST">
        <label>Choisir votre semaine :</label>
        <select name="semaine" required>
            <?php foreach ($semaines as $s): ?>
                <option value="<?php echo $s['debut']; ?>">Du <?php echo $s['debut']; ?> au <?php echo $s['fin']; ?></option>
            <?php endforeach; ?>
        </select>

        <label>Choisir votre formule :</label>
        <select name="formule" required>
            <?php foreach ($formules as $f): ?>
                <option value="<?php echo $f['type_formule']; ?>"><?php echo $f['type_formule']; ?> (<?php echo $f['prix_base']; ?>€)</option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Confirmer la réservation</button>
    </form>
</main>

<?php require_once '../includes/footer.php'; ?>