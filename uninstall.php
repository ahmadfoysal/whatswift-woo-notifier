<?php
/**
 * Runs when the plugin is deleted. Data is removed only when the store owner asked for it in Settings.
 *
 * @package SwiftNotify
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$snfw_settings = get_option( 'snfw_settings', array() );

wp_clear_scheduled_hook( 'snfw_daily_cleanup' );

if ( is_array( $snfw_settings ) && ! empty( $snfw_settings['delete_on_remove'] ) ) {
	global $wpdb;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'snfw_log' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE option_name LIKE %s', $wpdb->options, $wpdb->esc_like( 'snfw_' ) . '%' ) );
	// phpcs:enable

	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		as_unschedule_all_actions( 'snfw_send' );
	}
}
