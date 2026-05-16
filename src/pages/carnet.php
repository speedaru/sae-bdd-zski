<?php
/**
 * Page Carnet de Voyageurs - Zarza-Ski
 * Emplacement : src/pages/carnet.php
 * Affiche et permet de gérer la liste des voyageurs rattachés au compte.
 */
// On passe par l'inclusion du header qui exécute déjà init.php (session + db + functions)
require_once __DIR__ . '/../includes/header.php';

require_login("../pages/carnet.php");

$user_id = $_SESSION['id_user'];
$voyageurs = [];
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;

// Nettoyage des messages flash en session pour éviter qu'ils s'affichent indéfiniment
unset($_SESSION['error'], $_SESSION['success']);

try {
    // Requête SQL de sélection : Récupère les skieurs associés au carnet d'adresse de l'utilisateur
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

<div class="row mt-4">
    <!-- Navigation Latérale -->
    <div class="col-md-3">
        <?php include __DIR__ . '/../includes/sidebar_client.php'; ?>
    </div>

    <!-- Contenu Principal -->
    <div class="col-md-9">
        <div class="card shadow-sm border-0 p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1"><i class="fas fa-users text-primary me-2"></i>Ma Tribu</h2>
                    <p class="text-muted mb-0">Gérez le carnet d'adresses de vos proches pour simplifier vos réservations.</p>
                </div>
            </div>

            <hr>

            <!-- Messages d'alertes -->
            <?php if ($success) echo alert($success, 'success'); ?>
            <?php if ($error) echo alert($error, 'danger'); ?>

            <div class="row g-4 mt-2">
                
                <!-- LISTE DES VOYAGEURS (COLONNE GAUCHE) -->
                <div class="col-lg-7">
                    <h5 class="fw-bold mb-3"><i class="fas fa-list me-2 text-secondary"></i>Membres enregistrés</h5>
                    
                    <?php if (empty($voyageurs)): ?>
                        <div class="p-5 text-center bg-light rounded-3 border border-dashed">
                            <i class="fas fa-address-book fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">Votre carnet est vide. Ajoutez vos proches pour préparer votre séjour.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-light">
                                <thead class="table-light">
                                    <tr>
                                        <th>Identité</th>
                                        <th>Niveau</th>
                                        <th class="text-center">Matériel</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($voyageurs as $voy): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo h($voy['nom']) . ' ' . h($voy['prenom']); ?></div>
                                                <small class="text-muted"><i class="fas fa-phone-alt me-1"></i><?php echo h($voy['num_tel']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-primary border">
                                                    <?php echo ucfirst(h($voy['niveau_ski'])); ?>
                                                </span>
                                            </td>
                                            <td class="text-center text-muted small">
                                                <i class="fas fa-ruler-vertical me-1"></i><?php echo (float)$voy['taille']; ?>m<br>
                                                <i class="fas fa-weight me-1"></i><?php echo (int)$voy['poids']; ?>kg<br>
                                                <i class="fas fa-shoe-prints me-1"></i>T. <?php echo (float)$voy['pointure']; ?>
                                            </td>
                                            <td class="text-end">
                                                <!-- Bouton de suppression avec appel JavaScript -->
                                                <button class="btn btn-outline-danger btn-sm" 
                                                        onclick="confirmDelete(<?php echo $voy['id_client']; ?>, '<?php echo h(addslashes($voy['prenom'])) . ' ' . h(addslashes($voy['nom'])); ?>')" 
                                                        title="Supprimer ce contact">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- FORMULAIRE D'AJOUT (COLONNE DROITE) -->
                <div class="col-lg-5">
                    <div class="p-4 bg-light rounded-3 border">
                        <h5 class="fw-bold mb-3"><i class="fas fa-user-plus me-2 text-success"></i>Ajouter un membre</h5>
                        
                        <?php 
                        $form_action = "../actions/ajouter_voyageur.php";
                        $submit_label = "Ajouter à ma tribu";
                        include __DIR__ . '/../forms/form_voyageur.php'; 
                        ?>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<!-- Script de confirmation de suppression -->
<script>
function confirmDelete(idClient, fullName) {
    if (confirm("Êtes-vous sûr de vouloir supprimer " + fullName + " de votre tribu ? Cette action est irréversible et supprimera définitivement sa fiche skieur.")) {
        window.location.href = "../actions/supprimer_voyageur.php?id=" + idClient;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>