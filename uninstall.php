<?php
/**
 * Uninstall handler.
 *
 * Runs when the plugin is uninstalled.
 * Removes all plugin data including database tables and options.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

// Exit if not uninstalling.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Check if data should be removed on uninstall.
 */
$remove_data = get_option( 'ylc_remove_data_on_uninstall', true );

if ( ! $remove_data ) {
	return;
}

global $wpdb;

/**
 * Remove custom database tables.
 */
$tables = [
	$wpdb->prefix . 'ylc_links',
	$wpdb->prefix . 'ylc_urls',
	$wpdb->prefix . 'ylc_scans',
];

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

/**
 * Remove options.
 */
$options = [
	'ylc_db_version',
	'ylc_post_types',
	'ylc_check_timeout',
	'ylc_auto_scan_enabled',
	'ylc_auto_scan_frequency',
	'ylc_remove_data_on_uninstall',
];

foreach ( $options as $option ) {
	delete_option( $option );
}

// Remove scan cursor options (dynamic keys).
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE 'ylc_scan_%_cursor_%'"
);

/**
 * Remove capabilities from roles.
 */
$capabilities = [
	'ylc_manage_scans',
	'ylc_view_results',
	'ylc_manage_settings',
];

$roles = [ 'administrator', 'editor' ];

foreach ( $roles as $role_name ) {
	$role = get_role( $role_name );
	if ( $role ) {
		foreach ( $capabilities as $cap ) {
			$role->remove_cap( $cap );
		}
	}
}

/**
 * Clear scheduled hooks.
 */
wp_clear_scheduled_hook( 'ylc_process_scan_batch' );
wp_clear_scheduled_hook( 'ylc_auto_scan' );

/**
 * Clear transients.
 */
delete_transient( 'ylc_scan_lock' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ylc_%' OR option_name LIKE '_transient_timeout_ylc_%'"
);
