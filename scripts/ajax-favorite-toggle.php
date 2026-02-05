<?php
// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Sorry, you are not allowed to access this page directly.' );
}
//
//if (is_user_logged_in()) {
//	global $wpdb;
//	$user_id = get_current_user_id();

function bleuh_fav_toggle() {
	$post_id   = $_POST['variety_id'] ?? "";

	$action = $_POST['like_action'];
	if (!in_array($action, [ 'add-like', 'del-like' ], true)) {
		echo json_encode( [ 'status' => 'error', 'message' => 'Invalid action.' ] );
		exit();
	}

	if (!is_user_logged_in()) {
		$no_login_id = $_COOKIE['my_hash_id'] ?? '';
		if ( empty( $post_id ) || empty( $action ) || empty( $no_login_id ) ) {
			echo json_encode( [ 'status' => 'error', 'message' => 'Invalid request params.' ] );
			exit();
		}
	}

	$user_hash = md5( $_SERVER['REMOTE_ADDR'] . $no_login_id );
	if (is_user_logged_in()) {
		$user_hash = 'U'.get_current_user_id();
	}

	if ( $post_id && $action && $user_hash ) {

		global $wpdb;

		if ( $action === 'add-like') {
			$query = "INSERT IGNORE INTO {$wpdb->prefix}bleuh_favorites( hash_id, post_id )
		              VALUES (%s, %s);";
			$prepared_query = $wpdb->prepare( $query, $user_hash, $post_id );
			$executed_query = $wpdb->query( $prepared_query );
		} elseif ( $action === 'del-like' ) {
			$query = "DELETE
		              FROM {$wpdb->prefix}bleuh_favorites
		              WHERE hash_id = %s
					  AND post_id = %s;";
			$prepared_query = $wpdb->prepare( $query, $user_hash, $post_id );
			$executed_query = $wpdb->query( $prepared_query );
		} else {
			echo json_encode( [
				'status'          => 'success',
				'action'          => 'no-action'
			] );

			exit;
		}

		$query           = "SELECT COUNT(*) FROM {$wpdb->prefix}bleuh_favorites
	              			WHERE post_id = %s;";
		$prepared_query  = $wpdb->prepare( $query, $post_id );
		$db_count = (int) $wpdb->get_var( $prepared_query );
		$acf_count = get_field( 'base_likes', $post_id ) ?? 0;
		$count_sum = $acf_count + $db_count;

		if ( $executed_query !== false ) {
			echo json_encode( [
				'status'          => 'success',
				'action'          => $action,
				'post_id'         => $post_id,
				'favorites_count' => $count_sum,
				'liked' => $action === 'add-like',
			] );
		} else {
			echo json_encode( [ 'status' => 'error', 'message' => 'Database error.' ] );
		}
	} else {
		echo json_encode( [ 'status' => 'error', 'message' => 'Invalid request.' ] );
	}
	exit();
}

function get_fav_counts($post_ids = []) {
	global $wpdb;
	$query = '';

	$my_hash_id = $_COOKIE[ 'my_hash_id' ];
	$user_hash = md5( $_SERVER['REMOTE_ADDR'] . $my_hash_id );
	if (is_user_logged_in()) {
		$user_hash = 'U'.get_current_user_id();
	}

	if (!empty($post_ids)) {
		$post_ids_placeholder = implode( ',', array_fill( 0, count( $post_ids ), '%s' ) );
		$query = $wpdb->prepare(
			"SELECT post_id, COUNT(*) as count
		FROM {$wpdb->prefix}bleuh_favorites
		WHERE post_id IN ($post_ids_placeholder)
		GROUP BY post_id",
			...$post_ids
		);
	} else {
		$query = $wpdb->prepare(
			"SELECT post_id, COUNT(*) as count
		FROM {$wpdb->prefix}bleuh_favorites
		GROUP BY post_id"
		);
	}
	$results = $wpdb->get_results( $query, ARRAY_A );

	$fav_counts = [];
	foreach ( $results as $row ) {
		// get base count from ACF
		$acf_count = get_field( 'base_likes', $row['post_id'] ) ?? 0;
		$db_count =  (int) $row['count'] ?? 0;
		$count_sum = $acf_count + $db_count;
		$fav_counts[ $row['post_id'] ] = ['count' => $count_sum, 'liked' => false];
	}

	// get if liked by current user
	if (!empty($post_ids)) {
		$liked_query = $wpdb->prepare(
			"SELECT post_id
			FROM {$wpdb->prefix}bleuh_favorites
			WHERE hash_id = %s AND post_id IN ($post_ids_placeholder)",
			$user_hash,
			...$post_ids
		);
	} else {
		$liked_query = $wpdb->prepare(
			"SELECT post_id
			FROM {$wpdb->prefix}bleuh_favorites
			WHERE hash_id = %s",
			$user_hash
		);
	}

	$liked_results = $wpdb->get_col( $liked_query );
	foreach ( $liked_results as $liked_post_id ) {
		if ( isset( $fav_counts[ $liked_post_id ] ) ) {
			$fav_counts[ $liked_post_id ]['liked'] = true;
		}
	}

	// Ensure all post_ids are present in the result
	foreach ( $post_ids as $post_id ) {
		if ( ! isset( $fav_counts[ $post_id ] ) ) {
			$acf_count = get_field( 'base_likes', $post_id ) ?? 0;
			$fav_counts[ $post_id ] = ['count' => $acf_count, 'liked' => false];
		}
	}

	return $fav_counts;
}

add_action('wp_ajax_bleuh_fav_toggle', 'bleuh_fav_toggle'); // For logged in users
add_action('wp_ajax_nopriv_bleuh_fav_toggle', 'bleuh_fav_toggle'); // For non-logged in users
