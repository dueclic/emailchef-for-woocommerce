<?php
/**
 * Run against an isolated WordPress installation with WooCommerce active:
 * wp eval-file wp-content/plugins/emailchef-for-woocommerce/tests/order-pagination.php
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! function_exists( 'wc_create_order' ) ) {
	throw new RuntimeException( 'Run this test using WP-CLI with WooCommerce active.' );
}
add_filter( 'pre_wp_mail', '__return_true' );
add_filter( 'pre_http_request', function ( $response, $args, $url ) {
	if ( strpos( $url, '/apps/api/v1/' ) !== false ) {
		return new WP_Error( 'test_blocked', 'Emailchef API calls are disabled in this test.' );
	}
	return $response;
}, 10, 3 );
$user_id = wp_insert_user( array( 'user_login' => 'ec-test-' . wp_generate_password( 12, false ), 'user_pass' => wp_generate_password(), 'role' => 'customer' ) );
if ( is_wp_error( $user_id ) ) {
	throw new RuntimeException( $user_id->get_error_message() );
}
$product_ids = array();
$order_ids = array();
try {
	foreach ( array( 10, 7 ) as $price ) {
		$product = new WC_Product_Simple();
		$product->set_name( 'Emailchef pagination test' );
		$product->set_regular_price( $price );
		$product_ids[] = $product->save();
	}
	for ( $i = 0; $i < 52; $i++ ) {
		$order = wc_create_order( array( 'customer_id' => $user_id ) );
		$order_ids[] = $order->get_id();
		$order->add_product( wc_get_product( $product_ids[ $i === 51 ? 1 : 0 ] ), 1 );
		if ( $i === 51 ) {
			$order->set_date_created( strtotime( '-60 days' ) );
		}
		$order->calculate_totals();
		$order->update_status( 'completed' );
	}
	$products = array_map( 'intval', explode( ', ', wc_ec_get_all_products( $user_id ) ) );
	$total = wc_ec_get_total_by_days( $user_id, 30 );
	if ( count( $products ) !== 2 || array_diff( $product_ids, $products ) || (float) $total !== 510.0 ) {
		throw new RuntimeException( 'Order pagination or date filtering returned incorrect results.' );
	}
	WP_CLI::success( 'Order pagination and 30-day totals are correct across 52 orders.' );
} finally {
	foreach ( $order_ids as $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			$order->delete( true );
		}
	}
	foreach ( $product_ids as $product_id ) {
		wp_delete_post( $product_id, true );
	}
	require_once ABSPATH . 'wp-admin/includes/user.php';
	wp_delete_user( $user_id );
}
