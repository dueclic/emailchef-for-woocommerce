<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'WC_Emailchef_Handler' ) ) {

	final class WC_Emailchef_Handler {

		private static $instance = null;

		private $namespace;

        private $id = "emailchef";

		/**
		 * @var WC_Emailchef_Plugin
		 */
		private $wcec;

		public function __construct() {

			$this->namespace = 'wc_' . $this->id;
			$this->wcec      = WCEC();
			$this->hooks();

		}

		private function prefixed_setting( $suffix ) {
			return $this->namespace . '_' . $suffix;
		}

		private function wc_get_order( $order_id ) {
			if ( function_exists( 'wc_get_order' ) ) {
				return wc_get_order( $order_id );
			} else {
				return new WC_Order( $order_id );
			}
		}

		private function send_email( $list_id, $email, $firstname, $where ) {

			$email_recipient = $email;
			$email_url       = get_rest_url(
				null,
				sprintf( "emailchef/subscribe/%d/%s", $list_id, $email )
			);
			$email_url_unsub = get_rest_url(
				null,
				sprintf( "emailchef/unsubscribe/%d/%s", $list_id, $email )
			);

			$email_subject = __(
				"Confirm your email address to subscribe to our newsletter",
				"emailchef-for-woocommerce"
			);

			$opt_in_file = ( get_locale() == "it_IT" ? "opt_in_it.php" : "opt_in.php" );

			$email_text = file_get_contents( dirname( WC_EMAILCHEF_FILE ) . "/emails/" . $opt_in_file );
			$email_text = str_replace( "[[firstname]]", $firstname, $email_text );
			$email_text = str_replace( "[[subscribe]]", $email_url, $email_text );
			$email_text = str_replace( "[[unsubscribe]]", $email_url_unsub, $email_text );
			$email_text = str_replace( "[[image]]", plugins_url( "dist/img/placeholder.png", WC_EMAILCHEF_FILE ), $email_text );

			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			wp_mail( $email_recipient, $email_subject, $email_text, $headers );

		}

		/**
		 * Order status changed hook
		 *
		 * @param $order_id
		 * @param string $status
		 * @param string $new_status
		 */

		public function order_status_changed( $order_id, $status = 'new', $new_status = 'pending' ) {

			$order       = wc_get_order( $order_id );
			$customer_id = $order->get_user_id();
			$list_id     = get_option( $this->prefixed_setting( "list" ) );

			if ( ! $list_id ) {
				$this->wcec->log(
					sprintf(
						__(
							"Insert failure in list %d for updated data of guest customer (Order ID: %d, from status %s to %s). List not provided.",
							"emailchef-for-woocommerce"
						),
						get_option( $this->prefixed_setting( "list" ) ),
						$order_id,
						$status,
						$new_status
					)
				);

				return;
			}

			$wcec = $this->wcec->emailchef();

			if ( $customer_id == 0 ) {
				$this->wcec->log( __( "Guest synchronization to Emailchef in progress", "emailchef-for-woocommerce" ) );
				$success = $wcec->sync_guest( $list_id, $order_id, isset( $_POST['wc_emailchef_opt_in'] ) );

				if ( $success ) {

					if ( isset( $_POST['wc_emailchef_opt_in'] ) && $new_status == "pending" ) {

						$this->send_email( $list_id, $_POST['billing_email'], $_POST['billing_first_name'],
							"order_guest" );

						$this->wcec->log(
							sprintf(
								__( "Opt-in sent via email to user %d in list %d", "emailchef-for-woocommerce" ),
								$customer_id,
								$list_id
							)
						);

					}

				} else {
					$this->wcec->log(
						sprintf(
							__(
								"Insert failure in list %d for updated data of guest customer (Order ID: %d, from status %s to %s). Error: %s",
								"emailchef-for-woocommerce"
							),
							get_option( $this->prefixed_setting( "list" ) ),
							$order_id,
							$status,
							$new_status,
							$wcec->lastError
						)
					);
				}


			} else {

				$this->wcec->log(
					sprintf( __( "Customer synchronization to Emailchef in progress (Customer ID: %d)",
						"emailchef-for-woocommerce" ), $customer_id )
				);

				$success = $wcec->sync_order_change( $list_id, $customer_id );

				if ( $success ) {

					global $wpdb;

					$wpdb->delete(
						$this->wcec->abcart_table(),
						array(
							'user_id' => $customer_id
						)
					);

					if ( isset( $_POST['wc_emailchef_opt_in'] ) && $new_status == "pending" ) {

						$this->send_email( $list_id, $_POST['billing_email'], $_POST['billing_first_name'],
							"order_customer" );

						$this->wcec->log( sprintf( __( "Opt-in sent via email to user %d in list %d",
							"emailchef-for-woocommerce" ), $customer_id, $list_id ) );

					}

					$this->wcec->log(
						sprintf(
							__(
								"Insert success in list %d for updated data of customer %d (Order ID: %d, from status %s to %s)",
								"emailchef-for-woocommerce"
							),
							get_option( $this->prefixed_setting( "list" ) ),
							$customer_id,
							$order_id,
							$status,
							$new_status
						)
					);

				} else {

					$this->wcec->log(
						sprintf(
							__(
								"Insert failure in list %d for updated data of customer %d (Order ID: %d, from status %s to %s). Error: %s",
								"emailchef-for-woocommerce"
							),
							get_option( $this->prefixed_setting( "list" ) ),
							$customer_id,
							$order_id,
							$status,
							$new_status,
							$wcec->lastError
						)
					);

				}
			}

		}

		/**
		 * Add opt-in checkbox
		 */

		public function add_emailchef_consent() {

			$list_id = wc_ec_get_option_value( "list" );

			if ( ! $list_id && $this->wcec->display_opt_in() ) {

				if ( get_user_meta( get_current_user_id(), $this->prefixed_setting( "opt_in" ),
						true ) === "no" || ! is_user_logged_in() ) {

					do_action( $this->prefixed_setting( "before_opt_in_checkbox" ) );
					echo apply_filters(
						$this->prefixed_setting( 'opt_in_checkbox' ),
						'<p class="form-row woocommerce-emailchef-opt-in"><label for="' . $this->prefixed_setting(
							"opt_in"
						) . '"><input type="checkbox" name="' . $this->prefixed_setting(
							"opt_in"
						) . '" id="' . $this->prefixed_setting( "opt_in" ) . '" value="yes" checked /> ' . esc_html(
							$this->wcec->opt_in_label()
						) . '</label></p>'
					);
					do_action( $this->prefixed_setting( "after_opt_in_checkbox" ) );

				}


			}

		}

		/**
		 * Save emailchef consent
		 *
		 * @param $customer_id
		 */

		public function save_emailchef_consent( $customer_id ) {
			$first_name = isset( $_POST['billing_first_name'] )
				? $_POST['billing_first_name']
				: get_userdata(
					$customer_id
				)->first_name;

			$wcec = $this->wcec;

			$old_opt_in = get_user_meta( $customer_id, $this->prefixed_setting( 'opt_in' ), true );

			$opt_in = isset( $_POST[ $this->prefixed_setting( 'opt_in' ) ] ) ? 'yes' : 'no';
			update_user_meta( $customer_id, $this->prefixed_setting( 'opt_in' ), sanitize_text_field( $opt_in ) );

			$ec = $wcec->emailchef();

			$list_id = get_option( $this->prefixed_setting( "list" ) );

			if ( $old_opt_in !== "yes" && $wcec->double_opt_in() && $opt_in == "yes" ) {
				$opt_in = "pending";
			}

			$uc = $ec->sync_user_change( $list_id, $customer_id, $opt_in );

			if ( $uc ) {

				$this->wcec->log(
					sprintf(
						__( "Synchronization of customer %d in list %d done (Newsletter status: %s).",
							"emailchef-for-woocommerce" ),
						$customer_id,
						$list_id,
						$opt_in
					)
				);

				/*if ($opt_in === "pending") {

					$this->send_email($list_id, $_POST['billing_email'], $_POST['billing_first_name'], "save_consent");

					$this->wcec->log(
						sprintf(
							__("Opt-in sent via email to user %d in list %d", "emailchef-for-woocommerce"),
							$customer_id,
							$list_id
						)
					);
				}*/

			} else {
				$this->wcec->log(
					sprintf( __( "Synchronization of customer %d in list %d failed", "emailchef-for-woocommerce" ),
						$customer_id, $list_id )
				);
			}

		}

		public function emailchef_optin_extra_fields_edit() {

			if ( $this->wcec->display_opt_in() ) {

				$user_id = get_current_user_id();
				$user    = get_userdata( $user_id );

				if ( ! $user ) {
					return;
				}

				$optin = get_user_meta( $user_id, $this->prefixed_setting( 'opt_in' ), true );

				?>

                <p class="form-row">
                    <label for="<?php echo $this->prefixed_setting( "opt_in" ); ?>" class="inline">
                        <input class="woocommerce-Input woocommerce-Input--checkbox"
                               name="<?php echo $this->prefixed_setting( "opt_in" ); ?>" type="checkbox"
                               id="<?php echo $this->prefixed_setting( "opt_in" ); ?>"
                               value="yes" <?php echo checked( $optin, "yes" ); ?>> <?php echo esc_html(
							$this->wcec->opt_in_label()
						); ?>
                    </label>
                </p>

				<?php
			}

		}

		public function emailchef_optin_extra_fields_edit_save( $customer_id ) {

			$this->save_emailchef_consent( $customer_id );

			wp_update_user( array( 'ID' => $customer_id, 'user_url' => esc_url( $_POST['url'] ) ) );


		}

		public function subscribe_email( $data ) {

			if ( $this->wcec->double_opt_in() ) {

				$email = $data['customer_email'];

				$customer = get_user_by( "email", $email );

				/**
				 * @var $order WC_Order
				 */
				$order = $this->em_orders( $email )[0];

				if ( ! $customer ) {
					$firstname = $order->billing_first_name;
					$lastname  = $order->billing_last_name;
				} else {
					$firstname = $customer->first_name;
					$lastname  = $customer->last_name;
					update_user_meta( $customer->ID, $this->prefixed_setting( "opt_in" ), "yes" );
				}

				$list_id      = get_option( $this->prefixed_setting( "list" ) );
				$landing_page = get_option( $this->prefixed_setting( "landing_page" ) ) !== "" ? get_page_link(
					get_option( $this->prefixed_setting( "landing_page" ) )
				) : home_url();

				$wcec = $this->wcec->emailchef();

				$success = $wcec->upsert_customer(
					$list_id,
					array(
						"user_email" => $data['customer_email'],
						"firstname"  => $firstname,
						"lastname"   => $lastname,
						"source"     => $this->wcec->get_platform(),
					),
					1
				);

				if ( $success ) {
					wp_redirect( get_page_link( $landing_page ) );
					exit;
				}

				print $wcec->lastError;

			}

		}

		public function unsubscribe_email( $data ) {

			if ( $this->wcec->double_opt_in() ) {

				$email = $data['customer_email'];

				$customer = get_user_by( "email", $data['customer_email'] );

				/**
				 * @var $order WC_Order
				 */
				$order = $this->em_orders( $email )[0];

				if ( ! $customer ) {
					$firstname = $order->billing_first_name;
					$lastname  = $order->billing_last_name;
				} else {
					$firstname = $customer->first_name;
					$lastname  = $customer->last_name;
					update_user_meta( $customer->ID, $this->prefixed_setting( "opt_in" ), "no" );
				}


				$list_id   = get_option( $this->prefixed_setting( "list" ) );
				$unsubscription_page = get_option( $this->prefixed_setting( "unsubscription_page" ) ) !== "" ? get_page_link(
					get_option( $this->prefixed_setting( "unsubscription_page" ) )
				) : home_url();

				$wcec = $this->wcec->emailchef();

				$success = $wcec->upsert_customer(
					$list_id,
					array(
						"user_email" => $data['customer_email'],
						"firstname"  => $firstname,
						"lastname"   => $lastname,
						"source"     => $this->wcec->get_platform(),
					),
					2
				);

				if ( $success ) {
					wp_redirect( $unsubscription_page );
					exit;
				}

				print $wcec->lastError;

			}

		}

		private function em_orders( $email ) {
			return wc_get_orders(
				array(
					'email' => $email,
				)
			);
		}

		public function emailchef_is_active() {

			$list_id = get_option( $this->prefixed_setting( "list" ) );

			die( wp_json_encode( array(
				"is_active" => ( $list_id !== false && ! empty( $list_id ) ),
				"list_id"   => $list_id
			) ) );

		}

		public function rest_route() {
			register_rest_route(
				'emailchef',
				'/subscribe/(?P<list_id>\d+)/(?P<customer_email>(.*))',
				array(
					'methods'             => 'GET',
					'permission_callback' => '__return_true',
					'callback'            => array( $this, "subscribe_email" ),
					'args'                => array(
						'list_id'        => array(
							'validate_callback' => function ( $param, $request, $key ) {
								return get_option( $this->prefixed_setting( "list" ) ) == $param;
							},
						),
						'customer_email' => array(
							'validate_callback' => function ( $param, $request, $key ) {

								$double_opt = $this->wcec->double_opt_in();
								$customer   = get_user_by( "email", $param );

								return ( $double_opt && $customer ) || ( $double_opt && count(
									                                                        $this->em_orders( $param )
								                                                        ) > 0 );

							},
						),
					),
				)
			);

			register_rest_route(
				'emailchef',
				'/is_active',
				array(
					'methods'             => 'GET',
					'permission_callback' => '__return_true',
					'callback'            => array( $this, "emailchef_is_active" ),
					'args'                => array()
				)
			);

			register_rest_route(
				'emailchef',
				'/unsubscribe/(?P<list_id>\d+)/(?P<customer_email>(.*))',
				array(
					'methods'             => 'GET',
					'permission_callback' => '__return_true',
					'callback'            => array( $this, "unsubscribe_email" ),
					'args'                => array(
						'list_id'        => array(
							'validate_callback' => function ( $param, $request, $key ) {
								return get_option( $this->prefixed_setting( "list" ) ) == $param;
							},
						),
						'customer_email' => array(
							'validate_callback' => function ( $param, $request, $key ) {

								$double_opt = $this->wcec->double_opt_in();
								$customer   = get_user_by( "email", $param );

								return ( $double_opt && $customer ) || ( $double_opt && count(
									                                                        $this->em_orders( $param )
								                                                        ) > 0 );

							},
						),
					),
				)
			);

		}

		public function check_policy() {

			$wcec = WCEC();
			if ( $wcec->emailchef() && $wcec->emailchef()->get_policy() !== 'premium' ) {
				update_option( $this->prefixed_setting( "policy_type" ), "dopt" );
			}

		}

		public function sync_cart() {

			if ( is_user_logged_in() ) {

				global $wpdb;

				$abc = $this->wcec->abcart_table();

				$customer_id = get_current_user_id();

				$customer_data = array(
					'first_name' => get_user_meta( $customer_id, "first_name", true ),
					'last_name'  => get_user_meta( $customer_id, "last_name", true ),
					'user_email' => get_userdata( $customer_id )->user_email,
					'source'     => $this->wcec->get_platform(),
				);

				$items = WC()->cart->get_cart();

				$bigger_in_cart = array();

				foreach ( $items as $item => $values ) {
					$product = wc_get_product( $values['product_id'] );

					$name              = $product->get_name();
					$short_description = $product->get_short_description();
					$description       = $product->get_description();
					$image_to_send     = get_the_post_thumbnail_url( $product->get_id() );

					$description_to_send = ( empty( $short_description ) ? $description : $short_description );

					$current = array(
						'ab_cart_prod_name_pr_hr'    => $name,
						'ab_cart_prod_desc_pr_hr'    => $description_to_send,
						'ab_cart_prod_pr_pr_hr'      => $product->get_price(),
						'ab_cart_prod_url_pr_hr'     => $product->get_permalink(),
						'ab_cart_prod_url_img_pr_hr' => $image_to_send,
						'ab_cart_prod_id_pr_hr'      => $product->get_id(),
						'ab_cart_date'               => date( "Y-m-d" ),
					);

					if ( empty( $bigger ) ) {
						$bigger_in_cart = $current;
						continue;
					}

					if ( $current['ab_cart_prod_pr_pr_hr'] > $bigger_in_cart['ab_cart_prod_pr_pr_hr'] ) {
						$bigger_in_cart = $current;
					}
				}

				if ( empty( $bigger_in_cart ) ) {
					$empty_cart = $wpdb->query( "DELETE FROM {$abc} WHERE user_id={$customer_id} AND synced=0" );

					if ( $empty_cart !== false ) {
						$this->wcec->log(
							sprintf(
								__(
									"Abandoned cart for customer %d successfully cleared from table %s",
									"emailchef-for-woocommerce"
								),
								$customer_id,
								$abc
							)
						);
					} else {
						$this->wcec->log(
							sprintf(
								__( "Failed to clear abandoned cart for customer %d from table %s",
									"emailchef-for-woocommerce" ),
								$customer_id,
								$abc
							)
						);
					}

					return;
				}

				/**
				 * Inserimento in tabella
				 * carrelli abbandonati
				 */

				$ab_cart = array(
					'user_id'    => $customer_id,
					'user_email' => $customer_data['user_email'],
					'product_id' => $bigger_in_cart['ab_cart_prod_id_pr_hr'],
					'created'    => date( "Y-m-d H:i:s" ),
					'synced'     => 0,
				);


				$ab_cart_row = $wpdb->get_row(
					"SELECT id FROM {$abc} WHERE user_id={$customer_id} AND synced=0 ORDER BY created DESC LIMIT 1",
					ARRAY_A
				);

				if ( $ab_cart_row !== null ) {
					$ab_cart['id'] = $ab_cart_row['id'];
				}

				$sc = $wpdb->replace( $abc, $ab_cart );


				if ( $sc !== false ) {
					$this->wcec->log(
						sprintf(
							__(
								"Abandoned cart for customer %d successfully synced from table %s",
								"emailchef-for-woocommerce"
							),
							$customer_id,
							$abc
						)
					);
				} else {
					$this->wcec->log(
						sprintf(
							__( "Synchronization of abandoned cart for user %d in table %s failed",
								"emailchef-for-woocommerce" ),
							$customer_id,
							$abc
						)
					);
				}

			}

		}

		/**
		 * @param array | WP_Error $response
		 *
		 * @void
		 */

		public function handle_ec_wc_api_response(
			$response
		) {
			$status_code = wp_remote_retrieve_response_code( $response );
			if ( $status_code === 401 ) {
				update_option( $this->prefixed_setting( 'enabled' ), "no" );
			}

			do_action( "ec_wc_api_post_response", $response, $status_code );

		}

		public function hooks() {

			add_action( 'wp_ajax_' . $this->namespace . '_disconnect', array( $this, 'disconnect' ) );

			$enabled = wc_ec_get_option_value(
				'enabled'
			);

			if ( 'yes' === $enabled ) {

				/**
				 * Choice position of Emailchef consent
				 */

				add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'add_emailchef_consent' ) );

				/**
				 * Save Emailchef consent
				 */

				add_action( 'user_register', array( $this, 'save_emailchef_consent' ), 10, 1 );
				add_action( 'woocommerce_edit_account_form', array( $this, 'emailchef_optin_extra_fields_edit' ) );
				add_action( 'woocommerce_save_account_details', array(
					$this,
					'emailchef_optin_extra_fields_edit_save'
				) );
				add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'order_status_changed' ), 1000, 1 );
				add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 3 );
				add_action( 'woocommerce_cart_updated', array( $this, 'sync_cart' ), 10 );
				//add_action('woocommerce_cart_item_removed', array($this, 'remove_from_cart'), 10, 1);

				add_action( 'rest_api_init', array( $this, "rest_route" ) );


				add_action( 'wp_ajax_' . $this->namespace . '_manual_sync',
					array( $this, 'manual_sync' ) );
				add_action( 'wp_ajax_' . $this->namespace . '_debug_move_abandoned_carts',
					array( $this, 'debug_move_abandoned_carts' ) );

				add_action( 'wp_ajax_' . $this->namespace . '_lists', array( $this, 'get_lists' ) );
				add_action( 'wp_ajax_' . $this->namespace . '_add_list', array( $this, 'add_list' ) );
				add_action( 'wp_ajax_' . $this->namespace . '_sync_abandoned_carts', array(
					$this,
					'sync_abandoned_carts'
				) );
				add_action( 'wp_ajax_' . $this->namespace . '_debug_rebuild_customfields', array(
					$this,
					'debug_rebuild_customfields'
				) );
				//add_action( 'upgrader_process_complete', array( $this, 'upgrade_also_list' ), 10, 2 );
				add_action( 'wc_emailchef_loaded', array( $this, 'check_policy' ) );
				add_action( "emailchef_sync_cron_now", array( $this, 'sync_list_now' ), 1, 2 );


				if ( ! wp_next_scheduled( 'emailchef_abandoned_cart_sync' ) ) {
					wp_schedule_event(
                            time(),
                            apply_filters('emailchef_abandoned_cart_cron_schedule', 'emailchef_15_minutes'),
                            'emailchef_abandoned_cart_sync'
                    );
				}

				add_action( 'emailchef_abandoned_cart_sync', array( $this, 'maybe_abandoned_cart_sync' ) );

				add_action( 'admin_menu', array( $this, 'add_debug_page' ), 10 );

				add_action( 'ec_wc_api_response', array( $this, 'handle_ec_wc_api_response' ), 5 );

			}

			register_activation_hook( WC_EMAILCHEF_FILE, array( $this, 'create_ab_cart_table' ) );
			register_deactivation_hook( WC_EMAILCHEF_FILE, array( $this, 'delete_ab_cart_table' ) );

		}

		public function manual_sync() {

			$response = [
				'text' => __( "Manual sync successfully scheduled.",
					"emailchef-for-woocommerce" ),
				'type' => "success"
			];

			$list_id = wc_ec_get_option_value( "list" );

			if ( ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'emailchef_manual_sync' ) ) {
				$response = [
					'type' => 'error',
					'text' => __( 'Invalid request', 'emailchef-for-woocommerce' )
				];
			} else if ( ! $list_id ) {
				$response = [
					'type' => 'error',
					'text' => __( 'Please provide a valid Emailchef list', 'emailchef-for-woocommerce' )
				];
			} else {

				WCEC()->log( __( "Manual sync triggered",
					"emailchef-for-woocommerce" ) );

				$scheduled = wp_schedule_single_event( time(),
					"emailchef_sync_cron_now",
					array( $list_id, true ), true );


				if ( is_wp_error( $scheduled ) ) {
					$response['text'] = __( "Manual sync not scheduled (" . $scheduled->get_error_message() . ")",
						"emailchef-for-woocommerce" );
					$response['type'] = "error";
				}

				WCEC()->log(
					$response['text']
				);

			}

			set_transient( 'emailchef-admin-notice', $response, 30 );

			if ( $response['type'] === 'success' ) {
				wp_send_json_success(
					$response['message']
				);
			}

			wp_send_json_error(
				$response['message']
			);

		}

		public function add_debug_page() {
			$debug_name = 'Emailchef DEBUG';
			$debug_slug = 'emailchef-debug';
			$debug_cap  = 'manage_options';
			add_submenu_page(
				null,
				$debug_name,
				$debug_name,
				$debug_cap,
				$debug_slug,
				array( $this, 'render_debug_page' )
			);
		}

		public function render_debug_page() {
			$carts = $this->get_abandoned_carts( false );
			require_once( WC_EMAILCHEF_DIR . "/partials/admin-debug.php" );
		}

		public function get_abandoned_carts( $limit = true, $where = "" ) {
			global $wpdb;

			$abc = $this->wcec->abcart_table();

			$basic_query = "SELECT user_id, user_email, product_id, created FROM {$abc} WHERE synced = 0";

			if ( $limit ) {

				$interval_start = wc_ec_get_abandoned_carts_start_interval();
				$interval_end   = wc_ec_get_abandoned_carts_end_interval();

				$basic_query .= " AND created > (NOW() - INTERVAL {$interval_start}) AND created < (NOW() - INTERVAL {$interval_end})";
			}

			$basic_query .= " " . $where;

			return $wpdb->get_results(
				$basic_query,
				ARRAY_A
			);
		}

		public function debug_rebuild_customfields() {

			if ( ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'emailchef_debug_rebuild_customfields' ) ) {
				wp_send_json_error( [
					'message' => __( 'Invalid request', 'emailchef-for-woocommerce' )
				] );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [
					'message' => __( 'Insufficient permissions to execute the requested action', 'emailchef-for-woocommerce' )
				] );
			}


			$list_id = get_option( $this->prefixed_setting( "list" ) );

			if ( $list_id ) {
				WCEC()->log( sprintf( __( "[START] Custom fields rebuild for Emailchef list %d was started",
					"emailchef-for-woocommerce" ), $list_id ) );

				$this->wcec->emailchef()->initialize_custom_fields(
					$list_id
				);
				wp_send_json_success( [
					'message' => __( 'Rebuild of custom fields was completed successfully', 'emailchef-for-woocommerce' )
				] );

				WCEC()->log( sprintf( __( "[END] Rebuild of custom fields for Emailchef list %d was completed successfully",
					"emailchef-for-woocommerce" ), $list_id ) );
			} else {
				wp_send_json_error( [
					'message' => __( 'Emailchef list not provided.', 'emailchef-for-woocommerce' )
				] );
			}
		}

		private function _sync_abandoned_carts(
			$limit = true,
			$where = ""
		) {

			global $wpdb;

			$list_id = wc_ec_get_option_value( 'list' );
			if ( ! $list_id ) {

				$message = __(
					"Abandoned cart sync failed: Emailchef list missing.",
					"emailchef-for-woocommerce"
				);

				$this->wcec->log(
					$message
				);

				return new WP_Error( 'no_list_provided', $message );
			}

			$results = $this->get_abandoned_carts( $limit, $where );


			foreach ( $results as $result ) {

				$customer_id = $result['user_id'];

				$exists_user = (bool) get_user_by( 'id', $customer_id );

				if ( ! $exists_user ) {
					$wpdb->delete(
						$this->wcec->abcart_table(),
						array(
							'user_id' => $customer_id
						)
					);
					continue;
				}


				$product = wc_get_product( $result['product_id'] );

				if ( $product === false ) {
					$wpdb->delete(
						$this->wcec->abcart_table(),
						array(
							'product_id' => $result['product_id']
						)
					);
					continue;
				}

				if ( WCEC()::version_check() ) {
					$name              = $product->get_name();
					$short_description = $product->get_short_description();
				} else {
					$name              = $product->post->post_name;
					$short_description = $product->post->post_excerpt;
				}

				$list_id = get_option( $this->prefixed_setting( "list" ) );

				$send_data = array(
					'user_email'                 => $result['user_email'],
					'first_name'                 => get_user_meta( $customer_id, "first_name", true ),
					'last_name'                  => get_user_meta( $customer_id, "last_name", true ),
					'ab_cart_is_abandoned_cart'  => true,
					'ab_cart_prod_name_pr_hr'    => $name,
					'ab_cart_prod_desc_pr_hr'    => $short_description,
					'ab_cart_prod_pr_pr_hr'      => $product->get_price(),
					'ab_cart_prod_url_pr_hr'     => $product->get_permalink(),
					'ab_cart_prod_url_img_pr_hr' => $product->get_image(
						apply_filters( 'emailchef_abandoned_cart_image_size', 'woocommerce_thumbnail' )
					),
					'ab_cart_prod_id_pr_hr'      => $product->get_id(),
					'ab_cart_date'               => date( "Y-m-d" ),
				);

				$sc = $this->wcec->emailchef()->sync_cart( $list_id, $send_data );

				if ( $sc ) {
					$this->wcec->log(
						sprintf(
							__(
								"Abandoned cart for user %d successfully synchronized with Emailchef list %d",
								"emailchef-for-woocommerce"
							),
							$customer_id,
							$list_id
						)
					);
					$wpdb->update(
						$this->wcec->abcart_table(),
						array(
							'synced' => 1,
						),
						array(
							'user_id' => $customer_id,
						)
					);

				} else {
					$this->wcec->log(
						sprintf(
							__( "Abandoned cart synchronization for user %d failed in Emailchef list %d.",
								"emailchef-for-woocommerce" ),
							$customer_id,
							$list_id
						)
					);
				}

			}

			return $results;

		}

		public function sync_abandoned_carts() {

			if ( ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'emailchef_sync_abandoned_carts' ) ) {
				wp_send_json_error( [
					'message' => __( 'Invalid request', 'emailchef-for-woocommerce' )
				] );
			}

			$where = "";
			$limit = true;

			$email   = isset( $_POST['only_email'] ) && is_email( $_POST['only_email'] ) ? $_POST['only_email'] : null;
			$user_id = isset( $_POST['only_userid'] ) && ! empty( $_POST['only_userid'] ) ? $_POST['only_userid'] : null;

			if ( ! is_null( $email ) ) {
				$where = " AND user_email='" . $email . "'";
				$limit = false;
			} else if ( ! is_null( $user_id ) ) {
				$where = " AND user_id='" . $user_id . "'";
				$limit = false;
			}


			$carts_synced = $this->_sync_abandoned_carts( $limit, $where );

			if ( is_wp_error( $carts_synced ) ) {
				wp_send_json_error( [
					'message' => __( $carts_synced->get_error_message(), 'emailchef-for-woocommerce' )
				] );

			}

			wp_send_json_success( [
				'message' => __( 'Failed to sync abandoned carts.', 'emailchef-for-woocommerce' )
			] );

		}

		public function create_ab_cart_table() {

			global $wpdb;

			$abc             = $this->wcec->abcart_table();
			$charset_collate = $wpdb->get_charset_collate();

			$sight_sql = "CREATE TABLE IF NOT EXISTS {$abc} (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id INT(9),
                user_email VARCHAR(255),
                product_id INT(9),
                created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                synced SMALLINT DEFAULT 0,
                PRIMARY KEY  (id)
            ) {$charset_collate};";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			dbDelta( $sight_sql );

		}

		public function delete_ab_cart_table() {

			global $wpdb;

			$abc = $this->wcec->abcart_table();

			$sight_sql = "DROP TABLE IF EXISTS {$abc};";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			$wpdb->query( $sight_sql );
		}

		public function sync_list_now( $list_id, $all = true ) {

			if ( ! $list_id ) {
				$this->wcec->log(
					__(
						"Custom fields creation and sync failed: no list provided.",
						"emailchef-for-woocommerce"
					)
				);

				return null;
			}

			$this->wcec->emailchef()->upsert_integration( $list_id );
			$this->wcec->emailchef()->sync_list( $list_id, $all );
			if ( $all ) {
				$this->wcec->log( sprintf( __( "Custom fields creation and sync for Emailchef list %d",
					"emailchef-for-woocommerce" ), $list_id ) );
			} else {
				$this->wcec->log( sprintf( __( "Custom fields creation for Emailchef list %d",
					"emailchef-for-woocommerce" ), $list_id ) );
			}

		}

		public function maybe_abandoned_cart_sync() {
			$this->_sync_abandoned_carts();;
		}


		public function debug_move_abandoned_carts() {

			if ( ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'emailchef_debug_move_abandoned_carts' ) ) {
				wp_send_json_error( [
					'message' => __( 'Invalid request', 'emailchef-for-woocommerce' )
				] );
			}

			$list_id = wc_ec_get_option_value( 'list' );

			if ( ! $list_id ) {
				$message = __( 'Failed to move abandoned carts: missing target list.', 'emailchef-for-woocommerce' );

				wp_send_json_error( [
					'message' => esc_html( $message )
				] );

				$this->wcec->log(
					$message
				);
			}

			global $wpdb;

			$abc = $this->wcec->abcart_table();

			$results = $wpdb->get_results(
				"SELECT user_id, user_email, product_id, created FROM {$abc} WHERE synced = 0",
				ARRAY_A
			);

			foreach ( $results as $result ) {

				$new_created = strtotime( $result['created'] ) - 26 * 60 * 60;

				$wpdb->update( $abc, array(
					"created" => date( 'Y-m-d H:i:s', $new_created )
				), array(
					"user_id" => $result['user_id']
				) );

			}

			$message = __( 'Abandoned carts successfully moved', 'emailchef-for-woocommerce' );

			wp_send_json_success( [
				'message' => esc_html( $message )
			] );

			$this->wcec->log(
				esc_html( $message )
			);

		}

		public function get_lists() {

			if ( ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'emailchef_lists' ) ) {
				wp_send_json_error( [
					'message' => __( 'Invalid request', 'emailchef-for-woocommerce' )
				] );
			}


			$lists = $this->wcec->emailchef()->wrap_list();

			wp_send_json_success( [
				'lists' => $lists
			] );

		}

		public function disconnect() {

			if ( ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'emailchef_disconnect' ) ) {
				wp_send_json_error( [
					'message' => __( 'Invalid request', 'emailchef-for-woocommerce' )
				] );
			}


			$this->wcec::deactivate();

			wp_send_json_success( [
				'message' => __( 'Emailchef account successfully disconnected', 'emailchef-for-woocommerce' )
			] );

		}

		public function add_list() {

			if ( ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'emailchef_add_list' ) ) {
				wp_send_json_error( [
					'message' => __( 'Invalid request', 'emailchef-for-woocommerce' )
				] );
			}

			if ( empty( $_POST['data']['list_name'] ) ) {
				wp_send_json_error( [
					'message' => __( 'Provide a name for this new list.', 'emailchef-for-woocommerce' )
				] );
			}
			$list_name = sanitize_text_field( $_POST['data']['list_name'] );
			$list_desc = sanitize_text_field( $_POST['data']['list_desc'] );

			$ecwc = $this->wcec->emailchef();

			$cl_id = $ecwc->create_list( $list_name, $list_desc );

			if ( $cl_id !== false ) {

				$result['type'] = "success";

				WCEC()->log(
					sprintf(
						__( "Successfully generated list (ID: %d, Name: %s, Description: %s)", "emailchef-for-woocommerce" ),
						$cl_id,
						$list_name,
						$list_desc
					)
				);

				wp_send_json_success( [
					'message' => __( "List successfully created.", "emailchef-for-woocommerce" ),
					'list_id' => $cl_id
				] );


			}

			WCEC()->log(
				sprintf(
					__( "Error occurred during the creation of the new list (Name: %s, Description: %s). Error details: %s",
						"emailchef-for-woocommerce" ),
					$list_name,
					$list_desc,
					$ecwc->lastError
				)
			);

			wp_send_json_error( [
				'message' => __( "An error occurred during the creation of the new list ",
						"emailchef-for-woocommerce" ) . $ecwc->lastError,
			] );

		}

		public static function get_instance() {

			if ( empty( self::$instance ) ) {
				self::$instance = new self;
			}

			return self::$instance;

		}

	}

}
