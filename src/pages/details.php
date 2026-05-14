<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$donnee_recue_dans_url = $_GET['id'];
$identifiant_chambre_propre = intval($donnee_recue_dans_url);


$ma_commande_sql_pour_la_chambre = "SELECT * FROM chambre WHERE num_chambre = ?";
$ma_preparation = $pdo->prepare($ma_commande_sql_pour_la_chambre);
$mon_tableau_de_parametres = array($identifiant_chambre_propre);
$ma_preparation->execute($mon_tableau_de_parametres);
$ma_ligne_de_chambre = $ma_preparation->fetch();


$ma_commande_sql_pour_les_prix = "SELECT * FROM formule";
$mon_resultat_prix = $pdo->query($ma_commande_sql_pour_les_prix);
$la_liste_de_tous_les_tarifs = $mon_resultat_prix->fetchAll();


if ($ma_ligne_de_chambre == false) {
    echo "<center><h1>ERREUR : La chambre n'existe pas !</h1>";
    echo "<a href='recherche.php'>Retourner à la recherche</a></center>";
    exit();
}


echo "<br>";
echo "<center>";
    echo "<h1><font color='blue'><u>FICHE DE PRESENTATION : CHAMBRE " . $ma_ligne_de_chambre['num_chambre'] . "</u></font></h1>";
echo "</center>";
echo "<br>";

echo "<table border='1' width='90%' align='center' cellpadding='20'>";
    echo "<tr>";
        echo "<td>";
            echo "<b>Détails techniques du logement :</b><br><br>";
            echo "Bâtiment : " . $ma_ligne_de_chambre['batiment'] . "<br>";
            echo "Étage : " . $ma_ligne_de_chambre['etage'] . "<br>";
            echo "Superficie : " . $ma_ligne_de_chambre['superficie'] . " mètres carrés<br>";
            echo "Nombre de lits : " . $ma_ligne_de_chambre['nb_lits'] . " couchages disponibles<br>";
            echo "Exposition : " . $ma_ligne_de_chambre['type_vue'] . "<br>";
            
            echo "Balcon : ";
            $valeur_balcon = $ma_ligne_de_chambre['balcon_present'];
            if ($valeur_balcon == 1) {
                echo "<b>OUI</b>";
            } else {
                echo "<b>NON</b>";
            }
        echo "</td>";
    echo "</tr>";
echo "</table>";

echo "<br>";
echo "<center><h3>LES TARIFS DE LA STATION ZARZA-SKI</h3></center>";

echo "<table border='10' align='center' width='60%' bgcolor='#F0F0F0'>";
    echo "<tr bgcolor='#999999'>";
        echo "<td><b><font color='white'>TYPE DE FORMULE</font></b></td>";
        echo "<td><b><font color='white'>PRIX PAR PERSONNE</font></b></td>";
    echo "</tr>";

    foreach ($la_liste_de_tous_les_tarifs as $ma_formule) {
        echo "<tr>";
            echo "<td>" . $ma_formule['type_formule'] . "</td>";
            echo "<td>" . $ma_formule['prix_base'] . " €</td>";
        echo "</tr>";
    }
echo "</table>";

echo "<br><br>";

echo "<table width='100%'>";
    echo "<tr>"; 
        echo "<td align='center'>";
            echo "<a href='recherche.php'><button>RETOUR A LA LISTE</button></a>";
            echo " &nbsp;&nbsp;&nbsp;&nbsp; ";
            echo "<a href='reservation.php?id=" . $identifiant_chambre_propre . "'>";
                echo "<button style='background-color:black;'><b>RESERVER CE LOGEMENT</b></button>";
            echo "</a>";
        echo "</td>";
    echo "</tr>";
echo "</table>";

echo "<br><br>";

require_once '../includes/footer.php';
?>
