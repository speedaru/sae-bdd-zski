<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

// recuperation et validation des donnees
$id_get = $_GET['id'] ?? 0;
$id_chambre = intval($id_get);

// requete pour les details de la chambre
$stmtChambre = $pdo->prepare("SELECT * FROM chambre WHERE num_chambre = ?");
$stmtChambre->execute([$id_chambre]);
$chambre = $stmtChambre->fetch(PDO::FETCH_ASSOC);

// requete pour les tarifs (formules)
$stmtFormules = $pdo->query("SELECT * FROM formule ORDER BY prix_base ASC");
$formules = $stmtFormules->fetchAll(PDO::FETCH_ASSOC);

// gestion d'erreur si la chambre existe pas
if (!$chambre) {
    ?>
    <div class="container py-5 text-center">
        <div class="alert alert-danger shadow-sm">
            <h1 class="display-4"><i class="fas fa-exclamation-triangle"></i></h1>
            <h2 class="fw-bold">ERREUR : La chambre n'existe pas !</h2>
            <p class="text-muted">Le logement demandé est introuvable dans notre base de données.</p>
            <a href="recherche.php" class="btn btn-outline-danger mt-3">Retourner à la recherche</a>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Accueil</a></li>
            <li class="breadcrumb-item"><a href="recherche.php">Recherche</a></li>
            <li class="breadcrumb-item active">Chambre <?php echo $chambre['num_chambre']; ?></li>
        </ol>
    </nav>

    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold text-primary">
            <u>FICHE DE PRÉSENTATION : CHAMBRE <?php echo $chambre['num_chambre']; ?></u>
        </h1>
    </div>

    <div class="row g-4">
        <!-- colonne gauche: details techniques -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Détails techniques du logement</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <span class="text-muted d-block small">Bâtiment / Étage</span>
                            <span class="fw-bold fs-5">Bâtiment <?php echo htmlspecialchars($chambre['batiment']); ?> - Niveau <?php echo $chambre['etage']; ?></span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <span class="text-muted d-block small">Superficie</span>
                            <span class="fw-bold fs-5"><?php echo $chambre['superficie']; ?> m²</span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <span class="text-muted d-block small">Capacité</span>
                            <span class="fw-bold fs-5"><?php echo $chambre['nb_lits']; ?> couchages disponibles</span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <span class="text-muted d-block small">Exposition</span>
                            <span class="fw-bold fs-5">Vue sur <?php echo htmlspecialchars($chambre['type_vue']); ?></span>
                        </div>
                        <div class="col-12 mt-3">
                            <div class="p-3 bg-light rounded d-flex align-items-center">
                                <i class="fas fa-umbrella-beach me-3 fs-4 text-primary"></i>
                                <div>
                                    <span class="d-block text-muted small">Présence d'un balcon</span>
                                    <span class="badge <?php echo $chambre['balcon_present'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $chambre['balcon_present'] ? 'OUI' : 'NON'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- colonne droite: tarification -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white py-3 text-center">
                    <h5 class="mb-0">LES TARIFS DE LA STATION</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Type de formule</th>
                                <th class="text-end pe-4">Prix / Personne</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($formules as $f): ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?php echo htmlspecialchars($f['type_formule']); ?></td>
                                <td class="text-end pe-4 text-primary fw-bold"><?php echo number_format($f['prix_base'], 0, ',', ' '); ?> €</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="p-3 bg-light text-center">
                        <small class="text-muted">Prix indiqués par semaine de séjour.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- actions -->
    <div class="row mt-5">
        <div class="col-12 d-flex justify-content-center gap-3">
            <a href="recherche.php" class="btn btn-outline-secondary btn-lg px-4">
                <i class="fas fa-arrow-left me-2"></i>RETOUR À LA LISTE
            </a>
            <a href="<?php echo "reservation.php?id=" . $id_chambre; ?>" class="btn btn-primary btn-lg px-5 shadow">
                <i class="fas fa-calendar-check me-2"></i><strong>RÉSERVER CE LOGEMENT</strong>
            </a>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
