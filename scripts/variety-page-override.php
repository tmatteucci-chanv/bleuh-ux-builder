<?php

defined('ABSPATH') || exit;

// Filter the content for the "featured_item" post type
add_filter('the_content', 'bleuh_replace_long_description_variety', 20);

function bleuh_replace_long_description_variety($content) {
    if (is_singular('featured_item')) {
        // Check if the ACF field 'short_description' is not empty
        $acf_description = get_field('short_description');
        if (!empty($acf_description)) {
            // Path to your custom template file
            $template_file = plugin_dir_path(__FILE__) . '/../templates/single-variety-page.php';

            // Use the custom template if it exists
            if (file_exists($template_file)) {
                ob_start(); // Start output buffering
                include $template_file;
                $custom_description = ob_get_clean(); // Get the contents of the buffer

                return $custom_description; // Replace the original content
            }
        }
    }

    return $content; // Return the original content if not replaced
}
