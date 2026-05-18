<?php
/**
 * page de gestion des préférences de cohabitation
 */

require_once __DIR__ . '/../includes/header.php';

require_login("../pages/preferences.php");

$user_id = $_SESSION['id_user'];
$preferences = [];
$voyageurs = [];
$pref_edit = null;

$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);

$action = $_GET['action'] ?? '';
$id_emetteur = isset($_GET['id_client']) ? intval($_GET['id_client']) : 0;
$id_recepteur = isset($_GET['id_client_1']) ? intval($_GET['id_client_1']) : 0;

try {
    // chargement de tous les voyageurs du carnet
    $stmtVoy = $pdo->prepare("
        SELECT c.* FROM client c
        JOIN gestion_voyageurs gv ON c.id_client = gv.id_client
        WHERE gv.id_user = :id_user
        ORDER BY c.nom ASC, c.prenom ASC
    ");
    $stmtVoy->execute(['id_user' => $user_id]);
    $voyageurs = $stmtVoy->fetchAll(PDO::FETCH_ASSOC);

    // chargement de la preference sélectionnee pour edition
    if ($action === 'edit' && $id_emetteur > 0 && $id_recepteur > 0) {
        $stmt_edit = $pdo->prepare("
            SELECT p.*,
                   c1.nom AS emetteur_nom, c1.prenom AS emetteur_prenom,
                   c2.nom AS recepteur_nom, c2.prenom AS recepteur_prenom
            FROM preference p
            INNER JOIN client c1 ON p.id_client = c1.id_client
            INNER JOIN client c2 ON p.id_client_1 = c2.id_client
            INNER JOIN gestion_voyageurs gv1 ON p.id_client = gv1.id_client
            INNER JOIN gestion_voyageurs gv2 ON p.id_client_1 = gv2.id_client
            WHERE p.id_client = :emetteur AND p.id_client_1 = :recepteur
              AND gv1.id_user = :id_user AND gv2.id_user = :id_user
        ");
        $stmt_edit->execute([
            'emetteur'  => $id_emetteur,
            'recepteur' => $id_recepteur,
            'id_user'   => $user_id
        ]);
        $pref_edit = $stmt_edit->fetch(PDO::FETCH_ASSOC);
        
        if (!$pref_edit) {
            $error = "Affinité introuvable ou non autorisée.";
        }
    }

    // chargement des relations existantes
    $sql_list = "
        SELECT 
            p.id_client,
            p.id_client_1,
            p.niveau_preference,
            c1.nom AS emetteur_nom,
            c1.prenom AS emetteur_prenom,
            c2.nom AS recepteur_nom,
            c2.prenom AS recepteur_prenom
        FROM preference p
        INNER JOIN client c1 ON p.id_client = c1.id_client
        INNER JOIN client c2 ON p.id_client_1 = c2.id_client
        INNER JOIN gestion_voyageurs gv1 ON p.id_client = gv1.id_client
        INNER JOIN gestion_voyageurs gv2 ON p.id_client_1 = gv2.id_client
        WHERE gv1.id_user = :id_user AND gv2.id_user = :id_user
        ORDER BY c1.nom ASC, c1.prenom ASC
    ";
    $stmt_list = $pdo->prepare($sql_list);
    $stmt_list->execute(['id_user' => $user_id]);
    $preferences = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Une erreur technique s'est produite : " . $e->getMessage();
}
?>

<div class="preferences-container">
    <!-- barre de navigation -->
    <div class="preferences-sidebar">
        <?php include __DIR__ . '/../includes/sidebar_client.php'; ?>
    </div>

    <!-- contenu principal -->
    <div class="preferences-content">
        
        <div class="preferences-header">
            <h2>Mes Préférences de cohabitation</h2>
            <p>Déterminez les affinités ou incompatibilités de cohabitation entre les skieurs de votre foyer pour simplifier la répartition.</p>
        </div>

        <!-- alertes d'etat -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo h($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="preferences-grid">
            
            <!-- liste des relations -->
            <div class="preferences-list-section">
                <h3>Affinités et liaisons configurées</h3>
                
                <?php if (empty($preferences)): ?>
                    <div class="empty-state">
                        <p>Aucune préférence de cohabitation n'a été spécifiée pour l'instant. Utilisez le formulaire ci-contre pour lier deux personnes.</p>
                    </div>
                <?php else: ?>
                    <table class="academic-table">
                        <thead>
                            <tr>
                                <th>Émetteur</th>
                                <th>Relation (&rarr;)</th>
                                <th>Récepteur</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preferences as $p): 
                                $badge_class = 'pref-neutral';
                                if ($p['niveau_preference'] === 'impératif') $badge_class = 'pref-imperative';
                                elseif ($p['niveau_preference'] === 'Souhaitable') $badge_class = 'pref-desirable';
                                elseif ($p['niveau_preference'] === 'Pas souhaitable') $badge_class = 'pref-undesirable';
                                elseif ($p['niveau_preference'] === 'Interdit') $badge_class = 'pref-forbidden';
                            ?>
                                <tr class="<?php echo ($pref_edit && $pref_edit['id_client'] == $p['id_client'] && $pref_edit['id_client_1'] == $p['id_client_1']) ? 'editing-row' : ''; ?>">
                                    <td>
                                        <span class="client-name"><?php echo h($p['emetteur_prenom'] . ' ' . $p['emetteur_nom']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge-pref-level <?php echo $badge_class; ?>">
                                            <?php echo ucfirst(h($p['niveau_preference'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="client-name"><?php echo h($p['recepteur_prenom'] . ' ' . $p['recepteur_nom']); ?></span>
                                    </td>
                                    <td class="text-right">
                                        <div class="action-buttons">
                                            <a href="preferences.php?action=edit&id_client=<?php echo $p['id_client']; ?>&id_client_1=<?php echo $p['id_client_1']; ?>" class="btn-edit" title="Modifier">
                                                Modifier
                                            </a>
                                            <button class="btn-delete" onclick="confirmDeletePref(<?php echo $p['id_client']; ?>, <?php echo $p['id_client_1']; ?>, '<?php echo h(addslashes($p['emetteur_prenom'])); ?>', '<?php echo h(addslashes($p['recepteur_prenom'])); ?>')" title="Supprimer">
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

            <!-- formulaire dynamique -->
            <div class="preferences-form-section">
                <?php if ($pref_edit): ?>
                    <h3 class="form-title edit-mode">Modifier l'affinité</h3>
                    <div class="form-box border-edit">
                        <?php 
                        $form_action = "../actions/modifier_preference.php";
                        $submit_label = "Mettre à jour l'affinité";
                        include __DIR__ . '/../forms/form_preference.php'; 
                        ?>
                    </div>
                <?php else: ?>
                    <h3 class="form-title">Lier deux skieurs</h3>
                    <div class="form-box">
                        <?php 
                        $form_action = "../actions/ajouter_preference.php";
                        $submit_label = "Ajouter la préférence";
                        include __DIR__ . '/../forms/form_preference.php'; 
                        ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>

<script>
function confirmDeletePref(idClient, idClient1, emetteur, recepteur) {
    if (confirm("Êtes-vous sûr de vouloir supprimer l'affinité déclarée par " + emetteur + " envers " + recepteur + " ?")) {
        window.location.href = "../actions/supprimer_preference.php?id_client=" + idClient + "&id_client_1=" + idClient1;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
