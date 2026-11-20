<?php
/**
 * Install, upgrade from 1.x and scheduled clean-up.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates the log table and migrates old settings.
 */
class SNFW_Install {

	/**
	 * Activation hook.
	 */
	public static function activate() {
		self::install();
	}

	/**
	 * Deactivation hook.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'snfw_daily_cleanup' );
	}

	/**
	 * Run the installer when the stored version is behind (plugin updates skip the activation hook).
	 */
	public static function maybe_upgrade() {
		if ( get_option( 'snfw_db_version' ) !== SNFW_DB_VERSION ) {
			self::install();
		}
		add_action( 'snfw_daily_cleanup', array( 'SNFW_Logger', 'cleanup' ) );
		add_action( 'init', array( __CLASS__, 'schedule_cleanup' ) );
	}

	/**
	 * Make sure the daily clean-up is scheduled.
	 */
	public static function schedule_cleanup() {
		if ( ! wp_next_scheduled( 'snfw_daily_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'snfw_daily_cleanup' );
		}
	}

	/**
	 * Create or update the table, then migrate.
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = SNFW_Logger::table();
		$collate = $wpdb->get_charset_collate();

		dbDelta(
			"CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  order_id bigint(20) unsigned NOT NULL DEFAULT 0,
  event varchar(40) NOT NULL DEFAULT '',
  recipient varchar(10) NOT NULL DEFAULT '',
  phone varchar(20) NOT NULL DEFAULT '',
  message text NOT NULL,
  file varchar(500) NOT NULL DEFAULT '',
  status varchar(12) NOT NULL DEFAULT 'queued',
  attempts smallint(5) unsigned NOT NULL DEFAULT 0,
  response text NULL,
  dedupe_key varchar(120) NULL,
  msg_hash char(32) NOT NULL DEFAULT '',
  PRIMARY KEY  (id),
  UNIQUE KEY dedupe_key (dedupe_key),
  KEY order_id (order_id),
  KEY status_created (status,created_at),
  KEY msg_hash (msg_hash)
) {$collate};"
		);

		self::migrate_from_v1();

		if ( ! get_option( 'snfw_v2_since' ) ) {
			// Orders older than this never get a late "order placed" message.
			update_option( 'snfw_v2_since', time(), false );
		}

		update_option( 'snfw_db_version', SNFW_DB_VERSION );
	}

	/**
	 * Copy 1.x options (one option per field) into the new settings array. Old options are left in place.
	 */
	private static function migrate_from_v1() {
		if ( false !== get_option( SNFW_Settings::OPTION, false ) ) {
			return;
		}

		$old_app = get_option( 'snfw_api_key', null );
		if ( null === $old_app && null === get_option( 'snfw_template_order_placed', null ) ) {
			return; // Fresh install: defaults apply.
		}

		global $wpdb;

		$settings = array(
			'app_key'        => (string) $old_app,
			'auth_key'       => (string) get_option( 'snfw_auth_key', '' ),
			'seller_numbers' => (string) get_option( 'snfw_seller_number', '' ),
		);

		$placed                      = (string) get_option( 'snfw_template_order_placed', '' );
		$settings['customer_placed'] = array(
			'enabled'      => '' !== trim( $placed ),
			'template'     => $placed,
			'attach_image' => false,
		);

		$seller                    = (string) get_option( 'snfw_template_seller_alert', '' );
		$settings['seller_placed'] = array(
			'enabled'  => '' !== trim( $seller ),
			'template' => '' !== trim( $seller ) ? $seller : SNFW_Settings::default_template( 'seller_placed' ),
		);

		// In 1.x a status message was sent whenever its template was not empty.
		$settings['customer_statuses'] = array();
		$rows                          = $wpdb->get_results( $wpdb->prepare( 'SELECT option_name, option_value FROM %i WHERE option_name LIKE %s', $wpdb->options, $wpdb->esc_like( 'snfw_template_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach ( (array) $rows as $row ) {
			$slug = substr( $row->option_name, strlen( 'snfw_template_' ) );
			if ( in_array( $slug, array( 'order_placed', 'seller_alert' ), true ) ) {
				continue;
			}
			$template                               = (string) $row->option_value;
			$settings['customer_statuses'][ $slug ] = array(
				'enabled'  => '' !== trim( $template ),
				'template' => $template,
			);
		}

		// 1.x always used the Bangladesh rule for local numbers.
		$settings['country_code'] = '880';

		update_option( SNFW_Settings::OPTION, $settings );
	}
}
