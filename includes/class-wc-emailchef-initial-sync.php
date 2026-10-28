<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Persistent, bounded background import of existing customers. */
class WC_Emailchef_Initial_Sync {
	const OPTION = 'wc_emailchef_initial_sync';
	const LOCK = 'wc_emailchef_initial_sync_lock';
	const HOOK = 'wc_emailchef_initial_sync_batch';
	const WATCHDOG = 'wc_emailchef_initial_sync_watchdog';
	const BATCH_SIZE = 50;
	const BATCH_OPTION = 'wc_emailchef_initial_sync_batch_size';
	const MIN_BATCH = 5;
	const MAX_BATCH = 100;

	public static function init() {
		add_action( self::HOOK, array( __CLASS__, 'run' ), 10, 1 );
		add_action( self::WATCHDOG, array( __CLASS__, 'watchdog' ), 10, 1 );
	}

	/** Conservative starting point, not a guarantee for stores with large orders. */
	public static function suggested_batch_size() {
		$limits = array();
		foreach ( array( ini_get( 'memory_limit' ), defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : '' ) as $limit ) {
			if ( $limit && $limit !== '-1' ) {
				$bytes = wp_convert_hr_to_bytes( $limit );
				if ( $bytes > 0 ) {
					$limits[] = $bytes;
				}
			}
		}
		$available = $limits ? min( $limits ) : 0;
		if ( ! $available || $available <= 64 * MB_IN_BYTES ) return 10;
		if ( $available <= 128 * MB_IN_BYTES ) return 20;
		if ( $available <= 256 * MB_IN_BYTES ) return 35;
		return 50;
	}

	public static function batch_size() {
		$value = get_option( self::BATCH_OPTION, false );
		return $value === false ? self::suggested_batch_size() : max( self::MIN_BATCH, min( self::MAX_BATCH, (int) $value ) );
	}

	private static function schedule( $hook, $id, $when ) {
		$args = array( $id );
		if ( wp_next_scheduled( $hook, $args ) ) {
			return true;
		}
		$result = wp_schedule_single_event( $when, $hook, $args, true );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'schedule', sprintf( __( 'WordPress could not schedule %1$s: %2$s (%3$s).', 'emailchef-for-woocommerce' ), $hook, $result->get_error_message(), $result->get_error_code() ) );
		}
		if ( ! $result ) {
			return new WP_Error( 'schedule', sprintf( __( 'WordPress could not schedule %s.', 'emailchef-for-woocommerce' ), $hook ) );
		}
		return true;
	}

	public static function start( $list_id ) {
		global $wpdb;
		$job = get_option( self::OPTION );
		if ( is_array( $job ) && in_array( $job['status'], array( 'queued', 'running' ), true ) ) {
			return new WP_Error( 'busy', __( 'A synchronization is already in progress.', 'emailchef-for-woocommerce' ) );
		}
		if ( is_array( $job ) && $job['status'] === 'error' && (int) $job['list_id'] === (int) $list_id ) {
			$job['status'] = 'queued';
			$job['message'] = '';
			$job['updated_at'] = time();
		} else {
			$counts = count_users();
			$job = array(
				'id' => wp_generate_uuid4(), 'list_id' => (int) $list_id,
				'status' => 'queued', 'phase' => 'setup', 'processed' => 0,
				'total' => isset( $counts['avail_roles']['customer'] ) ? (int) $counts['avail_roles']['customer'] : 0,
				'cursor' => 0, 'max_id' => (int) $wpdb->get_var( "SELECT MAX(ID) FROM {$wpdb->users}" ),
				'message' => '', 'updated_at' => time(), 'chunks' => array(),
			);
		}
		update_option( self::OPTION, $job, false );
		$scheduled = self::schedule( self::HOOK, $job['id'], time() + 1 );
		if ( ! is_wp_error( $scheduled ) ) {
			$scheduled = self::schedule( self::WATCHDOG, $job['id'], time() + 600 );
		}
		if ( is_wp_error( $scheduled ) ) {
			$job['status'] = 'error';
			$job['message'] = $scheduled->get_error_message();
			update_option( self::OPTION, $job, false );
			return $scheduled;
		}
		return $job;
	}

	public static function watchdog( $id ) {
		$job = get_option( self::OPTION );
		if ( ! is_array( $job ) || $job['id'] !== $id || ! in_array( $job['status'], array( 'queued', 'running' ), true ) ) {
			return;
		}
		if ( time() - (int) $job['updated_at'] >= 600 ) {
			delete_option( self::LOCK );
			$scheduled = self::schedule( self::HOOK, $id, time() + 1 );
			if ( is_wp_error( $scheduled ) ) {
				$job['status'] = 'error';
				$job['message'] = $scheduled->get_error_message();
				update_option( self::OPTION, $job, false );
				return;
			}
		}
		self::schedule( self::WATCHDOG, $id, time() + 600 );
	}

	public static function run( $id ) {
		if ( ! add_option( self::LOCK, time(), '', false ) ) {
			return;
		}
		try {
			$job = get_option( self::OPTION );
			if ( ! is_array( $job ) || $job['id'] !== $id || ! in_array( $job['status'], array( 'queued', 'running' ), true ) ) {
				return;
			}
			$job['status'] = 'running';
			$job['updated_at'] = time();
			update_option( self::OPTION, $job, false );
			$api = WCEC()->emailchef();
			if ( ! $api ) {
				throw new Exception( __( 'Emailchef connection is unavailable.', 'emailchef-for-woocommerce' ) );
			}
			if ( $job['phase'] === 'setup' ) {
				if ( ! $api->upsert_integration( $job['list_id'] ) || ! $api->initialize_custom_fields( $job['list_id'] ) ) {
					throw new Exception( ! empty( $api->lastError ) ? $api->lastError : __( 'Failed to initialize custom fields.', 'emailchef-for-woocommerce' ) );
				}
				$job['phase'] = 'customers';
			} else {
				global $wpdb;
				$batch_size = self::batch_size();
				$filter = function ( $query ) use ( $job, $wpdb ) {
					$query->query_where .= $wpdb->prepare( " AND {$wpdb->users}.ID > %d AND {$wpdb->users}.ID <= %d", $job['cursor'], $job['max_id'] );
				};
				add_action( 'pre_user_query', $filter );
				try {
					$ids = get_users( array( 'role' => 'customer', 'fields' => 'ID', 'number' => $batch_size, 'orderby' => 'ID', 'order' => 'ASC', 'count_total' => false ) );
				} finally {
					remove_action( 'pre_user_query', $filter );
				}
				if ( $ids ) {
					if ( ! isset( $job['batch_number'] ) ) {
						$last_chunk = ! empty( $job['chunks'] ) ? end( $job['chunks'] ) : null;
						$job['batch_number'] = $last_chunk ? (int) $last_chunk['number'] - ( $last_chunk['status'] === 'error' ? 1 : 0 ) : (int) floor( $job['processed'] / self::BATCH_SIZE );
					}
					$success = $api->sync_customer_batch( $job['list_id'], $ids );
					$summary = isset( $api->last_import_summary ) && is_array( $api->last_import_summary ) ? $api->last_import_summary : array();
					if ( ! isset( $job['chunks'] ) || ! is_array( $job['chunks'] ) ) {
						$job['chunks'] = array();
					}
					$job['chunks'][] = array(
						'number' => $job['batch_number'] + 1,
						'time' => time(), 'sent' => count( $ids ), 'status' => $success ? 'accepted' : 'error',
						'accepted' => isset( $summary['accepted'] ) ? $summary['accepted'] : 0,
						'task_id' => isset( $summary['task_id'] ) ? $summary['task_id'] : '',
						'warnings' => isset( $summary['warnings'] ) ? $summary['warnings'] : 0,
						'warning_details' => isset( $summary['warning_details'] ) ? $summary['warning_details'] : array(),
						'message' => $success ? '' : ( ! empty( $api->lastError ) ? $api->lastError : __( 'The contact import failed.', 'emailchef-for-woocommerce' ) ),
					);
					if ( ! $success ) {
						throw new Exception( end( $job['chunks'] )['message'] );
					}
					$job['batch_number']++;
					$job['cursor'] = (int) end( $ids );
					$job['processed'] += count( $ids );
				}
				if ( count( $ids ) < $batch_size ) {
					$job['status'] = 'completed';
					$job['total'] = $job['processed'];
				}
			}
			$job['updated_at'] = time();
			update_option( self::OPTION, $job, false );
			if ( $job['status'] !== 'completed' ) {
				$scheduled = self::schedule( self::HOOK, $id, time() + 1 );
				if ( is_wp_error( $scheduled ) ) {
					throw new Exception( $scheduled->get_error_message() );
				}
			}
		} catch ( Throwable $error ) {
			if ( isset( $job ) && is_array( $job ) && $job['id'] === $id ) {
				$job['status'] = 'error';
				$job['message'] = $error->getMessage();
				$job['updated_at'] = time();
				update_option( self::OPTION, $job, false );
				WCEC()->log( 'Initial synchronization error: ' . $error->getMessage() );
			}
		} finally {
			delete_option( self::LOCK );
		}
	}
}
