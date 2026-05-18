<?php
require_once 'functions.php';

// verifie si l'utilisateur est connecte
function is_logged_in() {
    return isset($_SESSION['id_user']);
}

/**
 * force la connexion et conserve les parametres d'url (get)
 * @param string $redirect_path chemin relatif depuis le dossier auth/ vers la page cible
 */
function require_login($redirect_path) {
    if (!isset($_SESSION['id_user'])) {
        $full_redirect = add_current_args($redirect_path);
        
        // redirection vers login avec le parametre redirect encode
        header("Location: ../auth/login.php?redirect=" . urlencode($full_redirect));
        exit;
    }
}

/**
 * securise une page en fonction du rôle de l'utilisateur connecte.
 * respecte la hierarchie : admin > gestionnaire > client
 * * @param string $required_role le role minimal requis ('admin', 'gestionnaire', 'client')
 * @param string $redirect_path chemin de redirection en cas d'echec (par defaut l'index)
 */
function require_role($required_role, $redirect_path = "../index.php") {
    // on verifie d'abord si l'utilisateur est connecte
    if (!isset($_SESSION['id_user']) || !isset($_SESSION['role'])) {
        $_SESSION['error'] = "Veuillez vous connecter pour acceder à cette page.";
        header("Location: ../auth/login.php?redirect=" . urlencode(add_current_args($redirect_path)));
        exit;
    }

    // hierarchie des roles
    $roles_hierarchy = [
        'admin' => 3,
        'gestionnaire' => 2,
        'client' => 1
    ];

    $user_role = $_SESSION['role'];

    $user_weight = $roles_hierarchy[$user_role] ?? 0;
    $required_weight = $roles_hierarchy[$required_role] ?? 0;

    // bloquer utilisateur si role pas assez puissant
    if ($user_weight < $required_weight) {
        $_SESSION['error'] = "Acces refuse : Vos privileges actuels (" . h($user_role) . ") ne vous permettent pas d'acceder à cette ressource.";
        // echo "<h1>" . $redirect_path . "</h1>";
        header("Location: ../index.php");
        exit;
    }
}

function sanitize_redirect_url($redirect_url) {
    // protection contre les redirections vers des sites 
    // on s'assure que la redirection est interne, ne commence pas par http, https ou //
    if (preg_match('/^https?:\/\/|^\/\//i', $redirect_url)) {
        $redirect_url = '../index.php';
    }
    return $redirect_url;
}

// genere un lien de redirection vers login proprement
function get_auth_link($target) {
    if (is_logged_in()) {
        return $target;
    }
    return "../auth/login.php?redirect=" . urlencode($target);
}
