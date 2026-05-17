<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/header.php';

$id_get = $_GET['id'] ?? 0;
$id_chambre = intval($id_get);

$stmtChambre = $pdo->prepare("SELECT * FROM chambre WHERE num_chambre = ?");
$stmtChambre->execute([$id_chambre]);
$chambre = $stmtChambre->fetch(PDO::FETCH_ASSOC);

$stmtFormules = $pdo->query("SELECT * FROM formule ORDER BY prix_base ASC");
$formules = $stmtFormules->fetchAll(PDO::FETCH_ASSOC);

if ($chambre == false) {
    echo "<br><br><center>";
    echo "<h1 style='color:red;'>ERREUR : La chambre n'existe pas !</h1>";
    echo "<p>Le logement demandé est introuvable dans la base de données de notre station.</p>";
    echo "<a href='recherche.php'>Retourner à la recherche</a>";
    echo "</center>";
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

    <br><br>

    <table class="table-presentation" border="1" cellpadding="20" cellspacing="0">
        <tr>
            <td class="colonne-technique" valign="top">
                <h3 class="titre-section blue-bg">Caractéristiques techniques</h3>
                <p><b>Bâtiment :</b> Bâtiment <?php echo htmlspecialchars($chambre['batiment']); ?></p>
                <p><b>Étage :</b> Niveau <?php echo $chambre['etage']; ?></p>
                <p><b>Superficie :</b> <?php echo $chambre['superficie']; ?> m²</p>
                <p><b>Capacité d'accueil :</b> <?php echo $chambre['nb_lits']; ?> couchages individuels</p>
                <p><b>Exposition & Panorama :</b> Vue imprenable sur <?php echo htmlspecialchars($chambre['type_vue']); ?></p>
                <p>
                    <b>Présence d'un balcon extérieur :</b> 
                    <b><?php echo $chambre['balcon_present'] ? 'Oui' : 'Non'; ?></b>
                </p>
            </td>
            
            <td class="colonne-tarifs" valign="top">
                <h3 class="titre-section black-bg">Grille tarifaire (Semaine)</h3>
                <table class="table-prix" border="1" cellpadding="10" cellspacing="0">
                    <tr bgcolor="#EEEEEE">
                        <td><b>Type de formule</b></td>
                        <td align="right"><b>Prix de base / pers.</b></td>
                    </tr>
                    <?php foreach ($formules as $f): ?>
                    <tr>
                        <td><b><?php echo htmlspecialchars($f['type_formule']); ?></b></td>
                        <td align="right" class="prix-bleu"><?php echo $f['prix_base']; ?> €</td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <br>
                <p align="center"><i><small>Remises familles appliquées au moment de la validation finale.</small></i></p>
            </td>
        </tr>
    </table>

    <br><br><br>

    <div class="zone-boutons">
        <a href="recherche.php"><button class="btn-defaut">Retour à la recherche</button></a>
        &nbsp;&nbsp;&nbsp;&nbsp;
        
        <?php if ($is_in_panier): ?>
            <a href="../actions/supprimer_panier.php?id=<?php echo $id_chambre; ?>">
                <button class="btn-retirer">Retirer de ma sélection</button>
            </a>
            &nbsp;&nbsp;&nbsp;&nbsp;
            <a href="reservation.php">
                <button class="btn-valider">Finaliser ma réservation</button>
            </a>
        <?php else: ?>
            <a href="../actions/ajouter_panier.php?id=<?php echo $id_chambre; ?>">
                <button class="btn-ajouter">Ajouter à ma sélection</button>
            </a>
        <?php endif; ?>
    </div>

</main>

<?php require_once '../includes/footer.php'; ?>