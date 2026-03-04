<?php
/**
 * Plugin Activator.
 *
 * Handles plugin activation: creates database tables,
 * sets default options, and schedules initial cron events.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker;

/**
 * Handles plugin activation.
 *
 * @since 1.0.0
 */
final class Activator {

	/**
	 * Database schema version.
	 *
	 * Increment this when schema changes require migration.
	 *
	 * @var string
	 */
	public const SCHEMA_VERSION = '1.0.0';

	/**
	 * Option key for tracking installed schema version.
	 *
	 * @var string
	 */
	public const SCHEMA_VERSION_OPTION = 'yoko_lc_schema_version';

	/**
	 * Run activation routine.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function activate(): void {
		self::create_tables();
		self::set_default_options();
		self::schedule_cron_events();
		self::set_capabilities();

		// Flush rewrite rules on next page load.
		set_transient( 'yoko_lc_flush_rewrite', 1, 60 );

		// Record activation.
		update_option( 'yoko_lc_activated_at', current_time( 'mysql' ) );
	}

	/**
	 * Create custom database tables.
	 *
	 * Uses dbDelta for safe, idempotent table creation/updates.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Table name prefixes.
		$urls_table  = $wpdb->prefix . 'yoko_lc_urls';
		$links_table = $wpdb->prefix . 'yoko_lc_links';
		$scans_table = $wpdb->prefix . 'yoko_lc_scans';

		// SQL for yoko_lc_urls table.
		// Stores unique URLs with their check results.
		$sql_urls = "CREATE TABLE {$urls_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			url_hash CHAR(64) NOT NULL,
			url TEXT NOT NULL,
			url_normalized TEXT NOT NULL,
			is_internal TINYINT(1) NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			http_code SMALLINT UNSIGNED DEFAULT NULL,
			final_url TEXT DEFAULT NULL,
			redirect_count TINYINT UNSIGNED DEFAULT 0,
			response_time INT UNSIGNED DEFAULT NULL,
			error_type VARCHAR(50) DEFAULT NULL,
			error_message TEXT DEFAULT NULL,
			check_count INT UNSIGNED NOT NULL DEFAULT 0,
			first_seen DATETIME NOT NULL,
			last_checked DATETIME DEFAULT NULL,
			next_check DATETIME DEFAULT NULL,
			is_ignored TINYINT(1) NOT NULL DEFAULT 0,
			ignore_reason VARCHAR(255) DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY url_hash (url_hash),
			KEY status (status),
			KEY is_internal (is_internal),
			KEY next_check (next_check),
			KEY is_ignored (is_ignored),
			KEY status_ignored (status, is_ignored),
			KEY status_ignored_id (status, is_ignored, id)
		) {$charset_collate};";

		// SQL for yoko_lc_links table.
		// Stores link occurrences in content (many-to-one with urls).
		$sql_links = "CREATE TABLE {$links_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			url_id BIGINT UNSIGNED NOT NULL,
			source_id BIGINT UNSIGNED NOT NULL,
			source_type VARCHAR(50) NOT NULL,
			source_field VARCHAR(50) NOT NULL DEFAULT 'post_content',
			anchor_text TEXT DEFAULT NULL,
			link_context TEXT DEFAULT NULL,
			link_position INT UNSIGNED DEFAULT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY url_id (url_id),
			KEY source_id (source_id),
			KEY source_type (source_type),
			KEY source_composite (source_id, source_type, source_field)
		) {$charset_collate};";

		// SQL for yoko_lc_scans table.
		// Stores scan run metadata and state for resumability.
		$sql_scans = "CREATE TABLE {$scans_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			scan_type VARCHAR(30) NOT NULL DEFAULT 'full',
			started_at DATETIME DEFAULT NULL,
			completed_at DATETIME DEFAULT NULL,
			total_posts INT UNSIGNED DEFAULT 0,
			processed_posts INT UNSIGNED DEFAULT 0,
			total_urls INT UNSIGNED DEFAULT 0,
			checked_urls INT UNSIGNED DEFAULT 0,
			last_post_id BIGINT UNSIGNED DEFAULT 0,
			last_url_id BIGINT UNSIGNED DEFAULT 0,
			current_phase VARCHAR(30) DEFAULT 'discovery',
			error_message TEXT DEFAULT NULL,
			options TEXT DEFAULT NULL,
			PRIMARY KEY (id),
			KEY status (status),
			KEY scan_type (scan_type)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql_urls );
		dbDelta( $sql_links );
		dbDelta( $sql_scans );

		// Store schema version.
		update_option( self::SCHEMA_VERSION_OPTION, self::SCHEMA_VERSION );
	}

	/**
	 * Set default plugin options.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function set_default_options(): void {
		if ( false === get_option( 'yoko_lc_post_types' ) ) {
			add_option( 'yoko_lc_post_types', array( 'post', 'page' ) );
		}

		if ( false === get_option( 'yoko_lc_check_timeout' ) ) {
			add_option( 'yoko_lc_check_timeout', 30 );
		}

		if ( false === get_option( 'yoko_lc_auto_scan_enabled' ) ) {
			add_option( 'yoko_lc_auto_scan_enabled', false );
		}

		if ( false === get_option( 'yoko_lc_auto_scan_frequency' ) ) {
			add_option( 'yoko_lc_auto_scan_frequency', 'weekly' );
		}
	}

	/**
	 * Schedule cron events.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function schedule_cron_events(): void {
		// Register custom cron schedules if needed.
		// phpcs:disable WordPress.WP.CronInterval.CronSchedulesInterval -- 5-minute interval is intentional for link checking.
		add_filter(
			'cron_schedules',
			function ( array $schedules ): array {
				$schedules['yoko_lc_five_minutes'] = array(
					'interval' => 300,
					'display'  => __( 'Every Five Minutes', 'yoko-link-checker' ),
				);
				return $schedules;
			}
		);
		// phpcs:enable WordPress.WP.CronInterval.CronSchedulesInterval

		// Note: We don't auto-schedule scans.
		// Scans are user-initiated in the MVP.
		// Future: Add scheduled scan option.
	}

	/**
	 * Set up custom capabilities.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function set_capabilities(): void {
		$admin_role = get_role( 'administrator' );

		if ( $admin_role ) {
			$admin_role->add_cap( 'yoko_lc_manage_scans' );
			$admin_role->add_cap( 'yoko_lc_view_results' );
			$admin_role->add_cap( 'yoko_lc_manage_settings' );
		}
	}

	/**
	 * Check if table exists.
	 *
	 * @since 1.0.0
	 * @param string $table_name Full table name including prefix.
	 * @return bool
	 */
	public static function table_exists( string $table_name ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		return $result === $table_name;
	}

	/**
	 * Check if schema needs upgrade.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function needs_upgrade(): bool {
		$installed_version = get_option( self::SCHEMA_VERSION_OPTION, '0.0.0' );
		return version_compare( $installed_version, self::SCHEMA_VERSION, '<' );
	}
}
