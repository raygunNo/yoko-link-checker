<?php
/**
 * Dashboard Page class.
 *
 * Handles the main admin dashboard display.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Admin;

use YokoLinkChecker\Repository\UrlRepository;
use YokoLinkChecker\Repository\ScanRepository;
use YokoLinkChecker\Scanner\ScanOrchestrator;
use YokoLinkChecker\Model\Url;

/**
 * Dashboard page class.
 *
 * @since 1.0.0
 */
class DashboardPage {

	/**
	 * URL repository instance.
	 *
	 * @var UrlRepository
	 */
	private UrlRepository $url_repository;

	/**
	 * Scan repository instance.
	 *
	 * @var ScanRepository
	 */
	private ScanRepository $scan_repository;

	/**
	 * Scan orchestrator instance.
	 *
	 * @var ScanOrchestrator
	 */
	private ScanOrchestrator $scan_orchestrator;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param UrlRepository    $url_repository    URL repository.
	 * @param ScanRepository   $scan_repository   Scan repository.
	 * @param ScanOrchestrator $scan_orchestrator Scan orchestrator.
	 */
	public function __construct(
		UrlRepository $url_repository,
		ScanRepository $scan_repository,
		ScanOrchestrator $scan_orchestrator
	) {
		$this->url_repository    = $url_repository;
		$this->scan_repository   = $scan_repository;
		$this->scan_orchestrator = $scan_orchestrator;
	}

	/**
	 * Render the dashboard page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render(): void {
		$stats           = $this->get_stats();
		$scan_status     = $this->scan_orchestrator->get_status();
		$recent_broken   = $this->get_recent_broken();
		$status_breakdown = $this->get_status_breakdown();

		include YLC_PLUGIN_DIR . 'templates/admin/dashboard.php';
	}

	/**
	 * Get link statistics.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_stats(): array {
		return [
			'total_urls'   => $this->url_repository->count(),
			'broken'       => $this->url_repository->count( Url::STATUS_BROKEN ),
			'warnings'     => $this->url_repository->count( Url::STATUS_WARNING ),
			'redirects'    => $this->url_repository->count( Url::STATUS_REDIRECT ),
			'valid'        => $this->url_repository->count( Url::STATUS_VALID ),
			'pending'      => $this->url_repository->count( Url::STATUS_PENDING ),
			'blocked'      => $this->url_repository->count( Url::STATUS_BLOCKED ),
			'timeouts'     => $this->url_repository->count( Url::STATUS_TIMEOUT ),
			'errors'       => $this->url_repository->count( Url::STATUS_ERROR ),
			'total_scans'  => count( $this->scan_repository->get_recent( 100 ) ),
			'last_scan'    => $this->get_last_completed_scan(),
		];
	}

	/**
	 * Get status breakdown for chart.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_status_breakdown(): array {
		$statuses = [
			Url::STATUS_VALID    => [
				'label' => __( 'Valid', 'yoko-link-checker' ),
				'color' => '#4caf50',
			],
			Url::STATUS_BROKEN   => [
				'label' => __( 'Broken', 'yoko-link-checker' ),
				'color' => '#f44336',
			],
			Url::STATUS_WARNING  => [
				'label' => __( 'Warning', 'yoko-link-checker' ),
				'color' => '#ff9800',
			],
			Url::STATUS_REDIRECT => [
				'label' => __( 'Redirect', 'yoko-link-checker' ),
				'color' => '#2196f3',
			],
			Url::STATUS_BLOCKED  => [
				'label' => __( 'Blocked', 'yoko-link-checker' ),
				'color' => '#9c27b0',
			],
			Url::STATUS_TIMEOUT  => [
				'label' => __( 'Timeout', 'yoko-link-checker' ),
				'color' => '#795548',
			],
			Url::STATUS_PENDING  => [
				'label' => __( 'Pending', 'yoko-link-checker' ),
				'color' => '#9e9e9e',
			],
		];

		$breakdown = [];

		foreach ( $statuses as $status => $config ) {
			$count = $this->url_repository->count( $status );
			if ( $count > 0 ) {
				$breakdown[] = [
					'status' => $status,
					'label'  => $config['label'],
					'count'  => $count,
					'color'  => $config['color'],
				];
			}
		}

		return $breakdown;
	}

	/**
	 * Get the last completed scan.
	 *
	 * @since 1.0.0
	 * @return \YokoLinkChecker\Model\Scan|null
	 */
	private function get_last_completed_scan() {
		$recent = $this->scan_repository->get_recent( 10 );
		foreach ( $recent as $scan ) {
			if ( $scan->status === \YokoLinkChecker\Model\Scan::STATUS_COMPLETED ) {
				return $scan;
			}
		}
		return null;
	}

	/**
	 * Get recent broken links.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_recent_broken(): array {
		global $wpdb;

		$urls_table  = $wpdb->prefix . 'ylc_urls';
		$links_table = $wpdb->prefix . 'ylc_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.id, u.url, u.http_code, u.error_message, u.last_checked,
				        l.source_id, l.source_type, l.anchor_text
				 FROM {$urls_table} u
				 LEFT JOIN {$links_table} l ON u.id = l.url_id
				 WHERE u.status = %s
				 ORDER BY u.last_checked DESC
				 LIMIT 10",
				Url::STATUS_BROKEN
			)
		);

		$broken = [];

		foreach ( $results as $row ) {
			$post_title = '';
			$source_id  = $row->source_id ? (int) $row->source_id : 0;

			// Only get post title for post source types.
			if ( $source_id && in_array( $row->source_type, [ 'post', 'page' ], true ) ) {
				$post       = get_post( $source_id );
				$post_title = $post ? $post->post_title : '';
			}

			$broken[] = [
				'id'            => (int) $row->id,
				'url'           => $row->url,
				'http_code'     => (int) $row->http_code,
				'error_message' => $row->error_message,
				'last_checked'  => $row->last_checked,
				'source_id'     => $source_id,
				'source_type'   => $row->source_type ?? '',
				'post_title'    => $post_title,
				'anchor_text'   => $row->anchor_text,
			];
		}

		return $broken;
	}

	/**
	 * Get formatted last scan time.
	 *
	 * @since 1.0.0
	 * @param \YokoLinkChecker\Model\Scan|null $scan Scan model.
	 * @return string
	 */
	public function format_last_scan( $scan ): string {
		if ( ! $scan || ! $scan->completed_at ) {
			return __( 'Never', 'yoko-link-checker' );
		}

		$timestamp = strtotime( $scan->completed_at );

		return sprintf(
			/* translators: %s: human-readable time difference */
			__( '%s ago', 'yoko-link-checker' ),
			human_time_diff( $timestamp, current_time( 'timestamp' ) )
		);
	}

	/**
	 * Get scan duration.
	 *
	 * @since 1.0.0
	 * @param \YokoLinkChecker\Model\Scan|null $scan Scan model.
	 * @return string
	 */
	public function format_scan_duration( $scan ): string {
		if ( ! $scan || ! $scan->started_at || ! $scan->completed_at ) {
			return '—';
		}

		$start = strtotime( $scan->started_at );
		$end   = strtotime( $scan->completed_at );
		$diff  = $end - $start;

		if ( $diff < 60 ) {
			/* translators: %d: number of seconds */
			return sprintf( _n( '%d second', '%d seconds', $diff, 'yoko-link-checker' ), $diff );
		}

		$minutes = floor( $diff / 60 );
		$seconds = $diff % 60;

		if ( $minutes < 60 ) {
			/* translators: 1: number of minutes, 2: number of seconds */
			return sprintf( __( '%1$dm %2$ds', 'yoko-link-checker' ), $minutes, $seconds );
		}

		$hours   = floor( $minutes / 60 );
		$minutes = $minutes % 60;

		/* translators: 1: number of hours, 2: number of minutes */
		return sprintf( __( '%1$dh %2$dm', 'yoko-link-checker' ), $hours, $minutes );
	}
}
