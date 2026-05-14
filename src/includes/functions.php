<?php
/**
 * bibliotheque de fonctions reutilisables
 */

// verifie si l'utilisateur est connecte
function is_logged_in() {
    return isset($_SESSION['id_user']);
}

/**
 * force la connexion et conserve les paramètres d'url (get)
 * @param string $path_from_auth chemin relatif depuis le dossier auth/ vers la page cible
 */
function require_login($path_from_auth) {
    if (!isset($_SESSION['id_user'])) {
        // on récupère les arguments actuels (ex: id=105)
        $params = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
        
        // on construit l'url de retour complète
        $full_redirect = $path_from_auth . $params;
        
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
