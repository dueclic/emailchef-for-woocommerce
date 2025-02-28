<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class WC_Emailchef_Api {

	protected $api_url = "https://app.emailchef.com";
	public $lastError;
	private $isLogged = false;

	/**
	 * @var string | null
	 */
	private $consumer_key = null;
	/**
	 * @var string | null
	 */
	private $consumer_secret = null;

	public function __construct( $consumer_key, $consumer_secret ) {
		$this->consumer_key = $consumer_key;
		$this->consumer_secret = $consumer_secret;
	}

	public function getApiUrl(){
		return defined("EMAILCHEF_API_URL") ? EMAILCHEF_API_URL : $this->api_url;
	}

	protected function call( $route, $args = array(), $method = "POST" ) {

		$url = $this->getApiUrl() . "/apps/api/v1" . $route;

		$args = array(
			'body'   => $args,
			'method' => strtoupper( $method ),
			'user-agent' => 'Emailchef for WooCommerce (WordPress Plugin)',
			'headers' => [
				'consumerKey' => $this->consumer_key,
				'consumerSecret' => $this->consumer_secret
			]
		);

		$args = apply_filters( "ec_wc_get_args", $args );

		return wp_remote_request( $url, $args );

	}

}
