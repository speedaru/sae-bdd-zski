<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

// 1. On récupère le numéro de la chambre dans l'adresse URL
// On utilise intval pour être sûr d'avoir un nombre entier
$numero_selectionne = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 2. On prépare la requête pour trouver les infos de CETTE chambre
$sql_chambre = "SELECT * FROM chambre WHERE num_chambre = ?";
$prepare_chambre = $pdo->prepare($sql_chambre);
$prepare_chambre->execute([$numero_selectionne]);
$infos_chambre = $prepare_chambre->fetch();

// 3. On récupère aussi la liste des prix (table formule)
$sql_formules = "SELECT * FROM formule";
$requete_prix = $pdo->query($sql_formules);
$toutes_les_formules = $requete_prix->fetchAll();


// 4. Sécurité : si la chambre n'existe pas, on retourne au catalogue
if (!$infos_chambre) {
    header('Location: recherche.php');
    exit;
}
?>

<main>
    <h1>Détails de la chambre n°<?php echo htmlspecialchars($infos_chambre['num_chambre']); ?></h1>
    
    <section class="infos-logement">
        <p>
            Cette chambre se trouve dans le <strong>Bâtiment <?php echo htmlspecialchars($infos_chambre['batiment']); ?></strong>, 
            au niveau de l'étage n°<?php echo htmlspecialchars($infos_chambre['etage']); ?>.
        </p>
        <p>
            Elle possède une surface de <?php echo htmlspecialchars($infos_chambre['superficie']); ?> m² 
            et peut accueillir des voyageurs sur ses <?php echo htmlspecialchars($infos_chambre['nb_lits']); ?> lits.
        </p>
        <p>
            Atout de la chambre : une vue dégagée sur <?php echo htmlspecialchars($infos_chambre['type_vue']); ?>. 
            Présence d'un balcon : <?php echo $infos_chambre['balcon_present'] ? 'Oui' : 'Non'; ?>.
        </p>
    </section>

    <hr>

    <h3>Grille tarifaire Zarza-Ski (par personne)</h3>
    <table border="1" style="width: 100%; text-align: left;">
        <thead>
            <tr>
                <th>Nom de la formule</th>
                <th>Prix de base</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($toutes_les_formules as $une_formule): ?>
                <tr>
                    <td><?php echo htmlspecialchars($une_formule['type_formule']); ?></td>
                    <td><?php echo htmlspecialchars($une_formule['prix_base']); ?> €</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top:20px;">
        <a href="recherche.php">Retour</a>
        <a href="reservation.php?id=<?php echo $id_chambre; ?>">Réserver maintenant</a>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
