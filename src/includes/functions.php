<?php
/**
 * bibliotheque de fonctions reutilisables
 */

// verifie si l'utilisateur est connecte
function is_logged_in() {
    return isset($_SESSION['id_user']);
}

/**
 * adds current arguments to url and returns formated url with arguments
 * useful to keep current arguments when redirecting to a page
 */
function add_current_args($url) {
    // on récupère les arguments actuels (ex: id=105)
    $params = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';

    // on construit l'url complet
    $full_url = $url . $params;
    return $full_url;
}

function sanitize_redirect_url($redirect_url) {
    // SÉCURITÉ : Protection contre les redirections vers des sites 
    // On s'assure que la redirection est interne (ne commence pas par http, https ou //)
    if (preg_match('/^https?:\/\/|^\/\//i', $redirect_url)) {
        $redirect_url = '../index.php';
    }
    return $redirect_url;
}

/**
 * force la connexion et conserve les paramètres d'url (get)
 * @param string $path_from_auth chemin relatif depuis le dossier auth/ vers la page cible
 */
function require_login($path_from_auth) {
    if (!isset($_SESSION['id_user'])) {
        $full_redirect = add_current_args($path_from_auth);
        
        // redirection vers login avec le paramètre redirect encodé
        header("Location: ../auth/login.php?redirect=" . urlencode($full_redirect));
        exit;
    }
}

// 3. Vérifie les rôles (ex: admin, gestionnaire)
function require_role($roles_autorises = []) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles_autorises)) {
        header("Location: /index.php"); // Ou une page d'erreur 403
        exit;
    }
}

// 4. Génère un lien de redirection vers login proprement
function get_auth_link($target) {
    if (is_logged_in()) return $target;
    return "/auth/login.php?redirect=" . urlencode($target);
}

// 5. Affiche une alerte Bootstrap uniformisée
function display_alert($message, $type = 'danger') {
    if (!$message) return '';
    $icon = ($type === 'success') ? 'fa-check-circle' : 'fa-exclamation-triangle';
    return "<div class='alert alert-{$type} shadow-sm'><i class='fas {$icon} me-2'></i>" . htmlspecialchars($message) . "</div>";
}
