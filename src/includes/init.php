<?php
/**
 * Point d'entrée unique du projet Zarza-Ski
 * Initialise la session, la base de données et les fonctions globales.
 */

// 1. Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Inclusion de la connexion PDO (db.php est dans le même dossier)
require_once 'db.php';

// 3. Inclusion de la bibliothèque de fonctions
require_once 'auth_functions.php';
require_once 'functions.php';

// 4. Détection de la page actuelle (nom du fichier sans l'extension)
// Utile pour le chargement dynamique du CSS et la gestion du menu actif
$current_page = basename($_SERVER['PHP_SELF'], ".php");

// 5. Définition du chemin de base pour les assets (évite les erreurs de dossiers)
// On détecte si on est dans un sous-dossier (auth, pages, etc.)
$base_url = get_relative_base_url();