<?php
/**
 * Message log: one row per message, also the source of truth for duplicate protection.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- custom table, always fresh data.

/**
 * Log table access.
 */
class SNFW_Logger {

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'snfw_log';
	}

	/**
	 * Current time in UTC, MySQL format.
	 *
	 * @return string
	 */
	private static function now() {
		return gmdate( 'Y-m-d H:i:s' );
	}

	/**
	 * Insert a row.
	 *
	 * @param array $data Row data. A non-empty dedupe_key makes the insert fail when that key already exists.
	 * @return int Row ID, or 0 when the insert was refused (duplicate key).
	 */
	public static function insert( array $data ) {
		global $wpdb;

		$row = array_merge(
			array(
				'created_at' => self::now(),
				'updated_at' => self::now(),
				'order_id'   => 0,
				'event'      => '',
				'recipient'  => '',
				'phone'      => '',
				'message'    => '',
				'file'       => '',
				'status'     => 'queued',
				'attempts'   => 0,
				'response'   => '',
				'dedupe_key' => null,
			),
			$data
		);

		$row['msg_hash'] = md5( $row['phone'] . '|' . $row['message'] );
		if ( '' === $row['dedupe_key'] ) {
			$row['dedupe_key'] = null;
		}

		$suppress = $wpdb->suppress_errors( true );
		$ok       = $wpdb->insert( self::table(), $row );
		$wpdb->suppress_errors( $suppress );

		return $ok ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Update a row.
	 *
	 * @param int   $id   Row ID.
	 * @param array $data Columns.
	 */
	public static function update( $id, array $data ) {
		global $wpdb;
		$data['updated_at'] = self::now();
		$wpdb->update( self::table(), $data, array( 'id' => (int) $id ) );
	}

	/**
	 * Atomically take a waiting row for sending, so two processes never send the same message.
	 *
	 * @param int $id Row ID.
	 * @return bool True when this process now owns the row.
	 */
	public static function claim( $id ) {
		global $wpdb;
		return 1 === (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE %i SET status = 'sending', attempts = attempts + 1, updated_at = %s WHERE id = %d AND status IN ('queued','retry')",
				self::table(),
				self::now(),
				$id
			)
		);
	}

	/**
	 * One row.
	 *
	 * @param int $id Row ID.
	 * @return object|null
	 */
	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', self::table(), $id ) );
	}

	/**
	 * Row holding a dedupe key.
	 *
	 * @param string $key Dedupe key.
	 * @return object|null
	 */
	public static function get_by_key( $key ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE dedupe_key = %s', self::table(), $key ) );
	}

	/**
	 * Was the same text already queued or sent to the same number recently?
	 *
	 * @param string $phone   Number.
	 * @param string $message Text.
	 * @param int    $seconds Window.
	 * @return bool
	 */
	public static function recently_sent( $phone, $message, $seconds ) {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM %i WHERE msg_hash = %s AND status IN ('queued','retry','sending','sent') AND created_at >= %s LIMIT 1",
				self::table(),
				md5( $phone . '|' . $message ),
				gmdate( 'Y-m-d H:i:s', time() - $seconds )
			)
		);
	}

	/**
	 * Rows for the log screen.
	 *
	 * @param array $args status, search, order_id, per_page, page.
	 * @return array [ rows, total ]
	 */
	public static function query( array $args ) {
		global $wpdb;

		$status   = isset( $args['status'] ) ? (string) $args['status'] : '';
		$order_id = isset( $args['order_id'] ) ? (int) $args['order_id'] : 0;
		$search   = isset( $args['search'] ) ? ltrim( (string) $args['search'], '#' ) : '';
		$like     = '';

		if ( '' !== $search && ctype_digit( $search ) && strlen( $search ) < 8 ) {
			$order_id = (int) $search; // Short numbers are order numbers.
		} elseif ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$per_page = isset( $args['per_page'] ) ? max( 1, (int) $args['per_page'] ) : 20;
		$page     = isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1;

		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE ( %s = '' OR status = %s ) AND ( %d = 0 OR order_id = %d ) AND ( %s = '' OR phone LIKE %s OR message LIKE %s )",
				self::table(),
				$status,
				$status,
				$order_id,
				$order_id,
				$like,
				$like,
				$like
			)
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %i WHERE ( %s = '' OR status = %s ) AND ( %d = 0 OR order_id = %d ) AND ( %s = '' OR phone LIKE %s OR message LIKE %s ) ORDER BY id DESC LIMIT %d OFFSET %d",
				self::table(),
				$status,
				$status,
				$order_id,
				$order_id,
				$like,
				$like,
				$like,
				$per_page,
				( $page - 1 ) * $per_page
			)
		);

		return array( (array) $rows, $total );
	}

	/**
	 * Message counts per status since a point in time.
	 *
	 * @param int $since Unix time.
	 * @return array status => count
	 */
	public static function counts_since( $since ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) AS total FROM %i WHERE created_at >= %s GROUP BY status', self::table(), gmdate( 'Y-m-d H:i:s', $since ) ) );
		$out  = array(
			'sent'    => 0,
			'failed'  => 0,
			'skipped' => 0,
			'waiting' => 0,
		);
		foreach ( (array) $rows as $row ) {
			if ( isset( $out[ $row->status ] ) ) {
				$out[ $row->status ] += (int) $row->total;
			} else {
				$out['waiting'] += (int) $row->total;
			}
		}
		return $out;
	}

	/**
	 * Most recent row that reached the API (sent or failed).
	 *
	 * @return object|null
	 */
	public static function last_attempt() {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i WHERE status IN ('sent','failed') ORDER BY updated_at DESC, id DESC LIMIT 1", self::table() ) );
	}

	/**
	 * Daily clean-up: drop old rows and release rows stuck in "sending".
	 */
	public static function cleanup() {
		global $wpdb;
		$days = max( 1, (int) SNFW_Settings::get( 'retention_days' ) );

		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE created_at < %s', self::table(), gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) ) );
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE %i SET status = 'failed', response = %s WHERE status = 'sending' AND updated_at < %s",
				self::table(),
				__( 'Sending was interrupted before the API answered.', 'whatswift-woo-notifier' ),
				gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS )
			)
		);
	}

	/**
	 * Delete every row.
	 */
	public static function clear() {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', self::table() ) );
	}
}
