<?php
/**
 * Action PHP - Ajouter Voyageur au Carnet
 * Emplacement : src/actions/ajouter_voyageur.php
 */

require_once __DIR__ . '/../includes/init.php';

// Protection d'accès
if (!isset($_SESSION['id_user'])) {
    header("Location: /auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/carnet.php");
    exit();
}

$user_id = $_SESSION['id_user'];

// 1. Récupération et nettoyage des données POST
$nom            = trim($_POST['nom'] ?? '');
$prenom         = trim($_POST['prenom'] ?? '');
$date_naissance = trim($_POST['date_naissance'] ?? '');
$adresse        = trim($_POST['adresse'] ?? '');
$num_tel        = trim($_POST['num_tel'] ?? '');
$niveau_ski     = $_POST['niveau_ski'] ?? 'débutant';
$taille         = floatval($_POST['taille'] ?? 0);
$poids          = intval($_POST['poids'] ?? 0);
$pointure       = floatval($_POST['pointure'] ?? 0);

// Validation avec le nouveau champ date_naissance
if (empty($nom) || empty($prenom) || empty($date_naissance) || empty($adresse) || empty($num_tel) || $taille <= 0 || $poids <= 0 || $pointure <= 0) {
    $_SESSION['error'] = "Veuillez remplir correctement tous les champs obligatoires.";
    header("Location: ../pages/carnet.php");
    exit();
}

try {
    // 2. TRANSACTION POSTGRESQL
    $pdo->beginTransaction();

    // Insertion du voyageur avec date_naissance
    $sqlClient = "INSERT INTO client (nom, prenom, date_naissance, adresse, num_tel, niveau_ski, taille, poids, pointure) 
                  VALUES (:nom, :prenom, :date_naissance, :adresse, :num_tel, :niveau_ski, :taille, :poids, :pointure)";
                  
    $stmtClient = $pdo->prepare($sqlClient);
    $stmtClient->execute([
        'nom'            => $nom,
        'prenom'         => $prenom,
        'date_naissance' => $date_naissance,
        'adresse'        => $adresse,
        'num_tel'        => $num_tel,
        'niveau_ski'     => $niveau_ski,
        'taille'         => $taille,
        'poids'          => $poids,
        'pointure'       => $pointure
    ]);

    $id_client_cree = $pdo->lastInsertId();

    // Liaison carnet d'adresses
    $sqlLiaison = "INSERT INTO gestion_voyageurs (id_user, id_client) VALUES (:id_user, :id_client)";
    $stmtLiaison = $pdo->prepare($sqlLiaison);
    $stmtLiaison->execute([
        'id_user'   => $user_id,
        'id_client' => $id_client_cree
    ]);

    $pdo->commit();

    $_SESSION['success'] = "Le membre " . h($prenom) . " " . h($nom) . " a été ajouté à votre tribu !";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = "Erreur technique lors de l'enregistrement : " . $e->getMessage();
}

header("Location: ../pages/carnet.php");
exit();