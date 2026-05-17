<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/header.php';

$err = null;
$suc = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';

    if (empty($nom) || empty($prenom) || empty($u) || empty($p)) {
        $err = "Tous les champs sont obligatoires.";
    } elseif (strlen($p) < 6) {
        $err = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        try {
            $pdo->beginTransaction();

            $sql1 = "INSERT INTO client (nom, prenom, adresse, num_tel, niveau_ski, taille, poids, pointure, date_naissance) 
                     VALUES (?, ?, 'À renseigner', '0000000000', 'débutant', 0, 0, 0, '2000-01-01')";
            $q1 = $pdo->prepare($sql1);
            $q1->execute([$nom, $prenom]);

            $id = $pdo->lastInsertId();

            $hash = password_hash($p, PASSWORD_DEFAULT);
            $sql2 = "INSERT INTO compte_utilisateur (username, mdp_hash, role, id_client) 
                     VALUES (?, ?, 'client', ?)";
            $q2 = $pdo->prepare($sql2);
            $q2->execute([$u, $hash, $id]);

            $pdo->commit();
            $suc = "Compte créé avec succès ! Vous pouvez vous connecter.";

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getCode() === '23505') {
                $err = "Ce nom d'utilisateur est déjà utilisé.";
            } else {
                $err = "Erreur technique lors de l'inscription.";
            }
        }
    }
}
?>

<link rel="stylesheet" type="text/css" href="../css/register.css">

<main class="bloc-page">
    <div class="boite-register">
        <center>
            <h2>Rejoindre Zarza-Ski</h2>
            <p>Créez votre compte pour réserver un séjour</p>
        </center>

        <?php if ($err): ?>
            <div class="boite-erreur">
                <b>Attention :</b> <?php echo htmlspecialchars($err); ?>
            </div>
        <?php endif; ?>

        <?php if ($suc): ?>
            <div class="boite-succes">
                <b>Félicitations :</b> <?php echo htmlspecialchars($suc); ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <p>
                <label for="nom"><b>Nom :</b></label><br>
                <input type="text" name="nom" id="nom" value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>" required>
            </p>

            <p>
                <label for="prenom"><b>Prénom :</b></label><br>
                <input type="text" name="prenom" id="prenom" value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>" required>
            </p>

            <p>
                <label for="username"><b>Nom d'utilisateur :</b></label><br>
                <input type="text" name="username" id="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </p>

            <p>
                <label for="password"><b>Mot de passe :</b></label><br>
                <input type="password" name="password" id="password" required>
            </p>

            <p>
                <input type="submit" value="S'inscrire" class="btn-validation">
            </p>
        </form>

        <hr>

        <center>
            <p>Déjà un compte ?</p>
            <a href="login.php"><b>Se connecter à l'espace</b></a>
        </center>
    </div>

    <br>
    <center>
        <a href="../index.php">Retour à l'accueil</a>
    </center>
</main>

<?php require_once '../includes/footer.php'; ?>