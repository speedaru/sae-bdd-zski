<?php
/**
 * Page de gestion des groupes - Zarza-Ski
 * Emplacement : src/pages/groupes.php
 */

require_once __DIR__ . '/../includes/header.php';

require_login("../pages/groupes.php");

$user_id = $_SESSION['id_user'];
$groupes = [];
$edit_groupe = null;
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;

// Nettoyage des messages flash
unset($_SESSION['error'], $_SESSION['success']);

// Mode d'édition d'un groupe spécifique
$action = $_GET['action'] ?? '';
$editing_group_name = $_GET['nom'] ?? '';

if ($action === 'edit' && !empty($editing_group_name)) {
    try {
        // Vérifie la possession du groupe à éditer
        $stmt_edit = $pdo->prepare("SELECT * FROM groupe WHERE nom_groupe = :nom AND id_user = :id_user");
        $stmt_edit->execute(['nom' => $editing_group_name, 'id_user' => $user_id]);
        $edit_groupe = $stmt_edit->fetch(PDO::FETCH_ASSOC);
        
        if (!$edit_groupe) {
            $error = "Action non autorisée ou groupe inexistant.";
        }
    } catch (PDOException $e) {
        $error = "Erreur technique lors du chargement des données d'édition.";
    }
}

try {
    // Récupération globale pour la liste
    $sql = "SELECT * FROM groupe WHERE id_user = :id_user ORDER BY nom_groupe ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_user' => $user_id]);
    $groupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Une erreur technique est survenue lors du chargement de vos groupes.";
}
?>

<div class="row mt-4">
    <div class="col-md-3">
        <?php include __DIR__ . '/../includes/sidebar_client.php'; ?>
    </div>

    <div class="col-md-9">
        <div class="card shadow-sm border-0 p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1"><i class="fas fa-users-cog text-primary me-2"></i>Mes Groupes</h2>
                    <p class="text-muted mb-0">Organisez vos tribus pour vos séjours et locations communes.</p>
                </div>
            </div>

            <hr>

            <?php if ($success) echo alert($success, 'success'); ?>
            <?php if ($error) echo alert($error, 'danger'); ?>

            <div class="row g-4 mt-2">
                
                <!-- LISTE DES GROUPES -->
                <div class="col-lg-7">
                    <h5 class="fw-bold mb-3"><i class="fas fa-list me-2 text-secondary"></i>Groupes enregistrés</h5>
                    
                    <?php if (empty($groupes)): ?>
                        <div class="p-5 text-center bg-light rounded-3 border border-dashed">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">Vous n'avez créé aucun groupe de séjour pour l'instant.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-light">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nom du groupe</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($groupes as $g): ?>
                                        <tr class="<?php echo ($edit_groupe && $edit_groupe['nom_groupe'] === $g['nom_groupe']) ? 'table-warning' : ''; ?>">
                                            <td>
                                                <div class="fw-bold text-dark">
                                                    <i class="fas fa-user-friends me-2 text-muted"></i>
                                                    <?php echo h($g['nom_groupe']); ?>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <!-- Crayon pour l'édition -->
                                                    <a href="groupes.php?action=edit&nom=<?php echo urlencode($g['nom_groupe']); ?>" 
                                                       class="btn btn-outline-primary btn-sm" title="Modifier ce groupe">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </a>
                                                    <!-- Bouton de suppression -->
                                                    <button class="btn btn-outline-danger btn-sm" 
                                                            onclick="confirmDeleteGroup('<?php echo h(addslashes($g['nom_groupe'])); ?>')" 
                                                            title="Supprimer ce groupe">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- FORMULAIRE (DYNAMIQUE : CREATION OU EDITION) -->
                <div class="col-lg-5">
                    <div class="p-4 bg-light rounded-3 border">
                        <?php if ($edit_groupe): ?>
                            <h5 class="fw-bold mb-3 text-warning"><i class="fas fa-edit me-2"></i>Modifier le groupe</h5>
                            <?php 
                            $form_action = "../actions/modifier_groupe.php";
                            $submit_label = "Enregistrer";
                            $groupe = $edit_groupe;
                            include __DIR__ . '/../forms/form_groupe.php'; 
                            ?>
                        <?php else: ?>
                            <h5 class="fw-bold mb-3"><i class="fas fa-plus me-2 text-success"></i>Créer un groupe</h5>
                            <?php 
                            $form_action = "../actions/ajouter_groupe.php";
                            $submit_label = "Créer la tribu";
                            $groupe = null;
                            include __DIR__ . '/../forms/form_groupe.php'; 
                            ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<script>
function confirmDeleteGroup(groupName) {
    if (confirm("Attention ! Supprimer le groupe '" + groupName + "' annulera les réservations passées ou futures si aucune n'est active. Si des réservations sont en cours, la suppression sera bloquée par la base de données. Continuer ?")) {
        window.location.href = "../actions/supprimer_groupe.php?nom=" + encodeURIComponent(groupName);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>