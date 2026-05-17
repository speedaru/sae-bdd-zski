<?php
/**
 * Fiche de présentation d'une chambre - Zarza-Ski
 * Emplacement : src/pages/details.php
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

// 1. RÉCUPÉRATION ET VALIDATION DES DONNÉES
$id_get = $_GET['id'] ?? 0;
$id_chambre = intval($id_get);

// Recherche de la chambre en base de données
$stmtChambre = $pdo->prepare("SELECT * FROM chambre WHERE num_chambre = ?");
$stmtChambre->execute([$id_chambre]);
$chambre = $stmtChambre->fetch(PDO::FETCH_ASSOC);

// Recherche des tarifs (formules disponibles)
$stmtFormules = $pdo->query("SELECT * FROM formule ORDER BY prix_base ASC");
$formules = $stmtFormules->fetchAll(PDO::FETCH_ASSOC);

// Gestion de l'erreur si la chambre demandée n'existe pas
if (!$chambre) {
    ?>
    <div class="container py-5 text-center">
        <div class="alert alert-danger shadow-sm max-width-600 mx-auto">
            <h1 class="display-4 mb-3"><i class="fas fa-exclamation-triangle"></i></h1>
            <h2 class="fw-bold">ERREUR : La chambre n'existe pas !</h2>
            <p class="text-muted">Le logement demandé est introuvable dans la base de données de notre station.</p>
            <a href="recherche.php" class="btn btn-outline-danger mt-3">Retourner à la recherche</a>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

// Vérification de la présence de cette chambre dans le panier de session
$panier = $_SESSION['panier'] ?? [];
$is_in_panier = in_array($id_chambre, $panier);
?>

<!-- 2. CONTENU DE PRÉSENTATION GRAPHIQUE -->
<div class="container py-4">
    
    <!-- Fil d'Ariane de navigation -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Accueil</a></li>
            <li class="breadcrumb-item"><a href="recherche.php" class="text-decoration-none">Recherche</a></li>
            <li class="breadcrumb-item active" aria-current="page">Chambre <?php echo $chambre['num_chambre']; ?></li>
        </ol>
    </nav>

    <div class="text-center mb-5 mt-3">
        <span class="text-uppercase text-primary fw-bold tracking-wider small">Hébergement Premium</span>
        <h1 class="display-6 fw-bold text-dark mt-1">
            Fiche de présentation : Chambre n°<?php echo $chambre['num_chambre']; ?>
        </h1>
        <div class="heading-line mx-auto bg-primary rounded" style="width: 80px; height: 3px;"></div>
    </div>

    <div class="row g-4">
        <!-- Colonne Gauche : Détails techniques du logement -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2"></i>Caractéristiques techniques</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-sm-6">
                            <span class="text-muted d-block small text-uppercase fw-bold">Bâtiment / Étage</span>
                            <span class="fw-bold fs-5 text-dark">
                                <i class="fas fa-hotel text-secondary me-2"></i>Bâtiment <?php echo h($chambre['batiment']); ?> - Niveau <?php echo $chambre['etage']; ?>
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block small text-uppercase fw-bold">Superficie</span>
                            <span class="fw-bold fs-5 text-dark">
                                <i class="fas fa-vector-square text-secondary me-2"></i><?php echo $chambre['superficie']; ?> m²
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block small text-uppercase fw-bold">Capacité d'accueil</span>
                            <span class="fw-bold fs-5 text-dark">
                                <i class="fas fa-users text-secondary me-2"></i><?php echo $chambre['nb_lits']; ?> couchages individuels
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block small text-uppercase fw-bold">Exposition & Panorama</span>
                            <span class="fw-bold fs-5 text-dark">
                                <i class="fas fa-mountain text-secondary me-2"></i>Vue imprenable sur <?php echo h($chambre['type_vue']); ?>
                            </span>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between border">
                                <div class="d-flex align-items-center">
                                    <div class="bg-white rounded p-2 me-3 shadow-none border">
                                        <i class="fas fa-umbrella-beach text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <span class="d-block text-muted small">Présence d'un balcon extérieur</span>
                                        <span class="fw-bold text-dark">Balcon terrasse aménagé</span>
                                    </div>
                                </div>
                                <span class="badge <?php echo $chambre['balcon_present'] ? 'bg-success' : 'bg-secondary'; ?> px-3 py-2 fs-6">
                                    <?php echo $chambre['balcon_present'] ? 'Oui' : 'Non'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne Droite : Tarification de la station -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white py-3 text-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-tags me-2"></i>Grille tarifaire (Semaine)</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 py-3">Type de formule</th>
                                <th class="text-end pe-4 py-3">Prix de base / pers.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($formules as $f): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?php echo h($f['type_formule']); ?></td>
                                <td class="text-end pe-4 text-primary fw-bold fs-5"><?php echo number_format($f['prix_base'], 0, ',', ' '); ?> €</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="p-3 bg-light text-center border-top">
                        <small class="text-muted italic"><i class="fas fa-info-circle me-1"></i>Remises familles appliquées au moment de la validation finale.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions et Logique de panier interactive -->
    <div class="row mt-5">
        <div class="col-12 d-flex justify-content-center gap-3 flex-wrap">
            <a href="recherche.php" class="btn btn-outline-secondary btn-lg px-4">
                <i class="fas fa-arrow-left me-2"></i>Retour à la recherche
            </a>
            
            <?php if ($is_in_panier): ?>
                <!-- Si déjà dans le panier : bouton de retrait et d'accès rapide de validation -->
                <a href="../actions/supprimer_panier.php?id=<?php echo $id_chambre; ?>" class="btn btn-outline-danger btn-lg px-4">
                    <i class="fas fa-trash-alt me-2"></i>Retirer de ma sélection
                </a>
                <a href="reservation.php" class="btn btn-success btn-lg px-5 shadow-sm fw-bold">
                    <i class="fas fa-shopping-cart me-2"></i>Finaliser ma réservation
                </a>
            <?php else: ?>
                <!-- Si non présent dans le panier -->
                <a href="../actions/ajouter_panier.php?id=<?php echo $id_chambre; ?>" class="btn btn-primary btn-lg px-5 shadow-sm fw-bold">
                    <i class="fas fa-cart-plus me-2"></i>Ajouter à ma sélection
                </a>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>