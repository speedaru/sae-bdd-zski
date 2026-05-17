<?php
/**
 * bibliotheque de fonctions reutilisables
 */

/**
 * Nettoie une chaîne pour éviter les failles XSS
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Affiche une alerte Bootstrap stylisée
 * @param string $message Le texte à afficher
 * @param string $type success, danger, warning, info
 */
function alert($message, $type = 'info') {
    if (!$message) return '';
    $icon = [
        'success' => 'check-circle',
        'danger'  => 'exclamation-triangle',
        'warning' => 'exclamation-circle',
        'info'    => 'info-circle'
    ];
    return "
    <div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
        <i class='fas fa-{$icon[$type]} me-2'></i> {$message}
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>";
}

/**
 * Formate une date SQL (YYYY-MM-DD) vers le format français (DD/MM/YYYY)
 */
function date_fr($date_sql) {
    return date('d/m/Y', strtotime($date_sql));
}

function folder_in_curr_path($folder_name) {
    return strpos($_SERVER['PHP_SELF'], '/' . $folder_name . '/') !== false;
}

function folders_in_curr_path(array $folder_names) {
    $curr_path = $_SERVER['PHP_SELF'];
    echo $curr_path;
    foreach ($folder_names as $name) {
        $name_present = strpos($curr_path, '/' . $name . '/') !== false;
        if ($name_present) { // renvoie vrai si dossier present, sinon continue de regarder les autres
            return true;
        }
    }
    return false;
}

function get_relative_base_url() {
    $curr_path = $_SERVER['PHP_SELF'];
    $depth = substr_count($curr_path, "/");
    if ($depth <= 1) { // php root
        return "./";
    }
    
    $rel_base = "";
    while ($depth-- > 1) {
        $rel_base = $rel_base . "../";
    }
    
    return $rel_base;
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

/**
 * same as add_current_args() but uses current page by default
 */
function add_current_url_with_args() {
    return add_current_args($_SERVER['PHP_SELF']);
}

/**
 * Retourne le nombre de chambres actuellement sélectionnées dans le panier
 */
function get_panier_count() {
    if (isset($_SESSION['panier']) && is_array($_SESSION['panier'])) {
        return count($_SESSION['panier']);
    }
    return 0;
}

/**
 * Calcule l'âge exact en années à partir d'une date de naissance au format (YYYY-MM-DD)
 * @param string $date_naissance
 * @return int l'âge exact
 */
function calculer_age($date_naissance) {
    if (empty($date_naissance)) return 0;
    $today = new DateTime();
    $birthdate = new DateTime($date_naissance);
    $diff = $today->diff($birthdate);
    return $diff->y;
}

/**
 * Applique la tarification de la station selon les critères d'âge :
 * - Bébé (< 2 ans) : Gratuit (0€)
 * - Enfant (entre 2 ans et 11 ans révolus) : Réduction de -20%
 * - Adulte (>= 12 ans) : Plein tarif de base
 * @param int $prix_base Tarif brut d'une formule
 * @param string $date_naissance Date de naissance du skieur
 * @return int Tarif final calculé
 */
function calculer_prix_indiv($prix_base, $date_naissance) {
    $age = calculer_age($date_naissance);
    
    if ($age < 2) {
        return 0;
    } elseif ($age < 12) {
        // Enfant : réduction de 20%
        return intval(round($prix_base * 0.8));
    }
    
    // Adulte : prix normal
    return intval($prix_base);
}