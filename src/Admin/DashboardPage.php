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

defined( 'ABSPATH' ) || exit;

use YokoLinkChecker\Repository\LinkRepository;
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
	 * @param LinkRepository   $link_repository   Link repository.
	 * @param UrlRepository    $url_repository    URL repository.
	 * @param ScanRepository   $scan_repository   Scan repository.
	 * @param ScanOrchestrator $scan_orchestrator Scan orchestrator.
	 */
	public function __construct(
		LinkRepository $link_repository,
		UrlRepository $url_repository,
		ScanRepository $scan_repository,
		ScanOrchestrator $scan_orchestrator
	) {
		$this->link_repository   = $link_repository;
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
		$status_counts    = $this->url_repository->get_status_counts();
		$stats            = $this->get_stats( $status_counts );
		$scan_status      = $this->scan_orchestrator->get_status();
		$recent_broken    = $this->link_repository->get_recent_broken();
		$status_breakdown = $this->get_status_breakdown( $status_counts );

		include YOKO_LC_PLUGIN_DIR . 'templates/admin/dashboard.php';
	}

	/**
	 * Get link statistics.
	 *
	 * Uses pre-fetched status counts from a single GROUP BY query
	 * instead of firing individual COUNT queries per status.
	 *
	 * @since 1.0.0
	 * @param array<string, int> $status_counts Status counts from UrlRepository::get_status_counts().
	 * @return array
	 */
	private function get_stats( array $status_counts ): array {
		$total = array_sum( $status_counts );

		return array(
			'total_urls'  => $total,
			'broken'      => $status_counts[ Url::STATUS_BROKEN ] ?? 0,
			'warnings'    => $status_counts[ Url::STATUS_WARNING ] ?? 0,
			'redirects'   => $status_counts[ Url::STATUS_REDIRECT ] ?? 0,
			'valid'       => $status_counts[ Url::STATUS_VALID ] ?? 0,
			'pending'     => $status_counts[ Url::STATUS_PENDING ] ?? 0,
			'blocked'     => $status_counts[ Url::STATUS_BLOCKED ] ?? 0,
			'timeouts'    => $status_counts[ Url::STATUS_TIMEOUT ] ?? 0,
			'errors'      => $status_counts[ Url::STATUS_ERROR ] ?? 0,
			'total_scans' => $this->scan_repository->count_all(),
			'last_scan'   => $this->scan_repository->get_last_completed(),
		);
	}

	/**
	 * Get status breakdown for chart.
	 *
	 * Uses pre-fetched status counts from a single GROUP BY query
	 * instead of firing individual COUNT queries per status.
	 *
	 * @since 1.0.0
	 * @param array<string, int> $status_counts Status counts from UrlRepository::get_status_counts().
	 * @return array
	 */
	private function get_status_breakdown( array $status_counts ): array {
		$statuses = array(
			Url::STATUS_VALID    => array(
				'label' => __( 'Valid', 'yoko-link-checker' ),
				'color' => '#4caf50',
			),
			Url::STATUS_BROKEN   => array(
				'label' => __( 'Broken', 'yoko-link-checker' ),
				'color' => '#f44336',
			),
			Url::STATUS_WARNING  => array(
				'label' => __( 'Warning', 'yoko-link-checker' ),
				'color' => '#ff9800',
			),
			Url::STATUS_REDIRECT => array(
				'label' => __( 'Redirect', 'yoko-link-checker' ),
				'color' => '#2196f3',
			),
			Url::STATUS_BLOCKED  => array(
				'label' => __( 'Blocked', 'yoko-link-checker' ),
				'color' => '#9c27b0',
			),
			Url::STATUS_TIMEOUT  => array(
				'label' => __( 'Timeout', 'yoko-link-checker' ),
				'color' => '#795548',
			),
			Url::STATUS_PENDING  => array(
				'label' => __( 'Pending', 'yoko-link-checker' ),
				'color' => '#9e9e9e',
			),
		);

		$breakdown = array();

		foreach ( $statuses as $status => $config ) {
			$count = $status_counts[ $status ] ?? 0;
			if ( $count > 0 ) {
				$breakdown[] = array(
					'status' => $status,
					'label'  => $config['label'],
					'count'  => $count,
					'color'  => $config['color'],
				);
			}
		}

		return $breakdown;
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

		if ( false === $timestamp ) {
			return __( 'Unknown', 'yoko-link-checker' );
		}

		return sprintf(
			/* translators: %s: human-readable time difference */
			__( '%s ago', 'yoko-link-checker' ),
			human_time_diff( $timestamp, time() )
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

		if ( false === $start || false === $end ) {
			return "\xE2\x80\x94"; // em-dash.
		}

		$diff = $end - $start;

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
