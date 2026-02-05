<?php
defined( 'ABSPATH' ) || exit;
get_header(); ?>

<?php do_action( 'flatsome_before_page' ); ?>

<?php
    $current_language = apply_filters('wpml_current_language', null);

    // Query posts in the custom taxonomy and current language
    $query_args = [
            'post_type' => 'featured_item', // Custom post type
            'post_status'    => 'publish',
            'posts_per_page' => -1,        // Get all posts
            'orderby' => 'date',           // Order by date
            'order' => 'DESC',             // Newest posts first
            'fields' => 'ids',             // Return only post IDs
            'lang' => $current_language, // Filter by WPML post language
            'suppress_filters' => false, // Ensure WPML filters apply
    ];

    $query = new WP_Query($query_args);

    $aromas = [];
    $terpenes = [];
    $effects = [];

    // Return the posts
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $atts = get_field('attributs', get_the_ID());
            if ($atts && isset($atts['aromes'])) {
                $aromes = $atts['aromes'];
                foreach ($aromes as $arome) {
                    $tag = $arome['tags'];
                    $aromas[$tag] = $tag;
                }
            }
            if ($atts && isset($atts['terpenes'])) {
                $terps = $atts['terpenes'];
                foreach ($terps as $terp) {
                    $tag = $terp['tags'];
                    $terpenes[$tag] = $tag;
                }
            }
            if ($atts && isset($atts['effets'])) {
                $effect = $atts['effets'];
                foreach ($effect as $eff) {
                    $tag = $eff['tags'];
                    $effects[$tag] = $tag;
                }
            }
        }
    }

    sort($aromas);
    sort($terpenes);
    sort($effects);

    wp_reset_postdata();

?>

    <div class="section-content relative varieties_page_container">

        <div class="row row-main">
            <section class="section" id="weekly-section-head" style="padding-left:0;">
                <div class="section-bg fill"></div>
                <div class="section-content relative row">
                    <h1><?php echo get_the_title(); ?></h1>
                        <div class="col large-6 actions-left">
                            <div class="count-header">
                                <?php if ($current_language == 'en') { ?>
                                    <p>Showing <span>...</span> results</p>
                                <?php } else { ?>
                                    <p>Affichage de <span>...</span> résultats</p>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="col small-5 large-6 right-align actions-right mobile-only">
                            <!-- view size -->
                            <?php
                                switch ($_SESSION["view-type"]) {
                                    case "single":
                                        $view_single = "-active";
                                        $view_double = "";
                                        break;
                                    case "double":
                                        $view_single = "";
                                        $view_double = "-active";
                                        break;
                                    default:
                                        $view_single = "-active";
                                        $view_double = "";
                                }
                            ?>
                            <button type="button" class="mobile-view-single<?php echo (!empty($view_single)) ? ' active' : ''; ?>"><img src="/wp-content/plugins/bleuh-ux-builder/img/icos/view-single<?php echo $view_single; ?>.svg" /></button>
                            <button type="button" class="mobile-view-double<?php echo (!empty($view_double)) ? ' active' : ''; ?>"><img src="/wp-content/plugins/bleuh-ux-builder/img/icos/view-double<?php echo $view_double; ?>.svg" /></button>
                            <span><?php echo ($current_language == 'en') ? "View" : "Vue"; ?></span>
                        </div>
                        <div class="col small-7 large-6 right-align actions-right">
                            <!-- filters -->
                            <button class="mobile-filters-button"><?php echo ($current_language == 'en') ? "Filters" : "Filtres"; ?></button>

                            <!-- sort -->
                            <div class="custom-dropdown">
                                <button class="dropdown-btn"><?php echo ($current_language == 'en') ? "Sort" : "Trier"; ?></button>
                                <ul class="dropdown-options">
<!--                                    <li data-value="date">--><?php //echo ($current_language == 'en') ? "Date (old to new)" : "Date (ancien à nouveau)"; ?><!--</li>-->
                                    <li data-value="date-desc"><?php echo ($current_language == 'en') ? "Default - Most recent variety" : "Défaut - Lot le plus récent"; ?></li>
                                    <li data-value="thc"><?php echo ($current_language == 'en') ? "THC (ascending)" : "THC (croissant)"; ?></li>
                                    <li data-value="thc-desc"><?php echo ($current_language == 'en') ? "THC (descending)" : "THC (décroissant)"; ?></li>
                                    <li data-value="name"><?php echo ($current_language == 'en') ? "Alphabetical A-Z" : "Alphabétique A-Z"; ?></li>
                                    <li data-value="name-desc"><?php echo ($current_language == 'en') ? "Alphabetical Z-A" : "Alphabétique Z-A"; ?></li>
<!--                                    <li data-value="popularity">--><?php //echo ($current_language == 'en') ? "Popularity (low to high)" : "Popularité ordre croissant"; ?><!--</li>-->
                                    <li data-value="popularity-desc"><?php echo ($current_language == 'en') ? "Popularity" : "Popularité"; ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <div style="display: flex;">
                    <div class="mobile-side-panel col large-3 hide-for-medium" style="padding-left:0;">
                        <div id="shop-sidebar" class="sidebar-inner col-inner">
                            <aside id="yith-woocommerce-ajax-navigation-filters-3"
                                   class="widget widget_yith-woocommerce-ajax-navigation-filters">
                                <div class="yith-wcan-filters no-title enhanced" id="preset_2618" data-preset-id="2618"
                                     data-target="">
                                    <div class="filters-container">
                                        <form method="POST">

                                            <!-- mobile options -->
                                            <div class="mobile-only action-area">
                                                <button type="button" class="mobile-filter-close"><img src="/wp-content/plugins/bleuh-ux-builder/img/close-white.svg" /> </button>
                                            </div>

                                            <div class="yith-wcan-filter filter-tax checkbox-design">
                                                <h4 class="filter-title collapsable closed">Province</h4>
                                                <div class="filter-content" style="display:none;">
                                                    <ul class="filter-items filter-checkbox  level-0">
                                                        <li class="filter-item checkbox  level-0 no-color">
                                                            <label for="filter_2618_6_154">
                                                                <input data-type="province" type="checkbox" name="prov[]" value="on">
                                                                <a href="#" role="button" class="term-label">
                                                                    Ontario
                                                                </a>
                                                            </label>
                                                        </li>

	                                                    <li class="filter-item checkbox  level-0 no-color">
		                                                    <label for="filter_2618_6_154">
			                                                    <input data-type="province" type="checkbox" name="prov[]" value="qc">
			                                                    <a href="#" role="button" class="term-label">
				                                                    Québec
			                                                    </a>
		                                                    </label>
	                                                    </li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="yith-wcan-filter filter-tax checkbox-design">
                                                <h4 class="filter-title collapsable closed">Disponibilité</h4>
                                                <div class="filter-content" style="display:none;">
                                                    <ul class="filter-items filter-checkbox  level-0">
                                                        <li class="filter-item checkbox  level-0 no-color">
                                                            <label for="filter_2618_6_154">
                                                                <input data-type="availability" type="checkbox" name="prov[]" value="new-lot">
                                                                <a href="#" role="button" class="term-label">
                                                                    <?php echo ($current_language == 'fr') ? "Nouveau Lot" : "New Batch"; ?>
                                                                </a>
                                                            </label>
                                                        </li>
                                                        <li class="filter-item checkbox  level-0 no-color">
                                                            <label for="filter_2618_6_154">
                                                                <input data-type="availability" type="checkbox" name="prov[]" value="new-var">
                                                                <a href="#" role="button" class="term-label">
                                                                    <?php echo ($current_language == 'fr') ? "Nouvelle variété" : "New variety"; ?>
                                                                </a>
                                                            </label>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="yith-wcan-filter filter-tax checkbox-design">
                                                <h4 class="filter-title collapsable opened"><?php echo ($current_language == 'fr') ? "Intensité" : "Intensity"; ?></h4>
                                                <div class="filter-content">
                                                    <!-- THC slider -->
                                                    <div id="slider-container">
                                                        <!-- Slider -->
                                                        <div id="slider"></div>
                                                        <!-- Display selected range -->
                                                        <div id="range-values">
                                                            THC: <span id="min-value">0</span>% - <span id="max-value">100</span>%
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

	                                        <div class="yith-wcan-filter filter-tax checkbox-design">
		                                        <h4 class="filter-title collapsable closed">Type</h4>
		                                        <div class="filter-content" style="display:none;">
			                                        <ul class="filter-items filter-checkbox  level-0">
				                                        <li class="filter-item checkbox  level-0 no-color">
					                                        <label for="filter_2618_6_154">
						                                        <input data-type="type" type="checkbox" name="prov[]" value="hybride">
						                                        <a href="#" role="button" class="term-label">
                                                                    <?php echo ($current_language == 'fr') ? "Hybride" : "Hybrid"; ?>
						                                        </a>
					                                        </label>
				                                        </li>
				                                        <li class="filter-item checkbox  level-0 no-color">
					                                        <label for="filter_2618_6_154">
						                                        <input data-type="type" type="checkbox" name="prov[]" value="sativa">
						                                        <a href="#" role="button" class="term-label">
							                                        Sativa
						                                        </a>
					                                        </label>
				                                        </li>
				                                        <li class="filter-item checkbox  level-0 no-color">
					                                        <label for="filter_2618_6_154">
						                                        <input data-type="type" type="checkbox" name="prov[]" value="indica">
						                                        <a href="#" role="button" class="term-label">
							                                        Indica
						                                        </a>
					                                        </label>
				                                        </li>
			                                        </ul>
		                                        </div>
	                                        </div>

                                            <div class="yith-wcan-filter filter-tax checkbox-design">
                                                <h4 class="filter-title collapsable closed">Arômes</h4>
                                                <div class="filter-content" style="display:none;">
                                                    <ul class="filter-items filter-checkbox  level-0">

                                                        <?php foreach ($aromas as $aroma) { ?>
                                                        <li class="filter-item checkbox  level-0 no-color">
                                                            <label for="filter_2618_6_154">
                                                                <input data-type="aromas" type="checkbox" name="prov[]" value="<?php echo strtolower($aroma); ?>">
                                                                <a href="#" role="button" class="term-label">
                                                                    <?php
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
                                                                    ?>
                                                                </a>
                                                            </label>
                                                        </li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="yith-wcan-filter filter-tax checkbox-design">
                                                <h4 class="filter-title collapsable closed">Terpènes</h4>
                                                <div class="filter-content" style="display:none;">
                                                    <ul class="filter-items filter-checkbox  level-0">

                                                        <?php foreach ($terpenes as $ter) { ?>
                                                        <li class="filter-item checkbox  level-0 no-color">
                                                            <label for="filter_2618_6_154">
                                                                <input data-type="terpenes" type="checkbox" name="prov[]" value="<?php echo strtolower($ter); ?>">
                                                                <a href="#" role="button" class="term-label">
                                                                    <?php
                                                                    if (ICL_LANGUAGE_CODE == 'fr') {
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
                                                                    echo $ter;
                                                                    ?>
                                                                </a>
                                                            </label>
                                                        </li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="yith-wcan-filter filter-tax checkbox-design">
                                                <h4 class="filter-title collapsable closed">Effets</h4>
                                                <div class="filter-content" style="display:none;">
                                                    <ul class="filter-items filter-checkbox  level-0">

                                                        <?php foreach ($effects as $effet) { ?>
                                                        <li class="filter-item checkbox  level-0 no-color">
                                                            <label for="filter_2618_6_154">
                                                                <input data-type="effects" type="checkbox" name="prov[]" value="<?php echo strtolower($effet); ?>">
                                                                <a href="#" role="button" class="term-label">
                                                                    <?php
                                                                    if (ICL_LANGUAGE_CODE == 'fr') {
                                                                        switch (trim(strtolower($effet))) {
                                                                            case "calm":
                                                                                $effet = "Calme";
                                                                                break;
                                                                            case "cerebral":
                                                                                $effet = "Cérébral";
                                                                                break;
                                                                            case "creativity":
                                                                                $effet = "Créativité";
                                                                                break;
                                                                            case "focus":
                                                                                $effet = "Concentration";
                                                                                break;
                                                                            case "relaxation":
                                                                                $effet = "Détente";
                                                                                break;
                                                                            case "energy":
                                                                                $effet = "Énergie";
                                                                                break;
                                                                            case "euphoria":
                                                                                $effet = "Euphorie";
                                                                                break;
                                                                            case "joy":
                                                                                $effet = "Joie";
                                                                                break;
                                                                            case "motivation":
                                                                                $effet = "Motivation";
                                                                                break;
                                                                            case "social":
                                                                                $effet = "Social";
                                                                                break;
                                                                        }
                                                                    }
                                                                    echo $effet;
                                                                    ?>
                                                                </a>
                                                            </label>
                                                        </li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            </div>


                                        </form>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </div>

                    <div class="col large-9">
                        <div class="shop-container">

                            <div class="no-results-blurb" style="display:none;">
                                <?php if ($current_language == 'en') { ?>
                                    <p>No results found, <a href="#">remove filters</a> to see more varieties</p>
                                <?php } else { ?>
                                    <p>Aucun résultat trouvé, <a href="#">enlevez des filtres</a> pour voir plus de résultats.</p>
                                <?php } ?>
                            </div>

                            <div class="products row row-small large-columns-3 medium-columns-3 small-columns-1 has-equal-box-heights equalize-box">

                            <?php
                            $query_args = [
                                    'post_type' => 'featured_item',
                                    'post_status' => 'publish',
                                    'orderby' => 'date',
                                    'order' => 'DESC',
                                    'posts_per_page' => -1,
                            ];
                            $query = new WP_Query($query_args);

                            // get likes
                            $likes = get_fav_counts();

                            $output = '';
                            if ($query->have_posts()) {

                                $i = 0;
                                while ($query->have_posts()) {
                                    // Limit the number of products to display
                                    $i++;

                                    $query->the_post();
                                    $post_id = (int) get_the_ID();

                                    if (isset($likes[$post_id])) {
                                        $liked = $likes[$post_id]['liked'] ? ' liked' : 'not-liked';
                                        $favorites_count = (int) $likes[$post_id]['count'];
                                    } else {
                                        $liked = 'not-liked';
                                        $favorites_count = 0;
                                    }

                                    $categories = wp_get_post_terms(get_the_ID(), 'featured_item_category');
                                    $cat_list = [];
                                    foreach ($categories as $category) {
                                        $cat_list[] = $category->name;
                                    }
                                    $output .= '<div class="product-small box " style="max-width:33%;display:none;">';
                                    $output .= '<div class="vars-page-block">';

                                    // get img without txt if it exists from acf
                                    $img_url = get_field('img_no_txt', get_the_ID()); // for image without text of the variety
                                    $image_id = attachment_url_to_postid($img_url); // get the image ID from the URL
                                    if (!empty($image_id)) {
                                        $thumbnail = wp_get_attachment_image_src($image_id, 'medium');
                                        $img = $thumbnail[0];
                                        $img = '<img class="lazy" data-original="'.esc_url($img).'" src="/wp-content/plugins/bleuh-ux-builder/img/placeholder.jpg" alt="Thumbnail Image">';
                                    } else {
                                        $img = '<img class="lazy" data-original="'.esc_url(get_the_post_thumbnail_url(get_the_ID(), 'medium')).'" src="/wp-content/plugins/bleuh-ux-builder/img/placeholder.jpg" alt="Thumbnail Image">';
                                    }

                                    // Add the post content to the current slide
                                    $output .= '<div style="flex: 1; padding: 10px; box-sizing: border-box;">';

                                    $provs = [];
                                    if (get_field('is_ontario', get_the_ID())) {
                                        $provs[] = 'on';
                                    }
                                    if (get_field('is_qc', get_the_ID())) {
                                        $provs[] = 'qc';
                                    }
                                    $provs = implode(', ', $provs);
                                    $data_thc = get_field('thc', get_the_ID());
                                    $numbers_from_thc = preg_match_all('/\d+(\.\d+)?/', $data_thc, $matches);
                                    $numbers_from_thc = $matches[0] ?? [0];
                                    $data_thc_min = $numbers_from_thc[0];
                                    $data_thc_max = $numbers_from_thc[1] ?? $data_thc_min;

                                    $data_type = implode(', ', $cat_list);

                                    $availability = [];
                                    if (get_field('is_new', get_the_ID())) $availability[] = 'new-lot';
                                    if (get_field('is_new_var', get_the_ID())) $availability[] = 'new-var';
                                    $availability = implode(', ', $availability);

                                    $atts = get_field("attributs", get_the_ID());
                                    $aromas = [];
                                    if ($atts && isset($atts['aromes'])) {
                                        $aromes = $atts['aromes'];
                                        foreach ($aromes as $arome) {
                                            $aromas[] = $arome['tags'];
                                        }
                                    }
                                    $aromas = array_unique($aromas);
                                    $data_aromas = implode(', ', $aromas);

                                    $data_terpenes = '';
                                    if ($atts && isset($atts['terpenes'])) {
                                        $terps = $atts['terpenes'];
                                        foreach ($terps as $terp) {
                                            $data_terpenes .= $terp['tags'] . ', ';
                                        }
                                        $data_terpenes = rtrim($data_terpenes, ', ');
                                    }

                                    $data_effects = '';
                                    if ($atts && isset($atts['effets'])) {
                                        $effects = $atts['effets'];
                                        foreach ($effects as $effect) {
                                            $data_effects .= $effect['tags'] . ', ';
                                        }
                                        $data_effects = rtrim($data_effects, ', ');
                                    }

                                    $wrap_date_sort_id = bleuh_sortable_em_date(get_field('date_demballage', get_the_ID()));
                                    $output .= '    <a
                                                        data-province="' . esc_attr($provs) . '"
                                                        data-availability="' . esc_attr($availability) . '"
                                                        data-thc-min="' . esc_attr($data_thc_min) . '"
                                                        data-thc-max="' . esc_attr($data_thc_max) . '"
                                                        data-type="' . esc_attr($data_type) . '"
                                                        data-aromas="' . esc_attr($data_aromas) . '"
                                                        data-terpenes="' . esc_attr(strtolower($data_terpenes)) . '"
                                                        data-effects="' . esc_attr(strtolower($data_effects)) . '"
                                                        data-count= "' . esc_attr($favorites_count) . '"
                                                        data-wrap-date="' . esc_attr($wrap_date_sort_id) . '"
                                                        class="var-box-link" href="#" data-href="' . get_permalink() . '" style="text-decoration: none; color: inherit;">';

                                    $output .= '<span data-id="'.$post_id.'" class="like-box '.$liked.'"><span class="counter">' . esc_attr($favorites_count) . '</span><i class="ico"></i></span>';

                                    // add tags if image doesn't contain text
                                    $img_badge = '';
                                    $is_new = get_field('is_new', get_the_ID());
                                    $is_new_var = get_field('is_new_var', get_the_ID());
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
                                    $output .= '<span class="bleuh-var-fixed-attrs">';
                                    if (($is_new || $is_new_var) && !empty($img_url)) {
                                        $output .= '<img src ="' . esc_url(plugin_dir_url(__FILE__) . '../img/lots/'). $img_badge . '" alt="New" class="new-badge"><br>';
                                        // THC
                                        $thc = get_field('thc', get_the_ID());
                                        if (!empty($thc)) {
                                            $output .= '<span class="thc">THC:' . esc_html($thc) . '</span>';
                                        }
                                        // Wrap date
                                        $wrap_date = bleuh_display_em_date(get_field('date_demballage', get_the_ID()));
                                        if (!empty($wrap_date)) {
                                            $output .= '<span class="wrap-date">'. esc_html($wrap_date) . '</span>';
                                        }
                                    }
                                    $output .= '</span>';
                                    $output .=          $img;
                                    $output .= '        <h3 style="margin-top: 10px; font-size: 1.2rem;">' . get_the_title() . '</h3>';
                                    $output .= '        <p class="category uppercase is-smaller no-text-overflow product-cat op-7">' . implode(', ', $cat_list)  . '</p>';
                                    $output .= '    </a>';
                                    $output .= '</div>'; // flex
                                    $output .= '</div>'; // vars-page-block
                                    $output .= '</div>'; // product-small
                                }

                                wp_reset_postdata();
                                echo $output;
                            }
                            ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

<script type="text/javascript">
    jQuery(document).ready(function ($) {
        // refresh item count display
        function refresh_item_count() {
            let visible_count = $('.varieties_page_container .products .product-small:visible').length;
            if (visible_count !== $(".count-header span").text()) {
                $(".count-header span").text(visible_count);
                if (visible_count === 0) {
                    $('.no-results-blurb').show();
                } else {
                    $('.no-results-blurb').hide();
                }
                $("img.lazy").lazyload();
            }
        }

        setInterval(function () {
            refresh_item_count();
        }, 1000);

        // refresh hashtag on filter change
        function refresh_filters() {
            // build hashtag
            let hashtag = '#';
            let filters = {};
            let checkboxes = $('.varieties_page_container .filters-container input[type="checkbox"]');
            checkboxes.each(function () {
                let checkbox = $(this);
                if (checkbox.is(':checked')) {
                    let type = checkbox.attr('data-type').toLowerCase();
                    let val = checkbox.val();
                    // Initialize the filter type if not already present
                    if (!filters[type]) {
                        filters[type] = [];
                    }

                    // Add the value to the filter
                    filters[type].push(val);
                }
            });
            for (let key in filters) {
                let values = filters[key];
                hashtag += key + '=';
                values.forEach(function (val) {
                    hashtag += val + '+';
                });
                hashtag = hashtag.slice(0, -1); // Remove the last comma
                hashtag += '&'; // Add an ampersand between filters
            }

            // get thc range from slider
            let sliderValues = $("#slider").slider("values");
            if (sliderValues && sliderValues.length === 2) {
                let minThc = sliderValues[0];
                let maxThc = sliderValues[1];
                hashtag += 'thc=' + minThc + '+' + maxThc + '&';
            }

            hashtag = hashtag.slice(0, -1);

            if (hashtag.length > 1) {
                hashtag = hashtag.replace(/ /g, '-'); // replace spaces with dashes
                window.location.hash = hashtag;
            } else {
                window.location.hash = '';
            }
            filter_varieties();
        }

        // load hashtag on page load
        function load_hashtag() {
            let hash = window.location.hash;
            if (hash.length > 1) {
                hash = hash.substring(1); // Remove the leading #
                let filters = hash.split('&');
                filters.forEach(function (filter) {
                    let parts = filter.split('=');
                    if (parts.length === 2) {
                        let type = parts[0];

                        if (type === 'thc') {
                            // Handle THC range
                            let values = parts[1].split('+');
                            if (values.length === 2) {
                                let minThc = parseFloat(values[0]);
                                let maxThc = parseFloat(values[1]);
                                // $("#slider").parents(".filter-content").show();
                                // $("#slider").parents(".filter-content").prev(".filter-title").removeClass('closed').addClass('opened');
                                setSliderValues(minThc, maxThc);
                            }

                        } else {
                            let values = parts[1].split('+');
                            values.forEach(function (value) {
                                $('.varieties_page_container .filters-container input[type="checkbox"][value="' + value + '"]').prop('checked', true);
                            });
                        }
                    }
                });
            }

            // check if any checkboxes are checked, expand section if any checkbox is set
            $(".filters-container .yith-wcan-filter").each(function () {
               if ($(this).find("input[type='checkbox']:checked").length > 0) {
                   $(this).find('.filter-title').removeClass('closed').addClass('opened');
                   $(this).find('.filter-content').show();
               } else {
                   $(this).find('.filter-title').removeClass('opened').addClass('closed');
                   $(this).find('.filter-content').hide();
               }
            });

            // check thc slider, expand section if slider is not at default values
            let sliderValues = $("#slider").slider("values");
            if (sliderValues && (sliderValues[0] !== 0 || sliderValues[1] !== 100)) {
                $("#slider").parents(".filter-content").show();
                $("#slider").parents(".filter-content").prev(".filter-title").removeClass('closed').addClass('opened');
            }

            filter_varieties();
        }

        function filter_varieties() {
            let hash = window.location.hash;
            if (hash.length > 1) {
                hash = hash.substring(1); // Remove the leading #
                let filters = hash.split('&'); // Split the hash into filter types (e.g., province=on+qc)

                $('.varieties_page_container .products .product-small').each(function () {
                    let product = $(this).find('a.var-box-link'); // Find the product's link element
                    let matchesAllFilters = true; // Assume the product matches all filters initially

                    filters.forEach(function (filter) {
                        let parts = filter.split('=');
                        if (parts.length === 2) {
                            let type = parts[0].trim().toLowerCase(); // Trim and lowercase the filter type

                            // Handle THC range separately
                            // data-is-new-lot="' . esc_attr($data_is_new) . '"
                            // data-is-new-var="' . esc_attr($data_is_new_var) . '"

                            if (type === 'thc') {
                                let values = parts[1].split('+').map(value => parseFloat(value.trim())); // Convert THC values to numbers
                                if (values.length === 2) {
                                    let minThc = values[0];
                                    let maxThc = values[1];

                                    // Get product's THC values and convert them to numbers
                                    let productThcMin = parseFloat(product.attr('data-thc-min') || 0); // Default to 0 if missing
                                    let productThcMax = parseFloat(product.attr('data-thc-max') || 0); // Default to 0 if missing

                                    // Check if the product's THC range overlaps the selected range
                                    if (
                                        productThcMax < minThc || // Product max is below the selected min
                                        productThcMin > maxThc    // Product min is above the selected max
                                    ) {
                                        matchesAllFilters = false; // No overlap, product doesn't match
                                    }
                                } else {
                                    matchesAllFilters = false; // Invalid THC filter values
                                }
                            } else {
                                // Handle other filters (non-THC)
                                let values = parts[1].split('+').map(value => value.trim().toLowerCase()); // Trim and lowercase each filter value

                                // Check if the product has at least one matching attribute for this filter type
                                let productValue = product.data(type); // Extract the `data-*` attribute matching the type
                                if (productValue) {
                                    let productValues = productValue
                                        .toString() // Ensure the product value is a string
                                        .toLowerCase() // Convert to lowercase
                                        .split(',') // Support comma-separated values
                                        .map(value => value.trim()); // Trim each value

                                    // Check if there's at least one match between filter values and product values
                                    let hasMatch = values.some(value => productValues.includes(value));

                                    if (!hasMatch) {
                                        matchesAllFilters = false; // If no match found for this filter, the product fails
                                    }
                                } else {
                                    matchesAllFilters = false; // If the product doesn't have the attribute, it fails
                                }
                            }
                        }
                    });

                    // Show or hide the product based on whether it matches all filters
                    if (matchesAllFilters) {
                        $(this).fadeIn("fast");
                    } else {
                        $(this).fadeOut("fast");
                    }
                });
            } else {
                // If no filters are present, show all products
                $('.varieties_page_container .products .product-small').fadeIn("fast");
            }
            $("img.lazy").lazyload();
        }

        function setSliderValues(min, max) {
            // Dynamically set the min and max values
            jQuery("#slider").slider("option", "min", 0); // Set the new minimum value
            jQuery("#slider").slider("option", "max", 100); // Set the new maximum value

            // Optional: Update the handles to reflect the new range
            jQuery("#slider").slider("values", [min, max]); // Reset handle positions
            jQuery("#min-value").text(min); // Update displayed min value
            jQuery("#max-value").text(max); // Update displayed max value
        }

        if ($("#slider").length > 0) {
            // Initialize the slider
            $("#slider").slider({
                range: true, // Enable two handles
                min: 0, // Minimum value
                max: 100, // Maximum value
                values: [0, 100], // Initial handle positions (min, max)
                slide: function (event, ui) {
                    // Update the displayed range values
                    // setSliderValues(ui.values[0], ui.values[1]);
                    $("#min-value").text(ui.values[0]);
                    $("#max-value").text(ui.values[1]);
                    refresh_filters();
                }
            });
        }

        $(document).on('change', '.varieties_page_container .filters-container input[type=checkbox]', function () {
            refresh_filters();
        });

        $(document).on('click', '.varieties_page_container .filters-container a', function () {
            let checkbox = $(this).parent().find("input[type='checkbox']");
            checkbox.prop('checked', !checkbox.prop('checked'));
            refresh_filters();
            return false;
        });

        $(document).on('click', '.varieties_page_container h4.filter-title', function () {
            let content = $(this).next('.filter-content');
            if (content.is(':visible')) {
                content.slideUp();
                $(this).removeClass('opened');
                $(this).addClass('closed');
            } else {
                content.slideDown();
                $(this).addClass('opened');
                $(this).removeClass('closed');
            }
        });

        function sortByX(sortBy) {
            let sortOrder = false; // Default to ascending order
            if (sortBy.includes('-desc')) {
                sortBy = sortBy.replace('-desc', '');
                sortOrder = true; // Descending order
            } else {
                sortOrder = false; // Ascending order
            }
            let sort_by = sortBy;
            let products = $('.shop-container .products .product-small');

            // Sort the products
            products.sort(function (a, b) {
                let aValue, bValue;

                if (sort_by === 'name') {
                    // works
                    aValue = $(a).find('h3').text().toLowerCase();
                    bValue = $(b).find('h3').text().toLowerCase();
                } else if (sort_by === 'thc') {
                    aValue = ((parseFloat($(a).find('a.var-box-link').attr('data-thc-min')) || 0) + (parseFloat($(a).find('a.var-box-link').attr('data-thc-max')) || 0)) / 2;
                    bValue = ((parseFloat($(b).find('a.var-box-link').attr('data-thc-min')) || 0) + (parseFloat($(b).find('a.var-box-link').attr('data-thc-max')) || 0)) / 2;
                } else if (sort_by === 'date') {
                    aValue = new Date($(a).find('a.var-box-link').attr('data-wrap-date'));
                    bValue = new Date($(b).find('a.var-box-link').attr('data-wrap-date'));
                } else if (sort_by === 'popularity') {
                    aValue = parseInt($(a).find('span.like-box .counter').text()) || 0;
                    bValue = parseInt($(b).find('span.like-box .counter').text()) || 0;
                } else {
                    return 0; // No sorting
                }

                // Compare values
                if (aValue > bValue) {
                    return sortOrder ? -1 : 1; // Descending if sortOrder is true
                } else if (aValue < bValue) {
                    return sortOrder ? 1 : -1; // Ascending if sortOrder is false
                } else {
                    return 0;
                }
            });

            // Re-append the sorted products back to the container
            $('.shop-container .products').html(products);
            $("img.lazy").lazyload();

        }

        // Event listener for the sort dropdown
        $(document).on('change', '#sort-by.select', function () {
            let $this = $(this);
            sortByX($this.val());
        });

        $(document).on('click', '.no-results-blurb a', function () {
            window.location.hash = ''; // Clear the hash
            window.location.reload();
            return false;
        });

        load_hashtag();

        // load cookie sort if exists or default to sort by new
        if (Cookies.get("v_sort") === undefined) {
            // create new cookie
            let v_sort = 'date-desc';
            Cookies.set("v_sort", v_sort, {expires: 365, path: '/', domain: '.bleuh.co'});
        }
        sortByX(Cookies.get("v_sort"));
        let btn_caption = $("ul.dropdown-options li[data-value='"+Cookies.get("v_sort")+"']").first().text();
        $("button.dropdown-btn").text(btn_caption);

        // Get dropdown elements
        const $dropdown = $(".custom-dropdown");
        const $dropdownBtn = $dropdown.find(".dropdown-btn");
        const $dropdownOptions = $dropdown.find(".dropdown-options");
        const $options = $dropdownOptions.find("li");

        // Toggle dropdown visibility
        $dropdownBtn.on("click", function () {
            $dropdown.toggleClass("active");
        });

        // Update button text and close dropdown when an option is clicked
        $options.on("click", function () {
            const selectedText = $(this).text();
            $dropdownBtn.text(selectedText);
            $dropdown.removeClass("active");
            let this_val = $(this).data('value');
            Cookies.set("v_sort", this_val, {expires: 365, path: '/', domain: '.bleuh.co'});
            sortByX(this_val);
        });

        // Close dropdown if clicking outside
        $(document).on("click", function (e) {
            if (!$dropdown.is(e.target) && $dropdown.has(e.target).length === 0) {
                $dropdown.removeClass("active");
            }
        });

        $(document).on("click", 'button.mobile-filters-button,.mobile-filter-close',  function (e) {
            e.preventDefault();
            $(".mobile-side-panel").toggleClass("active");
        });

        let current_hash = window.location.hash;
        $(window).on('hashchange', function () {
            if (current_hash !== window.location.hash) {
                // Hash has changed, reload the filters
                $("input[type='checkbox']").prop('checked', false);
                current_hash = window.location.hash;
                load_hashtag();
            }
        });

    });
</script>
<?php do_action( 'flatsome_after_page' ); ?>

<?php get_footer();
