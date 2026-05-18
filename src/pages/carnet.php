<?php
/**
 * Page Carnet de Voyageurs - Zarza-Ski
 * Emplacement : src/pages/carnet.php
 * Version épurée et académique
 */
require_once __DIR__ . '/../includes/header.php';

require_login("../pages/carnet.php");

$user_id = $_SESSION['id_user'];
$voyageurs = [];
$edit_voyageur = null;
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;

// Nettoyage des messages flash
unset($_SESSION['error'], $_SESSION['success']);

// Mode d'édition d'un voyageur spécifique
$action = $_GET['action'] ?? '';
$editing_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($action === 'edit' && $editing_id > 0) {
    try {
        $stmt_edit = $pdo->prepare("SELECT c.* FROM client c 
                                    JOIN gestion_voyageurs gv ON c.id_client = gv.id_client 
                                    WHERE gv.id_user = :id_user AND c.id_client = :id_client");
        $stmt_edit->execute(['id_user' => $user_id, 'id_client' => $editing_id]);
        $edit_voyageur = $stmt_edit->fetch(PDO::FETCH_ASSOC);
        
        if (!$edit_voyageur) {
            $error = "Action non autorisée ou voyageur introuvable.";
        }
    } catch (PDOException $e) {
        $error = "Erreur technique lors du chargement des données.";
    }
}

try {
    // Récupération des skieurs du carnet
    $sql = "SELECT c.* FROM client c 
            JOIN gestion_voyageurs gv ON c.id_client = gv.id_client 
            WHERE gv.id_user = :id_user
            ORDER BY c.nom ASC, c.prenom ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_user' => $user_id]);
    $voyageurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Une erreur technique est survenue lors de la récupération de vos voyageurs.";
}
?>

<div class="carnet-container">
    <!-- Navigation latérale (Sidebar) -->
    <div class="carnet-sidebar">
        <?php include __DIR__ . '/../includes/sidebar_client.php'; ?>
    </div>

    <!-- Contenu Principal -->
    <div class="carnet-content">
        
        <div class="carnet-header">
            <h2>Ma Tribu — Carnet de Voyageurs</h2>
            <p>Gérez les membres de votre foyer et vos proches pour préparer et simplifier vos réservations.</p>
        </div>

        <!-- Alertes -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo h($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="carnet-grid">
            
            <!-- BLOC GAUCHE : LISTE DES VOYAGEURS -->
            <div class="carnet-list-section">
                <h3>Membres enregistrés</h3>
                
                <?php if (empty($voyageurs)): ?>
                    <div class="empty-state">
                        <p>Votre carnet de voyageurs est vide. Ajoutez vos proches ci-contre pour pouvoir configurer votre séjour.</p>
                    </div>
                <?php else: ?>
                    <table class="academic-table">
                        <thead>
                            <tr>
                                <th>Identité & Contact</th>
                                <th>Niveau</th>
                                <th>Spécifcations</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($voyageurs as $voy): ?>
                                <tr class="<?php echo ($edit_voyageur && $edit_voyageur['id_client'] === $voy['id_client']) ? 'editing-row' : ''; ?>">
                                    <td>
                                        <span class="skier-name"><?php echo h($voy['nom']) . ' ' . h($voy['prenom']); ?></span>
                                        <div class="skier-meta">Tél : <?php echo h($voy['num_tel']); ?></div>
                                        <div class="skier-meta">Né le : <?php echo date_fr($voy['date_naissance']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge-level"><?php echo ucfirst(h($voy['niveau_ski'])); ?></span>
                                    </td>
                                    <td>
                                        <ul class="skier-hardware-list">
                                            <li>Taille : <?php echo (float)$voy['taille']; ?> m</li>
                                            <li>Poids : <?php echo (int)$voy['poids']; ?> kg</li>
                                            <li>Pointure : <?php echo (float)$voy['pointure']; ?> EU</li>
                                        </ul>
                                    </td>
                                    <td class="text-right">
                                        <div class="action-buttons">
                                            <a href="carnet.php?action=edit&id=<?php echo $voy['id_client']; ?>" class="btn-edit" title="Modifier">
                                                Modifier
                                            </a>
                                            <button class="btn-delete" onclick="confirmDelete(<?php echo $voy['id_client']; ?>, '<?php echo h(addslashes($voy['prenom'])) . ' ' . h(addslashes($voy['nom'])); ?>')" title="Supprimer">
                                                Supprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- BLOC DROITE : FORMULAIRE D'AJOUT OU MODIFICATION -->
            <div class="carnet-form-section">
                <?php if ($edit_voyageur): ?>
                    <h3 class="form-title edit-mode">Modifier le membre</h3>
                    <div class="form-box border-edit">
                        <?php 
                        $form_action = "../actions/modifier_voyageur.php?redirect=" . add_current_url_with_args();
                        $cancel_label = "Annuler";
                        $submit_label = "Enregistrer";
                        $voyageur = $edit_voyageur;
                        include __DIR__ . '/../forms/form_voyageur.php'; 
                        ?>
                    </div>
                <?php else: ?>
                    <h3 class="form-title">Ajouter un membre</h3>
                    <div class="form-box">
                        <?php 
                        $form_action = "../actions/ajouter_voyageur.php?redirect=" . add_current_url_with_args();
                        $submit_label = "Ajouter à ma tribu";
                        $voyageur = null;
                        include __DIR__ . '/../forms/form_voyageur.php'; 
                        ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
function confirmDelete(idClient, fullName) {
    if (confirm("Êtes-vous sûr de vouloir supprimer " + fullName + " de votre tribu ? Cette action supprimera définitivement sa fiche skieur.")) {
        window.location.href = "../actions/supprimer_voyageur.php?id=" + idClient;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>