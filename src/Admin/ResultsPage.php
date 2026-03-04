<?php
/**
 * Results Page class.
 *
 * Handles the broken links results list display.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Admin;

use YokoLinkChecker\Repository\LinkRepository;
use YokoLinkChecker\Model\Url;

/**
 * Results page class.
 *
 * @since 1.0.0
 */
class ResultsPage {

	/**
	 * Link repository instance.
	 *
	 * @var LinkRepository
	 */
	private LinkRepository $link_repository;

	/**
	 * List table instance.
	 *
	 * @var LinksListTable|null
	 */
	private ?LinksListTable $list_table = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param LinkRepository $link_repository Link repository.
	 */
	public function __construct( LinkRepository $link_repository ) {
		$this->link_repository = $link_repository;
	}

	/**
	 * Render the results page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render(): void {
		// Handle actions.
		$this->handle_actions();

		// Get filter parameters.
		$status_filter = $this->get_status_filter();
		$filters       = $this->get_filters();

		// Create list table.
		$this->list_table = new LinksListTable( $this->link_repository );
		$this->list_table->set_filter( $status_filter );
		$this->list_table->prepare_items();

		include YOKO_LC_PLUGIN_DIR . 'templates/admin/results.php';
	}

	/**
	 * Handle page actions.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function handle_actions(): void {
		// Handle export action first (doesn't require link_id).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
		if ( isset( $_GET['action'] ) && 'export' === $_GET['action'] ) {
			$this->handle_export();
			return;
		}

		// Handle bulk actions from list table.
		if ( isset( $_POST['_wpnonce'] ) && isset( $_POST['action'] ) ) {
			// Bulk actions are handled by the list table.
			return;
		}

		// Handle single actions.
		if ( ! isset( $_GET['action'] ) || ! isset( $_GET['link_id'] ) ) {
			return;
		}

		$action  = sanitize_key( $_GET['action'] );
		$link_id = absint( $_GET['link_id'] );
		$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, "yoko_lc_action_{$link_id}" ) ) {
			wp_die( esc_html__( 'Security check failed.', 'yoko-link-checker' ) );
		}

		if ( ! current_user_can( 'yoko_lc_manage_scans' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'yoko-link-checker' ) );
		}

		switch ( $action ) {
			case 'ignore':
				$this->ignore_link( $link_id );
				break;

			case 'unignore':
				$this->unignore_link( $link_id );
				break;
		}

		// Redirect to remove action from URL.
		$redirect_url = remove_query_arg( array( 'action', 'link_id', '_wpnonce' ) );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Get current status filter.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_status_filter(): string {
		$valid_statuses = array(
			'all',
			Url::STATUS_BROKEN,
			Url::STATUS_WARNING,
			Url::STATUS_REDIRECT,
			Url::STATUS_BLOCKED,
			Url::STATUS_TIMEOUT,
			Url::STATUS_PENDING,
			Url::STATUS_VALID,
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Filter parameter doesn't require nonce.
		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'broken';

		return in_array( $status, $valid_statuses, true ) ? $status : 'broken';
	}

	/**
	 * Get filter options for display.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_filters(): array {
		return array(
			'all'      => __( 'All', 'yoko-link-checker' ),
			'broken'   => __( 'Broken', 'yoko-link-checker' ),
			'warning'  => __( 'Warning', 'yoko-link-checker' ),
			'redirect' => __( 'Redirect', 'yoko-link-checker' ),
			'blocked'  => __( 'Blocked', 'yoko-link-checker' ),
			'timeout'  => __( 'Timeout', 'yoko-link-checker' ),
			'pending'  => __( 'Pending', 'yoko-link-checker' ),
			'valid'    => __( 'Valid', 'yoko-link-checker' ),
		);
	}

	/**
	 * Ignore a link.
	 *
	 * @since 1.0.0
	 * @param int $link_id Link ID.
	 * @return void
	 */
	private function ignore_link( int $link_id ): void {
		$this->link_repository->update(
			$link_id,
			array( 'ignored' => 1 )
		);

		/**
		 * Fires when a link is ignored.
		 *
		 * @since 1.0.0
		 * @param int $link_id Link ID.
		 */
		do_action( 'yoko_lc_link_ignored', $link_id );
	}

	/**
	 * Unignore a link.
	 *
	 * @since 1.0.0
	 * @param int $link_id Link ID.
	 * @return void
	 */
	private function unignore_link( int $link_id ): void {
		$this->link_repository->update(
			$link_id,
			array( 'ignored' => 0 )
		);

		/**
		 * Fires when a link is unignored.
		 *
		 * @since 1.0.0
		 * @param int $link_id Link ID.
		 */
		do_action( 'yoko_lc_link_unignored', $link_id );
	}

	/**
	 * Handle CSV export.
	 *
	 * @since 1.0.3
	 * @return void
	 */
	private function handle_export(): void {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'yoko_lc_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'yoko-link-checker' ) );
		}

		if ( ! current_user_can( 'yoko_lc_view_results' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'yoko-link-checker' ) );
		}

		// Get all links for export.
		$links = $this->link_repository->get_all_for_export();

		// Set headers for CSV download.
		$filename = 'yoko-link-checker-export-' . gmdate( 'Y-m-d-His' ) . '.csv';
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Create output stream.
		$output = fopen( 'php://output', 'w' );

		// Write UTF-8 BOM for Excel compatibility.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Write header row.
		fputcsv(
			$output,
			array(
				__( 'URL', 'yoko-link-checker' ),
				__( 'Status', 'yoko-link-checker' ),
				__( 'HTTP Code', 'yoko-link-checker' ),
				__( 'Found In', 'yoko-link-checker' ),
				__( 'Post Type', 'yoko-link-checker' ),
				__( 'Link Text', 'yoko-link-checker' ),
				__( 'Error Message', 'yoko-link-checker' ),
				__( 'Last Checked', 'yoko-link-checker' ),
			)
		);

		// Write data rows.
		foreach ( $links as $link ) {
			fputcsv(
				$output,
				array(
					$link->url ?? '',
					$link->status ?? '',
					$link->http_code ?? '',
					$link->post_title ?? '',
					$link->post_type ?? '',
					$link->link_text ?? '',
					$link->error_message ?? '',
					$link->last_checked ?? '',
				)
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Using php://output stream for CSV export.
		fclose( $output );
		exit;
	}

	/**
	 * Get the list table instance.
	 *
	 * @since 1.0.0
	 * @return LinksListTable|null
	 */
	public function get_list_table(): ?LinksListTable {
		return $this->list_table;
	}
}
