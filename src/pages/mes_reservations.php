<?php
/**
 * page d'affichage des reservations
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// PROTECTION DE LA PAGE
// On vérifie d'abord si l'utilisateur est connecté au compte
require_login("../pages/mes_reservations.php");

// On récupère l'id_client en session
$id_client = $_SESSION['id_client'] ?? null;

// 2. LOGIQUE DE RÉCUPÉRATION DES DONNÉES
$reservations = [];
if ($id_client) {
    $sql = "SELECT 
                r.id_reservation,
                res.date_debut, 
                res.date_fin,
                r.num_chambre, 
                r.nom_groupe, 
                r.formule_prix_final, 
                c.batiment,
                c.etage
            FROM reserver r
            JOIN reservation res ON r.id_reservation = res.id_reservation
            JOIN chambre c ON r.num_chambre = c.num_chambre
            WHERE r.id_client = ?
            ORDER BY res.date_debut DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_client]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$nb_voyages = count($reservations);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mt-4">
    <!-- Barre latérale client -->
    <div class="col-md-3">
        <?php include __DIR__ . '/../includes/sidebar_client.php'; ?>
    </div>

    <!-- Contenu principal -->
    <div class="col-md-9">
        <div class="card shadow-sm border-0 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1"><i class="fas fa-skiing text-primary me-2"></i>Mes Séjours</h2>
                    <p class="text-muted mb-0">Retrouvez ici l'historique et le détail de vos réservations à Zarza-Ski.</p>
                </div>
                <?php if ($nb_voyages > 0): ?>
                    <span class="badge bg-primary rounded-pill px-3 py-2">
                        <?php echo $nb_voyages; ?> séjour(s)
                    </span>
                <?php endif; ?>
            </div>

            <hr>

            <?php if (!$id_client): ?>
                <!-- Cas où le profil n'est pas encore créé -->
                <div class="alert alert-warning border-0 shadow-sm p-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-edit fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading fw-bold">Profil incomplet</h5>
                            <p class="mb-0">Vous devez compléter votre fiche skieur avant de pouvoir effectuer ou consulter des réservations.</p>
                            <a href="profil.php" class="btn btn-warning btn-sm mt-3 fw-bold">Compléter mon profil maintenant</a>
                        </div>
                    </div>
                </div>

            <?php elseif ($nb_voyages === 0): ?>
                <!-- Cas où il n'y a aucune réservation -->
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-calendar-times fa-4x text-light"></i>
                    </div>
                    <h4 class="text-muted">Aucune réservation pour le moment</h4>
                    <p class="text-muted mb-4">Préparez vos skis et réservez votre prochain séjour !</p>
                    <a href="recherche.php" class="btn btn-primary px-4 shadow-sm">
                        <i class="fas fa-search me-2"></i>Rechercher un logement
                    </a>
                </div>

            <?php else: ?>
                <!-- Affichage de la liste des réservations -->
                <div class="table-responsive mt-3">
                    <table class="table table-hover align-middle border-light">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3">Période</th>
                                <th class="py-3">Logement</th>
                                <th class="py-3">Groupe</th>
                                <th class="py-3 text-center">Montant</th>
                                <th class="py-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $res): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold">Semaine du <?php echo date('d/m/Y', strtotime($res['date_debut'])); ?></div>
                                    <small class="text-muted">au <?php echo date('d/m/Y', strtotime($res['date_fin'])); ?></small>
                                </td>
                                <td>
                                    <span class="d-block fw-bold">Chambre <?php echo $res['num_chambre']; ?></span>
                                    <small class="text-muted">Bât. <?php echo htmlspecialchars($res['batiment']); ?> - Ét. <?php echo $res['etage']; ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($res['nom_groupe']); ?></span>
                                </td>
                                <td class="text-center fw-bold text-primary">
                                    <?php echo number_format($res['formule_prix_final'], 0, ',', ' '); ?> €
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="facture.php?chambre=<?php echo $res['num_chambre']; ?>&res=<?php echo $res['id_reservation']; ?>" 
                                           class="btn btn-outline-secondary btn-sm" title="Voir la facture">
                                            <i class="fas fa-file-invoice"></i>
                                        </a>
                                        <button class="btn btn-outline-danger btn-sm" 
                                                onclick="confirmCancellation(<?php echo $res['id_reservation']; ?>, <?php echo $res['num_chambre']; ?>)"
                                                title="Annuler le séjour">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 text-center">
                    <a href="recherche.php" class="text-decoration-none text-muted small">
                        <i class="fas fa-plus-circle me-1"></i> Réserver un autre séjour
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmation pour l'annulation (Optionnel mais recommandé) -->
<script>
function confirmCancellation(idRes, idChambre) {
    if (confirm("Êtes-vous sûr de vouloir annuler ce séjour ? Cette action est irréversible.")) {
        window.location.href = "annuler.php?res=" + idRes + "&chambre=" + idChambre;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>