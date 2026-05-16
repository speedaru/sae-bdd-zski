<?php
/**
 * Action PHP - Ajouter Voyageur au Carnet
 * Emplacement : src/actions/ajouter_voyageur.php
 * Traite les données du formulaire de création d'un skieur dans le carnet d'adresses.
 */

// On charge l'initialisation système (session, pdo, fonctions globales)
require_once __DIR__ . '/../includes/init.php';

require_login("../index.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/carnet.php");
    exit();
}

$user_id = $_SESSION['id_user'];

// 1. Récupération et nettoyage des données POST
$nom        = trim($_POST['nom'] ?? '');
$prenom     = trim($_POST['prenom'] ?? '');
$adresse    = trim($_POST['adresse'] ?? '');
$num_tel    = trim($_POST['num_tel'] ?? '');
$niveau_ski = $_POST['niveau_ski'] ?? 'débutant';
$taille     = floatval($_POST['taille'] ?? 0);
$poids      = intval($_POST['poids'] ?? 0);
$pointure   = floatval($_POST['pointure'] ?? 0);

// Validation simple
if (empty($nom) || empty($prenom) || empty($adresse) || empty($num_tel) || $taille <= 0 || $poids <= 0 || $pointure <= 0) {
    $_SESSION['error'] = "Veuillez remplir correctement tous les champs obligatoires.";
    header("Location: ../pages/carnet.php");
    exit();
}

try {
    // 2. TRANSACTION POSTGRESQL (Début de la transaction de sécurité)
    $pdo->beginTransaction();

    // Étape A : Insertion dans la table client
    $sqlClient = "INSERT INTO client (nom, prenom, adresse, num_tel, niveau_ski, taille, poids, pointure) 
                  VALUES (:nom, :prenom, :adresse, :num_tel, :niveau_ski, :taille, :poids, :pointure)";
                  
    $stmtClient = $pdo->prepare($sqlClient);
    $stmtClient->execute([
        'nom'        => $nom,
        'prenom'     => $prenom,
        'adresse'    => $adresse,
        'num_tel'    => $num_tel,
        'niveau_ski' => $niveau_ski,
        'taille'     => $taille,
        'poids'      => $poids,
        'pointure'   => $pointure
    ]);

    // Étape B : Récupération de l'ID client généré
    $id_client_cree = $pdo->lastInsertId();

    // Étape C : Liaison dans la table de carnet de voyageurs d'adresses (gestion_voyageurs)
    $sqlLiaison = "INSERT INTO gestion_voyageurs (id_user, id_client) VALUES (:id_user, :id_client)";
    $stmtLiaison = $pdo->prepare($sqlLiaison);
    $stmtLiaison->execute([
        'id_user'   => $user_id,
        'id_client' => $id_client_cree
    ]);

    // Étape D : Validation et écriture physique des données
    $pdo->commit();

    $_SESSION['success'] = "Le membre " . h($prenom) . " " . h($nom) . " a été ajouté à votre tribu avec succès !";

} catch (PDOException $e) {
    // En cas d'erreur inattendue BDD : Rollback pour annuler la transaction et éviter de polluer les tables
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = "Erreur technique lors de l'enregistrement : " . $e->getMessage();
}

// Redirection propre vers la page de gestion
header("Location: ../pages/carnet.php");
exit();