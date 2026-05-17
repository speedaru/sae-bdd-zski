<?php
/**
 * Page de déconnexion - Zarza-Ski
 * Emplacement : src/auth/logout.php
 * Sécurise la fin de session en nettoyant les données côté serveur et client.
 */

require_once '../includes/init.php';

$redirect_to = $_POST['redirect'] ?? $_GET['redirect'] ?? '../index.php';
$redirect_to = sanitize_redirect_url($redirect_to);

// Vider toutes les variables de session en mémoire
$_SESSION = array();

// 3. Détruire le cookie de session dans le navigateur du client
// C'est une mesure de sécurité cruciale pour éviter la réutilisation d'ID de session
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

// Détruire la session sur le serveur
session_destroy();

// Redirection vers la page ou on etait deja
header("Location: " . $redirect_to);
exit;
