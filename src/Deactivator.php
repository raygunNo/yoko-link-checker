<?php
/**
 * Plugin Deactivator.
 *
 * Handles plugin deactivation: clears scheduled events
 * and performs cleanup that should happen on deactivation.
 *
 * Note: Data is NOT deleted on deactivation.
 * Use uninstall.php for data removal.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin deactivation.
 *
 * @since 1.0.0
 */
final class Deactivator {

	/**
	 * Run deactivation routine.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function deactivate(): void {
		self::clear_scheduled_events();
		self::cancel_running_scans();
		self::cleanup_transients();
	}

	/**
	 * Clear all scheduled cron events.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function clear_scheduled_events(): void {
		// Clear any pending scan batch events.
		wp_clear_scheduled_hook( 'yoko_lc_process_scan_batch' );

		// Clear any scheduled rescans.
		wp_clear_scheduled_hook( 'yoko_lc_auto_scan' );
	}

	/**
	 * Cancel any running scans.
	 *
	 * Marks running scans as paused so they can be resumed
	 * after reactivation if desired.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function cancel_running_scans(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'yoko_lc_scans';

		// Check if table exists before querying.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
		);

		if ( $table_exists !== $table ) {
			return;
		}

		// Mark running scans as paused.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array(
				'status'        => 'paused',
				'error_message' => __( 'Scan paused due to plugin deactivation.', 'yoko-link-checker' ),
			),
			array( 'status' => 'running' ),
			array( '%s', '%s' ),
			array( '%s' )
		);
	}

	/**
	 * Clean up transients.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function cleanup_transients(): void {
		delete_transient( 'yoko_lc_scan_lock' );

		// Clean up batch lock transients (dynamic keys).
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_yoko_lc_batch_lock_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_yoko_lc_batch_lock_' ) . '%'
			)
		);
	}
}
