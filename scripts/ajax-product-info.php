<?php

// Info for the product page

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

function bleuh_ajax_inventory() {
    $SKU = $_REQUEST["SKU"];
    $lang = $_REQUEST["lang"];

    if ($lang == "fr") {
        $lang = "fr-CA";
    } else {
        $lang = "en-CA";
    }

    $url = 'https://www.sqdc.ca/api/storeinventory/storesinventory';
    $data = json_encode(array(
        'Sku' => $SKU,
        'Page' => 0,
        'Pagesize' => 999
    ));

    $headers = array(
        'Accept-Language: '.$lang,
        'x-requested-with: XMLHttpRequest',
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    );

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // accelerated query params:
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);

    if ($e = curl_error($ch)) {
        echo "Error: " . $e;
        bleuh_log('SQDC API fetch failed:'. $e);
    } else {
        echo $response;
    }

    curl_close($ch);

    wp_die();
}

add_action('wp_ajax_bleuh_ajax_inventory', 'bleuh_ajax_inventory'); // For logged in users
add_action('wp_ajax_nopriv_bleuh_ajax_inventory', 'bleuh_ajax_inventory'); // For non-logged in users

function bleuh_info_enqueue_ajax_script() {
    $SKU = [];
    if (is_single() && get_post_type() == 'product') {
        $product_id = get_the_ID();
        $product = wc_get_product($product_id); // Get the WC_Product object
        $is_ontario = false;
        if (!empty(get_field('is_ontario', $product_id))) $is_ontario = true;

        if ($product && $product->is_type('external')) {
            // Get the product URL
            $product_url = $product->get_product_url();
            $product_url = explode('/', $product_url);

            if (empty($SKU)) {
                $SKU = get_field('gtin', $product_id);
            }
            // get SKU from product URL
            if (empty($SKU)) {
                $SKU = [end($product_url)];
            }
            $SKU = is_array($SKU) ? $SKU : array($SKU);
            $varieties = bleuh_varieties(false, $SKU);
            $varieties = json_encode($varieties);

            $deliveries = [];
            $SKUArray = is_array($SKU) ? $SKU : array($SKU);
            if (strpos($_SERVER["REQUEST_URI"], "debug") !== false) {
                $deliveries = bleuh_deliveries(false, $SKUArray);
            }
            $deliveries = json_encode($deliveries);
            $overrides = bleuh_get_var_overrides(false, $SKUArray);

            $data = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'plugin_url' => plugins_url('../', __FILE__),
                "lang"=> ICL_LANGUAGE_CODE,
                "SKU" => $SKU,
                'is_ontario' => ($is_ontario) ? 'Y' : 'N',
                'can_manage_tags' => (current_user_can('administrator') || current_user_can('editor') || current_user_can('shop_manager')),
                "varieties" => $varieties,
                "overrides" => json_encode($overrides),
                "deliveries" => $deliveries,
                "varieties_update" => get_option('bleuh_vars_import'),
                "web_inventory_update" => get_option('bleuh_web_vars_import'),
                "produits_disponibles" => icl_t('bleuh', 'produits-disponibles', 'produits disponibles'),
                'caption_units_available' => icl_t('bleuh', 'caption_units_available', "UNITÉS DISPONIBLES"),
                "ouverture" => icl_t('bleuh', 'ouverture', 'Ouverture'),
                "stock_succursales" => icl_t('bleuh', 'stock-succursales', 'En stock dans ? succursales'),
                "dispo_header_glob" => icl_t('bleuh', 'dispo_header_glob', 'Disponibilité'),
                "web_header" => icl_t('bleuh', 'web_header', 'Disponibilité en ligne'),
                "postal_code" => icl_t('bleuh', 'postal_code', 'Code postal'),
                "locate_me" => icl_t('bleuh', 'locate_me', 'Localisez-moi'),
                "stock" => icl_t('bleuh', 'stock', 'Stock'),
                "variety" => icl_t('bleuh', 'variety', 'Variété'),
                "no_stock" => icl_t('bleuh', 'no-stock', 'Rupture de stock'),
                "var_new_warning" => icl_t('bleuh', 'var_new_warning', "L'association des variétés est effectuée manuellement avec plus de 85 % de précision, mais des erreurs peuvent survenir en raison de la rotation en magasin. Nous vous invitons à appeler en succursale pour confirmer."),
                'caption_online' => icl_t('bleuh', 'caption_online', "En ligne"),
                'caption_varieties_in_stock' => icl_t('bleuh', 'caption_varieties_in_stock', "Variété en stock"),
                'caption_varieties_to_come' => icl_t('bleuh', 'caption_varieties_to_come', "Variété à venir"),
                'caption_on_product_blurb' => icl_t('bleuh', 'on_product_blurb', "Bien que nous mettions tout en œuvre pour assurer l’exactitude des disponibilités en magasin, certaines informations peuvent varier. Nous vous recommandons de contacter le commerce avant de vous déplacer.")
            ];

            if ($is_ontario) {
                $data['ontario_stores'] = bleuh_ontario_stores($SKU);
            }

            wp_enqueue_script('bleuh_info_ajax', plugin_dir_url(__FILE__) . '../js/ajax.js?cache='.md5(time()), array('jquery', 'bleuh_main_js', 'bleuh_info_cookies'), BLEUH_CURRENT_VERSION, true);
            wp_localize_script('bleuh_info_ajax', 'ajax_bleuh_info', $data);
        }
    }

    wp_enqueue_script('bleuh_info_cookies', plugin_dir_url(__FILE__) . '../js/js.cookie.min.js', array('jquery'), BLEUH_CURRENT_VERSION, true);
}

function bleuh_register_translation_strings() {
    $current_user = wp_get_current_user();
    if (in_array('administrator', $current_user->roles)) {
        if (function_exists('icl_register_string')) {
            icl_register_string('bleuh', 'produits-disponibles', 'produits disponibles', false, "fr");
            icl_register_string('bleuh', 'ouverture', 'Ouverture', false, "fr");
            icl_register_string('bleuh', 'stock-succursales', 'En stock dans ? succursales', false, "fr");
            icl_register_string('bleuh', 'postal_code', 'Code postal', false, "fr");
            icl_register_string('bleuh', 'locate_me', 'Localisez-moi', false, "fr");
            icl_register_string('bleuh', 'dispo_header_glob', 'Disponibilité', false, "fr");
            icl_register_string('bleuh', 'web_header', 'Disponibilité en ligne', false, "fr");
            icl_register_string('bleuh', 'stock', 'Stock', false, "fr");
            icl_register_string('bleuh', 'variety', 'Variété', false, "fr");
            icl_register_string('bleuh', 'no-stock', 'Rupture de stock', false, "fr");
            icl_register_string('bleuh', 'var_new_warning', "L'association des variétés est effectuée manuellement avec plus de 85 % de précision, mais des erreurs peuvent survenir en raison de la rotation en magasin. Nous vous invitons à appeler en succursale pour confirmer.", false, "fr");
        }
    }
}

add_action('init', 'bleuh_register_translation_strings');
add_action('wp_enqueue_scripts', 'bleuh_info_enqueue_ajax_script', 99);
