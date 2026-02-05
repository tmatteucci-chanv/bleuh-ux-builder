<?php

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

// template rendering
function bleuh_rotation_widget_item_render($atts) {
    extract(shortcode_atts(array(
        'var_image'     => '',
        'var_rotation'  => '',
        'var'       => '',
        'url'       => '',
        'url_text'  => '',
    ), $atts));
    ob_start();
    include plugin_dir_path( __FILE__ ).'../templates/rotation-widget-item.php';
    return ob_get_clean();

}
function bleuh_rotation_widget_render($atts, $content=null) {
    ob_start();
    include plugin_dir_path( __FILE__ ).'../templates/rotation-widget.php';
    return ob_get_clean();
}

function bleuh_custom_init_rotate_widget() {

    if (function_exists('add_ux_builder_shortcode')) {

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

        // add ux option in ux builder
        add_ux_builder_shortcode('bleuh_rotation_item', array(
            'name' => __('Item pour le widget de rotation'),
            'category' => __('Bleuh'),
            'wrap' => true,
            'options' => array(
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
        add_ux_builder_shortcode('bleuh_rotation_widget', array(
            'type' => 'container',
            'name' => __('Bleuh Rotation Widget'),
            'category' => __('Bleuh'),
            'allow' => array( 'bleuh_rotation_item'),
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
                'bg_color' => array(
                    'type' => 'colorpicker',
                    'heading' => __('Couleur d`arrière-plan'),
                    'format' => 'rgb',
                    'default' => 'rgb(9, 38, 138)',
                    'position' => 'bottom right',
                ),
            ),

        ));

    }

}

// Init
add_action('ux_builder_setup', 'bleuh_custom_init_rotate_widget');

// Frontend only
add_shortcode('bleuh_rotation_item', 'bleuh_rotation_widget_item_render');
add_shortcode('bleuh_rotation_widget', 'bleuh_rotation_widget_render');

