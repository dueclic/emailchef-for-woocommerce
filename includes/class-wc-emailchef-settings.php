<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Emailchef_Settings' ) ) {
	class WC_Emailchef_Settings extends WC_Settings_Page {

		private static $instance;

		private $enabled;

		/**
		 * @var false|mixed|null
		 */
		private $consumer_key;

		/**
		 * @var false|mixed|null
		 */
		private $consumer_secret;

		/**
		 * @var false|mixed|null
		 */
		private $policy_type;

		private function prefixed_setting( $value ) {
			return apply_filters( 'wc_ec_add_prefix', $value );
		}

		public function get_option( $option ) {
			return get_option( $this->prefixed_setting( $option ) );
		}


		/**
		 * Instance
		 *
		 * @return WC_Emailchef_Settings
		 */

		public static function get_instance() {
			if ( empty( self::$instance ) ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		public function __construct() {
			$this->id    = "emailchef";
			$this->label = __( "Emailchef", "emailchef-for-woocommerce" );
			$this->init();
			$this->hooks();
		}

		public function init() {
			$this->consumer_key    = wc_ec_get_option_value( 'consumer_key' );
			$this->consumer_secret = wc_ec_get_option_value( 'consumer_secret' );
			$this->enabled         = wc_ec_get_option_value( 'enabled' );
			$this->policy_type     = wc_ec_get_option_value( 'policy_type' );
		}

		public function hooks() {
			add_filter( 'woocommerce_settings_tabs_array',
				array( $this, 'add_settings_page' ), 20 );
			add_action( 'woocommerce_settings_' . $this->id,
				array( $this, 'output' ) );
			add_action( 'woocommerce_settings_save_' . $this->id,
				array( $this, 'save' ) );
			add_action( 'woocommerce_sections_' . $this->id,
				array( $this, 'output_sections' ) );
			add_action( 'woocommerce_settings_saved', array( $this, 'init' ) );
		}

		private function wc_enqueue_js( $code ) {
			if ( function_exists( 'wc_enqueue_js' ) ) {
				wc_enqueue_js( $code );
			} else {
				global $woocommerce;
				$woocommerce->add_inline_js( $code );
			}
		}

		public function emailchef( $api_user = null, $api_pass = null ) {
			$wcec = WCEC();

			return $wcec->emailchef( $api_user, $api_pass );
		}

		public function get_settings( $current_section = '' ) {
			$settings = array();

			return apply_filters( 'woocommerce_get_settings_' . $this->id,
				$settings, $current_section );
		}

		private function send_msg( $message, $type = 'error' ) {
			ob_start();

			?>
            <div class="<?php
			echo $type ?>">
                <p><?php
					echo $message ?></p>
            </div>
			<?php
			return ob_get_clean();
		}

		public function emailchef_api_user_error_msg() {
			echo $this->send_msg(
				__( 'Failed to retrieve mailing lists. Please check if the API keys are correct and valid.',
					'emailchef-for-woocommerce' )
			);
		}

		public function save() {
			global $current_section;

			if ( 'yes' !== $this->enabled ) {
				$consumer_key    = sanitize_text_field( $_POST[ wc_ec_get_option_name( 'consumer_key' ) ] );
				$consumer_secret = sanitize_text_field( $_POST[ wc_ec_get_option_name( 'consumer_secret' ) ] );
				$account         = WCEC()->emailchef(
					$consumer_key,
					$consumer_secret
				)->account();

				if ( isset( $account['status'] ) && $account['status'] === 'error' ) {
					WC_Admin_Settings::add_error(
						__( 'Login attempt unsuccessful. Ensure your API keys are entered correctly.', 'emailchef-for-woocommerce' )
					);
				} else {
					update_option( wc_ec_get_option_name( 'consumer_key' ), $consumer_key );
					update_option( wc_ec_get_option_name( 'consumer_secret' ), $consumer_secret );
					update_option( wc_ec_get_option_name( 'enabled' ), "yes" );
					update_option( wc_ec_get_option_name( 'cron_end_interval_value' ), 24 );
				}

			} else {

				$sync_customers = isset( $_POST[ wc_ec_get_option_name( "sync_customers" ) ] ) && (boolean) sanitize_text_field(
						$_POST[ wc_ec_get_option_name( "sync_customers" ) ]
					);

				$fields = [
					'list'                    => (int) sanitize_text_field(
						$_POST[ wc_ec_get_option_name( "list" ) ]
					),
					'policy_type'             => sanitize_text_field(
						$_POST[ wc_ec_get_option_name( "policy_type" ) ]
					),
					'subscription_page'       => sanitize_text_field(
						$_POST[ wc_ec_get_option_name( "subscription_page" ) ]
					),
					'unsubscription_page'     => sanitize_text_field(
						$_POST[ wc_ec_get_option_name( "unsubscription_page" ) ]
					),
					'cron_end_interval_value' => sanitize_text_field(
						$_POST[ wc_ec_get_option_name( "cron_end_interval_value" ) ]
					),
				];

				if ( empty( $fields['list'] ) ) {
					WC_Admin_Settings::add_error(
						__( 'Choose an existing valid Emailchef mailing list or create a new one.', 'emailchef-for-woocommerce' )
					);

					return;
				}

				if ( $fields['cron_end_interval_value'] < 1 || $fields['cron_end_interval_value'] > 24 ) {
					WC_Admin_Settings::add_error(
						__( 'Number of hours for abandoned carts should be between 1 and 24.', 'emailchef-for-woocommerce' )
					);

					return;
				}

				$wcec = WCEC();

				foreach ( $fields as $name => $value ) {
					wc_ec_update_option( $name, $value );
				}

				$wcec->log( sprintf( __( "Plugin settings changed, selected Emailchef list: %d",
					"emailchef-for-woocommerce" ), $fields["list"] ) );
				$wcec->log( sprintf( __( "Selected Emailchef list: %d. Cron job executed for custom fields syncing",
					"emailchef-for-woocommerce" ), $fields['list'] ) );

				if ( $sync_customers ) {

					WC_Admin_Settings::add_message(
						__( 'Customer data and custom fields are currently synchronizing.', 'emailchef-for-woocommerce' )
					);

					$scheduled = wp_schedule_single_event( time(),
						"emailchef_sync_cron_now",
						array( $fields['list'], true ) );

					if ( false === $scheduled ) {
						$wcec->log( __( "First synchronization not scheduled.",
							"emailchef-for-woocommerce" ) );
					}
				} else {

					WC_Admin_Settings::add_message(
						__( 'Custom fields are currently synchronizing.', 'emailchef-for-woocommerce' )
					);

					$scheduled = wp_schedule_single_event( time(),
						"emailchef_sync_cron_now",
						array( $fields['list'], false ) );
					if ( false === $scheduled ) {
						$wcec->log( __( "Custom fields re-synchronised",
							"emailchef-for-woocommerce" ) );
					}
					$wcec->log( sprintf( __( "Initial synchronization option not selected for Emailchef list %d",
						"emailchef-for-woocommerce" ), $fields['list'] ) );
				}

			}

		}

		public function output() {
			$GLOBALS['hide_save_button'] = true;
			$enabled                     = $this->enabled;

			if ( 'yes' === $enabled ) {
				$wcec = WCEC();
				require_once( WC_EMAILCHEF_DIR . "/partials/settings/logged-in.php" );
			} else {
				require_once( WC_EMAILCHEF_DIR . "/partials/settings/logged-out.php" );
			}


			$this->wc_enqueue_js( '
	 			(function($){
	 				$(document).ready(function() {
	 				    WC_Emailchef.settings();
	 				});
	 			})(jQuery);
			' );

			do_action( 'ec_footer_copyright' );

			do_action( 'wc_emailchef_enqueue_js' );
		}


	}

	return WC_Emailchef_Settings::get_instance();
}
