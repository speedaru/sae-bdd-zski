<?php
/**
 * page de deconnexion
 * securise la fin de session en nettoyant les donnees cote serveur et client
 */

require_once '../includes/init.php';

$redirect_to = $_POST['redirect'] ?? $_GET['redirect'] ?? '../index.php';
$redirect_to = sanitize_redirect_url($redirect_to);

// vider toutes les variables de session en memoire
$_SESSION = array();

// detruire le cookie de session dans le navigateur du client
// pour eviter la reutilisation d'id de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// detruire la session sur le serveur
session_destroy();

// redirection vers la page ou on etait deja
header("Location: " . $redirect_to);
exit;
