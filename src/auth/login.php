<?php
/**
 * page de connexion
 * gere la connexion a un compte utilisateur
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

// 1. Déterminer la destination de redirection
// On vérifie d'abord en POST (si le formulaire vient d'être soumis) puis en GET (arrivée initiale)
$redirect_to = $_POST['redirect'] ?? $_GET['redirect'] ?? '../index.php';

// SÉCURITÉ : Protection contre les redirections vers des sites 
// On s'assure que la redirection est interne (ne commence pas par http, https ou //)
if (preg_match('/^https?:\/\/|^\/\//i', $redirect_to)) {
    $redirect_to = '../index.php';
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id_user, id_client, username, mdp_hash, role FROM compte_utilisateur WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['mdp_hash'])) {
                session_regenerate_id();

                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['id_client'] = $user['id_client'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Redirection finale vers la page donee en parametre
                header("Location: " . $redirect_to);
                exit;
            } else {
                $error = "Identifiants invalides.";
            }
        } catch (PDOException $e) {
            $error = "Une erreur technique est survenue.";
        }
    }
}

?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold">Connexion</h2>
                        <p class="text-muted">Accédez à votre espace Zarza-Ski</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger mb-3 py-2" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <!-- CHAMP CACHÉ : On conserve la destination pendant la soumission du formulaire -->
                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect_to); ?>">

                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Votre pseudo" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Votre mot de passe" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3 shadow-sm">Se connecter</button>

                        <hr class="my-4 text-muted">

                        <div class="text-center">
                            <p class="mb-0 text-muted">Pas encore de compte ?</p>
                            <a href="register.php" class="text-primary fw-bold text-decoration-none">Créer un compte station</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="../index.php" class="text-muted text-decoration-none small">
                    <i class="fas fa-arrow-left me-1"></i> Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
