<?php

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

/* starts at 0 */
function bleuh_col_to_number($label) {
    $number = 0;
    $length = strlen($label);
    for ($i = 0; $i < $length; $i++) {
        $char = strtoupper($label[$i]); // Convert to uppercase for case-insensitive matching
        $digit = ord($char) - ord('A') + 1; // Convert ASCII to digit (1-26)
        $number = $number * 26 + $digit; // Shift current number to the left and add new digit
    }
    return $number-1;
}

// logs
define('BLEUH_LOG_LINES_COUNT', 5000);
define('BLEUH_LOG_DISPLAY_COUNT', 200);

// emails
if ($_SERVER['HTTP_HOST'] == 'bleuh.co') {
	define( 'BLEUH_EMAIL_MISSING_LOTS', 'inventaires@bleuh.co' );
} else {
	define( 'BLEUH_EMAIL_MISSING_LOTS', 'l.laverdure@bleuh.co' );
}

// global timezone
define('BLEUH_TIMEZONE', 'America/New_York');

// misc
define('BLEUH_MAX_CAROUSEL_ITEMS', 10);
define('BLEUH_MAX_FORM_FILLS_BATCH', 25);

// Google Access
define('GOOGLE_SA_JSON', "/home/customer/www/bleuh-gdrive-pkey.json");
define('GOOGLE_MAPS_API_KEY', 'AIzaSyCW-4UHo_7yj7hBtqOpMiYmTkHACn_M6Pk');

// Ontario Stores
define('ON_STORES_DOC_ID', "1KrKMBcXJRF7DJ4ROoY8-wcjek2cBGI2ioEziWLxo8cs");
define('ON_STORES_SHEET', 1);
define('ON_STORES_ADDRESS', bleuh_col_to_number("I"));
define('ON_STORES_NAME', bleuh_col_to_number("H"));
define('ON_STORES_GTIN', bleuh_col_to_number("O"));
define('ON_STORES_QTY', bleuh_col_to_number("N"));
define('ON_STORES_P_COLLECTION', bleuh_col_to_number("E"));
define('ON_STORES_P_NAME', bleuh_col_to_number("B"));
define('ON_STORES_P_BLEND', bleuh_col_to_number("B")); // is within this string
define('ON_STORES_P_FORMAT', bleuh_col_to_number("D"));
define('ON_STORES_P_WEIGHT', bleuh_col_to_number("A")); // is within this string
define('ON_STORES_POSTAL_CODE_BATCH_SIZE', 50);
define('ON_STORES_POSTAL_CODE_UPDATE_BATCH_SIZE', 200);

// Master Lots doc
define('LOTS_DOC_ID', "1y4tuJlxo9nGzz-1W4L6mpDSmgrH4ofBP");
define('LOTS_DOC_SHEET', 1);
define('LOTS_DOC_COL_LOT', bleuh_col_to_number("A"));
define('LOTS_DOC_COL_VAR', bleuh_col_to_number("B"));
define('LOTS_DOC_COL_THC', bleuh_col_to_number("C"));
define('LOTS_DOC_COL_CBD', bleuh_col_to_number("D"));
define('LOTS_DOC_COL_WRAP_DATE', bleuh_col_to_number("E"));
// Ontario data same Master doc, but different sheet
define('ON_LOTS_DOC_SHEET', 3);
define('ON_LOTS_SKU', bleuh_col_to_number("L"));
define('ON_LOTS_PRODUCT_NAME', bleuh_col_to_number("B"));
define('ON_LOTS_PRODUCT_TYPE', bleuh_col_to_number("C"));
define('ON_LOTS_PRODUCT_FORMAT', bleuh_col_to_number("D"));
define('ON_LOTS_PRODUCT_WRAP_DATE', bleuh_col_to_number("E"));
define('ON_LOTS_PRODUCT_LOT', bleuh_col_to_number("F"));
define('ON_LOTS_VARIETY_NAME', bleuh_col_to_number("G"));
define('ON_LOTS_UNIT_THC', bleuh_col_to_number("H"));
define('ON_LOTS_UNIT_CBD', bleuh_col_to_number("I"));
define('ON_LOTS_UNIT_COLLECTION', bleuh_col_to_number("J"));
define('ON_LOTS_CATEGORY', bleuh_col_to_number("K"));
define('ON_LOTS_DISPLAY', bleuh_col_to_number("M"));
define('ON_LOTS_THRESHOLD_MONTHS', 5);

// GTIN doc
define('GTIN_DOC_ID', "1lF5aEsQhvccT3_cpEXnPDy9ieNu1wGgM");
define('GTIN_DOC_SHEET', 1);
define('GTIN_DOC_COL_COLL', bleuh_col_to_number("B"));
define('GTIN_DOC_COL_PROD', bleuh_col_to_number("C"));
define('GTIN_DOC_COL_GTIN', bleuh_col_to_number("D"));
define('GTIN_DOC_COL_WEIGHT', bleuh_col_to_number("E"));
define('GTIN_DOC_COL_FORMAT', bleuh_col_to_number("F"));
define('GTIN_DOC_COL_FORMAT_EN', bleuh_col_to_number("G"));
define('GTIN_DOC_COL_BLEND', bleuh_col_to_number("H"));
define('GTIN_DOC_COL_BLEND_EN', bleuh_col_to_number("I"));
define('GTIN_DOC_COL_DISPLAY', bleuh_col_to_number("J"));

// Deliveries docs
define('DELIVERIES_DIR_ID', "1MFVuXLztnoe2taj7_SJiTo2i6t89FUl7");
define('DELIVERIES_BATCH_MIN_DATE', '2023-08-25');
define('DELIVERIES_UPDATE_BATCH_COUNT', 9);
define('DELIVERIES_MANUAL_UPDATE_BATCH_COUNT', 25);
define('DELIVERIES_DOC_STORE_NUMBER', bleuh_col_to_number("B"));
define('DELIVERIES_DOC_GTIN', bleuh_col_to_number("C"));
define('DELIVERIES_DOC_LOT', bleuh_col_to_number("E"));
define('DELIVERIES_DOC_QTY', bleuh_col_to_number("F"));

// Deliveries offset doc
define('DELIVERIES_OFFSET_DOC_ID', '1-p_QQbtGDJbQgmbuzZG4R031sKlRzg43');
define('DELIVERIES_OFFSET_DOC_COL_STORE_NUM', bleuh_col_to_number("A"));
define('DELIVERIES_OFFSET_DOC_COL_OFFSET', bleuh_col_to_number("C"));
define('DELIVERIES_OFFSET_DEFAULT', 3);

// New: Deliveries DEAR Cin7
define('DEAR_ACCOUNT_ID', "3b252550-1a3c-464e-935f-4c74898f489a");
define('DEAR_ACCOUNT_KEY', "65d49363-2b49-2fd1-1014-52bd2d80082d");

// sFTP metrogreen
define('METROGREEN_URL', 'fileconnect.metroscg.com');
define('METROGREEN_RSA', '/home/customer/www/mg.private');
define('METROGREEN_RSA_PUB', '/home/customer/www/mg.pub');
define('METROGREEN_USER', 'BLEUH1035');
define('METROGREEN_PASSPHRASE', 'v489v$T0qv34t-uU');
define('METROGREEN_REMOTE_DIR', '/outbound');

// Web inventories doc
define('WEB_INV_DOC_COL_SKU', bleuh_col_to_number("E"));
define('WEB_INV_DOC_COL_LOT', bleuh_col_to_number("H"));
define('WEB_INV_DOC_COL_QTY', bleuh_col_to_number("J"));
define('WEB_INV_DOC_COL_DATE', bleuh_col_to_number("L"));
