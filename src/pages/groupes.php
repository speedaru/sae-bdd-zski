<?php
require_once __DIR__ . '/../includes/header.php';

require_login("../pages/groupes.php");

$user_id = $_SESSION['id_user'];
$groupes = [];
$edit_groupe = null;
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;

// nettoyage des messages
unset($_SESSION['error'], $_SESSION['success']);

// mode d'edition d'un groupe specifique
$action = $_GET['action'] ?? '';
$editing_group_name = $_GET['nom'] ?? '';

if ($action === 'edit' && !empty($editing_group_name)) {
    try {
        // verifie la possession du groupe à editer
        $stmt_edit = $pdo->prepare("SELECT * FROM groupe WHERE nom_groupe = :nom AND id_user = :id_user");
        $stmt_edit->execute(['nom' => $editing_group_name, 'id_user' => $user_id]);
        $edit_groupe = $stmt_edit->fetch(PDO::FETCH_ASSOC);
        
        if (!$edit_groupe) {
            $error = "Action non autorisee ou groupe inexistant.";
        }
    } catch (PDOException $e) {
        $error = "Erreur technique lors du chargement des donnees d'edition.";
    }
}

try {
    // recuperation globale pour la liste
    $sql = "SELECT * FROM groupe WHERE id_user = :id_user ORDER BY nom_groupe ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_user' => $user_id]);
    $groupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Une erreur technique est survenue lors du chargement de vos groupes.";
}
?>

<div class="groupes-container">
    <div class="groupes-sidebar">
        <?php include __DIR__ . '/../includes/sidebar_client.php'; ?>
    </div>

    <!-- contenu principal -->
    <div class="groupes-content">
        
        <div class="groupes-header">
            <h2>Mes Groupes — Tribus de Sejour</h2>
            <p>Gerez et organisez vos groupes pour vos sejours, hebergements et locations communes à la station.</p>
        </div>

        <!-- alertes de retour -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo h($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="groupes-grid">
            
            <!-- liste des groupes -->
            <div class="groupes-list-section">
                <h3>Groupes enregistres</h3>
                
                <?php if (empty($groupes)): ?>
                    <div class="empty-state">
                        <p>Vous n'avez cree aucun groupe de sejour pour l'instant. Utilisez le formulaire ci-contre pour demarrer.</p>
                    </div>
                <?php else: ?>
                    <table class="academic-table">
                        <thead>
                            <tr>
                                <th>Nom du groupe de sejour</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groupes as $g): ?>
                                <tr class="<?php echo ($edit_groupe && $edit_groupe['nom_groupe'] === $g['nom_groupe']) ? 'editing-row' : ''; ?>">
                                    <td>
                                        <span class="group-name-label"><?php echo h($g['nom_groupe']); ?></span>
                                    </td>
                                    <td class="text-right">
                                        <div class="action-buttons">
                                            <a href="groupes.php?action=edit&nom=<?php echo urlencode($g['nom_groupe']); ?>" class="btn-edit" title="Modifier">
                                                Modifier
                                            </a>
                                            <button class="btn-delete" onclick="confirmDeleteGroup('<?php echo h(addslashes($g['nom_groupe'])); ?>')" title="Supprimer">
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

            <!-- formulaire creation ou edition -->
            <div class="groupes-form-section">
                <?php if ($edit_groupe): ?>
                    <h3 class="form-title edit-mode">Modifier le groupe</h3>
                    <div class="form-box border-edit">
                        <?php 
                        $form_action = "../actions/modifier_groupe.php";
                        $submit_label = "Enregistrer";
                        $groupe = $edit_groupe;
                        include __DIR__ . '/../forms/form_groupe.php'; 
                        ?>
                    </div>
                <?php else: ?>
                    <h3 class="form-title">Creer un groupe</h3>
                    <div class="form-box">
                        <?php 
                        $form_action = "../actions/ajouter_groupe.php";
                        $submit_label = "Creer la tribu";
                        $groupe = null;
                        include __DIR__ . '/../forms/form_groupe.php'; 
                        ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
function confirmDeleteGroup(groupName) {
    if (confirm("Attention ! Supprimer le groupe '" + groupName + "' annulera les reservations passees ou futures si aucune n'est active. Si des reservations sont en cours, la suppression sera bloquee par la base de donnees. Continuer ?")) {
        window.location.href = "../actions/supprimer_groupe.php?nom=" + encodeURIComponent(groupName);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
