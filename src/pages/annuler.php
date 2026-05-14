<?php
session_start();
require_once '../includes/db.php';

if (isset($_SESSION['id_client']) == false) {
    echo "Vous n'avez pas le droit d'être ici";
    exit();
}

$identifiant_du_client_connecte = $_SESSION['id_client'];

$id_reservation_url = $_GET['res'];
$id_reservation_final = intval($id_reservation_url);

$numero_chambre_url = $_GET['chambre'];
$numero_chambre_final = intval($numero_chambre_url);

$ma_requete_sql_pour_effacer = "DELETE FROM reserver WHERE id_client = ? AND id_reservation = ? AND num_chambre = ?";

$ma_preparation_de_requete = $pdo->prepare($ma_requete_sql_pour_effacer);

$mon_tableau_de_parametres = array();
$mon_tableau_de_parametres[] = $identifiant_du_client_connecte;
$mon_tableau_de_parametres[] = $id_reservation_final;
$mon_tableau_de_parametres[] = $numero_chambre_final;

$ma_preparation_de_requete->execute($mon_tableau_de_parametres);

header("Location: mes_reservations.php");
exit();
?>