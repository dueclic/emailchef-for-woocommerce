<?php
/*
* Plugin Name: eMailChef for WooCommerce
* Plugin Uri: http://emailchef.com/email-marketing-woocommerce-emailchef/
* Description: Using this WooCommerce plugin, eMailChef can communicate with your online store and it creates easy, simply and automatic targeted campaigns.
* Author: dueclic
* Author URI: https://www.dueclic.com
* Version: 3.7
* Tested up: 6.0
* WC requires at least: 5.0.0
* WC tested up to: 6.9.0
* Text Domain: emailchef-for-woocommerce
* Domain Path: /languages/
* License: GPL v3
*/

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 *
 * Full path to the WooCommerce EmailChef File
 *
 */

define( 'WC_EMAILCHEF_FILE', __FILE__ );

/**
 *
 * The main plugin class
 *
 */

require_once('includes/class-wc-emailchef-plugin.php');

function wc_ec_get_total_by_days( $customer_id = null, $gap_days = 0 ) {

    if ($gap_days == 0)
        return wc_get_customer_total_spent($customer_id);

    global $wpdb;

    $spent = $wpdb->get_var( "SELECT SUM(meta2.meta_value)
        FROM $wpdb->posts as posts

        LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
        LEFT JOIN {$wpdb->postmeta} AS meta2 ON posts.ID = meta2.post_id

        WHERE   meta.meta_key       = '_customer_user'
        AND     meta.meta_value     = $customer_id
        AND     posts.post_type     IN ('" . implode( "','", wc_get_order_types( 'reports' ) ) . "')
        AND     posts.post_status   IN ( 'wc-completed', 'wc-processing' )
        AND     meta2.meta_key      = '_order_total'
        AND posts.post_date BETWEEN NOW() - INTERVAL ".$gap_days." DAY AND NOW()
    " );

    return $spent;
}

/**
 * @param $customer_id
 *
 * @return string
 *
 *
 */

function wc_ec_get_all_products( $customer_id, $no_order = - 1 ) {

	$products = array();

	if ( empty( $customer_id ) ) {
		$customer_id = get_current_user_id();
	}

	$customer_orders = array(wc_get_customer_last_order( $customer_id ));

	if ( $no_order == - 1 ) {

		$args = array(
			// WC orders post type
			'post_type'   => 'shop_order',
			// Only orders with status "completed" (others common status: 'wc-on-hold' or 'wc-processing')
			'post_status' => array_keys(wc_get_order_statuses()) ,
			// all posts
			'numberposts' => $no_order,
			// for current user id
			'meta_key'    => '_customer_user',
			'meta_value'  => $customer_id
		);
		// Get all customer orders
		$customer_orders = get_posts( $args );

	}

	if ( ! empty( $customer_orders ) ) {

		foreach ( $customer_orders as $customer_order ) {
			$order = new WC_Order( $customer_order->ID );
			foreach ( $order->get_items() as $key => $order_item ) {

				$order_item_id = $order_item['product_id'];
				if ( ! in_array( $order_item_id, $products ) ) {
					$products[] = $order_item_id;
				}

			}
		}

		return implode( ", ", $products );

	} else {
		return "";
	}

}

function wc_ec_get_customer_last_order( $customer_id ) {
	global $wpdb;

	$customer_id = absint( $customer_id );

	$id = $wpdb->get_var( "SELECT id
		FROM $wpdb->posts AS posts
		LEFT JOIN {$wpdb->postmeta} AS meta on posts.ID = meta.post_id
		WHERE meta.meta_key = '_customer_user'
		AND   meta.meta_value = {$customer_id}
		AND   posts.post_type = 'shop_order'
		AND   posts.post_status IN ( 'wc-completed' )
		ORDER BY posts.ID DESC
	" );

	return wc_get_order( $id );
}

function wc_ec_get_order_status_name($status) {
    $order_statuses = array(
        'wc-pending'    => 'Pending Payment',
        'wc-processing' => 'Processing',
        'wc-on-hold'    => 'On Hold',
        'wc-completed'  => 'Completed',
        'wc-cancelled'  => 'Cancelled',
        'wc-refunded'   => 'Refunded',
        'wc-failed'     => 'Failed',
    );
    return isset($order_statuses[$status]) ? $order_statuses[$status] : $status;
}

function wc_ec_last_active($user_id){
    return get_user_meta($user_id, 'wc_last_active', true);
}

function wc_ec_get_user_registration($user_id){
    $userdata = get_userdata($user_id);
    $date = date("Y-m-d", strtotime($userdata->user_registered));
    if ($date == '' || $date == null){
        return wc_ec_last_active($user_id);
    }
    return $date;
}

/**
 * @param $order WC_Order
 * @return mixed|string
 */

function wc_ec_get_user_registration_byorder($order){

    $user = $order->get_user();
    if ($user !== false){
       return wc_ec_get_user_registration($user);
    }

    return $order->get_date_created()->format("Y-m-d");
}


/**
 * @param $date NULL | WC_DateTime
 */

function wc_ec_get_date($date){

}

function wc_ec_nf_or_empty($price){

    if ((int)$price === 0){
        return "";
    }
    return number_format($price, 2);

}

function WCEC() {
	return WC_Emailchef_Plugin::get_instance();
}

WCEC();
