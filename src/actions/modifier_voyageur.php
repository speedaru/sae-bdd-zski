<?php
require_once __DIR__ . '/../includes/init.php';

$redirect_target = $_POST['redirect'] ?? $_GET['redirect'] ?? '../index.php';
$redirect_target = sanitize_redirect_url($redirect_target);

// protection d'acces
if (!isset($_SESSION['id_user'])) {
    header("Location: /auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . $redirect_target);
    exit();
}

$user_id = $_SESSION['id_user'];
$id_client = isset($_POST['id_client']) ? intval($_POST['id_client']) : 0;

if ($id_client <= 0) {
    $_SESSION['error'] = "Identifiant de voyageur manquant ou invalide.";
    header("Location: " . $redirect_target);
    exit();
}

// recuperation et nettoyage des champs avec date_naissance
$nom = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$date_naissance = trim($_POST['date_naissance'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');
$num_tel = trim($_POST['num_tel'] ?? '');
$niveau_ski = $_POST['niveau_ski'] ?? 'debutant';
$taille = floatval($_POST['taille'] ?? 0);
$poids = intval($_POST['poids'] ?? 0);
$pointure = floatval($_POST['pointure'] ?? 0);

if (empty($nom) || empty($prenom) || empty($date_naissance) || empty($adresse) || empty($num_tel) || $taille <= 0 || $poids <= 0 || $pointure <= 0) {
    $_SESSION['error'] = "Tous les champs doivent être saisis avec des valeurs valides.";
    header("Location: " . $redirect_target . "?action=edit&id=" . $id_client);
    exit();
}

try {
    // verifier la possession en base
    $stmtCheck = $pdo->prepare("SELECT 1 FROM gestion_voyageurs WHERE id_user = :id_user AND id_client = :id_client");
    $stmtCheck->execute([
        'id_user' => $user_id,
        'id_client' => $id_client
    ]);

    // ou sinon utilisateur essaie de se modifier lui meme
    $modifying_self = $id_client == $_SESSION['id_client'];
    
    if (!$stmtCheck->fetch() && !$modifying_self) {
        $_SESSION['error'] = "Action non autorisee.";
        header("Location: " . $redirect_target);
        exit();
    }

    // maj de la table client
    $stmtUpdate = $pdo->prepare("UPDATE client SET 
                                    nom = :nom, 
                                    prenom = :prenom, 
                                    date_naissance = :date_naissance,
                                    adresse = :adresse, 
                                    num_tel = :num_tel, 
                                    niveau_ski = :niveau_ski, 
                                    taille = :taille, 
                                    poids = :poids, 
                                    pointure = :pointure 
                                 WHERE id_client = :id_client");
    
    $stmtUpdate->execute([
        'nom' => $nom,
        'prenom' => $prenom,
        'date_naissance' => $date_naissance,
        'adresse' => $adresse,
        'num_tel' => $num_tel,
        'niveau_ski' => $niveau_ski,
        'taille' => $taille,
        'poids' => $poids,
        'pointure' => $pointure,
        'id_client' => $id_client
    ]);

    $_SESSION['success'] = "La fiche de " . h($prenom) . " " . h($nom) . " a ete mise à jour !";

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur technique lors de la modification : " . $e->getMessage();
}

header("Location: " . $redirect_target);
exit();
