<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/functions.php';

require_login("../pages/reservation.php");

$id_chambre = intval($_GET['id']);
$id_client = $_SESSION['id_client'];

$sql_formules = "SELECT * FROM formule";
$reponse_formules = $pdo->query($sql_formules);
$tableau_formules = $reponse_formules->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_debut = $_POST['date_debut'];
    $nom_groupe = $_POST['nom_groupe'];
    $type_formule = $_POST['type_formule'];
    
    $timestamp_debut = strtotime($date_debut);
    $timestamp_fin = $timestamp_debut + (7 * 24 * 60 * 60);
    $date_fin = date('Y-m-d', $timestamp_fin);
    $numero_jour = date('w', $timestamp_debut);
    
    if ($numero_jour != 6) { // samedi
        $mon_erreur = "Le séjour doit commencer un samedi.";
    } else {
       
        try {
            $pdo->beginTransaction();

            $sql_verif = "SELECT id_reservation FROM reservation WHERE date_debut = ? AND date_fin = ?";
            $req_verif = $pdo->prepare($sql_verif);
            $req_verif->execute([$date_debut, $date_fin]);
            $id_trouve = $req_verif->fetchColumn();

            if (!$id_trouve) {
                $sql_ajout_date = "INSERT INTO reservation (date_debut, date_fin) VALUES (?, ?)";
                $req_ajout_date = $pdo->prepare($sql_ajout_date);
                $req_ajout_date->execute([$date_debut, $date_fin]);
                $id_reservation_finale = $pdo->lastInsertId();
            } else {
                $id_reservation_finale = $id_trouve;
            }

            $sql_double = "SELECT * FROM reserver WHERE num_chambre = ? AND id_reservation = ? FOR UPDATE";
            $req_double = $pdo->prepare($sql_double);
            $req_double->execute([$id_chambre, $id_reservation_finale]);
            $verrou = $req_double->fetch();
            
            if ($verrou != false) {
                throw new Exception("Dommage, la chambre est déjà réservée pour cette date.");
            }

            $sql_ins_groupe = "INSERT INTO groupe (nom_groupe) VALUES (?) ON CONFLICT DO NOTHING";
            $req_ins_groupe = $pdo->prepare($sql_ins_groupe);
            $req_ins_groupe->execute([$nom_groupe]);

            $sql_le_prix = "SELECT prix_base FROM formule WHERE type_formule = ?";
            $req_le_prix = $pdo->prepare($sql_le_prix);
            $req_le_prix->execute([$type_formule]);
            $prix_a_payer = $req_le_prix->fetchColumn();

            $sql_final = "INSERT INTO reserver (id_client, nom_groupe, num_chambre, type_formule, id_reservation, occupe_lit, formule_prix_final) 
                          VALUES (?, ?, ?, ?, ?, true, ?)";
            $req_final = $pdo->prepare($sql_final);
            $req_final->execute([$id_client, $nom_groupe, $id_chambre, $type_formule, $id_reservation_finale, $prix_a_payer]);

            $pdo->commit();
            
            echo "<p style='color:green;'>Réservation réussie !</p>";
            echo "<a href='mes_reservations.php'>Voir mes réservations</a>";
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $mon_erreur = $e->getMessage();
        }
    }
}
?>

<main>
    <h1>Formulaire de réservation Zarza-Ski</h1>
    <p>Vous avez choisi la chambre numéro <?php echo $id_chambre; ?>.</p>

    <?php if (isset($mon_erreur)) { ?>
        <p style="color: red; border: 1px solid red; padding: 10px;">
            <?php echo $mon_erreur; ?>
        </p>
    <?php } ?>

    <form method="POST" action="">
        <p>
            <b>Votre groupe :</b><br>
            <input type="text" name="nom_groupe" required placeholder="Nom de votre famille">
        </p>

        <p>
            <b>Date de début (Samedi obligatoire) :</b><br>
            <input type="date" name="date_debut" required>
        </p>

        <p>
            <b>Choisissez votre formule :</b><br>
            <select name="type_formule">
                <?php foreach ($tableau_formules as $f) { ?>
                    <option value="<?php echo $f['type_formule']; ?>">
                        <?php echo $f['type_formule']; ?> (prix : <?php echo $f['prix_base']; ?> €)
                    </option>
                <?php } ?>
            </select>
        </p>

        <p>
            <input type="submit" value="Confirmer ma réservation">
        </p>
    </form>
    
    <p><a href="recherche.php">Retourner en arrière</a></p>
</main>

<?php require_once '../includes/footer.php'; ?>
