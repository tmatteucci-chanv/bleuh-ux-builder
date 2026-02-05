<?php

defined( 'ABSPATH' ) || exit;

function extract_range(string $text): array {
    $text = strtolower(trim($text));

    // Replace any known Unicode dash/minus variants with a normal hyphen
    $text = str_replace(
            ["–", "—", "−", "‑", "‒"],
            "-",
            $text
    );

    // Remove labels & extras
    $text = str_replace(["cbd", "thc"], "", $text);

    $min = $max = 0;

    // Range regex — tolerant of % right after numbers
    if (preg_match('/(\d+(?:[.,]\d+)?)(?:\s*%?)\s*-\s*(\d+(?:[.,]\d+)?)/u', $text, $m)) {
        $min = (float) str_replace(',', '.', $m[1]);
        $max = (float) str_replace(',', '.', $m[2]);
    } elseif (preg_match('/(\d+(?:[.,]\d+)?)/', $text, $m)) {
        $min = $max = (float) str_replace(',', '.', $m[1]);
    }

    return ['min' => $min, 'max' => $max];
}

function bleuh_render_fav_products( $ids , $with_filter_attributes = false ) {
    global $wpdb; // Declare $wpdb for database queries
    global $product; // Declare the global $product object

    foreach ( $ids as $pid ) {
        // Get the WooCommerce product object
        $product = wc_get_product( $pid );

        // Ensure it's a valid product and is visible
        if ( ! is_a( $product, WC_Product::class ) ) {
            continue;
        }

        // Extra product classes
        $classes   = array( 'product-small', 'col', 'has-hover' );

        // Start rendering the product HTML
        $data_tags_html = '';
        ?>
        <div <?php wc_product_class( $classes, $product ); ?>
                <?php
                if ( $with_filter_attributes ) {

                    // Get product attributes for filtering
                    $provs = [];
                    if (get_field('is_ontario', $pid)) {
                        $provs[] = 'on';
                    }
                    if (get_field('is_qc', $pid)) {
                        $provs[] = 'qc';
                    }
                    // default province is QC
                    if (empty($provs)) {
                        $provs[] = 'qc';
                    }
                    $provs = implode(', ', $provs);

                    // get categories (i.e. Indica, Sativa, Hybrid)
                    $categories = [];
                    $terms = get_the_terms($pid, 'product_cat');
                    if ($terms && !is_wp_error($terms)) {
                        foreach ($terms as $category) {
                            $categories[] = $category->name;
                        }
                    }
                    $categories = implode(', ', $categories);

                    // get tags
                    $tags = [];
                    $p_tags = get_the_terms($pid, 'product_tag');
                    if ($p_tags && !is_wp_error($p_tags)) {
                        foreach ($p_tags as $tag) {
                            $att = strtolower($tag->name);
                            $tags[$att] = $att;
                        }
                    }
                    $tags = implode(', ', $tags);


                    // formats
                    $formats = [];
                    $att = $product->get_attribute('pa_formats');
                    $atts = explode(',', $att);
                    foreach ($atts as $a) {
                        if (!empty(trim($a))) $formats[trim($a)] = trim($a);
                    }
                    $formats = implode(', ', $formats);

                    // get collections
                    $collections = [];
                    $att = $product->get_attribute('pa_marques');
                    $atts = explode(',', $att);
                    foreach ($atts as $a) {
                        if (!empty(trim($a))) $collections[trim($a)] = trim($a);
                    }
                    $collections = implode(', ', $collections);

                    // details
                    $effects = [];
                    $terpenes = [];

                    if (have_rows('details', $pid)) {
                        while (have_rows('details', $pid)) {
                            the_row();

                            // effects
                            $att = get_sub_field('effets_potentiels');
                            $att = str_replace(['–', '—', '−'], '-', $att);
                            $atts = explode('-', $att);
                            foreach ($atts as $a) {
                                if (!empty(trim($a))) $effects[trim(strtolower($a))] = trim($a);
                            }

                            // terpenes
                            $att = get_sub_field('terpenes');
                            $att = str_replace(['–', '—', '−'], '-', $att);
                            $atts = explode('-', $att);
                            foreach ($atts as $a) {
                                if (!empty(trim($a))) $terpenes[trim(strtolower($a))] = trim($a);
                            }

                        }
                    }
                    $effects = implode(', ', $effects);
                    $terpenes = implode(', ', $terpenes);

                    $data_thc = get_field('thc', $pid);
                    if (empty($data_thc)) $data_thc = $product->get_attribute('pa_intensite');

                    $thc = extract_range($data_thc);
                    $thc_min = $thc['min'];
                    $thc_max = $thc['max'];

                    $data_tags_html .= 'data-pid="' . esc_attr( $pid ) . '" ';
                    $data_tags_html .= 'data-province="' . esc_attr( $provs ) . '" ';
                    $data_tags_html .= 'data-availability="' . esc_attr( $tags ) . '" ';
                    $data_tags_html .= 'data-thc-min="' . esc_attr( $thc_min ) . '" ';
                    $data_tags_html .= 'data-thc-max="' . esc_attr( $thc_max ) . '" ';
                    $data_tags_html .= 'data-categories="' . esc_attr( $categories ) . '" ';
                    $data_tags_html .= 'data-terpenes="' . esc_attr( strtolower( $terpenes ) ) . '" ';
                    $data_tags_html .= 'data-effects="' . esc_attr( strtolower( $effects ) ) . '" ';
                    $data_tags_html .= 'data-formats="' . esc_attr( $formats ) . '" ';
                    $data_tags_html .= 'data-collections="' . esc_attr( $collections ) . '" ';
                    $w_date = get_post_field( 'post_date', $pid );
                    $data_tags_html .= 'data-wrap-date="' . esc_attr( date_i18n( 'Y-m-d', strtotime( $w_date ) ) ) . '" ';
                    $data_tags_html .= 'data-tags="' . esc_attr( $tags ) . '" ';

                }
                ?>
        >
            <div class="col-inner bleuh-loop-inner">
                <?php do_action( 'woocommerce_before_shop_loop_item' ); ?>
                <div class="product-small box <?php echo flatsome_product_box_class(); ?>">
                    <div class="box-image">
                        <div class="<?php echo flatsome_product_box_image_class(); ?> var-box-link"
                        <?php echo $data_tags_html; ?>
                        >
                            <?php
                            // Product ID and favorite counts
                            $like_data       = get_fav_counts( [ $pid ] );
                            $favorites_count = $like_data[ $pid ]['count'] ?? 0;
                            $liked           = ( $like_data[ $pid ]['liked'] ) ? 'liked' : 'not-liked';

                            // Like button
                            echo '<span data-id="' . esc_attr( $pid ) . '" class="like-box ' . esc_attr( $liked ) . '">
                                    <span class="counter">' . esc_attr( $favorites_count ) . '</span>
                                    <i class="ico"></i>
                                  </span>';
                            ?>

                            <?php
                            $is_new = get_field('is_new', $pid) ?? '';
                            if (!empty($is_new)) {
                                if (ICL_LANGUAGE_CODE == 'en') {
                                    echo '<span class="bleuh-var-fixed-attrs" style="z-index: 1 !important;left:20px;top:10px;"><img src="/wp-content/plugins/bleuh-ux-builder/templates/../img/products/new.svg" alt="New" class="new-badge"></span>';
                                } else {
                                    echo '<span class="bleuh-var-fixed-attrs" style="z-index: 1 !important;left:20px;top:10px;"><img src="/wp-content/plugins/bleuh-ux-builder/templates/../img/products/new.fr.svg" alt="Nouveau" class="new-badge"></span>';
                                }
                            } elseif (!empty(get_field('is_web_only', $pid))) {
                                if (ICL_LANGUAGE_CODE == 'en') {
                                    echo '<span class="bleuh-var-fixed-attrs" style="z-index: 1 !important;left:20px;top:10px;"><img src="/wp-content/plugins/bleuh-ux-builder/templates/../img/products/web-only.svg" alt="Web Only" class="new-badge"></span>';
                                } else {
                                    echo '<span class="bleuh-var-fixed-attrs" style="z-index: 1 !important;left:20px;top:10px;"><img src="/wp-content/plugins/bleuh-ux-builder/templates/../img/products/web-only-fr.svg" alt="Web Seulement" class="new-badge"></span>';
                                }
                            } elseif (!empty(get_field('is_coming_soon', $pid))) {
                                if (ICL_LANGUAGE_CODE == 'en') {
                                    echo '<span class="bleuh-var-fixed-attrs" style="z-index: 1 !important;left:20px;top:10px;"><img src="/wp-content/plugins/bleuh-ux-builder/templates/../img/products/coming-soon-on.svg" alt="Coming soon in Ontario" class="new-badge"></span>';
                                } else {
                                    echo '<span class="bleuh-var-fixed-attrs" style="z-index: 1 !important;left:20px;top:10px;"><img src="/wp-content/plugins/bleuh-ux-builder/templates/../img/products/coming-soon-on-fr.svg" alt="Bientôt en Ontario" class="new-badge"></span>';
                                }
                            }                            ?>

                            <a href="<?php echo esc_url( get_the_permalink( $pid ) ); ?>" aria-label="<?php echo esc_attr( $product->get_title() ); ?>">
                                <?php
                                /**
                                 * Hook: flatsome_woocommerce_shop_loop_images
                                 * - Outputs product images
                                 */
                                do_action( 'flatsome_woocommerce_shop_loop_images' );
                                ?>
                            </a>
                        </div>
                        <div class="image-tools is-small top right show-on-hover">
                            <?php do_action( 'flatsome_product_box_tools_top' ); ?>
                        </div>
                        <div class="image-tools is-small hide-for-small bottom left show-on-hover">
                            <?php do_action( 'flatsome_product_box_tools_bottom' ); ?>
                        </div>
                        <div class="image-tools <?php echo flatsome_product_box_actions_class(); ?>">
                            <?php do_action( 'flatsome_product_box_actions' ); ?>
                        </div>
                    </div>

                    <div class="box-text <?php echo flatsome_product_box_text_class(); ?>" style="height: auto !important;">
                        <?php
                            echo '<div class="title-wrapper">';
                        ?>

                        <p class="category uppercase is-smaller no-text-overflow product-cat op-8"><?php
                            // Get the list of product categories as an array
                            $product_categories = strtolower(strip_tags(trim(wc_get_product_category_list($pid))));

                            // Check if "sativa" or "indica" exists in the categories
                            if ('sativa' == $product_categories) {
                                $strain = 'sativa';
                            } elseif ('indica' == $product_categories) {
                                $strain = 'indica';
                            } else {
                                $strain = 'hybrid';
                            }

                            echo esc_html( $strain );

                            ?></p>

                        <p class="name product-title woocommerce-loop-product__title"><a href="<?php echo esc_url( get_the_permalink( $pid ) ); ?>" class="woocommerce-LoopProduct-link woocommerce-loop-product__link"><?php echo esc_html( $product->get_name() ); ?></a></p>

                        <?php
                        // Get product attributes
                        $intensity = $product->get_attribute( 'pa_intensite' );
                        $format    = $product->get_attribute( 'pa_quantite' );

                        // Fallback to ACF or database
                        if ( empty( $intensity ) ) {
                            $intensity = get_field( 'thc', $pid );
                        }
                        if ( empty( $format ) ) {
                            $format = get_field( 'weight', $pid );
                        }
                        if ( empty( $format ) ) {
                            $gtin = get_field( 'gtin', $pid );

                            // Fallback: Extract GTIN from the product URL
                            if ( empty( $gtin ) ) {
                                $gtin = get_post_meta( $pid, '_product_url', true );
                                $gtin = explode( '/', $gtin );
                                $gtin = end( $gtin );
                            }

                            // Query the database for weight
                            $table_name     = $wpdb->prefix . 'bleuh_products';
                            $query          = $wpdb->prepare( "SELECT weight FROM $table_name WHERE GTIN = %s", $gtin );
                            $result         = $wpdb->get_var( $query );
                            if ( ! empty( $result ) ) {
                                $format = $result;
                            }
                        }

                        // Display the attributes
                        if ( $intensity ) {
                            echo '<span class="custom-attribute">' . esc_html( $intensity ) . '</span><br>';
                        }
                        if ( $format ) {
                            echo '<span class="custom-attribute">' . esc_html( $format ) . '</span>';
                        }

                        echo '</div>';

                        echo '<div class="price-wrapper">';
                        do_action( 'woocommerce_after_shop_loop_item_title' );
                        echo '</div>';

                        do_action( 'flatsome_product_box_after' );
                        ?>
                    </div>
                </div>
                <?php do_action( 'woocommerce_after_shop_loop_item' ); ?>
            </div>
        </div>
        <?php
    }

    // Clean up global $product to avoid conflicts
    $product = null;
}