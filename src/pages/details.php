<?php
require_once '../includes/header.php';

$id_get = $_GET['id'] ?? 0;
$id_chambre = intval($id_get);

$stmtChambre = $pdo->prepare("SELECT * FROM chambre WHERE num_chambre = ?");
$stmtChambre->execute([$id_chambre]);
$chambre = $stmtChambre->fetch(PDO::FETCH_ASSOC);

$stmtFormules = $pdo->query("SELECT * FROM formule ORDER BY prix_base ASC");
$formules = $stmtFormules->fetchAll(PDO::FETCH_ASSOC);

if ($chambre == false) {
    ?>
    <link rel="stylesheet" href="../assets/css/details.css">
    <div class="error-wrapper">
        <h1 class="error-title">ERREUR : La chambre n'existe pas !</h1>
        <p class="error-text">Le logement demandé est introuvable dans la base de données de notre station.</p>
        <p><a href="recherche.php" class="btn-defaut">Retourner à la recherche</a></p>
    </div>
    <?php
    require_once '../includes/footer.php';
    exit();
}

$panier = $_SESSION['panier'] ?? [];
$is_in_panier = in_array($id_chambre, $panier);
?>

<main class="conteneur-principal">
    
    <p class="fil-ariane">
        <a href="../index.php">Accueil</a> / 
        <a href="recherche.php">Recherche</a> / 
        Chambre <?php echo $chambre['num_chambre']; ?>
    </p>

    <div class="titre-page">
        <h1>Fiche de présentation : Chambre n°<?php echo $chambre['num_chambre']; ?></h1>
        <div class="barre-separation"></div>
    </div>

    <table class="table-presentation">
        <tr>
            <td class="colonne-technique">
                <h3 class="titre-section blue-bg">Caractéristiques techniques</h3>
                <div class="tech-info-group">
                    <p><strong>Bâtiment :</strong> Bâtiment <?php echo htmlspecialchars($chambre['batiment']); ?></p>
                    <p><strong>Étage :</strong> Niveau <?php echo $chambre['etage']; ?></p>
                    <p><strong>Superficie :</strong> <?php echo $chambre['superficie']; ?> m²</p>
                    <p><strong>Capacité d'accueil :</strong> <?php echo $chambre['nb_lits']; ?> couchages individuels</p>
                    <p><strong>Exposition & Panorama :</strong> Vue imprenable sur <?php echo htmlspecialchars($chambre['type_vue']); ?></p>
                    <p>
                        <strong>Présence d'un balcon extérieur :</strong> 
                        <strong><?php echo $chambre['balcon_present'] ? 'Oui' : 'Non'; ?></strong>
                    </p>
                </div>
            </td>
            
            <td class="colonne-tarifs">
                <h3 class="titre-section black-bg">Grille tarifaire (Semaine)</h3>
                <table class="table-prix">
                    <thead>
                        <tr>
                            <th>Type de formule</th>
                            <th class="text-right">Prix de base / pers.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($formules as $f): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($f['type_formule']); ?></strong></td>
                            <td class="text-right prix-bleu"><?php echo $f['prix_base']; ?> €</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="remise-notice">Remises familles appliquées au moment de la validation finale.</p>
            </td>
        </tr>
    </table>

    <div class="zone-boutons">
        <a href="recherche.php" class="btn-link">
            <button class="btn-defaut">&larr; Retour à la recherche</button>
        </a>
        
        <?php if ($is_in_panier): ?>
            <a href="../actions/supprimer_panier.php?id=<?php echo $id_chambre; ?>" class="btn-link">
                <button class="btn-retirer">Retirer de ma sélection</button>
            </a>
            <a href="reservation.php" class="btn-link">
                <button class="btn-valider">Finaliser ma réservation &rarr;</button>
            </a>
        <?php else: ?>
        <a href="../actions/ajouter_panier.php?id=<?php echo $id_chambre; ?>&redirect=<?php echo urlencode('../pages/details.php?id=' . $id_chambre); ?>" class="btn-link">
            <button class="btn-ajouter">[+] Ajouter à ma sélection</button>
        </a>
        <?php endif; ?>
    </div>

</main>

<?php require_once '../includes/footer.php'; ?>