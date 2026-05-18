<?php
/**
 * Page de gestion du profil client - Zarza-Ski
 * Emplacement : src/pages/profil.php
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

?>

<div class="profile-container">
    
    <!-- Navigation latérale (Sidebar) -->
    <div class="profile-sidebar">
        <?php include __DIR__ . '/../includes/sidebar_client.php'; ?>
    </div>

    <!-- Contenu principal -->
    <div class="profile-content">
        
        <div class="profile-header">
            <h2>Mon Profil Skieur</h2>
            <p>Mettez à jour vos informations de sécurité physique pour préparer vos réservations de matériel.</p>
        </div>

        <!-- Alertes de retour -->
        <?php if ($success != null): ?>
            <div class="alert alert-success">Votre fiche a été mise à jour !</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <!-- Encadré du formulaire de modification -->
        <div class="form-wrapper">
            <?php 
//            $form_action = "profil.php"; 
            $form_action = "../actions/modifier_voyageur.php?redirect=" . add_current_url_with_args();
            $submit_label = "Enregistrer mes modifications";
            $voyageur = $client_data; 
            include __DIR__ . '/../forms/form_voyageur.php'; 
            ?>
        </div>
        
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>