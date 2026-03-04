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

// Wrap in closure to avoid global namespace pollution.
( function () {
	$remove_data = get_option( 'yoko_lc_remove_data_on_uninstall', true );

	if ( ! $remove_data ) {
		return;
	}

	global $wpdb;

	/**
	 * Remove custom database tables.
	 */
	$tables = array(
		$wpdb->prefix . 'yoko_lc_links',
		$wpdb->prefix . 'yoko_lc_urls',
		$wpdb->prefix . 'yoko_lc_scans',
	);

	foreach ( $tables as $table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}

	/**
	 * Remove options.
	 */
	$options = array(
		'yoko_lc_schema_version',
		'yoko_lc_activated_at',
		'yoko_lc_remove_data_on_uninstall',
		'yoko_lc_auto_scan_enabled',
		'yoko_lc_auto_scan_frequency',
		'yoko_lc_post_types',
		'yoko_lc_check_timeout',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Remove scan cursor and last-activity options (dynamic keys).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( 'yoko_lc_scan_' ) . '%'
		)
	);

	/**
	 * Remove capabilities from roles.
	 */
	$capabilities = array(
		'yoko_lc_manage_scans',
		'yoko_lc_view_results',
		'yoko_lc_manage_settings',
	);

	$role_names = array( 'administrator', 'editor' );

	foreach ( $role_names as $role_name ) {
		$wp_role = get_role( $role_name );
		if ( $wp_role ) {
			foreach ( $capabilities as $cap ) {
				$wp_role->remove_cap( $cap );
			}
		}
	}

	/**
	 * Clear scheduled hooks.
	 */
	wp_clear_scheduled_hook( 'yoko_lc_process_scan_batch' );
	wp_clear_scheduled_hook( 'yoko_lc_auto_scan' );

	/**
	 * Clear transients.
	 */
	delete_transient( 'yoko_lc_scan_lock' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_yoko_lc_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_yoko_lc_' ) . '%'
		)
	);
} )();
