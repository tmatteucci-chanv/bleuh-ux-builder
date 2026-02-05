<?php

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

add_filter('the_content', 'bleuh_replace_long_description', 20);

function bleuh_replace_long_description($content) {
    if (is_product()) {
        // Check if the ACF field 'description' is not empty.
        $acf_description = get_field('short_description');
        if (!empty($acf_description)) {
            // Path to your custom template file.
            $template_file = plugin_dir_path(__FILE__) . '/../templates/single-product-description.php';

            // Use the custom template if it exists.
            if (file_exists($template_file)) {
                ob_start(); // Start output buffering.
                include $template_file;
                $custom_description = ob_get_clean(); // Get the contents of the buffer.

                return $custom_description; // Replace the original long description.
            }
        }
    }

    return $content; // Return the original content if no replacement is needed.
}
/*
add_filter('woocommerce_product_tabs', 'bleuh_replace_long_desc', 20);
function bleuh_replace_long_desc($tabs) {
    if (!is_product()) {
        return $tabs;
    }

    // Check if the ACF field 'description' is not empty.
    $acf_description = get_field('description');
    if (!empty($acf_description)) {
        // Replace the default description tab content with a custom callback.
        $tabs['description']['callback'] = 'bleuh_product_description_callback';
    }

    return $tabs;
}
function bleuh_product_description_callback() {
    // Check if the ACF field 'description' is not empty.
    $acf_description = get_field('description');
    if (!empty($acf_description)) {
        // Path to your custom template file.
        $template_file = plugin_dir_path(__FILE__) . '/../templates/single-product-description.php';

        // Output custom description using a template or fallback.
        if (file_exists($template_file)) {
            include $template_file; // Include the custom template.
        }
    }
}
*/

add_action('woocommerce_single_product_summary', 'bleuh_short_description_hook', 15);
function bleuh_short_description_hook() {
    if (!is_product()) {
        return; // Ensure this only runs on the single product page
    }

    // Check if the short description setting exists (optional)
    if (!empty(get_field('short_description'))) {
        // Remove the default WooCommerce short description
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);

        // Add the custom short description
        bleuh_custom_short_description();
    }
}

function bleuh_custom_short_description() {
    // Path to custom template file for the short description
    $template_file = plugin_dir_path(__FILE__) . '/../templates/single-product-short-description.php';

    // Check if the custom template file exists and include it
    if (file_exists($template_file)) {
        include $template_file;
    }
}


// Hook into ACF's load_field filter to dynamically populate the dropdown
add_filter('acf/prepare_field/name=product_auto', 'bleuh_prepare_product_dropdown');
function bleuh_prepare_product_dropdown($field) {
    // Ensure WooCommerce is active
    if (!class_exists('WooCommerce')) {
        return $field;
    }

    // Get all published products
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1, // Get all products
        'orderby' => 'title',
        'order' => 'ASC',
        'suppress_filters' => false, // Disable custom filters to prevent caching or unexpected results
        'no_found_rows' => true, // Optimize query by not counting rows
    );

    $products = get_posts($args);

    // Ensure we have products and clear any existing choices
    $field['choices'] = array();
    //$current_language = apply_filters('wpml_current_language', null);
    if ($products) {
        foreach ($products as $product) {
            // Use product ID as the key and product title as the value
            $field['choices'][$product->ID] = $product->post_title . ' (' . get_permalink($product->ID) . ')';
        }
    }

    return $field;
}

// Hook into ACF's load_field filter to dynamically populate the dropdown
add_filter('acf/prepare_field/name=variety_auto', 'bleuh_prepare_var_dropdown');
function bleuh_prepare_var_dropdown($field) {
    // Ensure WooCommerce is active
    if (!class_exists('WooCommerce')) {
        return $field;
    }

    // Get all published 'featured_item' posts
    $args = array(
        'post_type' => 'featured_item',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'suppress_filters' => false,
        'no_found_rows' => true,
    );

    $products = get_posts($args);

    // Clear existing choices and repopulate with fresh data
    $field['choices'] = array();

    if ($products) {
        foreach ($products as $product) {
            $field['choices'][$product->ID] = $product->post_title . ' (' . get_permalink($product->ID) . ')';
        }
    }

    return $field;
}
