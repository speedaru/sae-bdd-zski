<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/functions.php';

require_login("../pages/reservation.php");

$id_chambre = intval($_GET['id']);
$id_client = $_SESSION['id_client'];

$sql_cap = "SELECT * FROM chambre WHERE num_chambre = ?";
$q_cap = $pdo->prepare($sql_cap);
$q_cap->execute([$id_chambre]);
$res_cap = $q_cap->fetch();
$nb_lits = $res_cap['nb_lits'];

$sql_f = "SELECT * FROM formule";
$q_f = $pdo->query($sql_f);
$formules = $q_f->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $date_deb = $_POST['date_debut'];
    $groupe = $_POST['nom_groupe'];
    $type_f = $_POST['type_formule'];
    
    $nb_p = $_POST['nb_personnes'];
    $nb_p_int = intval($nb_p);
    
    $t_deb = strtotime($date_deb);
    $t_fin = $t_deb + (7 * 24 * 60 * 60);
    $date_fin = date('Y-m-d', $t_fin);

    $jour = date('w', $t_deb);
    
    if ($jour != 6) {
        $err = "Le séjour doit commencer un samedi.";
    } else if ($nb_p_int > $nb_lits) {
        $err = "Erreur : Vous êtes trop nombreux pour cette chambre qui n'a que " . $nb_lits . " lits.";
    } else {
       
        try {
            $pdo->beginTransaction();

            $sql_v = "SELECT id_reservation FROM reservation WHERE date_debut = ? AND date_fin = ?";
            $q_v = $pdo->prepare($sql_v);
            $q_v->execute([$date_deb, $date_fin]);
            $id_res = $q_v->fetchColumn();

            if ($id_res == false) {
                $sql_add = "INSERT INTO reservation (date_debut, date_fin) VALUES (?, ?)";
                $q_add = $pdo->prepare($sql_add);
                $q_add->execute([$date_deb, $date_fin]);
                $id_res_fin = $pdo->lastInsertId();
            } else {
                $id_res_fin = $id_res;
            }

            $sql_d = "SELECT * FROM reserver WHERE num_chambre = ? AND id_reservation = ? FOR UPDATE";
            $q_d = $pdo->prepare($sql_d);
            $q_d->execute([$id_chambre, $id_res_fin]);
            $lock = $q_d->fetch();
            
            if ($lock != false) {
                throw new Exception("Dommage, la chambre est déjà réservée pour cette date.");
            }

            $sql_g = "INSERT INTO groupe (nom_groupe) VALUES (?) ON CONFLICT DO NOTHING";
            $q_g = $pdo->prepare($sql_g);
            $q_g->execute([$groupe]);

            $sql_p = "SELECT prix_base FROM formule WHERE type_formule = ?";
            $q_p = $pdo->prepare($sql_p);
            $q_p->execute([$type_f]);
            $prix = $q_p->fetchColumn();

            // On calcule le prix total du groupe
            $prix_total_groupe = $prix * $nb_p_int;

            // On laisse 'true' pour le champ boolean occupe_lit pour éviter l'erreur SQL
            $sql_fin = "INSERT INTO reserver (id_client, nom_groupe, num_chambre, type_formule, id_reservation, occupe_lit, formule_prix_final) 
                        VALUES (?, ?, ?, ?, ?, true, ?)";
            $q_fin = $pdo->prepare($sql_fin);
            $q_fin->execute([$id_client, $groupe, $id_chambre, $type_f, $id_res_fin, $prix_total_groupe]);

            $pdo->commit();
            
            echo "<p style='color:green;'>Réservation réussie !</p>";
            echo "<a href='mes_reservations.php'>Voir mes réservations</a>";
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $err = $e->getMessage();
        }
    }
}
?>

<main>
    <h1>Formulaire de réservation Zarza-Ski</h1>
    <p>Vous avez choisi la chambre numéro <?php echo $id_chambre; ?> (Capacité maximale : <?php echo $nb_lits; ?> lits).</p>

    <?php if (isset($err)) { ?>
        <p style="color: red; border: 1px solid red; padding: 10px;">
            <?php echo $err; ?>
        </p>
    <?php } ?>

    <form method="POST" action="">
        <p>
            <b>Votre groupe :</b><br>
            <input type="text" name="nom_groupe" required placeholder="Nom de votre famille">
        </p>

        <p>
            <b>Nombre de personnes venant dans la chambre :</b><br>
            <input type="number" name="nb_personnes" min="1" max="<?php echo $nb_lits; ?>" required>
        </p>

        <p>
            <b>Date de début (Samedi obligatoire) :</b><br>
            <input type="date" name="date_debut" required>
        </p>

        <p>
            <b>Choisissez votre formule :</b><br>
            <select name="type_formule">
                <?php foreach ($formules as $f) { ?>
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
