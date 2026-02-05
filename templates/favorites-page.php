<?php
defined( 'ABSPATH' ) || exit;
get_header(); ?>

<?php do_action( 'flatsome_before_page' );
$current_language = ICL_LANGUAGE_CODE;
?>


    <div class="section-content relative varieties_page_container fav-page-container">

        <div class="row row-main">
            <section class="section" id="weekly-section-head" style="padding-left:0;">
                <div class="section-bg fill"></div>
                <div class="section-content relative row">
                    <h1><?php
                        echo ( $current_language == 'en' ) ? "Favorites" : "Favoris";
                        ?></h1>
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
                            <!--
                            <button class="dropdown-btn"><?php echo ($current_language == 'en') ? "Sort" : "Trier"; ?></button>
                            <ul class="dropdown-options">
                                <li data-value="date-desc"><?php echo ($current_language == 'en') ? "Default - Most recent variety" : "Défaut - Lot le plus récent"; ?></li>
                                <li data-value="thc"><?php echo ($current_language == 'en') ? "THC (ascending)" : "THC (croissant)"; ?></li>
                                <li data-value="thc-desc"><?php echo ($current_language == 'en') ? "THC (descending)" : "THC (décroissant)"; ?></li>
                                <li data-value="name"><?php echo ($current_language == 'en') ? "Alphabetical A-Z" : "Alphabétique A-Z"; ?></li>
                                <li data-value="name-desc"><?php echo ($current_language == 'en') ? "Alphabetical Z-A" : "Alphabétique Z-A"; ?></li>
                                <li data-value="popularity-desc"><?php echo ($current_language == 'en') ? "Popularity" : "Popularité"; ?></li>
                            </ul>
                            -->
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
                                                <h4 class="filter-title collapsable closed"><?php echo ($current_language == 'en') ? "My account" : "Mon compte"; ?></h4>
                                                <div class="filter-content" style="display: block">
                                                    <ul class="filter-items filter-checkbox  level-0">
                                                        <li class="filter-item checkbox  level-0 no-color">
                                                            <a href="?content-type=products&nc=<?php echo md5(time()); ?>" data-menu-id="products" role="button" class="term-label <?php
                                                                if ((isset($_GET["content-type"]) && $_GET["content-type"] == "products")){
                                                                    echo 'active';
                                                                }
                                                                if (empty($_GET["content-type"])) {
                                                                    echo 'active';
                                                                }
                                                            ?>">
                                                                <?php echo ($current_language == 'en') ? "Products" : "Produits"; ?>
                                                            </a>
                                                        </li>
                                                        <li class="filter-item checkbox  level-0 no-color">
                                                            <a href="?content-type=varieties&nc=<?php echo md5(time()); ?>" data-menu-id="varieties" role="button" class="term-label <?php echo (isset($_GET["content-type"]) && $_GET["content-type"] == "varieties") ? 'active' : ''; ?>">
                                                                <?php echo ($current_language == 'en') ? "Varieties" : "Variétés"; ?>
                                                            </a>
                                                        </li>
                                                        <li class="filter-item checkbox  level-0 no-color">
                                                            <a href="?content-type=retailers&nc=<?php echo md5(time()); ?>" data-menu-id="retailers" role="button" class="term-label <?php echo (isset($_GET["content-type"]) && $_GET["content-type"] == "retailers") ? 'active' : ''; ?>">
                                                                <?php echo ($current_language == 'en') ? "Retailers" : "Détaillants"; ?>
                                                            </a>
                                                        </li>
                                                        <!--
                                                        <li class="filter-item checkbox  level-0 no-color">
                                                            <a href="?content-type=account" data-menu-id="account" role="button" class="term-label <?php echo (isset($_GET["content-type"]) && $_GET["content-type"] == "account") ? 'active' : ''; ?>">
                                                                <?php echo ($current_language == 'en') ? "Account settings" : "Gérer mon compte"; ?>
                                                            </a>
                                                        </li>
                                                        -->
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
                            <div class="no-results-blurb">
                            <?php
                                if ($current_language == 'en') {
?>
                                        <div class="products-not-found" style="display: none;">
                                            <p>No products found in your favorites list. Please click on the heart icon while <a href="/en/products/">browsing products</a>.</p>
                                        </div>
                                        <div class="varieties-not-found" style="display: none;">
                                            <p>No varieties found in your favorites list. Please click on the heart icon while <a href="/en/strains/">browsing varieties</a>.</p>
                                        </div>
                                        <div class="retailers-not-found" style="display: none;">
                                            <p>No retailers found in your favorites list. Please click on the heart icon while <a href="/en/store-locator/">browsing retailers</a>.</p>
                                        </div>
<?php
                                } else {
?>
                                    <div class="products-not-found" style="display: none;">
                                        <p>Aucun produit n'a été trouvé dans votre liste de favoris. Veuillez cliquer sur l'icône en forme de cœur en visitant la <a href="/produits/">page des produits</a> pour ajouter des favoris.</p>
                                    </div>
                                    <div class="varieties-not-found" style="display: none;">
                                        <p>Aucune variété n'a été trouvée dans votre liste de favoris. Veuillez cliquer sur l'icône en forme de cœur en visitant la <a href="/varietes/">page des variétés</a> pour ajouter des favoris.</p>
                                    </div>
                                    <div class="retailers-not-found" style="display: none;">
                                        <p>Aucun détaillant n'a été trouvé dans votre liste de favoris. Veuillez cliquer sur l'icône en forme de cœur en visitant la <a href="/map/">page des détaillants</a> pour ajouter des favoris.</p>
                                    </div>
<?php
                                }
                            ?>
                            </div>
                            <div class="products row row-small large-columns-3 medium-columns-3 small-columns-1 has-equal-box-heights equalize-box">

                                <?php
                                $favorites = get_fav_counts();
                                $id_list = [];

                                // render the favorite products / varieties / retailers
                                if (isset($_GET["content-type"]) && $_GET["content-type"] == "retailers") {
                                    // get favorite retailers
                                    foreach ($favorites as $retailer_id => $retailer_data) {
                                        if (isset($retailer_data['liked']) && $retailer_data['liked']) {
                                            if (substr($retailer_id, 0,6) == 'store-') {
                                                $store_number = substr($retailer_id, 6);
                                                $id_list[$store_number] = $retailer_id;
                                            }
                                        }
                                    }

                                    $store_numbers = array_keys($id_list);

                                    global $wpdb;
                                    $retailers = $wpdb->get_results(
                                        $wpdb->prepare(
                                            "SELECT * FROM {$wpdb->prefix}bleuh_stores WHERE number IN (" . implode(',', array_fill(0, count($store_numbers), '%s')) . ")",
                                            $store_numbers
                                        ),
                                        ARRAY_A
                                    );

                                    // render the retailer
                                    foreach ($retailers as $store_data) {
                                        $pid             = "store-". $store_data["number"];
                                        $favorites_count = $favorites[ $pid ]['count'] ?? 0;
                                        $liked           = ( $favorites[ $pid ]['liked'] ) ? 'liked' : 'not-liked';
                                    ?>
                                    <div class="store-box col-lg-4" data-store_number="77006" style="" data-lat="45.465684" data-lng="-75.716872">
                                        <div class="store-box-container">

                                            <?php
                                                echo '<span data-id="store-' . $store_data["number"] . '" class="like-box ' . $liked . '"><span class="counter">' . esc_attr( $favorites_count ) . '</span><i class="ico"></i></span>';
                                            ?>

                                            <h2><?php echo $store_data["name"]; ?></h2>
                                            <h3><?php echo $store_data["number"]; ?></h3>

                                            <address>
                                                <?php
                                                $blurb = 'Ouverture';
                                                if ($current_language == 'en') {
                                                    $blurb = 'Opening';
                                                }
                                                ?>
                                                <?php echo str_replace('?', $blurb, nl2br($store_data["DailyAddressBlock"])); ?>
                                            </address>

                                            <div class="actions">
                                                <span>
                                                    <img class="inventory-icon" src="https://staging2.bleuh.co/wp-content/plugins/bleuh-ux-builder/templates/../img/dispo.svg" alt="Disponible">
                                                    <?php $bleuh_link =  ($current_language == 'en') ? "/en/store-locator/#" : "/map/#"; ?>
                                                    <a href="<?php echo $bleuh_link; ?><?php echo $store_data["postal_code"]; ?>" class="inventory"><?php echo ($current_language == 'en') ? "Inventory" : "Inventaire"; ?></a>
                                                </span>
                                                <span>
                                                    <?php $bleuh_link =  ($current_language == 'en') ? "/en/store-locator/" : "/map/"; ?>
                                                    <img class="locate-icon" src="https://staging2.bleuh.co/wp-content/plugins/bleuh-ux-builder/templates/../img/pin.svg" alt="Pin">
                                                    <a href="<?php echo $bleuh_link; ?>?locate=<?php echo $store_data["postal_code"].'&'.md5(time()); ?>" class="locate-me"><?php echo ($current_language == 'en') ? "Location" : "Localisation"; ?></a>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
<?php
                                    }
                                } elseif (isset($_GET["content-type"]) && $_GET["content-type"] == "varieties") {
                                    // get favorite varieties
                                    foreach ($favorites as $variety_id => $variety_data) {
                                        $variety = get_post($variety_id);
                                        if ($variety) {
                                            if ($variety->post_type == 'featured_item' && $variety->post_status == 'publish') {
                                                if (isset($variety_data['liked']) && $variety_data['liked']) {
                                                    $id_list[] = $variety_id;
                                                }
                                            }
                                        }
                                    }

                                    echo bleuh_render_vars($id_list);
                                } else {
                                    // products
                                    foreach ($favorites as $id => $p_data) {
                                        $product = get_post($id);
                                        if ($product) {
                                            if ($product->post_type == 'product' && $product->post_status == 'publish') {
                                                if (isset($p_data['liked']) && $p_data['liked']) {
                                                    $id_list[] = $id;
                                                }
                                            }
                                        }
                                    }

                                    bleuh_render_fav_products($id_list);
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
        function refresh_item_count() {
            let visible_count = $('.varieties_page_container .products > *:visible').length;
            if (visible_count !== $(".count-header span").text()) {
                $(".count-header span").text(visible_count);
                if (visible_count === 0) {
                    let cat = $("ul.filter-items li a.active").attr("data-menu-id");
                    switch (cat) {
                        case "products":
                            $('.products-not-found').show();
                            $('.varieties-not-found').hide();
                            $('.retailers-not-found').hide();
                            break;
                        case "varieties":
                            $('.products-not-found').hide();
                            $('.varieties-not-found').show();
                            $('.retailers-not-found').hide();
                            break;
                        case "retailers":
                            $('.products-not-found').hide();
                            $('.varieties-not-found').hide();
                            $('.retailers-not-found').show();
                            break;
                        default:
                            $('.products-not-found').show();
                            $('.varieties-not-found').hide();
                            $('.retailers-not-found').hide();
                    }

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

        $(document).on("click", 'button.mobile-filters-button,.mobile-filter-close',  function (e) {
            e.preventDefault();
            $(".mobile-side-panel").toggleClass("active");
        });

    });
</script>
<?php do_action( 'flatsome_after_page' ); ?>

<?php get_footer();
