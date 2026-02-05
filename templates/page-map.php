<?php
defined( 'ABSPATH' ) || exit;
get_header(); ?>

<?php do_action( 'flatsome_before_page' ); ?>

<div class="section-content relative store-locator-page">

    <div class="row">
        <div class="col-lg-4">
            <h1><?php echo icl_t('bleuh', 'var_store_locator_h1', 'Magasinez par détaillant'); ?></h1>
            <div>
                <label>
                    <select id="store">
                        <option value="" data-geo-lat="" data-geo-lon=""></option>
                        <option value="WEB" data-geo-lat="" data-geo-lon="">Web</option>
                        <?php
                            global $wpdb;
                        $query = "SELECT `number`, `name`, ST_X(`location`) lon, ST_Y(`location`) lat, postal_code FROM {$wpdb->prefix}bleuh_stores ORDER BY `name`";
                            $prepared_query = $wpdb->prepare($query);
                            $results = $wpdb->get_results($prepared_query);
                            if (!empty($results)) {
                                foreach ($results as $row) {
                        ?>
    <option value="<?php echo $row->number ?>" data-geo-lat="<?php echo $row->lat ?>" data-geo-lon="<?php echo $row->lon ?>" data-postal-code="<?php echo str_replace(' ', '', strtoupper($row->postal_code)); ?>">
        <?php echo $row->name ?>
    </option>
                        <?php
                                }
                            }
                        ?>
                    </select>
                </label>
            </div>
        </div>
    </div>

    <div class="row">
        <a href="#" id="locate-me"><?php echo icl_t('bleuh', 'var_store_locator_locate_me', "Localisez-moi"); ?></a>
        <img id="locating" src="<?php echo plugin_dir_url(__FILE__) . "../img/"; ?>spinner.gif" alt="Locating..." />
        <h4><?php echo icl_t('bleuh', 'var_new_warning'); ?></h4>
    </div>

    <script type="text/javascript">
        function sortStores(lat, lng) {
            let stores = jQuery('.stores-list .store-box').toArray().sort((a, b) => {
                // Get the coordinates of each store
                let latA = jQuery(a).data('lat');
                let lngA = jQuery(a).data('lng');
                let latB = jQuery(b).data('lat');
                let lngB = jQuery(b).data('lng');

                // Calculate the distances to the specific coordinates
                let distA = haversine(lat, lng, latA, lngA);
                let distB = haversine(lat, lng, latB, lngB);

                // Sort the stores by distance
                return distA - distB;
            });

            jQuery('.stores-list .store-box').remove();

            stores.forEach(store => {
                jQuery('.stores-list').append(store);
            });
        }

        function displayTop3() {
            let i = 1;
            jQuery('.stores-list .store-box').each(function() {
                if (i <= 3) jQuery(this).fadeIn();
                i++;
            });
            jQuery("#locating").hide();
            jQuery("#more-stores").fadeIn();
            jQuery(".store-box address").matchHeight();
            jQuery(".store-box h2").matchHeight();
        }

        // locate me button
        jQuery(document).ready(function($) {

            $("#locate-me").click(function() {
                $('#store').val(null).trigger('change');
                jQuery('.stores-list .store-box').hide();
                jQuery("#locating").show();
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function (position) {
                        let lat = position.coords.latitude;
                        let lng = position.coords.longitude;
                        $(".stores-list").data("lat", lat).data("lng", lng);
                        sortStores(lat, lng);
                        $('.stores-list .store-box').each(function() {
                            let this_lat = $(this).data("lat");
                            let this_lng = $(this).data("lng");
                            $(this).find(".distance").html(haversine(lat, lng, this_lat, this_lng).toFixed(2) + " km");
                        });
                        displayTop3();
                    }, function (error) {
                        // display error
                        displayTop3();
                    });
                } else {
                    // display error
                    displayTop3();
                }
                return false;
            });

        });
    </script>

    <div class="row stores-list">
            <?php
            global $wpdb;
            $query = "SELECT *, ST_X(`location`) lon, ST_Y(`location`) lat, postal_code FROM {$wpdb->prefix}bleuh_stores ORDER BY `name`";
            $prepared_query = $wpdb->prepare($query);
            $results = $wpdb->get_results($prepared_query);

            // get stores info
            $store_fav_ids = [];
            foreach ($results as $row) {
                $store_fav_ids[] = 'store-'.$row->number;
            }
            $favs = get_fav_counts($store_fav_ids);

            if (!empty($results)) {
                foreach ($results as $i => $row) {
            ?>

<div class="store-box col-lg-4"
     data-store_number="<?php echo $row->number; ?>"
     style="display:none;"
     data-postal-code="<?php echo str_replace(' ', '', strtoupper($row->postal_code)); ?>"
     data-lat="<?php echo $row->lat; ?>"
     data-lng="<?php echo $row->lon; ?>">

    <div class="store-box-container">

        <?php
            $fav_id = 'store-'.$row->number;
            $favorites_count = $favs[ $fav_id ]['count'] ?? 0;
            $liked = ( $favs[ $fav_id ]['liked'] ) ? 'liked' : 'not-liked';
            echo '<span data-id="'.$fav_id.'" class="like-box '.$liked.'"><span class="counter">' . esc_attr($favorites_count) . '</span><i class="ico"></i></span>';
        ?>

        <?php if (strpos($row->number, 'ONTARIO') !== false) { ?>
            <h2><?php echo $row->name; ?></h2>
            <h3 style="visibility: hidden;"><?php echo $row->number; ?></h3>
        <?php } else { ?>
            <h2>SQDC - <?php echo $row->name; ?></h2>
            <h3><?php echo $row->number; ?></h3>
        <?php } ?>

        <address>
            <?php echo str_replace("?", icl_t('bleuh', 'ouverture', 'Ouverture'), nl2br($row->DailyAddressBlock)); ?>
        </address>

        <p class="distance"></p>

        <div class="actions">
            <span>
                <img class="inventory-icon" src="<?php echo plugin_dir_url(__FILE__) . "../img/"; ?>dispo.svg" alt="Disponible" />
                <a href="#" class="inventory"><?php echo icl_t('bleuh', 'var_store_locator_inventory', 'Inventaire'); ?></a>
            </span>
            <span>
                <img class="locate-icon" src="<?php echo plugin_dir_url(__FILE__) . "../img/"; ?>pin.svg" alt="Pin" />
                <a href="#" class="locate-me"><?php echo icl_t('bleuh', 'var_store_locator_locate', 'Localisation'); ?></a>
            </span>
        </div>
    </div>
</div>

            <?php
                }
            }
            ?>
    </div>

    <div class="row">
        <a href="#" id="more-stores" style="display: none;"><?php echo icl_t('bleuh', 'var_store_locator_more_stores', 'Voir plus de succursales'); ?></a>
        <img id="loading-stores" src="<?php echo plugin_dir_url(__FILE__) . "../img/"; ?>spinner.gif" alt="Loading Stores..." style="display: none;" />
    </div>

    <script type="text/javascript">
        // show more button
        jQuery(document).ready(function($) {
            $("#more-stores").click(function() {
                let i = 0;
                $(".store-box").not(":visible").each(function() {
                    i++;
                    if (i <= 3) {
                        $(this).fadeIn();
                    }
                });
                return false;
            });
        });
    </script>

    <script type="text/javascript">
        let bleuh_deliveries = {};

        // get store inventory
        function getStoreInventory($this = false) {
            let store_number, store_title, loading_icon;
            if (!$this) {
                store_number = 'WEB';
                store_title = 'Web';
                loading_icon = jQuery("#locating");
            } else {
                $this = $this.first();
                store_number = $this.parents(".store-box").attr("data-store_number");
                store_title = $this.parents(".store-box").find("h2").text();
                loading_icon = $this.parents(".store-box").find(".inventory-icon");
            }

            loading_icon.attr("src", '<?php echo plugin_dir_url(__FILE__) . "../img/"; ?>spinner.gif');
            jQuery("#selected-store").html("").show();
            jQuery("#map-container").hide();

            jQuery.post("/wp-admin/admin-ajax.php?cache=<?php echo md5(time()); ?>", {"POSTAL_CODE": encodeURIComponent(store_number), "action": "bleuh_ajax_store_inventory", 'debug': debug}, function(response) {
                let ret = `<div class="col-lg-12">
                                <h1><?php echo icl_t('bleuh', 'var_store_locator_inventory_of', 'Inventaire de'); ?>
                                <br>${store_title}</h1>
                            </div>`;
                response = JSON.parse(response);
                let previous_collection = "";
                for (let key in response) {
                    let product = response[key];
                    let addClass = '';
                    if (product.is_ontario === 'Y') {
                        addClass = 'is_ontario';
                    }
                    if (product.GTIN === null) continue;
                    if (previous_collection !== product.collection) {
                        ret += '<div class="col-lg-12 img-brand"><img src="<?php echo plugin_dir_url(__FILE__) . "../img/brands/"; ?>'+product.collection.toLowerCase()+'.png" alt="'+product.collection+'" /></div>'
                        previous_collection = product.collection;
                    }
                    let this_ret = ``;
                    this_ret += bleuh_product_template();

                    this_ret = this_ret.replace('%ADD_CLASS%', addClass);

                    <?php if (ICL_LANGUAGE_CODE == "fr") { ?>
                    this_ret = this_ret.replace("%BLEND%", `<a href="/produits/?yith_wcan=1&product_cat=${product.blend.toLowerCase()}">${product.blend}</a>`);
                    <?php } else { ?>
                    this_ret = this_ret.replace("%BLEND%", `<a href="/en/products/?yith_wcan=1&product_cat=${product.blend.toLowerCase()}-en">${product.blend_en}</a>`);
                    <?php }?>
                    let normalized_format = "";
                    <?php if (ICL_LANGUAGE_CODE == "fr") { ?>
                    normalized_format = product.format.toLowerCase().trim().normalize("NFD").replace(/[\u0300-\u036f]/g, "").split(' ')[0];
                    this_ret = this_ret.replace("%FORMAT%", `<a href="/en/products/?yith_wcan=1&query_type_formats=and&filter_formats=${normalized_format}">${product.format}</a>`);
                    <?php } else { ?>
                    normalized_format = product.format_en.toLowerCase().trim().normalize("NFD").replace(/[\u0300-\u036f]/g, "").split(' ')[0];
                    switch (normalized_format) {
                        case "dried":
                            normalized_format = "flowers";
                            break;
                        case "vape":
                            normalized_format = "vape-pen";
                            break;
                        case "haschich":
                            normalized_format = "haschich-en";
                            break;
                    }
                    this_ret = this_ret.replace("%FORMAT%", `<a href="/en/products/?yith_wcan=1&query_type_formats=and&filter_formats=${normalized_format}">${product.format_en}</a>`);
                    <?php }?>

                    if (debug) {
                        if (debug) {
                            bleuh_deliveries[product.GTIN.toLowerCase().trim()] = product.deliveries;
                        }

                        this_ret = jQuery(this_ret).find(".store-pad:eq(0)").append(`
                            <div>GTIN: <span class="GTIN">${product.GTIN.toLowerCase().trim()}</span></div>
                            <div>Store Number: ${store_number}</div>
                        `).parent(".product-box").get(0).outerHTML;
                    }
                    this_ret = this_ret.replace("%WEIGHT%", product.weight);
                    this_ret = this_ret.replace("%STORE_NUMBER%", product.GTIN.toLowerCase().trim());
                    this_ret = this_ret.replace("%NAME%", `<a href="${product.permalink}">${product.name}</a>`);
                    this_ret = this_ret.replace("%BUY_LINK%", product.buy_link);

                    let this_vars = ``;
                    if ('overrides' in product && product.overrides.length > 0) {
                        product.overrides.forEach(item => {
                            item.qty = item.override_qty;
                        });
                        var_overrides[product.GTIN.toLowerCase().trim()] = [...product.overrides];
                    }
                    if (product.varieties.length > 0) {
                        product.varieties.forEach(item => {
                            if (item.SQDC_SKU.toLowerCase().trim() === product.GTIN.toLowerCase().trim()) {
                                if (item.qty > 0) {
                                    item.store_number = store_number;
                                    if (typeof (var_tags[product.GTIN.toLowerCase().trim()]) === 'undefined') {
                                        var_tags[product.GTIN.toLowerCase().trim()] = [];
                                    }
                                    var_tags[product.GTIN.toLowerCase().trim()].push(item);
                                    var_tags_totals[product.GTIN.toLowerCase().trim()] = (var_tags_totals[product.GTIN.toLowerCase().trim()] || 0) + (Number(item.qty) || 0);
                                }
                            }
                        });

                        if (debug) {
                            this_ret = jQuery(this_ret).find(".store-pad:eq(0)").append(`
                            <div>Store qty before adjustments: ${product.cached_store_qty}</div>
                        `).parent(".product-box").get(0).outerHTML;
                        }
                        adjust_quantities(product.GTIN.toLowerCase().trim(), +product.cached_store_qty);
                        if (debug) {
                            this_ret = jQuery(this_ret).find(".store-pad:eq(0)").append(`
                            <div>Store qty after adjustments: ${product.cached_store_qty}</div>
                        `).parent(".product-box").get(0).outerHTML;
                        }
                        if ('overrides' in product && product.overrides.length > 0) {
                            product.overrides = override_adjust_quantities(product.overrides, +product.cached_store_qty);
                            if (product.overrides.length > 0) {
                                this_vars += product_varieties(product.overrides, +product.cached_store_qty, true);
                            } else {
                                this_vars += product_varieties(product.varieties, +product.cached_store_qty, false, store_number);
                            }
                        } else {
                            this_vars += product_varieties(product.varieties, +product.cached_store_qty, false, store_number);
                        }
                        this_vars += product_varieties_info_template();
                    } else {
                        if ('overrides' in product && product.overrides.length > 0) {
                            product.overrides = override_adjust_quantities(product.overrides, +product.cached_store_qty);
                            if (product.overrides.length > 0) {
                                this_vars += product_varieties(product.overrides, +product.cached_store_qty, true);
                            } else {
                                this_vars += product_varieties([], +product.cached_store_qty, false, store_number);
                            }
                        } else {
                            this_vars += product_varieties([], +product.cached_store_qty, false, store_number);
                        }
                        this_vars += product_varieties_info_template();
                    }

                    this_ret = this_ret.replace("%STORE_QTY%", product.cached_store_qty);

                    if (product.cached_store_qty > 0) {
                        this_ret = this_ret.replace("%AVAILABILITY%", `<img class="img-stock" src="<?php echo plugin_dir_url(__FILE__) . "../img/"; ?>dispo.svg" alt="Disponible" />`);
                    } else if (typeof (product.cached_store_qty) == 'undefined') {
                        this_ret = this_ret.replace("%AVAILABILITY%", ``);
                    } else {
                        this_ret = this_ret.replace("%AVAILABILITY%", `<img class="img-stock" src="<?php echo plugin_dir_url(__FILE__) . "../img/"; ?>non-dispo.svg" alt="Non disponible" />`);
                    }
                    this_ret = this_ret.replace("%VARIETIES%", this_vars);

                    ret += this_ret;
                }
                jQuery("#selected-store").attr("data-store_number", store_number).html(ret);
                if (!debug && store_number.toLocaleLowerCase().indexOf('ontario') === -1) {
                    jQuery(".product-box .link-caption .qty").each(function () {
                        if (jQuery(this).text() === "0" || jQuery(this).text() === "") {
                            jQuery(this).parents("a").hide();
                        }
                    });
                    jQuery(".product-box h2 a").each(function () {
                        if ((jQuery(this).attr("href") === "#")) {
                            jQuery(this).parents(".product-box").hide();
                        }
                    });
                } else {
                    jQuery(".product-box").each(function () {
                        // debug deliveries
                        let GTIN = jQuery(this).find(".store-pad:eq(0) .GTIN").text().toLowerCase().trim();
                        let deliveries = bleuh_deliveries[GTIN];
                        if (typeof (deliveries) !== 'undefined') {
                            let deliveries_html = `<div class="deliveries">Deliveries:<br>`;
                            deliveries.forEach(delivery => {
                                deliveries_html += delivery.latest_sunday.split(' ')[0] + ' | lot: ' + delivery.lot + ' | QTY: ' + delivery.qty + '<br>';
                            });
                            deliveries_html += `</div>`;
                            jQuery(this).find(".store-pad:eq(0)").append(deliveries_html);
                        }
                    });
                }
                loading_icon.attr("src", '<?php echo plugin_dir_url(__FILE__) . "../img/"; ?>dispo.svg');
                jQuery("#selected-store h2").matchHeight();
                jQuery("#selected-store h3").matchHeight();
                jQuery("#selected-store ul li span").matchHeight();
                jQuery("#selected-store ul li p").matchHeight();
                jQuery("#selected-store .store-tags").matchHeight();
                jQuery('html, body').animate({scrollTop: jQuery("#selected-store").offset().top - jQuery("#masthead").height() - 30}, 'slow');
            });
        }

        // selected store inventory
        jQuery(document).ready(function($) {

            let map, infoWindow, directionsService, directionsRenderer, locate_loading_icon;
            function initMap() {
                const defaultLocation = { lat: 45.465684, lng: -75.716872 }; // Replace with a default location
                map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 14,
                    center: defaultLocation
                });

                infoWindow = new google.maps.InfoWindow();
                directionsService = new google.maps.DirectionsService();
                directionsRenderer = new google.maps.DirectionsRenderer();
                directionsRenderer.setMap(map);
                directionsRenderer.setPanel(document.getElementById('directionsPanel')); // Panel for directions steps
            }

            function mapReady() {
                locate_loading_icon.attr("src", '<?php echo plugin_dir_url(__FILE__) . "../img/"; ?>pin.svg');
                $("#map-container").fadeIn();
                $('html, body').animate({scrollTop: $("#map").offset().top - $("#masthead").height() - 30}, 'slow');
            }

            function handleLocationError(fallbackLocation) {
                map.setCenter(fallbackLocation);
                const marker = new google.maps.Marker({
                    position: fallbackLocation,
                    map: map,
                    title: 'SQDC'
                });
                mapReady();
            }

            function calculateAndDisplayRoute(directionsService, directionsRenderer, destination) {
                if(navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition((position) => {
                        const currentLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };

                        directionsService.route({
                            origin: currentLocation,
                            destination: destination,
                            travelMode: google.maps.TravelMode.DRIVING
                        }, (response, status) => {
                            if (status === 'OK') {
                                directionsRenderer.setDirections(response);
                                mapReady();
                            } else {
                                handleLocationError(destination);
                            }
                        });
                    }, () => {
                        handleLocationError(destination);
                    });
                } else {
                    handleLocationError(destination);
                }
            }

            function get_url_param(param) {
                let urlParams = new URLSearchParams(window.location.search);
                return urlParams.get(param);
            }

            let bleuh_map_start_flag = 0;
            function bleuh_locate_me_click(element) {
                bleuh_map_start_flag++;
                if (typeof google == 'undefined' || typeof google.maps == 'undefined') {
                    if (bleuh_map_start_flag > 10) {
                        console.error("Google Maps API not loaded after multiple attempts.");
                        return;
                    }
                    setTimeout(function() {
                        bleuh_locate_me_click(element);
                    }, 500);
                    return;
                }
                let lat = element.parents(".store-box").data("lat");
                let lng = element.parents(".store-box").data("lng");
                const destination = { lat: lat, lng: lng }; // Replace with your destination coordinates
                $("#selected-store").fadeOut();
                $("#map-container").hide();
                locate_loading_icon = element.parents(".store-box").find(".locate-icon");
                locate_loading_icon.attr("src", '<?php echo plugin_dir_url(__FILE__) . "../img/"; ?>spinner.gif');
                initMap();
                calculateAndDisplayRoute(directionsService, directionsRenderer, destination);
                return false;
            }

            $(document).on("click", ".store-box .locate-me", function(e) {
                let $this = $(this);
                e.preventDefault();
                bleuh_locate_me_click($this);
                return false;
            });

            $(document).on("click", ".store-box .inventory", function(e) {
                e.preventDefault();
                getStoreInventory($(this));
                return false;
            });

            $("#store").select2({
                placeholder: "<?php echo icl_t('bleuh', 'var_store_store', "Détaillant"); ?>...",
                tags: true,
                width: "100%",
                createTag: function (params) {
                    // Add "Address search: " prefix to custom text
                    return {
                        id: params.term, // Use the entered text as the ID
                        <?php if (ICL_LANGUAGE_CODE == "fr") { ?>
                        text: `Recherche d'adresse: ${params.term}`, // Prefix the custom text
                        <?php } else { ?>
                        text: `Address search: ${params.term}`, // Prefix the custom text
                        <?php } ?>
                        newOption: true // Mark this as a new option
                    };
                },
                templateResult: function (data) {
                    // Highlight new options with a label (optional)
                    if (data.newOption) {
                        return $(`<span><em>${data.text}</em></span>`);
                    }
                    return data.text;
                }
            });
            $('#store').on('select2:select', function (e) {
                const val = $(this).val();
                if (val === "") return true;
                const selectedData = e.params.data;

                if (selectedData.newOption) {
                    // fetch lat lon from google maps api
                    jQuery.ajax({
                        url: "https://maps.googleapis.com/maps/api/geocode/json",
                        data: {
                            address: val,
                            key: "<?php echo GOOGLE_MAPS_API_KEY; ?>"
                        },
                        success: function(response) {
                            if (response.status === "OK") {
                                const lat = response.results[0].geometry.location.lat;
                                const lon = response.results[0].geometry.location.lng;
                                jQuery('.stores-list .store-box').hide();
                                sortStores(lat, lon);
                                displayTop3();
                            }
                        }
                    });
                } else {
                    if (val !== "WEB") {
                        const lat = $(this).find("option[value='" + val + "']").data("geo-lat");
                        const lon = $(this).find("option[value='" + val + "']").data("geo-lon");
                        jQuery('.stores-list .store-box').hide();
                        sortStores(lat, lon);
                        displayTop3();
                    } else {
                        jQuery('.stores-list .store-box').hide();
                        jQuery("#more-stores").hide();
                        getStoreInventory();
                    }
                }
            });

            // load top 3 close locations on page load
            $("#locate-me").click();

            // load store on window load
            if (window.location.hash) {
                let select_value = 'WEB'; // load web store by default
                let hash = decodeURI(window.location.hash);
                hash = hash.substring(1).replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
                $("#store option").each(function() {
                    let this_option = $(this);
                    let option_val = this_option.text().replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
                    if (hash.length === 6) {
                        let option_postal_code = this_option.attr("data-postal-code");
                        if (option_postal_code !== undefined && option_postal_code.toLowerCase().trim().replace(' ', '') === hash.toLowerCase().trim().replace(' ', '')) {
                            select_value = this_option.attr("value");
                        }
                    } else if (option_val.includes(hash)) {
                        select_value = this_option.attr("value");
                    }
                });
                $("#store").val(select_value).trigger('change').trigger({
                    type: 'select2:select',
                    params: {
                        data: {
                            id: select_value, // The value of the selected option
                            text: $('#store').find(`option[value="${select_value}"]`).text(), // The text of the selected option
                        }
                    }
                });

                $(".store-box .inventory").first().click();
            } else {
                let select_value = 'WEB'; // load web store by default
                let postal_code_to_load = '';
                let param_locate = get_url_param('locate')
                // locate by postal code if present in URL
                if (param_locate !== undefined && param_locate !== null && param_locate !== '') {
                    let locate = decodeURI(param_locate);
                    locate = locate.replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
                    $("#store option").each(function() {
                        let this_option = $(this);
                        let postal_code = this_option.attr("data-postal-code");
                        if (decodeURI(param_locate).replace(/[^a-zA-Z0-9]/g, '').toLowerCase().trim() === decodeURI(postal_code).replace(/[^a-zA-Z0-9]/g, '').toLowerCase().trim()) {
                            select_value = this_option.attr("value");
                            postal_code_to_load = postal_code.toUpperCase().replace(' ', '');
                        }
                    });
                    $("#store").val(select_value).trigger('change').trigger({
                        type: 'select2:select',
                        params: {
                            data: {
                                id: select_value, // The value of the selected option
                                text: $('#store').find(`option[value="${select_value}"]`).text(), // The text of the selected option
                            }
                        }
                    });

                    if ($(".store-box[data-postal-code='"+postal_code_to_load+"']").length > 0) {
                        $(".store-box[data-postal-code='"+postal_code_to_load+"']").find(".locate-me").first().click();
                    }

                }
            }
        });
    </script>

    <div id="selected-store" class="row" data-pc=""></div>
    <div class="row" id="map-container">
        <div id="map" style="height: 400px; width: 100%;"></div>
        <div id="directionsPanel" style="display: none;"></div>
    </div>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCW-4UHo_7yj7hBtqOpMiYmTkHACn_M6Pk"></script>

</div>

<?php do_action( 'flatsome_after_page' ); ?>

<?php get_footer(); ?>
