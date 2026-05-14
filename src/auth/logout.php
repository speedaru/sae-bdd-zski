<?php
/**
 * Page de déconnexion - Zarza-Ski
 * Emplacement : src/auth/logout.php
 * Sécurise la fin de session en nettoyant les données côté serveur et client.
 */

// 1. Initialiser la session pour pouvoir la manipuler
session_start();

// 2. Vider toutes les variables de session en mémoire
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

// 4. Détruire la session sur le serveur
session_destroy();

// 5. Redirection vers la page d'accueil (située dans src/)
header("Location: ../index.php");
exit;
