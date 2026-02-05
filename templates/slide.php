<?php
// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

    global $bleuh_slider_atts;

    // get THC from UX builder attributes
    //$thc = $bleuh_slider_atts['thc'] ?? '';

    // product attributes
    $prod_url = "";
    $prod_title = "";
    if (!empty($prod)) {
        $prod_id = apply_filters('wpml_object_id', $prod, 'product', false, ICL_LANGUAGE_CODE);
        $prod_url = get_permalink($prod_id);
        $prod_title = get_the_title($prod_id);
        if (empty($thc)) {
            $thc = get_field('thc', $prod_id);
        }
        if (empty($cbd)) {
            $cbd = get_field('cbd', $prod_id);
        }
    }

    // variety attributes
    $var_url = "";
    $var_title = "";
    $var_date = "";
    if (!empty($var)) {
        $var_id = apply_filters('wpml_object_id', $var, 'featured_item', false, ICL_LANGUAGE_CODE);
        if (empty($thc)) {
            $thc = get_field('thc', $var_id);
        }
        $var_date = DateTime::createFromFormat('d/m/Y', get_field('date_demballage', $var_id));
        if ($var_date) $var_date = $var_date->format('m') . "-" . $var_date->format('Y');
        $var_url = get_permalink($var_id);
        $var_title = get_the_title($var_id);
    }

    if (!empty($thc)) {
        if (!str_contains(strtolower($thc), 'thc')) $thc = 'THC:<br>' . $thc;
    }
    if (!empty($cbd)) {
        if (!str_contains(strtolower($cbd), 'cbd')) $cbd = 'CBD:<br>' . $cbd;
    }

?>
<div class="swiper-slide">

    <?php if (!empty($tags)) { ?>
        <!-- Tags -->
        <div class="tags">
            <h3 class="dispo"><a class="qty-link" href="<?php echo esc_url( $prod_url ); ?>#qty"><?php echo icl_t('bleuh', 'dispo', 'Disponibilité'); ?></a>:</h3>
            <?php
            $tags = explode(",", $tags);
            foreach ($tags as $tag) { ?>

                <a style="color: <?php echo $bleuh_slider_atts['text_color']; ?>;background: <?php echo $bleuh_slider_atts['outerglow_color']; ?>;" href="/map/#<?php
                    echo preg_replace('/[^a-zA-Z0-9]/', '', strtolower(trim($tag)));
                ?>"><?php echo trim($tag); ?></a>

                <?php
            }
            ?>
        </div>
    <?php } ?>

    <div class="feature">

        <div class="bleuh-info-slide">

            <!-- Exclusivity image -->
            <?php
            $image_id = $ex_image ?? null;

            if ($image_id) {
                $image_url = wp_get_attachment_url( $image_id );

                if ( $image_url ) {
                    ?>
                    <img class="ex-image" src="<?php echo esc_url( $image_url ); ?>" alt="" />
                    <?php
                }
            }
            ?>

            <?php if (!empty($thc)) { ?><div style="color: <?php echo $bleuh_slider_atts['text_color']; ?>;background: <?php echo $bleuh_slider_atts['outerglow_color']; ?>;" class="THC"><?php echo $thc; ?></div><?php } ?>
            <?php if (!empty($cbd)) { ?><div style="color: <?php echo $bleuh_slider_atts['text_color']; ?>;background: <?php echo $bleuh_slider_atts['outerglow_color']; ?>;" class="CBD"><?php echo $cbd; ?></div><?php } ?>
            <?php if (!empty($var_date)) { ?><div style="color: <?php echo $bleuh_slider_atts['text_color']; ?>;background: <?php echo $bleuh_slider_atts['outerglow_color']; ?>;" class="Date"><?php echo icl_t('bleuh',"date-wrapped", "Emballage"); ?><br><?php echo $var_date; ?></div><?php } ?>
        </div>

        <!-- Variety image -->
        <?php
        $image_id = $var_image ?? null;

        if (!empty($var_rotation)) $var_rotation = 'style="transform: rotate('.$var_rotation.'deg);"';

        if ($image_id) {
            $image_url = wp_get_attachment_url( $image_id );

            if ( $image_url ) {
                ?>
                <a href="<?php echo esc_url( $var_url ); ?>"><img <?php echo $var_rotation; ?> class="variety" src="<?php echo esc_url( $image_url ); ?>" alt="<?php // echo $var_title; ?>" /></a>
                <?php
            }
        }
        ?>

        <!-- Product image -->
        <?php
        $image_id = $prod_image ?? null;

        if ($image_id) {
            $image_url = wp_get_attachment_url( $image_id );

            if ( $image_url ) {
        ?>
                <a href="<?php echo esc_url( $prod_url ); ?>"><img class="product" src="<?php echo esc_url( $image_url ); ?>" alt="<?php // echo $prod_title; ?>" width="100%" height="auto" /></a>
        <?php
            }
        }
        ?>
    </div>

    <table class="info-box">
        <tr>
            <td>
                <?php if (!empty($var_url)) { ?>
                <h2><a href="<?php echo esc_url( $var_url ); ?>"><?php echo $var_title; ?></a>
                        <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                             viewBox="0 0 451.8 451.8" style="enable-background:new 0 0 451.8 451.8;" xml:space="preserve">
                            <g>
                                <path d="M354.7,225.9c0,8.1-3.1,16.2-9.3,22.4L151.2,442.6c-12.4,12.4-32.4,12.4-44.8,0c-12.4-12.4-12.4-32.4,0-44.7l171.9-171.9
                                L106.4,54c-12.4-12.4-12.4-32.4,0-44.7c12.4-12.4,32.4-12.4,44.7,0l194.3,194.3C351.6,209.7,354.7,217.8,354.7,225.9z"/>
                            </g>
                        </svg></h2>
                <?php } ?>
                <?php if (!empty($prod_url)) { ?>
                <h3><a href="<?php echo esc_url( $prod_url ); ?>"><?php echo $prod_title; ?></a>
                        <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                             viewBox="0 0 451.8 451.8" style="enable-background:new 0 0 451.8 451.8;" xml:space="preserve">
                            <g>
                                <path d="M354.7,225.9c0,8.1-3.1,16.2-9.3,22.4L151.2,442.6c-12.4,12.4-32.4,12.4-44.8,0c-12.4-12.4-12.4-32.4,0-44.7l171.9-171.9
                                L106.4,54c-12.4-12.4-12.4-32.4,0-44.7c12.4-12.4,32.4-12.4,44.7,0l194.3,194.3C351.6,209.7,354.7,217.8,354.7,225.9z"/>
                            </g>
                        </svg></h3>
                <?php } ?>
            </td>
            <?php
                // determine icon based on if the link is external or not
                $is_external = parse_url($url, PHP_URL_HOST) !== parse_url(get_site_url(), PHP_URL_HOST);
            ?>
            <td>
                <a <?php if ($is_external) echo 'target="_blank"'; ?> href="<?php echo esc_url( $url ); ?>"><?php echo $url_text; ?>
<?php if ($is_external) { ?>
    <svg id="open_in_new_FILL0_wght400_GRAD0_opsz24" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12">
                        <path id="open_in_new_FILL0_wght400_GRAD0_opsz24-2" data-name="open_in_new_FILL0_wght400_GRAD0_opsz24" d="M121.333-828a1.284,1.284,0,0,1-.942-.392,1.284,1.284,0,0,1-.392-.942v-9.333a1.284,1.284,0,0,1,.392-.942,1.284,1.284,0,0,1,.942-.392H126v1.333h-4.667v9.333h9.333V-834H132v4.667a1.284,1.284,0,0,1-.392.942,1.284,1.284,0,0,1-.942.392Zm3.133-3.533-.933-.933,6.2-6.2h-2.4V-840H132v4.667h-1.333v-2.4Z" transform="translate(-120 840)" fill="#0a3baf"/>
                    </svg>
<?php } else { ?>
    <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                         viewBox="0 0 451.8 451.8" style="enable-background:new 0 0 451.8 451.8;" xml:space="preserve">
                        <g>
                            <path d="M354.7,225.9c0,8.1-3.1,16.2-9.3,22.4L151.2,442.6c-12.4,12.4-32.4,12.4-44.8,0c-12.4-12.4-12.4-32.4,0-44.7l171.9-171.9
                            L106.4,54c-12.4-12.4-12.4-32.4,0-44.7c12.4-12.4,32.4-12.4,44.7,0l194.3,194.3C351.6,209.7,354.7,217.8,354.7,225.9z"/>
                        </g>
                    </svg>
<?php } ?>
                </a><br/>
<?php if (!empty($prod_url)) { ?>
                    <a class="qty-link" href="<?php echo esc_url( $prod_url ); ?>#qty"><?php echo icl_t('bleuh', 'dispo_header', 'Disponibilité en succursale'); ?></a>
<?php } ?>
            </td>
        </tr>
    </table>

</div>
