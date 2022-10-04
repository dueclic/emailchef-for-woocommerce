<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class WC_Emailchef_Api {

	protected $api_url = "https://app.emailchef.com";
	public $lastError;
	private $isLogged = false;
	private $authkey = false;

	public function __construct( $username, $password ) {
		$this->api_url = apply_filters('emailchef_api_url', $this->api_url);
		$this->process_login( $username, $password );
	}

	public function isLogged() {
		return $this->isLogged;
	}

	private function process_login( $username, $password ) {

		$response = $this->getDecodedJson( "/login", array(

			'username' => $username,
			'password' => $password

		), "POST", "/api" );

		if ( ! isset( $response['authkey'] ) ) {
			$this->lastError = $response['message'];
		} else {
			$this->authkey  = $response['authkey'];
			$this->isLogged = true;
		}

	}

	protected function get( $route, $args = array(), $type = "POST", $prefix = "/apps/api/v1" ) {

		$url = $this->api_url . $prefix . $route;

		$auth = array();

		if ( $this->authkey !== false ) {
			$auth = array(
				'authkey' => $this->authkey
			);
		}
		$args = array(
			'body'   => array_merge( $auth, $args ),
			'method' => strtoupper( $type )
		);

		$args = apply_filters( "ec_wc_get_args", $args );

		$response = wp_remote_request( $url, $args );

		$body     = wp_remote_retrieve_body( $response );

		return $body;

	}

	protected function getDecodedJson( $route, $args = array(), $type = "POST", $prefix = "/apps/api/v1" ) {
		return json_decode( $this->get( $route, $args, $type, $prefix ), true );
	}

}