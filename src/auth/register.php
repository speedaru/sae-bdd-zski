<?php
/**
 * page d'inscription
 * gère la création de comptes utilisateurs
 */

// connexion et header
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        try {
            // Hachage du mot de passe pour la sécurité
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Préparation de la requête d'insertion
            // Le rôle est fixé à 'client' par défaut selon les consignes
            $stmt = $pdo->prepare("INSERT INTO compte_utilisateur (username, mdp_hash, role) VALUES (:user, :hash, 'client')");
            
            $stmt->execute([
                'user' => $username,
                'hash' => $hash
            ]);

            $success = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
            
            // Optionnel : Redirection après un court délai
            // header("Refresh: 2; URL=login.php");

        } catch (PDOException $e) {
            // Code d'erreur PostgreSQL 23505 = Unique Violation (le username existe déjà)
            if ($e->getCode() === '23505') {
                $error = "Ce nom d'utilisateur est déjà utilisé.";
            } else {
                $error = "Une erreur est survenue lors de l'inscription : " . $e->getMessage();
            }
        }
    }
}
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Créer un compte</h2>
                    <p class="text-muted text-center mb-4">Rejoignez la station Zarza-Ski</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger mb-3" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success mb-3" role="alert">
                            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form action="register.php" method="POST" class="auth-form">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" 
                                   name="username" 
                                   id="username" 
                                   class="form-control" 
                                   placeholder="ex: j.durand"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                   required>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="form-control" 
                                   placeholder="••••••••"
                                   required>
                            <div class="form-text">Minimum 6 caractères.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                            S'inscrire
                        </button>

                        <div class="text-center">
                            <span class="text-muted">Déjà un compte ?</span>
                            <a href="login.php" class="text-decoration-none ms-1">Se connecter</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
