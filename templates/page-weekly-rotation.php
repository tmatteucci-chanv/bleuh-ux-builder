<?php
/**
 * Template Name: Weekly Rotations (Rotations Hebdomadaires)
 * Description: Affiche la liste des lots disponibles (Stock) en les croisant avec les infos produits.
 * Gère l'affichage par "Collection" avec des bandeaux spécifiques.
 */

defined('ABSPATH') || exit;
get_header();

?>

<?php
// ==============================================================================
// 1. CONFIGURATION ET FONCTIONS UTILITAIRES
// ==============================================================================

/**
 * Fonction de tri personnalisé pour les collections.
 * C'EST ICI QU'ON DÉFINIT L'ORDRE D'AFFICHAGE DES BANDEAUX.
 */
function bleuh_custom_sort_by_collection($a, $b)
{
    $custom_order = [
        'bleuh',
        'blanh',
        'bleuh-light', // On garde le tiret ici comme référence
        'skyh',
        'grindh',
        'blakh',
        'goldh'
    ];

    // On remplace les espaces par des tirets pour la comparaison
    // Ainsi "bleuh light" devient "bleuh-light" et matche la liste
    $col_a = str_replace(' ', '-', strtolower(trim($a->collection)));
    $col_b = str_replace(' ', '-', strtolower(trim($b->collection)));

    $position_a = array_search($col_a, $custom_order);
    $position_b = array_search($col_b, $custom_order);

    if ($position_a !== false && $position_b !== false) {
        return $position_a - $position_b;
    }
    if ($position_a !== false)
        return -1;
    if ($position_b !== false)
        return 1;

    return strcmp($col_a, $col_b);
}

/**
 * Fonction de nettoyage et formatage des chaînes de caractères (FR).
 * Gère les espaces insécables, la casse (Titre) et les acronymes (CBD, THC...).
 */
if (!function_exists('bleuh_format_fr_string')) {
    function bleuh_format_fr_string($str)
    {
        $str = (string)$str;
        // Nettoyage espaces insécables et multiples
        $str = str_replace(["\xc2\xa0", "&nbsp;"], ' ', $str);
        $str = preg_replace('/\s+/u', ' ', $str);

        // Mise en minuscule puis format "Titre" (Première lettre majuscule)
        $str = mb_strtolower($str, 'UTF-8');
        $str = str_replace(['(', ')'], '', $str);
        $str = mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');

        // Correction des acronymes techniques
        $acronyms = ['Cbd' => 'CBD', 'Thc' => 'THC', 'Gsc' => 'GSC', 'H4' => 'H4', 'Sqdc' => 'SQDC', 'Ocs' => 'OCS'];
        foreach ($acronyms as $bad => $good) {
            $str = preg_replace('/\b' . $bad . '\b/u', $good, $str);
        }

        // Remise en minuscule des articles/prépositions s'ils ne sont pas en début de phrase
        $stops = ['À', 'Au', 'Aux', 'De', 'Des', 'Du', 'En', 'Et', 'La', 'Le', 'Les', 'Par', 'Pour', 'Sur'];
        foreach ($stops as $word) {
            $lower = mb_strtolower($word, 'UTF-8');
            $str = preg_replace('/(?<=\s)' . $word . '\b/u', $lower, $str);
        }

        return trim($str);
    }
}
?>

<?php do_action('flatsome_before_page'); ?>

<div class="section-content relative">

    <div class="row row-main">
        <section class="section" id="weekly-section-head">
            <div class="section-bg fill"></div>
            <div class="section-content relative">
                <h1 style="text-align: center;"><?php echo get_the_title(); ?></h1>
            </div>
            <div>
                <?php echo get_the_content(); ?>
            </div>
        </section>
    </div>

    <?php
// Gestion de l'affichage conditionnel (Québec vs Ontario)
$fr_active = "";
$en_active = " active";
$is_fr = defined("BLEUH_WEEKLY_ROTATION_FR");

if ($is_fr) {
    $fr_active = " active";
    $en_active = "";
}
?>

    <ul class="nav nav-line-bottom nav-uppercase nav-size-xlarge nav-center" role="tablist">
        <li id="tab-quÉbec" class="tab <?php echo $fr_active; ?> has-icon" role="presentation">
            <a href="/lots-disponibles/" role="tab" aria-selected="true" aria-controls="tab_quÉbec"><span>QUÉBEC</span></a>
        </li>
        <li id="tab-ontario" class="tab <?php echo $en_active; ?> has-icon" role="presentation">
            <a href="/en/weekly-rotations/" tabindex="-1" role="tab" aria-selected="false" aria-controls="tab_ontario"><span>ONTARIO</span></a>
        </li>
    </ul>

    <div class="row" style="display: block;">
        <div class="col-lg-4" id="clipboard-container">
            <?php
// ==============================================================================
// 2. RÉCUPÉRATION DES DONNÉES (REQUÊTES SQL)
// ==============================================================================
global $wpdb;
$fr_vars = null;

// REQUÊTE A : Version Ontario (Anglais)
if (!$is_fr) {
    $query = "SELECT 
                            p.GTIN, 
                            p.collection, 
                            p.name, 
                            p.format format_en, 
                            p.weight, 
                            p.blend_en AS blend,
                            l.variety_name latest_var_name,
                            l.THC AS max_THC, 
                            l.CBD AS max_CBD, 
                            l.wrap_date AS latest_wrap_date, 
                            l.lot AS latest_lot
                          FROM `{$wpdb->prefix}bleuh_lots` l 
                          LEFT JOIN `{$wpdb->prefix}bleuh_vars` v ON TRIM(UPPER(v.lot)) = TRIM(UPPER(l.lot))
                          LEFT JOIN `{$wpdb->prefix}bleuh_products` p ON v.SQDC_SKU = p.GTIN
                          WHERE l.is_ontario = 'Y'
                          GROUP BY v.lot
                          ORDER BY p.collection, p.blend;";
}
// REQUÊTE B : Version Québec (Français)
else {
    $query = "SELECT 
                            p.GTIN, 
                            p.collection, 
                            p.name, 
                            p.format format_en, 
                            p.weight, 
                            p.blend,
                            l.variety_name latest_var_name,
                            l.THC AS max_THC, 
                            l.CBD AS max_CBD, 
                            l.wrap_date AS latest_wrap_date, 
                            l.lot AS latest_lot
                          FROM `{$wpdb->prefix}bleuh_vars` v 
                          LEFT JOIN `{$wpdb->prefix}bleuh_lots` l ON TRIM(UPPER(v.lot)) = TRIM(UPPER(l.lot))
                          LEFT JOIN `{$wpdb->prefix}bleuh_products` p ON v.SQDC_SKU = p.GTIN
                          LEFT JOIN `{$wpdb->prefix}bleuh_store_products` sp ON p.GTIN = sp.GTIN
                          WHERE sp.store_number = 'WEB'
                          AND v.store_number = 'WEB'
                          GROUP BY v.lot
                          ORDER BY p.collection, p.blend;";
}

// Exécution de la requête
$results = $wpdb->get_results($query);

// Application du tri personnalisé (défini plus haut)
usort($results, 'bleuh_custom_sort_by_collection');

$section_ending = "</tbody></table></div></div></div></div></div></section>";

// --- Récupération des données WordPress (Liens, THC, CBD ACF) ---
$args = [
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => 'publish',
];
$products_query = new WP_Query($args);
$prod_url = [];

if ($products_query->have_posts()) {
    while ($products_query->have_posts()) {
        $products_query->the_post();
        $product_id = get_the_ID();
        $gtin = trim(strtolower((string)get_field('gtin', $product_id)));
        // On stocke les URLs pour créer les liens plus tard
        $prod_url[$gtin] = get_permalink($product_id);
    }
    wp_reset_postdata();
}

// --- Récupération des URLs des "Variétés" (Featured Items) ---
$args_vars = [
    'post_type' => 'featured_item',
    'posts_per_page' => -1,
    'post_status' => 'publish',
];
$vars_query = new WP_Query($args_vars);
$lot_url = [];

if ($vars_query->have_posts()) {
    while ($vars_query->have_posts()) {
        $vars_query->the_post();
        $var_id = get_the_ID();
        $title = bleuh_normalize_title(get_the_title($var_id));
        $lot_url[$title] = get_permalink($var_id);
    }
    wp_reset_postdata();
}

// ==============================================================================
// 3. LOGIQUE DE FILTRAGE (DATE ET DOUBLONS)
// ==============================================================================
// On ne garde que le lot le plus récent pour chaque GTIN, s'il a moins de X mois.

$filtered_results = [];
$omitted_results = []; // Pour la section admin en bas de page
$threshold = new DateTime();
$threshold->modify('-' . ON_LOTS_THRESHOLD_MONTHS . ' months');

foreach ($results as $row) {
    if (!empty($row->latest_wrap_date)) {
        $wrap_date = DateTime::createFromFormat('Y-m-d', $row->latest_wrap_date);

        // Si le lot est assez récent
        if ($wrap_date && $wrap_date >= $threshold) {
            // Si on a déjà un lot pour ce produit (GTIN)
            if (isset($filtered_results[$row->GTIN])) {
                $previous_result = $filtered_results[$row->GTIN];
                $new_result = $row;

                // Comparaison des dates : on garde le plus récent
                $prev_date = DateTime::createFromFormat('Y-m-d', $previous_result->latest_wrap_date);
                $new_date = DateTime::createFromFormat('Y-m-d', $new_result->latest_wrap_date);

                if ($new_date >= $prev_date) {
                    $filtered_results[$row->GTIN] = $new_result; // Le nouveau gagne
                }
                else {
                    $omitted_results[] = $new_result; // L'ancien reste, le nouveau est omis
                }
            }
            else {
                // Premier lot rencontré pour ce GTIN
                $filtered_results[$row->GTIN] = $row;
            }
        }
        else {
            // Trop vieux
            $omitted_results[] = $row;
        }
    }
    else {
        // Pas de date
        $omitted_results[] = $row;
    }
}

// ==============================================================================
// 4. BOUCLE D'AFFICHAGE PRINCIPALE
// ==============================================================================

if (!empty($filtered_results)) {

    // 1. On transforme le tableau associatif (indexé par GTIN) en liste simple (0, 1, 2...)
    // C'est l'étape cruciale pour que le tri PHP soit stable et efficace.
    $final_list = array_values($filtered_results);

    // 2. On applique le tri personnalisé sur cette liste propre
    usort($final_list, 'bleuh_custom_sort_by_collection');

    $previous_collection = "";

    // 3. On boucle sur la nouvelle liste $final_list
    foreach ($final_list as $row) {

        // Sécurité : Si pas de format, on ignore (donnée incomplète)
        if (empty($row->format_en)) {
            continue;
        }

        // --- DÉTECTION DE NOUVELLE SECTION (BANDEAU) ---
        // On compare la collection actuelle avec la précédente (insensible à la casse)

        // 1. Fermeture du tableau précédent si ce n'est pas le premier
        if (strtolower($previous_collection) != strtolower($row->collection) && $previous_collection != "") {
            echo $section_ending;
        }

        // 2. Création du nouveau bandeau si la collection change
        if (strtolower($previous_collection) != strtolower($row->collection)) {

            // Configuration des couleurs et images par collection
            switch (strtolower($row->collection)) {
                case "bleuh":
                    $img_url = '/wp-content/uploads/2023/04/bleuh-2.png';
                    $bg_color = 'rgb(0, 33, 79)';
                    break;
                case "blanh":
                    $img_url = '/wp-content/uploads/2023/04/blanh.png';
                    $bg_color = 'rgb(255,255,255)';
                    break;
                case "bleuh-light":
                case "bleuh light": // Gère les variantes avec espace
                    $img_url = '/wp-content/uploads/2023/04/bleuh-light-1.png';
                    $bg_color = 'rgb(0, 113, 206)';
                    break;
                case "skyh": // Nouveau bandeau Skyh
                    $img_url = '/wp-content/uploads/2026/02/skyh.png';
                    $bg_color = '#0137b6';
                    break;
                case "blakh":
                    $img_url = '/wp-content/uploads/2023/04/blakh.png';
                    $bg_color = 'rgb(0, 0, 0)';
                    break;
                case "grindh":
                    $img_url = '/wp-content/uploads/2023/11/grindh.png';
                    $bg_color = 'rgb(0, 33, 79)';
                    break;
                case "goldh":
                    $img_url = '/wp-content/uploads/2023/11/goldh-2.png';
                    $bg_color = 'rgb(0, 0, 0)';
                    break;
                default:
                    // Fallback au cas où une collection inconnue apparaît
                    $img_url = '/wp-content/uploads/2023/04/bleuh-2.png';
                    $bg_color = 'rgb(0, 33, 79)';
                    break;
            }
?>
                        
                        <section class="section">
                            <div class="section-bg fill"></div>
                            <div class="section-content relative bleuh-bottom-margin">

                                <div class="rotation-banner-row row row-collapse row-full-width align-center" style="margin-bottom:10px;">
                                    <div class="col small-12 large-12">
                                        <div class="col-inner text-center" style="background-color: <?php echo $bg_color; ?>;border-radius: 82px;">
                                            <div class="img-banner-rotation img has-hover x md-x lg-x y md-y lg-y">
                                                <div class="img-inner dark">
                                                    <img width="318" height="234" src="<?php echo $img_url; ?>" class="attachment-original size-original" alt="<?php echo $row->collection; ?>" >
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col small-12 large-12">
                                        <div class="col-inner">
                                            <div class="scrollable-table-container" id="table-scroll">
                                                <table class="rotation-table-on">
                                                    <thead>
                                                        <tr>
                                                            <?php if (!$is_fr) { // EN-TÊTES ANGLAIS ?>
                                                                <th>Products</th>
                                                                <th>Species</th>
                                                                <th>Category</th>
                                                                <th>Format</th>
                                                                <th>Strain</th>
                                                                <th>THC</th>
                                                                <th>CBD</th>
                                                                <th>Packaged</th>
                                                                <th>Batch</th>
                                                            <?php
            }
            else { // EN-TÊTES FRANÇAIS ?>
                                                                <th>Produit</th>
                                                                <th>Espèce</th>
                                                                <th>Catégorie</th>
                                                                <th>Format</th>
                                                                <th>Variété</th>
                                                                <th>THC</th>
                                                                <th>CBD</th>
                                                                <th>Emballage</th>
                                                                <th>Lot</th>
                                                            <?php
            }?>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                        <?php
        } // Fin du if "Nouvelle Section"

        // --- PRÉPARATION DES DONNÉES DE LA LIGNE ---

        // 1. Poids
        $weight = strtolower((string)$row->weight);
        if (strpos($weight, 'g') !== false)
            $weight = str_replace('g', ' g', $weight);
        if (strpos($weight, 'x') !== false)
            $weight = str_replace('x', ' x ', $weight);

        // 2. Date
        $wrap_date = DateTime::createFromFormat('Y-m-d', $row->latest_wrap_date);
        $wrap_date = $wrap_date ? $wrap_date->format('m-Y') : '...';

        // 3. Noms
        $clean_prod_name = bleuh_format_fr_string($row->name);
        $clean_var_name = bleuh_format_fr_string($row->latest_var_name);

        // 4. Construction des liens HTML
        $url = $prod_url[trim(strtolower($row->GTIN))] ?? '';
        if (!empty($url)) {
            $row_name_display = "<a href='" . esc_url($url) . "'>" . esc_html($clean_prod_name) . "</a>";
        }
        else {
            $row_name_display = esc_html($clean_prod_name);
        }

        $this_lot_url = $lot_url[bleuh_normalize_title($row->latest_var_name)] ?? '';
        if (!empty($this_lot_url)) {
            $row_v_display = "<a href='" . esc_url($this_lot_url) . "'>" . esc_html($clean_var_name) . "</a>";
        }
        else {
            $row_v_display = esc_html($clean_var_name);
        }

        // 5. Formatage THC / CBD
        $thc = mb_strtolower(esc_html($row->max_THC), 'UTF-8');
        if (strpos($thc, '%') === false)
            $thc .= '%';
        $thc = str_replace('thc', '', $thc);

        $cbd = mb_strtolower(esc_html($row->max_CBD), 'UTF-8');
        if (strpos($cbd, '%') === false)
            $cbd .= '%';
        $cbd = str_replace('cbd', '', $cbd);

        // --- RENDU DE LA LIGNE HTML ---
        echo "<tr data-collection='" . $row->collection . "' data-gtin='" . trim(strtolower($row->GTIN)) . "'>
                            <td style='text-transform: none !important;'>" . $row_name_display . "</td>
                            <td>" . $row->blend . "</td>
                            <td>" . mb_strtolower(esc_html($row->format_en), 'UTF-8') . "</td>
                            <td style='text-transform: lowercase;'>" . esc_html($weight) . "</td>
                            <td style='text-transform: none !important;'>" . $row_v_display . "</td>
                            <td>" . $thc . "</td>
                            <td>" . $cbd . "</td>
                            <td>" . mb_strtolower(esc_html($wrap_date), 'UTF-8') . "</td>
                            <td>" . strtoupper(esc_html($row->latest_lot)) . "</td>
                          </tr>";

        $previous_collection = $row->collection;
    }
    echo $section_ending; // Fermeture de la dernière section
}

// ==============================================================================
// 5. SECTION ADMIN / DÉBOGAGE
// ==============================================================================
if (current_user_can('administrator')) {
    $admin_title = $is_fr ? 'Bouton Admin :' : 'Admin button:';
    $btn_text = $is_fr ? 'Copier le tableau' : 'Copy Table to clipboard';
?>
                <h2><?php echo $admin_title; ?></h2>
                <button id="btn-copy-html-to-clip" class="button primary is-large retrouver"><?php echo $btn_text; ?></button>
                
                <?php
    // Affichage des résultats omis (trop vieux ou supplantés par plus récent)
    $omitted_title = $is_fr ? "Résultats omis (derniers" : "Omitted Results (past";
    $omitted_month = $is_fr ? "mois ;" : "months;";
?>
                <h2><?php echo $omitted_title . ' ' . ON_LOTS_THRESHOLD_MONTHS . ' ' . $omitted_month . ' ' . $threshold->format('Y-m-d'); ?>):</h2>
                <?php
    foreach ($omitted_results as $result) {
        $row_name = mb_strtolower(esc_html($result->name), 'UTF-8');
        $prefix = $is_fr ? "Omis :" : "Omitted:";
        echo "<p>" . $prefix . " " . $row_name . " (" . $result->weight . ") - " . $result->latest_wrap_date . " - GTIN: " . $result->GTIN . ", LOT: " . $result->latest_lot . " </p>";
    }
}
?>

            <p id="buy-online-on-button">
                <?php
$buy_url = $is_fr ? 'https://www.sqdc.ca/fr-CA/Rechercher?keywords=bleuh' : 'https://ocs.ca/collections/bleuh';
$buy_text = $is_fr ? 'Commander en ligne' : 'Order Online';
?>
                <a href="<?php echo $buy_url; ?>" target="_blank" class="button primary is-large retrouver" rel="noopener">
                    <span><?php echo $buy_text; ?></span>
                </a>
            </p>
        </div>
    </div>
</div>

<?php do_action('flatsome_after_page'); ?>

<?php get_footer(); ?>