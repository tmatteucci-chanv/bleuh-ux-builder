<?php

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}



function bleuh_override_var_tags() {

    // exit when non admin logged in user tries to access this page
    $current_user = wp_get_current_user();
    if (user_can($current_user, 'administrator') || user_can($current_user, 'editor') || user_can($current_user, 'shop_manager')) {

        $store_number = $_POST['store_number'];
        $items = $_POST['items'];
        $override_until_qty = (int) $_POST['override_until_qty_sold'];

        // store data in the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'bleuh_store_lot_override';

        // Begin transaction for better performance and rollback capability
        $wpdb->query('START TRANSACTION');

        // Base query
        $query_base = "INSERT INTO $table_name (
                   store_number,
                   GTIN,
                   lot,
                   new_live_qty,
                   previous_live_qty,
                   displayed_qty,
                   depleted,
                   weight,
                   override_until_qty_drops_by) VALUES ";

        // Values array
        $query_values = [];

        $weight = 0;
        // Iterate through each item and create a query snippet for it
        foreach ($items as $item) {
            $weight++;
            $query_values[] = $wpdb->prepare("(%s, %s, %s, %d, %d, %d, %d, %d, %d)",
                $store_number,
                $_POST['GTIN'], // GTIN for the current item
                $item['lot'], // lot for the current item
                $_POST['live_qty'], // previous_live_qty for the current item
                $_POST['live_qty'], // previous_live_qty for the current item
                $item['qty'], // lot for the current item
                $override_until_qty, // qty for the current item, depleted starts at max qty defined
                $weight, // weight for the current item
                $override_until_qty
            );
        }

        // Combine base query with all values
        $query = $query_base . implode(', ', $query_values);

        // Prepare the DELETE query
        $delete_query = $wpdb->prepare(
            "DELETE FROM $table_name WHERE store_number = %s AND GTIN = %s;",
            $store_number,
            $_POST['GTIN'],
        );

        // Execute the DELETE query
        $wpdb->query($delete_query);

        // Perform the insert query
        $result = $wpdb->query($query);

        // Check result
        if ($result === false) {
            // If an error occurred, rollback the transaction
            $wpdb->query('ROLLBACK');
            // Handle error here
        } else {
            // If all went well, commit the transaction
            $wpdb->query('COMMIT');
        }

        echo "success";
        wp_die();
    }
}

add_action('wp_ajax_bleuh_override_var_tags', 'bleuh_override_var_tags'); // For logged in users
add_action('wp_ajax_nopriv_bleuh_override_var_tags', 'bleuh_override_var_tags'); // For logged in users

