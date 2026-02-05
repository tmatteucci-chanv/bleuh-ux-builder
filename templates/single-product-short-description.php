<?php

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

/* Template is used to override UX Builder template to "questions" from ACF */
?>

<!-- tags: Blend, THC, weight, rotation-->
<?php

    global $product;
    if (!$product) {
        $product = wc_get_product(get_the_ID()); // Get the product object explicitly.
    }
    $product_id = $product ? $product->get_id() : 0;

    // get GTIN from ACF
    $gtin = get_field('gtin', $product_id);

    // fallback to gtin from product URL
    if (empty($gtin)) {
        $gtin = get_post_meta($product_id, '_product_url', true);
        $gtin = explode('/', $gtin);
        $gtin = end($gtin);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'bleuh_products';
    $query = "SELECT GTIN, blend, weight FROM $table_name WHERE GTIN = %s;";
    $prepared_query = $wpdb->prepare($query, $gtin);
    $ret = $wpdb->get_results($prepared_query, ARRAY_A);

    $strain = "";
    $cbd = "";
    $thc = "";
    $weight = "";
    if (!empty($gtin)) {
        if (isset($ret[0])) {
            $strain = $ret[0]['blend'];
            $weight = $ret[0]['weight'];
        }
    }
    $thc_acf = get_field('thc', $product_id);
    if (!empty($thc_acf)) {
        $thc = $thc_acf;
        if (!str_contains(strtolower($thc), 'thc')) $thc = 'THC: '.$thc;
    }

    $cbd_acf = get_field('cbd', $product_id);
    if (!empty($cbd_acf)) {
        $cbd = $cbd_acf;
        if (!str_contains(strtolower($cbd), 'cbd')) $cbd = 'CBD: '.$cbd;
    }

    // Get the list of product categories as an array
    $product_categories = strtolower(strip_tags(trim(wc_get_product_category_list($product_id))));

    // Check if "sativa" or "indica" exists in the categories
    if ('sativa' == $product_categories) {
        $strain = 'Sativa';
    } elseif ('indica' == $product_categories) {
        $strain = 'Indica';
    } else {
        $strain = 'Hybrid';
    }

    $tags = [
        !empty($strain) ? $strain : '',
        !empty($thc) ? $thc : '',
        !empty($cbd) ? $cbd : '',
        !empty($weight) ? $weight : '',
        "Rotation"
    ];

    $color = "#ff8300";
    switch (trim(strtolower($strain))) {
        case "sativa":
            $color = "#ffd100";
            break;
        case "indica":
            $color = "#f095cd";
            break;
    }

    // get the is_ontario field
    $is_ontario_product = get_field('is_ontario');
    if (empty($is_ontario_product)) {
        $is_ontario_product = false;
    } else {
        $is_ontario_product = true;
    }

    // get tags override
    $tags_override = get_field('tags_override');
    if (!empty($tags_override) > 0) {
        $tags = [];
        foreach ($tags_override as $tag) {
            $tags[] = $tag['tag'];
        }
    }

?>
<p>
    <?php foreach ($tags as $tag) {
            if (!empty($tag)) { ?>
                <b class="sativa" style="background-color: <?php echo $color; ?> !important;"><?php echo $tag; ?></b>&nbsp;&nbsp;&nbsp;
    <?php   }
    } ?>
</p>

<hr/>

<!-- short description -->
<div class="bleuh-single-product-description">
    <?php the_field('short_description'); ?>
</div>

<hr/>

<?php // if (!$is_ontario_product) { ?>
    <p class="product-short-description">
        <span style="font-size: 80%;">
            <img class="alignnone wp-image-2880 size-full" src="https://bleuh.co/wp-content/plugins/bleuh-ux-builder/scripts/../img/dispo.svg" alt="" width="20" height="20">
            <?php if (ICL_LANGUAGE_CODE == 'fr') { ?>
                    <strong>Voir les disponibilités</strong>
            <?php } else { ?>
                    <strong>Check Availability</strong>
            <?php } ?>
        </span>
    </p>

    <hr/>
<?php // } ?>