<?php
/**
 * Page de gestion du profil client - Zarza-Ski
 * Emplacement : src/pages/profil.php
 */
require_once __DIR__ . '/../includes/header.php';

require_login("../pages/espace_client.php");

$user_id = $_SESSION['id_user'];
$client_id = $_SESSION['id_client'] ?? null;
$error = null;
$success = null;

// 1. CHARGEMENT DES DONNÉES EXISTANTES
try {
    $stmt = $pdo->prepare("SELECT * FROM client WHERE id_client = ?");
    $stmt->execute([$client_id]);
    $client_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client_data) {
        throw new Exception("Profil client introuvable. Veuillez contacter l'administrateur.");
    }
} catch (Exception $e) {
    die("Erreur critique : " . $e->getMessage());
}

// 2. TRAITEMENT DE LA MISE À JOUR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération sécurisée avec valeurs par défaut pour éviter les "Undefined index"
    $nom        = trim($_POST['nom'] ?? '');
    $prenom     = trim($_POST['prenom'] ?? '');
    $adresse    = trim($_POST['adresse'] ?? '');
    $num_tel    = trim($_POST['num_tel'] ?? '');
    $niveau_ski = $_POST['niveau_ski'] ?? 'débutant';
    $taille     = $_POST['taille'] ?? 0;
    $poids      = $_POST['poids'] ?? 0;
    $pointure   = $_POST['pointure'] ?? 0;

    // Validation simple
    if (empty($nom) || empty($prenom) || empty($num_tel) || $taille <= 0 || $poids <= 0) {
        $error = "Veuillez remplir tous les champs obligatoires avec des valeurs valides.";
    } else {
        try {
            $sql = "UPDATE client SET 
                    nom = :nom, 
                    prenom = :prenom, 
                    adresse = :adresse, 
                    num_tel = :num_tel, 
                    niveau_ski = :niveau_ski, 
                    taille = :taille, 
                    poids = :poids, 
                    pointure = :pointure 
                    WHERE id_client = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'nom'        => $nom,
                'prenom'     => $prenom,
                'adresse'    => $adresse,
                'num_tel'    => $num_tel,
                'niveau_ski' => $niveau_ski,
                'taille'     => $taille,
                'poids'      => $poids,
                'pointure'   => $pointure,
                'id'         => $client_id
            ]);

            $success = "Votre profil a été mis à jour avec succès !";
            
            // Rechargement des données fraîches
            $stmt_refresh = $pdo->prepare("SELECT * FROM client WHERE id_client = ?");
            $stmt_refresh->execute([$client_id]);
            $client_data = $stmt_refresh->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $error = "Erreur SQL : " . $e->getMessage();
        }
    }
}
?>

<div class="row mt-4">
    <div class="col-md-3">
        <?php include __DIR__ . '/../includes/sidebar_client.php'; ?>
    </div>
    <div class="col-md-9">
        <div class="card shadow-sm border-0 p-4">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary text-white rounded-circle p-3 me-3">
                    <i class="fas fa-user-gear fa-lg"></i>
                </div>
                <div>
                    <h2 class="mb-0">Ma Fiche Skieur</h2>
                    <p class="text-muted mb-0">Complétez vos informations pour faciliter vos locations de matériel.</p>
                </div>
            </div>

            <hr>

            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <div><?php echo $success; ?></div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo $error; ?></div>
                </div>
            <?php endif; ?>

            <form action="profil.php" method="POST" class="row g-4">
                <!-- Identité -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nom</label>
                    <input type="text" name="nom" class="form-control" 
                           value="<?php echo htmlspecialchars($client_data['nom']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Prénom</label>
                    <input type="text" name="prenom" class="form-control" 
                           value="<?php echo htmlspecialchars($client_data['prenom']); ?>" required>
                </div>

                <!-- Contact -->
                <div class="col-12">
                    <label class="form-label fw-bold">Adresse</label>
                    <?php 
                        // Logique de placeholder demandée
                        $addr_val = $client_data['adresse'];
                        $display_addr = ($addr_val === 'À renseigner') ? '' : $addr_val;
                    ?>
                    <input type="text" name="adresse" class="form-control" 
                           placeholder="Ex: 123 Rue de la Montagne, 75000 Paris" 
                           value="<?php echo htmlspecialchars($display_addr); ?>" required>
                    <?php if($addr_val === 'À renseigner'): ?>
                        <div class="form-text text-warning"><i class="fas fa-info-circle me-1"></i> Veuillez renseigner votre adresse.</div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Numéro de téléphone</label>
                    <input type="text" name="num_tel" class="form-control" 
                           placeholder="0601020304"
                           value="<?php echo ($client_data['num_tel'] === '0000000000') ? '' : htmlspecialchars($client_data['num_tel']); ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Niveau de ski</label>
                    <select name="niveau_ski" class="form-select">
                        <option value="débutant" <?php echo ($client_data['niveau_ski'] == 'débutant') ? 'selected' : ''; ?>>Débutant (Flocon / 1ère Étoile)</option>
                        <option value="moyen" <?php echo ($client_data['niveau_ski'] == 'moyen') ? 'selected' : ''; ?>>Moyen (2ème / 3ème Étoile)</option>
                        <option value="confirmé" <?php echo ($client_data['niveau_ski'] == 'confirmé') ? 'selected' : ''; ?>>Confirmé (Compétition / Hors-piste)</option>
                    </select>
                </div>

                <!-- Mensurations (Crucial pour la sécurité des fixations) -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Taille (m)</label>
                    <div class="input-group">
                        <input type="number" step="0.01" name="taille" class="form-control" 
                               value="<?php echo (float)$client_data['taille'] ?: ''; ?>" required>
                        <span class="input-group-text">m</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Poids (kg)</label>
                    <div class="input-group">
                        <input type="number" name="poids" class="form-control" 
                               value="<?php echo (int)$client_data['poids'] ?: ''; ?>" required>
                        <span class="input-group-text">kg</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Pointure</label>
                    <div class="input-group">
                        <input type="number" step="0.5" name="pointure" class="form-control" 
                               value="<?php echo (float)$client_data['pointure'] ?: ''; ?>" required>
                        <span class="input-group-text">EU</span>
                    </div>
                </div>

                <div class="col-12 mt-4 text-end">
                    <button type="submit" class="btn btn-primary px-5 py-2 shadow-sm">
                        <i class="fas fa-save me-2"></i>Enregistrer mes informations
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
