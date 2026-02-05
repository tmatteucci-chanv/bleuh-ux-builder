<?php

// Security measure to prevent direct access to the plugin file.
if (!defined('ABSPATH')) {
    die('Sorry, you are not allowed to access this page directly.');
}

include_once(plugin_dir_path(__FILE__) . '/common.php');

/**
 * Importation des magasins de l'Ontario.
 * 
 * Cette fonction télécharge un fichier XLSX depuis une URL Google Sheets spécifique (ON_STORES_DOC_ID),
 * l'enregistre temporairement, puis lance le processus d'importation via `bleuh_import_ontario_stores`.
 * Elle met ensuite à jour les produits et les associations de lots.
 *
 * @return bool Succès ou échec de l'importation.
 */
function bleuh_ON_stores_import()
{
    bleuh_update_option_with_date("bleuh_on_stores_import_trigger");
    try {
        $temp_file = tempnam(sys_get_temp_dir(), 'bleuh-on-stores');
        $data = file_get_contents("https://docs.google.com/spreadsheets/d/" . ON_STORES_DOC_ID . "/export?format=xlsx");
        file_put_contents($temp_file, $data);
        if (bleuh_import_ontario_stores($temp_file)) {
            unlink($temp_file);
            bleuh_update_option_with_date("bleuh_on_stores_import_success_run");
            bleuh_log("Ontario stores, products and associations imported.");

            ingest_ontario_lots_N_products();

            return true;
        }
        else {
            unlink($temp_file);
            return false;
        }
    }
    catch (\Exception $ex) {
        bleuh_log("Ontario stores, products and associations import failed: " . $ex->getMessage());
        return false;
    }
}

/**
 * Importation des 'Lots' depuis Google Drive.
 * 
 * Utilise l'API Google Drive pour télécharger le fichier des lots (LOTS_DOC_ID).
 * Si l'importation réussit via `bleuh_import_lots`, la date de modification est vérifiée
 * pour vider le cache si nécessaire.
 *
 * @return bool Succès ou échec.
 */
function bleuh_cron_lots_import(): bool
{
    bleuh_update_option_with_date("bleuh_lots_imports_trigger");
    try {
        $client = new Google_Client();
        $client->setApplicationName('Bleuh Drive API');
        $client->setScopes([
            Google_Service_Drive::DRIVE_METADATA_READONLY,
            Google_Service_Drive::DRIVE_READONLY,
            Google_Service_Drive::DRIVE_FILE,
        ]);
        $client->setAuthConfig(GOOGLE_SA_JSON);
        $service = new Google_Service_Drive($client);
        $file = $service->files->get(LOTS_DOC_ID, [
            "supportsAllDrives" => true,
            "alt" => "media",
        ]);
        $fileTime = $service->files->get(LOTS_DOC_ID, [
            "supportsAllDrives" => true,
            "fields" => "modifiedTime"
        ]);
        $temp_file = tempnam(sys_get_temp_dir(), 'bleuh-lots');
        file_put_contents($temp_file, $file->getBody()->getContents());
        if (bleuh_import_lots($temp_file)) {
            unlink($temp_file);
            $modifiedTime = new DateTime($fileTime->getModifiedTime());
            $timezone = new DateTimeZone(BLEUH_TIMEZONE);
            $modifiedTime->setTimezone($timezone);
            $dateString = $modifiedTime->format('Y-m-d H:i:s');
            $previous_date = get_option("bleuh_lots_import");
            $previousDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $previous_date);
            $latestDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
            if ($previousDateTime && $latestDateTime) {
                if ($previousDateTime < $latestDateTime) {
                    clear_sg_cache();
                }
            }
            bleuh_update_option_with_date("bleuh_lots_imports_success_run");
            bleuh_log("Lots imported.");
            return true;
        }
        else {
            bleuh_log("Lots import failed: bleuh_import_lots=false.");
            unlink($temp_file);
            return false;
        }
    }
    catch (\Exception $ex) {
        bleuh_log("Lots import failed: " . $ex->getMessage());
        return false;
    }
}

/**
 * Fonction Cron Principale (Importation Metrogreen / SFTP).
 * 
 * Cette fonction gère l'importation des inventaires web (stocks) depuis le serveur SFTP de Metrogreen.
 * Elle peut aussi être utilisée pour télécharger le fichier CSV directement (si $download_csv = true).
 * 
 * Processus :
 * 1. Connexion SFTP et téléchargement du fichier le plus récent.
 * 2. Importation des données dans la base de données via `bleuh_import_web_inventories`.
 * 3. Mise à jour de l'horodatage et vidage du cache si de nouvelles données sont trouvées.
 *
 * @param bool $download_csv Si vrai, force le téléchargement du fichier au navigateur au lieu de l'importer.
 * @return bool Succès ou échec.
 */
function bleuh_cron($download_csv = false): bool
{
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    bleuh_update_option_with_date("bleuh_lots_metrogreen_import_trigger");
    try {
        $temp_file = tempnam(sys_get_temp_dir(), 'bleuh');
        $time = bleuh_sftp_download($temp_file);
        if ($time === false) {
            throw new \Exception("Download from sFTP failed.");
        }

        if ($download_csv) {
            // download CSV file to user.
            if (file_exists($temp_file)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="metrogreen-export.csv"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($temp_file));
                ob_clean();
                flush();
                readfile($temp_file);
                unlink($temp_file);
                exit();
            }
        }
        elseif (bleuh_import_web_inventories($temp_file)) {
            $modifiedTime = new DateTime();
            $modifiedTime->setTimestamp($time);
            $timezone = new DateTimeZone(BLEUH_TIMEZONE);
            $modifiedTime->setTimezone($timezone);
            $dateString = $modifiedTime->format('Y-m-d H:i:s');
            $previous_date = get_option("bleuh_web_vars_import");
            $previousDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $previous_date);
            $latestDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
            if ($previousDateTime && $latestDateTime) {
                if ($previousDateTime < $latestDateTime) {
                    clear_sg_cache();
                }
            }
            update_option('bleuh_web_vars_import', $dateString);
            bleuh_update_option_with_date("bleuh_lots_metrogreen_import_success_run");
            bleuh_log("Metrogreen data imported.");
            unlink($temp_file);
        }
        else {
            unlink($temp_file);
        }
    }
    catch (\Exception $ex) {
        bleuh_log("Metrogreen import failed: " . $ex->getMessage());
        return false;
    }
    return true;
}

/**
 * Vérification du contenu manquant (Cron Quotidien).
 * 
 * Cette fonction vérifie plusieurs incohérences dans les données et envoie un rapport par courriel :
 * - Lots manquants (livraisons référençant des lots inexistants).
 * - Traductions manquantes pour les produits et les variétés.
 * - Pages de portfolio introuvables.
 * - Produits ou variétés obsolètes.
 * - Produits de l'Ontario manquants.
 *
 * Ne s'exécute pas le samedi et le dimanche.
 */
function bleuh_missing_lots()
{
    // TODO: filter with flowers only
    // TODO: add var product
    bleuh_update_option_with_date("bleuh_missing_lots_trigger");

    // if today is saturday or sunday skip the cron job
    $current_day = date('N'); // 1 (for Monday) through 7 (for Sunday)
    if ($current_day == 6 || $current_day == 7) {
        bleuh_log("Skipping missing lots cron job on weekend.");
        return;
    }

    global $wpdb;
    $host = $_SERVER['HTTP_HOST'];
    $send_email = false;
    $email_message = '<html><head><title>Contenu manquant!</title></head><body>';
    $email_message .= "<h1>Contenu manquant! ($host)</h1>";
    try {
        $query = "SELECT DISTINCT d.lot
                FROM {$wpdb->prefix}bleuh_store_deliveries d
                LEFT JOIN {$wpdb->prefix}bleuh_lots l
                    ON l.lot = d.lot
                WHERE l.variety_name IS NULL;";

        $results = $wpdb->get_results($query, ARRAY_N);
        $lots = array_values(array_filter(array_map(function ($item) {
            return trim($item[0]);
        }, $results), function ($lot) {
            return $lot !== '';
        }));

        if (!empty($lots)) {
            if (!defined('BLEUH_STAGE_ENV')) {
                $send_email = true;
                $email_message .= '<h2>Lots Invalides :</h2>';
                $email_message .= '<p>Les lots suivants ne figure pas dans le fichier <a href="https://docs.google.com/spreadsheets/d/1y4tuJlxo9nGzz-1W4L6mpDSmgrH4ofBP/edit" target="_blank">Master Lots.xlsx</a> : ' . implode(", ", $lots) . '</p>';
            }
            bleuh_log("Missing lots: " . implode(", ", $lots));
        }

        // get all products
        $result = find_missing_translations('product');

        if (!empty($result['missing_in_french'])) {
            $email_message .= "<h2>Traductions de produits manquantes en français :</h2>";
            $list = [];
            foreach ($result['missing_in_french'] as $product_id => $product_title) {
                $list[] = "<a href='https://$host/wp-admin/post.php?lang=fr&action=edit&post_type=product&post=" . $product_id . "&update_needed=1&trid=1472&language_code=fr'>$product_title (#$product_id)</a>";
            }
            $email_message .= "<p>" . implode(', ', $list) . "</p>";
            $send_email = true;
        }

        if (!empty($result['missing_in_english'])) {
            $email_message .= "<h2>Traductions de produits manquantes en anglais :</h2>";
            $list = [];
            foreach ($result['missing_in_english'] as $product_id => $product_title) {
                $list[] = "<a href='https://$host/wp-admin/post.php?lang=en&action=edit&post_type=product&post=" . $product_id . "&update_needed=1&trid=1472&language_code=en'>$product_title (#$product_id)</a>";
            }
            $email_message .= "<p>" . implode(', ', $list) . "</p>";
            $send_email = true;
        }

        $result = find_missing_translations('featured_item');
        if (!empty($result['missing_in_french'])) {
            $email_message .= "<h2>Traductions de variétés manquantes en français :</h2>";
            $list = [];
            foreach ($result['missing_in_french'] as $product_id => $product_title) {
                $list[] = "<a href='https://$host/wp-admin/post.php?lang=fr&action=edit&post_type=product&post=" . $product_id . "&update_needed=1&trid=1472&language_code=fr'>$product_title (#$product_id)</a>";
            }
            $email_message .= "<p>" . implode(', ', $list) . "</p>";
            $send_email = true;
        }

        if (!empty($result['missing_in_english'])) {
            $email_message .= "<h2>Traductions de variétés manquantes en anglais :</h2>";
            $list = [];
            foreach ($result['missing_in_english'] as $product_id => $product_title) {
                $list[] = "<a href='https://$host/wp-admin/post.php?lang=en&action=edit&post_type=product&post=" . $product_id . "&update_needed=1&trid=1472&language_code=en'>$product_title (#$product_id)</a>";
            }
            $email_message .= "<p>" . implode(', ', $list) . "</p>";
            $send_email = true;
        }

        $missing_varieties = get_missing_varieties();

        if (!empty($missing_varieties)) {
            $email_message .= "<h2>Contenu introuvable pour les " . count($missing_varieties) . " pages de variétés (portfolio) suivantes (quantités en parenthèses) :</h2>";
            $email_message .= "<p>" . implode('<br>', $missing_varieties) . "</p>";
            $send_email = true;
            bleuh_log("Missing portfolio pages for varieties: " . implode(", ", $missing_varieties));
        }

        // get deprecated products and varieties
        $deprecated_products = bleuh_get_deprecated("varieties");
        if (!empty($deprecated_products)) {
            $email_message .= "<h2>Contenu obsolète pour les " . count($deprecated_products) . " pages de variétés (portfolio) suivantes:</h2>";
            $email_message .= "<p>" . implode(', ', $deprecated_products) . "</p>";
            $send_email = true;
        }

        $deprecated_vars = bleuh_get_deprecated("products");
        if (!empty($deprecated_vars)) {
            $email_message .= "<h2>Contenu obsolète pour les " . count($deprecated_vars) . " pages de produits (woocommerce) suivantes:</h2>";
            $email_message .= "<p>" . implode(', ', $deprecated_vars) . "</p>";
            $send_email = true;
        }

        // get missing Ontario product content
        $query = "SELECT 
            p.GTIN, 
            p.collection, 
            p.name, 
            p.format_en, 
            p.weight, 
            (SELECT l2.variety_name
             FROM `{$wpdb->prefix}bleuh_lots` l2
             LEFT JOIN `{$wpdb->prefix}bleuh_vars` v2 ON TRIM(UPPER(v2.lot)) = TRIM(UPPER(l2.lot))
             WHERE v2.SQDC_SKU = p.GTIN
             ORDER BY l2.wrap_date DESC
             LIMIT 1
            ) latest_var_name,
            MAX(l.THC) AS max_THC, 
            MAX(l.CBD) AS max_CBD, 
            MAX(l.wrap_date) AS latest_wrap_date, 
            (SELECT l1.lot
             FROM `{$wpdb->prefix}bleuh_lots` l1
             LEFT JOIN `{$wpdb->prefix}bleuh_vars` v1 ON TRIM(UPPER(v1.lot)) = TRIM(UPPER(l1.lot))
             WHERE v1.SQDC_SKU = p.GTIN
             ORDER BY l1.wrap_date DESC
             LIMIT 1
            ) latest_lot
          FROM `{$wpdb->prefix}bleuh_lots` l 
          LEFT JOIN `{$wpdb->prefix}bleuh_vars` v ON TRIM(UPPER(v.lot)) = TRIM(UPPER(l.lot))
          LEFT JOIN `{$wpdb->prefix}bleuh_products` p ON v.SQDC_SKU = p.GTIN
          WHERE l.is_ontario = 'Y'
          GROUP BY p.GTIN
          ORDER BY p.collection, p.blend;";

        $prepared_query = $wpdb->prepare($query);
        $results = $wpdb->get_results($prepared_query);


        // Query all WooCommerce products
        $args = [
            'post_type' => 'product', // WooCommerce products
            'posts_per_page' => -1, // Get all products
            'post_status' => 'publish', // Only published products
        ];

        $products_query = new WP_Query($args);

        $prod_url = [];
        if ($products_query->have_posts()) {
            while ($products_query->have_posts()) {
                $products_query->the_post();
                $product_id = get_the_ID();
                $gtin = trim(strtolower(get_field('gtin', $product_id)));
                $prod_url[$gtin] = get_permalink($product_id);
            }
            // Reset post data
            wp_reset_postdata();
        }

        // get all varieties (porfolio items)
        $args = [
            'post_type' => 'featured_item',
            'posts_per_page' => -1, // Get all products
            'post_status' => 'publish', // Only published products
        ];

        $vars_query = new WP_Query($args);

        $lot_url = [];
        if ($vars_query->have_posts()) {
            while ($vars_query->have_posts()) {
                $vars_query->the_post();
                $var_id = get_the_ID();
                $title = bleuh_normalize_title(get_the_title($var_id));
                $lot_url[$title] = get_permalink($var_id);
            }
            // Reset post data
            wp_reset_postdata();
        }

        $missing_on_var = [];
        $missing_on_prod = [];

        if (!empty($results)) {
            foreach ($results as $row) {
                $url = $prod_url[trim(strtolower($row->GTIN))] ?? '';
                if (empty($url)) {
                    $missing_on_prod[] = $row->GTIN . " (" . $row->collection . " - " . $row->name . ")";
                }
                $this_lot_url = $lot_url[bleuh_normalize_title($row->latest_var_name)] ?? '';
                if (empty($this_lot_url)) {
                    $missing_on_var[] = $row->latest_var_name . " (" . $row->collection . " - " . $row->name . ")";
                }
            }
        }
        if (!empty($missing_on_prod)) {
            $email_message .= "<h2>Contenu introuvable pour les " . count($missing_on_prod) . " pages de produits d'Ontario (woocommerce) suivantes:</h2>";
            $email_message .= "<p>" . implode(', ', $missing_on_prod) . "</p>";
            $send_email = true;
        }

        if (!empty($missing_on_var)) {
            $email_message .= "<h2>Contenu introuvable pour les " . count($missing_on_var) . " pages de variétés d'Ontario (portfolio) suivantes:</h2>";
            $email_message .= "<p>" . implode(', ', $missing_on_var) . "</p>";
            $send_email = true;
        }

        $email_message .= '</body></html>';

        if ($send_email) {
            $to = BLEUH_EMAIL_MISSING_LOTS;
            $subject = 'Contenu manquant!';
            $headers = "From: Bleuh (System Reminder)<system@$host>\r\n" .
                "Reply-To: system@$host\r\n" .
                "X-Mailer: PHP/" . phpversion() . "\r\n" .
                "MIME-Version: 1.0\r\n" .
                'X-Priority: 1 (Highest)' . "\r\n" . // Mark email as high priority
                'Importance: High' . "\r\n" . // Mark email as important
                "Content-type: text/html; charset=utf-8\r\n"; // Specify HTML content type
            if (!wp_mail($to, $subject, $email_message, $headers)) {
                bleuh_log("Error sending missing content email.");
            }
            bleuh_update_option_with_date("bleuh_missing_lots_success");
        }

    }
    catch (\Exception $e) {
        bleuh_log("Error sending missing content email: " . $e->getMessage());
    }
}

// ---------------------------------------------------------
// Planification des tâches Cron (Schedules)
// ---------------------------------------------------------

// Rappel : Les heures sont basées sur le timezone du serveur ou de WordPress.

if (!wp_next_scheduled('bleuh_hook_missing_lots')) {
    // Get the current time with respect to the WordPress settings
    $current_time = current_time('timestamp');
    // Create a DateTime object from the current timestamp
    $current_time = new DateTime("@$current_time");
    // Set the timezone for the DateTime object
    // Make sure BLEUH_TIMEZONE is a string like 'America/New_York'
    $timezone = new DateTimeZone(BLEUH_TIMEZONE);
    $current_time->setTimezone($timezone);

    // If the current time is before 8 AM today, schedule for today. Otherwise, schedule for tomorrow.
    if ($current_time->format('H') < 8) {
        // Schedule for today at 8 AM
        $next_8am_time = $current_time->modify('today 8 am');
    }
    else {
        // If it's already past 8 AM, schedule for the next day
        $next_8am_time = $current_time->modify('tomorrow 8 am');
    }

    // Get the timestamp for the next 8 AM
    $next_8am_timestamp = $next_8am_time->getTimestamp();

    // Schedule the event
    wp_schedule_event($next_8am_timestamp, 'daily', 'bleuh_hook_missing_lots');
}
add_action('bleuh_hook_missing_lots', 'bleuh_missing_lots');

if (!wp_next_scheduled('bleuh_hook')) {
    wp_schedule_event(time(), 'hourly', 'bleuh_hook');
}
add_action('bleuh_hook', 'bleuh_cron');

if (!wp_next_scheduled('bleuh_hook_lots')) {
    wp_schedule_event(time(), 'hourly', 'bleuh_hook_lots');
}
add_action('bleuh_hook_lots', 'bleuh_cron_lots_import');

/* // old algorithm if ( ! wp_next_scheduled( 'bleuh_hook_strains' ) ) {
 wp_schedule_event( time(), 'hourly', 'bleuh_hook_strains' ); } add_action( 'bleuh_hook_strains', 'bleuh_cron_strains_import' ); */

if (!wp_next_scheduled('bleuh_hook_store_locator')) {
    wp_schedule_event(time(), 'hourly', 'bleuh_hook_store_locator');
}
add_action('bleuh_hook_store_locator', 'bleuh_save_store_data');

// empty products page fix
add_filter('cron_schedules', 'add_ten_minutes_cron_interval');
function add_ten_minutes_cron_interval($schedules)
{
    $schedules['ten_minutes'] = array(
        'interval' => 600, // Interval in seconds
        'display' => esc_html__('Every Ten Minutes'),
    );
    return $schedules;
}
if (!wp_next_scheduled('bleuh_hook_products_fix')) {
    wp_schedule_event(time(), 'ten_minutes', 'bleuh_hook_products_fix');
}
function bleuh_fix_products()
{
    bleuh_update_option_with_date("bleuh_permalinks_trigger");
    try {
        flush_rewrite_rules();
        bleuh_log("Rewrite rules flushed. (Products fix)");
        bleuh_update_option_with_date("bleuh_permalinks_success");
    }
    catch (\Exception $e) {
        bleuh_log("Rewrite rules couldn't be flushed. (Products fix failed)");
    }
}
add_action('bleuh_hook_products_fix', 'bleuh_fix_products');

// save deliveries every hour
if (!wp_next_scheduled('bleuh_hook_save_deliveries')) {
    wp_schedule_event(time(), 'hourly', 'bleuh_hook_save_deliveries');
}
add_action('bleuh_hook_save_deliveries', 'bleuh_save_deliveries_data');

if (!wp_next_scheduled('bleuh_hook_ontario_stores')) {
    wp_schedule_event(time(), 'hourly', 'bleuh_hook_ontario_stores');
}
add_action('bleuh_hook_ontario_stores', 'bleuh_ON_stores_import');

if (!wp_next_scheduled('bleuh_hook_ontario_stores_postal_code_updates')) {
    wp_schedule_event(time(), 'ten_minutes', 'bleuh_hook_ontario_stores_postal_code_updates');
}
add_action('bleuh_hook_ontario_stores_postal_code_updates', 'bleuh_fill_store_geo_location');
