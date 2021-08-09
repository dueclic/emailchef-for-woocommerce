<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Emailchef_Settings' ) ) {
	class WC_Emailchef_Settings extends WC_Settings_Page {

		private static $instance;

		private $namespace;

		private function prefixed_setting( $value ) {
			return $this->namespace . '_' . $value;
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
			$this->namespace = "wc_" . $this->id;
			$this->label     = __( "eMailChef", "emailchef-for-woocommerce" );
			$this->init();
			$this->hooks();
		}

		public function init() {
			$this->api_user    = $this->get_option( 'api_user' );
			$this->api_pass    = $this->get_option( 'api_pass' );
			$this->enabled     = $this->get_option( 'enabled' );
			$this->policy_type = $this->get_option( 'policy_type' );
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

			$default_language = ( get_locale() == "it_IT" ? "it" : "en" );

			if ( '' === $current_section ) {
				$settings = array(

					array(
						'title' => '',
						'type'  => 'title',
						'desc'  => __( 'Configure your eMailChef account using your authentication data.',
							'emailchef-for-woocommerce' ),
						'id'    => 'general_options',
					),

				);

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
					'id'          => $this->prefixed_setting( 'api_user' ),
					'title'       => __( 'emailChef username',
						'emailchef-for-woocommerce' ),
					'type'        => 'text',
					'desc'        => sprintf( __( '%sSignup now in eMailChef for creating a new account.',
						'emailchef-for-woocommerce' ),
						'<br/><a href="https://www.emailchef.com" target="_blank">',
						'</a>'
					),
					'placeholder' => __( 'Provide your eMailChef username',
						'emailchef-for-woocommerce' ),
					'default'     => '',
					'css'         => 'min-width:350px;',
					'desc_tip'    => __( "You must provide eMailChef username for list synchronization.",
						'emailchef-for-woocommerce' ),
				);

				$settings[] = array(
					'id'          => $this->prefixed_setting( 'api_pass' ),
					'title'       => __( 'eMailChef password',
						'emailchef-for-woocommerce' ),
					'type'        => 'password',
					'placeholder' => __( 'Provide your eMailChef password',
						'emailchef-for-woocommerce' ),
					'default'     => '',
					'css'         => 'min-width:350px;',
					'desc_tip'    => __( 'You must provide eMailChef password for list synchronization.',
						'emailchef-for-woocommerce' ),
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
				__( 'No lists found in your eMailChef account',
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
					'no_lists' => __( 'No lists configured in your eMailChef account',
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
		}

		public function output() {
			global $current_section;

			?>
            <div class="emailchef-logo">
                <img src="<?php
				echo plugins_url( "img/emailchef.png",
					dirname( __FILE__ ) ); ?>">
            </div>
			<?php

			$settings = $this->get_settings( $current_section );

			WC_Admin_Settings::output_fields( $settings );

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
