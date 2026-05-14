<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/header.php';



if (isset($_SESSION['id_client']) == false) {
    echo "<center><font color='red'><h2>Attention : Accès interdit</h2></font>";
    echo "Il faut être connecté pour voir cette page.<br>";
    echo "<a href='../auth/login.php'>Retourner au Login</a></center>";
    exit();
}

$numero_client_actuel = $_SESSION['id_client'];


$sql_pour_mes_reservations = "SELECT 
            reservation.date_debut, 
            reserver.num_chambre, 
            reserver.nom_groupe, 
            reserver.formule_prix_final, 
            reserver.id_reservation, 
            chambre.batiment
        FROM reserver
        JOIN reservation ON reserver.id_reservation = reservation.id_reservation
        JOIN chambre ON reserver.num_chambre = chambre.num_chambre
        WHERE reserver.id_client = ?
        ORDER BY reservation.date_debut DESC";

$preparation_de_la_requete = $pdo->prepare($sql_pour_mes_reservations);
$preparation_de_la_requete->execute([$numero_client_actuel]);
$tableau_de_resultats = $preparation_de_la_requete->fetchAll();

$nombre_de_lignes = count($tableau_de_resultats);

echo "<center><h1>MES RESERVATIONS CHEZ ZARZA-SKI</h1></center>";

if ($nombre_de_lignes == 0) {
    echo "<p align='center'>Vous n'avez aucune réservation enregistrée.</p>";
} else {
    echo "<p align='center'>Vous avez " . $nombre_de_lignes . " séjour(s) de réservé(s).</p>";
    
 
    echo "<table border='4' align='center' cellpadding='15' cellspacing='5'>";
    

    echo "<tr bgcolor='#808080'>";
        echo "<td><b><font color='white'>DATE</font></b></td>";
        echo "<td><b><font color='white'>N° CHAMBRE</font></b></td>";
        echo "<td><b><font color='white'>BATIMENT</font></b></td>";
        echo "<td><b><font color='white'>PRIX</font></b></td>";
        echo "<td><b><font color='white'>OPTIONS</font></b></td>";
    echo "</tr>";

  
    foreach ($tableau_de_resultats as $ma_ligne) {
        echo "<tr>";
            echo "<td>Semaine du " . $ma_ligne['date_debut'] . "</td>";
            echo "<td>Chambre " . $ma_ligne['num_chambre'] . "</td>";
            echo "<td>" . $ma_ligne['batiment'] . "</td>";
            echo "<td>" . $ma_ligne['formule_prix_final'] . " Euros</td>";
            echo "<td>";
          
                echo "<a href='facture.php?chambre=" . $ma_ligne['num_chambre'] . "&res=" . $ma_ligne['id_reservation'] . "'>Voir Facture</a>";
                echo " | ";
                echo "<a href='annuler.php?res=" . $ma_ligne['id_reservation'] . "&chambre=" . $ma_ligne['num_chambre'] . "' style='color:red;'>Annuler tout</a>";
            echo "</td>";
        echo "</tr>";
    }

    echo "</table>";
}

echo "<br><br><center><a href='recherche.php'>Revenir à la recherche de chambres</a></center>";

require_once '../includes/footer.php';
?>