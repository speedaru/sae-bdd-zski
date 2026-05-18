<?php
require_once 'functions.php';

// verifie si l'utilisateur est connecte
function is_logged_in() {
    return isset($_SESSION['id_user']);
}

/**
 * force la connexion et conserve les paramètres d'url (get)
 * @param string $redirect_path chemin relatif depuis le dossier auth/ vers la page cible
 */
function require_login($redirect_path) {
    if (!isset($_SESSION['id_user'])) {
        $full_redirect = add_current_args($redirect_path);
        
        // redirection vers login avec le paramètre redirect encodé
        header("Location: ../auth/login.php?redirect=" . urlencode($full_redirect));
        exit;
    }
}

/**
 * Sécurise une page en fonction du rôle de l'utilisateur connecté.
 * Respecte la hiérarchie : admin > gestionnaire > client
 * * @param string $required_role Le rôle minimal requis ('admin', 'gestionnaire', 'client')
 * @param string $redirect_path Chemin de redirection en cas d'échec (par défaut l'index)
 */
function require_role($required_role, $redirect_path = "../index.php") {
    // 1. On vérifie d'abord si l'utilisateur est connecté
    if (!isset($_SESSION['id_user']) || !isset($_SESSION['role'])) {
        $_SESSION['error'] = "Veuillez vous connecter pour accéder à cette page.";
        header("Location: ../auth/login.php?redirect=" . urlencode(add_current_args($redirect_path)));
        exit;
    }

    // 2. Définition du barème numérique de la hiérarchie
    $roles_hierarchy = [
        'admin' => 3,
        'gestionnaire' => 2,
        'client' => 1
    ];

    $user_role = $_SESSION['role'];

    // 3. Récupération des poids (sécurité avec l'opérateur null coalescent au cas où)
    $user_weight     = $roles_hierarchy[$user_role] ?? 0;
    $required_weight = $roles_hierarchy[$required_role] ?? 0;

    // 4. Comparaison : si le poids de l'utilisateur est insuffisant, on bloque !
    if ($user_weight < $required_weight) {
        $_SESSION['error'] = "Accès refusé : Vos privilèges actuels (" . h($user_role) . ") ne vous permettent pas d'accéder à cette ressource.";
        header("Location: " . $redirect_path);
        exit;
    }
}

function sanitize_redirect_url($redirect_url) {
    // SÉCURITÉ : Protection contre les redirections vers des sites 
    // On s'assure que la redirection est interne (ne commence pas par http, https ou //)
    if (preg_match('/^https?:\/\/|^\/\//i', $redirect_url)) {
        $redirect_url = '../index.php';
    }
    return $redirect_url;
}

// 4. Génère un lien de redirection vers login proprement
function get_auth_link($target) {
    if (is_logged_in()) {
        return $target;
    }
    return "../auth/login.php?redirect=" . urlencode($target);
}
