<?php

// info for store locator

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

// Store Locator: Get products info and their varieties
function bleuh_ajax_store_inventory() {
    global $wpdb;
    $PC = urldecode($_REQUEST["POSTAL_CODE"]);
    $PC_for_db = sanitize_text_field($PC);
    $query = "SELECT p.*, sp.qty AS `cached_store_qty`
              FROM {$wpdb->prefix}bleuh_store_products sp
              LEFT JOIN {$wpdb->prefix}bleuh_products p
                ON sp.GTIN = p.GTIN
              WHERE sp.store_number = '%s'
              AND p.GTIN IS NOT NULL
              ORDER BY p.collection, p.name";
    $prepared_query = $wpdb->prepare($query, $PC_for_db);
    $store_products = $wpdb->get_results($prepared_query, ARRAY_A);

    // Fetch posts of type products
    $args = array(
        'suppress_filters' => false,
        'lang'           => ICL_LANGUAGE_CODE,
        'post_type'      => 'product',
        'posts_per_page' => -1, // Get all products; use caution with this setting on large shops
        'post_status'    => 'publish', // Only retrieve published posts
        'fields'         => 'ids', // Only get the post IDs to reduce memory usage
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => 'external', // Only get external products
            ),
        )
    );

    $wp_products = new WP_Query($args);
    $permalinks = array();

    // Pre-build the GTIN lookup array for faster access
    $gtin_lookup = array_column($store_products, 'GTIN', 'GTIN');

    if ($wp_products->have_posts()) {
        $product_ids = $wp_products->posts;
        foreach ($product_ids as $product_id) {
            // Directly get the product URL using post meta
            $product_url = get_post_meta($product_id, '_product_url', true);
            $gtin = get_field('gtin', $product_id);
            if (!empty($gtin)) {
                if (strpos(ICL_LANGUAGE_CODE, 'fr') === false)
                    $permalinks[$gtin] = apply_filters('wpml_permalink', get_permalink($product_id), 'en');
                else
                    $permalinks[$gtin] = apply_filters('wpml_permalink', get_permalink($product_id), 'fr');
            } else {
                foreach ($gtin_lookup as $gtin) {
                    if (stripos($product_url, $gtin) !== false) {
                        if (strpos(ICL_LANGUAGE_CODE, 'fr') === false)
                            $permalinks[$gtin] = apply_filters('wpml_permalink', get_permalink($product_id), 'en');
                        else
                            $permalinks[$gtin] = apply_filters('wpml_permalink', get_permalink($product_id), 'fr');
                        // $permalinks[$gtin] = apply_filters('wpml_permalink', get_permalink($product_id), ICL_LANGUAGE_CODE);
                        // Remove the found GTIN from lookup to prevent unnecessary future checks
                        // unset($gtin_lookup[$gtin]);
                        break; // Break out of the loop once a match is found
                    }
                }
            }
        }
    }

    wp_reset_postdata(); // Reset the global post object


    // Loop through each variety and add the permalink if the GTIN matches
    foreach ($store_products as &$product) {
        $GTIN = trim($product['GTIN']);
        if (array_key_exists($GTIN, $permalinks)) {
            // Add the permalink to the variety data
            $product['permalink'] = $permalinks[$GTIN];
        } else {
            // If no permalink was found, set it to '#'
            $product['permalink'] = '#';
        }
    }
    // unset($product); // Break the reference with the last element

    // get varieties
    $gtins = array_column($store_products, "GTIN");
    $var_results = bleuh_varieties($PC, $gtins);

    // if no qty is set, set it to 0
    $store_products = array_map(function($product) {
        if (!isset($product['cached_store_qty'])) $product['cached_store_qty'] = 0;
        return $product;
    }, $store_products);

    // get buy links
    $buy_links = bleuh_get_buy_links($gtins);
    $var_results_map = [];
    foreach ($var_results as $var) {
        $var_results_map[$var["SQDC_SKU"]][] = $var;
    }

    // DEBUG: add deliveries to the response
    $deliveries_results_map = [];
    if ($_REQUEST["debug"]=='true') {
        $deliveries = bleuh_deliveries($PC, $gtins);
        foreach ($deliveries as $delivery) {
            $deliveries_results_map[$delivery["GTIN"]][] = $delivery;
        }
    }

    $overrides = bleuh_get_var_overrides($PC, $gtins);
    $overrides_map = [];
    foreach ($overrides as $override) {
        $overrides_map[$override["GTIN"]][] = $override;
    }

    foreach ($store_products as &$product) {
        $product["buy_link"] = $buy_links[$product['GTIN']] ?? '#';
        $product["varieties"] = $var_results_map[$product["GTIN"]] ?? [];
        if ($_REQUEST["debug"]=='true')
            $product["deliveries"] = $deliveries_results_map[$product["GTIN"]] ?? [];
        $product["overrides"] = $overrides_map[$product["GTIN"]] ?? [];
    }

    echo json_encode($store_products);
    wp_die();
}

add_action('wp_ajax_bleuh_ajax_store_inventory', 'bleuh_ajax_store_inventory'); // For logged in users
add_action('wp_ajax_nopriv_bleuh_ajax_store_inventory', 'bleuh_ajax_store_inventory'); // For non-logged in users