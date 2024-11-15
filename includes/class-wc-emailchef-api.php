<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class WC_Emailchef_Api {

	protected $api_url = "https://app.emailchef.com";
	public $lastError;
	private $isLogged = false;
	protected $authkey = false;

	public function __construct( $username, $password ) {
		$this->process_login( $username, $password );
	}

	public function isLogged() {
		return $this->isLogged;
	}

	private function authkey_name(){
		return defined("EMAILCHEF_API_AUTHKEY_NAME") ? EMAILCHEF_API_AUTHKEY_NAME : 'authkey';
	}

	public function getApiUrl(){
		return defined("EMAILCHEF_API_URL") ? EMAILCHEF_API_URL : $this->api_url;
	}


	private function process_login( $username, $password ) {
		if (!$authkey = get_transient('ecwc_authkey')) {

			$response = $this->getDecodedJson( "/login", array(
				'username' => $username,
				'password' => $password
			), "POST", "/api" );

			if ( ! isset( $response[ $this->authkey_name() ] ) ) {
				$authkey = false;
				$this->lastError = $response['message'];
			} else {
				$authkey = $response[ $this->authkey_name() ];
				set_transient('ecwc_authkey', $authkey, 60 * 60 * 24 * 365);
			}

		}

		if ($authkey !== false) {
			$this->authkey  = $authkey;
			$this->isLogged = true;
		}
	}

	protected function get( $route, $args = array(), $type = "POST", $prefix = "/apps/api/v1" ) {

		$url = $this->getApiUrl() . $prefix . $route;

		$auth = array();

		if ( $this->authkey !== false ) {
			$auth[$this->authkey_name()] = $this->authkey;
		}

		$args = array(
			'body'   => $args,
			'method' => strtoupper( $type ),
			'headers' => $auth
		);

		$args = apply_filters( "ec_wc_get_args", $args );

		$response = wp_remote_request( $url, $args );
		$status_code = wp_remote_retrieve_response_code($response);

		if ($status_code !== 200){
			delete_transient('ecwc_authkey');
		}

		return wp_remote_retrieve_body( $response );

	}

	protected function getDecodedJson( $route, $args = array(), $type = "POST", $prefix = "/apps/api/v1" ) {
		return json_decode( $this->get( $route, $args, $type, $prefix ), true );
	}

}
