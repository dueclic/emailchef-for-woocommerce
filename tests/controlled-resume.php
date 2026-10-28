<?php
/** Run with WP-CLI against a disposable WordPress installation. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	throw new RuntimeException( 'Run this test using WP-CLI.' );
}
global $wpdb;
if ( get_option( WC_Emailchef_Initial_Sync::OPTION, false ) !== false || get_option( WC_Emailchef_Initial_Sync::LOCK, false ) !== false ) {
	throw new RuntimeException( 'A synchronization exists. Use a disposable installation.' );
}
$job = array( 'id' => wp_generate_uuid4(), 'list_id' => 123, 'status' => 'running', 'phase' => 'customers', 'processed' => 75, 'total' => 100, 'cursor' => 345, 'max_id' => 500, 'message' => '', 'updated_at' => time() - 1000, 'chunks' => array() );
$other = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
$name = 'wcec_sync_' . substr( md5( DB_NAME . ':' . $wpdb->prefix ), 0, 32 );
try {
	update_option( WC_Emailchef_Initial_Sync::OPTION, $job, false );
	update_option( WC_Emailchef_Initial_Sync::LOCK, time() - 1000, false );
	if ( ! WC_Emailchef_Initial_Sync::is_stalled( $job ) || (string) $other->get_var( $other->prepare( 'SELECT GET_LOCK(%s, 0)', $name ) ) !== '1' ) {
		throw new RuntimeException( 'Could not prepare the stalled worker.' );
	}
	if ( ! is_wp_error( WC_Emailchef_Initial_Sync::start( 123 ) ) ) {
		throw new RuntimeException( 'An active worker did not prevent recovery.' );
	}
	$other->get_var( $other->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
	$result = WC_Emailchef_Initial_Sync::start( 123 );
	if ( is_wp_error( $result ) || $result['status'] !== 'queued' || $result['cursor'] !== 345 || $result['processed'] !== 75 || $result['id'] !== $job['id'] || ! wp_next_scheduled( WC_Emailchef_Initial_Sync::HOOK, array( $job['id'] ) ) ) {
		throw new RuntimeException( 'Recovery did not preserve and schedule the checkpoint.' );
	}
	if ( ! is_wp_error( WC_Emailchef_Initial_Sync::start( 123 ) ) ) {
		throw new RuntimeException( 'A duplicate recovery was accepted.' );
	}
	WP_CLI::success( 'Controlled recovery preserves the checkpoint and excludes concurrent workers.' );
} finally {
	$other->get_var( $other->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
	wp_clear_scheduled_hook( WC_Emailchef_Initial_Sync::HOOK, array( $job['id'] ) );
	wp_clear_scheduled_hook( WC_Emailchef_Initial_Sync::WATCHDOG, array( $job['id'] ) );
	delete_option( WC_Emailchef_Initial_Sync::OPTION );
	delete_option( WC_Emailchef_Initial_Sync::LOCK );
}
