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
use YokoLinkChecker\Repository\UrlRepository;
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
	 * URL repository instance.
	 *
	 * @var UrlRepository
	 */
	private UrlRepository $url_repository;

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
	 * @param UrlRepository  $url_repository  URL repository.
	 */
	public function __construct( LinkRepository $link_repository, UrlRepository $url_repository ) {
		$this->link_repository = $link_repository;
		$this->url_repository  = $url_repository;
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

		if ( ! current_user_can( 'yoko_lc_manage_scans' ) && ! current_user_can( 'manage_options' ) ) {
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
		$link = $this->link_repository->find( $link_id );

		if ( ! $link ) {
			return;
		}

		$result = $this->url_repository->mark_ignored( $link->url_id );

		if ( ! $result ) {
			$redirect_url = remove_query_arg( array( 'action', 'link_id', '_wpnonce' ) );
			wp_safe_redirect( add_query_arg( 'ylc_error', 'ignore_failed', $redirect_url ) );
			exit;
		}

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
		$link = $this->link_repository->find( $link_id );

		if ( ! $link ) {
			return;
		}

		$result = $this->url_repository->unmark_ignored( $link->url_id );

		if ( ! $result ) {
			$redirect_url = remove_query_arg( array( 'action', 'link_id', '_wpnonce' ) );
			wp_safe_redirect( add_query_arg( 'ylc_error', 'unignore_failed', $redirect_url ) );
			exit;
		}

		/**
		 * Fires when a link is unignored.
		 *
		 * @since 1.0.0
		 * @param int $link_id Link ID.
		 */
		do_action( 'yoko_lc_link_unignored', $link_id );
	}

	/**
	 * Handle CSV export using streaming for constant memory usage.
	 *
	 * @since 1.0.3
	 * @since 1.0.9 Switched to streaming generator for memory efficiency.
	 * @return void
	 */
	public function handle_export(): void {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'yoko_lc_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'yoko-link-checker' ) );
		}

		if ( ! current_user_can( 'yoko_lc_view_results' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'yoko-link-checker' ) );
		}

		// Set headers for CSV download.
		$filename = 'yoko-link-checker-export-' . gmdate( 'Y-m-d-His' ) . '.csv';
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Disable output buffering to stream directly to the client.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- ob_end_clean may warn if no buffer active.
		// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedWhile -- Intentionally empty loop body to clear all buffers.
		while ( @ob_end_clean() ) {
			// Clear all output buffers.
		}

		// Create output stream.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Using php://output for streaming CSV.
		$output = fopen( 'php://output', 'w' );

		// Write UTF-8 BOM for Excel compatibility.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Write header row with clear column names for non-technical users.
		fputcsv(
			$output,
			array(
				__( 'Broken URL', 'yoko-link-checker' ),
				__( 'Status', 'yoko-link-checker' ),
				__( 'HTTP Code', 'yoko-link-checker' ),
				__( 'Error Details', 'yoko-link-checker' ),
				__( 'Source URL', 'yoko-link-checker' ),
				__( 'Source Title', 'yoko-link-checker' ),
				__( 'Source Type', 'yoko-link-checker' ),
				__( 'Link Text', 'yoko-link-checker' ),
				__( 'Last Checked', 'yoko-link-checker' ),
			)
		);

		// Stream data rows from the generator -- constant memory regardless of dataset size.
		$row_count = 0;
		foreach ( $this->link_repository->stream_for_export() as $link ) {
			fputcsv(
				$output,
				array(
					$link->url ?? '',
					$link->status ?? '',
					$link->http_code ?? '',
					$link->error_message ?? '',
					$link->source_url ?? '',
					$link->post_title ?? '',
					$link->post_type ?? '',
					$link->link_text ?? '',
					$link->last_checked ?? '',
				)
			);

			++$row_count;
			if ( 0 === $row_count % 500 ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fflush
				fflush( $output );
			}
		}

		// Final flush to ensure all remaining rows are written.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fflush
		fflush( $output );

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
