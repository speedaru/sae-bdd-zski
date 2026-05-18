<?php
/**
 * initialise la session, la base de donnees et les fonctions globales
 */

// demarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// connexion pdo
require_once 'db.php';

require_once 'auth_functions.php';
require_once 'functions.php';

// detection de la page actuelle (nom du fichier sans extension)
$current_page = basename($_SERVER['PHP_SELF'], ".php");

$base_url = get_relative_base_url();
