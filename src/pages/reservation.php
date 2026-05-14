<?php
session_start();
require_once '../includes/db.php';

// On vérifie juste si le client est là
if (!isset($_SESSION['id_client'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id_chambre = intval($_GET['id']);
$id_client = $_SESSION['id_client'];

// On prépare les formules pour le formulaire
$formules = $pdo->query("SELECT * FROM formule")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debut = $_POST['date_debut'];
    $fin = date('Y-m-d', strtotime($debut . ' + 7 days'));
    $nom_groupe = $_POST['nom_groupe'];
    $type_formule = $_POST['type_formule'];

    try {
        // On démarre la sécurité
        $pdo->beginTransaction();

        // Étape 1 : Est-ce que la date existe déjà ?
        $sql_date = "SELECT id_reservation FROM reservation WHERE date_debut = ? AND date_fin = ?";
        $stmt_date = $pdo->prepare($sql_date);
        $stmt_date->execute([$debut, $fin]);
        $id_res = $stmt_date->fetchColumn();

        if (!$id_res) {
            $ins_date = "INSERT INTO reservation (date_debut, date_fin) VALUES (?, ?) RETURNING id_reservation";
            $res_ins = $pdo->prepare($ins_date);
            $res_ins->execute([$debut, $fin]);
            $id_res = $res_ins->fetchColumn();
        }

        // Étape 2 : Verrouillage pour éviter que quelqu'un d'autre prenne la chambre
        $sql_verrou = "SELECT 1 FROM reserver WHERE num_chambre = ? AND id_reservation = ? FOR UPDATE";
        $stmt_verrou = $pdo->prepare($sql_verrou);
        $stmt_verrou->execute([$id_chambre, $id_res]);

        if ($stmt_verrou->fetch()) {
            // Si on trouve quelque chose, c'est que c'est déjà pris !
            throw new Exception("Désolé, cette chambre est déjà occupée pour cette semaine.");
        }

        // Étape 3 : On crée le groupe s'il n'existe pas
        $sql_groupe = "INSERT INTO groupe (nom_groupe) VALUES (?) ON CONFLICT DO NOTHING";
        $pdo->prepare($sql_groupe)->execute([$nom_groupe]);

        // Étape 4 : On récupère le prix pour l'enregistrer
        $sql_prix = "SELECT prix_base FROM formule WHERE type_formule = ?";
        $stmt_p = $pdo->prepare($sql_prix);
        $stmt_p->execute([$type_formule]);
        $prix = $stmt_p->fetchColumn();

        // Étape 5 : L'enregistrement final
        $sql_final = "INSERT INTO reserver (id_client, nom_groupe, num_chambre, type_formule, id_reservation, occupe_lit, formule_prix_final) 
                      VALUES (?, ?, ?, ?, ?, true, ?)";
        $pdo->prepare($sql_final)->execute([$id_client, $nom_groupe, $id_chambre, $type_formule, $id_res, $prix]);

        // Si on arrive ici, tout s'est bien passé
        $pdo->commit();
        header("Location: mes_reservations.php");
        exit();

    } catch (Exception $e) {
        // En cas de bug, on annule tout ce qui a été fait dans le bloc try
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

require_once '../includes/header.php';
?>

<main>
    <h1>Réserver la chambre n°<?php echo $id_chambre; ?></h1>
    
    <?php if (isset($error)) echo "<p style='color:red'>Erreur : $error</p>"; ?>

    <form method="POST">
        <p>
            <label>Nom de votre groupe :</label><br>
            <input type="text" name="nom_groupe" required>
        </p>

        <p>
            <label>Date de début (Samedi) :</label><br>
            <input type="date" name="date_debut" required>
        </p>

        <p>
            <label>Choix de la formule :</label><br>
            <select name="type_formule">
                <?php foreach ($formules as $f): ?>
                    <option value="<?php echo $f['type_formule']; ?>"><?php echo $f['type_formule']; ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <button type="submit">Valider ma réservation</button>
    </form>
</main>

<?php require_once '../includes/footer.php'; ?>