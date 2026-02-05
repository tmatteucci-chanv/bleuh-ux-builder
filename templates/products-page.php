<?php
defined( 'ABSPATH' ) || exit;
get_header(); ?>

<?php do_action( 'flatsome_before_page' ); ?>

<?php
    function bleuh_alphanumeric( $string ) {
        $string = strtolower( $string );
        $string = trim( $string );
        return preg_replace( '/[^a-zA-Z0-9]/', '', $string );
    }

    $current_language = apply_filters('wpml_current_language', null);

    // Query posts in the custom taxonomy and current language
    $query_args = [
            'post_type' => 'product', // Custom post type
            'post_status'    => 'publish',
            'posts_per_page' => -1,        // Get all posts
            'orderby' => 'date',           // Order by date
            'order' => 'DESC',             // Newest posts first
            'fields' => 'ids',             // Return only post IDs
            'lang' => $current_language, // Filter by WPML post language
            'suppress_filters' => false, // Ensure WPML filters apply
    ];

    $query = new WP_Query($query_args);

    $collections = []; // x
    $formats = []; // x
    $thc = [];
    $effects = []; // x
    $terpenes = []; // x
    $categories = []; // x

    // static fields
    $provinces = ['on' => 'Ontario', 'qc' => 'Quebec']; // x
//    $tags = ['new' => 'new', 'web-only' => 'web-only', 'coming-soon' => 'coming-soon', 'best-seller' => 'best-seller']; // x
    $tags = []; // x

    // Return the posts
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            global $product;
            $pid = $product->get_id();
            $product = wc_get_product( $pid );

            // Categories (ie. Indica, Sativa, Hybrid)
            $terms = get_the_terms($pid, 'product_cat');
            if ($terms && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $att = strtolower($term->name);
                    $categories[$att] = $att;
                }
            }

            // Tags (ie. Nouveau, meilleur vendeur, etc.)
            $p_tags = get_the_terms($pid, 'product_tag');
            if ($p_tags && !is_wp_error($p_tags)) {
                foreach ($p_tags as $tag) {
                    $att = strtolower($tag->name);
                    $tags[bleuh_alphanumeric($att)] = $att;
                }
            }

            // Formats (ie. flowers, pre-rolls, etc.)
            $att = $product->get_attribute( 'pa_formats' );
            $atts = explode(',', $att);
            foreach ($atts as $a) {
                if (!empty(trim($a))) $formats[trim($a)] = trim($a);
            }

            // Collections (ie. Blakh)
            $att = $product->get_attribute( 'pa_marques' );
            $atts = explode(',', $att);
            foreach ($atts as $a) {
                if (!empty(trim($a))) $collections[trim($a)] = trim($a);
            }

            if ( have_rows('details', $pid) ) {
                while ( have_rows('details', $pid) ) {
                    the_row();

                    // effects
                    $att = get_sub_field('effets_potentiels');
                    $att = str_replace(['–', '—', '−'], '-', $att);
                    $atts = explode('-', $att);
                    foreach ($atts as $a) {
                        if (!empty(trim($a))) $effects[bleuh_alphanumeric($a)] = trim($a);
                    }

                    // terpenes
                    $att = get_sub_field('terpenes');
                    $att = str_replace(['–', '—', '−', ','], '-', $att);
                    $atts = explode('-', $att);
                    foreach ($atts as $a) {
                        if (!empty(trim($a)))  $terpenes[trim(strtolower($a))] = trim($a);
                    }

                }
            }

        }

    }

    // sort results alphabatically
    $coll = new Collator('fr_CA');

    usort($provinces, fn($a, $b) => $coll->compare($a, $b));
    usort($collections, fn($a, $b) => $coll->compare($a, $b));
    usort($formats, fn($a, $b) => $coll->compare($a, $b));
    usort($categories, fn($a, $b) => $coll->compare($a, $b));
    usort($tags, fn($a, $b) => $coll->compare($a, $b));
    usort($effects, fn($a, $b) => $coll->compare($a, $b));
    usort($terpenes, fn($a, $b) => $coll->compare($a, $b));

//    var_dump($provinces, $collections, $formats, $categories, $tags, $effects, $terpenes);

    wp_reset_postdata();

?>

    <div class="section-content relative varieties_page_container">

        <div class="row row-main">
            <section class="section" id="weekly-section-head" style="padding-left:0;">
                <div class="section-bg fill"></div>
                <div class="section-content relative row">
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
                                    <li data-value="date-desc"><?php echo ($current_language == 'en') ? "Default - Most recent variety" : "Défaut - Lot le plus récent"; ?></li>
                                    <li data-value="thc"><?php echo ($current_language == 'en') ? "THC (ascending)" : "THC (croissant)"; ?></li>
                                    <li data-value="thc-desc"><?php echo ($current_language == 'en') ? "THC (descending)" : "THC (décroissant)"; ?></li>
                                    <li data-value="name"><?php echo ($current_language == 'en') ? "Alphabetical A-Z" : "Alphabétique A-Z"; ?></li>
                                    <li data-value="name-desc"><?php echo ($current_language == 'en') ? "Alphabetical Z-A" : "Alphabétique Z-A"; ?></li>
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

                                            <!-- Provinces -->
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

                                            <!-- THC -->
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

                                            <!-- Collections -->
                                            <div class="yith-wcan-filter filter-tax checkbox-design">
                                                <h4 class="filter-title collapsable closed"><?php echo ($current_language == 'fr') ? "Collections" : "Collections"; ?></h4>
                                                <div class="filter-content" style="display:none;">
                                                    <ul class="filter-items filter-checkbox  level-0">
                                                        <?php foreach ($collections as $tag_key => $tag_label) { ?>
                                                            <li class="filter-item checkbox  level-0 no-color">
                                                                <label for="filter_2618_6_154">
                                                                    <input data-type="collections" type="checkbox" name="prov[]" value="<?php echo bleuh_alphanumeric($tag_label); ?>">
                                                                    <a href="#" role="button" class="term-label">
                                                                        <?php echo strtolower($tag_label); ?>
                                                                    </a>
                                                                </label>
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- Category -->
                                            <div class="yith-wcan-filter filter-tax checkbox-design">
                                                <h4 class="filter-title collapsable closed"><?php echo ($current_language == 'fr') ? "Catégorie" : "Category"; ?></h4>
                                                <div class="filter-content" style="display:none;">
                                                    <ul class="filter-items filter-checkbox  level-0">
                                                        <?php foreach ($categories as $tag_key => $tag_label) { ?>
                                                            <li class="filter-item checkbox  level-0 no-color">
                                                                <label for="filter_2618_6_154">
                                                                    <input data-type="categories" type="checkbox" name="prov[]" value="<?php echo bleuh_alphanumeric($tag_label); ?>">
                                                                    <a href="#" role="button" class="term-label">
                                                                        <?php echo strtolower($tag_label); ?>
                                                                    </a>
                                                                </label>
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- Formats -->
                                            <div class="yith-wcan-filter filter-tax checkbox-design">
                                                <h4 class="filter-title collapsable closed"><?php echo ($current_language == 'fr') ? "Formats" : "Formats"; ?></h4>
                                                <div class="filter-content" style="display:none;">
                                                    <ul class="filter-items filter-checkbox  level-0">
                                                        <?php foreach ($formats as $tag_key => $tag_label) { ?>
                                                            <li class="filter-item checkbox  level-0 no-color">
                                                                <label for="filter_2618_6_154">
                                                                    <input data-type="formats" type="checkbox" name="prov[]" value="<?php echo bleuh_alphanumeric($tag_label); ?>">
                                                                    <a href="#" role="button" class="term-label">
                                                                        <?php echo strtolower($tag_label); ?>
                                                                    </a>
                                                                </label>
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- terpenes -->
                                            <div class="yith-wcan-filter filter-tax checkbox-design">
                                                <h4 class="filter-title collapsable closed"><?php echo ($current_language == 'fr') ? "Terpènes" : "Terpenes"; ?></h4>
                                                <div class="filter-content" style="display:none;">
                                                    <ul class="filter-items filter-checkbox  level-0">
                                                        <?php foreach ($terpenes as $tag_key => $tag_label) { ?>
                                                            <li class="filter-item checkbox  level-0 no-color">
                                                                <label for="filter_2618_6_154">
                                                                    <input data-type="terpenes" type="checkbox" name="prov[]" value="<?php echo bleuh_alphanumeric($tag_label); ?>">
                                                                    <a href="#" role="button" class="term-label">
                                                                        <?php echo strtolower($tag_label); ?>
                                                                    </a>
                                                                </label>
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- Effects -->
                                            <div class="yith-wcan-filter filter-tax checkbox-design">
                                                <h4 class="filter-title collapsable closed"><?php echo ($current_language == 'fr') ? "Effets" : "Effects"; ?></h4>
                                                <div class="filter-content" style="display:none;">
                                                    <ul class="filter-items filter-checkbox  level-0">
                                                        <?php foreach ($effects as $tag_key => $tag_label) { ?>
                                                            <li class="filter-item checkbox  level-0 no-color">
                                                                <label for="filter_2618_6_154">
                                                                    <input data-type="effects" type="checkbox" name="prov[]" value="<?php echo bleuh_alphanumeric($tag_label); ?>">
                                                                    <a href="#" role="button" class="term-label">
                                                                        <?php echo strtolower($tag_label); ?>
                                                                    </a>
                                                                </label>
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- Tags -->
                                            <div class="yith-wcan-filter filter-tax checkbox-design">
                                                <h4 class="filter-title collapsable closed"><?php echo ($current_language == 'fr') ? "Disponibilité" : "Availability"; ?></h4>
                                                <div class="filter-content" style="display:none;">
                                                    <ul class="filter-items filter-checkbox  level-0">

                                                        <?php foreach ($tags as $tag_key => $tag_label) { ?>
                                                            <li class="filter-item checkbox  level-0 no-color">
                                                                <label for="filter_2618_6_154">
                                                                    <input data-type="tags" type="checkbox" name="prov[]" value="<?php echo bleuh_alphanumeric($tag_label); ?>">
                                                                    <a href="#" role="button" class="term-label">
                                                                        <?php
//                                                                        switch ($tag_label) {
//                                                                            case 'new':
//                                                                                echo ($current_language == 'fr') ? "Nouveau" : "New";
//                                                                                break;
//                                                                            case 'web-only':
//                                                                                echo ($current_language == 'fr') ? "Web seulement" : "Web only";
//                                                                                break;
//                                                                            case 'coming-soon':
//                                                                                echo ($current_language == 'fr') ? "Bientôt disponible" : "Coming soon";
//                                                                                break;
//                                                                            case 'best-seller':
//                                                                                echo ($current_language == 'fr') ? "Meilleur vendeur" : "Best seller";
//                                                                                break;
//                                                                            default:
//                                                                                echo $tag_label;
//                                                                        }
                                                                        echo $tag_label;
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
                                    'post_type' => 'product',
                                    'post_status' => 'publish',
                                    'orderby' => 'date',
                                    'order' => 'DESC',
                                    'posts_per_page' => -1,
                                    'fields' => 'ids',
                                    'lang' => $current_language, // Filter by WPML post language
                                    'suppress_filters' => false // Ensure WPML filters apply
                            ];
                            $query = new WP_Query($query_args);
                            wp_reset_postdata();
                            global $bleuh_add_filter_attributes;
                            if ($query->have_posts()) {
                                $to_display = [];
                                while ($query->have_posts()) {
                                    $query->the_post();
                                    global $product;
                                    $to_display[] = $product->get_id();
//                                    $query->the_post();
//                                    $pid = get_the_ID();
//                                    wc_get_template_part( 'content', 'product' );
                                }
                                bleuh_render_fav_products($to_display, true);
                                wp_reset_postdata();
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

    function bleuhTitleOverride(type = '') {
        $ = jQuery;
        let skipDefault = false;
        let defaultTitle = '';
        const retToJoin = [];

        let lang = (document.documentElement.lang.toLowerCase().includes("fr") ) ? "fr" : "en";

        if (type === 'h1') {
            defaultTitle = lang === 'fr' ? 'Nos produits' : 'Our Products';
        } else {
            defaultTitle = lang === 'fr'
                ? 'Découvrez nos produits de cannabis et leurs variétés en rotation.'
                : 'Discover our cannabis products and their rotating strains.';
        }

        let ret = '';
        if (type === 'h1') {
            ret += lang === 'fr' ? 'Nos produits' : 'Our Products';
        }
        ret += '<span>';

        /** ---- product_tag ---- */
        $("input[data-type='tags']:checked").each(function () {
            skipDefault = true;
            let label = $(this).next('a.term-label').text().trim().toLowerCase();
            retToJoin.push(label);
        });

        /** ---- filter_province ---- */
        let subRet = [];
        $("input[data-type='province']:checked").each(function () {
            skipDefault = true;
            let cap_label = $(this).next('a.term-label').text().trim();
            let label = $(this).next('a.term-label').text().trim().toLowerCase();
            if (label == 'ontario' || label == 'ontario') {
                if (lang === 'fr') label = "en " + cap_label;
                else label = "in " + cap_label;
            } else {
                if (lang === 'fr') label = "au " + cap_label;
                else label = "in " + cap_label;
            }
            subRet.push(label);
        });
        if (subRet.length > 0) {
            let joined = '';
            if (lang === 'fr') {
                joined = subRet.join(' et ');
            } else {
                joined = subRet.join(' or ');
            }
            retToJoin.push(joined);
        }

        /** ---- filter_marques ---- */
        let subList = [];
        $("input[data-type='collections']:checked").each(function () {
            skipDefault = true;
            let label = $(this).next('a.term-label').text().trim().toLowerCase();
            subList.push(label);
        });
        if (subList.length > 0) {
            if (lang === 'fr') {
                retToJoin.push(' de la marque ' + subList.join(' ou '));
            } else {
                retToJoin.push(' of the ' + subList.join(' or ') + ' brand');
            }
        }

        /** ---- filter_formats ---- */
        subList = [];
        $("input[data-type='formats']:checked").each(function () {
            skipDefault = true;
            let label = $(this).next('a.term-label').text().trim().toLowerCase();
            subList.push(label);
        });
        if (subList.length > 0) {
            retToJoin.push(
                lang === 'fr' ? subList.join(' ou ') : subList.join(' or ')
            );
        }
        // change banner based on format selected
        let bg_sel = $('.shop-page-title > .page-title-inner');
        if (subList.length > 0) {
            let format_for_banner = subList[0];
            if (format_for_banner.includes('fleur') || format_for_banner.includes('flower')) {
                bg_sel.css('background-image', 'url(/wp-content/plugins/bleuh-ux-builder/img/product-banners/flower.jpg)');
            } else if (format_for_banner.includes('prer') || format_for_banner.includes('roul')) {
                bg_sel.css('background-image', 'url(/wp-content/plugins/bleuh-ux-builder/img/product-banners/preroll.jpg)');
            } else if (format_for_banner.includes('mou') || format_for_banner.includes('grind')) {
                bg_sel.css('background-image', 'url(/wp-content/plugins/bleuh-ux-builder/img/product-banners/moulu.jpg)');
            } else if (format_for_banner.includes('vap')) {
                bg_sel.css('background-image', 'url(/wp-content/plugins/bleuh-ux-builder/img/product-banners/vape.jpg)');
            } else if (format_for_banner.includes('ha')) {
                bg_sel.css('background-image', 'url(/wp-content/plugins/bleuh-ux-builder/img/product-banners/hash.jpg)');
            } else {
                bg_sel.css('background-image', 'url(/wp-content/plugins/bleuh-ux-builder/img/product-banners/main.jpg)');
            }
        } else {
            bg_sel.css('background-image', 'url(/wp-content/plugins/bleuh-ux-builder/img/product-banners/main.jpg)');
        }


        /** ---- product_cat ---- */
        subList = [];
        $("input[data-type='categories']:checked").each(function () {
            skipDefault = true;
            let label = $(this).next('a.term-label').text().trim().toLowerCase();
            subList.push(label);
        });
        if (subList.length > 0) {
            retToJoin.push(
                lang === 'fr' ? subList.join(' ou ') : subList.join(' or ')
            );
        }

        ret += retToJoin.join(', ');
        ret += '</span>';

        if (!skipDefault) {
            return defaultTitle;
        }

        const prefix = lang === 'fr' ? 'Nos produits ' : 'Our Products ';

        if (type === 'h1') {
            return ret;
        } else if (type === 'meta-description') {
            return prefix + $(ret).text();
        } else if (type === 'document-title') {
            return prefix + $(ret).text() + ' | Bleuh';
        }

        return defaultTitle;
    }

    // TODO: calc box footers heights
    function bleuh_calc_box_footer() {
        let $ = jQuery;
        $("body .product-small.box .box-text").css("height", "auto");
        $(".products > .product-small:visible p.category").matchHeight();
        $(".products > .product-small:visible p.name").matchHeight();
    }

    function bleuh_alphanumeric(str) {
        str = str.toLowerCase();
        str = str.trim();
        return str.replace(/[^a-z0-9]/g, '');
    }

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
            bleuh_calc_box_footer();
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
                    let product = $(this).find('.var-box-link'); // Find the product's link element
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
                                    let productThcMax = parseFloat(product.attr('data-thc-max') || productThcMin); // Default to min if missing

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
                                let values = parts[1].split('+').map(value => bleuh_alphanumeric(value)); // Trim and lowercase each filter value

                                // Check if the product has at least one matching attribute for this filter type
                                let productValue = product.data(type); // Extract the `data-*` attribute matching the type
                                if (productValue) {
                                    let productValues = productValue
                                        .toString() // Ensure the product value is a string
                                        .toLowerCase() // Convert to lowercase
                                        .split(',') // Support comma-separated values
                                        .map(value => bleuh_alphanumeric(value)); // Trim each value

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

                if (sortBy === 'name') {
                    // works
                    aValue = $(a).find('p.name a').text().toLowerCase();
                    bValue = $(b).find('p.name a').text().toLowerCase();
                } else if (sortBy === 'thc') {
                    aValue = ((parseFloat($(a).find('.var-box-link').attr('data-thc-min')) || 0) + (parseFloat($(a).find('.var-box-link').attr('data-thc-max')) || 0)) / 2;
                    bValue = ((parseFloat($(b).find('.var-box-link').attr('data-thc-min')) || 0) + (parseFloat($(b).find('.var-box-link').attr('data-thc-max')) || 0)) / 2;
                } else if (sortBy === 'date') {
                    aValue = $(a).find('.var-box-link').attr('data-wrap-date');
                    bValue = $(b).find('.var-box-link').attr('data-wrap-date');
                } else if (sortBy === 'popularity') {
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
            bleuh_calc_box_footer();
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
                $("h1").html(bleuhTitleOverride('h1'));
            }
            bleuh_calc_box_footer();
        });

        $("h1").html(bleuhTitleOverride('h1'));
        bleuh_calc_box_footer();
    });
</script>
<?php do_action( 'flatsome_after_page' ); ?>

<?php get_footer();
