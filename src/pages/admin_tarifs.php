<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/header.php';

if (isset($_SESSION['role']) == false) {
    header('Location: ../index.php');
    exit();
}

$ma_variable_de_role = $_SESSION['role'];

if ($ma_variable_de_role != 'admin') {
    if ($ma_variable_de_role != 'gestionnaire') {
        header('Location: ../index.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['valider_changement']) == true) {

        $donnee_du_prix_post = $_POST['nouveau_prix'];
        $prix_en_chiffre_entier = intval($donnee_du_prix_post);

        $nom_de_la_prestation_post = $_POST['formule_concernee'];

        $phrase_sql_pour_modifier = "UPDATE formule SET prix_base = ? WHERE type_formule = ?";
        
        $preparation_de_la_commande = $pdo->prepare($phrase_sql_pour_modifier);

        $liste_des_donnees_a_envoyer = array();
        $liste_des_donnees_a_envoyer[] = $prix_en_chiffre_entier;
        $liste_des_donnees_a_envoyer[] = $nom_de_la_prestation_post;

        $preparation_de_la_commande->execute($liste_des_donnees_a_envoyer);

        $phrase_pour_dire_que_ca_a_marche = "Le nouveau prix de la formule " . $nom_de_la_prestation_post . " est maintenant de " . $prix_en_chiffre_entier . " euros.";
    }
}

$phrase_sql_pour_lire_les_prix = "SELECT * FROM formule";
$mon_execution_de_lecture = $pdo->query($phrase_sql_pour_lire_les_prix);
$le_tableau_avec_tous_les_prix = $mon_execution_de_lecture->fetchAll();

echo "<br>";
echo "<br>";
echo "<center><h1><u>PAGE DE MODIFICATION DES PRIX (ADMIN)</u></h1></center>";
echo "<br>";

if (isset($phrase_pour_dire_que_ca_a_marche) == true) {
    echo "<center><font color='blue'><b>MESSAGE : " . $phrase_pour_dire_que_ca_a_marche . "</b></font></center>";
    echo "<br>";
}

echo "<table border='10' align='center' cellpadding='20' cellspacing='0'>";
echo "<tr bgcolor='#999999'>";
    echo "<td><b><font color='white'>NOM DE LA FORMULE</font></b></td>";
    echo "<td><b><font color='white'>PRIX DANS LA BASE</font></b></td>";
    echo "<td><b><font color='white'>TAPER LE NOUVEAU CHIFFRE</font></b></td>";
    echo "<td><b><font color='white'>CLIQUER POUR ENREGISTRER</font></b></td>";
echo "</tr>";

foreach ($le_tableau_avec_tous_les_prix as $chaque_ligne_de_prix) {
    echo "<tr>";
        echo "<td>" . $chaque_ligne_de_prix['type_formule'] . "</td>";
        echo "<td>" . $chaque_ligne_de_prix['prix_base'] . " €</td>";

        echo "<form method='POST' action='admin_tarifs.php'>";
            echo "<td>";
                echo "Entrez le prix : <input type='number' name='nouveau_prix' value='" . $chaque_ligne_de_prix['prix_base'] . "'>";
                echo "<input type='hidden' name='formule_concernee' value='" . $chaque_ligne_de_prix['type_formule'] . "'>";
            echo "</td>";
            echo "<td>";
                echo "<input type='submit' name='valider_changement' value='METTRE A JOUR'>";
            echo "</td>";
        echo "</form>";
    echo "</tr>";
}

echo "</table>";

echo "<br>";
echo "<br>";
echo "<center><a href='../index.php'>Retourner sur la page d'accueil de Zarza-Ski</a></center>";
echo "<br>";

require_once '../includes/footer.php';
?>