<?php
session_start();
require_once '../includes/db.php';

// Sécurité : l'utilisateur doit être connecté pour accéder au formulaire
if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id_chambre = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_client = $_SESSION['id_client'];

// On interdit l'accès direct si aucune chambre n'est sélectionnée
if ($id_chambre === 0) {
    header('Location: recherche.php');
    exit();
}

// Récupération des données pour les listes déroulantes
$semaines = $pdo->query("SELECT * FROM semaine")->fetchAll();
$formules = $pdo->query("SELECT * FROM formule")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_debut = $_POST['semaine'];
    $nom_groupe = trim($_POST['nom_groupe']);

    try {
        $pdo->beginTransaction();

        // 1. CRÉATION DU GROUPE : S'il n'existe pas, on le crée automatiquement
        $stmt_groupe = $pdo->prepare("INSERT INTO groupe (nom_groupe) VALUES (?) ON CONFLICT DO NOTHING");
        $stmt_groupe->execute([$nom_groupe]);

        // 2. VÉRIFICATION DE DISPONIBILITÉ : On vérifie si la chambre est libre pour TOUT LE MONDE
        $stmt_verif = $pdo->prepare("SELECT 1 FROM attribution_chambre WHERE num_chambre = ? AND debut = ? FOR UPDATE");
        $stmt_verif->execute([$id_chambre, $date_debut]);

        if ($stmt_verif->fetch()) {
            throw new Exception("Cette chambre est déjà occupée par un autre client pour cette période.");
        }

        // 3. INSERTION DU SÉJOUR
        $stmt_sejour = $pdo->prepare("INSERT INTO sejour (id_client, nom_groupe, debut) VALUES (?, ?, ?)");
        $stmt_sejour->execute([$id_client, $nom_groupe, $date_debut]);

        // 4. ATTRIBUTION DE LA CHAMBRE
        $stmt_attr = $pdo->prepare("INSERT INTO attribution_chambre (id_client, num_chambre, debut) VALUES (?, ?, ?)");
        $stmt_attr->execute([$id_client, $id_chambre, $date_debut]);

        $pdo->commit();
        
        // REDIRECTION : On envoie l'utilisateur vers sa liste pour confirmer le succès
        header("Location: mes_reservations.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        // Gestion de l'erreur de doublon si l'utilisateur clique deux fois sur "Confirmer"
        if ($e->getCode() == '23505') {
            $error = "Vous avez déjà réservé cette chambre pour cette semaine.";
        } else {
            $error = $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<main>
    <h1>Confirmer la réservation (Chambre n°<?php echo $id_chambre; ?>)</h1>
    
    <?php if (isset($error)): ?>
        <p style="color:red; font-weight:bold; border:1px solid red; padding:10px;">
            Erreur : <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>

    <form method="POST" class="form-reservation">
        <div class="mb-3">
            <label>Nom de votre groupe (ex: Individuel, Famille...) :</label>
            <input type="text" name="nom_groupe" required placeholder="Entrez un nom" class="form-control">
        </div>

        <div class="mb-3">
            <label>Semaine de début :</label>
            <select name="semaine" required class="form-control">
                <?php foreach ($semaines as $s): ?>
                    <option value="<?php echo $s['debut']; ?>">Du <?php echo $s['debut']; ?> au <?php echo $s['fin']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Formule souhaitée :</label>
            <select name="formule" required class="form-control">
                <?php foreach ($formules as $f): ?>
                    <option value="<?php echo $f['type_formule']; ?>"><?php echo $f['type_formule']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Confirmer mon séjour</button>
        <a href="recherche.php" class="btn-cancel">Annuler</a>
    </form>
</main>

<?php require_once '../includes/footer.php'; ?>