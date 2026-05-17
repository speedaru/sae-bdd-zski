<?php
/**
 * Page de gestion des préférences de cohabitation - Zarza-Ski
 * Emplacement : src/pages/preferences.php
 */

require_once __DIR__ . '/../includes/header.php';

// Protection d'accès
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
    // 1. CHARGEMENT DE TOUS LES VOYAGEURS DU CARNET (Pour alimenter les dropdowns de création)
    $stmtVoy = $pdo->prepare("
        SELECT c.* FROM client c
        JOIN gestion_voyageurs gv ON c.id_client = gv.id_client
        WHERE gv.id_user = :id_user
        ORDER BY c.nom ASC, c.prenom ASC
    ");
    $stmtVoy->execute(['id_user' => $user_id]);
    $voyageurs = $stmtVoy->fetchAll(PDO::FETCH_ASSOC);

    // 2. CHARGEMENT DE LA PRÉFÉRENCE SÉLECTIONNÉE POUR ÉDITION
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

    // 3. CHARGEMENT DES RELATIONS EXISTANTES (Double contrôle d'appartenance au carnet)
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

<div class="row mt-4">
    <div class="col-md-3">
        <?php include __DIR__ . '/../includes/sidebar_client.php'; ?>
    </div>

    <div class="col-md-9">
        <div class="card shadow-sm border-0 p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1"><i class="fas fa-heart text-danger me-2"></i>Mes Préférences de séjour</h2>
                    <p class="text-muted mb-0">Organisez les affinités et interdits de cohabitation de votre tribu pour fluidifier la répartition des lits.</p>
                </div>
            </div>

            <hr>

            <?php if ($success) echo alert($success, 'success'); ?>
            <?php if ($error) echo alert($error, 'danger'); ?>

            <div class="row g-4 mt-2">
                
                <!-- LISTE DES RELATIONS EXISTANTES (COLONNE GAUCHE) -->
                <div class="col-lg-7">
                    <h5 class="fw-bold mb-3"><i class="fas fa-list me-2 text-secondary"></i>Affinités configurées</h5>
                    
                    <?php if (empty($preferences)): ?>
                        <div class="p-5 text-center bg-light rounded-3 border border-dashed">
                            <i class="fas fa-heart-broken fa-3x text-muted mb-3 opacity-50"></i>
                            <p class="text-muted mb-0">Aucune affinité de cohabitation n'a été spécifiée pour l'instant.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-light">
                                <thead class="table-light">
                                    <tr>
                                        <th>Émetteur</th>
                                        <th class="text-center">Relation</th>
                                        <th>Récepteur</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($preferences as $p): 
                                        $badge_class = 'bg-secondary';
                                        if ($p['niveau_preference'] === 'impératif') $badge_class = 'bg-success';
                                        elseif ($p['niveau_preference'] === 'Souhaitable') $badge_class = 'bg-info';
                                        elseif ($p['niveau_preference'] === 'Pas souhaitable') $badge_class = 'bg-warning text-dark';
                                        elseif ($p['niveau_preference'] === 'Interdit') $badge_class = 'bg-danger';
                                    ?>
                                        <tr class="<?php echo ($pref_edit && $pref_edit['id_client'] == $p['id_client'] && $pref_edit['id_client_1'] == $p['id_client_1']) ? 'table-warning' : ''; ?>">
                                            <td>
                                                <span class="fw-bold text-dark"><?php echo h($p['emetteur_prenom'] . ' ' . $p['emetteur_nom']); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?php echo $badge_class; ?> d-inline-block text-xs py-2 px-2" style="min-width: 110px;">
                                                    <?php echo ucfirst($p['niveau_preference']); ?>
                                                </span>
                                                <div class="text-muted text-xs mt-1"><i class="fas fa-chevron-right"></i></div>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark"><?php echo h($p['recepteur_prenom'] . ' ' . $p['recepteur_nom']); ?></span>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="preferences.php?action=edit&id_client=<?php echo $p['id_client']; ?>&id_client_1=<?php echo $p['id_client_1']; ?>" 
                                                       class="btn btn-outline-primary btn-sm" title="Modifier la relation">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </a>
                                                    <button class="btn btn-outline-danger btn-sm" 
                                                            onclick="confirmDeletePref(<?php echo $p['id_client']; ?>, <?php echo $p['id_client_1']; ?>, '<?php echo h(addslashes($p['emetteur_prenom'])); ?>', '<?php echo h(addslashes($p['recepteur_prenom'])); ?>')" 
                                                            title="Supprimer la relation">
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

                <!-- FORMULAIRE CRÉATION OU ÉDITION (COLONNE DROITE) -->
                <div class="col-lg-5">
                    <div class="p-4 bg-light rounded-3 border">
                        <?php if ($pref_edit): ?>
                            <h5 class="fw-bold mb-3 text-warning"><i class="fas fa-edit me-2"></i>Modifier l'affinité</h5>
                            <?php 
                            $form_action = "../actions/modifier_preference.php";
                            $submit_label = "Mettre à jour l'affinité";
                            include __DIR__ . '/../forms/form_preference.php'; 
                            ?>
                        <?php else: ?>
                            <h5 class="fw-bold mb-3 text-success"><i class="fas fa-plus me-2"></i>Ajouter une affinité</h5>
                            <?php 
                            $form_action = "../actions/ajouter_preference.php";
                            $submit_label = "Ajouter l'affinité";
                            include __DIR__ . '/../forms/form_preference.php'; 
                            ?>
                        <?php endif; ?>
                    </div>
                </div>

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