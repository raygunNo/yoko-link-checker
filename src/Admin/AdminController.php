<?php
/**
 * Admin Controller class.
 *
 * Handles admin menu registration and page routing.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Admin;

/**
 * Admin controller class.
 *
 * @since 1.0.0
 */
class AdminController {

	/**
	 * Menu slug.
	 */
	public const MENU_SLUG = 'yoko-link-checker';

	/**
	 * Dashboard page instance.
	 *
	 * @var DashboardPage
	 */
	private DashboardPage $dashboard_page;

	/**
	 * Results page instance.
	 *
	 * @var ResultsPage
	 */
	private ResultsPage $results_page;

	/**
	 * AJAX handler instance.
	 *
	 * @var AjaxHandler
	 */
	private AjaxHandler $ajax_handler;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param DashboardPage $dashboard_page Dashboard page instance.
	 * @param ResultsPage   $results_page   Results page instance.
	 * @param AjaxHandler   $ajax_handler   AJAX handler instance.
	 */
	public function __construct(
		DashboardPage $dashboard_page,
		ResultsPage $results_page,
		AjaxHandler $ajax_handler
	) {
		$this->dashboard_page = $dashboard_page;
		$this->results_page   = $results_page;
		$this->ajax_handler   = $ajax_handler;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// Register AJAX handlers.
		$this->ajax_handler->register();
	}

	/**
	 * Register admin menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_menu(): void {
		// Use manage_options as fallback if custom caps don't exist.
		$view_cap     = current_user_can( 'ylc_view_results' ) ? 'ylc_view_results' : 'manage_options';
		$settings_cap = current_user_can( 'ylc_manage_settings' ) ? 'ylc_manage_settings' : 'manage_options';

		// Main menu page.
		add_menu_page(
			__( 'Link Checker', 'yoko-link-checker' ),
			__( 'Link Checker', 'yoko-link-checker' ),
			$view_cap,
			self::MENU_SLUG,
			[ $this->dashboard_page, 'render' ],
			'dashicons-admin-links',
			80
		);

		// Dashboard submenu (same as main).
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Dashboard', 'yoko-link-checker' ),
			__( 'Dashboard', 'yoko-link-checker' ),
			$view_cap,
			self::MENU_SLUG,
			[ $this->dashboard_page, 'render' ]
		);

		// Results submenu.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Broken Links', 'yoko-link-checker' ),
			__( 'Broken Links', 'yoko-link-checker' ),
			$view_cap,
			self::MENU_SLUG . '-results',
			[ $this->results_page, 'render' ]
		);

		// Settings submenu.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'yoko-link-checker' ),
			__( 'Settings', 'yoko-link-checker' ),
			$settings_cap,
			self::MENU_SLUG . '-settings',
			[ $this, 'render_settings' ]
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// Only load on plugin pages.
		if ( ! $this->is_plugin_page( $hook_suffix ) ) {
			return;
		}

		// Styles.
		wp_enqueue_style(
			'ylc-admin',
			YLC_PLUGIN_URL . 'assets/css/admin.css',
			[],
			YLC_VERSION
		);

		// Scripts.
		wp_enqueue_script(
			'ylc-admin',
			YLC_PLUGIN_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			YLC_VERSION,
			true
		);

		// Localize script.
		wp_localize_script( 'ylc-admin', 'ylcAdmin', $this->get_js_data() );
	}

	/**
	 * Check if current page is a plugin page.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix Hook suffix.
	 * @return bool
	 */
	private function is_plugin_page( string $hook_suffix ): bool {
		// Check if the hook contains our menu slug.
		return strpos( $hook_suffix, self::MENU_SLUG ) !== false;
	}

	/**
	 * Get JavaScript localization data.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_js_data(): array {
		return [
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'ylc_admin' ),
			'strings'  => [
				'confirmStart'  => __( 'Start a new scan?', 'yoko-link-checker' ),
				'confirmCancel' => __( 'Cancel the current scan?', 'yoko-link-checker' ),
				'confirmIgnore' => __( 'Ignore this link?', 'yoko-link-checker' ),
				'scanning'      => __( 'Scanning...', 'yoko-link-checker' ),
				'checking'      => __( 'Checking...', 'yoko-link-checker' ),
				'complete'      => __( 'Complete', 'yoko-link-checker' ),
				'error'         => __( 'An error occurred. Please try again.', 'yoko-link-checker' ),
			],
		];
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings(): void {
		// Handle form submission.
		if ( isset( $_POST['ylc_settings_nonce'] ) ) {
			$this->handle_settings_save();
		}

		$settings = $this->get_settings();

		include YLC_PLUGIN_DIR . 'templates/admin/settings.php';
	}

	/**
	 * Handle settings save.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function handle_settings_save(): void {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! wp_verify_nonce( wp_unslash( $_POST['ylc_settings_nonce'] ?? '' ), 'ylc_settings' ) ) {
			add_settings_error( 'ylc_settings', 'nonce_error', __( 'Security check failed.', 'yoko-link-checker' ) );
			return;
		}

		if ( ! current_user_can( 'ylc_manage_settings' ) ) {
			add_settings_error( 'ylc_settings', 'permission_error', __( 'Permission denied.', 'yoko-link-checker' ) );
			return;
		}

		// Sanitize and save settings.
		$post_types = isset( $_POST['ylc_post_types'] ) && is_array( $_POST['ylc_post_types'] )
			? array_map( 'sanitize_key', $_POST['ylc_post_types'] )
			: [ 'post', 'page' ];

		$check_timeout = isset( $_POST['ylc_check_timeout'] )
			? absint( $_POST['ylc_check_timeout'] )
			: 30;

		$auto_scan = isset( $_POST['ylc_auto_scan_enabled'] );

		$scan_frequency = isset( $_POST['ylc_auto_scan_frequency'] )
			? sanitize_key( $_POST['ylc_auto_scan_frequency'] )
			: 'weekly';

		update_option( 'ylc_post_types', $post_types );
		update_option( 'ylc_check_timeout', min( 120, max( 5, $check_timeout ) ) );
		update_option( 'ylc_auto_scan_enabled', $auto_scan );
		update_option( 'ylc_auto_scan_frequency', $scan_frequency );

		add_settings_error( 'ylc_settings', 'saved', __( 'Settings saved.', 'yoko-link-checker' ), 'success' );
	}

	/**
	 * Get current settings.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_settings(): array {
		return [
			'post_types'          => get_option( 'ylc_post_types', [ 'post', 'page' ] ),
			'check_timeout'       => get_option( 'ylc_check_timeout', 30 ),
			'auto_scan_enabled'   => get_option( 'ylc_auto_scan_enabled', false ),
			'auto_scan_frequency' => get_option( 'ylc_auto_scan_frequency', 'weekly' ),
		];
	}
}
