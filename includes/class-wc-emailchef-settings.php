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
			$this->label     = __( "Emailchef", "emailchef-for-woocommerce" );
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
						'desc'  => __( 'Configure your Emailchef account using your authentication data.',
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
					'desc'        => sprintf( __( '%sSignup now in Emailchef for creating a new account.',
						'emailchef-for-woocommerce' ),
						'<br/><a href="https://www.emailchef.com" target="_blank">',
						'</a>'
					),
					'placeholder' => __( 'Provide your Emailchef username',
						'emailchef-for-woocommerce' ),
					'default'     => '',
					'css'         => 'min-width:350px;',
					'desc_tip'    => __( "You must provide Emailchef username for list synchronization.",
						'emailchef-for-woocommerce' ),
				);

				$settings[] = array(
					'id'          => $this->prefixed_setting( 'api_pass' ),
					'title'       => __( 'Emailchef password',
						'emailchef-for-woocommerce' ),
					'type'        => 'password',
					'placeholder' => __( 'Provide your Emailchef password',
						'emailchef-for-woocommerce' ),
					'default'     => '',
					'css'         => 'min-width:350px;',
					'desc_tip'    => __( 'You must provide Emailchef password for list synchronization.',
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
				__( 'No lists found in your Emailchef account',
					'emailchef-for-woocommerce' )
			);
		}

		public function get_lists() {
			if ( $this->emailchef()->isLogged() ) {
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
            
            <style>

                :root{
                    --ecwc-error-color: #D73638;
                    --ecwc-success-color: #01A32A;
                }
                /***/
                .truncate {
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                .ecwc-not-logged{
                    display: block;
                    margin-top: 1em;
                    width: 280px;
                    margin-left: auto;
                    margin-right: auto;
                }
                .ecwc-not-logged-signup{
                    margin-bottom: 2.5em;
                }
                .ecwc-not-logged label{
                    display: block;
                    margin-bottom: .5em;
                    font-size: 1.2em;
                }
                .ecwc-not-logged input[ type="text" ],
                .ecwc-not-logged input[ type="password"]{
                    display: block;
                    width: 100%;
                }
                .ecwc-not-logged-control-group{
                    margin-bottom: 1.5em;
                }
                .ecwc-not-logged label.ecwc-not-logged-get-api{
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .ecwc-not-logged label.ecwc-not-logged-get-api a{
                    font-size: .8em;
                }
                .ecwc-text-center,
                .ecwc-text-center p{
                    text-align: center;
                }
                .ecwc-not-logged-password-field{
                    position: relative;
                }
                .ecwc-not-logged-password-field input{
                    padding-right: 36px;
                }
                .ecwc-not-logged-password-field a {
                    cursor: pointer;
                    position: absolute;
                    right: 0;
                    bottom: 0;
                    display: flex;
                    height: 32px;
                    width: 36px;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.2s;
                    opacity: .5;
                }
                .ecwc-not-logged-password-field a:hover {
                    opacity: 1;
                }
                .ecwc-not-logged-password-field a svg {
                    width: 18px;
                }
                .ecwc-login-logo{
                    width: 160px;
                    margin: 3em auto 0;
                }

                .ecwc-main-container{
                    display: flex;
                    margin-top: 1em ;
                    margin-bottom: 1em ;
                }
                .ecwc-main-container > div:first-child{
                    border-right: 1px solid #dddddd;
                    padding-right: 1em;
                }
                .ecwc-main-container > div:last-child{
                    padding-left: 1em;
                }
                .ecwc-main-account{
                    flex-grow: 0;
                    min-width: 260px;
                }
                .ecwc-main-forms{
                    flex-grow: 1;
                }
                .ecwc-forms-logo{
                    display: flex;
                    justify-content: space-between;
                    margin-top: 1rem;
                    align-items: start;
                }
                .ecwc-forms-logo svg{
                    width: 100px;
                }
                .ecwc-account-status{
                    display: flex;
                    align-items: center;
                    gap: .5em;
                    font-size: .9em;
                    margin-bottom: 2rem;
                }
                .ecwc-account-connected{
                    width: 10px;
                    height: 10px;
                    border-radius: 50%;
                    background-color: var(--ecwc-success-color);
                }
                .ecwc-account-info {
                    margin-top: .5rem;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: .3rem;
                }
                .ecwc-account-disconnect {
                    display: inline-flex;
                    border-radius: 3px;
                    align-items: center;
                    justify-content: center;
                    border: none;
                    width: 24px;
                    height: 24px;
                    cursor: pointer;
                }
                .ecwc-account-disconnect:hover {
                    background-color: #d5d5d5;
                }
                .ecwc-account-disconnect svg {
                    fill: #616161;
                    height: 14px;
                    width: 14px;
                }



                .error-field{
                    color: var(--ecwc-error-color);
                    display: block;
                }

                .emailchef-form {
                    /*width: 100% !important;*/
                    max-width: 100% !important;
                    margin-right: 1.5em;
                }
                .emailchef-form .map-reload {
                    display: none;
                }

                .emailchef-form .form-table td.nopadding {
                    padding: 0;
                    max-width:170px !important;
                }
                .emailchef-form .form-table input[type=text],
                .emailchef-form .form-table input[type=password],
                .emailchef-form .form-table textarea,
                .emailchef-form .form-table select
                {
                    background-color: #fff;
                    width: 200px;
                }
                .emailchef-form select {
                    max-width:100% !important;
                }

                .emailchef-form .save {
                    margin: 0 0 0 10px !important;
                }

                .emailchef-check-login-result {
                    margin-top: 1em;
                    display: none;
                }

                input[name="emailchef_settings\[consumer_key\]"].error,
                input[name="emailchef_settings\[consumer_secret\]"].error {
                    border-color: var(--ecwc-error-color);
                }

                input[name="emailchef_settings\[consumer_key\]"].valid,
                input[name="emailchef_settings\[consumer_secret\]"].valid {
                    border-color: var(--ecwc-success-color);
                }
                /*
                .js .emailchef-form .control-section.open .accordion-section-title{
                    color: #FFF;
                    background: #F47200;
                }
                .js .emailchef-form  .control-section.open .accordion-section-title .not-connected{
                    color: #FFF;
                }
                .control-section.open .accordion-section-title:after{
                    color: #FFF;
                }


                .accordion-section .not-connected {
                    display: inline-block;
                    float: right;
                    margin-right: 20px;
                    color: var(--ecwc-error-color);
                }
                .accordion-section.active .not-connected {
                    display: none;
                }
                .accordion-section .loading{
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100px;
                }
                .accordion-section-content{
                    border-left: 1px solid #dddddd;
                    border-right: 1px solid #dddddd;
                    background-color: #f8f8f8;
                }
                .accordion-section .form-table-container{
                    border:1px solid #ccc;
                    padding:.5em;
                    margin-top:.5em;
                    margin-bottom: 1em;
                }
                .accordion-section.warning .form-table-container,
                .accordion-section.warning .list-id{
                    border-color:var(--ecwc-error-color);
                }
                .accordion-section .warning-select-list{
                    display:none;
                    color:var(--ecwc-error-color);
                }
                .accordion-section.warning .warning-select-list{
                    display:block;
                }
                .accordion-section.warning-map .form-table-container{
                    border-color:var(--ecwc-error-color);
                }
                .accordion-section.warning-map .at-least-email{
                    color:var(--ecwc-error-color);
                }
                .accordion-section .form-table td,
                .accordion-section .form-table th{
                    border-bottom: 1px solid #ddd;
                }
                .accordion-section .form-table th{
                    font-size: 1.1em;
                }
                .accordion-section .form-table tr:last-child td,
                .accordion-section .form-table tr:last-child th{
                    border: none;
                }
                .accordion-section .form-table th {
                    padding: .5em;
                }
                .accordion-section .form-table td {
                    padding: .5em;
                }
                .auto-create{
                    text-align: center;
                    padding-bottom: 1em;
                }
                */
                .ecwc-new-list-container{
                    border: 1px solid #dddddd;
                    background-color: #f8f8f8;
                    padding: 1rem;
                }
                .ecwc-new-list-container label{
                    padding: 0;
                    margin-bottom: .4rem;
                    display: block;
                    font-weight: 600;
                }
                .ecwc-new-list-container input[type=text]{
                    margin-bottom: 1rem !important;
                }
                .ecwc-new-list-container .ecwc-buttons-container {
                    display: flex;
                    gap: .5rem;
                    margin-top: .5rem;
                }

            </style>

            <!-- BOF user not logged -->

            <div class="ecwc-not-logged-container">


                <div class="ecwc-not-logged" id="ecwc-not-logged">

                    <div class="ecwc-text-center">
                        <img class="ecwc-login-logo" src="<?php
                        echo plugins_url( "dist/img/logo-compact.svg",
                            dirname( __FILE__ ) ); ?>">
                    </div>

                    <p class="ecwc-text-center ecwc-not-logged-signup">
                        Not a member? <a target="_blank" href="https://app.emailchef.com/apps/demo/quicksignup">Sign up for free</a>.    </p>

                    <fieldset>

                        <div class="ecwc-not-logged-control-group">

                            <label for="consumer_key" class="ecwc-not-logged-get-api">
                                Consumer Key:
                                <a href="https://app.emailchef.com/build/#/settings/apikeys" target="_blank" class="ecwc-get-api">Get API Key</a>
                            </label>

                            <input class="ecwc-input" type="text" value="" id="consumer_key" name="emailchef_settings[consumer_key]">

                        </div>

                        <div class="ecwc-not-logged-control-group ecwc-not-logged-password-field">

                            <a id="showPassword" title="Show Consumer Secret">

                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                                    <path d="M288 80c-65.2 0-118.8 29.6-159.9 67.7C89.6 183.5 63 226 49.4 256c13.6 30 40.2 72.5 78.6 108.3C169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256c-13.6-30-40.2-72.5-78.6-108.3C406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1c3.3 7.9 3.3 16.7 0 24.6c-14.9 35.7-46.2 87.7-93 131.1C433.5 443.2 368.8 480 288 480s-145.5-36.8-192.6-80.6C48.6 356 17.3 304 2.5 268.3c-3.3-7.9-3.3-16.7 0-24.6C17.3 208 48.6 156 95.4 112.6zM288 336c44.2 0 80-35.8 80-80s-35.8-80-80-80c-.7 0-1.3 0-2 0c1.3 5.1 2 10.5 2 16c0 35.3-28.7 64-64 64c-5.5 0-10.9-.7-16-2c0 .7 0 1.3 0 2c0 44.2 35.8 80 80 80zm0-208a128 128 0 1 1 0 256 128 128 0 1 1 0-256z"></path>
                                </svg>

                            </a>

                            <a id="hidePassword" style="display: none" title="Hide Consumer Secret">

                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                                    <path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L525.6 386.7c39.6-40.6 66.4-86.1 79.9-118.4c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C465.5 68.8 400.8 32 320 32c-68.2 0-125 26.3-169.3 60.8L38.8 5.1zm151 118.3C226 97.7 269.5 80 320 80c65.2 0 118.8 29.6 159.9 67.7C518.4 183.5 545 226 558.6 256c-12.6 28-36.6 66.8-70.9 100.9l-53.8-42.2c9.1-17.6 14.2-37.5 14.2-58.7c0-70.7-57.3-128-128-128c-32.2 0-61.7 11.9-84.2 31.5l-46.1-36.1zM394.9 284.2l-81.5-63.9c4.2-8.5 6.6-18.2 6.6-28.3c0-5.5-.7-10.9-2-16c.7 0 1.3 0 2 0c44.2 0 80 35.8 80 80c0 9.9-1.8 19.4-5.1 28.2zm9.4 130.3C378.8 425.4 350.7 432 320 432c-65.2 0-118.8-29.6-159.9-67.7C121.6 328.5 95 286 81.4 256c8.3-18.4 21.5-41.5 39.4-64.8L83.1 161.5C60.3 191.2 44 220.8 34.5 243.7c-3.3 7.9-3.3 16.7 0 24.6c14.9 35.7 46.2 87.7 93 131.1C174.5 443.2 239.2 480 320 480c47.8 0 89.9-12.9 126.2-32.5l-41.9-33zM192 256c0 70.7 57.3 128 128 128c13.3 0 26.1-2 38.2-5.8L302 334c-23.5-5.4-43.1-21.2-53.7-42.3l-56.1-44.2c-.2 2.8-.3 5.6-.3 8.5z"></path>
                                </svg>

                            </a>

                            <label for="consumer_secret">Consumer Secret:</label>

                            <input class="ecwc-input" type="password" id="consumer_secret" value="" name="emailchef_settings[consumer_secret]">

                        </div>

                        <div class="ecwc-text-center">

                            <input type="button" id="ecwc-login-submit" class="button button-primary" value="Login">

                        </div>

                    </fieldset>

                    <div class="emailchef-check-login-result notice notice-alt"></div>

                </div>
                
                
            </div>

            <!-- EOF user not logged -->
            
            <!-- BOF user logged -->

            <div class="ecwc-main-container">
                <div class="ecwc-main-account">
                    <div class="ecwc-forms-logo">
                        <img src="<?php
                        echo plugins_url( "dist/img/logo-compact.svg",
                            dirname( __FILE__ ) ); ?>" alt="">
                        <div class="ecwc-account-status">
                            <div>Account connesso</div>
                            <div class="ecwc-account-connected"></div>
                        </div>
                    </div>
                    <div class="ecwc-account-info">
                        <span class="flex-grow-1 truncate" title="alessandro@sendblaster.com"><strong>alessandro@sendblaster.com</strong></span>
                        <span>
                            <a id="emailchef-disconnect" class="ecwc-account-disconnect" title="Disconnect account">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M280 24c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 240c0 13.3 10.7 24 24 24s24-10.7 24-24l0-240zM134.2 107.3c10.7-7.9 12.9-22.9 5.1-33.6s-22.9-12.9-33.6-5.1C46.5 112.3 8 182.7 8 262C8 394.6 115.5 502 248 502s240-107.5 240-240c0-79.3-38.5-149.7-97.8-193.3c-10.7-7.9-25.7-5.6-33.6 5.1s-5.6 25.7 5.1 33.6c47.5 35 78.2 91.2 78.2 154.7c0 106-86 192-192 192S56 368 56 262c0-63.4 30.7-119.7 78.2-154.7z"></path></svg>
                            </a>
                        </span>
                    </div>
                </div>
                <div class="ecwc-main-forms">
                    <h1>Emailchef for Woocommerce settings</h1>
                    <p>Description</p>
                    <div class="emailchef-form card accordion-container">
                        <h2>Emailchef List Settings</h2>
                        <p>Info about this section...</p>
                        <table class="form-table">
                            <tbody>
                            <tr class="" style="">
                                <th scope="row" class="titledesc">
                                    <label for="wc_emailchef_list">List <span class="woocommerce-help-tip" tabindex="0" aria-label="Select your destination list or create a new."></span></label>
                                </th>
                                <td class="forminp forminp-select">
                                    <select name="" id="" style="min-width: 350px;" class="" tabindex="-1" aria-hidden="true">
                                        <option value="124312">Star Rating test</option>
                                        <option value="121034">demo</option>
                                        <option value="120918" selected="selected">Mi Tienda Online</option>
                                        <option value="120820">My New Leads</option>
                                        <option value="104630">My First List</option>
                                    </select>
                                    <p class="description "><br><a href="#" id="wc_emailchef_create_list">Add a new destination list.</a></p>
                                    <div class="ecwc-new-list-container">
                                        <label>List name</label>
                                        <input name="wc_emailchef_new_name" id="wc_emailchef_new_name" type="text" dir="ltr" style="min-width:350px;" value="" class="" placeholder="Provide a name for this new list.">
                                        <label>List description</label>
                                        <input name="wc_emailchef_new_description" id="wc_emailchef_new_description" type="text" dir="ltr" style="min-width:350px;" value="" class="" placeholder="Provide a description for this new list.">
                                        <p>By creating a new list, you confirm its compliance with the privacy policy and the CAN-SPAM Act.</p>
                                        <p class="ecwc-buttons-container">
                                            <button name="wc_emailchef_save" class="button-primary woocommerce-save-button" id="wc_emailchef_new_save">Create</button>
                                            <button name="wc_emailchef_undo" class="button woocommerce-undo-button" id="wc_emailchef_undo_save">Undo</button>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            <tr class="" style="">
                                <th scope="row" class="titledesc">Sync customers</th>
                                <td class="forminp forminp-checkbox ">
                                    <fieldset>
                                        <legend class="screen-reader-text"><span>Sync customers</span></legend>
                                        <label for="wc_emailchef_sync_customers">
                                            <input name="wc_emailchef_sync_customers" id="wc_emailchef_sync_customers" type="checkbox" class="" value="1" checked="checked"> 							</label> 																</fieldset>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="emailchef-form card accordion-container">
                        <h2>Emailchef Subscription settings</h2>
                        <p>Info about this section...</p>
                        <table class="form-table">
                            <tbody>

                                <tr class="">
                                    <th scope="row" class="titledesc">
                                        <label for="wc_emailchef_lang">Language <span class="woocommerce-help-tip" tabindex="0" aria-label="You can choose your favorite language"></span></label>
                                    </th>
                                    <td class="forminp forminp-select">
                                        <select style="min-width: 350px;" class="" tabindex="-1" aria-hidden="true">
                                            <option value="it" selected="selected">Italian</option>
                                            <option value="en">English</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="">
                                    <th scope="row" class="titledesc">
                                        <label for="wc_emailchef_policy_type">Policy <span class="woocommerce-help-tip" tabindex="0" aria-label="Which policy would you like to use?"></span></label>
                                    </th>
                                    <td class="forminp forminp-select">
                                        <select style="" class="">
                                            <option value="sopt">Single opt-in</option>
                                            <option value="dopt" selected="selected">Double opt-in</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="single_select_page " style="">
                                    <th scope="row" class="titledesc">
                                        <label>Subscription page <span class="woocommerce-help-tip" tabindex="0" aria-label="Page where customer moved after subscribe newsletter in double opt-in"></span></label>
                                    </th>
                                    <td class="forminp">
                                        <select name="wc_emailchef_landing_page" class="wc-enhanced-select-nostd select2-hidden-accessible enhanced" data-placeholder="Select a page…" style="" id="wc_emailchef_landing_page" tabindex="-1" aria-hidden="true">
                                            <option value=""> </option>
                                            <option class="level-0" value="2">Sample Page</option>
                                            <option class="level-0" value="5">Shop</option>
                                            <option class="level-0" value="6">Cart</option>
                                            <option class="level-0" value="7">Checkout</option>
                                            <option class="level-0" value="8">My account</option>
                                            <option class="level-0" value="275">test wpforms</option>
                                            <option class="level-0" value="26" selected="selected">Welcome</option>
                                            <option class="level-0" value="27">Blog</option>
                                            <option class="level-0" value="291">Refund and Returns Policy</option>
                                            <option class="level-0" value="343">Landing page</option>
                                            <option class="level-0" value="111">form</option>
                                        </select><span class="select2 select2-container select2-container--default" dir="ltr" style="width: 400px;"><span class="selection"><span class="select2-selection select2-selection--single" aria-haspopup="true" aria-expanded="false" tabindex="0" aria-labelledby="select2-wc_emailchef_landing_page-container" role="combobox"><span class="select2-selection__rendered" id="select2-wc_emailchef_landing_page-container" role="textbox" aria-readonly="true" title="Welcome"><span class="select2-selection__clear">×</span>Welcome</span><span class="select2-selection__arrow" role="presentation"><b role="presentation"></b></span></span></span><span class="dropdown-wrapper" aria-hidden="true"></span></span>
                                    </td>
                                </tr>
                                <tr class="single_select_page " style="">
                                    <th scope="row" class="titledesc">
                                        <label>Unsubscription page <span class="woocommerce-help-tip" tabindex="0" aria-label="Page where customer moved after unsubscribe newsletter in double opt-in"></span></label>
                                    </th>
                                    <td class="forminp">
                                        <select name="wc_emailchef_fuck_page" class="wc-enhanced-select-nostd select2-hidden-accessible enhanced" data-placeholder="Select a page…" style="" id="wc_emailchef_fuck_page" tabindex="-1" aria-hidden="true">
                                            <option value=""> </option>
                                            <option class="level-0" value="2" selected="selected">Sample Page</option>
                                            <option class="level-0" value="5">Shop</option>
                                            <option class="level-0" value="6">Cart</option>
                                            <option class="level-0" value="7">Checkout</option>
                                            <option class="level-0" value="8">My account</option>
                                            <option class="level-0" value="275">test wpforms</option>
                                            <option class="level-0" value="26">Welcome</option>
                                            <option class="level-0" value="27">Blog</option>
                                            <option class="level-0" value="291">Refund and Returns Policy</option>
                                            <option class="level-0" value="343">Landing page</option>
                                            <option class="level-0" value="111">form</option>
                                        </select><span class="select2 select2-container select2-container--default" dir="ltr" style="width: 400px;"><span class="selection"><span class="select2-selection select2-selection--single" aria-haspopup="true" aria-expanded="false" tabindex="0" aria-labelledby="select2-wc_emailchef_fuck_page-container" role="combobox"><span class="select2-selection__rendered" id="select2-wc_emailchef_fuck_page-container" role="textbox" aria-readonly="true" title="Sample Page"><span class="select2-selection__clear">×</span>Sample Page</span><span class="select2-selection__arrow" role="presentation"><b role="presentation"></b></span></span></span><span class="dropdown-wrapper" aria-hidden="true"></span></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="submit">
                        <button name="save" class="woocommerce-save-button components-button is-primary" type="submit" value="Save changes">Save changes</button>
                        <input type="hidden" id="_wpnonce" name="_wpnonce" value="9d2922f01a"><input type="hidden" name="_wp_http_referer" value="/wp-admin/admin.php?page=wc-settings&amp;tab=emailchef">
                    </p>
                </div>

            </div>
            
            <!-- EOF user logged -->
            
            
            <!--
            <p>ciao</p>
            <div class="emailchef-logo">
                <img src="<?php
				echo plugins_url( "dist/img/emailchef.png",
					dirname( __FILE__ ) ); ?>">
            </div>
            -->
			<?php

			$settings = $this->get_settings( $current_section );

			//WC_Admin_Settings::output_fields( $settings );

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
