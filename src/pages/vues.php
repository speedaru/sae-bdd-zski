<?php
/**
 * Page de statistiques pour la direction - Zarza-Ski
 * Emplacement : src/pages/vues.php
 * S'appuie de manière stricte sur les colonnes des vues PostgreSQL.
 */

require_once __DIR__ . '/../includes/header.php';

require_role('gestionnaire');

$tab = $_GET['tab'] ?? 'frequentation';
$error = null;
$results = [];

try {
    if ($tab === 'frequentation') {
        // 1. Nombre de personnes présentes par semaine (Jointure réservation -> réserver)
        $sql = "SELECT 
                    r.date_debut AS semaine_debut,
                    r.date_fin AS semaine_fin,
                    COUNT(re.id_client) AS total_skieurs_pistes
                FROM reservation r
                LEFT JOIN reserver re ON r.id_reservation = re.id_reservation
                GROUP BY r.id_reservation, r.date_debut, r.date_fin
                ORDER BY r.date_debut ASC";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($tab === 'recherche') {
        $num_chambre = isset($_POST['num_chambre']) ? intval($_POST['num_chambre']) : null;
        $date_semaine = isset($_POST['date_semaine']) ? trim($_POST['date_semaine']) : null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $num_chambre && $date_semaine) {
            // 2. Recherche des occupants d'une chambre pour une date précise
            $sql = "SELECT 
                        c.nom AS client_nom, 
                        c.prenom AS client_prenom, 
                        re.type_formule 
                    FROM reserver re
                    INNER JOIN client c ON re.id_client = c.id_client
                    INNER JOIN reservation r ON re.id_reservation = r.id_reservation
                    WHERE re.num_chambre = :chambre AND r.date_debut = :date";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'chambre' => $num_chambre,
                'date'    => $date_semaine
            ]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    } elseif ($tab === 'occupants') {
        // 3. Liste complète et globale de tous les occupants de toutes les chambres
        $sql = "SELECT 
                    r.date_debut AS semaine_debut, 
                    re.num_chambre, 
                    ch.batiment, 
                    ch.etage, 
                    c.nom AS client_nom, 
                    c.prenom AS client_prenom, 
                    re.type_formule 
                FROM reserver re
                INNER JOIN reservation r ON re.id_reservation = r.id_reservation
                INNER JOIN client c ON re.id_client = c.id_client
                INNER JOIN chambre ch ON re.num_chambre = ch.num_chambre
                ORDER BY r.date_debut ASC, re.num_chambre ASC, c.nom ASC";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "Une erreur technique est survenue : " . $e->getMessage();
}
?>

<!-- Liaison à la feuille de style simplifiée externe -->
<link rel="stylesheet" href="../assets/css/vues.css">

<div class="report-container">
    
    <div class="report-header">
        <h1>Suivi des Vues</h1>
        <p>Visualisation brute des tables de statistiques et d'occupation de la station.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert-error"><?php echo h($error); ?></div>
    <?php endif; ?>

    <!-- Navigation horizontale épurée par séparateurs de texte -->
    <nav class="tab-navigation">
        <a href="vues.php?tab=frequentation" class="tab-link <?php echo $tab === 'frequentation' ? 'active-tab' : ''; ?>">
            Fréquentation Hebdomadaire
        </a>
        <span class="tab-separator">|</span>
        <a href="vues.php?tab=recherche" class="tab-link <?php echo $tab === 'recherche' ? 'active-tab' : ''; ?>">
            Recherche par Chambre
        </a>
        <span class="tab-separator">|</span>
        <a href="vues.php?tab=occupants" class="tab-link <?php echo $tab === 'occupants' ? 'active-tab' : ''; ?>">
            Liste des Occupants
        </a>
    </nav>

    <div class="tab-content-box">

        <!-- ================= ONGLET : FRÉQUENTATION ================= -->
        <?php if ($tab === 'frequentation'): ?>
            <div class="tab-pane">
                <h3>Fréquentation hebdomadaire</h3>
                <p class="description-text">Nombre de skieurs présents par semaine de location.</p>
                
                <?php if (empty($results)): ?>
                    <p class="no-data-msg">Aucune donnée disponible.</p>
                <?php else: ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Semaine de début</th>
                                <th>Semaine de fin</th>
                                <th>Nombre de personnes présentes (total_skieurs_pistes)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td><?php echo date_fr($row['semaine_debut']); ?></td>
                                    <td><?php echo date_fr($row['semaine_fin']); ?></td>
                                    <td><?php echo intval($row['total_skieurs_pistes']); ?> skieur(s)</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        <!-- ================= ONGLET : RECHERCHE PAR CHAMBRE ================= -->
        <?php elseif ($tab === 'recherche'): ?>
            <div class="tab-pane">
                <h3>Recherche d'occupants</h3>
                <p class="description-text">Voir qui occupe une chambre a une date précise.</p>
                
                <form method="POST" action="vues.php?tab=recherche" class="search-form">
                    <div class="form-group-row">
                        <div class="input-item">
                            <label>Numéro de chambre :</label>
                            <input type="number" name="num_chambre" placeholder="Ex: 227" value="<?php echo isset($_POST['num_chambre']) ? intval($_POST['num_chambre']) : ''; ?>" required>
                        </div>
                        <div class="input-item">
                            <label>Date de début (Dimanche) :</label>
                            <input type="date" name="date_semaine" value="<?php echo isset($_POST['date_semaine']) ? h($_POST['date_semaine']) : ''; ?>" required>
                        </div>
                        <button type="submit" class="btn-search-submit">Lancer l'interrogation</button>
                    </div>
                </form>

                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <h4>Résultats de recherche :</h4>
                    
                    <?php if (empty($results)): ?>
                        <p class="no-data-msg">Aucun occupant n'occupe cette chambre à cette date.</p>
                    <?php else: ?>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Prénom</th>
                                    <th>Nom</th>
                                    <th>Formule</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $occ): ?>
                                    <tr>
                                        <td><?php echo h($occ['client_prenom']); ?></td>
                                        <td><?php echo h($occ['client_nom']); ?></td>
                                        <td><?php echo h($occ['type_formule']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        <!-- ================= ONGLET : OCCUPANTS COMPLETS ================= -->
        <?php elseif ($tab === 'occupants'): ?>
            <div class="tab-pane">
                <h3>Registre complet des occupants</h3>
                <p class="description-text">Tableau d'affectation globale.</p>

                <?php if (empty($results)): ?>
                    <p class="no-data-msg">Aucun enregistrement d'occupation trouvé.</p>
                <?php else: ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Date Semaine</th>
                                <th>N° Chambre</th>
                                <th>Bâtiment</th>
                                <th>Étage</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Formule</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td><?php echo date_fr($row['semaine_debut']); ?></td>
                                    <td>Chambre <?php echo intval($row['num_chambre']); ?></td>
                                    <td>Bâtiment <?php echo h($row['batiment']); ?></td>
                                    <td>Étage <?php echo intval($row['etage']); ?></td>
                                    <td><?php echo h($row['client_nom']); ?></td>
                                    <td><?php echo h($row['client_prenom']); ?></td>
                                    <td><?php echo h($row['type_formule']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>