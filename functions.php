<?php
/*
Plugin Name: Bleuh Custom UX Builder Add-Ons
Plugin URI: https://bleuh.co
Description: Custom UX Fields addons
Version: 5.8.5
Author: Luc Laverdure
Author URI: https://luclaverdure.com
*/

define('BLEUH_CURRENT_VERSION', '5.8.5'); // This needs to be kept in sync with the version above.

/* Logs
--------------------------------------------------------------------------------------------------------------------
    * 2024-08-26: Adding like buttons for anonymous users
    * 3.0.0 - 2025-03-04: Ontario stores listings
    * 2.7.0 - 2025-02-21: New cron job to email missing content
    * 2.6.0 - 2025-01-31: Ontario products preparations
    * 2.5.0 - 2025-01-24: Refactoring of products
    * 2.4.5 - 2025-01-22: Optimizing logging for better performance and storage space
    * 2.4.4 - 2024-09-09: Working on new rotation widget for the homepage
    * 2.4.3 - 2024-08-29: Fixed deprecated X() and Y() point functions for MySQL 8.0
    * 2.4.2 - 2024-04-10: Fixed Web varieties order (reversed) [completed]
    * 2.4.1 - 2024-04-03: Refactored custom age gate functionality. (Homepage bug fix) [completed]
    * 2.4.0 - 2024-03-28: Added custom age gate functionality. [completed]
    * 2.3.1 - 2024-03-20: Added admin functionality to override automated inventory updates for strains. [completed]
    * 2024-03-20: fixed variety tags for store locator and product page easier display.
    * 2024-03-19: Added admin functionality to override automated inventory updates for strains.
    * 2024-03-14: Fixed variety tags order when there are more than 2 tags to display.

	P.S. Products can be ON &| QC, vars cannot be multi province.

********************************************************************************************************************/

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

//define ("BLEUH_DO_AGE_GATE", ((!isset($_COOKIE['bleuh_age_gate']) || $_COOKIE['bleuh_age_gate'] !== 'yes') && ($_SERVER['REQUEST_URI'] !== '/politique-de-confidentialite/' || $_SERVER['REQUEST_URI'] !== '/en/privacy-policy/') ));
define ("BLEUH_DO_AGE_GATE", true);

// include libs
require_once __DIR__ . '/vendor/autoload.php';

// configs
include_once( plugin_dir_path(__FILE__ ).'/config/configs.php');

// configs
include_once( plugin_dir_path(__FILE__ ).'/scripts/sftp.php');

// common functions
include_once( plugin_dir_path(__FILE__ ).'/scripts/common.php');

// translations
include_once( plugin_dir_path(__FILE__ ).'/scripts/translations.php');

// cron jobs
include_once( plugin_dir_path(__FILE__ ).'/scripts/cron.php');

// custom age gate
if (!bleuh_is_bot()) {
	if (BLEUH_DO_AGE_GATE)
		include_once( plugin_dir_path(__FILE__ ).'/scripts/bleuh-age-gate.php');
}

// slider functionality
include_once( plugin_dir_path(__FILE__ ).'/scripts/slider.php');

// rotation widget functionality
include_once( plugin_dir_path(__FILE__ ).'/scripts/rotation-widget.php');

// ajax product info
include_once( plugin_dir_path(__FILE__ ).'/scripts/ajax-product-info.php');

// ajax store info
include_once( plugin_dir_path(__FILE__ ).'/scripts/ajax-store-info.php');

// ajax favorites toggle
include_once( plugin_dir_path(__FILE__ ).'/scripts/ajax-favorite-toggle.php');

// ajax admin override for varieties
include_once( plugin_dir_path(__FILE__ ).'/scripts/ajax-admin-var-override.php');

// admin override for varieties
include_once( plugin_dir_path(__FILE__ ).'/scripts/variety-page-override.php');

// admin menus
include_once( plugin_dir_path(__FILE__ ).'/scripts/admin-menus.php');

// Product page refactoring
include_once( plugin_dir_path(__FILE__ ).'/scripts/product-description-override.php');

// title override for SEO: DISABLED FOR NOW.
include_once( plugin_dir_path(__FILE__ ).'/scripts/title-override.php');

// remove product gallery "flicker" on single product page
function bleuh_remove_woocommerce_product_gallery($template, $template_name, $template_path) {
    global $product;
    if ($template_name === 'single-product/product-image.php') {
        return plugin_dir_path(__FILE__) . '/templates/product-image.php';
    }
    return $template;
}
add_filter('woocommerce_locate_template', 'bleuh_remove_woocommerce_product_gallery', 10, 3);

function bleuh_main_enqueue_scripts() {
    // Enqueue your scripts and styles here.
    if (!wp_script_is('jquery', 'enqueued')) {
        wp_enqueue_script('jquery');
    }
    wp_register_script('jquery-match-height', plugin_dir_url(__FILE__) . 'js/jquery.matchHeight-min.js', array('jquery'), BLEUH_CURRENT_VERSION, false);
    wp_register_script('jquery-cookie', plugin_dir_url(__FILE__) . 'js/js.cookie.min.js', array('jquery'), BLEUH_CURRENT_VERSION, false) ;
	wp_register_script('jquery-ui-js', 'https://code.jquery.com/ui/1.13.2/jquery-ui.min.js', array('jquery'), BLEUH_CURRENT_VERSION, false);
	wp_register_script('lazy-js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.lazyload/1.9.1/jquery.lazyload.min.js', array('jquery'), BLEUH_CURRENT_VERSION, true);
	wp_enqueue_script('lazy-js');
	wp_enqueue_style('jquery-ui-css', "https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css");
    wp_enqueue_script('bleuh_main_js', plugin_dir_url(__FILE__) . 'js/main.js', array('jquery-ui-js', 'jquery', 'jquery-cookie', 'jquery-match-height', 'swiper'), BLEUH_CURRENT_VERSION, true);
    wp_enqueue_style('select2-styles', "https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css");
    wp_enqueue_script('select2', "https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js", array('jquery'), BLEUH_CURRENT_VERSION, false);

    $admin_varieties = [];
    $current_user = wp_get_current_user();
    if (user_can($current_user, 'administrator') || user_can($current_user, 'editor') || user_can($current_user, 'shop_manager')) {
        $admin_varieties = bleuh_master_varieties();
    }

    wp_localize_script('bleuh_main_js', 'bleuh_info_main', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'plugin_url' => plugins_url('/', __FILE__),
        'caption_lot' => icl_t('bleuh', 'var_store_locator_batch', 'Lot'),
        'caption_date_wrap' => icl_t('bleuh', 'var_store_locator_date_wrap', "Date d'emballage"),
        'caption_units_available' => icl_t('bleuh', 'caption_units_available', "UNITÉS DISPONIBLES"),
        'caption_units' => icl_t('bleuh', 'caption_units', "unités"),
        'caption_other_units' => icl_t('bleuh', 'caption_other_units', "Autres unités disponibles"),
        'caption_var_in_store' => icl_t('bleuh', 'caption_var_in_store', "en magasin"),
        'caption_var_in_backstore' => icl_t('bleuh', 'caption_var_in_backstore', "en entrepôt"),
        'can_manage_tags' => (current_user_can('administrator') || current_user_can('editor') || current_user_can('shop_manager')),
        'caption_varieties_in_stock' => icl_t('bleuh', 'caption_varieties_in_stock', "Variété en stock"),
        'caption_varieties_to_come' => icl_t('bleuh', 'caption_varieties_to_come', "Variété à venir"),
        'caption_online' => icl_t('bleuh', 'caption_online', "En ligne"),
        'view_cols' => $_SESSION["view-type"] ?? '',
        'admin_varieties' => $admin_varieties,

    ]);
}
add_action('wp_enqueue_scripts', 'bleuh_main_enqueue_scripts');

function bleuh_fav_page() {
	include_once( plugin_dir_path(__FILE__ ).'/templates/content-product.php');
	if (is_page('favorites') || is_page('favoris')) {
		include(plugin_dir_path(__FILE__) . '/templates/favorites-page.php');
		exit();
	}
}
add_filter('template_redirect', 'bleuh_fav_page', 10, 3);

function bleuh_store_locator() {
    if (is_page('map') || is_page('store-locator')) {
        include(plugin_dir_path(__FILE__) . '/templates/page-map.php');
        exit();
    }
}
add_filter('template_redirect', 'bleuh_store_locator', 10, 3);

function bleuh_weekly_rotation() {
	if (is_page('weekly-rotations')) {
		include(plugin_dir_path(__FILE__) . '/templates/page-weekly-rotation.php');
		exit();
	}
}
add_filter('template_redirect', 'bleuh_weekly_rotation', 10, 3);

function bleuh_weekly_rotation_fr() {
	if (is_page('lots-disponibles')) {
		define("BLEUH_WEEKLY_ROTATION_FR", true);
		include(plugin_dir_path(__FILE__) . '/templates/page-weekly-rotation.php');
		exit();
	}
}
add_filter('template_redirect', 'bleuh_weekly_rotation_fr', 10, 3);

function bleuh_vars_page() {
	if (is_page('varietes') || is_page('strains')) {
		include(plugin_dir_path(__FILE__) . '/templates/varieties-page.php');
		exit();
	}

    if (is_shop()) {
        include plugin_dir_path(__FILE__) . '/templates/products-page.php';
        exit();
    }
}
add_filter('template_redirect', 'bleuh_vars_page', 10, 3);

// add ontario class to product
add_filter('post_class', function ($classes, $class, $post_id) {
    // Check if it's a product post type
    if ('product' === get_post_type($post_id)) {
        // Access the product ID

        // $post_id is the product ID
        $product_id = $post_id;

        // get the is_ontario field
        $is_ontario_product = get_field('is_ontario', $product_id);
        if (empty($is_ontario_product)) {
            $is_ontario_product = false;
        } else {
            $is_ontario_product = true;
        }

        // Optionally, you can add conditional logic based on the product ID
        if ($is_ontario_product) {
            $classes[] = 'is-ontario-product';
        }
    }

    return $classes;
}, 10, 3);

// Lazy load images
add_filter( 'wp_get_attachment_image_attributes', function( $attr, $attachment, $size ) {
    $attr['loading'] = 'lazy';
    return $attr;
}, 10, 3 );

// remove old ajax nav of products
add_filter( 'nav_menu_link_attributes', function( $atts ) {
    if ( empty( $atts['href'] ) ) {
        return $atts;
    }

    $parts = wp_parse_url( $atts['href'] );

    // parse query if present
    if ( isset( $parts['query'] ) ) {
        parse_str( $parts['query'], $query );
        unset( $query['filter_province'], $query['yith_wcan'] );

        // rebuild the query string excluding those parameters
        $parts['query'] = http_build_query( $query );
    }

    // rebuild full url
    $new  = isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '';
    $new .= $parts['host'] ?? '';
    if ( isset( $parts['path'] ) ) {
        $new .= $parts['path'];
    }
    if ( ! empty( $parts['query'] ) ) {
        $new .= '?' . $parts['query'];
    }
    if ( isset( $parts['fragment'] ) ) {
        $new .= '#' . $parts['fragment'];
    }

    $atts['href'] = $new;
    return $atts;
});
