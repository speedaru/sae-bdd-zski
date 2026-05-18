<?php

/**
 * nettoie une chaîie pour eviter les failles xss
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * affiche une alerte stylisee
 * @param string $message le texte à afficher
 * @param string $type success, danger, warning, info
 */
function alert($message, $type = 'info') {
    if (!$message) return '';
    $icon = [
        'success' => 'check-circle',
        'danger' => 'exclamation-triangle',
        'warning' => 'exclamation-circle',
        'info' => 'info-circle'
    ];
    return "
    <div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
        <i class='fas fa-{$icon[$type]} me-2'></i> {$message}
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>";
}

/**
 * formate une date sql (YYYY-MM-DD) vers le format francais (DD/MM/YYYY)
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
 * ajoute arguments de la page sur laquel on est pour redireger
 */
function add_current_args($url) {
    // on recupere les arguments actuels (ex: id=105)
    $params = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';

    // on construit l'url complet
    $full_url = $url . $params;
    return $full_url;
}

/**
 * pareil que add_current_args() mais utilise par defaut la page actuel
 */
function add_current_url_with_args() {
    return add_current_args($_SERVER['PHP_SELF']);
}

/**
 * renvoie le nombre de chambres actuellement selectionnees dans le panier
 */
function get_panier_count() {
    if (isset($_SESSION['panier']) && is_array($_SESSION['panier'])) {
        return count($_SESSION['panier']);
    }
    return 0;
}

/**
 * calcule l'age exact en annees à partir d'une date de naissance au format (yyyy-mm-dd)
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
 * applique la tarification de la station selon les criteres d'age :
 * bebe (< 2 ans) : gratuit (0€)
 * enfant (entre 2 ans et 11 ans revolus) : reduction de -20%
 * adulte (>= 12 ans) : plein tarif de base
 * @param int $prix_base tarif brut d'une formule
 * @param string $date_naissance date de naissance du skieur
 * @return int tarif final calcule
 */
function calculer_prix_indiv($prix_base, $date_naissance) {
    $age = calculer_age($date_naissance);
    
    if ($age < 2) {
        return 0;
    } elseif ($age < 12) {
        // enfant
        return intval(round($prix_base * 0.8));
    }
    
    // adulte
    return intval($prix_base);
}
