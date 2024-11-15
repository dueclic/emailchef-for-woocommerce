<?php
/*
* Plugin Name: Emailchef for WooCommerce
* Plugin Uri: http://emailchef.com/email-marketing-woocommerce-emailchef/
* Description: Using this WooCommerce plugin, Emailchef can communicate with your online store and it creates easy, simply and automatic targeted campaigns.
* Author: dueclic
* Author URI: https://www.dueclic.com
* Version: 5.2
* Tested up: 6.7
* WC requires at least: 8.3.1
* WC tested up to: 9.4.1
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
	if ( is_null( $customer_id ) ) {
		return 0;
	}

	if ( $gap_days == 0 ) {
		return wc_get_customer_total_spent( $customer_id );
	}

	$customer_id = absint( $customer_id );
	$gap_days = absint( $gap_days );

	$date_from = ( new DateTime() )->modify( "-{$gap_days} days" )->format( 'Y-m-d H:i:s' );

	$query = new WC_Order_Query( array(
		'customer_id' => $customer_id,
		'status'      => array( 'wc-completed', 'wc-processing' ),
		'date_after'  => $date_from,
		'return'      => 'ids',
	) );

	$order_ids = $query->get_orders();
	$total_spent = 0;

	foreach ( $order_ids as $order_id ) {
		$order = wc_get_order( $order_id );
		$total_spent += $order->get_total();
	}

	return $total_spent;
}

/**
 * @param $customer_id
 *
 * @return string
 *
 *
 */

function wc_ec_get_all_products( $customer_id, $no_order = -1 ) {
	$products = array();

	if ( empty( $customer_id ) ) {
		$customer_id = get_current_user_id();
	}

	// Ottieni gli ordini del cliente
	$query_args = array(
		'customer_id' => $customer_id,
		'status'      => array_keys( wc_get_order_statuses() ),
		'limit'       => $no_order == -1 ? -1 : 1,
		'orderby'     => 'date',
		'order'       => 'DESC',
	);

	$query = new WC_Order_Query( $query_args );
	$customer_orders = $query->get_orders();

	if ( ! empty( $customer_orders ) ) {
		foreach ( $customer_orders as $order ) {
			foreach ( $order->get_items() as $order_item ) {
				$product_id = $order_item->get_product_id();
				if ( ! in_array( $product_id, $products ) ) {
					$products[] = $product_id;
				}
			}
		}
	}

	return implode( ", ", $products );
}

function wc_ec_get_customer_last_order( $customer_id ) {
	$customer_id = absint( $customer_id );

	$query = new WC_Order_Query( array(
		'customer_id' => $customer_id,
		'limit'       => 1,
		'orderby'     => 'date',
		'order'       => 'DESC',
		'status'      => 'completed',
	) );

	$orders = $query->get_orders();

	if ( ! empty( $orders ) ) {
		return $orders[0];
	}

	return null;
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
