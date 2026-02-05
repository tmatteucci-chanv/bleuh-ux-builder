<?php

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

// Action hook to add the menu
add_action( 'admin_menu', 'my_admin_menu' );
add_action('admin_menu', function () {
    // Remove the default "Admin Bleuh" submenu item
    remove_submenu_page('bleuh-admin', 'bleuh-admin');
});

function my_admin_menu() {
    // Add the main menu page
    add_menu_page(
        'Administration Bleuh',
        'Admin Bleuh',
        'manage_options',
        'bleuh-admin',
        'bleuh_admin_landing',
        plugins_url('../img/logo.png', __FILE__),
        2
    );

    add_submenu_page(
        'bleuh-admin', // Parent slug (matches the main menu slug)
        'Bleuh Logs', // Page title
        'Logs & Crons', // Menu title (item displayed in the submenu)
        'manage_options', // Capability required
        'bleuh-logs', // Submenu slug
        'bleuh_admin_landing' // Callback function to render the page
    );

    // submenu Varieties overrides
    add_submenu_page(
        'bleuh-admin',
        'Variétés temporaires',
        'Variétés temporaires',
        'manage_options',
        'bleuh-admin-var-overrides',
        'bleuh_admin_var_overrides'
    );

    // submenu XLSX to PDF
    add_submenu_page(
        'bleuh-admin',
        'XLSX to PDF',
        'ChatPot (XLSX to PDF)',
        'manage_options',
        'bleuh-admin-pdf',
        'bleuh_admin_pdf'
    );
}

function bleuh_admin_styles() {
    wp_enqueue_style('bleuh-admin-styles', plugins_url('../css/admin.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'bleuh_admin_styles');

// This function outputs the content of the main menu page
function bleuh_admin_landing() {
    include_once( plugin_dir_path( __FILE__ ). '../scripts/admin-landing.php' );
}

// This function outputs the content of the PDF TO XLSX converter page
function bleuh_admin_pdf() {
    include_once( plugin_dir_path( __FILE__ ). '../scripts/admin-pdf.php' );
}

// This function outputs the content of the var & shops page
function bleuh_admin_var_shops() {
    include_once( plugin_dir_path( __FILE__ ). '../scripts/admin-strains.php' );
}

// This function outputs the content of the Web shops page
function bleuh_admin_web_shops() {
    include_once( plugin_dir_path( __FILE__ ). '../scripts/admin-web-shops.php' );
}

// This function outputs the content of the store locator admin page
function bleuh_admin_store_locator() {
    include_once( plugin_dir_path( __FILE__ ). '../scripts/admin-store-locator.php' );
}

function bleuh_admin_var_overrides() {
    include_once( plugin_dir_path( __FILE__ ). '../scripts/admin-overrides.php' );
}