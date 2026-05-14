<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$sql = "SELECT * FROM chambre WHERE 1=1";
$parametres = [];


if (isset($_GET['vue'])) {
    if ($_GET['vue'] != "") {
        $sql = $sql . " AND type_vue = ?";
        $parametres[] = $_GET['vue'];
    }
}

if (isset($_GET['batiment'])) {
    if ($_GET['batiment'] != "") {
        $sql = $sql . " AND batiment = ?";
        $parametres[] = $_GET['batiment'];
    }
}

if (isset($_GET['nb_lits'])) {
    if ($_GET['nb_lits'] != "") {
        $sql = $sql . " AND nb_lits >= ?";
        $parametres[] = $_GET['nb_lits'];
    }
}


$prepa = $pdo->prepare($sql);
$prepa->execute($parametres);
$resultats = $prepa->fetchAll();
?>

<main>
    <h1>Rechercher une chambre</h1>

    <div style="background-color: #f9f9f9; padding: 15px; border: 1px solid black;">
        <form method="GET" action="recherche.php">
            <p>
                Vue : 
                <select name="vue">
                    <option value="">Peu importe</option>
                    <option value="pistes">Pistes</option>
                    <option value="parking">Parking</option>
                </select>
            </p>

            <p>
                Bâtiment : 
                <select name="batiment">
                    <option value="">Tous</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                </select>
            </p>

            <p>
                Nombre de lits : 
                <input type="number" name="nb_lits">
            </p>

            <p>
                <input type="submit" value="Filtrer les résultats">
            </p>
        </form>
    </div>

    <br>

    <table border="1">
        <tr>
            <th>Numéro</th>
            <th>Bâtiment</th>
            <th>Vue</th>
            <th>Lits</th>
            <th>Action</th>
        </tr>

        <?php

        if (count($resultats) > 0) {
          
            foreach ($resultats as $chambre) {
                echo "<tr>";
                echo "<td>" . $chambre['num_chambre'] . "</td>";
                echo "<td>" . $chambre['batiment'] . "</td>";
                echo "<td>" . $chambre['type_vue'] . "</td>";
                echo "<td>" . $chambre['nb_lits'] . "</td>";
                echo "<td><a href='details.php?id=" . $chambre['num_chambre'] . "'>Voir</a></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>Aucune chambre trouvée</td></tr>";
        }
        ?>
    </table>
</main>

<?php require_once '../includes/footer.php'; ?>