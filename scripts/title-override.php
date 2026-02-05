<?php

// $type = 'h1', 'meta' or 'title'
function bleuh_title_override($type = '') {
	$skip_default = false;
	$default_title = '';

	$ret_to_join = [];

	if ($type == 'h1') {
		if (ICL_LANGUAGE_CODE == 'fr') {
			$default_title = "Nos produits";
		} else {
			$default_title = "Our Products";
		}
	} else {
		if (ICL_LANGUAGE_CODE == 'fr') {
			$default_title = "Découvrez nos produits de cannabis et leurs variétés en rotation.";
		} else {
			$default_title = "Discover our cannabis products and their rotating strains.";
		}

	}

	$ret = '';
	if ($type == 'h1') {
		if (ICL_LANGUAGE_CODE == 'fr') {
			$ret .= "Nos produits";
		} else {
			$ret .= "Our Products";
		}
	}

	$ret .= "<span>";

	if (isset($_GET["product_tag"])) {
		$skip_default = true;
		$get_var = trim( strtolower( $_GET["product_tag"] ) );
		if (strpos( $get_var, 'nouveautes' ) !== false ) {
			if (ICL_LANGUAGE_CODE == 'fr') {
				$ret_to_join[] = 'nouveaux';
			} else {
				$ret_to_join[] = 'new arrivals';
			}
		}
		if ( (strpos($get_var, 'meilleur') !== false) || (strpos($get_var, 'best') !== false) ) {
			if (ICL_LANGUAGE_CODE == 'fr') {
				$ret_to_join[] = 'meilleurs vendeurs';
			} else {
				$ret_to_join[] = 'best sellers';
			}
		}
	}

	if (isset($_GET["filter_province"])) {
		$skip_default = true;
		$sub_ret = '';
		if (strpos($_GET["filter_province"], 'quebec') !== false) {
			if ( ICL_LANGUAGE_CODE == 'fr' ) {
				$sub_ret = ' au Québec';
			} else {
				$sub_ret = ' in Quebec';
			}
		}

		if (strpos($_GET["filter_province"], 'ont') !== false) {
			if (!empty($sub_ret)) {
				if (ICL_LANGUAGE_CODE == 'fr') {
					$sub_ret .= ' et ';
				} else {
					$sub_ret .= ' or ';
				}
			}
			if (ICL_LANGUAGE_CODE == 'fr') {
				$sub_ret .= ' en Ontario';
			} else {
				$sub_ret .= ' in Ontario';
			}
		}
		$ret_to_join[] = $sub_ret;
	}

	if (isset($_GET["filter_marques"])) {
		$skip_default = true;
		$brand = 'bleuh';
		$get_var = trim(strtolower($_GET["filter_marques"]));
		$sub_list = [];
		if (strpos($get_var, 'bleuh') !== false) {
			$sub_list[] = 'bleuh';
		}
		if (strpos($get_var, 'blakh') !== false) {
			$sub_list[] = 'blakh';
		}
		if (strpos($get_var, 'blanh') !== false) {
			$sub_list[] = 'blanh';
		}
		if (strpos($get_var, 'bleuh-leger') !== false) {
			if (ICL_LANGUAGE_CODE == 'fr') {
				$sub_list[] = 'bleuh léger';
			} else {
				$sub_list[] = 'bleuh light';
			}
		}
		if (strpos($get_var, 'goldh') !== false) {
			$sub_list[] = 'goldh';
		}
		if (strpos($get_var, 'grindh') !== false) {
			$sub_list[] = 'grindh';
		}
		if (count($sub_list) > 0) {
			if (ICL_LANGUAGE_CODE == 'fr') {
				$brands = implode(' ou ', $sub_list);
				$ret_to_join[] = ' de la marque ' . $brands;
			} else {
				$brands = implode(' or ', $sub_list);
				$ret_to_join[] = ' of the ' . $brands . ' brand';
			}
		}
	}

	if (isset($_GET["filter_formats"])) {
		$skip_default = true;
		$get_var = trim(strtolower($_GET["filter_formats"]));
		$sub_list = [];
		if ( (strpos($get_var, 'fleurs') !== false) || (strpos($get_var, 'flower') !== false) ) {
			if (ICL_LANGUAGE_CODE == 'fr') {
				$sub_list[] = 'fleurs séchées';
			} else {
				$sub_list[] = 'dried flowers';
			}
		}
		if (strpos($get_var, 'hasch') !== false) {
			$sub_list[] = 'haschich';
		}
		if ( (strpos($get_var, 'premoulus') !== false) || (strpos($get_var, 'pre-grind') !== false) ) {
			if (ICL_LANGUAGE_CODE == 'fr') {
				$sub_list[] = 'prémoulus';
			} else {
				$sub_list[] = 'pre-grind';
			}
		}
		if ( (strpos($get_var, 'preroules') !== false) || (strpos($get_var, 'pre-rolls') !== false) ) {
			if (ICL_LANGUAGE_CODE == 'fr') {
				$sub_list[] = 'préroulés';
			} else {
				$sub_list[] = 'pre-rolls';
			}
		}
		if ( (strpos($get_var, 'vap') !== false)) {
			if (ICL_LANGUAGE_CODE == 'fr') {
				$sub_list[] = 'vapoteuses';
			} else {
				$sub_list[] = 'vape pen';
			}
		}
		if (!empty($sub_list)) {
			if (ICL_LANGUAGE_CODE == 'fr') {
				$ret_to_join[] = implode(' ou ', $sub_list);
			} else {
				$ret_to_join[] = implode(' or ', $sub_list);
			}
		}
	}

	if (isset($_GET["product_cat"])) {
		$skip_default = true;
		$get_var = trim(strtolower($_GET["product_cat"]));
		$sub_list = [];
		if (strpos($get_var, 'hybrid') !== false) {
			if (ICL_LANGUAGE_CODE == 'fr') {
				$sub_list[] = 'hybride';
			} else {
				$sub_list[] = 'hybrid';
			}
		}
		if (strpos($get_var, 'indica') !== false) {
			$sub_list[] = 'indica';
		}
		if (strpos($get_var, 'sativa') !== false) {
			$sub_list[] = 'sativa';
		}

		if (ICL_LANGUAGE_CODE == 'fr') {
			$ret_to_join[] = implode(' ou ', $sub_list);
		} else {
			$ret_to_join[] = implode(' or ', $sub_list);
		}
	}

	$ret .= (implode(', ', $ret_to_join));

	$ret .= '</span>';

	if (!$skip_default) {
		return $default_title;
	}

	if (ICL_LANGUAGE_CODE == 'fr') {
		$prefix = "Nos produits ";
	} else {
		$prefix = "Our Products ";
	}

	if ($type == 'h1') {
		return $ret;
	} elseif ($type == 'meta-description') {
		return $prefix. strip_tags($ret);
	} elseif ($type == 'document-title') {
		return $prefix. strip_tags($ret) . ' | Bleuh';
	}

	return $default_title;
}

// SEO h1
add_filter('woocommerce_page_title', function ($page_title) {
	if (is_shop()) {
		// Check the language and return the appropriate title
		return bleuh_title_override('h1');
	}
	return $page_title;
});

// SEO page title
function bleuh_force_shop_page_title( $title ) {
	if ( is_shop() ) {
		return bleuh_title_override('document-title');
	}

	return $title; // For all other pages, return the default title
}
add_filter( 'pre_get_document_title', 'bleuh_force_shop_page_title', 999 );

// SEO meta description
function bleuh_force_shop_page_description( $description ) {
	if ( is_shop() ) {
		return bleuh_title_override('meta-description');
	}
	return $description; // For all other pages, return the default description
}
add_filter( 'wpseo_metadesc', 'bleuh_force_shop_page_description', 20 );

add_filter( 'wpseo_robots_array', function( $robots ) {
    if (isset($_GET['yith_wcan'])) {
		$robots = [ 'index' => false, 'follow' => false ];
	}
	return $robots;
}, PHP_INT_MAX );

add_filter( 'wpseo_canonical', function( $canonical ) {
	if ( is_shop() ) {

		// Build canonical from the current request
		$scheme = is_ssl() ? 'https://' : 'http://';
		$host   = $_SERVER['HTTP_HOST'];
		$uri    = $_SERVER['REQUEST_URI']; // includes query string

		$canonical = esc_url( $scheme . $host . $uri );
	}
	return $canonical;
}, PHP_INT_MAX );

add_action( 'wp_head', 'bleuh_prevent_indexing_orderby' );
function bleuh_prevent_indexing_orderby () {
	if (isset($_GET['yith_wcan'])){
		echo '<meta name="robots" content="noindex, nofollow">';
	}
}

