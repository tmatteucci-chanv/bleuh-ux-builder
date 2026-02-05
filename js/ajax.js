// const cookies_api = Cookies.withAttributes({ path: '/', domain: '.bleuh.co', sameSite: 'strict' });
let loading_screen = true;
let bleuh_deliveries = [];
jQuery(document).ready(function($) {

    JSON.parse(ajax_bleuh_info.overrides).forEach(item => {
        item.store_number = item.store_number.trim();
        item.qty = item.override_qty.trim();
        item.depleted = item.depleted.trim();
        if (typeof(var_overrides[item.store_number]) === 'undefined') {
            var_overrides[item.store_number] = [];
        }
        var_overrides[item.store_number].push(item);
    });

    // loading icon or inventory available or not available icon
    let webSuccImg = $(".product-short-description img:eq(0)");

    if (webSuccImg.length > 0) {
        webSuccImg.attr("src", ajax_bleuh_info.plugin_url + "/img/spinner.gif");
    }

    $.ajaxSetup({
        cache: false
    });

    let SKU = "";

    if (ajax_bleuh_info.SKU != null && ajax_bleuh_info.SKU.length > 0) {
        SKU = ajax_bleuh_info.SKU[0];
    } else {
        SKU = $("form.cart").attr("action").split("/").pop().split('#')[0];
        if (debug) {
            console.log("NO SKU PROVIDED, USING SKU FROM PRODUCT BUY URL: " + SKU);
        }
    }

    var_tags = {};
    web_qty = 0;
    var_tags_totals = {};
    JSON.parse(ajax_bleuh_info.varieties).forEach(item => {
        if (item.SQDC_SKU.toLowerCase().trim() === SKU.toLowerCase().trim()) {
            item.store_number = item.store_number.trim();

            if (typeof(var_tags[item.store_number]) === 'undefined') {
                var_tags[item.store_number] = [];
            }

            var_tags[item.store_number].push(item);
            var_tags_totals[item.store_number] = (var_tags_totals[item.store_number] || 0) + (Number(item.qty) || 0);

            if (item.store_number === 'WEB') {
                web_qty += (Number(item.qty) || 0);
            }
        }
    });

    if (debug) {
        JSON.parse(ajax_bleuh_info.deliveries).forEach(item => {
            if (item.GTIN.toLowerCase().trim() === SKU.toLowerCase().trim()) {
                item.store_number = item.store_number.trim();

                if (typeof(bleuh_deliveries[item.store_number]) === 'undefined') {
                    bleuh_deliveries[item.store_number] = [];
                }

                bleuh_deliveries[item.store_number].push(item);
            }
        });
    }

    let ajax_data = {
        'action': 'bleuh_ajax_inventory',
        'SKU': SKU,
        'lang': ajax_bleuh_info.lang,
        'debug': debug,
        'is_ontario': ajax_bleuh_info.is_ontario
    };

    function update_admin_buttons_display() {
        if (ajax_bleuh_info.can_manage_tags) {
            $(".override-admin-wrapper ol li").each(function() {
                let $this = $(this);
                if ($this.is(":first-child")) {
                    $this.find(".var-adjust.up-link").hide();
                } else {
                    $this.find(".var-adjust.up-link").show();
                }
                if ($this.is(":last-child")) {
                    $this.find(".var-adjust.down-link").hide();
                } else {
                    $this.find(".var-adjust.down-link").show();
                }
                if ($this.is(":only-child")) {
                    $this.find(".var-adjust.del-link").hide();
                } else {
                    $this.find(".var-adjust.del-link").show();
                }
            });
            refreshSelectOptions();
        }
    }

    function refreshSelectOptions() {
        if (debug && ajax_bleuh_info.can_manage_tags) {
            $("select.select2-admin").select2({
                placeholder: "Sélection de variété",
                allowClear: false,
                dropdownParent: $(".inventory-right .stores-scroll-area"),
            });
        }
    }

    function sortInventories(lat, lng) {
        let stores = $('.stores-list .store-info').toArray().sort((a, b) => {
            // Get the coordinates of each store
            let latA = $(a).data('lat');
            let lngA = $(a).data('lng');
            let latB = $(b).data('lat');
            let lngB = $(b).data('lng');

            // Calculate the distances to the specific coordinates
            let distA = haversine(lat, lng, latA, lngA);
            let distB = haversine(lat, lng, latB, lngB);

            // Sort the stores by distance
            return distA - distB;
        });

        $('.stores-list .store-info').remove();

        stores.forEach(store => {
            $('.stores-list').append(store);
        });
    }

    function nl2br(str) {
        if (typeof str !== "string") return "";
        return str.replace(/\n/g, "<br>");
    }
    function encodeHTML(str) {
        // Escape special HTML characters to prevent XSS
        return str.replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function fetchInventories(display) {
        if (ajax_bleuh_info.is_ontario === 'Y') {

            let ret = '';

            let available = true;
            let svgDispoIcon = '';

            ret += '<div class="inventory-right" style="display:none;">';
            ret += '    <a class="close-inventory" href="#"><svg xmlns="http://www.w3.org/2000/svg" width="17.193" height="17.193" viewBox="0 0 17.193 17.193">\n' +
                '  <g id="Groupe_917" data-name="Groupe 917" transform="translate(-1753.76 -433.759)">\n' +
                '    <g id="Groupe_897" data-name="Groupe 897" transform="translate(1753.759 433.759)">\n' +
                '      <path id="Icon_feather-chevron-down" data-name="Icon feather-chevron-down" d="M16.6,22.594a1.5,1.5,0,0,1-1.059-.439l-7.1-7.1a1.5,1.5,0,0,1,2.118-2.118l6.04,6.04,6.04-6.04a1.5,1.5,0,1,1,2.118,2.118l-7.1,7.1A1.5,1.5,0,0,1,16.6,22.594Z" transform="translate(-8 -12.5)" fill="#021760"/>\n' +
                '      <path id="Icon_feather-chevron-down-2" data-name="Icon feather-chevron-down" d="M8.6,10.094a1.5,1.5,0,0,1-1.059-.439l-7.1-7.1A1.5,1.5,0,0,1,2.556.439L8.6,6.479l6.04-6.04a1.5,1.5,0,1,1,2.118,2.118l-7.1,7.1A1.5,1.5,0,0,1,8.6,10.094Z" transform="translate(17.193 17.193) rotate(180)" fill="#021760"/>\n' +
                '    </g>\n' +
                '  </g>\n' +
                '</svg></a>';

            ret += '    <h2> ' + ajax_bleuh_info.dispo_header_glob + '</h2>';

            ret += '    <div class="search-location">';
            ret += '        <input type="text" placeholder="' + ajax_bleuh_info.postal_code + '" />';
            ret += '        <a class="search_location" href="#"><svg xmlns="http://www.w3.org/2000/svg" width="28.677" height="28.677" viewBox="0 0 28.677 28.677">\n' +
                '  <g id="Icon_feather-search" data-name="Icon feather-search" transform="translate(-3.5 -3.5)">\n' +
                '    <path id="Tracé_447" data-name="Tracé 447" d="M27.5,16A11.5,11.5,0,1,1,16,4.5,11.5,11.5,0,0,1,27.5,16Z" transform="translate(0 0)" fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>\n' +
                '    <path id="Tracé_448" data-name="Tracé 448" d="M31.5,31.5l-6.525-6.525" transform="translate(-0.738 -0.737)" fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>\n' +
                '  </g>\n' +
                '</svg>\n</a>';
            ret += '    </div>';
            ret += '    <a class="locate_me" href="#">' + ajax_bleuh_info.locate_me + '</a>';

            ret += `<p class="warning-head">*`+ajax_bleuh_info.caption_on_product_blurb+`</p>`;
                
            let stock_label = ajax_bleuh_info.stock_succursales.replace('?', '???');
            ret += '<div class="stores-scroll-area"><h3>'+encodeHTML(stock_label)+'</h3><div class="stores-list">';

            let scount = 0;
            $.each(ajax_bleuh_info.ontario_stores, function (store, storeValue) {
                if ($.trim(storeValue.name) !== '') {
                    ret += '<div class="store-info" data-lat="' + storeValue.latitude + '" data-lng="' + storeValue.longitude + '">';
                    ret += '  <div class="store-pad">'
                    ret += '    <div class="store-name">' + storeValue.name + '</div>';
                    ret += '  </div>';
                    ret += '  <div class="tab-p tab-address store-pad">';
                    ret += '    <div class="last-update-var" style="display:none;">Store #<span class="store_number">77096</span></div>';
                    ret += '    <p>' + nl2br(storeValue.DailyAddressBlock) + '</p>';
                    ret += '</div>';
                    ret += '</div>';
                    scount++;
                }
            });

            ret = ret.replace('???', scount.toString());

            if (scount > 0) {
                svgDispoIcon = '/wp-content/plugins/bleuh-ux-builder/img/dispo.svg';
            } else {
                svgDispoIcon = '/wp-content/plugins/bleuh-ux-builder/img/non-dispo.svg';
            }

            ret += '</div>';
            ret += '</div>';
            ret += '</div>';

            $('body.single-product .product-short-description img').attr('src', svgDispoIcon);
            $("body").prepend(ret);

            $(".locate_me").click();

            return;
        }

        $.post(ajax_bleuh_info.ajax_url, ajax_data, function (response) {

            try {
                response = JSON.parse(response);

                let ret = "";
                let inStockStores = 0;

                ret += '<div class="stores-list">';

                $.each(response.Stores, function (store, storeValue) {

                    if (response.Stores[store].InventoryStatus.Quantity > 0) {

                        let svgDispoIcon = "";

                        if (response.Stores[store].InventoryStatus.Quantity > 0) {
                            inStockStores++;
                            svgDispoIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21">\n' +
                                '  <g id="Groupe_1112" data-name="Groupe 1112" transform="translate(-1015 -759.5)">\n' +
                                '    <circle id="Ellipse_101" data-name="Ellipse 101" cx="10.5" cy="10.5" r="10.5" transform="translate(1015 759.5)" fill="#fff"/>\n' +
                                '    <path id="check_FILL0_wght400_GRAD0_opsz24" d="M157.149-714.357,154-717.506l.787-.787,2.362,2.362L162.218-721l.787.787Z" transform="translate(866.46 1487.678)" fill="#1fac0d"/>\n' +
                                '  </g>\n' +
                                '</svg>';
                        } else {
                            svgDispoIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21">\n' +
                                '  <g id="Groupe_1113" data-name="Groupe 1113" transform="translate(-1015 -688)">\n' +
                                '    <circle id="Ellipse_101" data-name="Ellipse 101" cx="10.5" cy="10.5" r="10.5" transform="translate(1015 688)" fill="#fff"/>\n' +
                                '    <path id="x" d="M5.17,5.17a.548.548,0,0,1,.793,0l3,3,3-3c.057-.057.113-.113.17-.113a.481.481,0,0,1,.453,0c.057.057.113.057.17.113s.113.113.113.17a.341.341,0,0,1,.057.227.341.341,0,0,1-.057.227c-.057.057-.057.113-.113.17l-3,3,3,3a.561.561,0,1,1-.793.793l-3-3-3,3a.548.548,0,0,1-.793,0,.548.548,0,0,1,0-.793l3-3-3-3a.548.548,0,0,1,0-.793Z" transform="translate(1016.535 689.535)" fill="#ff1818" fill-rule="evenodd"/>\n' +
                                '  </g>\n' +
                                '</svg>';
                        }

                        ret += '<div class="store-info" data-lat="' + storeValue["Address"].Latitude + '" data-lng="' + storeValue["Address"].Longitude + '">';
                        ret += '  <div class="store-pad">'
                        ret += '    <div class="store-name">SQDC - ' + storeValue.LocalizedDisplayName + '</div>';
                        ret += '    <div class="store-products">' + svgDispoIcon + '<span>' + storeValue.InventoryStatus.Quantity + ' ' + ajax_bleuh_info.caption_units_available + '</span></div>';

                        let removed_lots = [];
                        let recent_lots_sales = [];
                        let store_number = (storeValue["Number"] + "").trim();
                        let live_qty = storeValue.InventoryStatus.Quantity || 0;
                        if (store_number in var_tags) {

                            // Assume `storeValue.InventoryStatus.Quantity` holds the live quantity you want to match
                            let var_tags_array = JSON.parse(JSON.stringify(var_tags[store_number] || []));

                            // Sort the var_tags_array by date if not already sorted, oldest first
                            var_tags_array.sort((a, b) => a.order_weight - b.order_weight);

                            // If the `var_tags_array` is empty or `live_qty` is 0, no need to process further
                            if (var_tags_array.length === 0 || live_qty === 0) {
                                // Handle the scenario where no lots should remain
                                removed_lots = [...var_tags_array]; // Copy all lots to removed_lots
                            } else {
                                // Calculate the 'new_total' by summing up the quantities in var_tags_array
                                // let new_total = var_tags_array.reduce((sum, item) => sum + (+item.qty || 0), 0);
                                let new_total = var_tags_array.reduce((sum, item) => {
                                    // Inline function to modify item.qty
                                    const modifyQuantity = (qty) => (qty < 0 ? 0 : +qty);

                                    // Use the inline function to get the modified quantity
                                    return sum + modifyQuantity(item.qty);
                                }, 0);

                                // Start from the newest lot
                                let index = var_tags_array.length - 1;
                                while (new_total > live_qty && index >= 0) {
                                    let item = var_tags_array[index];
                                    let item_qty = +item.qty || 0;
                                    if (item_qty < 0) item_qty = 0; // Handle negative quantities
                                    let difference = new_total - live_qty;

                                    if (item_qty > difference) {
                                        // If the current item quantity is greater than the difference, adjust it
                                        item.qty -= difference;
                                        recent_lots_sales.push({ ...item, qty: difference }); // Record the adjusted quantity
                                        new_total -= difference; // Update the new total quantity
                                        break; // We have adjusted the quantity, no need to continue
                                    } else {
                                        // If the current item quantity is less than or equal to the difference, remove the item
                                        removed_lots.push({ ...item }); // Add to removed lots
                                        new_total -= item_qty; // Update the new total quantity
                                        // Remove the item from var_tags_array
                                        var_tags_array.splice(index, 1);
                                        // The index will now point to the next newest item due to the splice
                                    }

                                    index--; // Move to the previous item
                                }

                                // Update the original var_tags object with the potentially modified var_tags_array
                                var_tags[store_number] = [...var_tags_array];
                            }

                            let varieties = [];
                            if (store_number in var_overrides) {
                                var_overrides[store_number] = override_adjust_quantities(var_overrides[store_number], live_qty);
                                if (var_overrides[store_number].length > 0) {
                                    varieties = product_varieties(var_overrides[store_number], live_qty, true);
                                } else {
                                    varieties = product_varieties(var_tags[store_number], live_qty);
                                }
                            } else {
                                varieties = product_varieties(var_tags[store_number], live_qty);
                            }

                            ret += varieties;
                        } else {
                            let varieties = [];
                            if (store_number in var_overrides) {
                                var_overrides[store_number] = override_adjust_quantities(var_overrides[store_number], live_qty);
                                if (var_overrides[store_number].length > 0) {
                                    varieties = product_varieties(var_overrides[store_number], live_qty, true);
                                } else {
                                    varieties = product_varieties([], live_qty);
                                }
                            } else {
                                varieties = product_varieties([], live_qty);
                            }
                            ret += varieties;
                        }
                        ret += '</div><!-- /store-pad -->';

                        ret += '<div class="tab-p tab-address store-pad">';
                        let display_store_number = (debug) ? 'style="display:block;"' : 'style="display:none;"';
                        ret += '    <div class="last-update-var"'+display_store_number+'>Store #<span class="store_number">' + store_number + '</span></div>';
                        ret += '    <p>';
                        ret += storeValue["Address"].Line1 + '<br/>';
                        ret += storeValue["Address"].City + ', '
                            + storeValue["Address"].RegionName + ', '
                            + storeValue["Address"].PostalCode + '<br/>';
                        ret += '    </p>';
                        ret += '    <p>' + storeValue["Address"].PhoneNumber + '</p>';

                        if (storeValue.Schedule.TodayOpeningTimes.length > 0) {
                            ret += '    <p> ' + ajax_bleuh_info.ouverture + ': ' + storeValue.Schedule.TodayOpeningTimes[0].BeginTime
                                + " - " + storeValue.Schedule.TodayOpeningTimes[0].EndTime + '</p>';
                        }
                        ret += '</div><!-- /tab-p -->';

                        ret += product_varieties_info_template();

                        if (debug) {
                            ret += '    <div class="last-update-stock">'
                                + ajax_bleuh_info.stock + ': '
                                + storeValue.InventoryStatus.LastUpdatedFormatted
                                + '     </div><!-- /last-update-stock -->';

                            ret += '    <div class="last-update-var">Lots: ';

                            let var_tags_index = storeValue["Number"];
                            let items = var_tags[var_tags_index];
                            if (typeof items === 'undefined') items = [];

                            let lots_array = [];
                            Object.values(items).forEach((item) => {
                                if (item.lot == null) item.lot = "?";
                                lots_array.push(item.lot + ' "' + item.variety_name + '" (' + item.qty + ')');
                            });

                            ret += lots_array.join(", ");
                            ret += '     </div><!-- /last-update-var -->';

                            if (recent_lots_sales.length > 0) {
                                ret += '    <div class="last-update-var">Recently sold: ';

                                lots_array = [];
                                Object.values(recent_lots_sales).forEach((item) => {
                                    if (item.lot == null) item.lot = "?";
                                    lots_array.push(item.lot + ' "' + item.variety_name + '" (' + item.qty + ')');
                                });

                                ret += lots_array.join(", ");
                                ret += '     </div><!-- /last-update-var -->';
                            }

                            if (removed_lots.length > 0) {
                                ret += '    <div class="last-update-var">Recently sold out: ';

                                lots_array = [];
                                Object.values(removed_lots).forEach((item) => {
                                    if (item.lot == null) item.lot = "?";
                                    lots_array.push(item.lot);
                                });

                                ret += lots_array.join(", ");
                                ret += '     </div><!-- /last-update-var -->';
                            }

                            if (store_number in bleuh_deliveries) {
                                ret += '    <div class="last-update-var">Deliveries: <br>';
                                bleuh_deliveries[store_number].forEach((item) => {
                                    ret += item.latest_sunday.split(' ')[0] + ' | Lot: ' + item.lot + ' | QTY: ' + item.qty + '<br>';
                                });
                                ret += '     </div><!-- /last-update-var -->';
                            }

                        }
                        ret += '</div><!-- /store-info -->';
                    }
                });
                ret += '</div><!-- /stores-list -->';

                let prepend = '';

                const caption = ajax_bleuh_info.stock_succursales.replace("?", inStockStores);

                prepend += '<div class="stores-scroll-area">';
                if ('WEB' in var_tags) {
                    let svgDispoIcon = "";
                    prepend += '<h3>' + ajax_bleuh_info.web_header + '</h3>';
                    prepend += '<div class="store-info web-store-info">';

                    prepend += '<div class="tab-p tab-address" style="display: none;">';
                    prepend += '    <span class="store_number" style="display:none">WEB</span>';
                    prepend += '</div><!-- /tab-p -->';

                    prepend += `<div class="store-pad">`;
                    if (web_qty > 0) {
                        svgDispoIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21">\n' +
                            '  <g id="Groupe_1112" data-name="Groupe 1112" transform="translate(-1015 -759.5)">\n' +
                            '    <circle id="Ellipse_101" data-name="Ellipse 101" cx="10.5" cy="10.5" r="10.5" transform="translate(1015 759.5)" fill="#fff"/>\n' +
                            '    <path id="check_FILL0_wght400_GRAD0_opsz24" d="M157.149-714.357,154-717.506l.787-.787,2.362,2.362L162.218-721l.787.787Z" transform="translate(866.46 1487.678)" fill="#1fac0d"/>\n' +
                            '  </g>\n' +
                            '</svg>';
                    } else {
                        svgDispoIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21">\n' +
                            '  <g id="Groupe_1113" data-name="Groupe 1113" transform="translate(-1015 -688)">\n' +
                            '    <circle id="Ellipse_101" data-name="Ellipse 101" cx="10.5" cy="10.5" r="10.5" transform="translate(1015 688)" fill="#fff"/>\n' +
                            '    <path id="x" d="M5.17,5.17a.548.548,0,0,1,.793,0l3,3,3-3c.057-.057.113-.113.17-.113a.481.481,0,0,1,.453,0c.057.057.113.057.17.113s.113.113.113.17a.341.341,0,0,1,.057.227.341.341,0,0,1-.057.227c-.057.057-.057.113-.113.17l-3,3,3,3a.561.561,0,1,1-.793.793l-3-3-3,3a.548.548,0,0,1-.793,0,.548.548,0,0,1,0-.793l3-3-3-3a.548.548,0,0,1,0-.793Z" transform="translate(1016.535 689.535)" fill="#ff1818" fill-rule="evenodd"/>\n' +
                            '  </g>\n' +
                            '</svg>';
                    }
                    if (debug) {
                        prepend += '    <div class="last-update-var">GTIN: ' + SKU + '</div>';
                    }
                    prepend += `<div class="store-name">SQDC - ${ajax_bleuh_info.caption_online}</div>`;
                    prepend += '    <div class="store-products">' + svgDispoIcon + '<span>' + web_qty + ' ' + ajax_bleuh_info.caption_units_available + '</span></div>';

                    if ('WEB' in var_overrides) {

                        var_overrides['WEB'] = override_adjust_quantities(var_overrides['WEB'], web_qty);
                        if (var_overrides['WEB'].length > 0) {
                            prepend += product_varieties(var_overrides['WEB'], web_qty, true);
                        } else {
                            prepend += product_varieties(var_tags['WEB'], web_qty, false, 'WEB');
                        }

                    } else {
                        prepend += product_varieties(var_tags['WEB'], web_qty, false, 'WEB');
                    }
                    prepend += `</div>`;

                    prepend += product_varieties_info_template();

                    if (debug) {
                        prepend += '    <div class="last-update-stock">'
                            + ajax_bleuh_info.stock + ': '
                            + ajax_bleuh_info.web_inventory_update
                            + '             </div><!-- /last-update-stock -->';
                        prepend += '    <div class="last-update-var">'
                            + ajax_bleuh_info.variety + ': '
                            + ajax_bleuh_info.varieties_update + '</div>';

                        prepend += '    <div class="last-update-var">Lots: '
                        // Web lots inventories
                        var_tags_index = 'WEB';
                        items = var_tags[var_tags_index];
                        if (typeof items === 'undefined') {
                            items = [];
                        } else {
                            items.sort((a, b) => new Date(b.order_weight) - new Date(a.order_weight));
                        }
                        let web_lots_array = [];
                        for (let item of items) {
                            if (item.lot == null) item.lot = "?";
                            web_lots_array.push(item.lot + ' (' + item.qty + ')');
                        }
                        prepend += web_lots_array.join(" ");
                        prepend += '    </div><!-- /last-update-var -->';
                    }
                    prepend += `</div>`;

                }

                if (inStockStores > 0) {
                    prepend += '<div class="stores-info">';
                    prepend += '    <h3> ' + caption + '</h3>';
                    prepend += '</div><!-- /stores-info -->';
                } else if (inStockStores === 0 && web_qty === 0) {
                    prepend += '    <h3> ' + ajax_bleuh_info.no_stock + '</h3>';
                }

                ret = prepend + ret;

                prepend = '<div class="inventory-right" style="display:none;">';
                prepend += '    <a class="close-inventory" href="#"><svg xmlns="http://www.w3.org/2000/svg" width="17.193" height="17.193" viewBox="0 0 17.193 17.193">\n' +
                    '  <g id="Groupe_917" data-name="Groupe 917" transform="translate(-1753.76 -433.759)">\n' +
                    '    <g id="Groupe_897" data-name="Groupe 897" transform="translate(1753.759 433.759)">\n' +
                    '      <path id="Icon_feather-chevron-down" data-name="Icon feather-chevron-down" d="M16.6,22.594a1.5,1.5,0,0,1-1.059-.439l-7.1-7.1a1.5,1.5,0,0,1,2.118-2.118l6.04,6.04,6.04-6.04a1.5,1.5,0,1,1,2.118,2.118l-7.1,7.1A1.5,1.5,0,0,1,16.6,22.594Z" transform="translate(-8 -12.5)" fill="#021760"/>\n' +
                    '      <path id="Icon_feather-chevron-down-2" data-name="Icon feather-chevron-down" d="M8.6,10.094a1.5,1.5,0,0,1-1.059-.439l-7.1-7.1A1.5,1.5,0,0,1,2.556.439L8.6,6.479l6.04-6.04a1.5,1.5,0,1,1,2.118,2.118l-7.1,7.1A1.5,1.5,0,0,1,8.6,10.094Z" transform="translate(17.193 17.193) rotate(180)" fill="#021760"/>\n' +
                    '    </g>\n' +
                    '  </g>\n' +
                    '</svg></a>';

                prepend += '    <h2> ' + ajax_bleuh_info.dispo_header_glob + '</h2>';

                if (inStockStores > 0) {
                    prepend += '    <div class="search-location">';
                    prepend += '        <input type="text" placeholder="' + ajax_bleuh_info.postal_code + '" />';
                    prepend += '        <a class="search_location" href="#"><svg xmlns="http://www.w3.org/2000/svg" width="28.677" height="28.677" viewBox="0 0 28.677 28.677">\n' +
                        '  <g id="Icon_feather-search" data-name="Icon feather-search" transform="translate(-3.5 -3.5)">\n' +
                        '    <path id="Tracé_447" data-name="Tracé 447" d="M27.5,16A11.5,11.5,0,1,1,16,4.5,11.5,11.5,0,0,1,27.5,16Z" transform="translate(0 0)" fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>\n' +
                        '    <path id="Tracé_448" data-name="Tracé 448" d="M31.5,31.5l-6.525-6.525" transform="translate(-0.738 -0.737)" fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>\n' +
                        '  </g>\n' +
                        '</svg>\n</a>';
                    prepend += '    </div>';
                    prepend += '    <a class="locate_me" href="#">' + ajax_bleuh_info.locate_me + '</a>';
                }

                if (web_qty > 0 || inStockStores > 0) {
                    webSuccImg.attr("src", ajax_bleuh_info.plugin_url + "/img/dispo.svg");
                } else {
                    webSuccImg.attr("src", ajax_bleuh_info.plugin_url + "/img/non-dispo.svg");
                }

                prepend += '    <p class="warning-head">*' + ajax_bleuh_info.var_new_warning + '</p>';

                ret = prepend + ret;

                ret += '</div><!-- /store-info -->';
                ret += '</div><!-- /stores-scroll-area -->';

                $("body .inventory-right").remove();

                $("body").prepend(ret);
                if (display === "show") {
                    $("body .inventory-right").fadeIn("fast", function() {
                        refreshSelectOptions();
                    });
                }

                $(".locate_me").click();

            } catch (e) {
                console.log(e);
            }
        });
    }

    // get current location, then sort locations by proximity
    $(document).on("click", ".locate_me", function(e) {
            e.preventDefault();
            let $input = $(".inventory-right input[type=text]");
            if (loading_screen && (typeof (Cookies.get('bleuh_address', { path: '/', domain: '.bleuh.co', sameSite: 'strict' })) !== "undefined") ) {
                $input.val(Cookies.get('bleuh_address', { path: '/', domain: '.bleuh.co', sameSite: 'strict' }));
                $input.trigger("keyup");
            }

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    let lat = position.coords.latitude;
                    let lng = position.coords.longitude;
                    sortInventories(lat, lng);
                    $.ajax({
                        url: 'https://maps.googleapis.com/maps/api/geocode/json?key=AIzaSyCW-4UHo_7yj7hBtqOpMiYmTkHACn_M6Pk&latlng='+lat+','+lng,
                        dataType: 'json',
                        success: function(data){
                            try {
                                if (loading_screen && (typeof (Cookies.get('bleuh_address', { path: '/', domain: '.bleuh.co', sameSite: 'strict' })) !== "undefined") ) {
                                    $input.val(Cookies.get('bleuh_address', { path: '/', domain: '.bleuh.co', sameSite: 'strict' }));
                                    $input.trigger("keyup");
                                }
                                if (!loading_screen || $input.val() === "")
                                    $input.val(data.results[0].formatted_address);
                            } catch (ex) {
                                $input.val(Cookies.get('bleuh_address', { path: '/', domain: '.bleuh.co', sameSite: 'strict' }));
                                if (debug) console.log(ex);
                            }
                            $("body .inventory-right").fadeIn("fast", function() {
                                refreshSelectOptions();
                            });
                            loading_screen = false;
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $input.val(Cookies.get('bleuh_address', { path: '/', domain: '.bleuh.co', sameSite: 'strict' }));
                            $input.trigger("keyup");
                            if (debug) console.log(errorThrown);
                            loading_screen = false;
                        }
                    });
                }, function (error) {
                    $input.val(Cookies.get('bleuh_address', { path: '/', domain: '.bleuh.co', sameSite: 'strict' }));
                    if (debug) console.log(error);
                });
            } else {
                $input.val(Cookies.get('bleuh_address', { path: '/', domain: '.bleuh.co', sameSite: 'strict' }));
                if (debug) console.log('no geolocation');
            }
    });

    $(document).on("keyup mouseup", ".inventory-right input[type=text]", function(e) {
        Cookies.set('bleuh_address', $(".inventory-right input[type=text]").val(), { sameSite: 'strict', domain: '.bleuh.co', path: '/'});
        $(".stores-list .store-info").each(function () {
            let store_text = $(this).text().toLowerCase();
            let input_text = $(".inventory-right input[type=text]").val().toLowerCase();
            if (store_text === "") {
                $(this).show();
            }
            if (store_text.indexOf(input_text) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        if ($(".stores-list .store-info:visible").length === 0) {
            $(".stores-list .store-info").show();
        }

        if (e.which === 13) {
            $(".stores-list .store-info").show();
            $(".search_location").click();
        }
    }).on("paste", ".inventory-right input[type=text]", function() {
        setTimeout(() => {
            $(this).trigger("keyup");
        }, 0);
    }).on("click", ".inventory-right input[type=text]", function() {
        $(this).focus().select();
    });

    // search by postal code
    $(document).on("click", ".search_location", function(e) {
        e.preventDefault();
        $.ajax({
            url: 'https://maps.googleapis.com/maps/api/geocode/json?key=AIzaSyCW-4UHo_7yj7hBtqOpMiYmTkHACn_M6Pk&address=' + $(".inventory-right input[type=text]").val(),
            dataType: 'json',
            success: function(data) {
                try {
                    $(".inventory-right input[type=text]").val(data.results[0].formatted_address);
                    ajax_data["SearchPoint"] = {"lat": data.results[0].geometry.location.lat, "lng": data.results[0].geometry.location.lng};
                    sortInventories(data.results[0].geometry.location.lat, data.results[0].geometry.location.lng);
                    $(".stores-list .store-info").show();
                } catch (ex) {
                    $(".stores-list .store-info").show();
                    $(".inventory-right input[type=text]").val(Cookies.get('bleuh_address', { path: '/', domain: '.bleuh.co', sameSite: 'strict' }));
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $(".stores-list .store-info").show();
                $(".inventory-right input[type=text]").val(Cookies.get('bleuh_address', { path: '/', domain: '.bleuh.co', sameSite: 'strict' }));
            }
        });
        $(".stores-list .store-info").show();
        return false;
    });

    $(document).on("click", ".close-inventory", function(e) {
        e.preventDefault();
        $(this).parent().fadeOut("fast");
        return false;
    });

    $(document).on("click", ".product-short-description u:last-of-type", function(e) {
        e.preventDefault();
        $(".inventory-right").fadeToggle("fast");
        $('html, body').animate({scrollTop: 0}, 'slow');
        return false;
    });

    $(document).on("click", ".product-short-description strong:last-of-type", function(e) {
        e.preventDefault();
        $(".inventory-right").fadeToggle("fast");
        $('html, body').animate({scrollTop: 0}, 'slow');
        return false;
    });

    // show inventory after page load when qty is in url
    if (document.location.hash === "#qty") {
        fetchInventories("show");
    } else {
        fetchInventories("hide");
    }

    $(document).on("click", ".var-adjust.del-link", function(e) {
        e.preventDefault();
        let $this = $(this);
        $this.parents("li").remove();
        update_admin_buttons_display();
        return false;
    });

    $(document).on("click", ".var-adjust.up-link", function(e) {
        e.preventDefault();
        let $this = $(this);
        let $li = $this.parents("li");
        $li.prev().before($li);
        update_admin_buttons_display();
        return false;
    });

    $(document).on("click", ".var-adjust.down-link", function(e) {
        e.preventDefault();
        let $this = $(this);
        let $li = $this.parents("li");
        $li.next().after($li);
        update_admin_buttons_display();
        return false;
    });

    $(document).on("click", ".admin-add-var", function(e) {
        let $this = $(this);
        $(this).parent().find("ol").append($this.parent().find("ol li").last().clone());
        $(this).parent().find("ol li:last .select2").remove()
        update_admin_buttons_display();
        refreshSelectOptions();
    });

});
