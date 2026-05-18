<?php
/**
 * page de statistiques pour la direction
 */

require_once __DIR__ . '/../includes/header.php';

require_role('gestionnaire', "../pages/vues.php");

$tab = $_GET['tab'] ?? 'frequentation';
$error = null;
$results = [];

try {
    if ($tab === 'frequentation') {
        // interroger directement la vue de frequentation
        $sql = "SELECT semaine_debut, semaine_fin, total_skieurs_pistes 
                        FROM vue_frequentation_semaine 
                        ORDER BY semaine_debut ASC";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($tab === 'recherche') {
        $num_chambre = isset($_POST['num_chambre']) ? intval($_POST['num_chambre']) : null;
        $date_semaine = isset($_POST['date_semaine']) ? trim($_POST['date_semaine']) : null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $num_chambre && $date_semaine) {
            // recherche sur la vue des details des occupants
            $sql = "SELECT client_nom, client_prenom, type_formule 
                    FROM vue_details_occupants_chambre 
                    WHERE num_chambre = :chambre AND semaine_debut = :date";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'chambre' => $num_chambre,
                'date'    => $date_semaine
            ]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    } elseif ($tab === 'occupants') {
        // recuperer tout le contenu de la vue des occupants
        $sql = "SELECT semaine_debut, num_chambre, batiment, etage, client_nom, client_prenom, type_formule 
                FROM vue_details_occupants_chambre 
                ORDER BY semaine_debut ASC, num_chambre ASC, client_nom ASC";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "Une erreur technique est survenue : " . $e->getMessage();
}
?>

<link rel="stylesheet" href="../assets/css/vues.css">

<div class="report-container">
    
    <div class="report-header">
        <h1>Suivi des Vues</h1>
        <p>Visualisation brute des tables de statistiques et d'occupation de la station.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert-error"><?php echo h($error); ?></div>
    <?php endif; ?>

    <!-- navigation horizontale -->
    <nav class="tab-navigation">
        <a href="vues.php?tab=frequentation" class="tab-link <?php echo $tab === 'frequentation' ? 'active-tab' : ''; ?>">
            Frequentation Hebdomadaire
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

        <!-- frequentation -->
        <?php if ($tab === 'frequentation'): ?>
            <div class="tab-pane">
                <h3>Frequentation hebdomadaire</h3>
                <p class="description-text">Nombre de skieurs presents par semaine de location.</p>
                
                <?php if (empty($results)): ?>
                    <p class="no-data-msg">Aucune donnee disponible.</p>
                <?php else: ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Semaine de debut</th>
                                <th>Semaine de fin</th>
                                <th>Nombre de personnes presentes (total_skieurs_pistes)</th>
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

        <!-- onglet recherche par chambre -->
        <?php elseif ($tab === 'recherche'): ?>
            <div class="tab-pane">
                <h3>Recherche d'occupants</h3>
                <p class="description-text">Voir qui occupe une chambre a une date precise.</p>
                
                <form method="POST" action="vues.php?tab=recherche" class="search-form">
                    <div class="form-group-row">
                        <div class="input-item">
                            <label>Numero de chambre :</label>
                            <input type="number" name="num_chambre" placeholder="Ex: 227" value="<?php echo isset($_POST['num_chambre']) ? intval($_POST['num_chambre']) : ''; ?>" required>
                        </div>
                        <div class="input-item">
                            <label>Date de debut (Dimanche) :</label>
                            <input type="date" name="date_semaine" value="<?php echo isset($_POST['date_semaine']) ? h($_POST['date_semaine']) : ''; ?>" required>
                        </div>
                        <button type="submit" class="btn-search-submit">Lancer l'interrogation</button>
                    </div>
                </form>

                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <h4>Resultats de recherche :</h4>
                    
                    <?php if (empty($results)): ?>
                        <p class="no-data-msg">Aucun occupant n'occupe cette chambre à cette date.</p>
                    <?php else: ?>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Prenom</th>
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

        <!-- onglet occupants complets -->
        <?php elseif ($tab === 'occupants'): ?>
            <div class="tab-pane">
                <h3>Registre complet des occupants</h3>
                <p class="description-text">Tableau d'affectation globale.</p>

                <?php if (empty($results)): ?>
                    <p class="no-data-msg">Aucun enregistrement d'occupation trouve.</p>
                <?php else: ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Date Semaine</th>
                                <th>N° Chambre</th>
                                <th>Bâtiment</th>
                                <th>etage</th>
                                <th>Nom</th>
                                <th>Prenom</th>
                                <th>Formule</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td><?php echo date_fr($row['semaine_debut']); ?></td>
                                    <td>Chambre <?php echo intval($row['num_chambre']); ?></td>
                                    <td>Bâtiment <?php echo h($row['batiment']); ?></td>
                                    <td>etage <?php echo intval($row['etage']); ?></td>
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
