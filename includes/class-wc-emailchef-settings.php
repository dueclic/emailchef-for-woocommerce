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
			return apply_filters('wc_ec_add_prefix', $value);
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
			$this->id        = "emailchef";
			$this->label     = __( "Emailchef", "emailchef-for-woocommerce" );
			$this->init();
			$this->hooks();
		}

		public function init() {
			$this->consumer_key    = wc_ec_get_option_value( 'consumer_key' );
			$this->consumer_secret    = wc_ec_get_option_value( 'consumer_secret' );
			$this->enabled     = wc_ec_get_option_value( 'enabled' );
			$this->policy_type = wc_ec_get_option_value( 'policy_type' );
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
            add_action('ec_wc_api_response', array($this, 'logout_if_unauth_request'), 5 );
		}

		/**
		 * @param array | WP_Error $response
		 *
		 * @void
		 */

        public function logout_if_unauth_request(
                $response
        ){

            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code === 401){
	            update_option( $this->prefixed_setting( 'enabled' ), "no" );
            }

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

			$default_language = ( get_locale() == "it_IT" ? "it" : "en" );

			if ( '' === $current_section ) {

                /*

				$settings[] = array(
					'id'       => $this->prefixed_setting( 'lang' ),
					'title'    => __( 'Language', 'emailchef-for-woocommerce' ),
					'type'     => 'select',
					'options'  => array(
						"it" => __( "Italian", "emailchef-for-woocommerce" ),
						"en" => __( "English", "emailchef-for-woocommerce" ),
					),
					'default'  => $default_language,
					'class'    => 'wc-enhanced-select-nostd',
					'css'      => 'min-width: 350px;',
					'desc_tip' => __( 'You can choose your favorite language',
						'emailchef-for-woocommerce' ),
				);

				$settings[] = array(
					'id'          => $this->prefixed_setting( 'consumer_key' ),
					'title'       => __( 'Consumer Key',
						'emailchef-for-woocommerce' ),
					'type'        => 'text',
					'desc'        => sprintf( __( '%sSignup now in Emailchef for creating a new account.',
						'emailchef-for-woocommerce' ),
						'<br/><a href="https://www.emailchef.com" target="_blank">',
						'</a>'
					),
                    'section_id' => 'login'
				);

				$settings[] = array(
					'id'          => $this->prefixed_setting( 'consumer_secret' ),
					'title'       => __( 'Consumer Secret',
						'emailchef-for-woocommerce' ),
					'type'        => 'password',
					'section_id' => 'login'
				);

				$lists = $this->get_lists();

				$settings[] = array(
					'id'       => $this->prefixed_setting( 'list' ),
					'title'    => __( 'List', 'emailchef-for-woocommerce' ),
					'type'     => 'select',
					'desc'     => sprintf( __( '%sAdd a new destination list.',
						'emailchef-for-woocommerce' ), '<br/><a href="#" id="'
					                                   . $this->prefixed_setting( 'create_list' )
					                                   . '">', '</a>'
					),
					'options'  => $lists,
					'class'    => 'wc-enhanced-select-nostd',
					'css'      => 'min-width: 350px;',
					'desc_tip' => __( 'Select your destination list or create a new.',
						'emailchef-for-woocommerce' ),
					'section_id' => 'settings_up',
				);

				$settings[] = array(
					'name'     => __( 'Sync customers',
						'emailchef-for-woocommerce' ),
					'type'     => 'checkbox',
					'id'       => $this->prefixed_setting( 'sync_customers' ),
					'default'  => '',
				);

				$settings[] = array(
					'name'     => __( 'Policy', 'emailchef-for-woocommerce' ),
					'type'     => 'select',
					'desc'     => __( 'Which policy would you like to use?',
						'emailchef-for-woocommerce' ),
					'desc_tip' => true,
					'id'       => $this->prefixed_setting( 'policy_type' ),
					'options'  => array(
						'sopt' => __( 'Single opt-in',
							'emailchef-for-woocommerce' ),
						'dopt' => __( 'Double opt-in',
							'emailchef-for-woocommerce' ),
					),
					'default'  => 'dopt',
				);

				$settings[] = array(
					'name'     => __( 'Subscription page',
						'emailchef-for-woocommerce' ),
					'type'     => 'single_select_page',
					'desc'     => __( 'Page where customer moved after subscribe newsletter in double opt-in',
						'emailchef-for-woocommerce' ),
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select-nostd',
					'id'       => $this->prefixed_setting( 'landing_page' ),
					'default'  => '',
				);

				$settings[] = array(
					'name'     => __( 'Unsubscription page',
						'emailchef-for-woocommerce' ),
					'type'     => 'single_select_page',
					'desc'     => __( 'Page where customer moved after unsubscribe newsletter in double opt-in',
						'emailchef-for-woocommerce' ),
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select-nostd',
					'id'       => $this->prefixed_setting( 'fuck_page' ),
					'default'  => '',
				);



				$settings[] = array(
					'type' => 'sectionend',
					'id'   => 'general_options',
				);
                */
			}

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
				__( 'Unable to display lists. Username and password are valid?',
					'emailchef-for-woocommerce' )
			);
		}

		public function emailchef_no_lists_found() {
			echo $this->send_msg(
				__( 'No lists found in your Emailchef account',
					'emailchef-for-woocommerce' )
			);
		}

		public function get_lists() {
			if ( $this->emailchef() ) {
				$lists = $this->emailchef()->wrap_list();
			} else {
				return false;
			}

			if ( $lists === false ) {
				add_action( 'admin_notices',
					array( $this, 'emailchef_api_user_error_msg' ) );
				add_action( 'network_admin_notices',
					array( $this, 'emailchef_api_user_error_msg' ) );

				return false;
			}

			if ( count( $lists ) === 0 ) {
				$default = array(
					'no_lists' => __( 'No lists configured in your Emailchef account',
						'emailchef-for-woocommerce' ),
				);

				add_action( 'admin_notices',
					array( $this, 'emailchef_no_lists_found' ) );

				$lists = array_merge( $default, $lists );
			}

			return $lists;
		}

		public function save() {
			global $current_section;

            if ('yes' !== $this->enabled){
                $consumer_key = sanitize_text_field($_POST[$this->prefixed_setting('consumer_key')]);
                $consumer_secret = sanitize_text_field($_POST[$this->prefixed_setting('consumer_secret')]);
                $account = WCEC()->emailchef(
                    $consumer_key,
                    $consumer_secret
                )->account();

                if (isset($account['status']) && $account['status'] === 'error') {
                    WC_Admin_Settings::add_error(
                            __('Login failed. Please check your credentials.', 'emailchef-for-woocommerce')
                    );
                } else {
                    update_option( $this->prefixed_setting( 'consumer_key' ), $consumer_key );
	                update_option( $this->prefixed_setting( 'consumer_secret' ), $consumer_secret );
	                update_option( $this->prefixed_setting( 'enabled' ), "yes" );
                }

            }


            /*

			$previous_list = $this->get_option( "list" );

			$settings = $this->get_settings( $current_section );

			WC_Admin_Settings::save_fields( $settings );

			update_option( 'wc_emailchef_list', $_POST['wc_emailchef_list'] );

			if ( ! isset( $_POST['wc_emailchef_api_user'] )
			     || empty( $_POST['wc_emailchef_api_user'] )
			     || ! isset( $_POST['wc_emailchef_api_pass'] )
			     || empty( $_POST['wc_emailchef_api_pass'] )
			) {
				delete_transient( 'ecwc_lists' );
			}

			$wcec = WCEC();
			$wcec->settings( true );

			$wcec->log( sprintf( __( "Plugin settings changed, selected list %d",
				"emailchef-for-woocommerce" ), $_POST['wc_emailchef_list'] ) );
			$wcec->log( sprintf( __( "Selected list %d, execution of cron for custom fields synchronization",
				"emailchef-for-woocommerce" ), $_POST['wc_emailchef_list'] ) );
			if (isset($_POST['wc_emailchef_sync_customers'])){
				$scheduled = wp_schedule_single_event( time(),
					"emailchef_sync_cron_now",
					array( $_POST['wc_emailchef_list'], true ) );

				if ( false === $scheduled ) {
					$wcec->log( __( "First synchronization not scheduled.",
						"emailchef-for-woocommerce" ) );
				}
			} else {
				$scheduled = wp_schedule_single_event( time(),
					"emailchef_sync_cron_now",
					array( $_POST['wc_emailchef_list'], false ) );
				if ( false === $scheduled ) {
					$wcec->log( __( "Custom fields re-syncronised",
						"emailchef-for-woocommerce" ) );
				}
				$wcec->log( sprintf( __( "First synchronization not choosed for list %d",
					"emailchef-for-woocommerce" ), $_POST['wc_emailchef_list'] ) );
			}
            */
		}

		public function output() {
			$GLOBALS['hide_save_button'] = true;
            $enabled = $this->enabled;

            if ('yes' === $enabled){
                $wcec = WCEC();
	            require_once( WC_EMAILCHEF_DIR . "/partials/settings/logged-in.php" );
            } else {
	            $input_consumerkey_name = $this->prefixed_setting('consumer_key');
	            $input_consumersecret_name = $this->prefixed_setting('consumer_secret');
	            require_once( WC_EMAILCHEF_DIR . "/partials/settings/logged-out.php" );
            }


			$this->wc_enqueue_js( '
	 			(function($){
	 				$(document).ready(function() {
	 				    WC_Emailchef.go();
	 				});
	 			})(jQuery);
			' );

			do_action( 'ec_footer_copyright' );

			do_action( 'wc_emailchef_enqueue_js' );
		}


	}

	return WC_Emailchef_Settings::get_instance();
}
