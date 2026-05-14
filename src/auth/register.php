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
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($nom) || empty($prenom) || empty($username) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        try {
            // Début de la transaction
            $pdo->beginTransaction();

            // ÉTAPE A : Création du profil client (avec valeurs par défaut pour les champs NOT NULL)
            $sqlClient = "INSERT INTO client (nom, prenom, adresse, num_tel, niveau_ski, taille, poids, pointure) 
                          VALUES (:nom, :prenom, 'À renseigner', '0000000000', 'débutant', 0, 0, 0)";
            $stmtClient = $pdo->prepare($sqlClient);
            $stmtClient->execute([
                'nom' => $nom,
                'prenom' => $prenom
            ]);

            // ÉTAPE B : Récupération de l'ID client généré
            $id_client = $pdo->lastInsertId();

            // ÉTAPE C : Création du compte utilisateur lié
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sqlUser = "INSERT INTO compte_utilisateur (username, mdp_hash, role, id_client) 
                        VALUES (:user, :hash, 'client', :id_client)";
            $stmtUser = $pdo->prepare($sqlUser);
            $stmtUser->execute([
                'user' => $username,
                'hash' => $hash,
                'id_client' => $id_client
            ]);

            // Validation de la transaction
            $pdo->commit();
            $success = "Compte créé avec succès ! Vous pouvez vous connecter.";

        } catch (PDOException $e) {
            // Annulation en cas d'erreur (ex: username déjà pris)
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getCode() === '23505') {
                $error = "Ce nom d'utilisateur est déjà utilisé.";
            } else {
                $error = "Erreur technique lors de l'inscription.";
            }
        }
    }
}
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Rejoindre Zarza-Ski</h2>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form action="register.php" method="POST">
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Nom</label>
                                <input type="text" name="nom" class="form-control" required value="<?php echo htmlspecialchars($nom ?? ''); ?>">
                            </div>
                            <div class="col">
                                <label class="form-label">Prénom</label>
                                <input type="text" name="prenom" class="form-control" required value="<?php echo htmlspecialchars($prenom ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nom d'utilisateur</label>
                            <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2">S'inscrire</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
