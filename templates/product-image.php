<?php
/**
 * Single Product Image
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see              https://docs.woocommerce.com/document/template-structure/
 * @package          WooCommerce\Templates
 * @version          7.8.0
 * @flatsome-version 3.17.2
 *
 * @flatsome-parallel-template {
 * product-image-default.php
 * product-image-stacked.php
 * product-image-vertical.php
 * product-image-wide.php
 * }
 */

defined( 'ABSPATH' ) || exit;

// FL: Disable check, Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
//if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
//	return;
//}

if ( get_theme_mod( 'product_gallery_woocommerce' ) ) {
	wc_get_template_part( 'single-product/product-image', 'default' );

	return;
}

if ( get_theme_mod( 'product_layout' ) == 'gallery-wide' ) {
	wc_get_template_part( 'single-product/product-image', 'wide' );

	return;
}

if ( get_theme_mod( 'product_layout' ) == 'stacked-right' ) {
	wc_get_template_part( 'single-product/product-image', 'stacked' );

	return;
}

if ( get_theme_mod( 'product_image_style' ) == 'vertical' ) {
	wc_get_template_part( 'single-product/product-image', 'vertical' );

	return;
}

global $product;

$columns           = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
$post_thumbnail_id = $product->get_image_id();
$wrapper_classes   = apply_filters( 'woocommerce_single_product_image_gallery_classes', array(
	'woocommerce-product-gallery',
	'woocommerce-product-gallery--' . ( $product->get_image_id() ? 'with-images' : 'without-images' ),
	'woocommerce-product-gallery--columns-' . absint( $columns ),
	'images',
) );

$slider_classes = array('product-gallery-slider','slider','slider-nav-small','mb-half');

// Image Zoom
if(get_theme_mod('product_zoom', 0)){
  $slider_classes[] = 'has-image-zoom';
}

$rtl = 'false';
if(is_rtl()) $rtl = 'true';

if(get_theme_mod('product_lightbox','default') == 'disabled'){
  $slider_classes[] = 'disable-lightbox';
}

?>
<?php do_action('flatsome_before_product_images'); ?>

<div class="product-images relative mb-half has-hover <?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>">

  <?php do_action('flatsome_sale_flash'); ?>

  <div class="image-tools absolute top show-on-hover right z-3">
    <?php do_action('flatsome_product_image_tools_top'); ?>
  </div>

    <?php
        $pid = $product->get_id();
        $like_data = get_fav_counts([$pid]);
        $favorites_count = $like_data[$pid]['count'] ?? 0;
        $liked = ($like_data[$pid]['liked']) ? 'liked' : 'not-liked';

        echo '<span data-id="'.$pid.'" class="like-box '.$liked.'"><span class="counter">' . esc_attr($favorites_count) . '</span><i class="ico"></i></span>';
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
    }
    ?>

  <div class="woocommerce-product-gallery__wrapper <?php echo implode(' ', $slider_classes); ?>"
        data-flickity-options='{
                "cellAlign": "center",
                "wrapAround": true,
                "autoPlay": false,
                "prevNextButtons":true,
                "adaptiveHeight": true,
                "imagesLoaded": true,
                "lazyLoad": 1,
                "dragThreshold" : 15,
                "pageDots": false,
                "rightToLeft": <?php echo $rtl; ?>
       }'>
    <?php
    if ( $product->get_image_id() ) {
      $html  = flatsome_wc_get_gallery_image_html( $post_thumbnail_id, true );
    } else {
      $html  = '<div class="woocommerce-product-gallery__image--placeholder">';
      $html .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
      $html .= '</div>';
    }

		echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, $post_thumbnail_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped

    do_action( 'woocommerce_product_thumbnails' );
    ?>
  </div>

  <div class="image-tools absolute bottom left z-3">
    <?php do_action('flatsome_product_image_tools_bottom'); ?>
  </div>
</div>
<?php do_action('flatsome_after_product_images'); ?>

<?php // wc_get_template( 'woocommerce/single-product/product-gallery-thumbnails.php' ); ?>
