<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( WC_EMAILCHEF_DIR . 'includes/class-wc-emailchef-api.php' );

class WC_Emailchef extends WC_Emailchef_Api {

	private $new_custom_id;

	/**
	 * @var string
	 */
	private $lastResponse;

	private $import_response_diagnostic = '';

	public $last_import_summary = array();

	private function get_custom_fields() {
		return require( WC_EMAILCHEF_DIR . "conf/custom_fields.php" );
	}

	/**
	 *
	 * Emailchef sync initial list
	 *
	 * @param $list_id
	 * @param bool $all if not all, only custom fields will be created
	 *
	 * @return bool
	 */

	public function sync_list( $list_id, $all = true ) {

		global $wpdb;

		wc_set_time_limit( 0 );

		if ( ! $this->initialize_custom_fields( $list_id ) ) {
			return false;
		}

		if ( ! $all ) {
			return true;
		}

		WCEC()->log( sprintf( __( "[START] Synchronization and custom fields creation for Emailchef list %d",
			"emailchef-for-woocommerce" ), $list_id ) );

		$batch_size = 50;
		$offset = 0;
		$success = true;
		do {
			$customers = get_users( array(
				'role' => 'customer',
				'fields' => 'ID',
				'number' => $batch_size,
				'offset' => $offset,
				'orderby' => 'ID',
				'order' => 'ASC',
				'count_total' => false,
			) );
			$data = array();
			foreach ( $customers as $customer_id ) {
				$wpdb->check_connection();
				$wc_customer = new WC_Emailchef_Customer( $customer_id );
				$contact = array();
				foreach ( $wc_customer->sync_array() as $placeholder => $value ) {
					$contact[] = array(
						'placeholder' => $placeholder === 'user_email' ? 'email' : $placeholder,
						'value' => $value,
					);
				}
				$data[] = $contact;
				unset( $wc_customer, $contact );
			}
			if ( $data ) {
				WCEC()->log( sprintf( __( "[PROGRESS] Synchronization of the list %d, chunk of %d contacts.",
					"emailchef-for-woocommerce" ), $list_id, count( $data ) ) );
				if ( ! $this->import( $list_id, $data ) ) {
					$success = false;
					WCEC()->log( sprintf( __( "[ERROR] Synchronization of the list %d, details : %s",
						"emailchef-for-woocommerce" ), $list_id, $this->lastError ) );
				}
			}
			$count = count( $customers );
			$offset += $count;
			unset( $data, $customers );
			// Release objects cached during this batch without clearing a persistent cache.
			if ( function_exists( 'wp_cache_flush_runtime' ) ) {
				wp_cache_flush_runtime();
			}
		} while ( $count === $batch_size );

		return $success;
	}

	/** Send a bounded set of customers, without recreating custom fields. */
	public function sync_customer_batch( $list_id, $customers ) {
		global $wpdb;
		$data = array();
		foreach ( $customers as $customer_id ) {
			$wpdb->check_connection();
			$customer = new WC_Emailchef_Customer( $customer_id );
			$contact = array();
			foreach ( $customer->sync_array() as $placeholder => $value ) {
				$contact[] = array( 'placeholder' => $placeholder === 'user_email' ? 'email' : $placeholder, 'value' => $value );
			}
			$data[] = $contact;
		}
		return ! $data || $this->import( $list_id, $data );
	}

	/**
	 *
	 * Emailchef sync order change
	 *
	 * @param $list_id
	 * @param $customer_id
	 *
	 * @return bool
	 */

	public function sync_order_change( $list_id, $customer_id ) {

		$ab_cart = array(

			'ab_cart_is_abandoned_cart'  => false,
			'ab_cart_prod_name_pr_hr'    => "",
			'ab_cart_prod_desc_pr_hr'    => "",
			'ab_cart_prod_pr_pr_hr'      => "",
			'ab_cart_prod_url_pr_hr'     => "",
			'ab_cart_prod_url_img_pr_hr' => "",
			'ab_cart_prod_id_pr_hr'      => "",
			'ab_cart_date'               => "",

		);

		$wc_customer = new WC_Emailchef_Customer( $customer_id );

		$send_data = array_merge( $wc_customer->sync_array(), $ab_cart );
		unset( $send_data['newsletter'] );

		return $this->upsert_customer( $list_id, $send_data );
	}

	private function ordered_product_ids( $items ) {

		$ids = array();

		foreach ( $items as $item ) {
			$ids[] = $item['product_id'];
		}

		return implode( ",", $ids );

	}

	public function sync_guest( $list_id, $order_id, $newsletter ) {
		$order = wc_get_order( $order_id );

		$sendData = array(
			'source'                   => WCEC()->get_platform(),
			'first_name'               => $order->get_billing_first_name(),
			'last_name'                => $order->get_billing_last_name(),
			'billing_company'          => $order->get_billing_company(),
			'billing_address_1'        => $order->get_billing_address_1(),
			'billing_postcode'         => $order->get_billing_postcode(),
			'billing_city'             => $order->get_billing_city(),
			'billing_phone'            => $order->get_billing_phone(),
			'billing_state'            => $order->get_billing_state(),
			'billing_country'          => $order->get_billing_country(),
			'customer_type'            => 'Guest',
			'user_email'               => $order->get_billing_email(),
			'currency'                 => get_woocommerce_currency(),
			'total_ordered'            => $order->get_total(),
			'total_ordered_30d'        => $order->get_total(),
			'total_ordered_12m'        => $order->get_total(),
			'total_orders'             => 1,
			'all_ordered_product_ids'  => $this->ordered_product_ids( $order->get_items() ),
			'latest_order_product_ids' => $this->ordered_product_ids( $order->get_items() ),
			'latest_order_id'          => $order_id,
			'latest_order_amount'      => $order->get_total(),
			'latest_order_date'        => $order->get_date_modified()->date_i18n(),
			'latest_order_status'      => wc_ec_get_order_status_name( "wc-" . $order->get_status() ),
			'newsletter'               => $newsletter ? "pending" : "no",
			'store_registration_date'  => wc_ec_get_user_registration_byorder( $order )
		);

		if ( $order->get_status() != "pending" ) {
			unset( $sendData['newsletter'] );
		}

		if ( $order->get_status() == "completed" ) {
			$other_data = array(
				'latest_shipped_order_id'     => $order_id,
				'latest_shipped_order_date'   => $order->get_date_modified()->date_i18n(),
				'latest_shipped_order_status' => wc_ec_get_order_status_name( "wc-" . $order->get_status() )
			);
		} else {
			$other_data = array(
				'latest_shipped_order_id'     => "",
				'latest_shipped_order_date'   => "",
				'latest_shipped_order_status' => ""
			);
		}

		$sendData = array_merge( $sendData, $other_data );

		return $this->upsert_customer( $list_id, $sendData );

	}

	public function import( $list_id, $customers ) {

		$args = array(
			"instance_in" => array(
				"contacts"          => $customers,
				"notification_link" => ""
			)
		);

		add_filter( "ec_wc_get_args", array( $this, "filter_args_json" ) );

		$update = $this->json( "/lists/" . $list_id . "/import", $args, "POST" );

		remove_filter( "ec_wc_get_args", array( $this, "filter_args_json" ) );
		$this->last_import_summary = array(
			'task_id' => is_array( $update ) && isset( $update['id'] ) && is_scalar( $update['id'] ) ? sanitize_text_field( (string) $update['id'] ) : '',
			'accepted' => is_array( $update ) && isset( $update['contacts'] ) && is_numeric( $update['contacts'] ) ? (int) $update['contacts'] : 0,
			'warnings' => is_array( $update ) && isset( $update['validation_warnings'] ) && is_array( $update['validation_warnings'] ) ? count( $update['validation_warnings'] ) : 0,
			'warning_details' => array(),
		);
		if ( is_array( $update ) && ! empty( $update['validation_warnings'] ) && is_array( $update['validation_warnings'] ) ) {
			foreach ( array_slice( $update['validation_warnings'], 0, 5 ) as $warning ) {
				if ( is_array( $warning ) && isset( $warning['placeholder'], $warning['message'] ) && is_scalar( $warning['placeholder'] ) && is_scalar( $warning['message'] ) ) {
					$this->last_import_summary['warning_details'][] = substr( sanitize_text_field( (string) $warning['placeholder'] ), 0, 100 ) . ': ' . substr( sanitize_text_field( (string) $warning['message'] ), 0, 100 );
				}
			}
		}
		if ( is_array( $update ) && isset( $update['validation_errors'] ) && is_array( $update['validation_errors'] ) && count( $update['validation_errors'] ) > 0 ) {
			$reasons = array();
			foreach ( $update['validation_errors'] as $error ) {
				if ( isset( $error['item_index'], $error['message'] ) && is_scalar( $error['message'] ) ) {
					$reasons[] = sprintf( __( 'contact %1$d: %2$s', 'emailchef-for-woocommerce' ), (int) $error['item_index'] + 1, sanitize_text_field( (string) $error['message'] ) );
				}
			}
			$this->lastError = sprintf( __( 'Emailchef rejected %1$d contacts in this batch. Accepted: %2$d. %3$s', 'emailchef-for-woocommerce' ), count( $update['validation_errors'] ), isset( $update['contacts'] ) ? (int) $update['contacts'] : 0, implode( '; ', array_slice( $reasons, 0, 5 ) ) );
			return false;
		}
		if ( is_array( $update ) && isset( $update['id'], $update['contacts'] ) && is_scalar( $update['id'] ) && (string) $update['id'] !== '' && (string) $update['id'] !== '0' && is_numeric( $update['contacts'] ) && (int) $update['contacts'] === count( $customers ) && isset( $update['validation_errors'] ) && is_array( $update['validation_errors'] ) ) {
			return true;
		}
		if ( isset( $update['status'] ) && $update['status'] == "OK" ) {
			$this->last_import_summary['accepted'] = count( $customers );
			return true;
		}

		if ( isset( $update['status_code'] ) ) {
			$code = (int) $update['status_code'];
			$this->lastError = $code
				? sprintf( __( 'Import request failed (HTTP %d).', 'emailchef-for-woocommerce' ), $code )
				: __( 'Import request failed: the server could not reach Emailchef.', 'emailchef-for-woocommerce' );
			if ( ! empty( $update['diagnostic'] ) ) {
				$this->lastError .= ' ' . $update['diagnostic'];
			}
		} elseif ( isset( $update['message'] ) && is_scalar( $update['message'] ) ) {
			$this->lastError = sanitize_text_field( (string) $update['message'] );
		} else {
			$this->lastError = __( 'Import request failed: unexpected API response.', 'emailchef-for-woocommerce' );
			if ( $this->import_response_diagnostic ) {
				$this->lastError .= ' ' . $this->import_response_diagnostic;
			}
		}

		return false;

	}

	public function maybe_empty_cart( $list_id, $customer_id, $order_id ) {

		$wc_customer = new WC_Emailchef_Customer( $customer_id );

		$path        = "/contacts";
		$route       = sprintf(
			"%s?query_string=%s&limit=10&offset=0&list_id=%d&orderby=e&ordertype=a",
			$path,
			$wc_customer->getEmail(),
			$list_id
		);
		$ec_customer = $this->json( $route, array(), "GET" );
		$ec_id       = $ec_customer[0]['id'];

		$route = sprintf( "%s/%d?list_id=%d", $path, $ec_id, $list_id );
		$in_ec = $this->json( $route, array(), "GET" );


		$customFields = $in_ec['customFields'];
		$placeholders = array_column( $customFields, 'place_holder' );
		$idKey        = array_search( 'ab_cart_prod_id_pr_hr', $placeholders );
		$ec_pd_id     = $customFields[ $idKey ]['value'];

		$order = wc_get_order( $order_id );

		$customer = array(

			'user_email'                 => $wc_customer->getEmail(),
			'first_name'                 => $wc_customer->meta()->get( "first_name" ),
			'last_name'                  => $wc_customer->meta()->get( "last_name" ),
			'ab_cart_prod_name_pr_hr'    => "",
			'ab_cart_prod_desc_pr_hr'    => "",
			'ab_cart_prod_pr_pr_hr'      => "",
			'ab_cart_prod_url_pr_hr'     => "",
			'ab_cart_prod_url_img_pr_hr' => "",
			'ab_cart_prod_id_pr_hr'      => "",
			'ab_cart_date'               => "",

		);

		foreach ( $order->get_items() as $item ) {

			if ( $item['product_id'] == $ec_pd_id ) {
				return $this->update_customer( $list_id, $customer, $ec_id, true );
			}

		}

		return true;

	}

	public function sync_cart( $list_id, $send_data ) {
		return $this->upsert_customer( $list_id, $send_data );
	}

	public function sync_user_change( $list_id, $customer_id, $newsletter ) {
		$wc_customer = new WC_Emailchef_Customer( $customer_id );

		return $this->upsert_customer( $list_id, $wc_customer->sync_user( $newsletter ) );
	}

	/**
	 *
	 * Emailchef gateway send email
	 *
	 * @param $to
	 * @param $subject
	 * @param $message
	 *
	 * @return bool
	 */

	public function em_mail( $to, $subject, $message ) {

		$senders = $this->json( "/senders", array(), "GET" );

		$args = array(

			"instance_in" => array(
				"sender_id" => $senders[0]['id'],
				"to"        => array(
					"email" => $to,
					"name"  => $to,
				),
				"subject"   => $subject,
				"text_body" => $message,
				"html_body" => $message,
			),

		);

		$response = $this->json( "/sendmail", $args, "POST" );

		if ( isset( $response['status'] ) && $response['status'] == "OK" ) {
			return true;
		} else {
			$this->lastError = $response['message'];

			return false;
		}

	}

	/**
	 *
	 * Insert customer
	 *
	 * @param $list_id
	 * @param $customer
	 *
	 * @return bool
	 */

	private function insert_customer( $list_id, $customer ) {

		$customer = $this->_prepare_customer_data( $customer, $list_id );

		$collection = $this->get_collection( $list_id );

		$custom_fields = array_map(
			function ( $field ) use ( $customer ) {

				$field['value'] = $customer[ $field['place_holder'] ];

				if ( $field['value'] == null ) {
					$field['value'] = "";
				}

				return $field;

			},
			$collection
		);

		$args = array(

			"instance_in" => array(
				"list_id"       => $list_id,
				"status"        => "ACTIVE",
				"email"         => $customer['user_email'],
				"firstname"     => $customer['first_name'],
				"lastname"      => $customer['last_name'],
				"custom_fields" => $custom_fields,
				"mode"          => "ADMIN",
			),

		);

		if ( ! isset( $customer['first_name'] ) || empty( $args['instance_in']['firstname'] ) ) {
			unset( $args['instance_in']['firstname'] );
		}

		if ( ! isset( $customer['last_name'] ) || empty( $args['instance_in']['lastname'] ) ) {
			unset( $args['instance_in']['lastname'] );
		}

		$response = $this->json( "/contacts", $args, "POST" );

		if ( isset( $response['contact_added_to_list'] ) && $response['contact_added_to_list'] ) {
			return true;
		}

		$this->lastError = $response['message'];

		return false;

	}

	public function dopt_confirm( $list_id, $customer_id, $firstname, $lastname, $opt_in ) {

		$path  = "/contacts";
		$route = sprintf( "%s/%d", $path, $customer_id );

		$collection = $this->get_collection( $list_id );

		$dKey = array_search( "newsletter", array_column( $collection, "place_holder" ) );

		if ( $opt_in == 1 ) {
			$collection[ $dKey ]['value'] = 'yes';
		} elseif ( $opt_in == 2 ) {
			$collection[ $dKey ]['value'] = 'no';
		}

		$args = array(

			"instance_in" => array(
				"list_id"       => $list_id,
				"firstname"     => $firstname,
				"lastname"      => $lastname,
				"status"        => "ACTIVE",
				"custom_fields" => array(
					$collection[ $dKey ],
				),
				"mode"          => "ADMIN",
			),

		);

		$update = $this->json( $route, $args, "PUT" );

		if ( isset( $update['status'] ) && $update['status'] == "OK" ) {
			return true;
		}

		$this->lastError = $update['message'] . ": " . $update['more_info'];

		return false;

	}

	private function _prepare_customer_data( $customer, $list_id, $ec_id = 0 ) {
		return apply_filters( 'emailchef_customer_data', $customer, $list_id, $ec_id );
	}

	private function update_customer( $list_id, $customer, $ec_id ) {

		$customer = $this->_prepare_customer_data( $customer, $list_id, $ec_id );

		$path  = "/contacts";
		$route = sprintf( "%s/%d", $path, $ec_id );

		$custom_fields = array();
		$collection    = $this->get_collection( $list_id );

		foreach ( $collection as $custom ) {

			$my_custom = $custom;

			if ( ! isset( $customer[ $my_custom['place_holder'] ] ) ) {

				if ( $my_custom['place_holder'] == "latest_shipped_order_id" ) {
					$my_custom['value'] = "";
					$custom_fields[]    = $my_custom;
				}

				if ( $my_custom['place_holder'] == "latest_shipped_order_date" ) {
					$my_custom['value'] = "";
					$custom_fields[]    = $my_custom;
				}

				if ( $my_custom['place_holder'] == "latest_shipped_order_status" ) {
					$my_custom['value'] = "";
					$custom_fields[]    = $my_custom;
				}

				continue;

			}

			$my_custom['value'] = $customer[ $my_custom['place_holder'] ];

			$custom_fields[] = $my_custom;

		}

		$args = array(

			"instance_in" => array(
				"list_id"       => $list_id,
				"status"        => "ACTIVE",
				"email"         => $customer['user_email'],
				"firstname"     => $customer['first_name'],
				"lastname"      => $customer['last_name'],
				"custom_fields" => $custom_fields,
				"mode"          => "ADMIN",
			),

		);

		$update = $this->json( $route, $args, "PUT" );

		if ( isset( $update['status'] ) && $update['status'] == "OK" ) {
			return true;
		}

		$this->lastError = $update['message'];

		return false;

	}

	public function upsert_customer( $list_id, $customer, $opt_in = 0 ) {

		$path = "/contacts";

		$route = sprintf(
			"%s?query_string=%s&limit=10&offset=0&list_id=%d&orderby=e&ordertype=a",
			$path,
			$customer['user_email'],
			$list_id
		);

		$ec_customer = $this->json( $route, array(), "GET" );

		if ( empty( $ec_customer ) ) {
			return $this->insert_customer( $list_id, $customer );
		}

		if ( $opt_in != 0 ) {
			return $this->dopt_confirm(
				$list_id,
				$ec_customer[0]['id'],
				$customer['firstname'],
				$customer['lastname'],
				$opt_in
			);
		}

		return $this->update_customer( $list_id, $customer, $ec_customer[0]['id'] );


	}

	public function initialize_custom_fields( $list_id ) {

		$collection = $this->get_collection( $list_id );
		if ( ! is_array( $collection ) || isset( $collection['status'] ) ) {
			$this->lastError = __( 'Unable to retrieve custom fields.', 'emailchef-for-woocommerce' );
			return false;
		}

		$new_custom_fields = array();

		foreach ( $this->get_custom_fields() as $place_holder => $custom_field ) {

			$type          = $custom_field['type'];
			$name          = $custom_field['name'];
			$options       = ( isset( $custom_field['options'] ) ? $custom_field['options'] : array() );
			$default_value = ( isset( $custom_field['default_value'] ) ? $custom_field['default_value'] : "" );

			/**
			 *
			 * Check if is predefined
			 * if it is continue
			 *
			 */

			if ( $type == "predefined" ) {
				continue;
			}

			/**
			 *
			 * Check if a custom field exists by placeholder
			 *
			 */

			$cID = array_search( $place_holder, array_column( $collection, "place_holder" ) );

			if ( $cID !== false ) {

				/**
				 *
				 * Check if the type of custom fields is valid
				 *
				 */

				$data_type = $collection[ $cID ]['data_type'];
				$data_id   = $collection[ $cID ]['id'];

				if ( $type != $data_type ) {
					if ( ! $this->delete_custom_field( $data_id ) ) {
						return false;
					}
				} else {
					$new_custom_fields[] = $data_id;
					continue;
				}

			}

			if ( ! $this->create_custom_field( $list_id, $type, $name, $place_holder, $options, $default_value ) ) {
				return false;
			}
			$new_custom_fields[] = $this->new_custom_id;

		}

		/**
		 *
		 * Check if there are fields in Emailchef
		 * not present in @private $custom_fields
		 *
		 * If fields are present delete
		 *
		 */

		return true;

		/*$ec_id_custom_fields = array_column($collection, "id");
		$diff                = array_diff($ec_id_custom_fields, $new_custom_fields);

		foreach ($diff as $custom_id) {
			$this->delete_custom_field($custom_id);
		}*/

		return true;

	}

	public function filter_args_json( $args ) {

		$args['headers'] = array_merge(
			$args['headers'],
			array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			) );
		$args['body']    = json_encode( $args['body'] );

		return $args;

	}

	public function delete_custom_field( $field_id ) {

		$route = sprintf( "/customfields/%d", $field_id );

		add_filter( "ec_wc_get_args", array( $this, "filter_args_json" ) );

		$status = $this->json( $route, array(), "DELETE" );

		if ( $status !== "OK" ) {
			$this->lastError = $status['message'];
		}

		remove_filter( "ec_wc_get_args", array( $this, "filter_args_json" ) );

		return ( $status == "OK" );

	}

	public function delete_integration( $list_id ) {

		$integrations = $this->get_integrations($list_id);
		foreach ($integrations as $integration) {
			if ($integration['id'] == 3 && $integration['website'] == get_home_url()){
				$route = "/lists/".$list_id."/integrations/".$integration["row_id"];
				$response = $this->json( $route, array(), "DELETE" );
				return ( $response["status"] == "OK" );
			}
		}
		return false;
	}

	public function create_custom_field(
		$list_id,
		$type,
		$name,
		$placeholder,
		$options = array(),
		$default_value = ""
	) {

		$route = sprintf( "/lists/%d/customfields", $list_id );

		$args = array(

			"instance_in" => array(
				"data_type"     => $type,
				"name"          => ( $name == "" ? $placeholder : $name ),
				"place_holder"  => $placeholder,
				"default_value" => $default_value,
			),

		);


		if ( $type == "select" ) {
			$args["instance_in"]["options"] = $options;
		}

		add_filter( "ec_wc_get_args", array( $this, "filter_args_json" ) );

		$response = $this->json( $route, $args, "POST" );

		remove_filter( "ec_wc_get_args", array( $this, "filter_args_json" ) );

		if ( isset( $response['status'] ) && $response['status'] == "OK" ) {

			$this->new_custom_id = $response['custom_field_id'];

			return true;
		}

		$this->lastError = $response['message'];

		return false;


	}

	public function get_collection( $list_id ) {
		$route = sprintf( "/lists/%d/customfields", $list_id );

		return $this->json( $route, array(), "GET" );
	}

	public function get_policy() {

		$account = $this->json( "/accounts/current?with_policy_details=1", array(), "GET" );

		if ( isset( $account['policy_details'] ) ) {
			if ( $account['policy_details']['single_optin'] ) {
				return 'premium';
			}

			return 'safe';
		}

		return $account['mode'];

	}

	public function __construct( $consumer_key, $consumer_secret ) {
		parent::__construct( $consumer_key, $consumer_secret );
	}

	private function json( $route, $args, $method = "POST" ) {

		$response = $this->call(
			$route,
			$args,
			$method
		);

		$status_code = wp_remote_retrieve_response_code( $response );

		do_action( "ec_wc_api_response", $response, [
			'route'  => $route,
			'args'   => $args,
			'method' => $method
		] );

		if ( $status_code !== 200 ) {
			$diagnostic = '';
			if ( is_wp_error( $response ) ) {
				$diagnostic = sanitize_text_field( $response->get_error_message() );
			} else {
				$body = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( is_array( $body ) && isset( $body['message'] ) && is_scalar( $body['message'] ) ) {
					$diagnostic = sanitize_text_field( (string) $body['message'] );
				}
			}
			return apply_filters( "ec_wc_api_response_body_error",
				[
					"status"      => "error",
					"sub_status"  => "api_error",
					"status_code" => $status_code,
					"diagnostic"  => substr( $diagnostic, 0, 500 ),
				],
				$response,
				$route,
				$args,
				$method
			);
		}

		$response_body = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $response_body, true );
		if ( preg_match( '#^/lists/[0-9]+/import$#', $route ) ) {
			$content_type = sanitize_text_field( (string) wp_remote_retrieve_header( $response, 'content-type' ) );
			$description = is_array( $decoded ) ? 'JSON object/array' : gettype( $decoded );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$description = 'invalid JSON: ' . json_last_error_msg();
			} elseif ( is_array( $decoded ) ) {
				// Only report known structural keys, never contact data or arbitrary response values.
				$keys = array_intersect( array( 'status', 'message', 'error', 'errors', 'id', 'import_id', 'task_id', 'data' ), array_keys( $decoded ) );
				$description .= '; known keys: ' . ( $keys ? implode( ', ', $keys ) : 'none' );
				if ( isset( $decoded['status'] ) && is_string( $decoded['status'] ) && preg_match( '/^[A-Za-z_-]{1,30}$/', $decoded['status'] ) ) {
					$description .= '; status: ' . $decoded['status'];
				}
			}
			$this->import_response_diagnostic = sprintf( 'HTTP %d; Content-Type: %s; bytes: %d; %s.', $status_code, $content_type ?: 'unknown', strlen( $response_body ), $description );
		}

		return apply_filters( "ec_wc_api_response_body_success",
			$decoded,
			$response,
			$route,
			$args,
			$method
		);
	}

	/**
	 * Get integrations
	 *
	 * @return string
	 */

	public function get_meta_integrations() {
		return $this->json( "/meta/integrations", array(), "GET" );
	}


	/**
	 * Get integrations from Emailchef List
	 *
	 * @param $list_id
	 *
	 * @return mixed
	 */

	public function get_integrations( $list_id ) {
		$route = sprintf( "/lists/%d/integrations", $list_id );

		return $this->json( $route, array(), "GET" );
	}

	/**
	 * Upsert integrations yo Emailchef List
	 *
	 * @param $list_id
	 *
	 * @return mixed
	 */

	public function upsert_integration( $list_id ) {
		$integrations = $this->get_integrations( $list_id );
		foreach ( $integrations as $integration ) {
			if ( $integration["id"] == 3 && $integration["website"] == get_home_url() ) {
				return $this->update_integration( $integration["row_id"], $list_id );
			}
		}

		return $this->create_integration( $list_id );
	}

	/**
	 * Upsert integrations yo Emailchef List
	 *
	 * @param $list_id
	 * @param $integration_id
	 *
	 * @return mixed
	 */

	public function update_integration( $integration_id, $list_id ) {

		$args = array(

			"instance_in" => array(
				"list_id"        => $list_id,
				"integration_id" => 3,
				"website"        => get_site_url(),
			)

		);

		$response = $this->json( "/integrations/" . $integration_id, $args, "PUT" );

		if ( $response['status'] != "OK" ) {
			$this->lastError    = $response['message'];
			$this->lastResponse = $response;

			return false;
		}

		return $response['integration_id'];

	}

	/**
	 * Get integrations from Emailchef List
	 *
	 * @param $list_id
	 *
	 * @return mixed
	 */

	public function create_integration( $list_id ) {

		$args = array(

			"instance_in" => array(
				"list_id"        => $list_id,
				"integration_id" => 3,
				"website"        => get_site_url(),
			)

		);

		$response = $this->json( "/integrations", $args, "POST" );

		if ( $response['status'] != "OK" ) {
			$this->lastError    = $response['message'];
			$this->lastResponse = $response;

			return false;
		}

		return $response['id'];

	}

	public function lists( $args = array() ) {
		return $this->json( "/lists", $args, "GET" );
	}

	public function wrap_list( $args = array() ) {

		$args['offset']    = 0;
		$args['orderby']   = 'cd';
		$args['ordertype'] = 'd';
		$args['pinned'] = 1;

		if ( ! array_key_exists( 'limit', $args ) ) {
			$args['limit'] = 100;
		}

		$lists = $this->lists( $args );

		if ( ! $lists ) {
			return [];
		}

		$results = array();

		foreach ( $lists as $list ) {

			$results[ $list['id'] ] = $list['name'];

		}

		return $results;

	}

	public function account() {
		return $this->json( "/accounts/current", array(), "GET" );
	}

	public function create_list( $name, $description ) {

		$args = array(

			"instance_in" => array(
				"list_name"    => $name,
				"integrations" => array(
					array(
						"integration_id" => 3,
						"website"        => get_site_url()
					)
				)
			),

		);

		if ( $description != "" ) {
			$args["instance_in"]["list_description"] = $description;
		}

		$response = $this->json( "/lists", $args, "POST" );

		if ( $response['status'] != "OK" ) {
			$this->lastError = $response['message'];

			return false;
		}

		delete_transient( 'ecwc_lists' );

		return $response['list_id'];

	}

}
