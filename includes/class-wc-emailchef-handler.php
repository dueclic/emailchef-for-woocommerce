<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'WC_Emailchef_Handler' ) ) {

	final class WC_Emailchef_Handler {

		private static $instance = null;

		private $namespace;

		public function __construct() {

			$this->id        = 'emailchef';
			$this->namespace = 'wc_' . $this->id;
			$this->label     = __( 'emailChef', 'emailchef-for-woocommerce' );
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
				"Confirm your email to receive our newsletter",
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
			$wcec        = $this->wcec->emailchef();

			if ( $customer_id == 0 ) {
				$this->wcec->log( __( "Guest synchronization to eMailChef in progress", "emailchef-for-woocommerce" ) );
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
								"Insert failure in list %d for updated data of guest customer (Order %d from status %s to %s). Error: %s",
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
					sprintf( __( "Customer synchronization to eMailChef in progress (Customer ID: %d)",
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
								"Insert success in list %d for updated data of customer %d (Order %d from status %s to %s)",
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
								"Insert failure in list %d for updated data of customer %d (order id: %d, from status %s to %s). Error: %s",
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

			if ( $this->wcec->is_valid() ) {

				if ( $this->wcec->display_opt_in() ) {

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
						__( "Synchronization customer %d in list %d done (Newsletter status: %s).",
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
					sprintf( __( "Synchronization customer %d in list %d failed", "emailchef-for-woocommerce" ),
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
				$fuck_page = get_option( $this->prefixed_setting( "fuck_page" ) ) !== "" ? get_page_link(
					get_option( $this->prefixed_setting( "fuck_page" ) )
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
					wp_redirect( $fuck_page );
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
					'methods'  => 'GET',
					'callback' => array( $this, "subscribe_email" ),
					'args'     => array(
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
					'methods'  => 'GET',
					'callback' => array( $this, "emailchef_is_active" ),
					'args'     => array()
				)
			);

			register_rest_route(
				'emailchef',
				'/unsubscribe/(?P<list_id>\d+)/(?P<customer_email>(.*))',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, "unsubscribe_email" ),
					'args'     => array(
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
			if ( $wcec->emailchef()->isLogged() && $wcec->emailchef()->get_policy() !== 'premium' ) {
				update_option( $this->prefixed_setting( "policy_type" ), "dopt" );
			}

		}

		public static function version_check( $version = '3.0' ) {
			if ( class_exists( 'WooCommerce' ) ) {
				global $woocommerce;
				if ( version_compare( $woocommerce->version, $version, ">=" ) ) {
					return true;
				}
			}

			return false;
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
									"Abandoned cart successfully emptied for customer %d in table %s",
									"emailchef-for-woocommerce"
								),
								$customer_id,
								$abc
							)
						);
					} else {
						$this->wcec->log(
							sprintf(
								__( "Abandoned carty emptying failed for customer %d in table %s",
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
								"Synchronization of abandoned cart for user %d in table %s successfully done",
								"emailchef-for-woocommerce"
							),
							$customer_id,
							$abc
						)
					);
				} else {
					$this->wcec->log(
						sprintf(
							__( "Synchronization of abandoned cart for user %d in table %s successfully failed",
								"emailchef-for-woocommerce" ),
							$customer_id,
							$abc
						)
					);
				}

			}

		}

		public function hooks() {

			/**
			 * Choice position of eMailChef consent
			 */

			add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'add_emailchef_consent' ) );

			/**
			 * Save eMailChef consent
			 */

			add_action( 'user_register', array( $this, 'save_emailchef_consent' ), 10, 1 );
			add_action( 'woocommerce_edit_account_form', array( $this, 'emailchef_optin_extra_fields_edit' ) );
			add_action( 'woocommerce_save_account_details', array( $this, 'emailchef_optin_extra_fields_edit_save' ) );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'order_status_changed' ), 1000, 1 );
			add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 3 );
			add_action( 'woocommerce_cart_updated', array( $this, 'sync_cart' ), 10 );
			//add_action('woocommerce_cart_item_removed', array($this, 'remove_from_cart'), 10, 1);

			add_action( 'rest_api_init', array( $this, "rest_route" ) );

			add_action( 'wp_ajax_' . $this->namespace . '_account', array( $this, "get_account" ) );
			add_action( 'wp_ajax_' . $this->namespace . '_move_abandoned_carts',
				array( $this, 'move_abandoned_carts_trigger' ) );
			add_action( 'wp_ajax_nopriv_' . $this->namespace . '_move_abandoned_carts',
				array( $this, 'move_abandoned_carts_trigger' ) );

			add_action( 'wp_ajax_' . $this->namespace . '_lists', array( $this, 'get_lists' ) );
			add_action( 'wp_ajax_' . $this->namespace . '_changelanguage', array( $this, 'change_language' ) );
			add_action( 'wp_ajax_' . $this->namespace . '_add_list', array( $this, 'add_list' ) );
			add_action( 'wp_ajax_' . $this->namespace . '_sync_abandoned_carts', array(
				$this,
				'sync_abandoned_carts'
			) );
			add_action( 'upgrader_process_complete', array( $this, 'upgrade_also_list' ), 10, 2 );
			add_action(
				'wp_ajax_nopriv_' . $this->namespace . '_sync_abandoned_carts',
				array( $this, 'sync_abandoned_carts' )
			);

			add_action( 'wc_emailchef_loaded', array( $this, 'check_policy' ) );
			add_action( "emailchef_sync_cron_now", array( $this, 'sync_list_now' ), 1, 2 );
			//add_action("admin_bar_menu", array($this, "move_abandoned_carts"), 999);

			register_activation_hook( WC_EMAILCHEF_FILE, array( $this, 'create_ab_cart_table' ) );
			register_deactivation_hook( WC_EMAILCHEF_FILE, array( $this, 'delete_ab_cart_table' ) );

			add_action( 'wp_footer', array( $this, 'maybe_abandoned_cart_sync' ) );
			add_action( 'wp_footer', array( $this, 'trigger_send_abandoned_carts' ) );

			add_action( 'admin_footer', array( $this, 'trigger_send_abandoned_carts' ) );
			add_action( 'admin_footer', array( $this, 'maybe_abandoned_cart_sync' ) );

			add_action( 'admin_menu', array( $this, 'add_debug_page' ), 10 );

		}

		public function add_debug_page() {
			$debug_name = 'eMailChef DEBUG';
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
			require_once( WC_EMAILCHEF_DIR . "/pages/admin-debug.php" );
		}


		public function trigger_send_abandoned_carts() {
			?>
            <script type="text/javascript">
                jQuery(function ($) {
                    var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';

                    $("#wp-admin-bar-wc_ec_mabc a").on("click", function (evt) {
                        evt.preventDefault();
                        $.post(
                            ajaxurl,
                            {
                                'action': '<?php echo $this->namespace; ?>_move_abandoned_carts'
                            },
                            function (response) {
                                console.log("Abandoned carts moved successfully");
                                location.reload();
                            }
                        );

                    });


                });
            </script>
			<?php
		}

		/**
		 * @param $wp_admin_bar WP_Admin_Bar
		 */

		public function move_abandoned_carts( $wp_admin_bar ) {

			if ( current_user_can( 'manage_options' ) ) {
				$args = array(
					'id'    => 'wc_ec',
					'title' => __( 'eMailChef', 'emailchef-for-woocommerce' ),
					'href'  => '#',
					'meta'  => array( 'class' => 'my-empty-cart' )
				);
				$wp_admin_bar->add_node( $args );
				$wp_admin_bar->add_menu( array(
					'parent' => "wc_ec",
					'title'  => __( 'Send abandoned carts', 'emailchef-for-woocommerce' ),
					'id'     => 'wc_ec_mabc',
					'href'   => '#'
				) );

			}
		}

		/**
		 * This function runs when WordPress completes its upgrade process
		 * It iterates through each plugin updated to see if ours is included
		 *
		 * @param $upgrader_object Array
		 * @param $options Array
		 */

		function wp_upe_upgrade_completed( $upgrader_object, $options ) {
			// The path to our plugin's main file
			$our_plugin = plugin_basename( WC_EMAILCHEF_FILE );
			// If an update has taken place and the updated type is plugins and the plugins element exists
			if ( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
				// Iterate through the plugins being updated and check if ours is there
				foreach ( $options['plugins'] as $plugin ) {
					if ( $plugin == $our_plugin && $this->wcec->emailchef()->isLogged() ) {
						$list_id = get_option( $this->prefixed_setting( "list" ) );
						$this->wcec->emailchef()->upsert_integration( $list_id );
					}
				}
			}
		}

		public function get_abandoned_carts( $limit = true, $where = "" ) {
			global $wpdb;

			$abc = $this->wcec->abcart_table();

			$basic_query = "SELECT user_id, user_email, product_id, created FROM {$abc} WHERE synced = 0";

			if ( $limit ) {
				$basic_query .= " AND created > (NOW() - INTERVAL " . apply_filters( 'ec_get_abandoned_carts_start_day', 7 ) . " DAY) AND created < (NOW() - INTERVAL " . apply_filters( 'ec_get_abandoned_carts_end_day', 1 ) . " DAY)";
			}

			$basic_query .= " " . $where;

			return $wpdb->get_results(
				$basic_query,
				ARRAY_A
			);
		}

		public function sync_abandoned_carts() {

			global $wpdb;

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

				if ( self::version_check() ) {
					$image             = $product->get_gallery_image_ids()[0];
					$name              = $product->get_name();
					$short_description = $product->get_short_description();
				} else {
					$image             = $product->get_gallery_attachment_ids()[0];
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
								"Synchronization of abandoned cart for user %d in eMailChef list %d successfully done",
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
							__( "Synchronization of abandoned cart for user %d in eMailChef list %d failed",
								"emailchef-for-woocommerce" ),
							$customer_id,
							$list_id
						)
					);
				}

			}

			die(
			wp_json_encode(
				array(
					"type" => "success",
					"text" => __( "Abandoned cart was successfully synced",
						"emailchef-for-woocommerce" )
				)
			)
			);


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

		public function sync_list_now( $list, $all = true ) {
			$this->wcec->emailchef()->upsert_integration( $list );
			$this->wcec->emailchef()->sync_list( $list, $all );
			if ( $all ) {
				$this->wcec->log( sprintf( __( "Synchronization and custom fields creation for eMailChef list %d",
					"emailchef-for-woocommerce" ), $list ) );
			} else {
				$this->wcec->log( sprintf( __( "Custom fields creation for eMailChef list %d",
					"emailchef-for-woocommerce" ), $list ) );
			}

		}

		public function maybe_abandoned_cart_sync() {

			global $wpdb;

			if ( $this->wcec->is_valid() ) {

				$abc = $this->wcec->abcart_table();

				$results = $wpdb->get_results(
					"SELECT user_id, user_email, product_id, created FROM {$abc} WHERE synced = 0 AND created > (NOW() - INTERVAL 7 DAY) AND created < (NOW() - INTERVAL 1 DAY)",
					ARRAY_A
				);

				if ( count( $results ) > 0 ) {

					?>
                    <script type="text/javascript">
                        jQuery(function ($) {
                            var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
                            $.post(
                                ajaxurl,
                                {
                                    'action': '<?php echo $this->namespace; ?>_sync_abandoned_carts'
                                },
                                function (response) {
                                    console.log("Abandoned carts synced successfully")
                                }
                            );


                        });
                    </script>
					<?php

				}
			}

		}

		private function json( $data ) {
			echo json_encode( $data );
			exit;
		}

		public function change_language() {
			update_option( $this->prefixed_setting( "lang" ), $_POST['data']['lang'] );
			$result = array(
				'type' => 'success',
				'msg'  => __( 'Language was loaded, do you want refresh this page with new language?',
					'emailchef-for-woocommerce' ),
			);
			$this->json( $result );
		}

		public function move_abandoned_carts_trigger() {

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
		}

		public function get_lists() {

			$result = array(
				'type' => 'error',
				'msg'  => __( 'User or password are wrong', 'emailchef-for-woocommerce' ),
			);

			if ( ! $_POST['data']['api_user'] || empty( $_POST['data']['api_user'] ) || ! $_POST['data']['api_pass'] || empty( $_POST['data']['api_pass'] ) ) {

				$result['msg'] = __( 'Insert your username and password to display your lists',
					'emailchef-for-woocommerce' );
				$this->json( $result );

			}

			$api_user = $_POST['data']['api_user'];
			$api_pass = $_POST['data']['api_pass'];

			$lists = $this->wcec->emailchef( $api_user, $api_pass )->wrap_list();
			unset( $result['msg'] );
			$result['type']  = 'success';
			$result['lists'] = $lists;

			$this->json( $result );
		}

		public function get_account() {

			$result = array(
				'type' => 'error',
				'msg'  => __( 'User or password are wrong', 'emailchef-for-woocommerce' ),
			);

			if ( ! $_POST['data']['api_user'] || empty( $_POST['data']['api_user'] ) || ! $_POST['data']['api_pass'] || empty( $_POST['data']['api_pass'] ) ) {

				$result['msg'] = __( 'Provide your username and password', 'emailchef-for-woocommerce' );
				$this->json( $result );

			}

			$api_user = $_POST['data']['api_user'];
			$api_pass = $_POST['data']['api_pass'];

			$wcec = $this->wcec->emailchef( $api_user, $api_pass );

			if ( $wcec->isLogged() ) {

				$result['type']   = 'success';
				$result['msg']    = __( 'Valid username and password', 'emailchef-for-woocommerce' );
				$result['policy'] = $wcec->get_policy();

			}

			$this->json( $result );

		}

		public function add_list() {

			$result = array(
				'type' => 'error',
				'msg'  => __( 'User or password are wrong', 'emailchef-for-woocommerce' ),
			);

			if ( ! $_POST['data']['api_user'] || empty( $_POST['data']['api_user'] ) || ! $_POST['data']['api_pass'] || empty( $_POST['data']['api_pass'] ) ) {

				$result['msg'] = __( 'Provide your username and password.', 'emailchef-for-woocommerce' );
				$this->json( $result );

			}

			if ( ! $_POST['data']['list_name'] || empty( $_POST['data']['list_name'] ) ) {

				$result['msg'] = __( 'Provide a name for this new list.', 'emailchef-for-woocommerce' );
				$this->json( $result );

			}

			$api_user = $_POST['data']['api_user'];
			$api_pass = $_POST['data']['api_pass'];

			$list_name = $_POST['data']['list_name'];
			$list_desc = $_POST['data']['list_desc'];

			$ecwc = $this->wcec->emailchef( $api_user, $api_pass );

			$isLogged = $ecwc->isLogged();

			if ( ! $isLogged ) {
				$this->json( $result );
			}

			$cl_id = $ecwc->create_list( $list_name, $list_desc );

			if ( $cl_id !== false ) {

				$result['type']    = "success";
				$result['msg']     = __( "List successfully created.", "emailchef-for-woocommerce" );
				$result['list_id'] = $cl_id;

				WCEC()->log(
					sprintf(
						__( "List created sucessfully (ID: %d, name: %s, description: %s)", "emailchef-for-woocommerce" ),
						$cl_id,
						$list_name,
						$list_desc
					)
				);


			} else {

				$result['type'] = "error";
				$result['msg']  = __( "Error occurs while creating the new list: ",
						"emailchef-for-woocommerce" ) . $ecwc->lastError;

				WCEC()->log(
					sprintf(
						__( "Error occurs while creating the new list (Name: %s, Description: %s). Error: %s",
							"emailchef-for-woocommerce" ),
						$list_name,
						$list_desc,
						$ecwc->lastError
					)
				);

			}

			$this->json( $result );

		}

		public static function get_instance() {

			if ( empty( self::$instance ) ) {
				self::$instance = new self;
			}

			return self::$instance;

		}

	}

}
