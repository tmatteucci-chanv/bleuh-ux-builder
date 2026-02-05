<?php
// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

global $rwi;
global $bleuh_rw_atts;
$rwi--;

// variety attributes
$var_url = "";
$var_title = "";
if (!empty($var)) {
    $var_id = apply_filters('wpml_object_id', $var, 'featured_item', false, ICL_LANGUAGE_CODE);
    $var_attrs_id = apply_filters('wpml_object_id', $var, 'featured_item', false, "fr");
    $var_date = DateTime::createFromFormat('d/m/Y', get_field('date_demballage', $var_attrs_id));
    $var_url = get_permalink($var_id);
    $var_title = get_the_title($var_id);
}

?>
<div class="fader-slide" style="position:absolute;display:none;background:<?php echo $bleuh_rw_atts['bg_color']; ?>;">
    <div class="rw-main">
            <!-- rotation circle image -->
            <?php
            if ($rwi >= 1 && $rwi <= 8) {
                ?>
                <img class="rw-circle outer-image" src="<?php echo plugin_dir_url( __FILE__ ).'../img/widget-rotation/Cercle'.$rwi.'.svg'; ?>" alt="Rotation" />
                <?php
            }
            ?>

            <!-- Variety image -->
            <?php
            $image_id = $var_image ?? null;

            if (!empty($var_rotation)) $var_rotation = 'style="transform: rotate('.$var_rotation.'deg);"';

            if ($image_id) {
                $image_url = wp_get_attachment_url( $image_id );

                if ( $image_url ) {
                    ?>
                    <a href="<?php echo esc_url( $var_url ); ?>"><img <?php echo $var_rotation; ?> class="rw-variety inner-image" src="<?php echo esc_url( $image_url ); ?>" alt="<?php // echo $var_title; ?>" /></a>
                    <?php
                }
            }
            ?>

    </div>
    <a class="rw-sub" href="<?php echo esc_url( $var_url ); ?>"><h3><?php echo $var_title; ?></h3></a>
</div>

