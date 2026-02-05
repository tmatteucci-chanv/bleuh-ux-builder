<?php

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
die( 'Sorry, you are not allowed to access this page directly.' );
}

// template rendering for slider
function bleuh_image_carousel_render($atts) {
    extract(shortcode_atts(array(
        'ex_image'    => '',
        'prod_image'    => '',
        'var_image'     => '',
        'var_rotation'  => '',
        'var'       => '',
        'prod'      => '',
        'url'       => '',
        'tags'      => '',
        'url_text'  => '',
        'thc'       => '',
    ), $atts));
    ob_start();
    include plugin_dir_path( __FILE__ ).'../templates/slide.php';
    return ob_get_clean();

}
function bleuh_slider_render($atts, $content=null) {
    ob_start();
    include plugin_dir_path( __FILE__ ).'../templates/slider.php';
    return ob_get_clean();
}

// backend implementation for UX builder
function bleuh_custom_init() {

    // register translations
    if ( function_exists('icl_register_string') ) {
        icl_register_string('bleuh', 'dispo', 'Disponibilité',false,"fr");
        icl_register_string('bleuh', 'thc-percent', 'THC',false,"fr");
        icl_register_string('bleuh', 'date-wrapped', 'Emballage',false,"fr");
    }

    // Check if function exists to avoid errors if UX Builder is not active.
    if ( function_exists( 'add_ux_builder_shortcode' ) ) {

        // get variety list
        $args = array(
            'post_type' => 'featured_item',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'order' => 'ASC',
            'suppress_filters' => false, // Allow WPML to filter the query by the current language
        );
        $query = new WP_Query($args);
        global $variety_list;
        $variety_list = ['' => ' '];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $variety_list[get_the_ID()] = get_the_title() . " (" . get_the_permalink() .")";
            }
        }
        wp_reset_postdata();
        asort($variety_list);

        // get product list
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'order' => 'ASC',
            'suppress_filters' => false, // Allow WPML to filter the query by the current language
        );
        $loop = new WP_Query($args);
        $product_list = ['' => ' '];
        if ($loop->have_posts()) {
            while ($loop->have_posts()) {
                $loop->the_post();
                $product_list[get_the_ID()] = get_the_title() . " (" . get_the_permalink() .")";
            }
        }
        wp_reset_postdata();
        asort($product_list);

        // add ux option in ux builder
        add_ux_builder_shortcode('bleuh_image_carousel', array(
            'name' => __('Image de Carrousel (Bleuh)'),
            'category' => __('Bleuh'),
            'wrap' => true,
            'options' => array(
                'tags' => array(
                    'type' => 'textfield',
                    'heading' => __('Tags'),
                    'default' => '',
                    'placeholder' => __("Entrer les tags ici (Séparés par virgules)"),
                    'group' => 'Entête'
                ),
                'thc' => array(
	                'type' => 'textfield',
	                'heading' => __('THC'),
	                'default' => '',
	                'placeholder' => __("Override THC"),
	                'group' => 'Entête'
                ),
                'ex_image' => array(
                    'type' => 'image',
                    'heading' => __('Image de manchette'),
                    'group' => 'Entête'
                ),
                'prod_image' => array(
                    'type' => 'image',
                    'heading' => __('Image du Produit'),
                    'group' => 'Produit'
                ),
                'prod' => array(
                    'type' => 'select',
                    'heading' => __('Choix du produit'),
                    'options' => $product_list,
                    'group' => 'Produit'
                ),
                'var_image' => array(
                    'type' => 'image',
                    'heading' => __('Image de variété'),
                    'group' => 'Variété'
                ),
                'var_rotation' => array(
                    'type' => 'select',
                    'heading' => __("Rotation de l'image de variété"),
                    'default' => "",
                    'options' => [
                        "" => "",
                        "0" => "0 degrées",
                        "90" => "90 degrées",
                        "180" => "180 degrées",
                        "270" => "270 degrées"
                    ],
                    'group' => 'Variété'
                ),
                'var' => array(
                    'type' => 'select',
                    'heading' => __('Choix de la variété'),
                    'options' => $variety_list,
                    'group' => 'Variété'
                ),
                'url_text' => array(
                    'type' => 'textfield',
                    'heading' => __('Texte du lien'),
                    'default' => '',
                    'placeholder' => __("Entrer le text ici (Laissez vide pour ne pas afficher)"),
                    'group' => 'Lien'
                ),
                'url' => array(
                    'type' => 'textfield',
                    'heading' => __('URL pour achat Web'),
                    'default' => '',
                    'placeholder' => __("Entrer l'URL ici (Laissez vide pour ne pas afficher)"),
                    'group' => 'Lien'
                ),
            ),
        ));

        // make ux bleuh slider
        add_ux_builder_shortcode('bleuh_slider', array(
            'type' => 'container',
            'name' => __('Bleuh Slider'),
            'category' => __('Bleuh'),
            'allow' => array( 'bleuh_image_carousel'),
            'wrap' => false,
            'children' => array(
                'inline' => true,
                'addable_spots' => array( 'left', 'right' )
            ),
            'toolbar' => array(
                'show_children_selector' => true,
                'show_on_child_active' => true,
            ),
            'options' => array(
                'text_color' => array(
                    'type' => 'colorpicker',
                    'heading' => __('Couleur de texte'),
                    'format' => 'rgb',
                    'default' => 'rgb(9, 38, 138)',
                    'position' => 'bottom right',
                ),
                'outerglow_color' => array(
                    'type' => 'colorpicker',
                    'heading' => __('Couleur de lueur externe'),
                    'format' => 'rgb',
                    'default' => 'rgb(220, 253, 178)',
                    'position' => 'bottom right',
                ),
            ),
        ));
    }
}

// Slider (Swiper) implementation
function bleuh_enqueue_css_and_scripts() {
    // https://www.jqueryscript.net/slider/Responsive-Flexible-Mobile-Touch-Slider-Swiper.html
    wp_enqueue_style('swiper', "https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css");
    wp_enqueue_script('swiper', "https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js", array('jquery', 'jquery-cookie', 'jquery-match-height'), BLEUH_CURRENT_VERSION);

    wp_enqueue_style('bleuh_ux', plugins_url('../css/ux.css', __FILE__), null, BLEUH_CURRENT_VERSION );
}

// Init
add_action('ux_builder_setup', 'bleuh_custom_init');
add_action( 'wp_enqueue_scripts', 'bleuh_enqueue_css_and_scripts' );

// Frontend only
add_shortcode('bleuh_image_carousel', 'bleuh_image_carousel_render');
add_shortcode('bleuh_slider', 'bleuh_slider_render');

