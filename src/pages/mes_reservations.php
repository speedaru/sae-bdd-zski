<?php
/**
 * page d'affichage des reservations
 */

require_once __DIR__ . '/../includes/header.php';

// accessible uniquement aux membres connectes
require_login("../pages/mes_reservations.php");

$user_id = $_SESSION['id_user'];
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;

// nettoyage des messages flash
unset($_SESSION['error'], $_SESSION['success']);

try {
    // REQUETE PRINCIPALE recupere les reservations et calcule la somme globale des factures par sejour
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
    $error = "Une erreur est survenue lors du chargement de vos sejours : " . $e->getMessage();
}
?>

<link rel="stylesheet" href="../assets/css/mes_reservations.css">

<div class="reservations-container">
    <!-- menu client -->
    <div class="reservations-sidebar">
        <?php include __DIR__ . '/../includes/sidebar_client.php'; ?>
    </div>

    <!-- contenu principal -->
    <div class="reservations-content">
        
        <div class="reservations-header">
            <h2>Mes Sejours & Reservations</h2>
            <p>Retrouvez ci-dessous l'historique complet de vos vacances à la station Zarza-Ski ainsi que vos factures.</p>
        </div>

        <!-- messages d'erreur ou de succes -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo h($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <?php if (empty($reservations)): ?>
            <!-- aucun sejour trouve -->
            <div class="empty-state">
                <p>Vous n'avez pas encore de reservation de prevue. Prenez d'assaut les pistes !</p>
                <p><a href="recherche.php" class="btn-primary">Rechercher une chambre</a></p>
            </div>
        <?php else: ?>
            
            <!-- liste des sejours trouves -->
            <div class="reservations-list">
                <?php foreach ($reservations as $res): ?>
                    <div class="reservation-box">
                        
                        <!-- en-tête de la reservation -->
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
                                    Annuler le sejour
                                </button>
                            </div>
                        </div>

                        <!-- details des occupants -->
                        <div class="reservation-box-body">
                            <h4>Repartition des occupants & formules :</h4>
                            
                            <?php
                            try {
                                $sql_sub = "SELECT 
                                                re.num_chambre,
                                                ch.batiment,
                                                ch.etage,
                                                c.nom AS client_nom,
                                                c.prenom AS client_prenom,
                                                c.date_naissance,
                                                re.type_formule,
                                                re.formule_prix_final,
                                                f.prix_base
                                            FROM reserver re
                                            INNER JOIN client c ON re.id_client = c.id_client
                                            INNER JOIN chambre ch ON re.num_chambre = ch.num_chambre
                                            INNER JOIN formule f ON re.type_formule = f.type_formule
                                            WHERE re.id_reservation = :id_reservation
                                            ORDER BY re.num_chambre, c.nom, c.prenom";

                                $stmt_sub = $pdo->prepare($sql_sub);
                                $stmt_sub->execute(['id_reservation' => $res['id_reservation']]);
                                $occupants = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);

                                // recuperation des facturations par chambre
                                $stmt_rooms = $pdo->prepare("
                                    SELECT f.num_chambre, f.montant_total, ch.nb_lits 
                                    FROM facturation f 
                                    INNER JOIN chambre ch ON f.num_chambre = ch.num_chambre 
                                    WHERE f.id_reservation = :id_reservation
                                    ORDER BY f.num_chambre ASC
                                ");
                                $stmt_rooms->execute(['id_reservation' => $res['id_reservation']]);
                                $rooms_billed = $stmt_rooms->fetchAll(PDO::FETCH_ASSOC);

                            } catch (PDOException $e) {
                                $occupants = [];
                                $rooms_billed = [];
                            }
                            ?>

                            <?php if (empty($occupants)): ?>
                                <p class="no-occupants-msg">Aucun proche n'est affecte à ce sejour.</p>
                            <?php else: ?>
                                <table class="academic-table">
                                    <thead>
                                        <tr>
                                            <th>Hebergement</th>
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

                                <!-- resume par chambre -->
                                <div class="math-summary">
                                    <p class="math-summary-title"><strong>Calculs de facturation par chambre :</strong></p>
                                    
                                    <?php foreach ($rooms_billed as $room): 
                                        $num_ch = $room['num_chambre'];
                                        $nb_lits = $room['nb_lits'];
                                        $total_chambre = $room['montant_total'];

                                        // filtrer les skieurs de la chambre
                                        $room_occupants = array_filter($occupants, function($o) use ($num_ch) {
                                            return $o['num_chambre'] == $num_ch;
                                        });

                                        $adulte_count = 0;
                                        $enfant_count = 0;
                                        $bebe_count = 0;
                                        $formules_tarifs = [];

                                        foreach ($room_occupants as $occ) {
                                            $ref_date = new DateTime($res['date_debut']);
                                            $birthdate = new DateTime($occ['date_naissance']);
                                            $age = $ref_date->diff($birthdate)->y;

                                            if ($age < 2) {
                                                $bebe_count++;
                                            } elseif ($age < 12) {
                                                $enfant_count++;
                                            } else {
                                                $adulte_count++;
                                            }

                                            // on regroupe par formule pour résumer simplement les tarifs
                                            $tarif_detail = $occ['type_formule'] . " (" . ($age < 2 ? 'Bébé' : ($age < 12 ? 'Enfant' : 'Adulte')) . ")";
                                            if (!isset($formules_tarifs[$tarif_detail])) {
                                                $formules_tarifs[$tarif_detail] = ['count' => 0, 'prix' => $occ['formule_prix_final']];
                                            }
                                            $formules_tarifs[$tarif_detail]['count']++;
                                        }

                                        $lits_occupes = $adulte_count + $enfant_count;
                                        $lits_vides = max(0, $nb_lits - $lits_occupes);
                                        $amende_lits_vides = $lits_vides * 150;
                                    ?>
                                        <div class="room-math-block">
                                            <p class="room-math-name"><strong>Chambre n°<?php echo $num_ch; ?></strong> (<?php echo $nb_lits; ?> lits) :</p>
                                            <ul class="math-list">
                                                <?php foreach ($formules_tarifs as $formule_label => $info): ?>
                                                    <li>• <?php echo $info['count']; ?> x <?php echo $formule_label; ?> : <?php echo $info['count'] * $info['prix']; ?> €</li>
                                                <?php endforeach; ?>
                                                <?php if ($bebe_count > 0 || $enfant_count > 0): ?>
                                                    <li>• Remises : <?php if($enfant_count > 0) echo "Enfant -20% (x".$enfant_count.")"; ?> <?php if($bebe_count > 0) echo "| Bébé Gratuit (x".$bebe_count.")"; ?></li>
                                                <?php endif; ?>
                                                <?php if ($lits_vides > 0): ?>
                                                    <li>• Pénalité : <?php echo $lits_vides; ?> lit(s) vide(s) x 150 € = <?php echo $amende_lits_vides; ?> €</li>
                                                <?php endif; ?>
                                                <li class="room-subtotal">Total Chambre : <?php echo number_format($total_chambre, 0, ',', ' '); ?> €</li>
                                            </ul>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
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
    if (confirm("Êtes-vous sûr de vouloir annuler definitivement la reservation pour le groupe '" + groupName + "' ?\n\nCette action supprimera egalement les affectations de chambres ainsi que toutes les factures associees.")) {
        window.location.href = "../actions/annuler_sejour.php?id=" + idReservation;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
