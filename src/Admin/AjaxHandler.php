<?php
/**
 * AJAX Handler class.
 *
 * Handles all AJAX requests for the plugin.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Admin;

use YokoLinkChecker\Scanner\ScanOrchestrator;
use YokoLinkChecker\Scanner\BatchProcessor;
use YokoLinkChecker\Repository\UrlRepository;
use YokoLinkChecker\Repository\LinkRepository;

/**
 * AJAX handler class.
 *
 * @since 1.0.0
 */
class AjaxHandler {

	/**
	 * Scan orchestrator instance.
	 *
	 * @var ScanOrchestrator
	 */
	private ScanOrchestrator $scan_orchestrator;

	/**
	 * Batch processor instance.
	 *
	 * @var BatchProcessor
	 */
	private BatchProcessor $batch_processor;

	/**
	 * URL repository instance.
	 *
	 * @var UrlRepository
	 */
	private UrlRepository $url_repository;

	/**
	 * Link repository instance.
	 *
	 * @var LinkRepository
	 */
	private LinkRepository $link_repository;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param ScanOrchestrator $scan_orchestrator Scan orchestrator.
	 * @param BatchProcessor   $batch_processor   Batch processor.
	 * @param UrlRepository    $url_repository    URL repository.
	 * @param LinkRepository   $link_repository   Link repository.
	 */
	public function __construct(
		ScanOrchestrator $scan_orchestrator,
		BatchProcessor $batch_processor,
		UrlRepository $url_repository,
		LinkRepository $link_repository
	) {
		$this->scan_orchestrator = $scan_orchestrator;
		$this->batch_processor   = $batch_processor;
		$this->url_repository    = $url_repository;
		$this->link_repository   = $link_repository;
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register(): void {
		// Scan actions.
		add_action( 'wp_ajax_ylc_start_scan', [ $this, 'start_scan' ] );
		add_action( 'wp_ajax_ylc_pause_scan', [ $this, 'pause_scan' ] );
		add_action( 'wp_ajax_ylc_resume_scan', [ $this, 'resume_scan' ] );
		add_action( 'wp_ajax_ylc_cancel_scan', [ $this, 'cancel_scan' ] );
		add_action( 'wp_ajax_ylc_get_scan_status', [ $this, 'get_scan_status' ] );

		// Link actions.
		add_action( 'wp_ajax_ylc_recheck_url', [ $this, 'recheck_url' ] );
		add_action( 'wp_ajax_ylc_ignore_link', [ $this, 'ignore_link' ] );
		add_action( 'wp_ajax_ylc_unignore_link', [ $this, 'unignore_link' ] );

		// Stats.
		add_action( 'wp_ajax_ylc_get_stats', [ $this, 'get_stats' ] );
	}

	/**
	 * Start a new scan.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function start_scan(): void {
		$this->verify_request( 'ylc_manage_scans' );

		try {
			error_log( '[YLC Debug] start_scan AJAX called' );
			$scan_id = $this->scan_orchestrator->start_scan( 'full' );
			error_log( '[YLC Debug] start_scan returned: ' . var_export( $scan_id, true ) );

			if ( ! $scan_id ) {
				wp_send_json_error(
					[
						'message' => __( 'A scan is already running.', 'yoko-link-checker' ),
					]
				);
			}

			wp_send_json_success(
				[
					'scan_id' => $scan_id,
					'message' => __( 'Scan started.', 'yoko-link-checker' ),
				]
			);
		} catch ( \Throwable $e ) {
			error_log( '[YLC ERROR] start_scan exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
			error_log( '[YLC ERROR] Stack trace: ' . $e->getTraceAsString() );
			wp_send_json_error( [ 'message' => 'Error: ' . $e->getMessage() ] );
		}
	}

	/**
	 * Pause a scan.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function pause_scan(): void {
		$this->verify_request( 'ylc_manage_scans' );

		$scan_id = isset( $_POST['scan_id'] ) ? absint( $_POST['scan_id'] ) : 0;

		if ( ! $scan_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid scan ID.', 'yoko-link-checker' ) ] );
		}

		$result = $this->scan_orchestrator->pause_scan( $scan_id );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Could not pause scan.', 'yoko-link-checker' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'Scan paused.', 'yoko-link-checker' ) ] );
	}

	/**
	 * Resume a scan.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function resume_scan(): void {
		$this->verify_request( 'ylc_manage_scans' );

		$scan_id = isset( $_POST['scan_id'] ) ? absint( $_POST['scan_id'] ) : 0;

		if ( ! $scan_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid scan ID.', 'yoko-link-checker' ) ] );
		}

		$result = $this->scan_orchestrator->resume_scan( $scan_id );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Could not resume scan.', 'yoko-link-checker' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'Scan resumed.', 'yoko-link-checker' ) ] );
	}

	/**
	 * Cancel a scan.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function cancel_scan(): void {
		$this->verify_request( 'ylc_manage_scans' );

		$scan_id = isset( $_POST['scan_id'] ) ? absint( $_POST['scan_id'] ) : 0;

		if ( ! $scan_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid scan ID.', 'yoko-link-checker' ) ] );
		}

		$result = $this->scan_orchestrator->cancel_scan( $scan_id );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Could not cancel scan.', 'yoko-link-checker' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'Scan cancelled.', 'yoko-link-checker' ) ] );
	}

	/**
	 * Get scan status.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function get_scan_status(): void {
		$this->verify_request( 'ylc_view_results' );

		try {
			$status = $this->scan_orchestrator->get_status();

			error_log( '[YLC Debug] get_scan_status called. Status: ' . wp_json_encode( $status ) );

			// If scan is running, process a batch via AJAX to avoid WP-Cron dependency.
			if ( $status && 'running' === $status['status'] ) {
				error_log( '[YLC Debug] Processing batch for scan ID: ' . $status['scan_id'] );
				$this->scan_orchestrator->process_batch( $status['scan_id'] );
				// Refresh status after processing.
				$status = $this->scan_orchestrator->get_status();
				error_log( '[YLC Debug] After batch, status: ' . wp_json_encode( $status ) );
			}
		} catch ( \Throwable $e ) {
			error_log( '[YLC ERROR] get_scan_status exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
			error_log( '[YLC ERROR] Stack trace: ' . $e->getTraceAsString() );
			wp_send_json_error( [ 'message' => 'Error: ' . $e->getMessage() ] );
			return;
		}

		if ( ! $status ) {
			wp_send_json_success(
				[
					'running'  => false,
					'status'   => null,
					'progress' => 0,
				]
			);
		}

		wp_send_json_success(
			[
				'running'  => 'running' === $status['status'],
				'status'   => $status,
				'progress' => $status['progress'],
			]
		);
	}

	/**
	 * Recheck a URL.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function recheck_url(): void {
		$this->verify_request( 'ylc_manage_scans' );

		$url_id = isset( $_POST['url_id'] ) ? absint( $_POST['url_id'] ) : 0;

		if ( ! $url_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid URL ID.', 'yoko-link-checker' ) ] );
		}

		$result = $this->batch_processor->recheck_url( $url_id );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'URL not found.', 'yoko-link-checker' ) ] );
		}

		// Get updated URL data.
		$url = $this->url_repository->find( $url_id );

		wp_send_json_success(
			[
				'message' => __( 'URL rechecked.', 'yoko-link-checker' ),
				'url'     => $url ? [
					'status'    => $url->status,
					'http_code' => $url->http_code,
				] : null,
			]
		);
	}

	/**
	 * Ignore a link.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ignore_link(): void {
		$this->verify_request( 'ylc_manage_scans' );

		$link_id = isset( $_POST['link_id'] ) ? absint( $_POST['link_id'] ) : 0;

		if ( ! $link_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid link ID.', 'yoko-link-checker' ) ] );
		}

		$result = $this->link_repository->update( $link_id, [ 'ignored' => 1 ] );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Could not ignore link.', 'yoko-link-checker' ) ] );
		}

		/**
		 * Fires when a link is ignored via AJAX.
		 *
		 * @since 1.0.0
		 * @param int $link_id Link ID.
		 */
		do_action( 'ylc_link_ignored', $link_id );

		wp_send_json_success( [ 'message' => __( 'Link ignored.', 'yoko-link-checker' ) ] );
	}

	/**
	 * Un-ignore a link.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function unignore_link(): void {
		$this->verify_request( 'ylc_manage_scans' );

		$link_id = isset( $_POST['link_id'] ) ? absint( $_POST['link_id'] ) : 0;

		if ( ! $link_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid link ID.', 'yoko-link-checker' ) ] );
		}

		$result = $this->link_repository->update( $link_id, [ 'ignored' => 0 ] );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Could not un-ignore link.', 'yoko-link-checker' ) ] );
		}

		/**
		 * Fires when a link is un-ignored via AJAX.
		 *
		 * @since 1.0.0
		 * @param int $link_id Link ID.
		 */
		do_action( 'ylc_link_unignored', $link_id );

		wp_send_json_success( [ 'message' => __( 'Link un-ignored.', 'yoko-link-checker' ) ] );
	}

	/**
	 * Get stats.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function get_stats(): void {
		$this->verify_request( 'ylc_view_results' );

		$stats = [
			'total'     => $this->url_repository->count_all(),
			'broken'    => $this->url_repository->count_by_status( 'broken' ),
			'warning'   => $this->url_repository->count_by_status( 'warning' ),
			'redirect'  => $this->url_repository->count_by_status( 'redirect' ),
			'valid'     => $this->url_repository->count_by_status( 'valid' ),
			'pending'   => $this->url_repository->count_by_status( 'pending' ),
		];

		wp_send_json_success( $stats );
	}

	/**
	 * Verify AJAX request.
	 *
	 * @since 1.0.0
	 * @param string $capability Required capability.
	 * @return void
	 */
	private function verify_request( string $capability ): void {
		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'ylc_admin' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Security check failed.', 'yoko-link-checker' ),
				],
				403
			);
		}

		// Check capability with fallback to manage_options.
		$has_cap = current_user_can( $capability );
		if ( ! $has_cap ) {
			$has_cap = current_user_can( 'manage_options' );
		}

		if ( ! $has_cap ) {
			wp_send_json_error(
				[
					'message' => __( 'Permission denied.', 'yoko-link-checker' ),
				],
				403
			);
		}
	}
}
