<?php

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

// Security measure to prevent direct access to the plugin file.
if (!defined('ABSPATH')) {
    die('Sorry, you are not allowed to access this page directly.');
}

function bleuh_is_bot()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $knownBots = [
        'Googlebot', 'Bingbot', 'AhrefsBot', 'SemrushBot',
        'YandexBot', 'DuckDuckBot'
    ];

    $isBot = false;
    foreach ($knownBots as $bot) {
        if (stripos($userAgent, $bot) !== false) {
            $isBot = true;
            break;
        }
    }

    return $isBot;
}

function bleuh_migrate_favorites()
{
    if (is_user_logged_in()) {
        global $wpdb;
        $user_id = get_current_user_id();
        $user_hash = md5($_SERVER['REMOTE_ADDR'] . $_COOKIE['my_hash_id']);

        // Get local favorite cookie counts
        $liked_query = $wpdb->prepare(
            "SELECT post_id
             FROM {$wpdb->prefix}bleuh_favorites
             WHERE hash_id = %s",
            $user_hash
        );
        $liked_results = $wpdb->get_results($liked_query, ARRAY_A);
        $liked_count = count($liked_results);

        if ($liked_count > 0) {
            // Extract post IDs
            $post_ids = array_column($liked_results, 'post_id');

            // Dynamically create placeholders for the IN clause
            $placeholders = implode(',', array_fill(0, count($post_ids), '%s'));

            // Prepare the SQL query
            $query = "UPDATE {$wpdb->prefix}bleuh_favorites
                      SET hash_id = %s
                      WHERE post_id IN ($placeholders)
                      AND hash_id = %s";

            // Merge arguments in the correct order: hash_id, post_ids, and original hash_id
            $args = array_merge(['U' . $user_id], $post_ids, [$user_hash]);

            // Prepare and execute the query
            $migrate_sql = $wpdb->prepare($query, $args);
            $wpdb->query($migrate_sql);
        }
    }
}
add_action('init', 'bleuh_migrate_favorites');

function bleuh_number_to_col($number)
{
    $label = '';
    $number++; // Adjust since `bleuh_col_to_number` subtracts 1
    while ($number > 0) {
        $remainder = ($number - 1) % 26; // Find the remainder (0-25)
        $label = chr($remainder + ord('A')) . $label; // Convert to character and prepend
        $number = intval(($number - 1) / 26); // Move to the next "digit"
    }
    return $label;
}
function bleuh_update_option_with_date($option)
{
    $now = new DateTime();
    $timezone = new DateTimeZone(BLEUH_TIMEZONE);
    $now->setTimezone($timezone);
    $now = $now->format('Y-m-d H:i:s');
    update_option($option, $now);
}
function bleuh_normalize_title($title)
{
    return strtolower(preg_replace('/[^a-zA-Z]/', '', trim($title)));
}

// d-m-Y input, returns m-Y
function bleuh_display_em_date($date)
{
    if (empty($date)) {
        return '';
    }
    $date = DateTime::createFromFormat('d/m/Y', $date);
    if (!$date) {
        return '';
    }
    return $date->format('m-Y');
}

// d-m-Y input, returns Y-m-d
function bleuh_sortable_em_date($date)
{
    if (empty($date)) {
        return '';
    }
    $date = DateTime::createFromFormat('d/m/Y', $date);
    if (!$date) {
        return '';
    }
    return $date->format('Y-m-d');
}

function bleuh_get_lat_lon($postal_code)
{

    // Build the request URL
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($postal_code) . "&key=" . GOOGLE_MAPS_API_KEY;

    // Fetch the API response
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    // Check if the API response is valid
    if ($data['status'] === 'OK') {
        // Extract latitude and longitude
        $latitude = $data['results'][0]['geometry']['location']['lat'];
        $longitude = $data['results'][0]['geometry']['location']['lng'];

        // Add the result to the array
        set_transient('PC_' . $postal_code, $latitude . ',' . $longitude, 0); // doesn't expire
        return $latitude . ',' . $longitude;
    }
    else {
        // Handle errors (e.g., invalid postal code)
        bleuh_log("Error fetching lat/lon for postal code: $postal_code. Error: " . $data['status']);
    }
    return false;
}

function bleuh_fill_store_geo_location()
{
    bleuh_update_option_with_date("bleuh_on_geo_trigger");
    try {
        $non_cached_api_requests = 0;
        global $wpdb;

        $stores = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bleuh_stores WHERE location = POINT(0, 0);", ARRAY_A);

        $updates = [];

        foreach ($stores as $store) {
            $postal_code = $store['postal_code'];
            $cached_string = get_transient('PC_' . $postal_code);
            if ($cached_string) {
                $lat_lng = explode(",", $cached_string);
            }
            else {
                $non_cached_api_requests++;
                $lat_lng = explode(",", bleuh_get_lat_lon($postal_code));
            }
            $updates[$postal_code] = $lat_lng;

            if ($non_cached_api_requests > ON_STORES_POSTAL_CODE_BATCH_SIZE) {
                bleuh_log("GEO Location: Reached the maximum number of non-cached API requests (" . ON_STORES_POSTAL_CODE_BATCH_SIZE . ")");
                break;
            }
        }

        $updated_rows = 0;
        foreach ($updates as $postal_code => $lat_lng) {
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}bleuh_stores SET location = POINT(%8f, %8f) WHERE postal_code = '%s';", $lat_lng[1], $lat_lng[0], $postal_code));
            $updated_rows++;
            if ($updated_rows > ON_STORES_POSTAL_CODE_UPDATE_BATCH_SIZE) {
                bleuh_log("GEO Location: Reached the maximum number of updated rows (" . ON_STORES_POSTAL_CODE_UPDATE_BATCH_SIZE . ")");
                break;
            }
        }
        if ($updated_rows > 0) {
            bleuh_log("Stores Geo Location Updated: {$updated_rows} rows.");
        }
        bleuh_update_option_with_date("bleuh_on_geo_success");
    }
    catch (\Exception $e) {
        bleuh_log("GEO Location: Error updating stores geo location: " . $e->getMessage());
    }
}

function tag_choices($pid, $field)
{
    $field_object = get_field_object('atts', $pid);

    if ($field_object && isset($field_object['sub_fields'])) {
        foreach ($field_object['sub_fields'] as $sub_field) {
            if ($sub_field['name'] === $field) {
                foreach ($sub_field['sub_fields'] as $aromes_sub_field) {
                    if ($aromes_sub_field['name'] === 'tags') {
                        return $aromes_sub_field['choices'];
                    }
                }
            }
        }
    }
    return [];
}

function bleuh_get_ids($post_type)
{
    // Define the query arguments
    $args = array(
        'post_type' => $post_type, // WooCommerce product post type
        'posts_per_page' => -1, // Get all products
        'fields' => 'ids' // Only return IDs
    );

    // Run the query
    $query = new WP_Query($args);

    // Check if products are found
    $product_ids = [];
    if ($query->have_posts()) {
        $product_ids = $query->posts; // Array of product IDs
    }

    // Restore original post data
    wp_reset_postdata();

    return $product_ids;
}

function bleuh_fix_vars_forms()
{

    global $wpdb;
    $ids_list = bleuh_get_ids('featured_item');

    bleuh_log("Fixing varieties forms for " . count($ids_list) . " varieties: " . implode(', ', $ids_list));

    foreach ($ids_list as $post_id) {

        try {

            $variety_name = urlencode(get_the_title($post_id));
            $atts = get_field("attributs", $post_id);

            // ON / QC
            // new product
            // tags: no not touch, automated

            // THC - can't find this info to automate...

            // wrap date
            if (empty(get_field("date_demballage", $post_id))) {
                if (!empty($variety_name)) {
                    $query = "SELECT l.wrap_date
                          FROM {$wpdb->prefix}bleuh_lots l
                          LEFT JOIN {$wpdb->prefix}bleuh_vars v
                            ON v.lot = l.lot
                          WHERE RemoveNonAlphabetic(l.variety_name) = RemoveNonAlphabetic(%s)
                          ORDER BY l.wrap_date DESC;";

                    $prepared_query = $wpdb->prepare($query, $variety_name);
                    $wrap_date = $wpdb->get_var($prepared_query);
                    update_field("date_demballage", $wrap_date, $post_id);
                }
            }

            // aromas
            if (empty($atts['aromes'])) {

                $aromas_list = [];

                // first get aromas from old acf
                $aromes = get_field("aromes", $post_id);

                if (!empty($aromes)) {
                    foreach ($aromes as $arome) {
                        $aromas_list[] = array("tags" => $arome);
                    }
                }
                else {
                    // get from UX Builder Content
                    $content = get_post($post_id)->post_content;
                    $aromas_choices = tag_choices($post_id, 'aromes');
                    foreach ($aromas_choices as $value => $label) {
                        if (strpos(bleuh_normalize_title($content), bleuh_normalize_title($label)) !== false) {
                            $aromas_list[] = array("tags" => $value);
                        }
                    }
                }
                $atts["aromes"] = $aromas_list;
            }

            // terpenes
            if (empty($atts['terpenes'])) {

                $terp_list = [];

                // first get terpenes from old acf
                $terp = get_field("terpenes", $post_id);

                if (!empty($terp)) {
                    foreach ($terp as $t) {
                        $terp_list[] = array("tags" => $t);
                    }
                }
                else {
                    // get from UX Builder Content
                    $content = get_post($post_id)->post_content;
                    $terp_choices = tag_choices($post_id, 'terpenes');
                    foreach ($terp_choices as $value => $label) {
                        if (strpos(bleuh_normalize_title($content), bleuh_normalize_title($label)) !== false) {
                            $terp_list[] = array("tags" => $value);
                        }
                    }
                }
                $atts["terpenes"] = $terp_list;
            }

            // effects
            if (empty($atts['effets'])) {

                $effects_list = [];

                // first get effects from old acf
                $effect = get_field("effets", $post_id);

                if (!empty($effect)) {
                    foreach ($effect as $eff) {
                        $effects_list[] = array("tags" => $eff);
                    }
                }
                else {
                    // get from UX Builder Content
                    $content = get_post($post_id)->post_content;
                    $effects_choices = tag_choices($post_id, 'effets');
                    foreach ($effects_choices as $value => $label) {
                        if (strpos(bleuh_normalize_title($content), bleuh_normalize_title($label)) !== false) {
                            $effects_list[] = array("tags" => $value);
                        }
                    }
                }
                $atts["effets"] = $effects_list;
            }


            // short description: do not touch as filling these triggers render of new template

            // Details (culture, croisement, lieu): can't automate...

            // products related: do not touch, automated
            // varieties related: do not touch, automated

            update_field('attributs', $atts, $post_id);

        }
        catch (\Exception $e) {
            bleuh_log("Error updating variety forms for post ID {$post_id}: " . $e->getMessage());
        }
    }


    return true;

}

function bleuh_fix_prod_forms()
{
    global $wpdb;

    $ids_list = bleuh_get_ids('product');

    bleuh_log("Fixing product forms for " . count($ids_list) . " varieties: " . implode(', ', $ids_list));

    foreach ($ids_list as $post_id) {

        try {

            // GTIN
            $gtin = get_field("gtin", $pid);
            // fallback to gtin from product URL
            if (empty($gtin)) {
                $gtin = get_post_meta($pid, '_product_url', true);
                $gtin = explode('/', $gtin);
                $gtin = end($gtin);
                update_field('gtin', $gtin, $pid);
            }


            // is ontario
            if (empty(get_field("is_ontario", $pid))) {
                $query = "SELECT is_ontario
                      FROM {$wpdb->prefix}bleuh_products p
                      WHERE p.GTIN = %s;";

                $prepared_query = $wpdb->prepare($query, $gtin);
                $results = $wpdb->get_results($prepared_query, ARRAY_A);
                foreach ($results as $result) {
                    if (trim(strtolower($result['is_ontario'])) == 'y') {
                        update_field('is_ontario', true, $pid);
                        break;
                    }
                }
            }

            // tags: do not touch, automated

            // THC
            if (empty(get_field("thc", $pid))) {
                $product = wc_get_product($pid);
                if ($product) {
                    // Get all attributes of the product
                    $attributes = $product->get_attributes();

                    // Check if the "Intensité" attribute exists
                    if (isset($attributes['intensite'])) {
                        // Get the "Intensité" attribute
                        $intensite_attribute = $attributes['intensite'];

                        if ($intensite_attribute->is_taxonomy()) {
                            // If it's a taxonomy-based attribute, get the terms
                            $terms = wp_get_post_terms($pid, $intensite_attribute->get_name(), array('fields' => 'names'));
                            update_field('thc', implode(', ', $terms), $pid);
                        }
                        else {
                            update_field('thc', $intensite_attribute->get_options()[0], $pid);
                        }
                    }
                }
            }

            // weight
            if (empty(get_field("weight", $pid))) {
                // get data from db
                $query = "SELECT p.weight
                FROM {$wpdb->prefix}bleuh_products p
                WHERE p.GTIN = %s;";
                $prepared_query = $wpdb->prepare($query, $gtin);
                $results = $wpdb->get_results($prepared_query, ARRAY_A);
                foreach ($results as $result) {
                    update_field('weight', $result["weight"], $pid);
                    break;
                }

                // fallback to data from attributes
                if (empty(get_field("weight", $pid))) {
                    $product = wc_get_product($pid);
                    if ($product) {
                        // Get all attributes of the product
                        $attributes = $product->get_attributes();

                        // Check if the "Quantité" attribute exists
                        if (isset($attributes['quantite'])) {
                            // Get the "Quantité" attribute
                            $quantite_attribute = $attributes['quantite'];

                            if ($quantite_attribute->is_taxonomy()) {
                                // If it's a taxonomy-based attribute, get the terms
                                $terms = wp_get_post_terms($pid, $quantite_attribute->get_name(), array('fields' => 'names'));
                                update_field('weight', implode(', ', $terms), $pid);
                            }
                            else {
                                update_field('weight', $quantite_attribute->get_options()[0], $pid);
                            }
                        }
                    }
                }
            }

            // short description: do not touch as filling these triggers render of new template

            // details:
            $atts = get_field("details", $pid);
            $atts_mod = false;

            // format
            if (empty($atts["format"])) {
                $atts["format"] = (!empty(get_field("weight", $pid))) ? get_field("weight", $pid) : "";
                $atts_mod = true;
            }

            // effects
            //    if (empty($atts["effets_potentiels"])) {
            //        $atts_mod = true;
            //    }

            // lieu
            if (empty($atts["origin"])) {
                $atts["origin"] = "Québec";
                $atts_mod = true;
            }

            // variety
            //    if (empty($atts["variety"])) {
            //        $atts_mod = true;
            //    }

            // terpenes
            if (empty($atts["terpenes"])) {
                $atts["terpenes"] = "Varient selon les rotations";
                $atts_mod = true;
            }

            // distribution
            if (empty($atts["distribution"])) {
                $atts["distribution"] = "En ligne – En magasin";
                $atts_mod = true;
            }

            if ($atts_mod)
                update_field('details', $atts, $pid);

        // products related: do not touch, automated
        // varieties related: do not touch, automated
        }
        catch (\Exception $e) {
            bleuh_log("Error updating product forms for post ID {$post_id}: " . $e->getMessage());
        }
    }

    return true;
}

function bleuh_ontario_stores($GTIN)
{
    if (is_array($GTIN))
        $GTIN = $GTIN[0];
    global $wpdb;

    $query = "SELECT s.*, ST_X(s.location) AS longitude, ST_Y(s.location) as latitude
              FROM {$wpdb->prefix}bleuh_store_products sp
              LEFT JOIN {$wpdb->prefix}bleuh_stores s
                ON s.number = sp.store_number
              WHERE sp.GTIN = %s
              ORDER BY s.postal_code;";

    $prepared_query = $wpdb->prepare($query, $GTIN);
    return $wpdb->get_results($prepared_query, ARRAY_A);
}

function bleuh_fetch_products_by_variety($variety_title)
{
    global $wpdb;
    $current_language = apply_filters('wpml_current_language', null);

    $query = "SELECT DISTINCT v.SQDC_SKU
              FROM {$wpdb->prefix}bleuh_vars v
              LEFT JOIN {$wpdb->prefix}bleuh_lots l
                ON v.lot = l.lot
              LEFT JOIN {$wpdb->prefix}bleuh_products p
                ON p.GTIN = v.SQDC_SKU
              WHERE RemoveNonAlphabetic(l.variety_name) = RemoveNonAlphabetic(%s)
              ORDER BY v.SQDC_SKU;";

    $variety_title = bleuh_normalize_title(html_entity_decode($variety_title));

    $prepared_query = $wpdb->prepare($query, $variety_title);

    $results = $wpdb->get_results($prepared_query, ARRAY_A);

    // return product ids match on GTIN to h1 of posts
    $product_GTINs = [];
    foreach ($results as $result) {
        $product_GTINs[] = sanitize_text_field($result['SQDC_SKU']);
    }

    // Return early if no GTINs are found
    if (empty($product_GTINs)) {
        return [];
    }

    // Initialize the array to store prepared WHERE conditions
    $where_conditions = [];

    // Loop through each GTIN and prepare the condition
    foreach ($product_GTINs as $product_GTIN) {
        // Add a prepared condition for each GTIN
        $where_conditions[] = $wpdb->prepare("pm.meta_value LIKE %s", '%' . $wpdb->esc_like($product_GTIN) . '%');
    }

    // Combine all conditions with `OR`
    $where_clause = implode(' OR ', $where_conditions);

    // Complete the query string with the safe WHERE clause
    $query = "
        SELECT p.ID, pm.meta_value as product_url
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'product'
          AND p.post_status = 'publish'
          AND pm.meta_key = '_product_url'
          AND ( $where_clause )
    ";

    // Execute the query
    $results = $wpdb->get_results($query);

    $pids = [];

    foreach ($results as $result) {
        foreach ($product_GTINs as $gtin) {
            $language_details = apply_filters('wpml_post_language_details', null, $result->ID);
            if (!$language_details || !isset($language_details['language_code'])) {
                continue;
            }
            if ($current_language == $language_details['language_code']) {
                if (strpos($result->product_url, $gtin) !== false) {
                    $pids[$gtin] = $result->ID;
                    break;
                }
                elseif (get_field('gtin', $result->ID) == $gtin) {
                    $pids[$gtin] = $result->ID;
                }
            }
        }
    }

    return $pids;
}

function bleuh_fetch_related_varieties($variety_id)
{
    // get variety strain
    $categories = wp_get_post_terms($variety_id, 'featured_item_category');
    // Extract the slug of the first category (if it exists)
    if (!empty($categories) && !is_wp_error($categories)) {
        $first_category_slug = $categories[0]->slug; // Get the first category's slug
        // Query posts in the category
        $current_language = apply_filters('wpml_current_language', null);
        // Query posts in the custom taxonomy and current language
        $query_args = [
            'post_type' => 'featured_item', // Custom post type
            'post_status' => 'publish',
            'posts_per_page' => -1, // Get all posts
            'orderby' => 'date', // Order by date
            'order' => 'DESC', // Newest posts first
            'fields' => 'ids', // Return only post IDs
            'post__not_in' => [$variety_id],
            'tax_query' => [
                'relation' => 'AND', // Combine multiple taxonomies
                [
                    'taxonomy' => 'featured_item_category', // Custom taxonomy
                    'field' => 'slug', // Filter by slug
                    'terms' => $first_category_slug, // Slug of the category
                ],
            ],
            'lang' => $current_language, // Filter by WPML post language
            'suppress_filters' => false, // Ensure WPML filters apply
        ];

        $query = new WP_Query($query_args);

        // Return the posts
        if ($query->have_posts()) {
            return $query->posts; // Returns an array of post ids
        }
    }
    return [];
}

// TODO: add info "i" button and learn more
function bleuh_render_products($swiper_id, $related_products, $with_link = false)
{
    if (empty($related_products))
        return '';

    $related_products = array_slice($related_products, 0, BLEUH_MAX_CAROUSEL_ITEMS);

    // Preload caches to avoid repeated queries
    update_meta_cache('post', $related_products);
    update_object_term_cache($related_products, 'product');

    $like_data = get_fav_counts($related_products);
    ob_start(); ?>

    <div class="swiper bleuh-swiper-responsive bleuh-ca-1" id="bswipe-<?php echo esc_attr($swiper_id); ?>">
        <div class="swiper-wrapper">
            <?php foreach ($related_products as $pid):

        $product = wc_get_product($pid);
        if (!$product)
            continue;

        $meta = get_post_meta($pid);
        $likes = $like_data[$pid]['count'] ?? 0;
        $liked = !empty($like_data[$pid]['liked']) ? 'liked' : 'not-liked';
        $stock_class = $product->is_in_stock() ? '' : ' out-of-stock';
        $terms = get_the_terms($pid, 'product_cat');
        $cat_name = $terms && !is_wp_error($terms) ? $terms[0]->name : '';
?>
                <div class="swiper-slide">
                    <div class="product-small col has-hover<?php echo esc_attr($stock_class); ?> <?php echo esc_attr(implode(' ', get_post_class('', $pid))); ?>">
                        <div class="col-inner">

                            <div class="badge-container absolute left top z-1"></div>

                            <div class="product-small box ">
                                <div class="box-image">
									<span data-id="<?php echo esc_attr($pid); ?>" class="like-box <?php echo esc_attr($liked); ?>">
										<span class="counter"><?php echo intval($likes); ?></span><i class="ico"></i>
									</span>

                                    <div class="image-fade_in_back">
                                        <a href="<?php echo esc_url(get_permalink($pid)); ?>" aria-label="<?php echo esc_attr($product->get_name()); ?>">
                                            <?php echo wp_get_attachment_image($product->get_image_id(), 'woocommerce_thumbnail'); ?>

                                            <?php
        // get gallery images (array of attachment IDs)
        $gallery_ids = $product->get_gallery_image_ids();

        if (!empty($gallery_ids)) {
            $first_gallery_id = $gallery_ids[0];

            echo wp_get_attachment_image(
                $first_gallery_id,
                'woocommerce_thumbnail',
                false,
            [
                'class' => 'show-on-hover absolute fill hide-for-small back-image',
                'alt' => 'Alternative view of ' . esc_attr($product->get_name()),
                'aria-hidden' => 'true',
            ]
            );
        }
?>
                                        </a>
                                    </div>

                                    <div class="image-tools is-small top right show-on-hover"></div>
                                    <div class="image-tools is-small hide-for-small bottom left show-on-hover"></div>
                                    <div class="image-tools grid-tools text-center hide-for-small bottom hover-slide-in show-on-hover"></div>
                                </div>

                                <div class="box-text box-text-products">
                                    <div class="title-wrapper">
                                        <?php if ($cat_name): ?>
                                            <p class="category uppercase is-smaller no-text-overflow product-cat op-8" style="color: rgb(255,131,0);" data-color="is_set">
                                                <?php echo esc_html($cat_name); ?>
                                            </p>
                                        <?php
        endif; ?>

                                        <p class="name product-title woocommerce-loop-product__title">
                                            <a href="<?php echo esc_url(get_permalink($pid)); ?>" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">
                                                <?php echo esc_html($product->get_name()); ?>
                                            </a>
                                        </p>
                                        <span class="custom-attribute">
											<?php echo esc_html($product->get_attribute('pa_intensite')); ?>
										</span><br>
                                        <span class="custom-attribute">
											<?php echo esc_html($product->get_attribute('pa_quantite')); ?>
										</span>
                                    </div>
                                    <div class="price-wrapper"><?php echo $product->get_price_html(); ?></div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" class="wpmProductId" data-id="<?php echo esc_attr($pid); ?>">

                        <script>
                            (window.wpmDataLayer = window.wpmDataLayer || {}).products = window.wpmDataLayer.products || {};
                            window.wpmDataLayer.products[<?php echo $pid; ?>] = {
                                "id": "<?php echo $pid; ?>",
                                "sku": "<?php echo esc_js($product->get_sku()); ?>",
                                //"price": <?php //echo floatval( $product->get_price() ); ?>//,
                                "brand": "",
                                "quantity": 1,
                                "dyn_r_ids": {
                                    "post_id": "<?php echo $pid; ?>",
                                    "sku": "<?php echo esc_js($product->get_sku()); ?>",
                                    "gpf": "woocommerce_gpf_<?php echo $pid; ?>",
                                    "gla": "gla_<?php echo $pid; ?>"
                                },
                                "is_variable": <?php echo $product->is_type('variable') ? 'true' : 'false'; ?>,
                                "type": "<?php echo esc_js($product->get_type()); ?>",
                                "name": "<?php echo esc_js($product->get_name()); ?>",
                                "category": ["<?php echo esc_js($cat_name); ?>"],
                                "is_variation": false
                            };
                            window.pmw_product_position = window.pmw_product_position || 1;
                            window.wpmDataLayer.products[<?php echo $pid; ?>]['position'] = window.pmw_product_position++;
                        </script>
                    </div>
                </div>
            <?php
    endforeach; ?>

            <div class="swiper-slide more-see-all" style="background-color:rgb(15,68,191);border-color:rgb(2,23,96);border-width:1px">
                <a href="<?php echo ICL_LANGUAGE_CODE == 'fr' ? '/produits/' : '/en/products/'; ?>">
                    <?php echo(ICL_LANGUAGE_CODE == 'fr') ? 'Voir tous les produits' : 'See all products'; ?>
                </a>
            </div>
        </div>
        <a href="#" class="bleuh-prod-swiper-prev"> &lt; </a>
        <a href="#" class="bleuh-prod-swiper-next"> &gt; </a>
        <div class="swiper-pagination-wrapper"><div class="swiper-pagination"></div></div>
    </div>

    <?php
    return ob_get_clean();
}
function bleuh_render_posts($related_posts)
{
    if (empty($related_posts)) {
        return '';
    }

    // cache the final HTML for, say, 10 minutes
    $cache_key = 'bleuh_posts_' . md5(serialize($related_posts));
    if ($html = get_transient($cache_key)) {
        return $html;
    }

    // pre‑cache meta & terms
    update_meta_cache('post', $related_posts);
    update_object_term_cache($related_posts, 'featured_item_category');

    $like_data = get_fav_counts($related_posts);

    $query = new WP_Query([
        'post_type' => 'featured_item',
        'post__in' => $related_posts,
        'orderby' => 'date',
        'order' => 'DESC',
        'posts_per_page' => BLEUH_MAX_CAROUSEL_ITEMS,
        'no_found_rows' => true,
        'cache_results' => true,
        'update_post_term_cache' => true,
        'update_post_meta_cache' => true,
    ]);

    ob_start(); ?>
    <div class="swiper bleuh-swiper-responsive bleuh-ca-2">
        <div class="swiper-wrapper">
            <?php
    while ($query->have_posts()) {
        $query->the_post();
        $pid = get_the_ID();

        // meta in one call
        $meta = get_post_meta($pid);
        $is_new = !empty($meta['is_new'][0]);
        $is_new_var = !empty($meta['is_new_var'][0]);
        $thc = $meta['thc'][0] ?? '';
        $wrap_date = !empty($meta['date_demballage'][0]) ? bleuh_display_em_date($meta['date_demballage'][0]) : '';

        // build category list from cache
        $terms = get_the_terms($pid, 'featured_item_category');
        $cat_list = $terms ? wp_list_pluck($terms, 'name') : [];

        // pick image
        $img_no_txt = get_field('img_no_txt', $pid);
        $img_url = '';
        if (is_array($img_no_txt) && !empty($img_no_txt['url'])) {
            // "Image Array" format
            $img_url = $img_no_txt['url'];
        }
        elseif (is_numeric($img_no_txt)) {
            // "Image ID" format (integer or numeric string)
            $attachment_url = wp_get_attachment_url((int)$img_no_txt);
            if ($attachment_url) {
                $img_url = $attachment_url;
            }
        }
        elseif (is_string($img_no_txt) && filter_var($img_no_txt, FILTER_VALIDATE_URL)) {
            // "Image URL" format
            $img_url = $img_no_txt;
        }
        if (empty($img_url)) {
            $img_url = get_the_post_thumbnail_url($pid, 'medium');
        }
        $likes = $like_data[$pid]['count'] ?? 0;
        $liked = !empty($like_data[$pid]['liked']) ? 'liked' : 'not-liked';
?>
                <div class="swiper-slide">
                    <div style="flex:1;padding:10px;box-sizing:border-box;">
                        <a class="var-box-link" href="<?php the_permalink(); ?>" style="text-decoration:none;color:inherit;">
                    <span data-id="<?php echo $pid; ?>"
                          class="like-box <?php echo $liked; ?>">
                        <span class="counter"><?php echo intval($likes); ?></span><i class="ico"></i>
                    </span>

                            <?php
        // badges
        if (($is_new || $is_new_var) && !empty($img_no_txt)) {
            $img_badge = '';
            if (!$is_new_var) {
                $img_badge = (ICL_LANGUAGE_CODE === 'fr') ? 'nouveau.png' : 'new.png';
            }
            else {
                $img_badge = (ICL_LANGUAGE_CODE === 'fr') ? 'nouvelle-variete.svg' : 'new-strain.svg';
            }
?>
                                <span class="bleuh-var-fixed-attrs">
                            <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../img/lots/' . $img_badge); ?>"
                                 alt="badge" class="new-badge"><br>
                            <?php if ($thc): ?><span class="thc">THC:<?php echo esc_html($thc); ?></span><?php
            endif; ?>
                                    <?php if ($wrap_date): ?><span class="wrap-date"><?php echo esc_html($wrap_date); ?></span><?php
            endif; ?>
                        </span>
                            <?php
        }?>

                            <?php if ($img_url): ?>
                                <img src="<?php echo esc_url($img_url); ?>"
                                     alt="<?php echo esc_attr(get_the_title()); ?>"
                                     style="width:100%;height:auto;">
                            <?php
        endif; ?>

                            <h3 style="margin-top:10px;font-size:1.2rem;"><?php the_title(); ?></h3>
                            <p class="category"><?php echo esc_html(implode(', ', $cat_list)); ?></p>
                        </a>
                    </div>
                </div>
            <?php
    } // loop ?>
            <div class="swiper-slide more-see-all"
                 style="height:auto;background-color:rgb(15,68,191);border:1px solid rgb(2,23,96);">
                <a href="<?php echo(ICL_LANGUAGE_CODE == 'fr') ? '/varietes/' : '/en/strains/'; ?>">
                    <?php echo(ICL_LANGUAGE_CODE == 'fr') ? 'Voir tous les variétés' : 'See all varieties'; ?>
                </a>
            </div>
        </div>
        <a href="#" class="bleuh-prod-swiper-prev">&lt;</a>
        <a href="#" class="bleuh-prod-swiper-next">&gt;</a>
        <div class="swiper-pagination-wrapper"><div class="swiper-pagination"></div></div>
    </div>
    <?php
    wp_reset_postdata();
    $html = ob_get_clean();
    set_transient($cache_key, $html, MINUTE_IN_SECONDS * 10);

    return $html;
}

function bleuh_import_ontario_stores(string $filename): bool
{
    try {

        if (isset($_FILES["xlsx"]["error"])) {
            if ($_FILES["xlsx"]["error"] !== UPLOAD_ERR_OK) {
                bleuh_log("Store File upload error: " . bleuh_file_upload_error_message($_FILES["xlsx"]["error"]));
                return false;
            }
        }

        // read XLSX
        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($filename);

        global $wpdb;

        try {
            $wpdb->query('START TRANSACTION');
            $wpdb->query("DELETE FROM {$wpdb->prefix}bleuh_stores WHERE number LIKE 'ONTARIO%';");
            $wpdb->query("DELETE FROM {$wpdb->prefix}bleuh_store_products WHERE store_number LIKE 'ONTARIO%';");
            $wpdb->query("DELETE FROM {$wpdb->prefix}bleuh_products WHERE is_ontario = 'Y';");

            $stores = [];
            $products_in_store = [];
            $on_products = [];

            foreach ($reader->getSheetIterator() as $s => $sheet) {
                if ($s == ON_STORES_SHEET) {
                    foreach ($sheet->getRowIterator() as $i => $row) {
                        // skip Excel Header
                        if ($i >= 1) {
                            try {
                                $cells = $row->getCells();

                                $Store_name = $cells[ON_STORES_NAME]->getValue();
                                $Store_number = 'ONTARIO';
                                $Store_address = $cells[ON_STORES_ADDRESS]->getValue();
                                $postal_code_pattern = "/[A-Z][0-9][A-Z][0-9][A-Z][0-9]/"; // Canadian postal code format
                                $postal_code_with_space_pattern = "/[A-Z][0-9][A-Z]\s[0-9][A-Z][0-9]/"; // Canadian postal code format with space
                                $postal_code = '';
                                if (preg_match($postal_code_pattern, strtoupper($Store_address), $matches)) {
                                    $postal_code = $matches[0];
                                    $postal_code = substr($postal_code, 0, 3) . ' ' . substr($postal_code, 3);
                                }
                                elseif (preg_match($postal_code_with_space_pattern, strtoupper($Store_address), $matches)) {
                                    // Match postal code that already includes a space
                                    $postal_code = $matches[0];
                                }

                                $Store_number .= '-' . $postal_code;
                                $GTIN = $cells[ON_STORES_GTIN]->getValue();
                                $GTIN = ltrim(trim($GTIN), '0');
                                $qty = $cells[ON_STORES_QTY]->getValue();

                                // address text manipulation
                                $address_manipulation = explode("\n", $Store_address);
                                $road = ucwords(strtolower($address_manipulation[0]));
                                $city_and_postal_code = ucwords(strtolower($address_manipulation[1]));
                                if (preg_match('/\b([A-Za-z]\d[A-Za-z]\s?\d[A-Za-z]\d)\b/', $city_and_postal_code, $matches)) {
                                    // Extract and format the postal code
                                    $postalCode = strtoupper(str_replace(' ', '', $matches[1])); // Remove any existing spaces and convert to uppercase
                                    $formattedPostalCode = substr($postalCode, 0, 3) . ' ' . substr($postalCode, 3);

                                    // Replace the postal code within the original string
                                    $city_and_postal_code = str_replace($matches[1], $formattedPostalCode, $city_and_postal_code);
                                }
                                $city_and_postal_code = str_replace('On', 'Ontario,', $city_and_postal_code);
                                $city_and_postal_code = str_replace(',,', ',', $city_and_postal_code);

                                $country = ucwords(strtolower($address_manipulation[2]));
                                if (trim(strtoupper($country)) === 'CAN' || trim(strtoupper($country)) === 'CA') {
                                    $country = 'Canada';
                                }

                                $Store_address = $road . "\n" . $city_and_postal_code . "\n" . $country;

                                $this_data = [
                                    "number" => trim($Store_number),
                                    "postal_code" => trim(strtoupper($postal_code)),
                                    "name" => trim($Store_name),
                                    "address" => trim($Store_address),
                                    "location_lat" => 0,
                                    "location_lng" => 0,
                                ];

                                if (!empty($this_data["postal_code"])) {
                                    $stores[$this_data["postal_code"]] = $this_data;
                                }

                                if (!empty($Store_number) && !empty($GTIN) && !empty($qty)) {
                                    $qty = intval($qty);
                                    if (isset($products_in_store[$Store_number . '-' . $GTIN])) {
                                        $products_in_store[$Store_number . '-' . $GTIN] = [
                                            "store_number" => $Store_number,
                                            "GTIN" => $GTIN,
                                            "qty" => $products_in_store[$Store_number . '-' . $GTIN]["qty"] + $qty,
                                        ];
                                    }
                                    else {
                                        $products_in_store[$Store_number . '-' . $GTIN] = [
                                            "store_number" => $Store_number,
                                            "GTIN" => $GTIN,
                                            "qty" => $qty,
                                        ];
                                    }
                                }

                                if (!empty($GTIN)) {
                                    $theblend = 'Mélange';
                                    $theblend_en = "Mix";
                                    $within_blend = $cells[ON_STORES_P_BLEND]->getValue();
                                    if (strpos(strtolower($within_blend), 'indica') !== false) {
                                        $theblend = 'Indica';
                                        $theblend_en = 'Indica';
                                    }
                                    elseif (strpos(strtolower($within_blend), 'sativa') !== false) {
                                        $theblend = 'Sativa';
                                        $theblend_en = 'Sativa';
                                    }
                                    elseif (strpos(strtolower($within_blend), 'hybrid') !== false) {
                                        $theblend = 'Hybride';
                                        $theblend_en = 'Hybrid';
                                    }
                                    elseif (strpos(strtolower($within_blend), 'hash') !== false) {
                                        $theblend = 'Hash';
                                        $theblend_en = 'Hash';
                                    }

                                    $weight = $cells[ON_STORES_P_WEIGHT]->getValue();
                                    $the_weight = '';
                                    if (preg_match('/_(.*?)_/', $weight, $matches)) {
                                        $the_weight = $matches[1]; // $matches[1] contains the value
                                    }

                                    $the_format = $cells[ON_STORES_P_FORMAT]->getValue();
                                    $the_format_fr = $the_format;
                                    if (strpos(strtolower($within_blend), 'flower') !== false) {
                                        $the_format_fr = 'Fleurs séchées';
                                    }
                                    elseif (strpos(strtolower($within_blend), 'pre-rolled') !== false) {
                                        $the_format_fr = 'Préroulés';
                                    }
                                    elseif (strpos(strtolower($within_blend), 'concentrates') !== false) {
                                        $the_format_fr = 'Haschich';
                                    }

                                    if (is_numeric($GTIN)) {
                                        // GTIN, collection, name, blend, format, weight, blend_en, format_en, is_ontario
                                        $on_products[$GTIN] = [
                                            "GTIN" => $GTIN,
                                            "collection" => strtoupper($cells[ON_STORES_P_COLLECTION]->getValue()),
                                            "name" => $cells[ON_STORES_P_NAME]->getValue(),
                                            "blend" => $theblend,
                                            "format" => $the_format_fr,
                                            "weight" => $the_weight,
                                            "blend_en" => $theblend_en,
                                            "format_en" => $cells[ON_STORES_P_FORMAT]->getValue(),
                                            "is_ontario" => 'Y',
                                        ];
                                    }
                                }

                            }
                            catch (\Exception $e) {
                                // Handle the exception, perhaps log it or set a default value
                                bleuh_log("Ontario stores import failed on XLSX row: " . $e->getMessage());
                            }
                        }
                    }
                    break; // get data from single page sheet
                }
            }

            $stores = array_map('serialize', $stores);
            $stores = array_unique($stores);
            $stores = array_map('unserialize', $stores);

            $products_in_store = array_map('serialize', $products_in_store);
            $products_in_store = array_unique($products_in_store);
            $products_in_store = array_map('unserialize', $products_in_store);

            // save stores data
            $values = [];
            $place_holders = [];
            $query = "INSERT INTO {$wpdb->prefix}bleuh_stores (`number`, `postal_code`, `name`, `DailyAddressBlock`, `location`) VALUES ";
            foreach ($stores as $store) {
                $values = array_merge($values, array_values($store));
                $place_holders[] = "( '%s', '%s', '%s', '%s', POINT(%8f, %8f) )";
            }
            $query .= implode(', ', $place_holders);
            $query = $wpdb->prepare($query, $values);
            $wpdb->query($query);

            // save product and store associations data
            $values = [];
            $place_holders = [];
            $query = "INSERT INTO {$wpdb->prefix}bleuh_store_products (`store_number`, `GTIN`, `qty`) VALUES ";
            foreach ($products_in_store as $pro) {
                $values = array_merge($values, array_values($pro));
                $place_holders[] = "( '%s', '%s', '%d')";
            }
            $query .= implode(', ', $place_holders);
            $query = $wpdb->prepare($query, $values);
            $wpdb->query($query);

            // save ontario products data
            $values = [];
            $place_holders = [];
            $query = "INSERT INTO {$wpdb->prefix}bleuh_products (`GTIN`, `collection`, `name`, `blend`, `format`, `weight`, `blend_en`, `format_en`, `is_ontario`) VALUES ";
            foreach ($on_products as $pro) {
                $values = array_merge($values, array_values($pro));
                $place_holders[] = "( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )";
            }
            $query .= implode(', ', $place_holders);
            $query = $wpdb->prepare($query, $values);
            $wpdb->query($query);

            $wpdb->query('COMMIT');

            bleuh_fill_store_geo_location();
            return true;
        }
        catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            bleuh_log("Ontario stores, products and associations import failed: " . $e->getMessage());
            return false;
        }
        finally {
            // close reader
            $reader->close();
        }

    }
    catch (\Exception $e) {
        bleuh_log("Ontario stores, products and associations import failed from xlsx: " . $e->getMessage());
        return false;
    }
}

function bleuh_get_deprecated($post_type)
{
    $ret = [];
    $post_type = ($post_type == 'varieties') ? 'featured_item' : 'product';

    // Define the post type and ACF field name
    $acf_field = 'short_description'; // Replace 'z' with your ACF field name

    // Query arguments
    $args = array(
        'post_type' => $post_type,
        'posts_per_page' => -1, // Retrieve all posts
        'meta_query' => array(
                array(
                'key' => $acf_field, // The ACF field name
                'compare' => 'NOT EXISTS' // Field does not exist
            ),
                array(
                'key' => $acf_field, // The ACF field name
                'value' => '', // Empty value
                'compare' => '=' // Match empty string
            ),
            'relation' => 'OR' // Either condition can be true
        )
    );

    // Run the query
    $query = new WP_Query($args);

    // Check if posts are found
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $ret[] = '<a href="https://' . $_SERVER['HTTP_HOST'] . '/wp-admin/post.php?post=' . get_the_ID() . '&action=edit">' . get_the_title() . '</a>';
        }
    }

    // Restore original post data
    wp_reset_postdata();

    return $ret;
}

function get_missing_varieties()
{
    // get all unique varieties titles
    global $wpdb;

    try {

        // get all used varieties
        $query = "SELECT l.variety_name, v.qty
                  FROM {$wpdb->prefix}bleuh_vars v
                  LEFT JOIN {$wpdb->prefix}bleuh_lots l
                    ON TRIM(LOWER(v.lot)) = TRIM(LOWER(l.lot))
                  WHERE v.qty > 0
                  ORDER BY v.order_weight";


        $results = $wpdb->get_results($query, ARRAY_N);

        $varieties = [];
        $qty_counts = [];
        foreach ($results as $row) {
            if ((trim($row[0]) !== "mélange") && (trim($row[0]) !== "")) {
                $normnalized_title = bleuh_normalize_title($row[0]);
                $varieties[$normnalized_title] = $row[0];
                if (!array_key_exists($normnalized_title, $qty_counts)) {
                    $qty_counts[$normnalized_title] = 0;
                }
                if (is_numeric($row[1]) && $row[1] > 0) {
                    $qty_counts[$normnalized_title] += $row[1];
                }
            }
        }

        // get all featured_item post titles
        $posts = new WP_Query([
            'suppress_filters' => false,
            'post_type' => 'featured_item',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids' // Only get post IDs to reduce memory usage
        ]);

        // get all used titles
        $featured_items = [];
        $post_content_h1 = [];
        if ($posts->have_posts()) {
            foreach ($posts->posts as $post_id) {

                // get normal title
                $post_title = get_the_title($post_id);
                $normalized_title = bleuh_normalize_title($post_title);
                $featured_items[$normalized_title] = $post_title;

                // get content title
                $post_content = get_post_field('post_content', $post_id);
                $matches = [];
                preg_match_all('/<h1.*?>(.*?)<\/h1>/si', $post_content, $matches);
                $h1 = $matches[1] ?? []; // $matches[1] contains the text inside the <h1> tags
                if (!empty($h1)) {
                    $h1 = strip_tags($h1[0]);
                    $normalized_title = bleuh_normalize_title($h1);
                    $post_content_h1[$normalized_title] = $h1;
                }

            }
        }

        // find missing varieties
        $missing_varieties = [];
        foreach ($varieties as $variety) {
            $normalized_title = bleuh_normalize_title($variety);
            if (!array_key_exists($normalized_title, $featured_items)) {
                if (!array_key_exists($normalized_title, $post_content_h1)) {
                    $missing_varieties[$normalized_title] = $variety;
                }
            }
        }

        foreach ($missing_varieties as $key => $value) {
            $missing_varieties[$key] = $value . " (" . number_format($qty_counts[$key]) . ")";
        }

        $products = [];
        foreach ($missing_varieties as $variety) {
            $products[$variety] = bleuh_fetch_products_by_variety($variety);
        }

        $final_var_list = [];

        // get product gtins with product ids in $products
        foreach ($products as $variety => $product_ids) {
            // get GTIN from ACF
            foreach ($product_ids as $pid) {
                // ACF default
                $gtin = get_field('gtin', $pid);
                // fallback to gtin from product URL
                if (empty($gtin)) {
                    $gtin = get_post_meta($pid, '_product_url', true);
                    $gtin = explode('/', $gtin);
                    $gtin = end($gtin);
                }

                if (!empty($gtin)) {
                    // check db if flower strain exists
                    global $wpdb;
                    $query = "SELECT `name` p_title, `format` fmat, `GTIN` gtin
                              FROM {$wpdb->prefix}bleuh_products
                              WHERE GTIN LIKE %s
                                AND format LIKE '%%fleur%%';";
                    $prepared_query = $wpdb->prepare($query, $gtin);
                    $results = $wpdb->get_results($prepared_query, ARRAY_A);

                    if (!empty($results)) {
                        $variety = bleuh_normalize_title($variety);
                        $final_var_list[$variety] = $missing_varieties[$variety] . " (GTIN: " . $gtin . "), (Product: " . $results[0]['p_title'] . ")";
                    }
                    else { //                        unset($missing_varieties[$variety]);
                    }
                }
                else { //                    unset($missing_varieties[$variety]);
                }

            }
        }

        return $final_var_list;
    }
    catch (\Exception $e) {
        bleuh_log("Get missing varieties failed: " . $e->getMessage());
        return [];
    }
}

function get_products_by_language($post_type, $language_code)
{
    // WP_Query to fetch products in a specific language
    $args = array(
        'post_type' => $post_type, // WooCommerce product post type
        'posts_per_page' => -1, // Get all products
        'suppress_filters' => false, // Allow WPML to filter by language
        'post_status' => 'publish',
        'lang' => $language_code, // WPML language code
    );

    $query = new WP_Query($args);
    $products = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $products[get_the_ID()] = get_the_title(); // Store product ID and title
        }
    }
    wp_reset_postdata();

    return $products;
}

function find_missing_translations($post_type)
{
    // Get all English and French products
    $english_products = get_products_by_language($post_type, 'en');
    $french_products = get_products_by_language($post_type, 'fr');

    $missing_in_french = array();
    $missing_in_english = array();

    // Check English products for missing French translations
    foreach ($english_products as $product_id => $product_title) {
        $french_translation_id = apply_filters('wpml_object_id', $product_id, $post_type, false, 'fr');
        if (!$french_translation_id) {
            $missing_in_french[$product_id] = $product_title;
        }
    }

    // Check French products for missing English translations
    foreach ($french_products as $product_id => $product_title) {
        $english_translation_id = apply_filters('wpml_object_id', $product_id, $post_type, false, 'en');
        if (!$english_translation_id) {
            $missing_in_english[$product_id] = $product_title;
        }
    }

    return array(
        'missing_in_french' => $missing_in_french,
        'missing_in_english' => $missing_in_english,
    );
}

function bleuh_deliveries($store_number = false, $gtins = [])
{
    global $wpdb;

    $where_conditions = [];
    $query_params = [];

    // Add store number condition if provided
    if ($store_number) {
        $where_conditions[] = "TRIM(LOWER(d.store_number)) = TRIM(LOWER(%s))";
        $query_params[] = trim(strtolower($store_number));
    }

    // Add GTINs condition if the array is not empty
    if (!empty($gtins)) {
        $gtin_placeholders = implode(', ', array_fill(0, count($gtins), '%s')); // Prepare placeholders for GTINs
        $where_conditions[] = "d.GTIN IN ($gtin_placeholders)";
        $query_params = array_merge($query_params, $gtins); // Merge GTINs into query parameters
    }

    // Build the WHERE clause of the SQL query
    $where_sql = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Prepare the full SQL query
    $query = $wpdb->prepare(
        "SELECT d.*
        FROM {$wpdb->prefix}bleuh_store_deliveries d
        $where_sql
        ORDER BY d.latest_sunday DESC, d.store_number, d.GTIN, d.lot",
        $query_params
    );

    // Execute the query and return the results
    return $wpdb->get_results($query, ARRAY_A);
}

function bleuh_master_varieties()
{
    global $wpdb;

    $query = "SELECT DISTINCT l.*
              FROM {$wpdb->prefix}bleuh_lots l
              ORDER BY l.variety_name";

    return $wpdb->get_results($query, ARRAY_A);
}

// get varieties info
function bleuh_varieties($store_number = false, $gtins = [])
{
    global $wpdb;
    $prepared_gtins = array_map('esc_sql', $gtins); // Sanitize GTINs for direct query usage

    // Build the WHERE clause components based on supplied parameters
    $store_query_part = $store_number ? $wpdb->prepare(" AND TRIM(LOWER(v.store_number)) = TRIM(LOWER(%s))", trim(strtolower($store_number))) : "";
    $gtins_query_part = !empty($prepared_gtins) ? " AND v.SQDC_SKU IN ('" . implode("','", $prepared_gtins) . "')" : "";

    // Complete SQL query
    $query = "SELECT v.*, COALESCE(l.variety_name, 'Mélange') as variety_name, l.lot, l.THC, l.CBD, l.wrap_date
              FROM {$wpdb->prefix}bleuh_vars v
              LEFT JOIN {$wpdb->prefix}bleuh_lots l
                ON TRIM(LOWER(v.lot)) = TRIM(LOWER(l.lot))
              WHERE v.qty > 0
                $gtins_query_part
                $store_query_part
              ORDER BY v.order_weight";

    $varieties = $wpdb->get_results($query, ARRAY_A);

    // Get all featured_item post titles and permalinks in one query
    $posts = new WP_Query([
        'suppress_filters' => false,
        'lang' => ICL_LANGUAGE_CODE,
        'post_type' => 'featured_item',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'fields' => 'ids' // Only get post IDs to reduce memory usage
    ]);

    $permalinks = [];
    if ($posts->have_posts()) {
        foreach ($posts->posts as $post_id) {
            $post_title = get_the_title($post_id);
            $normalized_title = bleuh_normalize_title($post_title);
            $permalinks[$normalized_title] = get_permalink($post_id);
        }
    }

    // Map the permalinks to the varieties
    foreach ($varieties as &$variety) {
        $normalized_variety_name = bleuh_normalize_title($variety['variety_name']);
        $variety['permalink'] = $permalinks[$normalized_variety_name] ?? '#';
    }

    return $varieties;
}

function bleuh_get_buy_links($gtins)
{
    if (empty($gtins))
        return [];

    global $wpdb;

    // Query the posts and their meta to find matching GTINs in the product URLs
    $query = "
        SELECT p.ID, pm.meta_value as product_url
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'product'
          AND p.post_status = 'publish'
          AND pm.meta_key = '_product_url'
          AND ( " . implode(' OR ', array_map(function ($gtin) {
        return "pm.meta_value LIKE '%" . esc_sql($gtin) . "%'";
    }, $gtins)) . " )
    ";

    $results = $wpdb->get_results($query);
    $buy_links = [];

    // Populate the $buy_links array with GTINs as keys and product URLs as values
    foreach ($results as $result) {
        foreach ($gtins as $gtin) {
            if (strpos($result->product_url, $gtin) !== false) {
                $buy_links[$gtin] = esc_url($result->product_url);
                break;
            }
        }
    }

    return $buy_links;
}


function bleuh_execute_parallel_curls($url, $data, $extra_data, $batch_size = 2)
{
    $responses = [];
    $lang = "fr-CA";
    $batches = array_chunk($data, $batch_size, true);

    foreach ($batches as $batch_key => $batch_data) {
        $multi_handle = curl_multi_init();
        $curl_handles = [];
        $batch_responses = [];

        foreach ($batch_data as $i => $this_data) {
            $headers = array(
                'Accept-Language: ' . $lang,
                'x-requested-with: XMLHttpRequest',
                'Content-Type: application/json',
                'Content-Length: ' . strlen($this_data)
            );

            $ch = curl_init($url);
            // On garde le User-Agent par sécurité
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this_data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            curl_multi_add_handle($multi_handle, $ch);
            $curl_handles[$i] = $ch;
        }

        $running = null;
        do {
            $status = curl_multi_exec($multi_handle, $running);
            if (curl_multi_select($multi_handle) === -1) {
                usleep(100);
            }
        } while ($status === CURLM_CALL_MULTI_PERFORM || $running);

        foreach ($curl_handles as $i => $ch) {
            $content = curl_multi_getcontent($ch);
            $decoded = json_decode($content, true);

            // On s'assure que si le décodage échoue, on a au moins un tableau vide
            $batch_responses[$i] = array_merge(($decoded ?? []), $extra_data[$i]);

            curl_multi_remove_handle($multi_handle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multi_handle);
        $responses = array_merge($responses, $batch_responses);
        unset($batch_responses, $curl_handles, $multi_handle);
    }
    return $responses;
}

function bleuh_import_deliveries(string $filename, $sunday): mixed
{
    try {
        // read XLSX
        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($filename);
        global $wpdb;
        try {
            $data = [];
            $store_number_col = DELIVERIES_DOC_STORE_NUMBER;
            $GTIN_col = DELIVERIES_DOC_GTIN;
            $lot_col = DELIVERIES_DOC_LOT;
            $qty_col = DELIVERIES_DOC_QTY;
            foreach ($reader->getSheetIterator() as $s => $sheet) {
                if ($s == 1) {
                    foreach ($sheet->getRowIterator() as $i => $row) {
                        if ($i <= 1) {
                            $cells = $row->getCells();
                            $idx = 0;
                            foreach ($cells as $cell) {
                                if (trim($cell->getValue() != "")) {
                                    if (str_contains(strtolower($cell->getValue()), "store")) {
                                        $store_number_col = $idx;
                                    }
                                    elseif (str_contains(strtolower($cell->getValue()), "part")) {
                                        $GTIN_col = $idx;
                                    }
                                    elseif (str_contains(strtolower($cell->getValue()), "product")) {
                                        $GTIN_col = $idx;
                                    }
                                    elseif (str_contains(strtolower($cell->getValue()), "lot")) {
                                        $lot_col = $idx;
                                    }
                                    elseif (str_contains(strtolower($cell->getValue()), "quantity")) {
                                        $qty_col = $idx;
                                    }
                                    elseif (str_contains(strtolower($cell->getValue()), "qty")) {
                                        $qty_col = $idx;
                                    }
                                }
                                $idx++;
                            }
                        }
                        // skip Excel Header
                        if ($i >= 2) {
                            $cells = $row->getCells();
                            if (isset($cells[$store_number_col])
                            && isset($cells[$GTIN_col])
                            && isset($cells[$lot_col])
                            && isset($cells[$qty_col])
                            ) {
                                $this_data = [
                                    "store_number" => trim($cells[$store_number_col]->getValue()),
                                    "GTIN" => trim($cells[$GTIN_col]->getValue()),
                                    "lot" => trim($cells[$lot_col]->getValue()),
                                    "qty" => trim($cells[$qty_col]->getValue()),
                                    "sunday" => date('Y-m-d H:i:s', $sunday),
                                ];

                                if (!empty($this_data["store_number"])) {
                                    $data[] = $this_data;
                                }
                            }
                        }
                    }
                    break;
                }
            }

            return $data;
        }
        catch (\Exception $e) {
            bleuh_log('ERROR: ' . $e->getMessage());
            return false;
        }
        finally {
            // close reader
            $reader->close();
        }

    }
    catch (\Exception $e) {
        bleuh_log($e->getMessage());
        return false;
    }
}
function bleuh_listFilesAndFolders($service, $folderId, &$filesArray = [])
{
    try {
        $parameters = [
            "supportsAllDrives" => true,
            'q' => "'{$folderId}' in parents and trashed=false",
            'pageSize' => 1000,
            'fields' => 'nextPageToken, files(id, name, mimeType, createdTime)',
            'orderBy' => 'createdTime desc',
            'includeItemsFromAllDrives' => true,
        ];

        do {
            $results = $service->files->listFiles($parameters);
            foreach ($results->getFiles() as $file) {
                if (in_array($file->getMimeType(), [
                'application/vnd.google-apps.spreadsheet',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                ])) {
                    $filesArray[] = [
                        'id' => $file->getId(),
                        'name' => $file->getName(),
                        'mimeType' => $file->getMimeType(),
                        'createdTime' => $file->getCreatedTime()
                    ];
                }
                if ($file->getMimeType() == 'application/vnd.google-apps.folder') {
                    bleuh_listFilesAndFolders($service, $file->getId(), $filesArray);
                }
            }
            $pageToken = $results->getNextPageToken();
            if ($pageToken) {
                $parameters['pageToken'] = $pageToken;
            }
            else {
                $parameters['pageToken'] = null;
            }
        } while ($pageToken);
    }
    catch (\Exception $ex) {
        bleuh_log('ERROR: ' . $ex->getMessage());
    }
    return $filesArray;
}

function bleuh_save_deliveries_data($delete_previous_dates = true): bool
{
    bleuh_update_option_with_date("bleuh_deliveries_trigger");
    global $wpdb;
    try {
        $latest_date_fetched = 0;
        $client = new Google_Client();
        $client->setApplicationName('Bleuh Drive API');
        $client->setScopes([
            Google_Service_Drive::DRIVE_METADATA_READONLY,
            Google_Service_Drive::DRIVE_READONLY,
            Google_Service_Drive::DRIVE_FILE,
        ]);
        $client->setAuthConfig(GOOGLE_SA_JSON);
        $service = new Google_Service_Drive($client);
        $allFiles = bleuh_listFilesAndFolders($service, DELIVERIES_DIR_ID);
        usort($allFiles, function ($a, $b) {
            return strtotime($b['createdTime']) - strtotime($a['createdTime']);
        });
        $top_dates = [];
        $i = 1;
        foreach ($allFiles as $key => $file) {
            $creationDate = new DateTime($file['createdTime']);
            // TODO: get latest date here?
            $dayOfWeek = $creationDate->format('w');
            if ($dayOfWeek != 0) {
                $creationDate->sub(new DateInterval('P' . $dayOfWeek . 'D'));
            }
            $creationDate->setTime(0, 0, 0);
            $lastSunday = $creationDate->getTimestamp();
            $allFiles[$key]["lastSunday"] = $lastSunday;
            if ($i <= DELIVERIES_UPDATE_BATCH_COUNT)
                $top_dates[] = "'" . date('Y-m-d H:i:s', $lastSunday) . "'";
            $i++;
        }

        $wpdb->query("DELETE FROM {$wpdb->prefix}bleuh_store_deliveries WHERE `latest_sunday` IN(" . implode(", ", $top_dates) . ");");
        $query = "SELECT DISTINCT `latest_sunday` FROM {$wpdb->prefix}bleuh_store_deliveries;";
        $results = $wpdb->get_results($query);
        $sundays = [];

        foreach ($results as $result) {
            $sundays[] = $result->latest_sunday;
        }
        unset($results);

        $allFiles = array_reverse($allFiles);

        $updated_docs = [];

        $i = 1;
        foreach ($allFiles as $google_file) {
            if (($google_file["lastSunday"] > strtotime(DELIVERIES_BATCH_MIN_DATE))
            && (!in_array(date('Y-m-d H:i:s', $google_file["lastSunday"]), $sundays))
            && ($i <= DELIVERIES_MANUAL_UPDATE_BATCH_COUNT)
            ) {
                $wpdb->query("DELETE FROM {$wpdb->prefix}bleuh_store_deliveries WHERE `latest_sunday` IN('" . date('Y-m-d H:i:s', $google_file["lastSunday"]) . "');");
                $downloaded = false;
                $file = $service->files->get($google_file["id"], array('fields' => 'mimeType', "supportsAllDrives" => true));
                $mimeType = $file->getMimeType();
                $temp_file = tempnam(sys_get_temp_dir(), 'bleuh-deliveries');
                if ($mimeType === 'application/vnd.google-apps.spreadsheet') {
                    $exportMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                    $response = $service->files->export($google_file["id"], $exportMimeType, array(
                        //'supportsAllDrives' => true,
                        'alt' => 'media'
                    ));
                    file_put_contents($temp_file, $response->getBody()->getContents());
                    $downloaded = true;
                }
                elseif ($mimeType === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                    $file = $service->files->get($google_file["id"], [
                        "supportsAllDrives" => true,
                        "alt" => "media",
                    ]);
                    file_put_contents($temp_file, $file->getBody()->getContents());
                    $downloaded = true;
                }
                else {
                    echo $mimeType . "<br>";
                }

                if ($downloaded) {
                    $data = bleuh_import_deliveries($temp_file, $google_file["lastSunday"]);
                    unlink($temp_file);
                    if ($data !== false) {
                        $values = [];
                        $place_holders = [];
                        $query = "INSERT INTO {$wpdb->prefix}bleuh_store_deliveries (store_number, GTIN, lot, qty, latest_sunday) VALUES ";
                        foreach ($data as $datum) {
                            $values = array_merge($values, array_values($datum));
                            $place_holders[] = "('%s', '%s', '%s', %d, '%s')";
                        }
                        $query .= implode(', ', $place_holders);
                        $query = $wpdb->prepare($query, $values);
                        $wpdb->query($query);
                        $updated_docs[] = $google_file["name"];
                        if ($latest_date_fetched < $google_file["lastSunday"])
                            $latest_date_fetched = $google_file["lastSunday"];
                    }
                    unset($data);
                }
                unset($file, $temp_file);
                $i++;
            }
        }
        $wpdb->query('COMMIT');
        bleuh_log("Deliveries imported (added/updated: " . implode(", ", $updated_docs) . ").");
        bleuh_update_varieties($service);

        $modifiedTime = new DateTime("@$latest_date_fetched");
        $timezone = new DateTimeZone(BLEUH_TIMEZONE);
        $modifiedTime->setTimezone($timezone);
        $dateString = $modifiedTime->format('Y-m-d H:i:s');
        $previous_date = get_option("bleuh_vars_import");
        $previousDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $previous_date);
        $latestDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
        if ($previousDateTime && $latestDateTime) {
            if ($previousDateTime < $latestDateTime) {
                clear_sg_cache();
            }
        }
        bleuh_update_option_with_date("bleuh_deliveries_success");
        return true;
    }
    catch (\Exception $ex) {
        $wpdb->query('ROLLBACK');
        bleuh_log("Deliveries data import failed: " . $ex->getMessage());
        return false;
    }
}

function bleuh_update_varieties($google_service)
{
    global $wpdb;
    $offsets = [];
    $file = $google_service->files->get(DELIVERIES_OFFSET_DOC_ID, [
        "supportsAllDrives" => true,
        "alt" => "media",
    ]);
    $temp_file = tempnam(sys_get_temp_dir(), 'bleuh-offsets');
    file_put_contents($temp_file, $file->getBody()->getContents());
    $reader = ReaderEntityFactory::createXLSXReader();
    $reader->open($temp_file);
    try {

        // read day of the week offset per store from excel sheet on Google Drive.
        foreach ($reader->getSheetIterator() as $s => $sheet) {
            if ($s == 1) {
                foreach ($sheet->getRowIterator() as $i => $row) {
                    // skip Excel Header
                    if ($i >= 2) {
                        $cells = $row->getCells();
                        if (isset($cells[DELIVERIES_OFFSET_DOC_COL_STORE_NUM])
                        && isset($cells[DELIVERIES_OFFSET_DOC_COL_OFFSET])
                        ) {
                            $offsets[trim($cells[DELIVERIES_OFFSET_DOC_COL_STORE_NUM]->getValue())]
                                 = $cells[DELIVERIES_OFFSET_DOC_COL_OFFSET]->getValue();
                            unset($cells, $this_data);
                        }
                    }
                }
                break;
            }
        }

        // get hierarchy:
        //      - store (number, postal code)
        //          - products (GTIN, live_available)
        //              - deliveries (date, LOT, qty)

        $query = "SELECT d.*, s.number, COALESCE(sp.qty, 0) qty_of_product_by_store
                  FROM {$wpdb->prefix}bleuh_store_deliveries d
                  LEFT JOIN {$wpdb->prefix}bleuh_stores s
                    ON s.number LIKE d.store_number
                  LEFT JOIN {$wpdb->prefix}bleuh_store_products sp
                    ON sp.GTIN = d.GTIN
                    AND TRIM(LOWER(sp.store_number)) LIKE TRIM(LOWER(s.number))
                  ORDER BY d.store_number, d.GTIN, d.latest_sunday DESC;";
        $results = $wpdb->get_results($query);

        $store = [];
        foreach ($results as $row) {
            $store_number = trim($row->store_number);
            $offset = DELIVERIES_OFFSET_DEFAULT;
            if (isset($offsets[$store_number])) {
                $offset = (int)$offsets[$store_number];
            }

            $store[$store_number]["store_number"] = $store_number;
            $store[$store_number]["products"][trim($row->GTIN)]["live_available"] = (int)trim($row->qty_of_product_by_store);
            $store[$store_number]["products"][trim($row->GTIN)]["deliveries"][] = [
                "date" => strtotime("{$row->latest_sunday} +{$offset} days"),
                "lot" => trim($row->lot),
                "qty" => (int)trim($row->qty),
            ];
        }
        unset($offsets);
    }
    catch (\Exception $e) {
        bleuh_log('ERROR: ' . $e->getMessage());
        return false;
    }
    finally {
        // close reader
        $reader->close();
        unset($file, $temp_file, $reader);
    }

    // reorder items with offset of delivery after sunday in consideration
    foreach ($store as $store_number => $store_data) {
        foreach ($store_data["products"] as $gtin => $product) {
            usort($store[$store_number]["products"][$gtin]["deliveries"], function ($a, $b) {
                return $b['date'] <=> $a['date'];
            });
        }
    }

    // get data to insert into available varieties database
    $to_insert = [];
    $order_weight = 0;
    foreach ($store as $store_number => $this_store) {

        // Iterate over each product to determine the available lots
        foreach ($this_store["products"] as $GTIN => $this_product) {

            $quantityAvailable = $this_product["live_available"];

            // Calculate which lots are still available, FIFO rules
            foreach ($this_product["deliveries"] as $this_delivery) {
                if ($quantityAvailable <= 0) {
                    // No more available stock, break out of the loop
                    break;
                }

                if ($quantityAvailable >= $this_delivery["qty"]) {
                    // This entire lot is still available
                    $order_weight++;
                    $to_insert[] = [
                        "GTIN" => $GTIN,
                        "lot" => $this_delivery["lot"],
                        "store_number" => $store_number,
                        "qty" => (int)$this_delivery["qty"],
                        "order_weight" => $order_weight,
                    ];

                    // Reduce the available quantity by the amount this lot contributed
                    $quantityAvailable -= $this_delivery["qty"];
                }
                else {
                    // Part of this lot is still available, but this is where we stop
                    $order_weight++;
                    $to_insert[] = [
                        "GTIN" => $GTIN,
                        "lot" => $this_delivery["lot"],
                        "store_number" => $store_number,
                        "qty" => (int)$quantityAvailable,
                        "order_weight" => $order_weight,
                    ];

                    // We've found the last available lot, so we set quantity to 0 to end the loop
                    $quantityAvailable = 0;
                }
            }
        }
    }

    $values = [];
    $wpdb->query("DELETE FROM {$wpdb->prefix}bleuh_vars WHERE store_number <> 'WEB' AND store_number NOT LIKE 'ONTARIO%';");
    $query = "INSERT INTO {$wpdb->prefix}bleuh_vars (SQDC_SKU, lot, store_number, qty, order_weight) VALUES ";
    $place_holders = [];
    foreach ($to_insert as $datum) {
        $values = array_merge($values, array_values($datum));
        $place_holders[] = "('%s', '%s', '%s', %d, %d)";
    }
    $query .= implode(', ', $place_holders);
    $query = $wpdb->prepare($query, $values);
    $wpdb->query($query);
    bleuh_log("Store varieties imported (algorithm v3 with ONTARIO data).");
    return true;
}

function ingest_ontario_lots_N_products()
{
    bleuh_update_option_with_date("bleuh_on_ingest_trigger");
    global $wpdb;

    $client = new Google_Client();
    $client->setApplicationName('Bleuh Drive API');
    $client->setScopes([
        Google_Service_Drive::DRIVE_METADATA_READONLY,
        Google_Service_Drive::DRIVE_READONLY,
        Google_Service_Drive::DRIVE_FILE,
    ]);
    $client->setAuthConfig(GOOGLE_SA_JSON);
    $google_service = new Google_Service_Drive($client);

    $file = $google_service->files->get(LOTS_DOC_ID, [
        "supportsAllDrives" => true,
        "alt" => "media",
    ]);
    $temp_file = tempnam(sys_get_temp_dir(), 'bleuh-on-ingest');
    file_put_contents($temp_file, $file->getBody()->getContents());
    $reader = ReaderEntityFactory::createXLSXReader();
    $reader->open($temp_file);
    // new products to add
    $products = [];
    // new lots to add
    $lots = [];
    // associated products <> lots
    $associated = []; // key: SKU+LOT, value = [Sku, lot]

    try {
        $wpdb->query('START TRANSACTION');
        // read day of the week offset per store from excel sheet on Google Drive.
        foreach ($reader->getSheetIterator() as $s => $sheet) {
            if ($s == ON_LOTS_DOC_SHEET) {
                foreach ($sheet->getRowIterator() as $i => $row) {
                    // skip Excel Header
                    if ($i >= 2) {

                        // get data

                        $cells = $row->getCells();

                        $do_display = trim($cells[ON_LOTS_DISPLAY]->getValue() ?? '');

                        // skip item if set not to display
                        if (strtolower(trim($do_display)) == 'non')
                            continue;

                        $SKU = trim($cells[ON_LOTS_SKU]->getValue() ?? '');
                        $P_NAME = trim($cells[ON_LOTS_PRODUCT_NAME]->getValue() ?? '');
                        $P_TYPE = trim($cells[ON_LOTS_PRODUCT_TYPE]->getValue() ?? '');
                        $P_FORMAT = trim($cells[ON_LOTS_PRODUCT_FORMAT]->getValue() ?? '');
                        $LOT = trim($cells[ON_LOTS_PRODUCT_LOT]->getValue() ?? '');
                        $L_NAME = trim($cells[ON_LOTS_VARIETY_NAME]->getValue() ?? '');
                        $THC = trim($cells[ON_LOTS_UNIT_THC]->getValue() ?? '');
                        $CBD = trim($cells[ON_LOTS_UNIT_CBD]->getValue() ?? '');
                        $COLLECTION = trim($cells[ON_LOTS_UNIT_COLLECTION]->getValue() ?? '');
                        $BLEND = trim($cells[ON_LOTS_CATEGORY]->getValue() ?? '');

                        $P_WRAP_DATE = $cells[ON_LOTS_PRODUCT_WRAP_DATE]->getValue() ?? '';

                        if ($P_WRAP_DATE instanceof DateTime) {
                            $formatted_date = $P_WRAP_DATE->format('Y-m-d');
                        }
                        elseif (!empty($P_WRAP_DATE)) {
                            $date_str = trim($P_WRAP_DATE);

                            // Match formats like MM-YYYY or YYYY-MM
                            if (preg_match('/^(\d{2})-(\d{4})$/', $date_str, $m)) {
                                // Format as first day of that month
                                $formatted_date = "{$m[2]}-{$m[1]}-01";
                            }
                            elseif (preg_match('/^(\d{4})-(\d{2})$/', $date_str, $m)) {
                                $formatted_date = "{$m[1]}-{$m[2]}-01";
                            }
                            else {
                                try {
                                    $date = new DateTime($date_str);
                                    $formatted_date = $date->format('Y-m-d');
                                }
                                catch (Exception $e) {
                                    $formatted_date = '';
                                }
                            }
                        }
                        else {
                            $formatted_date = '';
                        }

                        if (empty($SKU) || empty($P_NAME) || empty($P_TYPE) || empty($P_FORMAT)
                        || empty($P_WRAP_DATE) || empty($LOT) || empty($L_NAME)
                        || empty($THC) || empty($CBD) || empty($COLLECTION)) {
                            continue; // skip incomplete rows
                        }

                        $theblend = 'Mélange';
                        $theblend_en = "Mix";
                        $within_blend = $BLEND;
                        if (strpos(strtolower($within_blend), 'indica') !== false) {
                            $theblend = 'Indica';
                            $theblend_en = 'Indica';
                        }
                        elseif (strpos(strtolower($within_blend), 'sativa') !== false) {
                            $theblend = 'Sativa';
                            $theblend_en = 'Sativa';
                        }
                        elseif (strpos(strtolower($within_blend), 'hybrid') !== false) {
                            $theblend = 'Hybride';
                            $theblend_en = 'Hybrid';
                        }
                        elseif (strpos(strtolower($within_blend), 'hash') !== false) {
                            $theblend = 'Hash';
                            $theblend_en = 'Hash';
                        }

                        $the_format = $P_TYPE;
                        $the_format_fr = $the_format;
                        if (strpos(strtolower($within_blend), 'flower') !== false) {
                            $the_format_fr = 'Fleurs séchées';
                        }
                        elseif (strpos(strtolower($within_blend), 'pre-rolled') !== false) {
                            $the_format_fr = 'Préroulés';
                        }
                        elseif (strpos(strtolower($within_blend), 'concentrates') !== false) {
                            $the_format_fr = 'Haschich';
                        }


                        // create or update product data
                        $products[$SKU] = [
                            'SKU' => $SKU, // aka GTIN
                            'collection' => $COLLECTION, // collection name, i.e. Blakh
                            'name' => $P_NAME, // product name
                            'blend' => $theblend,
                            'format' => $the_format_fr, // product type, i.e. 'Flower', 'Pre-Roll', etc.
                            'weight' => $P_FORMAT, // weight, i.e. '3.5g', '7g', etc. AKA Type in db
                            'blend_en' => $theblend_en,
                            'format_en' => $the_format,
                        ];

                        // create or update lot data
                        $lots[$LOT] = [
                            'lot' => $LOT, // lot number
                            'variety_name' => $L_NAME, // variety name
                            'THC' => $THC, // THC content
                            'CBD' => $CBD, // CBD content
                            'wrap_date' => $formatted_date, // wrap date no day, only month and year, i.e. '05-2025'
                        ];

                        // associate product with lot
                        $associated[$SKU . '+' . $LOT] = [
                            'SKU' => $SKU,
                            'lot' => $LOT,
                            'store_number' => 'Ontario Store', // hardcoded store number for Ontario
                            'qty' => 1,
                            'order_weight' => 0, // order weight is not used in this context
                        ];

                    }
                }
                break;
            }
        }

        unlink($temp_file);

        // insert data in sql, bleuh_lots + is_ontario
        $values = [];
        $wpdb->query("DELETE FROM {$wpdb->prefix}bleuh_lots WHERE is_ontario = 'Y';");
        $query = "INSERT INTO {$wpdb->prefix}bleuh_lots (lot, variety_name, THC, CBD, wrap_date, is_ontario) VALUES ";
        $place_holders = [];
        foreach ($lots as $lot_data) {
            $values = array_merge($values, array_values($lot_data));
            $place_holders[] = "('%s', '%s', %s, %s, '%s', 'Y')";
        }
        $query .= implode(', ', $place_holders);
        $query = $wpdb->prepare($query, $values);
        $wpdb->query($query);

        // insert or update products
        $values = [];
        $query = "INSERT INTO {$wpdb->prefix}bleuh_products 
          (GTIN, collection, name, blend, format, weight, blend_en, format_en, is_ontario) 
          VALUES ";
        $place_holders = [];
        foreach ($products as $product_data) {
            $values = array_merge($values, array_values($product_data));
            $place_holders[] = "('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', 'Y')";
        }
        $query .= implode(', ', $place_holders);

        // Add ON DUPLICATE KEY UPDATE clause
        $query .= " ON DUPLICATE KEY UPDATE 
            collection = VALUES(collection), 
            name = VALUES(name), 
            blend = VALUES(blend), 
            format = VALUES(format), 
            weight = VALUES(weight), 
            blend_en = VALUES(blend_en), 
            format_en = VALUES(format_en), 
            is_ontario = VALUES(is_ontario);";

        // Prepare the query with the values
        $query = $wpdb->prepare($query, $values);
        // Execute the query
        $wpdb->query($query);

        // insert products in sql
        $values = [];
        $query = "INSERT INTO {$wpdb->prefix}bleuh_vars (SQDC_SKU, lot, store_number, qty, order_weight) VALUES ";
        $place_holders = [];
        foreach ($associated as $assoc_data) {
            $values = array_merge($values, array_values($assoc_data));
            $place_holders[] = "('%s', '%s', '%s', %d, %d)";
        }
        $query .= implode(', ', $place_holders);
        $query = $wpdb->prepare($query, $values);
        $wpdb->query($query);
        $wpdb->query('COMMIT');
        bleuh_update_option_with_date("bleuh_on_ingest_success");
        bleuh_log("Ontario products and lots updated successfully.");
    }
    catch (\Exception $e) {
        $wpdb->query('ROLLBACK');
        bleuh_log('Ontario digest error: ' . $e->getMessage());
    }
}

/*
 * BLEUH DEAR SYSTEMS API LEGACY CODE
 * function bleuh_get_locations() : bool {
 $account_id = 'api-auth-accountid: '.DEAR_ACCOUNT_ID;
 $application_key = 'api-auth-applicationkey: '.DEAR_ACCOUNT_KEY;
 $naked_dear_url = '';
 $data = array ('Page' => '1', 'Limit' => '1000');
 $data = http_build_query($data);
 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL,$naked_dear_url."?".$data); //GET API CALL
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 $headers = [
 "Content-type: application/json",
 $account_id,
 $application_key
 ];
 curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
 $server_output = curl_exec ($ch);
 curl_close ($ch);
 print $server_output;
 return true; }
 // BLEUH DEAR SYSTEMS API LEGACY CODE // add customer id to stores function bleuh_get_customers() : bool {
 $account_id = 'api-auth-accountid: '.DEAR_ACCOUNT_ID;
 $application_key = 'api-auth-applicationkey: '.DEAR_ACCOUNT_KEY;
 // contains store info
 $naked_dear_url = 'https://inventory.dearsystems.com/ExternalApi/v2/customer';
 $data = array ('Page' => '1', 'Limit' => '1000');
 $data = http_build_query($data);
 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL,$naked_dear_url."?".$data); //GET API CALL
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 $headers = [
 "Content-type: application/json",
 $account_id,
 $application_key
 ];
 curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
 $server_output = curl_exec ($ch);
 curl_close ($ch);
 print $server_output;
 return true; }
 // BLEUH DEAR SYSTEMS API LEGACY CODE // add customer id to stores function bleuh_get_sales() : bool {
 $account_id = 'api-auth-accountid: '.DEAR_ACCOUNT_ID;
 $application_key = 'api-auth-applicationkey: '.DEAR_ACCOUNT_KEY;
 // contains store info
 $naked_dear_url = 'https://inventory.dearsystems.com/ExternalApi/v2/sale/order';
 $data = array ('Page' => '1', 'Limit' => '1000', 'IncludeProductInfo' => 'IncludeProductInfo');
 $data = http_build_query($data);
 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL,$naked_dear_url."?".$data); //GET API CALL
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 $headers = [
 "Content-type: application/json",
 $account_id,
 $application_key
 ];
 curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
 $server_output = curl_exec ($ch);
 curl_close ($ch);
 print $server_output;
 return true; }
 // BLEUH DEAR SYSTEMS API LEGACY CODE function bleuh_save_deliveries_data() : bool {
 @bleuh_get_sales();
 //@bleuh_get_locations();
 //@bleuh_get_customers();
 return true;
 // has no info:
 // https://inventory.dearsystems.com/ExternalApi/v2/ref/location
 $account_id = 'api-auth-accountid: '.DEAR_ACCOUNT_ID;
 $application_key = 'api-auth-applicationkey: '.DEAR_ACCOUNT_KEY;
 $naked_dear_url = 'https://inventory.dearsystems.com/ExternalApi/v2/SaleList';
 $data = array ('Page' => '1', 'Limit' => '1000');
 $data = http_build_query($data);
 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL,$naked_dear_url."?".$data); //GET API CALL
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 $headers = [
 "Content-type: application/json",
 $account_id,
 $application_key
 ];
 curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
 $server_output = curl_exec ($ch);
 curl_close ($ch);
 print $server_output;
 return true; } */

// fetch varieties overrides
function bleuh_get_var_overrides($store = false, $SKUs_array = [])
{
    global $wpdb;
    $query = "";
    try {
        if ($store === false) {
            // fetch overrides by SKUs
            $placeholders = implode(', ', array_fill(0, count($SKUs_array), '%s'));
            $query = $wpdb->get_results($wpdb->prepare("SELECT o.new_live_qty db_qty, o.displayed_qty override_qty, l.*, o.store_number, o.GTIN, o.depleted
                                            FROM {$wpdb->prefix}bleuh_store_lot_override o
                                            LEFT JOIN {$wpdb->prefix}bleuh_lots l
                                                ON o.lot = l.lot
                                            WHERE o.GTIN IN (" . $placeholders . ")
                                                AND o.depleted > 0
                                                AND o.displayed_qty > 0
                                            ORDER BY o.weight;", ...$SKUs_array), ARRAY_A);
        }
        else {
            // fetch overrides by store
            $query = $wpdb->get_results($wpdb->prepare("SELECT o.new_live_qty db_qty, o.displayed_qty override_qty, l.*, o.store_number, o.GTIN, o.depleted
                                            FROM {$wpdb->prefix}bleuh_store_lot_override o
                                            LEFT JOIN {$wpdb->prefix}bleuh_lots l
                                                ON o.lot = l.lot
                                            WHERE o.store_number = %s
                                                AND o.depleted > 0
                                                AND o.displayed_qty > 0
                                            ORDER BY o.weight;", $store), ARRAY_A);
        }
        return $query;
    }
    catch (\Exception $e) {
        return [];
    }
    return [];
}

function bleuh_save_store_data(): bool
{
    // Silence sur les notices PHP 8.2 (Spout incompatibility)
    $original_error_reporting = error_reporting();
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

    bleuh_update_option_with_date("bleuh_store_locator_trigger");
    global $wpdb;

    try {
        // --- ÉTAPE A : RÉCUPÉRATION DU FICHIER GOOGLE DRIVE ---
        $client = new Google_Client();
        $client->setApplicationName('Bleuh Drive API');
        $client->setAuthConfig(GOOGLE_SA_JSON);
        $client->setScopes([Google_Service_Drive::DRIVE_READONLY]);
        $service = new Google_Service_Drive($client);

        $file = $service->files->get(GTIN_DOC_ID, ["supportsAllDrives" => true, "alt" => "media"]);
        $temp_file = tempnam(sys_get_temp_dir(), 'bleuh-gtin');
        file_put_contents($temp_file, $file->getBody()->getContents());

        // --- ÉTAPE B : EXTRACTION DES GTINS ---
        $import = bleuh_import_GTINS($temp_file);
        if (file_exists($temp_file)) {
            unlink($temp_file);
        }

        if (empty($import)) {
            bleuh_log("Abandon : Fichier Excel vide ou illisible.");
            error_reporting($original_error_reporting);
            return false;
        }

        // --- ÉTAPE C : APPEL API SQDC ---
        $post_data = [];
        foreach ($import as $data) {
            $post_data[] = json_encode(['Sku' => $data["gtin"], 'Page' => 0, 'Pagesize' => 999]);
        }
        $url = 'https://www.sqdc.ca/api/storeinventory/storesinventory';
        $responses = bleuh_execute_parallel_curls($url, $post_data, $import);

        // --- ÉTAPE D : PRÉ-TRAITEMENT ET BOUCLIER ANTI-PURGE ---
        $products = [];
        $stores = [];
        $store_products = [];
        $live_qty_for_deplete = [];

        if (empty($responses)) {
            bleuh_log("Abandon : Aucune réponse de l'API SQDC.");
            error_reporting($original_error_reporting);
            return false;
        }

        foreach ($responses as $response) {
            // Le gtin peut être dans 'Sku' ou injecté dans 'gtin' par parallel_curls
            $current_gtin = $response["gtin"] ?? ($response["Sku"] ?? null);
            if (!$current_gtin || !isset($response["Stores"]))
                continue;

            if (!array_key_exists($current_gtin, $products)) {
                $products[$current_gtin] = [
                    "gtin" => $current_gtin,
                    "collection" => $response["collection"] ?? '',
                    "name" => $response["product"] ?? '',
                    "blend" => $response["blend"] ?? '',
                    "format" => $response["format"] ?? '',
                    "blend_en" => $response["blend_en"] ?? '',
                    "format_en" => $response["format_en"] ?? '',
                    "weight" => $response["weight"] ?? '',
                ];
            }

            foreach ($response["Stores"] as $store) {
                $qty = (int)($store["InventoryStatus"]["Quantity"] ?? 0);
                if ($qty > 0) {
                    $store_products[] = ["qty" => $qty, "store_number" => $store["Number"], "gtin" => $current_gtin];
                    if (!array_key_exists($store["Number"], $stores)) {
                        $addr = $store["Address"];
                        $schedule = isset($store["Schedule"]["TodayOpeningTimes"][0]) ? '?: ' . $store["Schedule"]["TodayOpeningTimes"][0]["BeginTime"] . " - " . $store["Schedule"]["TodayOpeningTimes"][0]["EndTime"] : '';
                        $stores[$store["Number"]] = [
                            "number" => $store["Number"],
                            "postal_code" => $addr["PostalCode"],
                            "name" => $store["Name"],
                            "DailyAddressBlock" => $addr["Line1"] . "\n" . $addr["City"] . ", " . $addr["RegionName"] . ", " . $addr["PostalCode"] . "\n" . $addr["PhoneNumber"] . ($schedule ? "\n" . $schedule : ""),
                            "Location_Lng" => $addr["Longitude"],
                            "Location_Lat" => $addr["Latitude"],
                        ];
                    }
                    $live_qty_for_deplete[$store["Number"] . "|||" . $current_gtin] = $qty;
                }
            }
        }

        // Si aucune donnée n'a pu être traitée, on n'atteint jamais la purge SQL
        if (empty($products) || empty($store_products)) {
            bleuh_log("Abandon : Données SQDC reçues mais aucun stock trouvé. Purge annulée.");
            error_reporting($original_error_reporting);
            return false;
        }

        // --- ÉTAPE E : ÉCRITURE SQL ---
        try {
            $wpdb->query('START TRANSACTION');

            // Suppression du catalogue Québec (On préserve l'Ontario 'Y')
            $wpdb->query("DELETE FROM {$wpdb->prefix}bleuh_products WHERE is_ontario = 'N';");
            $wpdb->query("DELETE FROM {$wpdb->prefix}bleuh_stores WHERE number NOT LIKE 'ONTARIO%';");
            $wpdb->query("DELETE FROM {$wpdb->prefix}bleuh_store_products WHERE store_number <> 'WEB' AND store_number NOT LIKE 'ONTARIO%';");

            // Insertion Produits : On utilise INSERT IGNORE pour skip les GTIN déjà présents (Ontario)
            $v_p = [];
            $p_p = [];
            foreach ($products as $row) {
                $v_p = array_merge($v_p, array_values($row));
                $p_p[] = "('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')";
            }
            $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->prefix}bleuh_products (`gtin`, `collection`, `name`, `blend`, `format`, `blend_en`, `format_en`, `weight`) VALUES " . implode(', ', $p_p), $v_p));

            // Insertion Magasins
            $v_s = [];
            $p_s = [];
            foreach ($stores as $row) {
                $v_s = array_merge($v_s, array_values($row));
                $p_s[] = "( '%s', '%s', '%s', '%s', POINT(%8f, %8f) )";
            }
            $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}bleuh_stores (`number`, `postal_code`, `name`, `DailyAddressBlock`, `location`) VALUES " . implode(', ', $p_s), $v_s));

            // Insertion Stocks (Chunks de 500 pour respecter max_allowed_packet)
            $v_sp = [];
            $p_sp = [];
            foreach ($store_products as $sp) {
                $v_sp = array_merge($v_sp, [$sp["store_number"], $sp["gtin"], $sp["qty"]]);
                $p_sp[] = "('%s', '%s', %d)";
            }
            foreach (array_chunk($p_sp, 500) as $idx => $chunk) {
                $val_chunk = array_slice($v_sp, $idx * 1500, 1500);
                $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}bleuh_store_products (`store_number`, `GTIN`, `qty`) VALUES " . implode(', ', $chunk), $val_chunk));
            }

            $wpdb->query('COMMIT');
            bleuh_log("Importation réussie : " . count($products) . " produits traités.");
            bleuh_update_option_with_date("bleuh_store_locator_success");
            error_reporting($original_error_reporting);
            return true;

        }
        catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            bleuh_log('Erreur SQL : ' . $e->getMessage());
        }
    }
    catch (Exception $exception) {
        bleuh_log('Erreur Critique : ' . $exception->getMessage());
    }

    error_reporting($original_error_reporting);
    return false;
}

function bleuh_clear_logs()
{
    $log_file = plugin_dir_path(__FILE__) . '/../logs/bleuh.log';
    $log_file = realpath($log_file);
    // Check if the file exists before attempting to delete it.
    if (file_exists($log_file)) {
        // Attempt to delete the file.
        if (!unlink($log_file)) {
            echo "There was an error deleting the log file.";
        }
    }
    else {
        echo "Log file does not exist.";
    }
}

function bleuh_get_first_lines($file, $num_lines)
{
    if (!file_exists($file) || !is_readable($file)) {
        return []; // Return an empty array if the file doesn't exist or isn't readable.
    }

    $lines = [];
    $handle = fopen($file, 'r'); // Open the file for reading.

    if ($handle) {
        $line_count = 0;

        // Read lines until the desired number is reached or EOF.
        while (($line = fgets($handle)) !== false && $line_count < $num_lines) {
            $lines[] = $line;
            $line_count++;
        }

        fclose($handle); // Close the file after reading.
    }

    return implode("\n", $lines);
}


function bleuh_display_logs()
{
    $log_file = plugin_dir_path(__FILE__) . '/../logs/bleuh.log';

    if (file_exists($log_file) && is_readable($log_file)) {
        $ret = bleuh_get_first_lines($log_file, BLEUH_LOG_DISPLAY_COUNT);
        echo '<pre style="height: 400px; overflow-y: scroll;">';
        echo $ret;
        echo '</pre>';
    }
    else {
        echo 'Log file does not exist or is not readable.';
    }
}

function prepend_to_file($file, $content)
{
    if (!file_exists($file)) {
        // If the file doesn't exist, just create it with the new content.
        file_put_contents($file, $content, LOCK_EX);
    }
    else {
        // Read the existing content of the file.
        $existing_content = file_get_contents($file);

        // Prepend the new content to the existing content.
        $new_content = $content . $existing_content;

        // Write the combined content back to the file.
        file_put_contents($file, $new_content, LOCK_EX);
    }
}

function bleuh_log($message)
{
    $log_file = plugin_dir_path(__FILE__) . '/../logs/bleuh.log';

    // Convert arrays or objects to JSON strings
    if (is_array($message) || is_object($message)) {
        $message = json_encode($message);
    }

    // Format the log entry with a timestamp
    $errorTime = new DateTime();
    $errorTime->setTimestamp(time());
    $timezone = new DateTimeZone(BLEUH_TIMEZONE);
    $errorTime->setTimezone($timezone);
    $log_entry = $errorTime->format('Y-m-d H:i:s') . " " . $message . "\n";

    // Append the log entry to the file
    prepend_to_file($log_file, $log_entry);

    // Trim the file to the last x lines
    bleuh_trim_log_file($log_file, BLEUH_LOG_LINES_COUNT);
}

function bleuh_trim_log_file($file, $max_lines)
{
    // Check if the file exists and is writable
    if (!file_exists($file) || !is_writable($file)) {
        return; // Exit if the file doesn't exist or isn't writable
    }

    // Read the entire file into an array of lines
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return; // Exit if the file could not be read
    }

    // Get only the first $max_lines lines
    $lines_to_keep = array_slice($lines, 0, $max_lines);

    // Write the first $max_lines lines back to the file
    file_put_contents($file, implode("\n", $lines_to_keep) . "\n", LOCK_EX);
}

function clear_sg_cache()
{
    if (function_exists('sg_cachepress_purge_cache')) {
        sg_cachepress_purge_cache();
        bleuh_log("sg caches purged.");
    }
}

function bleuh_import_GTINS(string $filename): mixed
{
    try {
        // read XLSX
        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($filename);

        global $wpdb;

        try {
            $data = [];
            $exclude = ["de la ferme"];
            foreach ($reader->getSheetIterator() as $s => $sheet) {
                if ($s == GTIN_DOC_SHEET) {
                    foreach ($sheet->getRowIterator() as $i => $row) {
                        // skip Excel Header
                        if ($i >= 2) {
                            $cells = $row->getCells();

                            // verify display option for GTIN
                            $display = strtolower(trim($cells[GTIN_DOC_COL_DISPLAY]->getValue()));

                            if ($display == 'non' || $display == 'no') {
                                continue; // skip item if set not to display
                            }

                            $this_data = [
                                "product" => trim($cells[GTIN_DOC_COL_PROD]->getValue()),
                                "collection" => trim($cells[GTIN_DOC_COL_COLL]->getValue()),
                                "gtin" => trim($cells[GTIN_DOC_COL_GTIN]->getValue()),
                                "weight" => trim($cells[GTIN_DOC_COL_WEIGHT]->getValue()),
                                "format" => trim($cells[GTIN_DOC_COL_FORMAT]->getValue()),
                                "format_en" => trim($cells[GTIN_DOC_COL_FORMAT_EN]->getValue()),
                                "blend" => trim($cells[GTIN_DOC_COL_BLEND]->getValue()),
                                "blend_en" => trim($cells[GTIN_DOC_COL_BLEND_EN]->getValue()),
                            ];
                            if (!empty($this_data["gtin"]) && !in_array(strtolower($this_data["collection"]), $exclude)) {
                                $data[] = $this_data;
                            }
                        }
                    }
                    break;
                }
            }

            $data = array_map('serialize', $data);
            $data = array_unique($data);
            $data = array_map('unserialize', $data);
            return $data;
        }
        catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            var_dump($e->getMessage());
            error_log($e->getMessage());
            return false;
        }
        finally {
            // close reader
            $reader->close();
        }

    }
    catch (\Exception $e) {
        var_dump($e->getMessage());
        error_log($e->getMessage());
        return false;
    }
}

function bleuh_import_lots(string $filename): bool
{
    try {

        if (isset($_FILES["xlsx"]["error"])) {
            if ($_FILES["xlsx"]["error"] !== UPLOAD_ERR_OK) {
                bleuh_log("Lots import failed: xlsx error.");
                return false;
            }
        }

        // read XLSX
        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($filename);

        global $wpdb;

        try {
            $wpdb->query('START TRANSACTION');
            $wpdb->query("DELETE FROM {$wpdb->prefix}bleuh_lots WHERE is_ontario <> 'Y';");

            $data = [];

            foreach ($reader->getSheetIterator() as $s => $sheet) {
                if ($s == LOTS_DOC_SHEET) {
                    foreach ($sheet->getRowIterator() as $i => $row) {
                        // skip Excel Header
                        if ($i >= 1) {
                            $cells = $row->getCells();

                            $THC = $cells[LOTS_DOC_COL_THC]->getValue();
                            if (is_numeric($THC))
                                $THC = round(($THC * 100), 2) . "%";
                            $CBD = $cells[LOTS_DOC_COL_CBD]->getValue();
                            if (is_numeric($CBD))
                                $CBD = round(($CBD * 100), 2) . "%";
                            $dateTime = new DateTime();
                            try {
                                $date_data = $cells[LOTS_DOC_COL_WRAP_DATE]->getValue();
                                if (is_numeric($date_data)) {
                                    // Assuming $date_data is a timestamp here
                                    $dateTime = (new DateTime())->setTimestamp($date_data);
                                }
                                elseif (is_string($date_data)) {
                                    $dateTime = new DateTime($date_data, new DateTimeZone(BLEUH_TIMEZONE));
                                    $dateTime->setTime(0, 0, 0); // Assuming you want to reset the time part to 00:00:00
                                }
                                else {
                                    $dateTime = $date_data;
                                    if (empty($dateTime)) {
                                        $dateTime = new DateTime();
                                    }
                                }
                                $WRAP_DATE = $dateTime->format('U'); // 'U' is the format character for a Unix timestamp
                            }
                            catch (\Exception $e) {
                                // Handle the exception, perhaps log it or set a default value
                                $WRAP_DATE = (new DateTime())->format('U');
                            }

                            $this_data = [
                                "lot" => trim($cells[LOTS_DOC_COL_LOT]->getValue()),
                                "variety_name" => trim(mb_strtolower($cells[LOTS_DOC_COL_VAR]->getValue(), "UTF-8")),
                                "THC" => $THC,
                                "CBD" => $CBD,
                                "wrap_date" => date("Y-m-d", $WRAP_DATE),
                            ];

                            if (!empty($this_data["lot"]) && !empty($this_data["variety_name"])) {
                                $data[$this_data["lot"]] = $this_data;
                            }
                        }
                    }
                    break;
                }
            }

            $data = array_map('serialize', $data);
            $data = array_unique($data);
            $data = array_map('unserialize', $data);
            $values = [];
            $place_holders = [];
            $query = "INSERT INTO {$wpdb->prefix}bleuh_lots (lot, variety_name, THC, CBD, wrap_date) VALUES ";
            foreach ($data as $datum) {
                $values = array_merge($values, array_values($datum));
                $place_holders[] = "('%s', '%s', '%s', '%s', '%s')";
            }
            $query .= implode(', ', $place_holders);
            $query = $wpdb->prepare($query, $values);
            $wpdb->query($query);

            $wpdb->query('COMMIT');
            return true;
        }
        catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            var_dump($e->getMessage());
            error_log($e->getMessage());
            return false;
        }
        finally {
            // close reader
            $reader->close();
        }

    }
    catch (\Exception $e) {
        var_dump($e->getMessage());
        error_log($e->getMessage());
        return false;
    }
}

function bleuh_import_web_inventories(string $filename): bool
{
    if (isset($_FILES["csv"]["error"])) {
        if ($_FILES["csv"]["error"] !== UPLOAD_ERR_OK) {
            bleuh_log("File upload error: " . bleuh_file_upload_error_message($_FILES["csv"]["error"]));
            return false;
        }
    }

    try {
        // read XLSX
        $reader = ReaderEntityFactory::createCSVReader();
        $reader->open($filename);

        global $wpdb;

        $wpdb->query('START TRANSACTION');
        $wpdb->query("DELETE FROM {$wpdb->prefix}bleuh_vars WHERE store_number='WEB'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}bleuh_store_products WHERE store_number='WEB'");

        $data = [];

        $i = 0; // line
        foreach ($reader->getSheetIterator() as $s => $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                // skip Excel Header
                if ($i >= 1) {
                    $cells = $row->getCells();
                    $SQDC_SKU = $cells[WEB_INV_DOC_COL_SKU]->getValue();
                    $SQDC_SKU = str_replace('"', '', $SQDC_SKU); // remove quotes
                    $SQDC_SKU = str_replace('=', '', $SQDC_SKU); // remove equal sign

                    $lot = trim($cells[WEB_INV_DOC_COL_LOT]->getValue());
                    $qty = (int)trim($cells[WEB_INV_DOC_COL_QTY]->getValue());

                    if (isset($cells[WEB_INV_DOC_COL_DATE])) {
                        $date = strtotime(trim($cells[WEB_INV_DOC_COL_DATE]->getValue()));

                        if ($qty < 0)
                            $qty = 0;

                        $this_data = [
                            "SQDC_SKU" => trim($SQDC_SKU),
                            "lot" => $lot,
                            "store_number" => 'WEB',
                            "qty" => $qty,
                            "date" => $date,
                        ];
                        $data[] = $this_data;
                    }
                }
                $i++;
            }
            break;
        }

        usort($data, function ($a, $b) {
            return $b["date"] - $a["date"];
        });

        $ordered_data = [];
        $order_weight = 0;
        $store_locator_data = [];
        foreach ($data as $row) {
            $order_weight++;
            $ordered_data[] = [
                "SQDC_SKU" => $row["SQDC_SKU"],
                "lot" => $row["lot"],
                "store_number" => $row["store_number"],
                "qty" => $row["qty"],
                "order_weight" => $order_weight,
            ];
            if (isset($store_locator_data[$row["store_number"] . '-' . $row["SQDC_SKU"]])) {
                $store_locator_data[$row["store_number"] . '-' . $row["SQDC_SKU"]] = [
                    "store_number" => $row["store_number"],
                    "GTIN" => $row["SQDC_SKU"],
                    "qty" => $store_locator_data[$row["store_number"] . '-' . $row["SQDC_SKU"]]["qty"] + $row["qty"],
                ];
            }
            else {
                $store_locator_data[$row["store_number"] . '-' . $row["SQDC_SKU"]] = [
                    "store_number" => $row["store_number"],
                    "GTIN" => $row["SQDC_SKU"],
                    "qty" => $row["qty"],
                ];
            }
        }

        $values = [];
        $place_holders = [];
        $query = "INSERT INTO {$wpdb->prefix}bleuh_vars (SQDC_SKU, lot, store_number, qty, order_weight) VALUES ";
        foreach ($ordered_data as $datum) {
            $values = array_merge($values, array_values($datum));
            $place_holders[] = "('%s', '%s', '%s', %d, %d)";
        }
        $query .= implode(', ', $place_holders);
        $wpdb->query($wpdb->prepare($query, $values));

        $values = [];
        $place_holders = [];
        $query = "INSERT INTO {$wpdb->prefix}bleuh_store_products (store_number, GTIN, qty) VALUES ";
        foreach ($store_locator_data as $datum) {
            $values = array_merge($values, array_values($datum));
            $place_holders[] = "('%s', '%s', %d)";
        }
        $query .= implode(', ', $place_holders);
        $wpdb->query($wpdb->prepare($query, $values));
        $wpdb->query('COMMIT');
    }
    catch (\Exception $e) {
        $wpdb->query('ROLLBACK');
        echo $e->getMessage();
        return false;
    }
    return true;
}

function bleuh_fix_tables()
{
    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    try {
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'bleuh_vars';
        $sql = "CREATE TABLE $table_name (
                    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                    `SQDC_SKU` varchar(255),
                    `lot` varchar(255),
                    `store_number` varchar(255),
                    `qty` int(8),
                    `order_weight` int(8),
                    PRIMARY KEY (`id`)
                ) $charset_collate;";
        $delta = dbDelta($sql);
        bleuh_log($delta);
        $table_name = $wpdb->prefix . 'bleuh_lots';
        $sql = "CREATE TABLE $table_name (
                    `variety_name` varchar(255),
                    `lot` varchar(255),
                    `THC` varchar(255),
                    `CBD` varchar(255),
                    `wrap_date` varchar(255),
                    `is_ontario` varchar(1) DEFAULT 'N',
                    PRIMARY KEY (`lot`)
                ) $charset_collate;";
        $delta = dbDelta($sql);
        bleuh_log($delta);
        $table_name = $wpdb->prefix . 'bleuh_stores';
        $sql = "CREATE TABLE `$table_name` (
                    `postal_code` varchar(255) NOT NULL,
                    `number` varchar(255) NOT NULL,
                    `name` varchar(255) NOT NULL,
                    `DailyAddressBlock` text NOT NULL,
                    `location` POINT NOT NULL,
                    PRIMARY KEY  (`postal_code`),
                    SPATIAL INDEX `location` (`location`),
                    INDEX `store_number` (`number`)
                ) $charset_collate;";
        $delta = dbDelta($sql);
        bleuh_log($delta);
        $table_name = $wpdb->prefix . 'bleuh_products';
        $sql = "CREATE TABLE $table_name (
                    `GTIN` varchar(255),
                    `collection` varchar(255),
                    `name` varchar(255),
                    `blend` varchar(255),
                    `format` varchar(255),
                    `weight` varchar(255),
                    `blend_en` varchar(255),
                    `format_en` varchar(255),
                    `is_ontario` varchar(1) DEFAULT 'N',
                    PRIMARY KEY (`GTIN`)
                ) $charset_collate;";
        $delta = dbDelta($sql);
        bleuh_log($delta);
        $table_name = $wpdb->prefix . 'bleuh_store_products';
        $sql = "CREATE TABLE $table_name (
                    `store_number` varchar(255),
                    `GTIN` varchar(255),
                    `qty` int(8),
                    PRIMARY KEY (`GTIN`, `store_number`)
                ) $charset_collate;";
        $delta = dbDelta($sql);
        bleuh_log($delta);
        $table_name = $wpdb->prefix . 'bleuh_store_deliveries';
        $sql = "CREATE TABLE $table_name (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `store_number` varchar(255) NOT NULL,
                    `GTIN` varchar(255),
                    `lot` varchar(255),
                    `qty` int(8),
                    `latest_sunday` TIMESTAMP,
                    PRIMARY KEY (id),
                    INDEX `index_sunday` (`latest_sunday`)
                ) $charset_collate;";
        $delta = dbDelta($sql);
        bleuh_log($delta);
        $table_name = $wpdb->prefix . 'bleuh_store_lot_override';
        $sql = "CREATE TABLE $table_name (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `store_number` varchar(255) NOT NULL,
                    `GTIN` varchar(255),
                    `lot` varchar(255),
                    `previous_live_qty` int(8),
                    `new_live_qty` int(8),
                    `displayed_qty` int(8),
                    `depleted` int(8),
                    `weight` int(8),
                    `override_until_qty_drops_by` int(8),
                    `added_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) $charset_collate;";
        $delta = dbDelta($sql);
        bleuh_log($delta);

        // create favorites table
        $table_name = $wpdb->prefix . 'bleuh_favorites';
        $sql = "CREATE TABLE $table_name (
                    `hash_id` varchar(32) NOT NULL,
                    `post_id` varchar(32) NOT NULL,
                    `stamp_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (hash_id, post_id)
                ) $charset_collate;";
        $delta = dbDelta($sql);
        bleuh_log($delta);

        // Check if the function already exists
        $check_function_query = "
            SELECT COUNT(*) AS function_exists 
            FROM information_schema.ROUTINES 
            WHERE ROUTINE_SCHEMA = %s 
              AND ROUTINE_NAME = 'RemoveNonAlphabetic' 
              AND ROUTINE_TYPE = 'FUNCTION';
        ";

        // Replace `%s` with the database name
        $function_exists = $wpdb->get_var($wpdb->prepare($check_function_query));

        // If the function does not exist, create it
        if ($function_exists == 0) {
            $create_function_query = "
            DELIMITER $$
    
            CREATE FUNCTION RemoveNonAlphabetic(input TEXT)
            RETURNS TEXT
            DETERMINISTIC
            BEGIN
                DECLARE output TEXT DEFAULT '';
                DECLARE i INT DEFAULT 1;
    
                -- Loop through each character in the string
                WHILE i <= CHAR_LENGTH(input) DO
                    -- Get the current character
                    IF SUBSTRING(input, i, 1) REGEXP '[a-zA-Z]' THEN
                        -- Append alphabetic characters to the output
                        SET output = CONCAT(output, SUBSTRING(input, i, 1));
                    END IF;
    
                    SET i = i + 1;
                END WHILE;
    
                RETURN output;
            END$$
    
            DELIMITER ;
            ";

            // Remove DELIMITER statements for $wpdb execution
            $create_function_query = str_replace('DELIMITER $$', '', $create_function_query);
            $create_function_query = str_replace('DELIMITER ;', '', $create_function_query);
            $create_function_query = str_replace('$$', '', $create_function_query);

            // Execute the query to create the function
            $wpdb->query($create_function_query);

            bleuh_log("Function 'RemoveNonAlphabetic' created successfully.");
        }
        else {
            bleuh_log("Function 'RemoveNonAlphabetic' already exists.");
        }

        bleuh_log("MySQL Tables Fixed.");
    }
    catch (\Exception $ex) {
        bleuh_log("MySQL Tables Fixes error: " . $ex->getMessage());
    }
}

function bleuh_file_upload_error_message($error_code)
{
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded.';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk.';
        case UPLOAD_ERR_EXTENSION:
            return 'A PHP extension stopped the file upload.';
        default:
            return 'Unknown upload error.';
    }
}

function bleuh_render_vars($ids)
{

    if (empty($ids)) {
        return '';
    }

    $likes = get_fav_counts($ids);
    $output = '';
    foreach ($ids as $id) {
        $post_id = (int)$id;

        if (isset($likes[$post_id])) {
            $liked = $likes[$post_id]['liked'] ? ' liked' : 'not-liked';
            $favorites_count = (int)$likes[$post_id]['count'];
        }
        else {
            $liked = 'not-liked';
            $favorites_count = 0;
        }

        $categories = wp_get_post_terms($post_id, 'featured_item_category');
        $cat_list = [];
        foreach ($categories as $category) {
            $cat_list[] = $category->name;
        }
        $output .= '<div class="product-small box " style="max-width:33%;">';
        $output .= '<div class="vars-page-block">';

        // get img without txt if it exists from acf
        $img = '';
        $img_no_txt = get_field('img_no_txt', $post_id);
        $img_url = is_array($img_no_txt) ? $img_no_txt['url'] : $img_no_txt;
        if (empty($img_url)) {
            $img_url = get_the_post_thumbnail_url($post_id, 'medium');
        }
        if (!empty($img_url)) {
            $img = '<img src="' . esc_url($img_url) . '" alt="' . esc_attr(get_the_title($post_id)) . '" style="width: 100%; height: auto;" />';
        }

        $is_new = get_field('is_new', $post_id);
        $is_new_var = get_field('is_new_var', $post_id);

        // Add the post content to the current slide
        $output .= '<div style="flex: 1; padding: 10px; box-sizing: border-box;">';

        $data_prov = get_field('is_ontario', $post_id) ? 'on' : 'qc';
        $data_thc = get_field('thc', $post_id);
        $numbers_from_thc = preg_match_all('/\d+(\.\d+)?/', $data_thc, $matches);
        $numbers_from_thc = $matches[0] ?? [0];
        $data_thc_min = $numbers_from_thc[0];
        $data_thc_max = $numbers_from_thc[1] ?? $data_thc_min;

        $data_is_new = get_field('is_new', $post_id) ? 'new' : 'not-new';
        $data_type = implode(', ', $cat_list);


        $atts = get_field("attributs", $post_id);
        $aromas = [];
        if ($atts && isset($atts['aromes'])) {
            $aromes = $atts['aromes'];
            foreach ($aromes as $arome) {
                $aromas[] = $arome['tags'];
            }
        }
        $aromas = array_unique($aromas);
        $data_aromas = implode(', ', $aromas);

        $data_terpenes = '';
        if ($atts && isset($atts['terpenes'])) {
            $terps = $atts['terpenes'];
            foreach ($terps as $terp) {
                $data_terpenes .= $terp['tags'] . ', ';
            }
            $data_terpenes = rtrim($data_terpenes, ', ');
        }

        $data_effects = '';
        if ($atts && isset($atts['effets'])) {
            $effects = $atts['effets'];
            foreach ($effects as $effect) {
                $data_effects .= $effect['tags'] . ', ';
            }
            $data_effects = rtrim($data_effects, ', ');
        }

        $date_wrap_display = bleuh_display_em_date(get_field('date_demballage', $post_id));
        $date_wrap_sort_id = bleuh_sortable_em_date(get_field('date_demballage', $post_id));
        $output .= '    <a
                        data-province="' . esc_attr($data_prov) . '"
                        data-thc-min="' . esc_attr($data_thc_min) . '"
                        data-thc-max="' . esc_attr($data_thc_max) . '"
                        data-availability="' . esc_attr($data_is_new) . '"
                        data-type="' . esc_attr($data_type) . '"
                        data-aromas="' . esc_attr($data_aromas) . '"
                        data-terpenes="' . esc_attr(strtolower($data_terpenes)) . '"
                        data-effects="' . esc_attr(strtolower($data_effects)) . '"
                        data-count= "' . esc_attr($favorites_count) . '"
                        data-wrap-date="' . esc_attr($date_wrap_sort_id) . '"
                        class="var-box-link" href="#" data-href="' . get_permalink($post_id) . '" style="text-decoration: none; color: inherit;">';

        $output .= '<span data-id="' . $post_id . '" class="like-box ' . $liked . '"><span class="counter">' . esc_attr($favorites_count) . '</span><i class="ico"></i></span>';

        // add tags if image doesn't contain text
        $img_badge = '';
        if (($is_new || $is_new_var) && !empty($img_no_txt)) {
            if (!$is_new_var) {
                if (ICL_LANGUAGE_CODE == 'fr') {
                    $img_badge = 'nouveau.png';
                }
                else {
                    $img_badge = 'new.png';
                }
            }
            else {
                if (ICL_LANGUAGE_CODE == 'fr') {
                    $img_badge = 'nouvelle-variete.svg';
                }
                else {
                    $img_badge = 'new-strain.svg';
                }
            }

            $output .= '<span class="bleuh-var-fixed-attrs">';
            if (get_field('is_new', $post_id) != '') {
                $output .= '<img src ="' . esc_url(plugin_dir_url(__FILE__) . '../img/lots/') . $img_badge . '" alt="New" class="new-badge"><br>';
                // THC
                $thc = get_field('thc', $post_id);
                if (!empty($thc)) {
                    $output .= '<span class="thc">THC:' . esc_html($thc) . '</span>';
                }
                // Wrap date
                if (!empty($date_wrap_display)) {
                    $output .= '<span class="wrap-date">' . esc_html($date_wrap_display) . '</span>';
                }
            }
            $output .= '</span>';
        }
        $output .= $img;
        $output .= '        <h3 style="margin-top: 10px; font-size: 1.2rem;">' . get_the_title($post_id) . '</h3>';
        $output .= '        <p class="category uppercase is-smaller no-text-overflow product-cat op-7">' . implode(', ', $cat_list) . '</p>';
        $output .= '    </a>';
        $output .= '</div>'; // flex
        $output .= '</div>'; // vars-page-block
        $output .= '</div>'; // product-small
    }

    return $output;
}
