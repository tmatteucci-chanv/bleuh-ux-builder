<?php
defined('ABSPATH') || exit;

    // fetch info for render

    $title = get_the_title();
    $acf_description = get_field('short_description');
    $acf_tags = get_field('tags_override');
    $acf_thc = get_field('thc');
    $acf_weight = get_field('weight');
    $details = get_field('details');
    $culture = $details["culture"];
    $croisement = $details["croisement"];
    $culture_location = $details["culture_location"];
    $tags = get_field('tags_override');

    $atts = get_field("attributs");

    $is_new_img_no_txt = false;
    $full_image_url = get_the_post_thumbnail_url(get_the_ID(), 'large');
    if (!empty(get_field('img_no_txt'))) {
        $full_image_url = get_field('img_no_txt');
        $is_new_img_no_txt = true;
    }

    $is_new = (!empty(get_field('is_new')));
    $is_new_var = (!empty(get_field('is_new_var')));

    $aromas = [];
    if ($atts && isset($atts['aromes'])) {
        $aromes = $atts['aromes'];
        foreach ($aromes as $arome) {
            $aromas[] = $arome['tags'];
        }
    }
    $aromas = array_unique($aromas);

    $terpenes = [];
    if ($atts && isset($atts['terpenes'])) {
        $ter = $atts['terpenes'];
        foreach ($ter as $tag) {
            $terpenes[] = $tag['tags'];
        }
    }
    $terpenes = array_unique($terpenes);

    $effects = [];
    if ($atts && isset($atts['effets'])) {
        $eff = $atts['effets'];
        foreach ($eff as $tag) {
            $effects[] = $tag['tags'];
        }
    }
    $effects = array_unique($effects);

    $related_products = [];

    $categories_objs = get_the_terms(get_the_ID(), 'featured_item_category');

    $color_class = "hybride";
    $categories = [];
    $tags_list = [];

    if (count($categories_objs)) {
        foreach ($categories_objs as $cat) {
            $categories[] = trim(strtolower($cat->name));
            $tags_list[trim(strtolower($cat->name))] = $cat->name;
        }
    }

    if (in_array('sativa', $categories)) {
        $color_class = "sativa";
    } else if (in_array('indica', $categories)) {
        $color_class = "indica";
    }

    if (!empty($tags)) {
        foreach ($tags as $tag) {
            $tags_list[strtolower(trim($tag["tag"]))] = trim($tag["tag"]);
        }
    }

    $thc = get_field('thc') ?? '';
    if (!empty($thc)) {
        if (strpos(strtolower(trim($thc)), 'thc') !== false) {
	        $tags_list[trim(strtolower($thc))] = $thc;
        } else {
	        $tags_list[trim(strtolower($thc))] = 'THC '.$thc;
        }
    }

    $is_ontario = get_field('is_ontario') ?? false;
    if ($is_ontario) $tags_list["ontario"] = "Ontario";

    $is_qc = get_field('is_qc') ?? false;
    if ($is_qc) $tags_list["qc"] = "Québec";

?>
<div id="portfolio-content" role="main" class="page-wrapper">
    <div class="portfolio-inner">
        <div class="row" id="row-729478601">
            <div id="col-1047009721" class="col medium-6 small-12 large-6">
                <div class="col-inner">
                    <div class="img has-hover x md-x lg-x y md-y lg-y" id="image_1520561551">
                        <div class="img-inner dark">

                            <?php
                            $img_badge = '';
                            if (($is_new || $is_new_var) && $is_new_img_no_txt) {
                                if (!$is_new_var) {
                                    if (ICL_LANGUAGE_CODE == 'fr') {
                                        $img_badge = 'nouveau.png';
                                    } else {
                                        $img_badge = 'new.png';
                                    }
                                } else {
                                    if (ICL_LANGUAGE_CODE == 'fr') {
                                        $img_badge = 'nouvelle-variete.svg';
                                    } else {
                                        $img_badge = 'new-strain.svg';
                                    }
                                }
?>
                            <span class="bleuh-var-fixed-attrs">
                                <?php
                                echo '<img src ="' . esc_url(plugin_dir_url(__FILE__) . '../img/lots/') . $img_badge . '" alt="New" class="new-badge"><br>';
                                $thc = get_field('thc', get_the_ID());
                                if (!empty($thc)) {
                                    echo '<span class="thc">THC:' . esc_html($thc) . '</span>';
                                }

                                // Wrap date
                                $wrap_date = bleuh_display_em_date(get_field('date_demballage', get_the_ID()));
                                if (!empty($wrap_date)) {
                                    echo '<span class="wrap-date">'. esc_html($wrap_date) . '</span>';
                                }
                                ?>
                            </span>
<?php
                            }


                            $pid             = get_the_ID();
                            $like_data       = get_fav_counts( [ $pid ] );
                            $favorites_count = $like_data[ $pid ]['count'] ?? 0;
                            $liked           = ( $like_data[ $pid ]['liked'] ) ? 'liked' : 'not-liked';

                            echo '<span data-id="' . $pid . '" class="like-box ' . $liked . '"><span class="counter">' . esc_attr( $favorites_count ) . '</span><i class="ico"></i></span>';

                            ?>

                            <img decoding="async" width="1020" height="1020"
                                 src="<?PHP echo $full_image_url; ?>"
                                 class="attachment-large size-large" alt="">
                        </div>

                        <style>
                            #image_1520561551 {
                                width: 100%;
                            }
                        </style>
                    </div>

                </div>
            </div>
            <div id="col-1374151930" class="col medium-6 small-12 large-6 variety-tags-list-b">
                <div class="col-inner">
                    <h1><span style="color: #ffffff; font-size: 150%;"><?php echo $title; ?></span></h1>
                    <p>
                    <?php
                        if (count($tags_list)) {
                            foreach($tags_list as $tag) {
                                if (empty($tag)) continue;
                                ?>
                                <b class="<?php echo $color_class; ?>"><?php echo $tag; ?></b>&nbsp;&nbsp;&nbsp;
                    <?php
                            }
                        }
                    ?>
                    </p>

                    <?php echo $acf_description; ?>

                    <?php if (!empty($aromas)) { ?>
                        <hr />

                        <h4><?php echo (ICL_LANGUAGE_CODE == 'fr') ? 'Arômes' : 'Aromas'; ?></h4>
                        <div class="aromas">
                            <?php foreach ($aromas as $aroma) { ?>
                                <div>
                                    <i class="ico <?php echo strtolower($aroma); ?>"></i>
                                    <span><?php
                                        if (ICL_LANGUAGE_CODE == 'fr') {
                                            switch (trim(strtolower($aroma))) {
                                                case "acre":
                                                    $aroma = "acré";
                                                    break;
                                                case "agrumes":
                                                    $aroma = "agrumes";
                                                    break;
                                                case "boise":
                                                    $aroma = "boisé";
                                                    break;
                                                case "dessert":
                                                    $aroma = "dessert";
                                                    break;
                                                case "diesel":
                                                    $aroma = "diesel";
                                                    break;
                                                case "epice":
                                                    $aroma = "épicé";
                                                    break;
                                                case "floral":
                                                    $aroma = "floral";
                                                    break;
                                                case "fruity":
                                                    $aroma = "fruité";
                                                    break;
                                                case "fromage":
                                                    $aroma = "fromage";
                                                    break;
                                                case "herbal":
                                                    $aroma = "herbacé";
                                                    break;
                                                case "moufette":
                                                    $aroma = "moufette";
                                                    break;
                                                case "noisette":
                                                    $aroma = "noisette";
                                                    break;
                                                case "terreux":
                                                    $aroma = "terreux";
                                                    break;
                                            }
                                        } else {
                                            switch (trim(strtolower($aroma))) {
                                                case "acre":
                                                    $aroma = "acrid";
                                                    break;
                                                case "agrumes":
                                                    $aroma = "citrus";
                                                    break;
                                                case "boise":
                                                    $aroma = "woody";
                                                    break;
                                                case "dessert":
                                                    $aroma = "dessert";
                                                    break;
                                                case "diesel":
                                                    $aroma = "diesel";
                                                    break;
                                                case "epice":
                                                    $aroma = "spicy";
                                                    break;
                                                case "floral":
                                                    $aroma = "floral";
                                                    break;
                                                case "fruity":
                                                    $aroma = "fruity";
                                                    break;
                                                case "fromage":
                                                    $aroma = "cheese";
                                                    break;
                                                case "herbal":
                                                    $aroma = "herbal";
                                                    break;
                                                case "moufette":
                                                    $aroma = "skunk";
                                                    break;
                                                case "noisette":
                                                    $aroma = "hazelnut";
                                                    break;
                                                case "terreux":
                                                    $aroma = "earthy";
                                                    break;
                                            }
                                        }
                                        echo $aroma;
                                    ?></span>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <?php if (!empty($terpenes)) { ?>
                        <?php
                            // terpenes default is english
                            if (ICL_LANGUAGE_CODE == 'fr') {
                                foreach ($terpenes as &$ter) {
                                    switch (trim(strtolower($ter))) {
                                        case "bisabolol":
                                            $ter = "Bisabolol";
                                            break;
                                        case "caryophyllene":
                                            $ter = "Caryophyllène";
                                            break;
                                        case "cedrene":
                                            $ter = "Cédrène";
                                            break;
                                        case "farnesene":
                                            $ter = "Farnésène";
                                            break;
                                        case "geraniol":
                                            $ter = "Géraniol";
                                            break;
                                        case "germacrene":
                                            $ter = "Germacrène";
                                            break;
                                        case "guaiene":
                                            $ter = "Guaiène";
                                            break;
                                        case "humulene":
                                            $ter = "Humulène";
                                            break;
                                        case "limonene":
                                            $ter = "Limonène";
                                            break;
                                        case "linalool":
                                            $ter = "Linalol";
                                            break;
                                        case "myrcene":
                                            $ter = "Myrcène";
                                            break;
                                        case "nerolidol":
                                            $ter = "Nérolidol";
                                            break;
                                        case "ocimene":
                                            $ter = "Ocimène";
                                            break;
                                        case "pinene":
                                            $ter = "Pinène";
                                            break;
                                        case "phellandrene":
                                            $ter = "Phellandrène";
                                            break;
                                        case "phytol":
                                            $ter = "Phytol";
                                            break;
                                        case "selina-diene":
                                            $ter = "Sélina-diène";
                                            break;
                                        case "terpinolene":
                                            $ter = "Terpinolène";
                                            break;
                                        case "terpineol":
                                            $ter = "Terpinéol";
                                            break;
                                        case "santalene":
                                            $ter = "Santalène";
                                            break;
                                    }
                                }
                                unset($ter);
                            }
                        ?>
                        <hr />

                        <h4><?php echo (ICL_LANGUAGE_CODE == 'fr') ? 'Terpènes' : 'Terpenes'; ?></h4>

                        <p><?php echo implode(" - ", $terpenes); ?></p>

                    <?php } ?>

                    <hr />

                </div>
            </div>
        </div>
        <div id="text-57713996" class="text port-title">
            <h2 style="text-align: center;"><?php echo (ICL_LANGUAGE_CODE == 'fr') ? 'Détails' : 'Details'; ?></h2>
            <style>
                #text-57713996 {
                    line-height: 1;
                    color: #92c0e9;
                }

                #text-57713996 > * {
                    color: #92c0e9;
                }
            </style>
        </div>
        <div class="row" id="row-28787119">
            <div id="col-1300630423" class="col medium-4 small-12 large-4">
                <div class="col-inner">
                    <h4 class="table_row" data-line-height="xs"><span class="table_title"><?php echo (ICL_LANGUAGE_CODE == 'fr') ? 'Effets' : 'Effects'; ?></span></h4>
                    <?php
                    if (!empty($terpenes)) {
                    // terpenes default is english
                        if (ICL_LANGUAGE_CODE == 'fr') {
                            foreach ($effects as &$ref) {
                                switch (trim(strtolower($ref))) {
                                    case "calm":
                                        $ref = "Calme";
                                        break;
                                    case "cerebral":
                                        $ref = "Cérébral";
                                        break;
                                    case "creativity":
                                        $ref = "Créativité";
                                        break;
                                    case "focus":
                                        $ref = "Concentration";
                                        break;
                                    case "relaxation":
                                        $ref = "Détente";
                                        break;
                                    case "energy":
                                        $ref = "Énergie";
                                        break;
                                    case "euphoria":
                                        $ref = "Euphorie";
                                        break;
                                    case "joy":
                                        $ref = "Joie";
                                        break;
                                    case "motivation":
                                        $ref = "Motivation";
                                        break;
                                    case "social":
                                        $ref = "Social";
                                        break;
                                }
                            }
                            unset($ter);
                        }
                    ?>

                   <p><?php echo implode(" - ", $effects); ?></p>

                    <?php } ?>

                    <div>
                        <hr>
                    </div>
                </div>
                <style>
                    #col-1300630423 > .col-inner {
                        padding: 0px 0px 0px 0px;
                        margin: 0px 0px -35px 0px;
                    }
                </style>
            </div>
            <div id="col-1093916898" class="col medium-4 small-12 large-4">
                <div class="col-inner">
                    <h4 class="table_row" data-line-height="xs"><span class="table_title"><?php echo (ICL_LANGUAGE_CODE == 'fr') ? 'Croisement' : 'Crossbreed'; ?></span></h4>
                    <p class="table_row" data-line-height="xs"><?php echo $croisement; ?></p>
                    <div>
                        <hr>
                    </div>
                </div>
                <style>
                    #col-1093916898 > .col-inner {
                        margin: 0px 0px -35px 0px;
                    }
                </style>
            </div>
            <div id="col-1058676229" class="col medium-4 small-12 large-4">
                <div class="col-inner">
                    <h4 class="table_row" data-line-height="xs"><span class="table_title"><?php echo (ICL_LANGUAGE_CODE == 'fr') ? 'Cultivation' : 'Cultivation'; ?></span></h4>
                    <p class="table_row" data-line-height="xs"><?php echo $culture .', '. $culture_location; ?></p>
                    <div>
                        <hr>
                    </div>
                </div>
            </div>
        </div>
        <?php
            $cherry_picked_products = [];
            $related_products = [];
            if (have_rows('products_related')) { // Check if the repeater field has rows
                while (have_rows('products_related')) {
                    the_row();
                    $product_auto = get_sub_field('product_auto');
                    $cherry_picked_products[] = $product_auto;
                }
            } else {
                $related_products = bleuh_fetch_products_by_variety($title);
            }

            if (!empty($cherry_picked_products)) {
                $related_products = $cherry_picked_products;
            }
            $cols = count($related_products);
        ?>
        <div id="text-761245804" class="text port-title">
            <h2 style="text-align: center;"><?php
                if ($cols > 1) {
                    echo (ICL_LANGUAGE_CODE == 'fr') ? 'Disponible dans les produits' : 'Available in the products';
                } else {
                    echo (ICL_LANGUAGE_CODE == 'fr') ? 'Disponible dans le produit' : 'Available in the product';
                }
                ?></h2>
            <style>
                #text-761245804 {
                    color: #92c0e9;
                }

                #text-761245804 > * {
                    color: #92c0e9;
                }
            </style>
        </div>

        <div class="row">
        <?php
            $swiper_id = md5('blh' . time());
            // render related products
            $related_products = array_unique($related_products);
            echo bleuh_render_products($swiper_id, $related_products, true);
        ?>
        </div>

        <div class="row">
            <div class="related-vars">
            <?php
                // render related varieties
                $related_varieties = [];
                // merge with auto varieties
                $cherry_picked_varieties = [];
                if (have_rows('varieties_rotation')) { // Check if the repeater field has rows
                    while (have_rows('varieties_rotation')) {
                        the_row();
                        $product_auto = get_sub_field('variety_auto');
                        $cherry_picked_varieties[] = $product_auto;
                    }
                }
                if (empty($cherry_picked_products)) {
                    $related_varieties = bleuh_fetch_related_varieties(get_the_ID());
                } else {
                    $related_varieties = $cherry_picked_varieties;
                }
            ?>
            <h2 style="text-align: center;"><?php
                $related_varieties = array_unique($related_varieties);
                $cols = count($related_varieties);
                if ($cols > 1) {
                    echo (ICL_LANGUAGE_CODE == 'fr') ? 'Autres variétés que vous pourriez aimer' : 'Other strains you may also like';
                } else {
                    echo (ICL_LANGUAGE_CODE == 'fr') ? 'Autre variété que vous pourriez aimer' : 'Other strain you may also like';
                }
                ?></h2>
            <?php
                echo bleuh_render_posts($related_varieties);
            ?>
            </div>
        </div>
    </div>
</div>