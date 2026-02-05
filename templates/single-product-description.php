<?php

// Security measure to prevent direct access to the plugin file.
if (!defined('ABSPATH')) {
    die('Sorry, you are not allowed to access this page directly.');
}

/* Template is used to override UX Builder template to "questions" from ACF */
?>

<!-- heading -->
<section class="section p-sec details-sec">
    <!-- Details -->
    <?php if (ICL_LANGUAGE_CODE == 'fr') { ?>
        <h2 class="details-h">Détails</h2>
    <?php } else { ?>
        <h2 class="details-h">Details</h2>
    <?php } ?>

    <?php
        $details = get_field('details');
    ?>
    <div class="row">
        <div class="col medium-4 small-12 large-4">
            <div class="col-inner">
                <h4 class="table_row" data-line-height="xs"><span class="table_title">Format</span></h4>
                <p class="table_row" data-line-height="xs"><?php echo $details['format']; ?></p>
                <hr>

                <?php if (ICL_LANGUAGE_CODE == 'fr') { ?>
                <h4 class="table_row" data-line-height="xs"><span class="table_title">Variété</span></h4>
                <?php } else { ?>
                <h4 class="table_row" data-line-height="xs"><span class="table_title">Variety</span></h4>
                <?php } ?>
                <p class="table_row" data-line-height="xs"><?php echo $details['variety']; ?></p>
                <hr>
            </div>
        </div>
        <div class="col medium-4 small-12 large-4">
            <div class="col-inner">
                <?php if (ICL_LANGUAGE_CODE == 'fr') { ?>
                <h4 class="table_row" data-line-height="xs"><span class="table_title">Effets potentiels</span></h4>
                <?php } else { ?>
                <h4 class="table_row" data-line-height="xs"><span class="table_title">Potential Effects</span></h4>
                <?php } ?>
                <p class="table_row" data-line-height="xs"><?php echo $details['effets_potentiels']; ?></p>
                <hr>

                <?php if (ICL_LANGUAGE_CODE == 'fr') { ?>
                <h4 class="table_row" data-line-height="xs"><span class="table_title">Terpènes</span></h4>
                <?php } else { ?>
                <h4 class="table_row" data-line-height="xs"><span class="table_title">Terpenes</span></h4>
                <?php } ?>

                <p class="table_row" data-line-height="xs"><?php echo $details['terpenes']; ?></p>
                <hr>
            </div>
        </div>
        <div class="col medium-4 small-12 large-4">
            <div class="col-inner">
                <?php if (ICL_LANGUAGE_CODE == 'fr') { ?>
                <h4 class="table_row" data-line-height="xs"><span class="table_title">Lieu de culture</span></h4>
                <?php } else { ?>
                <h4 class="table_row" data-line-height="xs"><span class="table_title">Growing Location</span></h4>
                <?php } ?>

                <p class="table_row" data-line-height="xs"><?php echo $details['origin']; ?></p>
                <hr>

                <h4 class="table_row" data-line-height="xs"><span class="table_title">Distribution</span></h4>
                <p class="table_row" data-line-height="xs"><?php echo $details['distribution']; ?></p>
                <hr>
            </div>
        </div>
    </div>
</section>

        <!-- Varieties Rotation -->
        <?php

        // fetch attributed varieties from ACF
        $varieties_list = [];
        while (have_rows('varieties_rotation')) {
            the_row();
            $var_id = get_sub_field('variety_auto');
            $varieties_list[] = $var_id;
        }

        if (empty($varieties_list)) {

            global $wpdb;
            global $product;
            $product_id = $product->get_id();

            // get current product GTIN
            $gtin = get_field('gtin', $product_id);
            $product_url = $product->get_product_url();
            $product_url = explode('/', $product_url);
            // get SKU from product URL
            if (empty($gtin)) {
                $gtin = [end($product_url)];
            }

            // cache varieties titles for lookup by title
            $v_cache_lookup = [];
            $current_language = apply_filters('wpml_current_language', null);
            $args = array(
                'post_type' => 'featured_item', // Specify the custom post type
                'posts_per_page' => -1,             // Set to -1 to get all posts
                'post_status' => 'publish',      // Only get published posts
                'lang' => $current_language, // Filter by WPML post language
                'suppress_filters' => false, // Ensure WPML filters apply
            );

            $query = new WP_Query($args);

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $title = get_the_title();
                    $id = get_the_ID();
                    $v_cache_lookup[bleuh_normalize_title(html_entity_decode($title))] = $id;
                }
            }

            wp_reset_postdata(); // Reset the global $post object

            // fetch varieties automatically and merge with previous dataset
            $prep = $wpdb->prepare(
                "SELECT v.*, l.*
                    FROM " . $wpdb->prefix . "bleuh_vars v
                    LEFT JOIN " . $wpdb->prefix . "bleuh_lots l
                        ON TRIM(LOWER(v.lot)) = TRIM(LOWER(l.lot))
                    WHERE TRIM(LOWER(v.SQDC_SKU)) LIKE TRIM(LOWER(%s))
                    AND v.qty > 0
                    ORDER BY l.wrap_date DESC
                ", $gtin);
            $ret = $wpdb->get_results($prep, ARRAY_A);

            foreach ($ret as $row) {
                $name = $row["variety_name"] ?? "";
                if (empty($name)) {
                    continue;
                }
                $key = bleuh_normalize_title(html_entity_decode($name));
                if (isset($v_cache_lookup[$key])) {
                    $var_id = $v_cache_lookup[$key];
                    $varieties_list[] = $var_id;
                }
            }
        }

        $varieties_list = array_unique($varieties_list);
        $cols = count($varieties_list);

        if ($cols) {

        ?>
<section class="section p-sec vars-rot-sec">
        <?php if (ICL_LANGUAGE_CODE == 'fr') { ?>
            <h3 class="h">Nos variétés en rotation</h3>
        <?php } else { ?>
            <h3 class="h">Our Strains On Rotation</h3>
        <?php } ?>

        <div class="var-rotate row prod-port large-columns-4 medium-columns-3 small-columns-1 row-collapse row-full-width row-masonry">
            <div class="row">
                <div class="related-vars">
                    <?php
                        echo bleuh_render_posts($varieties_list);
                    ?>
                </div>
            </div>
        </div>
</section>
<?php
        }
?>

<!-- Related Products - Cherry-picked only. -->
<section class="section p-sec rel-prod-sec">
<?php if (have_rows('products_related')) { ?>
    <?php if (ICL_LANGUAGE_CODE == 'fr') { ?>
        <h3 class="h">Vous pourriez aussi être intéressé(e) par</h3>
    <?php } else { ?>
        <h3 class="h">You Might Also Be Interested In</h3>
    <?php } ?>

    <?php

    // get gtins array
    $gtins = [];
    while (have_rows('products_related')) {
        the_row();
        $pid = get_sub_field('product_auto');

        // get GTIN from ACF
        $gtin = get_field('gtin', $pid);

        // fallback to gtin from product URL
        if (empty($gtin)) {
            $gtin = get_post_meta($pid, '_product_url', true);
            $gtin = explode('/', $gtin);
            $gtin = end($gtin);
        }

        if (!empty($gtin)) {
            $gtins[$pid] = $gtin;
        }
    }

    $ids = array_keys($gtins);
    $ids = array_unique($ids);
    $swiper_id = md5('blht' . time());
    echo bleuh_render_products($swiper_id, $ids, true);

    ?>


<?php } ?>
</section>