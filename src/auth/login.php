<?php
/**
 * page de connexion
 * gere la connexion a un compte utilisateur
 */

require_once '../includes/header.php';

$url = $_POST['redirect'] ?? $_GET['redirect'] ?? '../index.php';
$url = sanitize_redirect_url($url);

$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';

    if (empty($u) || empty($p)) {
        $err = "Veuillez remplir tous les champs.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id_user, id_client, username, mdp_hash, role FROM compte_utilisateur WHERE username = ?");
            $stmt->execute([$u]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($p, $user['mdp_hash'])) {
                session_regenerate_id();

                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['id_client'] = $user['id_client'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                header("Location: " . $url);
                exit();
            } else {
                $err = "Identifiants invalides.";
            }
        } catch (PDOException $e) {
            $err = "Une erreur technique est survenue.";
        }
    }
}
?>

<link rel="stylesheet" type="text/css" href="../css/login.css">

<main class="bloc-page">
    <div class="boite-login">
        <center>
            <h2>Connexion</h2>
            <p>Accédez à votre espace Zarza-Ski</p>
        </center>

        <?php if ($err): ?>
            <div class="boite-erreur">
                <b>Attention :</b> <?php echo htmlspecialchars($err); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($url); ?>">

            <p>
                <label for="username"><b>Nom d'utilisateur :</b></label><br>
                <input type="text" name="username" id="username" placeholder="Votre pseudo" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </p>

            <p>
                <label for="password"><b>Mot de passe :</b></label><br>
                <input type="password" name="password" id="password" placeholder="Votre mot de passe" required>
            </p>

            <p>
                <input type="submit" value="Se connecter" class="btn-validation">
            </p>
        </form>

        <hr>

        <center>
            <p>Pas encore de compte ?</p>
            <a href="register.php"><b>Créer un compte station</b></a>
        </center>
    </div>

    <br>
    <center>
        <a href="../index.php">Retour à l'accueil</a>
    </center>
</main>

<?php require_once '../includes/footer.php'; ?>