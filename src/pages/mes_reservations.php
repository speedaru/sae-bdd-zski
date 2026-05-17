<?php
/**
 * Page d'affichage des réservations - Zarza-Ski
 * Emplacement : src/pages/mes_reservations.php
 * Affiche l'historique des séjours passés ou futurs de l'utilisateur.
 */

require_once __DIR__ . '/../includes/header.php';

// Protection de la page : accessible uniquement aux membres connectés
require_login("../pages/mes_reservations.php");

$user_id = $_SESSION['id_user'];
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;

// Nettoyage des messages flash
unset($_SESSION['error'], $_SESSION['success']);

try {
    // 1. REQUÊTE PRINCIPALE : Récupère les réservations et calcule la somme globale des factures par séjour
    $sql_main = "SELECT 
                    r.id_reservation, 
                    r.date_debut, 
                    r.date_fin, 
                    r.nom_groupe,
                    COALESCE(SUM(f.montant_total), 0) AS prix_total_sejour
                 FROM reservation r
                 INNER JOIN groupe g ON r.nom_groupe = g.nom_groupe
                 LEFT JOIN facturation f ON r.id_reservation = f.id_reservation
                 WHERE g.id_user = :id_user
                 GROUP BY r.id_reservation, r.date_debut, r.date_fin, r.nom_groupe
                 ORDER BY r.date_debut DESC";

    $stmt_main = $pdo->prepare($sql_main);
    $stmt_main->execute(['id_user' => $user_id]);
    $reservations = $stmt_main->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Une erreur est survenue lors du chargement de vos séjours : " . $e->getMessage();
}
?>

<div class="row mt-4">
    <!-- Menu Latéral Client -->
    <div class="col-md-3">
        <?php include __DIR__ . '/../includes/sidebar_client.php'; ?>
    </div>

    <!-- Contenu Principal -->
    <div class="col-md-9">
        <div class="card shadow-sm border-0 p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1 fw-bold"><i class="fas fa-skiing text-primary me-2"></i>Mes Séjours</h2>
                    <p class="text-muted mb-0">Consultez, gérez et suivez l'ensemble de vos réservations à la station Zarza-Ski.</p>
                </div>
                <?php if (!empty($reservations)): ?>
                    <span class="badge bg-primary rounded-pill px-3 py-2">
                        <?php echo count($reservations); ?> séjour(s)
                    </span>
                <?php endif; ?>
            </div>

            <hr>

            <!-- Affichage des messages flash d'erreur ou de succès -->
            <?php if ($success) echo alert($success, 'success'); ?>
            <?php if ($error) echo alert($error, 'danger'); ?>

            <?php if (empty($reservations)): ?>
                <!-- Aucun séjour trouvé -->
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-calendar-times fa-4x text-muted opacity-50"></i>
                    </div>
                    <h4 class="fw-bold text-muted">Aucune réservation pour le moment</h4>
                    <p class="text-muted mb-4">Vous n'avez pas encore de réservation de prévue. Prenez d'assaut les pistes !</p>
                    <a href="recherche.php" class="btn btn-primary px-4 py-2 shadow-sm">
                        <i class="fas fa-search me-2"></i>Rechercher une chambre
                    </a>
                </div>
            <?php else: ?>
                <!-- Liste des séjours trouvés -->
                <div class="row g-4">
                    <?php foreach ($reservations as $res): ?>
                        <div class="col-12">
                            <div class="card border border-2 shadow-none">
                                <!-- En-tête de la carte de séjour -->
                                <div class="card-header bg-light d-flex justify-content-between align-items-center py-3 border-0">
                                    <div>
                                        <h5 class="fw-bold text-dark mb-1">
                                            <i class="fas fa-users text-secondary me-2"></i>Groupe : <?php echo h($res['nom_groupe']); ?>
                                        </h5>
                                        <span class="text-muted small">
                                            <i class="far fa-calendar-alt me-1"></i>Du <?php echo date_fr($res['date_debut']); ?> au <?php echo date_fr($res['date_fin']); ?>
                                        </span>
                                    </div>
                                    <div class="text-end d-flex align-items-center gap-3">
                                        <div>
                                            <span class="text-xs text-muted d-block uppercase fw-bold">Montant Total</span>
                                            <span class="badge bg-primary fs-5 px-3 py-2">
                                                <?php echo number_format($res['prix_total_sejour'], 0, ',', ' '); ?> €
                                            </span>
                                        </div>
                                        
                                        <!-- Bouton Annuler la réservation entière -->
                                        <button class="btn btn-danger btn-sm px-3 py-2 fw-bold" 
                                                onclick="confirmCancelReservation(<?php echo $res['id_reservation']; ?>, '<?php echo h(addslashes($res['nom_groupe'])); ?>')"
                                                title="Annuler tout le séjour">
                                            <i class="fas fa-trash-alt me-1"></i> Annuler
                                        </button>
                                    </div>
                                </div>

                                <!-- Corps de la carte : Détails des occupants (Requête secondaire) -->
                                <div class="card-body p-4">
                                    <h6 class="fw-bold mb-3 text-secondary"><i class="fas fa-info-circle me-1"></i>Répartition et Formules</h6>
                                    
                                    <?php
                                    try {
                                        // 2. REQUÊTE SECONDAIRE : Récupère les chambres, les occupants et les prix des formules pour ce séjour
                                        $sql_sub = "SELECT 
                                                        re.num_chambre,
                                                        ch.batiment,
                                                        ch.etage,
                                                        c.nom AS client_nom,
                                                        c.prenom AS client_prenom,
                                                        re.type_formule,
                                                        re.formule_prix_final
                                                    FROM reserver re
                                                    INNER JOIN client c ON re.id_client = c.id_client
                                                    INNER JOIN chambre ch ON re.num_chambre = ch.num_chambre
                                                    WHERE re.id_reservation = :id_reservation
                                                    ORDER BY re.num_chambre, c.nom, c.prenom";

                                        $stmt_sub = $pdo->prepare($sql_sub);
                                        $stmt_sub->execute(['id_reservation' => $res['id_reservation']]);
                                        $occupants = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);
                                    } catch (PDOException $e) {
                                        $occupants = [];
                                    }
                                    ?>

                                    <?php if (empty($occupants)): ?>
                                        <p class="text-muted italic small mb-0">Aucun occupant n'est lié à cette réservation.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless align-middle mb-0">
                                                <thead>
                                                    <tr class="text-muted small border-bottom">
                                                        <th class="pb-2">Logement</th>
                                                        <th class="pb-2">Occupant</th>
                                                        <th class="pb-2">Formule choisie</th>
                                                        <th class="pb-2 text-end">Tarif final</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($occupants as $occ): ?>
                                                        <tr>
                                                            <td class="py-2">
                                                                <span class="fw-bold text-dark">Chambre <?php echo $occ['num_chambre']; ?></span>
                                                                <span class="text-muted small d-block">Bâtiment <?php echo h($occ['batiment']); ?> - Étage <?php echo $occ['etage']; ?></span>
                                                            </td>
                                                            <td class="py-2">
                                                                <span class="text-dark fw-semibold"><?php echo h($occ['client_prenom'] . ' ' . $occ['client_nom']); ?></span>
                                                            </td>
                                                            <td class="py-2">
                                                                <span class="badge bg-light text-secondary border"><?php echo h($occ['type_formule']); ?></span>
                                                            </td>
                                                            <td class="py-2 text-end text-primary fw-bold">
                                                                <?php echo $occ['formule_prix_final'] > 0 ? $occ['formule_prix_final'] . ' €' : 'Gratuit'; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-4 text-center">
                    <a href="recherche.php" class="text-decoration-none text-muted small">
                        <i class="fas fa-plus-circle me-1"></i> Louer d'autres chambres pour une autre semaine
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmCancelReservation(idReservation, groupName) {
    if (confirm("Êtes-vous sûr de vouloir annuler définitivement la réservation pour le groupe '" + groupName + "' ?\n\nCette action supprimera également les affectations de chambres ainsi que toutes les factures associées.")) {
        window.location.href = "../actions/annuler_sejour.php?id=" + idReservation;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>