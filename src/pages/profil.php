<?php
/**
 * Page de gestion du profil client - Zarza-Ski
 * Emplacement : src/pages/profil.php
 * Refactorisée pour utiliser le formulaire générique d'édition de voyageur (DRY)
 */
require_once __DIR__ . '/../includes/header.php';

require_login("../pages/espace_client.php");

$user_id = $_SESSION['id_user'];
$client_id = $_SESSION['id_client'] ?? null;
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;

// Nettoyage des messages flash
unset($_SESSION['error'], $_SESSION['success']);

// 1. CHARGEMENT DES DONNÉES EXISTANTES DEPUIS LA BASE
try {
    $stmt = $pdo->prepare("SELECT * FROM client WHERE id_client = ?");
    $stmt->execute([$client_id]);
    $client_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client_data) {
        throw new Exception("Fiche skieur introuvable. Veuillez contacter l'administration de la station.");
    }
} catch (Exception $e) {
    die("Erreur critique : " . $e->getMessage());
}

// 2. TRAITEMENT DE LA MISE À JOUR (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération sécurisée des variables envoyées par notre formulaire modulaire
    $nom            = trim($_POST['nom'] ?? '');
    $prenom         = trim($_POST['prenom'] ?? '');
    $date_naissance = trim($_POST['date_naissance'] ?? '');
    $adresse        = trim($_POST['adresse'] ?? '');
    $num_tel        = trim($_POST['num_tel'] ?? '');
    $niveau_ski     = $_POST['niveau_ski'] ?? 'débutant';
    $taille         = floatval($_POST['taille'] ?? 0);
    $poids          = intval($_POST['poids'] ?? 0);
    $pointure       = floatval($_POST['pointure'] ?? 0);

    // Validation des données obligatoires
    if (empty($nom) || empty($prenom) || empty($date_naissance) || empty($adresse) || empty($num_tel) || $taille <= 0 || $poids <= 0 || $pointure <= 0) {
        $error = "Veuillez remplir correctement l'ensemble des champs du profil.";
    } else {
        try {
            // Requête de mise à jour de la table client (incluant la nouvelle colonne date_naissance)
            $sql = "UPDATE client SET 
                    nom = :nom, 
                    prenom = :prenom, 
                    date_naissance = :date_naissance,
                    adresse = :adresse, 
                    num_tel = :num_tel, 
                    niveau_ski = :niveau_ski, 
                    taille = :taille, 
                    poids = :poids, 
                    pointure = :pointure 
                    WHERE id_client = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'nom'            => $nom,
                'prenom'         => $prenom,
                'date_naissance' => $date_naissance,
                'adresse'        => $adresse,
                'num_tel'        => $num_tel,
                'niveau_ski'     => $niveau_ski,
                'taille'         => $taille,
                'poids'          => $poids,
                'pointure'       => $pointure,
                'id'             => $client_id
            ]);

            $success = "Votre fiche skieur a été mise à jour avec succès !";
            
            // Rechargement des données fraîches pour mise à jour immédiate du formulaire
            $stmt_refresh = $pdo->prepare("SELECT * FROM client WHERE id_client = ?");
            $stmt_refresh->execute([$client_id]);
            $client_data = $stmt_refresh->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $error = "Erreur technique lors de la mise à jour : " . $e->getMessage();
        }
    }
}
?>

<div class="row mt-4">
    <!-- Barre de navigation latérale de l'Espace Client -->
    <div class="col-md-3">
        <?php include __DIR__ . '/../includes/sidebar_client.php'; ?>
    </div>

    <!-- Contenu Principal -->
    <div class="col-md-9">
        <div class="card shadow-sm border-0 p-4">
            
            <!-- En-tête de la fiche skieur -->
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary text-white rounded-circle p-3 me-3">
                    <i class="fas fa-user-gear fa-lg"></i>
                </div>
                <div>
                    <h2 class="mb-0">Ma Fiche Skieur</h2>
                    <p class="text-muted mb-0">Complétez et mettez à jour votre profil pour faciliter vos futures réservations et réservations de matériel.</p>
                </div>
            </div>

            <hr>

            <!-- Alertes -->
            <?php if ($success) echo alert($success, 'success'); ?>
            <?php if ($error) echo alert($error, 'danger'); ?>

            <div class="p-3 bg-light rounded-3 border">
                <?php 
                // Configuration des variables pour l'inclusion de notre formulaire réutilisable
                $form_action  = "profil.php";
                $submit_label = "Mettre à jour mon profil";
                
                // On passe les données du client au tableau $voyageur attendu par le partial
                $voyageur     = $client_data; 
                
                // Inclusion propre et isolée du formulaire
                include __DIR__ . '/../forms/form_voyageur.php'; 
                ?>
            </div>
            
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>