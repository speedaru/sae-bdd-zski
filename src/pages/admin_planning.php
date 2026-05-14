<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/header.php';

if (isset($_SESSION['role']) == false) {
 
    header('Location: ../index.php');
    exit();
}

$le_role_de_la_personne = $_SESSION['role'];

if ($le_role_de_la_personne != 'admin' && $le_role_de_la_personne != 'gestionnaire') {

    header('Location: ../index.php');
    exit();
}

$texte_pour_le_mode_lecture_seule = "SET SESSION CHARACTERISTICS AS TRANSACTION READ ONLY";
$pdo->exec($texte_pour_le_mode_lecture_seule);


$ma_requete_pour_afficher_le_planning = "SELECT 
    reservation.date_debut, 
    reserver.num_chambre, 
    reserver.nom_groupe, 
    reserver.type_formule,
    client.nom_client,
    client.prenom_client
FROM reserver
JOIN reservation ON reserver.id_reservation = reservation.id_reservation
JOIN client ON reserver.id_client = client.id_client
ORDER BY reservation.date_debut ASC";


$resultat_de_la_requete = $pdo->query($ma_requete_pour_afficher_le_planning);

$liste_de_toutes_les_reservations = $resultat_de_la_requete->fetchAll();
?>

<main>
    <h1 align="center">TABLEAU DE BORD DU GESTIONNAIRE</h1>
    
    <p align="center"><b>Liste de toutes les réservations de la station Zarza-Ski :</b></p>

    <table border="5" align="center" cellpadding="15">
        <tr bgcolor="#CCCCCC">
            <td><b>DATE DE DEBUT</b></td>
            <td><b>NUMERO CHAMBRE</b></td>
            <td><b>NOM DU CLIENT</b></td>
            <td><b>PRENOM DU CLIENT</b></td>
            <td><b>NOM DU GROUPE</b></td>
            <td><b>FORMULE</b></td>
        </tr>

        <?php

        $combien_de_lignes = count($liste_de_toutes_les_reservations);

        if ($combien_de_lignes == 0) {
        
            echo "<tr>";
            echo "<td colspan='6' align='center'>Il n'y a personne dans la station.</td>";
            echo "</tr>";
        } else {
         
            foreach ($liste_de_toutes_les_reservations as $chaque_reservation) {
                echo "<tr>";
                echo "<td>" . $chaque_reservation['date_debut'] . "</td>";
                echo "<td>" . $chaque_reservation['num_chambre'] . "</td>";
                echo "<td>" . $chaque_reservation['nom_client'] . "</td>";
                echo "<td>" . $chaque_reservation['prenom_client'] . "</td>";
                echo "<td>" . $chaque_reservation['nom_groupe'] . "</td>";
                echo "<td>" . $chaque_reservation['type_formule'] . "</td>";
                echo "</tr>";
            }
        }
        ?>
    </table>

    <br>
    <p align="center">
        <a href="admin_tarifs.php">Cliquer ici pour changer les prix des chambres</a>
    </p>
</main>

<?php require_once '../includes/footer.php'; ?>