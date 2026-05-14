<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/header.php';

// Vérification du rôle : seuls les admins ou gestionnaires peuvent entrer ici
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'gestionnaire')) {
    header('Location: ../index.php');
    exit();
}

// Traitement du formulaire quand on clique sur "Mettre à jour"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider_changement'])) {
    
    // On récupère les données du formulaire
    $nouveau_prix = intval($_POST['nouveau_prix']);
    $nom_formule = $_POST['formule_concernee'];
    
    // Requête SQL pour modifier le prix dans la table 'formule'
    $sql_update = "UPDATE formule SET prix_base = ? WHERE type_formule = ?";
    $preparation = $pdo->prepare($sql_update);
    $preparation->execute([$nouveau_prix, $nom_formule]);
    
    $message_confirmation = "Le tarif de la formule " . htmlspecialchars($nom_formule) . " a bien été modifié.";
}

// On récupère la liste des formules pour l'affichage
$requete_affichage = $pdo->query("SELECT * FROM formule");
$liste_formules = $requete_affichage->fetchAll();
?>

<main>
    <h1>Gestion des tarifs de la station</h1>
    
    <?php if (isset($message_confirmation)): ?>
        <p style="color: green; font-weight: bold; background: #e0ffe0; padding: 10px; border: 1px solid green;">
            <?php echo $message_confirmation; ?>
        </p>
    <?php endif; ?>

    <p>Utilisez ce tableau pour ajuster les prix de base des prestations Zarza-Ski :</p>

    <table border="1" cellpadding="10">
        <thead>
            <tr>
                <th>Type de prestation</th>
                <th>Prix actuel</th>
                <th>Modifier le prix</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($liste_formules as $une_formule): ?>
                <tr>
                    <td><?php echo htmlspecialchars($une_formule['type_formule']); ?></td>
                    <td><?php echo htmlspecialchars($une_formule['prix_base']); ?> €</td>
                    
                    <form method="POST">
                        <td>
                            <input type="number" name="nouveau_prix" min="0" required 
                                   value="<?php echo $une_formule['prix_base']; ?>">
                            
                            <input type="hidden" name="formule_concernee" 
                                   value="<?php echo htmlspecialchars($une_formule['type_formule']); ?>">
                        </td>
                        <td>
                            <button type="submit" name="valider_changement">Mettre à jour</button>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p style="margin-top: 20px;">
        <a href="../index.php">Retour à l'accueil</a>
    </p>
</main>

<?php require_once '../includes/footer.php'; ?>