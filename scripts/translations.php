<?php

function bleuh_register_all_translation_strings() {
    $current_user = wp_get_current_user();
    if (in_array('administrator', $current_user->roles)) {
        if (function_exists('icl_register_string')) {
            icl_register_string('bleuh', 'var_store_locator_h1', "Magasinez par détaillant", false, "fr");
            icl_register_string('bleuh', 'var_store_store', "Détaillant", false, "fr");
            icl_register_string('bleuh', 'var_store_locator_locate_me', "Localisez-moi", false, "fr");
            icl_register_string('bleuh', 'var_store_locator_inventory', "Inventaire", false, "fr");
            icl_register_string('bleuh', 'var_store_locator_locate', "Localisation", false, "fr");
            icl_register_string('bleuh', 'var_store_locator_more_stores', "Voir plus de succursales", false, "fr");
            icl_register_string('bleuh', 'var_store_locator_batch', 'Lot', false, "fr");
            icl_register_string('bleuh', 'var_store_locator_inventory_of', 'Inventaire de', false, "fr");
            icl_register_string('bleuh', 'var_store_locator_date_wrap', "Date d'emballage", false, "fr");
            icl_register_string('bleuh', 'caption_units', "unités", false, "fr");
            icl_register_string('bleuh', 'caption_other_units', "Autres unités disponibles", false, "fr");
            icl_register_string('bleuh', 'caption_var_in_store', "en magasin", false, "fr");
            icl_register_string('bleuh', 'caption_var_in_backstore', "en entrepôt", false, "fr");
            icl_register_string('bleuh', 'caption_units_available', "UNITÉS DISPONIBLES", false, "fr");
            icl_register_string('bleuh', 'caption_varieties_in_stock', "Variété en stock", false, "fr");
            icl_register_string('bleuh', 'caption_varieties_to_come', "Variété à venir", false, "fr");
            icl_register_string('bleuh', 'caption_online', "En ligne", false, "fr");

            // age gate
            icl_register_string('bleuh', 'caption_ag_title', "Êtes-vous majeur dans votre juridiction et acceptez-vous d'utiliser des témoins?", false, "fr");
            icl_register_string('bleuh', 'caption_ag_blurb', 'En cliquant sur «&nbsp;Entrer&nbsp;», vous confirmez que vous avez l\'âge légal pour afficher le contenu de ce site Web et que ce site utilise des témoins, également appelés « Cookies », dans le but de vous offrir une expérience de navigation maximale. Souhaitez-vous nous accorder votre confiance quant à l\'utilisation de vos données conformément à notre <a target="_blank" href="/politique-de-confidentialite/">politique de confidentialité?</a> Sinon, cliquez sur «&nbsp;Quitter&nbsp;» pour quitter.', false, "fr");
            icl_register_string('bleuh', 'caption_ag_quit', "Quitter", false, "fr");
            icl_register_string('bleuh', 'caption_ag_enter', "Entrer", false, "fr");
            icl_register_string('bleuh', 'caption_ag_error', "Vous n’êtes pas assez vieux pour voir ce contenu et/ou vous n'acceptez pas d'utiliser des témoins.", false, "fr");

            // product page ontario
            icl_register_string('bleuh', 'on_product_blurb', "Bien que nous mettions tout en œuvre pour assurer l’exactitude des disponibilités en magasin, certaines informations peuvent varier. Nous vous recommandons de contacter le commerce avant de vous déplacer.", false, "fr");
        }
    }
}

add_action('init', 'bleuh_register_all_translation_strings');
