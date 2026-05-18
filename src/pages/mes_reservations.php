<?php
/**
 * Page d'affichage des réservations - Zarza-Ski
 * Emplacement : src/pages/mes_reservations.php
 * Version épurée, académique et sans framework
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

<div class="reservations-container">
    <!-- Menu Latéral Client -->
    <div class="reservations-sidebar">
        <?php include __DIR__ . '/../includes/sidebar_client.php'; ?>
    </div>

    <!-- Contenu Principal -->
    <div class="reservations-content">
        
        <div class="reservations-header">
            <h2>Mes Séjours & Réservations</h2>
            <p>Retrouvez ci-dessous l'historique complet de vos vacances à la station Zarza-Ski ainsi que vos factures.</p>
        </div>

        <!-- Messages d'erreur ou de succès -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo h($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <?php if (empty($reservations)): ?>
            <!-- Aucun séjour trouvé -->
            <div class="empty-state">
                <p>Vous n'avez pas encore de réservation de prévue. Prenez d'assaut les pistes !</p>
                <p><a href="recherche.php" class="btn-primary">Rechercher une chambre</a></p>
            </div>
        <?php else: ?>
            
            <!-- Liste des séjours trouvés -->
            <div class="reservations-list">
                <?php foreach ($reservations as $res): ?>
                    <div class="reservation-box">
                        
                        <!-- En-tête de la réservation -->
                        <div class="reservation-box-header">
                            <div class="group-info">
                                <h3>Groupe : <?php echo h($res['nom_groupe']); ?></h3>
                                <span class="stay-dates">Du <?php echo date_fr($res['date_debut']); ?> au <?php echo date_fr($res['date_fin']); ?></span>
                            </div>
                            
                            <div class="billing-info">
                                <div class="total-badge">
                                    Total : <strong><?php echo number_format($res['prix_total_sejour'], 0, ',', ' '); ?> €</strong>
                                </div>
                                <button class="btn-cancel-stay" onclick="confirmCancelReservation(<?php echo $res['id_reservation']; ?>, '<?php echo h(addslashes($res['nom_groupe'])); ?>')">
                                    Annuler le séjour
                                </button>
                            </div>
                        </div>

                        <!-- Détails des occupants (Requête secondaire) -->
                        <div class="reservation-box-body">
                            <h4>Répartition des occupants & formules :</h4>
                            
                            <?php
                            try {
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
                                <p class="no-occupants-msg">Aucun proche n'est affecté à ce séjour.</p>
                            <?php else: ?>
                                <table class="academic-table">
                                    <thead>
                                        <tr>
                                            <th>Hébergement</th>
                                            <th>Skieur</th>
                                            <th>Formule</th>
                                            <th class="text-right">Tarif final</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($occupants as $occ): ?>
                                            <tr>
                                                <td>
                                                    <strong>Chambre <?php echo $occ['num_chambre']; ?></strong> 
                                                    <span class="room-meta">(Bat. <?php echo h($occ['batiment']); ?>, Niv. <?php echo $occ['etage']; ?>)</span>
                                                </td>
                                                <td><?php echo h($occ['client_prenom'] . ' ' . $occ['client_nom']); ?></td>
                                                <td><span class="badge-formula"><?php echo h($occ['type_formule']); ?></span></td>
                                                <td class="text-right highlight-price">
                                                    <?php echo $occ['formule_prix_final'] > 0 ? $occ['formule_prix_final'] . ' €' : 'Gratuit'; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

            <div class="more-actions">
                <a href="recherche.php" class="link-more-rooms">
                    <u>Louer d'autres chambres pour une autre semaine</u>
                </a>
            </div>
            
        <?php endif; ?>
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