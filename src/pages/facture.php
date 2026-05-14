<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/header.php';

$v1 = $_GET['chambre'];
$v2 = $_GET['res'];

$num = intval($v1);
$id = intval($v2);

$sql1 = "SELECT * FROM chambre WHERE num_chambre = ?";
$q1 = $pdo->prepare($sql1);
$q1->execute(array($num));
$chambre = $q1->fetch();

$sql2 = "SELECT * FROM reserver WHERE num_chambre = ? AND id_reservation = ?";
$q2 = $pdo->prepare($sql2);
$pari = array($num, $id);
$q2->execute($pari);
$liste = $q2->fetchAll();

$total_f = 0;
$nb_occupe = 0;

foreach ($liste as $ligne) {
    $prix = $ligne['formule_prix_final'];
    $total_f = $total_f + $prix;
    
    $lit = $ligne['occupe_lit'];
    if ($lit == 1) {
        $nb_occupe = $nb_occupe + 1;
    }
}

$nb_total = $chambre['nb_lits'];
$nb_vides = $nb_total - $nb_occupe;

if ($nb_vides < 0) {
    $nb_vides = 0;
}

$taxe = $nb_vides * 150;
$total_tout = $total_f + $taxe;

echo "<br>";
echo "<center><h1><u>VOTRE FACTURE ZARZA-SKI</u></h1></center>";
echo "<br>";

echo "<center>";
echo "<table border='1' width='70%' cellpadding='15'>";
echo "<tr>";
echo "<td>";

echo "<b>Détail des personnes :</b><br>";
echo "<ul>";
foreach ($liste as $p) {
    echo "<li>";
    echo "Client " . $p['id_client'];
    echo " - Formule " . $p['type_formule'];
    echo " : " . $p['formule_prix_final'] . " €";
    echo "</li>";
}
echo "</ul>";

echo "<hr>";

echo "<b>Calcul des lits vides :</b><br>";
echo "Il y a " . $nb_total . " lits au total.<br>";
echo "Il y a " . $nb_occupe . " lits occupés.<br>";
echo "Taxe : " . $nb_vides . " lits vides x 150 € = <b>" . $taxe . " €</b><br>";

echo "<hr>";

echo "<p align='right'>";
echo "<b><font size='5' color='red'>TOTAL A PAYER : " . $total_tout . " €</font></b>";
echo "</p>";

echo "</td>";
echo "</tr>";
echo "</table>";

echo "<br>";
echo "<a href='mes_reservations.php'><button>RETOUR</button></a>";
echo "</center>";

require_once '../includes/footer.php';
?>