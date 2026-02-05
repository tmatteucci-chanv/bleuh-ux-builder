// globals
let lang = (document.documentElement.lang.toLowerCase().includes("fr") ) ? "fr" : "en";
let debug = (window.location.search.toLowerCase().includes("debug"));
let var_tags = {};
let var_overrides = [];
let web_qty = 0;
let var_tags_totals = {};

jQuery(document).ready(function ($) {
    // add favorites button to main nav
    // desktop
    const no_cache = `${Date.now()}`;
    if (lang === 'en') {
        $("#masthead .flex-right ul.header-nav > li").first().after(`
            <li class="header-fav has-icon">
                <a href="/en/favorites/?nc=${no_cache}" class="is-small" aria-label="Favoris" role="button"><i class="icon-fav"></i></a>
            </li>        
        `);
        // mobile
        $("#masthead .flex-right ul.mobile-nav > li").first().after(`
            <li class="header-fav has-icon">
                <a href="/en/favorites/?nc=${no_cache}" class="is-small" aria-label="Favoris" role="button"><i class="icon-fav"></i></a>
            </li>        
        `);
    } else {
        $("#masthead .flex-right ul.header-nav > li").first().after(`
            <li class="header-fav has-icon">
                <a href="/favoris?nc=${no_cache}" class="is-small" aria-label="Favoris" role="button"><i class="icon-fav"></i></a>
            </li>        
        `);
        // mobile
        $("#masthead .flex-right ul.mobile-nav > li").first().after(`
            <li class="header-fav has-icon">
                <a href="/favoris?nc=${no_cache}" class="is-small" aria-label="Favoris" role="button"><i class="icon-fav"></i></a>
            </li>        
        `);
    }

    // add language selector to main nav
    if (lang === 'fr') {
        const switch_url = $("ul.nav a[hreflang='en']").attr('href') || '/en/';
        $("#masthead .flex-right ul.header-nav > li").last().before(`
        <li class="header-language-dropdown has-icon">
            <a href="#" class="is-small toggle-lang-menu" aria-label="Language Selector" role="button">FR - Québec &nbsp;
                <i class="icon-angle-down"></i>
            </a>
            <ul class="header-language-submenu" style="display: none;">
                <li><a href="${switch_url}">EN - Ontario
                </a></li>
            </ul>
        </li>
        `);
    } else {
        const switch_url = $("ul.nav a[hreflang='fr']").attr('href') || '/';
        $("#masthead .flex-right ul.header-nav > li").last().before(`
        <li class="header-language-dropdown has-icon">
            <a href="#" class="is-small toggle-lang-menu" aria-label="Language Selector" role="button">EN - Ontario &nbsp;
                <i class="icon-angle-down"></i>
            </a>
            <ul class="header-language-submenu" style="display: none;">
                <li><a href="${switch_url}">FR - Québec
                </a></li>
            </ul>
        </li>
        `);
    }

    // add js toggle for menu
    $(document).on("click", ".toggle-lang-menu", function(e) {
        $(this).next('ul').slideToggle('fast');
        return false;
    });
    $(document).on("mouseenter", ".toggle-lang-menu", function(e) {
        $(this).next('ul').slideDown('fast');
        return false;
    });

    $(document).on("mouseleave", ".header-language-submenu", function(e) {
        $(this).slideUp('fast');
        return false;
    });

});

jQuery(document).ready(function ($) {
    $(document).on("click", ".bleuh-inner-p-slides .accordion-item", function(e) {
        let $this = $(this);
        // let acc = $this.parents(".accordion-item");
        let index = $this.index();
        $(".bleuh-inner-p-slides ol.flickity-page-dots li").eq(index).click();
    });
});

jQuery(document).ready(function ($) {

    function bleuh_update_banner() {
        let banner = '';
        const params = new URLSearchParams(window.location.search);
        const b_type = params.get('filter_formats') || '';
        const prov = params.get('filter_province') || '';
        const is_qc = prov.includes('quebec');

        switch (b_type) {
            case 'fleurs':
            case 'flowers':
                banner = 'flower.jpg';
                break;
            case 'haschich':
            case 'haschich-en':
                banner = 'hash.jpg';
                break;
            case 'premoulus':
            case 'pre-grind':
                banner = 'moulu.jpg';
                break;
            case 'preroules':
            case 'pre-rolls':
                banner = 'preroll.jpg';
                break;
            case 'vapoteuses':
            case 'vape-pen':
                banner = 'vape.jpg';
                break;
            default:
                if (is_qc) {
                    banner = 'flower.jpg';
                } else {
                    banner = 'main.jpg';
                }
        }

        let b_element = $("h1.shop-page-title").parents("div.page-title-inner");
        b_element.addClass("bg-on");
        b_element.css('background-image', "url("+bleuh_info_main.plugin_url+"img/product-banners/"+banner+")");

        let count = $(".shop-container .products > .product-small:visible").length;
        last_count = count;
        // counts
        let caption = '';
        if (lang === 'fr') {
            caption = count + ' résultats affichés';
            if (count === 1) {
                caption = '1 résultat affiché';
            }
        } else {
            caption = 'Showing all ' + count + ' results';
            if (count === 1) {
                caption = 'Showing 1 result';
            }
        }
        $("body .shop-counts").text(caption);

        // setTimeout(bleuh_update_banner, 1000);
    }



    // in shop pages
    function init_and_triggers() {
        let view_single = '';
        let view_double = '';

        switch (bleuh_info_main.view_cols) {
            case "single":
                view_single= "-active";
                view_double = "";
                break;
            case "double":
                view_single = "";
                view_double = "-active";
                break;
            default:
                view_single = "-active";
                view_double = "";
        }

        const viewLabel = lang === 'en' ? 'View' : 'Vue';

        let after_banner = `
        <div class="after-banner">
          <div class="view-cols mobile-only">
            <button type="button" class="mobile-view-single${view_single ? ' active' : ''}">
              <img src="/wp-content/plugins/bleuh-ux-builder/img/icos/view-single${view_single}.svg" />
            </button>
            <button type="button" class="mobile-view-double${view_double ? ' active' : ''}">
              <img src="/wp-content/plugins/bleuh-ux-builder/img/icos/view-double${view_double}.svg" />
            </button>
            <span>${viewLabel}</span>
          </div>
          <div class="filter-buttons"></div>
          <div class="shop-counts"></div>
        </div>
        `;
        $(".shop-container").parent().prepend(after_banner);

        // $('.after-banner .filter-buttons').append($(".shop-page-title .category-filtering > a").prop('outerHTML'));
        $('.after-banner .filter-buttons').append($(".shop-page-title .category-filtering > a").clone(true));
        $(".shop-page-title .category-filtering > a").hide();
        $(".woocommerce-result-count").hide();
        $('aside.widget.woocommerce.widget_layered_nav_filters, body .shop-counts').matchHeight();

    }

    function bleuh_load_until_refresh() {
        $("body").prepend(`<div id="bleuh-page-loader">
                              <div class="spinner"></div>
                           </div>`);
        $('#bleuh-page-loader').addClass('active');
    }

    if ($("body .shop-page-title").length > 0) {
        init_and_triggers();
        bleuh_update_banner();
    }

    function afterProductsAjax() {
        bleuh_load_until_refresh();
        init_and_triggers();
        bleuh_update_banner();
        window.location.reload(true);
    }

    // --- 1. YITH Ajax Filter fires this:
    $(document).on('yith-wcan-ajax-filtered', function() {
        afterProductsAjax();
    });

    // --- 2. WooCommerce (general) overlays
    $(document).on('updated_wc_div', function() {
        afterProductsAjax();
    });

    // --- 3. Flatsome quick filter / UX builder modules sometimes use this:
    $(document).on('flatsome_ajax_loaded', function() {
        afterProductsAjax();
    });

});

// set no-login identity
jQuery(document).ready(function ($) {
    if (Cookies.get("my_hash_id") === undefined) {
        // create new cookie
        let my_hash_id = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        Cookies.set("my_hash_id", my_hash_id, {expires: 365, path: '/', domain: '.bleuh.co'});
    } else {
        // keep alive the cookie
        let my_hash_id = Cookies.get("my_hash_id");
        Cookies.set("my_hash_id", my_hash_id, {expires: 365, path: '/', domain: '.bleuh.co'});
    }
});

// age gate
jQuery(document).ready(function ($) {
    if (Cookies.get("bleuh_age_gate") === "yes") {
        $(".age-gate__wrapper").hide();
    } else {
        $(".age-gate__wrapper").show();
    }
});

/* mod lang menu */
jQuery(document).ready(function ($) {

    // image lazy loader
    $("img.lazy").lazyload({
        effect: "fadeIn" // Optional: Adds a fade-in effect
        // threshold: 200,  // Optional: Preload images 200px before they enter the viewport
    });

    let swiper_1 = new Swiper('.bleuh-ca-1', {
        pagination: {
            el: '.bleuh-ca-1 .swiper-pagination',
        },
        loop: false,
        navigation: {
            prevEl: '.bleuh-ca-1 .bleuh-prod-swiper-prev',
            nextEl: '.bleuh-ca-1 .bleuh-prod-swiper-next',
        },        // when window width is >= X (px)
        breakpoints: {
            500: {
                slidesPerView: 1,
                spaceBetween: 10
            },
            700: {
                slidesPerView: 2,
                spaceBetween: 10
            },
            1024: {
                slidesPerView: 4,
                spaceBetween: 10
            }
        }
    });

    let swiper_2 = new Swiper('.bleuh-ca-2', {
        pagination: {
            el: '.bleuh-ca-2 .swiper-pagination',
        },
        loop: false,
        navigation: {
            prevEl: '.bleuh-ca-2 .bleuh-prod-swiper-prev',
            nextEl: '.bleuh-ca-2 .bleuh-prod-swiper-next',
        },        // when window width is >= X (px)
        // when window width is >= X (px)
        breakpoints: {
            500: {
                slidesPerView: 1,
                spaceBetween: 10
            },
            700: {
                slidesPerView: 2,
                spaceBetween: 10
            },
            1024: {
                slidesPerView: 4,
                spaceBetween: 10
            }
        }
    });

    /*
    // Previous lang toggle menu
    const div = $('.header-language-dropdown a').get(0); // Get the first <div>
    if (div.firstChild.nodeType === 3) { // Ensure it's a text node
        if (div.firstChild.nodeValue.trim() === "English") {
            div.firstChild.nodeValue = 'EN - Ontario';
        } else {
            div.firstChild.nodeValue = 'FR - Québec';
        }
    }

    $('.header-language-dropdown ul li a').contents().filter(function () {
        return this.nodeType === 3; // Text node
    }).each(function () {
        if (this.nodeValue.trim() === "English") {
            this.nodeValue = 'EN - Ontario';
        } else {
            this.nodeValue = 'FR - Québec';
        }
    });

    $("#main-menu ul.nav li:first a").each(function () {
        if ($(this).text().trim() === "English") {
            $(this).text('EN - Ontario');
        } else {
            $(this).text('FR - Québec');
        }
    });
    */
});


// The Haversine formula
/* used in product page & store locator */
function haversine(lat1, lon1, lat2, lon2) {
    function toRad(x) {
        return x * Math.PI / 180;
    }

    var R = 6371; // km
    var x1 = lat2 - lat1;
    var dLat = toRad(x1);
    var x2 = lon2 - lon1;
    var dLon = toRad(x2)
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    var d = R * c;

    return d;
}

// Adjust quantities in globals
function adjust_quantities(store_number, live_qty) {
    store_number = (store_number + "").trim();
    if (store_number in var_tags) {

        // Assume `storeValue.InventoryStatus.Quantity` holds the live quantity you want to match
        live_qty = live_qty || 0;
        let var_tags_array = JSON.parse(JSON.stringify(var_tags[store_number] || []));

        // Sort the var_tags_array by date if not already sorted, oldest first
        var_tags_array.sort((a, b) => a.order_weight - b.order_weight);

        // If the `var_tags_array` is empty or `live_qty` is 0, no need to process further
        if (var_tags_array.length === 0 || live_qty === 0) {
            // Handle the scenario where no lots should remain
            // removed_lots = [...var_tags_array]; // Copy all lots to removed_lots
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
                    new_total -= difference; // Update the new total quantity
                    break; // We have adjusted the quantity, no need to continue
                } else {
                    // If the current item quantity is less than or equal to the difference, remove the item
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
    }
}

// adjusts items referenced, when no more items are available return false, else true.
function override_adjust_quantities(items, live_qty) {
    // adjust quantities if live_qty is greater than the sum of all quantities
    items = [...items];
    let qty_sum = items.reduce((sum, item) => sum + Number(item.qty), 0);
    let overflow = qty_sum - live_qty;
    if (qty_sum > live_qty) {
        items.forEach((item) => {
            let new_qty = Number(item.qty) - overflow;
            item.qty = new_qty;
            if (item.qty < 0) {
                overflow = Math.abs(item.qty);
                item.qty = 0;
            }
        });
        items = items.filter((item) => item.qty > 0);
    }
    return items;
}

function addOneMonth(date) {
    let newDate = new Date(date.getTime());
    newDate.setMonth(newDate.getMonth() + 1);
    if (newDate.getDate() < date.getDate()) {
        newDate.setDate(0);
    }
    return newDate;
}

// Store tags
/* used in product page & store locator */
function product_varieties(items_reference, live_qty, overriding = false, store_ref = null) {
    if (items_reference == null) {
        return '<div class="store-tags"></div>';
    }
    let depleted = 0;
    let bypass = false;
    let items = [...items_reference];
    let queued_tags = [];
    let tags_to_display = {};
    let qty_total = live_qty;
    let counted_qty = 0;
    // preprocess: when no items are to be displayed and we are on the product page, display web store items

    if (items.length === 0) {
        // when no items are available
        try {
            let web_items = {...var_tags.WEB};
            // get items from web store if available
            items.push({
                variety_name: web_items[0].variety_name,
                qty: live_qty,
                depleted: 0,
                lot: "",
                permalink: web_items[0].permalink,
            });
        } catch (e) {
            items.push({
                variety_name: 'Mélange',
                depleted: 0,
                qty: live_qty,
                lot: "",
                permalink: '#',
            });
        }
        bypass = true;
    }

    if (items.length > 0) {
        items.forEach((item) => {
            let normalized_variety_name = item.variety_name.replace(/[^a-zA-Z0-9]/g, '').toLowerCase();

            // Aggregate quantities
            if (tags_to_display[normalized_variety_name]) {
                tags_to_display[normalized_variety_name].qty += Number(item.qty);
            } else {
                tags_to_display[normalized_variety_name] = {
                    variety_name: item.variety_name,
                    qty: Number(item.qty),
                    lot: item.lot,
                    depleted: (depleted in item) ? item.depleted : 0,
                    normalized_variety_name: normalized_variety_name
                };
                queued_tags.push(normalized_variety_name); // Keep track of the order of unique varieties
            }

            counted_qty += Number(item.qty);
        });
    }
    queued_tags = [...queued_tags];
    if (!overriding) {
        if (store_ref !== 'WEB') {
            queued_tags.reverse();
        }
    }

    let ret = '';
    // Construct the HTML for the first two tags
    ret += '<div class="store-pad">';
    if (debug) {
        ret += `<div>Live QTY: ${live_qty}</div>
                <div>DB QTY: ${counted_qty}</div>`;
        queued_tags.forEach((tag) => {
            let this_item = tags_to_display[tag];
            ret += `<div>${this_item.variety_name} [${this_item.lot}] (${this_item.qty})</div>`;
        });
        ret += `<br>`;
    }
    ret += `<div class="store-tags">`;
    let displayed_ret = '';
    let counted_tags = 0;

    queued_tags.forEach((tag) => {
        counted_tags++;
        if (counted_tags > 2) return;
        let this_item = tags_to_display[tag];
        this_item.html = '';
        qty_total -= Number(this_item.qty); // Subtract the item quantity only once
        let qty_limited = "";
        if (this_item.qty <= 12) {
            let qty_limited_img = lang === 'fr' ? 'qty-limited-fr.svg' : 'qty-limited-en.svg';
                qty_limited += `<img class="qty-limited" src="${bleuh_info_main.plugin_url}img/${qty_limited_img}" />`;
        }
        if (counted_tags === 1) {
            qty_limited += `<span class="top-label">${bleuh_info_main.caption_varieties_in_stock}</span>`;
        } else if (counted_tags === 2) {
            qty_limited += `<span class="top-label">${bleuh_info_main.caption_varieties_to_come}</span>`;
        }
        if (bypass || this_item.variety_name.toLowerCase() === "mélange") {
            this_item.html += `<span class="variety-button"> ${qty_limited} <span class="var-name-txt">${this_item.variety_name} (${this_item.qty})</span></span>`;
        } else {
            this_item.html += `<a href="#" class="variety-button"> ${qty_limited} <span class="var-name-txt">${this_item.variety_name} (${this_item.qty})</span>
                            <span><img src="${bleuh_info_main.plugin_url}img/arrow-down.svg" /></span></a>`;
        }
        displayed_ret += this_item.html;
    });

    ret += displayed_ret;

    queued_tags = [...queued_tags];

    // Add the "Autres" category if there is remaining quantity
    if (qty_total > 0) {
        ret += `<span class="other-units">${bleuh_info_main.caption_other_units}:<br><strong>${qty_total}</strong></span>`;
    }

    ret += '</div><!-- /store-tags -->';
    // admin options
    if (bleuh_info_main.can_manage_tags && debug) {
        ret += `<div class="override-admin-wrapper" data-gtin="${ajax_bleuh_info.SKU}"> <br>`;
        if (overriding) {
            ret += `<h3 style="background: red;">[OVERRIDE ACTIF]</h3>`;
        }
        ret += `<h3>Ajustements (Override temporaire)</h3>`;
        ret += `<ol>`;
        let ii = 0;
        queued_tags.forEach((tag) => {
            let this_item = tags_to_display[tag];
            if (this_item.depleted > 0) depleted = this_item.depleted;
            ret += `<li>
                        <select class="select2-admin" style="width:100%;">`;

            let items_tmp = Object.values(var_tags).flatMap(group => Object.values(group));

            // Now `items` is an array of item objects.
            let unique_lots = Array.from(new Set(items_tmp.map(item => item.lot)));

            // If you want to filter out unique items based on their lots being different
            let unique_items = items_tmp.filter((item, index, self) =>
                self.findIndex(t => t.lot === item.lot) === index
            );

            let optionsHtml = unique_items.map((item) => {
                let this_option = item.lot === this_item.lot ? 'selected' : '';
                return `<option value="${item.lot}" ${this_option}>${item.lot}: ${item.variety_name}</option>`;
            }).join('');

            // add master lot varieties
            let masterOptions = bleuh_info_main.admin_varieties.map((item) => {
                return `<option value="${item.lot}">${item.lot}: ${item.variety_name}</option>`;
            }).join('');

            ret += `<option value="Mélange">Mélange (Lot Inconnu)</option>` + optionsHtml + masterOptions;

            ret += `</select>
                    <input type="number" value="${this_item.qty}" />`;

            if (ii > 0) ret+=  `<a href="#" class="var-adjust up-link">^ Up</a>`;
            else  ret+=  `<a href="#" class="var-adjust up-link" style="display:none;">^ Up</a>`;
            if (ii < queued_tags.length -1) ret+= `<a href="#" class="var-adjust down-link">v Down</a>`;
            else ret+= `<a href="#" class="var-adjust down-link" style="display:none;">v Down</a>`;
            ret+= `<a href="#" class="var-adjust del-link">x Effacer</a>
                        </li>`;
            ii++;
        });
        ret += `</ol>`;
        ret += `<input class="admin-add-var" type="button" value="Ajouter une variété">`;
        let currentDate = new Date();
        let monthlater = addOneMonth(currentDate);
        let date_default = monthlater.toISOString().split('T')[0];
        ret += `<p style="display:none;"><label><input type="checkbox"> Override until date: <input type="date" value="${date_default}"></label></p>`;
        ret += `<p><label><input type="checkbox" checked disabled> Override until this amount of units are sold: <input type="number" value="12"></label></p>`;
        ret += `<a href="#" class="var-save-new-changes">Enregistrer les modifications (Override)</a>`;
        ret += `</div>`;

    }
    ret += '</div><!-- /store-pad -->';
    return ret;
}

/* used in product page & store locator */
function product_varieties_info_template() {
    let close_button = '';
    let caption = '';
    if (jQuery(".store-locator-page").length === 0) {
        close_button = 'close.svg';
    } else {
        close_button = 'close-white.svg';
    }
    return `<div class="tab-p tab-info" style="display:none;">
                <div class="store-pad">
                    <a class="tab-info-close" href="#"><img src="${bleuh_info_main.plugin_url}img/${close_button}" /></a>
                    <a class="var-button" href="#"></a>
                    <div class="info-style"><span class="units">0</span> ${bleuh_info_main.caption_units} <span class="store-caption"></span></div>
                    <div class="info-style">${bleuh_info_main.caption_lot}: <span class="lot"></span></div>
                    <div class="info-style">${bleuh_info_main.caption_date_wrap}: <span class="date"></span></div>
                </div>
            </div>`;
}

/* used in store locator */
function bleuh_product_template() {
    return `<div class="product-box col-lg-4 %ADD_CLASS%">
                <div class="store-pad">
                    <div class="row">
                        <h2 class="col-lg-6">%NAME%</h2>
                        <h3 class="col-lg-6">%BLEND%</h3>
                    </div>
            
                    <p class="row format">
                        <span class="col-lg-6">%FORMAT%</span>
                        <span class="col-lg-6">%WEIGHT%</span>
                    </p>
                </div>
                <div class="store-pad qty-container">
                    <a href="%BUY_LINK%" target="_blank">
                        %AVAILABILITY%
                        <span class="link-caption"><span class="qty">%STORE_QTY%</span>
                        ${bleuh_info_main.caption_units_available}</span>
                    </a>
                </div>
                <div class="store-info">
                    <div style="display: none;" class="store_number">%STORE_NUMBER%</div>
                    %VARIETIES%
                </div>
            </div>`;
}


jQuery(document).ready(function ($) {

    // home page
    if ($("body.home").length > 0) {
        // $("main #content > div .col").matchHeight();
        $("main #content > div .col .col-inner").matchHeight();
        $("main #content > div .col-inner .banner,main #content > div .col-inner .slider-wrapper").matchHeight();
        $("main #content > div .col-inner .banner.is-full-height").removeClass("is-full-height");
        $(".rw-wrapper,.fader-slide,main #content > div .col-inner .banner").matchHeight();
        $(".slider-wrapper,.banner-inner.fill").matchHeight();
        $('.vape-banner-b > div, .bleuh-slider-carts .img-inner .img').matchHeight();
    }

    // $("body .product-small.box .box-text").matchHeight();
    $("body .swiper-wrapper .swiper-slide.more-see-all").matchHeight();
    $("body .product-small.box span.custom-attribute:first-of-type, body .product-small.box p.product-title").matchHeight();
    $("body .product-small.box p.category, body .product-small.box span.custom-attribute:last-of-type").matchHeight();

    if ($("body .related-vars .swiper-slide").length === 0) {
        $("body .portfolio-bottom .portfolio-related").attr("style", 'display: block !important;');
    }

    function colorize_strain_types() {
        $(".product-small .title-wrapper p.category, .related-vars .ux-slide p.category, .swiper-wrapper .swiper-slide p.category, .var-box-link p.category,.varieties_page_container .shop-container .product-small p.category").each(function() {
            let $this = $(this);
            if ($this.attr("data-color")) {
                return true;
            }
            let strain_type = $.trim($this.text()).toLowerCase();

            if (strain_type.includes('indica')) {
                $this.css('color', '#F095CD');
                $this.attr("data-color", 'is_set');
                return;
            }
            if (strain_type.includes('sativa')) {
                $this.css('color', '#FFD103');
                $this.attr("data-color", 'is_set');
                return;
            }

            // hybrid
            $this.attr("data-color", 'is_set');
            $this.css('color', '#FF8300');
        });
    }

    // Trigger a script when an AJAX request is complete
    setInterval(colorize_strain_types, 1000);
    colorize_strain_types();

    // varieties page
    $(document).on("click", "span.like-box", function(e) {
        let $this = $(this);
        e.preventDefault();
        e.stopPropagation();

        if ($this.find('div.spinner').length > 0) {
            // If a spinner is already present, do not proceed
            return false;
        }

        // set loading spinner
        $this.find('span').hide();
        $this.find('i').hide();
        $this.append(`<div class="spinner"></div>`);

        let action = "add-like";
        if ($(this).hasClass('liked')) {
            action = "del-like";
        }
        $.ajax({
            url: bleuh_info_main.ajax_url,
            type: 'POST',
            data: {
                action: 'bleuh_fav_toggle',
                like_action: action,
                variety_id: $this.attr('data-id'),
                no_login_id: Cookies.get("my_hash_id") || ''
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    if (response.action === 'no-action') {
                        $this.find('span').show();
                        $this.find('i').show();
                        $this.find(`.spinner`).remove();
                        return false;
                    }
                    let post_id = response.post_id;
                    let likesCount = response.favorites_count;
                    let liked = response.liked;
                    $(`span.like-box[data-id="${post_id}"] .counter`).text(likesCount);
                    if (liked) {
                        $(`span.like-box[data-id="${post_id}"]`).addClass('liked');
                        $(`span.like-box[data-id="${post_id}"]`).removeClass('not-liked');
                    } else {
                        $(`span.like-box[data-id="${post_id}"]`).removeClass('liked');
                        $(`span.like-box[data-id="${post_id}"]`).addClass('not-liked');
                    }
                } else {
                    console.error('Error updating likes:', response.data);
                    alert('Error updating likes. Please try again later.');
                }
                $this.find('span').show();
                $this.find('i').show();
                $this.find(`.spinner`).remove();
            }
        });
        return false;
    });

    // Prevent the parent <a> from being triggered when clicking the span
    $(document).on("click", "a.var-box-link", function (e) {
        let $this = $(this);
        if ($this.attr("href") === "#") {
            e.preventDefault(); // Prevent default action of the <a> tag
            e.stopPropagation(); // Stop bubbling to parent
            window.location = $this.attr("data-href");
        }
        return true;
    });

    // product page & store page
    $(document).on("click", "a.variety-button", function(e) {
        let $this = $(this);
        let img_button = $(this).find("span img");
        e.preventDefault();
        let store_number = $(this).parents(".store-info").find(".store_number").text().trim();
        let text = $(this).find(".var-name-txt").text();
        let variety_name = text.substring(0, text.lastIndexOf("(")).trim();
        let items = [];
        if (store_number in var_overrides) {
            items = var_overrides[store_number];
        } else {
            items = var_tags[store_number];
        }
        if (typeof items === 'undefined') items = [];

        img_button.parents(".store-info").find("span img").removeClass("toclose");
        $(this).parents(".store-info").removeClass("active-bottom-tab");
        let tab = $(this).parents(".store-info").find(".tab-p.tab-info");
        if (tab.is(":visible") && tab.find(".var-button").text() === variety_name) {
            tab.slideUp("fast");
            tab.parents(".store-info").find(".tab-address").slideDown("fast");
            return false;
        }
        tab.slideUp("fast", function() {
            tab.find(".units").text("");
            tab.find(".lot").text("");
            tab.find(".date").text("");
            tab.find(".var-button").text(variety_name);
            let breaker = false;
            let had_first_match = false;
            items.forEach((item, index) => {
                if (breaker) return;
                if (item.variety_name.replace(/[^a-zA-Z0-9]/g, '').toLowerCase() === variety_name.replace(/[^a-zA-Z0-9]/g, '').toLowerCase()) {
                    let prev_units = tab.find(".units").text();
                    if (prev_units === "") prev_units = 0;
                    tab.find(".units").text(Number(prev_units) + Number(item.qty));
                    let prev_lots = tab.find(".lot").text();
                    if (prev_lots !== "") prev_lots += ", ";
                    tab.find(".lot").text(prev_lots + item.lot);
                    if (tab.find(".lot").text() === "null") tab.find(".lot").parent().hide();
                    tab.find(".date").text(item.wrap_date);
                    if (tab.find(".date").text() === "") tab.find(".date").parent().hide();
                    let wrap_date = new Date(item.wrap_date);
                    if (wrap_date.getFullYear() < 2000) tab.find(".date").parent().hide();
                    tab.find(".var-button").attr("href", item.permalink);
                    if (tab.find(".var-button").attr("href") === "#")
                        tab.find(".var-button")
                            .replaceWith(`<span class="var-button">${$(this).find(".var-button").text()}</span>`);
                    had_first_match = true;
                } else {
                    if (had_first_match) {
                        breaker = true;
                    }
                }
            });

            let caption = '';
            let first_item = ( $this.is($this.parents(".store-tags").find(".variety-button").first()) );
            if (first_item) {
                caption = bleuh_info_main.caption_var_in_store;
            } else {
                caption = bleuh_info_main.caption_var_in_backstore;
            }
            tab.find("span.store-caption").text(caption);

            tab.parents(".store-info").find(".tab-address").slideUp("fast");
            tab.parents(".store-info").addClass("active-bottom-tab");
            $("#selected-store .tab-info").matchHeight();
            tab.slideDown("fast");
            img_button.addClass("toclose");
        });
        return false;
    });

    // product page & store page
    $(document).on("click", ".tab-info-close", function(e) {
        e.preventDefault();
        $(this).parents(".store-info").removeClass("active-bottom-tab");
        $(this).parents(".tab-p").slideUp("fast");
        $(this).parents(".store-info").find(".tab-address").slideDown("fast");
        $(this).parents(".store-info").find("span img").removeClass("toclose");
        return false;
    });

    $(document).on("click", ".var-save-new-changes", function(e) {
        if (ajax_bleuh_info.can_manage_tags) {
            try {
                e.preventDefault();
                let $this = $(this);
                let $store_tags = $this.parents(".override-admin-wrapper").first();
                let $store_info = $store_tags.parents(".store-info").first();
                let store_number = $store_info.find(".store_number").text().trim();
                let $ol = $store_tags.find("ol");
                let $date = $store_tags.find("input[type='date']");
                let $checkboxes = $store_tags.find("input[type='checkbox']");
                let override_until_date = ($checkboxes.eq(0).is(":checked")) ? $date.val() : null;
                let override_until_qty_sold = $store_info.find("p label input[type='number']").val();
                let live_qty = parseInt($store_info.find(".store-products").text().trim().split(" ")[0], 10);
                let items = [];
                let GTIN = ajax_bleuh_info.SKU[0];
                $ol.find("li").each(function () {
                    let $this_li = $(this);
                    let $select = $this_li.find("select");
                    let $input = $this_li.find("input[type='number']");
                    let lot = $select.val();
                    let qty = $input.val();
                    items.push({lot, qty});
                });
                let post_data = {
                    action: "bleuh_override_var_tags",
                    store_number: store_number,
                    items: items,
                    override_until_date: override_until_date,
                    override_until_qty_sold: override_until_qty_sold,
                    live_qty: live_qty,
                    GTIN: GTIN
                };
                $.post(bleuh_info_main.ajax_url, post_data, function (data) {
                    if (data.trim() === 'success') {
                        alert("Override saved");
                        window.location.reload(true);
                    } else {
                        console.log(data);
                        alert("Error saving override");
                    }
                }).fail(function (jqXHR, textStatus, errorThrown) {
                    console.error("Request failed: " + textStatus + ", " + errorThrown);
                    alert("Error saving override");
                });
            } catch (e) {
                console.error(e);
                alert("Error saving override");
            }
        } else {
            console.error(e);
            alert("Error saving override");
        }
        return false;
    });

    $(document).on("click", ".age-gate__submit--yes", function(e) {
        e.preventDefault();
        $(".age-gate__errors").remove();
        let $age_gate = $(this).parents(".age-gate__wrapper");
        let $body = $("body");
        Cookies.set("bleuh_age_gate", "yes", {expires: 365, path: '/', domain: '.bleuh.co'});
        $age_gate.fadeOut("fast", function() {
            $age_gate.remove();
            $body.removeClass("age-gate-active");
        });
        return false;
    });

    $(document).on("click", ".age-gate__submit--no", function(e) {
        e.preventDefault();
        Cookies.set("bleuh_age_gate", "no", {expires: 365, path: '/', domain: '.bleuh.co'});
        $(".age-gate__errors").fadeIn("fast");
        return false;
    });

    $(document).on("click", "#btn-copy-html-to-clip", function(e) {
        const htmlContent = $('#clipboard-container').get(0).outerHTML;

        navigator.clipboard.writeText(htmlContent)
            .then(() => {
                console.log('HTML copied to clipboard successfully!');
                alert('HTML copied to clipboard successfully!');
            })
            .catch(err => {
                console.error('Failed to copy HTML: ', err);
            });
    });

    function set_view_type(v_type) {
        if (v_type === 'double') {
            $(".mobile-view-single").removeClass("active");
            $(".mobile-view-single img").attr("src", bleuh_info_main.plugin_url + "img/icos/view-single.svg");
            $(".mobile-view-double").addClass("active");
            $(".mobile-view-double img").attr("src", bleuh_info_main.plugin_url + "img/icos/view-double-active.svg");

            Cookies.set("bleuh_mobile_view_type", "double", {expires: 365, path: '/', domain: '.bleuh.co'});

            $(".varieties_page_container .product-small.box,.shop-container .products > .product-small").each(function () {
                if ($(this).is(":visible")) {
                    $(this).attr(
                        "style",
                        "max-width: 50% !important; width: 50% !important; display: block;"
                    );
                } else {
                    $(this).attr(
                        "style",
                        "max-width: 50% !important; width: 50% !important; display: none;"
                    );
                }
            });

            if ($(".section-content.fav-page-container").length > 0) {
                $(".varieties_page_container .products > .product-small,.varieties_page_container .products > .store-box").each(function () {
                    $(this).attr(
                        "style",
                        "max-width: 50% !important; width: 50% !important; display: block;"
                    );
                });
                $(".varieties_page_container .products > .product-small .product-small.box").each(function () {
                    $(this).attr(
                        "style",
                        "max-width: 100% !important; width: 100% !important; display: block;"
                    );
                });
            }

        } else {
            $(".mobile-view-single").addClass("active");
            $(".mobile-view-single img").attr("src", bleuh_info_main.plugin_url + "img/icos/view-single-active.svg");
            $(".mobile-view-double").removeClass("active");
            $(".mobile-view-double img").attr("src", bleuh_info_main.plugin_url + "img/icos/view-double.svg");

            Cookies.set("bleuh_mobile_view_type", "single", {expires: 365, path: '/', domain: '.bleuh.co'});

            $(".varieties_page_container .product-small.box,.shop-container .products > .product-small").each(function () {
                $(this).attr(
                    "style",
                    "max-width: 100% !important; width: 100% !important; display: block;"
                );
            });

            if ($(".section-content.fav-page-container").length > 0) {
                $(".varieties_page_container .products > .product-small,.varieties_page_container .products > .store-box").each(function () {
                    $(this).attr(
                        "style",
                        "max-width: 100% !important; width: 100% !important; display: block;"
                    );
                });
                $(".varieties_page_container .products > .product-small .product-small.box").each(function () {
                    $(this).attr(
                        "style",
                        "max-width: 100% !important; width: 100% !important; display: block;"
                    );
                });
            }
        }
        $('.shop-container .products > .product-small p.product-title').css('height', 'auto');

    }

    $(document).on("click", '.actions-right.mobile-only button,.view-cols button',  function (e) {
        let $this = $(this);
        e.preventDefault();
        if (($this.hasClass("mobile-view-single"))) {
            set_view_type('single');
        } else {
            set_view_type('double');
        }
    });

    if ($(document).width() <= 849) {
        let view_type = Cookies.get('bleuh_mobile_view_type', { path: '/', domain: '.bleuh.co', sameSite: 'strict' })
        if (view_type === 'double') {
            set_view_type('double');
        } else {
            set_view_type('single');
        }
    }

});
