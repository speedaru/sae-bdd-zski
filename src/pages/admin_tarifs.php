<?php
session_start();
require_once '../includes/db.php';

// Sécurité : Vérification du rôle avant tout affichage
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'gestionnaire'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Traitement de la modification du prix si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_prix'])) {
    $nouveau_prix = intval($_POST['nouveau_prix']);
    $type_formule = $_POST['type_formule'];

    $stmt_update = $pdo->prepare("UPDATE formule SET prix_base = ? WHERE type_formule = ?");
    $stmt_update->execute([$nouveau_prix, $type_formule]);
    $message = "Le tarif de la formule " . htmlspecialchars($type_formule) . " a été mis à jour.";
}

// Récupération de la liste des formules pour l'affichage
$stmt_formules = $pdo->query("SELECT * FROM formule");
$formules = $stmt_formules->fetchAll();

require_once '../includes/header.php';
?>

<main>
    <h1>Gestion des Tarifs des Formules</h1>

    <?php if (isset($message)): ?>
        <p style="color: green; font-weight: bold;"><?php echo $message; ?></p>
    <?php endif; ?>

    <table border="1">
        <thead>
            <tr>
                <th>Type de Formule</th>
                <th>Prix de Base Actuel</th>
                <th>Nouveau Prix</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($formules as $formule): ?>
                <tr>
                    <td><?php echo htmlspecialchars($formule['type_formule']); ?></td>
                    <td><?php echo htmlspecialchars($formule['prix_base']); ?> €</td>
                    <td>
                        <form method="POST" action="admin_tarifs.php" style="display:inline;">
                            <input type="hidden" name="type_formule" value="<?php echo htmlspecialchars($formule['type_formule']); ?>">
                            <input type="number" name="nouveau_prix" required min="0" placeholder="Ex: 450">
                    </td>
                    <td>
                            <button type="submit" name="modifier_prix">Enregistrer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="actions" style="margin-top: 20px;">
        <a href="../index.php">Retour à l'accueil</a>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>