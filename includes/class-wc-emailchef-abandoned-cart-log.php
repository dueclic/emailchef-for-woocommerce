<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * History of the abandoned carts sent to Emailchef.
 *
 * Every synchronization attempt is recorded with the data actually sent, so the
 * settings page can show what the customer will receive. Rows older than the
 * retention period (30 days, filterable) are deleted, together with the stale
 * rows of the abandoned carts table.
 */
class WC_Emailchef_Abandoned_Cart_Log {

	const DB_VERSION = '1';
	const DB_VERSION_OPTION = 'wc_emailchef_abcart_db_version';
	const PURGE_TRANSIENT = 'wc_emailchef_abcart_purged';
	const RETENTION_DAYS = 30;

	const STATUS_SYNCED = 'synced';
	const STATUS_ERROR = 'error';
	const STATUS_LEGACY = 'legacy';

	public static function init() {
		if ( did_action( 'plugins_loaded' ) ) {
			self::maybe_upgrade();
		} else {
			add_action( 'plugins_loaded', array( __CLASS__, 'maybe_upgrade' ), 20 );
		}
	}

	public static function table() {
		global $wpdb;

		return $wpdb->prefix . 'emailchef_abcart_log';
	}

	public static function carts_table() {
		global $wpdb;

		return $wpdb->prefix . 'emailchef_abcart';
	}

	public static function retention_days() {
		$days = (int) apply_filters( 'emailchef_abandoned_cart_retention_days', self::RETENTION_DAYS );

		return max( 1, $days );
	}

	/**
	 * Creates or updates both tables. Runs on activation and, through the stored
	 * schema version, after updates that skip activation (automatic updates, FTP).
	 */
	public static function maybe_upgrade() {
		if ( get_option( self::DB_VERSION_OPTION ) === self::DB_VERSION ) {
			return;
		}
		self::install();
	}

	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$carts           = self::carts_table();
		$log             = self::table();

		dbDelta( "CREATE TABLE {$carts} (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  user_id INT(9),
  user_email VARCHAR(255),
  product_id INT(9),
  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  synced SMALLINT DEFAULT 0,
  PRIMARY KEY  (id)
) {$charset_collate};" );

		$log_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $log ) ) ) === $log;

		dbDelta( "CREATE TABLE {$log} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  synced_at int(10) unsigned NOT NULL,
  status varchar(20) NOT NULL,
  list_id bigint(20) unsigned DEFAULT NULL,
  user_id bigint(20) unsigned DEFAULT NULL,
  user_email varchar(255) NOT NULL DEFAULT '',
  product_id bigint(20) unsigned DEFAULT NULL,
  product_name text,
  product_price varchar(32) DEFAULT NULL,
  product_url text,
  image_url text,
  description text,
  message text,
  PRIMARY KEY  (id),
  KEY synced_at (synced_at),
  KEY status (status)
) {$charset_collate};" );

		// First install of the history: keep the carts already sent as "previous history"
		// (only customer, product and date are known for them).
		if ( ! $log_exists ) {
			$wpdb->query( "INSERT INTO {$log} (synced_at, status, user_id, user_email, product_id)
				SELECT UNIX_TIMESTAMP(created), '" . self::STATUS_LEGACY . "', user_id, COALESCE(user_email, ''), product_id
				FROM {$carts} WHERE synced = 1" );
		}

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
		self::purge( true );
	}

	public static function uninstall() {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . self::table() );
		delete_option( self::DB_VERSION_OPTION );
		delete_transient( self::PURGE_TRANSIENT );
	}

	/**
	 * Records one synchronization attempt.
	 *
	 * @param array $data user_id, user_email, list_id, product (WC_Product|null), sent (array), success (bool), message (string)
	 */
	public static function record( array $data ) {
		global $wpdb;

		$sent = isset( $data['sent'] ) && is_array( $data['sent'] ) ? $data['sent'] : array();

		$wpdb->insert(
			self::table(),
			array(
				'synced_at'     => time(),
				'status'        => ! empty( $data['success'] ) ? self::STATUS_SYNCED : self::STATUS_ERROR,
				'list_id'       => isset( $data['list_id'] ) ? (int) $data['list_id'] : null,
				'user_id'       => isset( $data['user_id'] ) ? (int) $data['user_id'] : null,
				'user_email'    => isset( $data['user_email'] ) ? (string) $data['user_email'] : '',
				'product_id'    => isset( $sent['ab_cart_prod_id_pr_hr'] ) ? (int) $sent['ab_cart_prod_id_pr_hr'] : null,
				'product_name'  => isset( $sent['ab_cart_prod_name_pr_hr'] ) ? (string) $sent['ab_cart_prod_name_pr_hr'] : null,
				'product_price' => isset( $sent['ab_cart_prod_pr_pr_hr'] ) ? (string) $sent['ab_cart_prod_pr_pr_hr'] : null,
				'product_url'   => isset( $sent['ab_cart_prod_url_pr_hr'] ) ? (string) $sent['ab_cart_prod_url_pr_hr'] : null,
				'image_url'     => isset( $sent['ab_cart_prod_url_img_pr_hr'] ) ? (string) $sent['ab_cart_prod_url_img_pr_hr'] : null,
				'description'   => isset( $sent['ab_cart_prod_desc_pr_hr'] ) ? (string) $sent['ab_cart_prod_desc_pr_hr'] : null,
				'message'       => isset( $data['message'] ) ? (string) $data['message'] : null,
			),
			array( '%d', '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Deletes history rows and abandoned carts older than the retention period.
	 * Runs at most once a day unless forced.
	 */
	public static function purge( $force = false ) {
		global $wpdb;

		if ( ! $force && get_transient( self::PURGE_TRANSIENT ) ) {
			return;
		}
		set_transient( self::PURGE_TRANSIENT, 1, DAY_IN_SECONDS );

		$days = self::retention_days();

		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE synced_at < %d', time() - $days * DAY_IN_SECONDS ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::carts_table() . ' WHERE created < (NOW() - INTERVAL %d DAY)', $days ) );
	}

	public static function stats() {
		global $wpdb;

		$since = time() - 7 * DAY_IN_SECONDS;
		$table = self::table();

		$counts = $wpdb->get_results(
			$wpdb->prepare( "SELECT status, COUNT(*) AS total FROM {$table} WHERE synced_at >= %d AND status IN (%s, %s) GROUP BY status", $since, self::STATUS_SYNCED, self::STATUS_ERROR ),
			OBJECT_K
		);

		return array(
			'pending' => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::carts_table() . ' WHERE synced = 0' ),
			'synced'  => isset( $counts[ self::STATUS_SYNCED ] ) ? (int) $counts[ self::STATUS_SYNCED ]->total : 0,
			'errors'  => isset( $counts[ self::STATUS_ERROR ] ) ? (int) $counts[ self::STATUS_ERROR ]->total : 0,
		);
	}

	/**
	 * @return array{rows: array, total: int, page: int, pages: int}
	 */
	public static function query( $page = 1, $per_page = 20, $status = '' ) {
		global $wpdb;

		$table    = self::table();
		$per_page = max( 1, min( 100, (int) $per_page ) );
		$where    = '';
		if ( in_array( $status, array( self::STATUS_SYNCED, self::STATUS_ERROR, self::STATUS_LEGACY ), true ) ) {
			$where = $wpdb->prepare( 'WHERE status = %s', $status );
		}

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" );
		$pages = max( 1, (int) ceil( $total / $per_page ) );
		$page  = max( 1, min( $pages, (int) $page ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} {$where} ORDER BY synced_at DESC, id DESC LIMIT %d OFFSET %d", $per_page, ( $page - 1 ) * $per_page ),
			ARRAY_A
		);

		return array(
			'rows'  => array_map( array( __CLASS__, 'format' ), $rows ? $rows : array() ),
			'total' => $total,
			'page'  => $page,
			'pages' => $pages,
		);
	}

	/**
	 * Row as returned to the admin page. Rows of the previous history only know
	 * the product id, so name, link and image are read from the current product.
	 */
	public static function format( array $row ) {
		$product_name = $row['product_name'];
		$product_url  = $row['product_url'];
		$image_url    = $row['image_url'];

		if ( $row['status'] === self::STATUS_LEGACY && ! empty( $row['product_id'] ) ) {
			$product = wc_get_product( (int) $row['product_id'] );
			if ( $product ) {
				$product_name = $product->get_name();
				$product_url  = $product->get_permalink();
				$image_id     = $product->get_image_id();
				$image_url    = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_gallery_thumbnail' ) : '';
			}
		}

		return array(
			'id'            => (int) $row['id'],
			'time'          => (int) $row['synced_at'],
			'status'        => $row['status'],
			'user_id'       => $row['user_id'] !== null ? (int) $row['user_id'] : null,
			'user_email'    => (string) $row['user_email'],
			'product_id'    => $row['product_id'] !== null ? (int) $row['product_id'] : null,
			'product_name'  => (string) $product_name,
			'product_price' => $row['product_price'] !== null && $row['product_price'] !== '' ? html_entity_decode( wp_strip_all_tags( wc_price( (float) $row['product_price'] ) ), ENT_QUOTES, 'UTF-8' ) : '',
			'product_url'   => esc_url_raw( (string) $product_url ),
			'image_url'     => esc_url_raw( (string) $image_url ),
			'description'   => (string) $row['description'],
			'message'       => (string) $row['message'],
		);
	}
}
