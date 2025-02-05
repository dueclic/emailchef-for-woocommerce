<?php

final class WC_Emailchef_Plugin {

	private static $instance;

	private $handler;

	/**
	 * Settings
	 *
	 * @var array
	 */

	private $settings;

	/**
	 * Plugin emailChef
	 *
	 * @var WC_Emailchef
	 */

	private $emailchef;

	/**
	 * @var WC_Logger
	 */

	private $log;

	/**
	 * Namespace for prefixed setting
	 *
	 * @var string
	 */

	private $namespace = "wc_emailchef";

	public static function get_instance() {
		if ( empty( self::$instance )
		     && ! ( self::$instance instanceof WC_Emailchef_Plugin )
		) {
			self::$instance = new WC_Emailchef_Plugin;
			self::$instance->define_constants();

			self::$instance->save_settings();
			self::$instance->settings();
			self::$instance->includes();
			self::$instance->emailchef();
			self::$instance->handler = WC_Emailchef_Handler::get_instance();
			self::$instance->load_translations();

			self::$instance->add_hooks();

			do_action( "wc_emailchef_loaded" );
		}

		return self::$instance;
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

	/**
	 * Return plugin version
	 *
	 * @return string
	 */

	public static function version() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$plugin_data = get_plugin_data( WC_EMAILCHEF_FILE );

		return $plugin_data['Version'];
	}

	public static function activate() {
		set_transient( 'emailchef-admin-notice', [
			'type' => 'success',
			'text' => __( 'Well! Now that you activated Emailchef for WooCommerce, go to the ',
					'emailchef-for-woocommerce' ) . '<a href="'
			          . WC_EMAILCHEF_SETTINGS_URL . '">'
			          . __( 'configuration',
					'emailchef-for-woocommerce' ) . '</a>'
		], 30 );
	}

	public static function deactivate() {
		$options = array(
			'consumer_key',
			'consumer_secret',
			'enabled',
			'list',
			'policy_type',
			'landing_page',
			'fuck_page',
		);

		foreach ( $options as $option ) {
			delete_option( "wc_emailchef_" . $option );
		}
		delete_transient( 'ecwc_authkey' );
		delete_transient( 'ecwc_lists' );
	}

	/**
	 * Define constants
	 *
	 * @param string $name
	 * @param string|bool $value
	 */

	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Return abcart table name
	 *
	 * @return string
	 */

	public function abcart_table() {
		global $wpdb;

		return $wpdb->prefix . "emailchef_abcart";
	}

	/**
	 * Load scripts and styles
	 */

	public function enqueue_scripts() {

		global $current_screen;
		if ( $current_screen->id === 'woocommerce_page_wc-settings' ) {

			/** @noinspection PhpUndefinedConstantInspection */

			wp_register_script( 'woocommerce-emailchef-backend-js',
				WC_EMAILCHEF_URL . "dist/js/emailchef.min.js", array( 'jquery' ),
				self::version() );

			wp_localize_script( 'woocommerce-emailchef-backend-js', 'wcec', array(
				"disconnect_confirm"   => __( "Are you sure you want to disconnect this account?", "emailchef-for-woocommerce" ),
				"ajax_manual_sync_url" => wp_nonce_url(
					add_query_arg( [
						'action' => $this->prefixed_setting(
							'manual_sync'
						)
					],
						admin_url( 'admin-ajax.php' )
					),
					'emailchef_manual_sync'
				),
				"ajax_disconnect_url"  => wp_nonce_url(
					add_query_arg( [
							'action' => $this->prefixed_setting(
								'disconnect'
							)
						],
					admin_url( 'admin-ajax.php' )
					),
					'emailchef_disconnect'
				),
				"ajax_lists_url"  => wp_nonce_url(
					add_query_arg( [
						'action' => $this->prefixed_setting(
							'lists'
						)
					],
						admin_url( 'admin-ajax.php' )
					),
					'emailchef_lists'
				)
			) );

			/** @noinspection PhpUndefinedConstantInspection */

			wp_register_style( 'woocommerce-emailchef-backend-css',
				WC_EMAILCHEF_URL . "dist/css/emailchef.min.css", array(),
				self::version() );

			wp_enqueue_script( 'woocommerce-emailchef-backend-js' );
			wp_enqueue_style( 'woocommerce-emailchef-backend-css' );
		}
	}

	/**
	 * @param $message
	 */

	public function log( $message ) {
		$logger = $this->log;

		if ( is_array( $message ) || is_object( $message ) ) {
			$logger->add( 'emailchef-for-woocommerce',
				print_r( $message, true ) );
		} else {
			$logger->add( 'emailchef-for-woocommerce', $message );
		}
	}

	/**
	 * Hooks
	 */

	private function add_hooks() {
		register_activation_hook( WC_EMAILCHEF_FILE,
			array( __CLASS__, 'activate' ) );
		register_deactivation_hook( WC_EMAILCHEF_FILE,
			array( __CLASS__, 'deactivate' ) );

		if ( is_admin() ) {
			add_filter( "plugin_action_links_"
			            . plugin_basename( WC_EMAILCHEF_FILE ),
				array( $this, 'action_links' ) );

			add_filter( "woocommerce_get_settings_pages",
				array( $this, 'add_emailchef_settings' ) );

			add_action( "admin_enqueue_scripts",
				array( $this, 'enqueue_scripts' ) );
		}

		add_action( "woocommerce_loaded", array( $this, "set_logger" ), 10 );

		add_action( 'before_woocommerce_init', function () {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WC_EMAILCHEF_FILE, true );
			}
		} );

		add_action( "ec_footer_copyright",
			array( $this, 'dueclic_copyright' ) );

		add_action( 'admin_notices', function () {
			if ( $notice = get_transient( 'emailchef-admin-notice' ) ) {
				?>

                <div class="notice notice-<?php echo esc_attr( $notice['type'] ) ?> is-dismissible">
                    <p><?php
						echo esc_html( $notice['text'] );
						?></p>
                </div>

				<?php
				delete_transient( 'emailchef-admin-notice' );
			}
		} );

		add_action( 'admin_footer', array( $this, 'emailchef_debug_js' ) );

		add_filter( "wc_ec_add_prefix", array( $this, 'prefixed_setting' ), 10, 1 );

	}

	public function emailchef_debug_js() {
		$screen = get_current_screen();
		if ( $screen->id === 'admin_page_emailchef-debug' ) {
			?>
            <script>
                (function ($) {

                    $(document).ready(function () {
                        $('.button-force-sync').on('click', function (evt) {
                            evt.preventDefault();
                            var userId = $(this).data('user-id');
                            var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
                            $.post(
                                ajaxurl,
                                {
                                    'action': '<?php echo $this->namespace; ?>_sync_abandoned_carts',
                                    'only_userid': userId
                                },
                                function (response) {
                                    console.log("Abandoned cart synced successfully");
                                    location.reload();
                                }
                            );
                        });
                    });

                    $(document).ready(function () {
                        $('.button-rebuild-customfields').on('click', function (evt) {
                            evt.preventDefault();
                            var userId = $(this).data('user-id');
                            var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
                            $.post(
                                ajaxurl,
                                {
                                    'action': '<?php echo $this->namespace; ?>_rebuild_customfields'
                                },
                                function (response) {
                                    console.log("Recover custom fields successfully");
                                    location.reload();
                                }
                            );
                        });
                    });

                })(jQuery);
            </script>
			<?php
		}
	}

	public function set_logger() {
		if ( self::version_check( '2.7' ) ) {
			$this->log = wc_get_logger();
		} else {
			$this->log = new WC_Logger();
		}
	}

	public function add_emailchef_settings() {
		/** @noinspection PhpIncludeInspection */
		/** @noinspection PhpUndefinedConstantInspection */

		$settings[] = require_once( WC_EMAILCHEF_DIR
		                            . 'includes/class-wc-emailchef-settings.php' );

		return $settings;
	}

	/**
	 * Action links in list of plugin
	 *
	 * @param $links
	 *
	 * @return array
	 */

	public function action_links( $links ) {
		/** @noinspection PhpUndefinedConstantInspection */

		$manual_link = ( get_locale() == "it_IT"
			? 'http://emailchef.com/it/email-marketing-con-woocommerce-e-emailchef/'
			: 'http://emailchef.com/email-marketing-woocommerce-emailchef/' );

		$plugin_links = array(
			'<a href="' . WC_EMAILCHEF_SETTINGS_URL . '">' . __( 'Settings',
				'emailchef-for-woocommerce' ) . '</a>',
			'<a href="' . $manual_link . '">' . __( 'Instruction manual',
				'emailchef-for-woocommerce' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Update settings
	 */

	public function save_settings() {
		$settings = $this->settings();

		foreach ( $settings as $key => $value ) {
			update_option( $this->prefixed_setting( $key ), $value );
		}
	}

	public function footer_text() {
		return __( 'This plugin is powered by', 'emailchef-for-woocommerce' )
		       . ' <a href="https://www.dueclic.com/" target="_blank">dueclic</a>. <a class="social-foot" href="https://www.facebook.com/dueclic/"><span class="dashicons dashicons-facebook bg-fb"></span></a>';
	}

	public function dueclic_copyright() {
		add_filter( 'admin_footer_text', array( $this, 'footer_text' ), 11 );
	}

	/**
	 * Load translations
	 */

	public function load_translations() {
		$lang_path = dirname( dirname( plugin_basename( __FILE__ ) ) )
		             . '/languages/';

		load_plugin_textdomain(
			'emailchef-for-woocommerce',
			false,
			$lang_path
		);
	}

	/**
	 * Include libraries
	 */

	public function includes() {
		/** @noinspection PhpIncludeInspection */
		/** @noinspection PhpUndefinedConstantInspection */

		require_once( WC_EMAILCHEF_DIR
		              . "includes/class-wc-emailchef-api.php" );

		/** @noinspection PhpIncludeInspection */
		/** @noinspection PhpUndefinedConstantInspection */

		require_once( WC_EMAILCHEF_DIR
		              . "includes/class-wc-emailchef-customer.php" );

		/** @noinspection PhpIncludeInspection */
		/** @noinspection PhpUndefinedConstantInspection */

		require_once( WC_EMAILCHEF_DIR . "includes/class-wc-emailchef.php" );

		/** @noinspection PhpIncludeInspection */
		/** @noinspection PhpUndefinedConstantInspection */

		require_once( WC_EMAILCHEF_DIR
		              . "includes/class-wc-emailchef-handler.php" );
	}

	public function opt_in_label() {
		return __( "Signup to our newsletter", "emailchef-for-woocommerce" );
	}

	public function get_platform() {
		return "Emailchef for WooCommerce";
	}

	public function display_opt_in() {
		return $this->settings['policy_type'] != 'none';
	}

	/**
	 * Check if plugin is configured as double opt-in
	 *
	 * @return bool
	 */

	public function double_opt_in() {
		return $this->settings['policy_type'] === 'dopt';
	}

	/**
	 * Check if Emailchef login is valid
	 *
	 * @return WC_Emailchef
	 */

	public function is_valid() {
		return $this->emailchef();
	}

	public function get_api_url() {
		return $this->emailchef->getApiUrl();
	}

	/**
	 * Set plugin constants
	 */

	public function define_constants() {
		$this->define( 'WC_EMAILCHEF_MIN_WP', '4.0.0' );

		// Minimum supported version of WooCommerce
		$this->define( 'WC_EMAILCHEF_MIN_WC', '2.6.8' );

		// Minimum supported version of PHP
		$this->define( 'WC_EMAILCHEF_MIN_PHP', '5.4.0' );

		// Plugin version
		$this->define( "WC_EMAILCHEF_VERSION", self::version() );

		// Plugin folder path
		$this->define( "WC_EMAILCHEF_DIR",
			plugin_dir_path( WC_EMAILCHEF_FILE ) );

		// Plugin URL
		$this->define( "WC_EMAILCHEF_URL",
			plugin_dir_url( WC_EMAILCHEF_FILE ) );

		// Settings URL
		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=emailchef' );
		$this->define( "WC_EMAILCHEF_SETTINGS_URL", $settings_url );
	}

	public function emailchef( $consumer_key = null, $consumer_secret = null ) {
		$settings = $this->settings();

		$consumer_key    = $consumer_key ?: $settings['consumer_key'];
		$consumer_secret = $consumer_secret ?: $settings['consumer_secret'];

		/** @noinspection PhpUndefinedConstantInspection */

		require_once( WC_EMAILCHEF_DIR
		              . 'includes/class-wc-emailchef.php' );
		$this->emailchef = new WC_Emailchef( $consumer_key, $consumer_secret );

		return $this->emailchef;
	}

	/**
	 * Get settings and merge with initial settings
	 *
	 * @param bool $fetch
	 *
	 * @return array|mixed
	 */
	public function settings( $fetch = false ) {
		if ( $fetch === true || empty( $this->settings ) ) {

			/** @noinspection PhpIncludeInspection */
			/** @noinspection PhpUndefinedConstantInspection */

			$initial  = require( WC_EMAILCHEF_DIR
			                     . "conf/default_settings.php" );
			$initial  = apply_filters( "wc_emailchef_default_settings",
				$initial );
			$settings = array();

			foreach ( $initial as $key => $init_value ) {
				$value = get_option( $this->prefixed_setting( $key ) );

				$settings[ $key ] = $value ?: $init_value;
			}

			$settings = apply_filters( 'wc_emailchef_settings',
				array_merge( $initial, $settings ) );

			$this->settings = $settings;
		}

		return $this->settings;
	}

	/**
	 * Return a prefixed setting
	 *
	 * @param string $suffix
	 *
	 * @return string
	 */

	public function prefixed_setting( $suffix ) {
		return $this->namespace . '_' . $suffix;
	}

}
