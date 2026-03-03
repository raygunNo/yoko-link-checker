<?php
/**
 * Scan Orchestrator class.
 *
 * High-level coordinator for scan operations.
 * Manages scan lifecycle, phase transitions, and scheduling.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Scanner;

use YokoLinkChecker\Repository\ScanRepository;
use YokoLinkChecker\Repository\UrlRepository;
use YokoLinkChecker\Model\Scan;

/**
 * Scan orchestrator class.
 *
 * @since 1.0.0
 */
class ScanOrchestrator {

	/**
	 * Cron hook name for scheduled scans.
	 */
	public const CRON_HOOK = 'ylc_process_scan_batch';

	/**
	 * Batch processor instance.
	 *
	 * @var BatchProcessor
	 */
	private BatchProcessor $batch_processor;

	/**
	 * Scan repository instance.
	 *
	 * @var ScanRepository
	 */
	private ScanRepository $scan_repository;

	/**
	 * URL repository instance.
	 *
	 * @var UrlRepository
	 */
	private UrlRepository $url_repository;

	/**
	 * Content discovery instance.
	 *
	 * @var ContentDiscovery
	 */
	private ContentDiscovery $content_discovery;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param BatchProcessor   $batch_processor   Batch processor instance.
	 * @param ScanRepository   $scan_repository   Scan repository instance.
	 * @param UrlRepository    $url_repository    URL repository instance.
	 * @param ContentDiscovery $content_discovery Content discovery instance.
	 */
	public function __construct(
		BatchProcessor $batch_processor,
		ScanRepository $scan_repository,
		UrlRepository $url_repository,
		ContentDiscovery $content_discovery
	) {
		$this->batch_processor   = $batch_processor;
		$this->scan_repository   = $scan_repository;
		$this->url_repository    = $url_repository;
		$this->content_discovery = $content_discovery;
	}

	/**
	 * Start a new scan.
	 *
	 * @since 1.0.0
	 * @param string $type Scan type: 'full' or 'incremental'.
	 * @return int|false Scan ID or false on failure.
	 */
	public function start_scan( string $type = 'full' ) {
		error_log( '[YLC Debug] start_scan() called with type: ' . $type );

		// Check if a scan is already running.
		$running = $this->scan_repository->get_running();
		if ( $running ) {
			error_log( '[YLC Debug] start_scan - A scan is already running: ID ' . $running->id );
			return false;
		}

		// Count total posts to scan.
		$total_posts = $this->content_discovery->count_posts();
		error_log( '[YLC Debug] start_scan - Total posts to scan: ' . $total_posts );

		// Create scan record with proper arguments.
		$scan = $this->scan_repository->create( $type );
		error_log( '[YLC Debug] start_scan - Created scan: ' . ( $scan ? 'ID ' . $scan->id : 'FAILED' ) );

		if ( ! $scan || ! $scan->id ) {
			error_log( '[YLC Debug] start_scan - Failed to create scan record' );
			return false;
		}

		// Set scan to running status with phase and totals.
		$scan->status        = Scan::STATUS_RUNNING;
		$scan->current_phase = Scan::PHASE_DISCOVERY;
		$scan->total_posts   = $total_posts;
		$scan->started_at    = current_time( 'mysql' );

		$update_result = $this->scan_repository->update( $scan );
		error_log( '[YLC Debug] start_scan - Updated scan: ' . ( $update_result ? 'success' : 'failed' ) );

		$scan_id = $scan->id;

		/**
		 * Fires when a scan starts.
		 *
		 * @since 1.0.0
		 * @param int    $scan_id Scan ID.
		 * @param string $type    Scan type.
		 */
		do_action( 'ylc_scan_started', $scan_id, $type );

		// Schedule first batch.
		$this->schedule_next_batch( $scan_id );

		return $scan_id;
	}

	/**
	 * Process the next batch of a scan.
	 *
	 * @since 1.0.0
	 * @param int $scan_id Scan ID.
	 * @return void
	 */
	public function process_batch( int $scan_id ): void {
		$scan = $this->scan_repository->find( $scan_id );

		error_log( '[YLC Debug] process_batch - Scan ID: ' . $scan_id );
		error_log( '[YLC Debug] process_batch - Scan found: ' . ( $scan ? 'yes' : 'no' ) );

		if ( ! $scan ) {
			error_log( '[YLC Debug] process_batch - No scan found, returning' );
			return;
		}

		error_log( '[YLC Debug] process_batch - Scan status: ' . $scan->status . ', phase: ' . $scan->current_phase );

		if ( Scan::STATUS_RUNNING !== $scan->status ) {
			error_log( '[YLC Debug] process_batch - Scan not running, returning' );
			return;
		}

		$start_time = microtime( true );
		$state      = null;

		if ( Scan::PHASE_DISCOVERY === $scan->current_phase ) {
			error_log( '[YLC Debug] Calling process_discovery_phase' );
			$state = $this->process_discovery_phase( $scan );
			error_log( '[YLC Debug] Discovery phase result: ' . ( $state ? wp_json_encode( [ 'total' => $state->total, 'processed' => $state->processed, 'complete' => $state->complete ] ) : 'null' ) );
		} elseif ( Scan::PHASE_CHECKING === $scan->current_phase ) {
			error_log( '[YLC Debug] Calling process_checking_phase' );
			$state = $this->process_checking_phase( $scan );
		}

		if ( ! $state ) {
			error_log( '[YLC Debug] No state returned from phase processing' );
			return;
		}

		$batch_time = microtime( true ) - $start_time;

		// Log progress.
		$this->log_progress( $scan_id, $state, $batch_time );

		// Handle phase transitions and scheduling.
		$this->handle_phase_completion( $scan_id, $state );
	}

	/**
	 * Process discovery phase batch.
	 *
	 * @since 1.0.0
	 * @param Scan $scan Scan model.
	 * @return ScanState|null
	 */
	private function process_discovery_phase( Scan $scan ): ?ScanState {
		/**
		 * Filters the discovery batch size.
		 *
		 * @since 1.0.0
		 * @param int $batch_size Default batch size.
		 */
		$batch_size = apply_filters( 'ylc_discovery_batch_size', 50 );

		// Get cursor from scan metadata.
		$cursor = $this->get_scan_cursor( $scan->id, 'discovery' );

		$state = $this->batch_processor->process_discovery_batch(
			$scan->id,
			$cursor,
			$batch_size
		);

		// Save cursor.
		$this->set_scan_cursor( $scan->id, 'discovery', $state->cursor );

		return $state;
	}

	/**
	 * Process checking phase batch.
	 *
	 * @since 1.0.0
	 * @param Scan $scan Scan model.
	 * @return ScanState|null
	 */
	private function process_checking_phase( Scan $scan ): ?ScanState {
		/**
		 * Filters the checking batch size.
		 * Smaller batches = more reliable but slower overall.
		 * Larger batches = faster but may timeout.
		 *
		 * @since 1.0.0
		 * @param int $batch_size Default batch size.
		 */
		$batch_size = apply_filters( 'ylc_checking_batch_size', 5 );

		// Get cursor from scan metadata.
		$cursor = $this->get_scan_cursor( $scan->id, 'checking' );

		$state = $this->batch_processor->process_checking_batch(
			$scan->id,
			$cursor,
			$batch_size
		);

		// Save cursor.
		$this->set_scan_cursor( $scan->id, 'checking', $state->cursor );

		return $state;
	}

	/**
	 * Handle phase completion and transitions.
	 *
	 * @since 1.0.0
	 * @param int       $scan_id Scan ID.
	 * @param ScanState $state   Current state.
	 * @return void
	 */
	private function handle_phase_completion( int $scan_id, ScanState $state ): void {
		if ( ! $state->complete ) {
			// More work to do, schedule next batch.
			$this->schedule_next_batch( $scan_id );
			return;
		}

		$scan = $this->scan_repository->find( $scan_id );
		if ( ! $scan ) {
			return;
		}

		if ( 'discovery' === $state->phase ) {
			// Transition to checking phase.
			$this->transition_to_checking( $scan_id );
		} elseif ( 'checking' === $state->phase ) {
			// Scan complete.
			$this->complete_scan( $scan_id );
		}
	}

	/**
	 * Transition scan to checking phase.
	 *
	 * @since 1.0.0
	 * @param int $scan_id Scan ID.
	 * @return void
	 */
	private function transition_to_checking( int $scan_id ): void {
		// Count total URLs to check.
		$total_urls = $this->url_repository->count_all();

		$this->scan_repository->update_phase( $scan_id, Scan::PHASE_CHECKING );

		// Update total URLs in scan record.
		$scan = $this->scan_repository->find( $scan_id );
		if ( $scan ) {
			$scan->total_urls = $total_urls;
			$this->scan_repository->update( $scan );
		}

		/**
		 * Fires when scan transitions to checking phase.
		 *
		 * @since 1.0.0
		 * @param int $scan_id   Scan ID.
		 * @param int $total_urls Total URLs to check.
		 */
		do_action( 'ylc_scan_phase_checking', $scan_id, $total_urls );

		// Schedule next batch.
		$this->schedule_next_batch( $scan_id );
	}

	/**
	 * Complete a scan.
	 *
	 * @since 1.0.0
	 * @param int $scan_id Scan ID.
	 * @return void
	 */
	private function complete_scan( int $scan_id ): void {
		$scan = $this->scan_repository->find( $scan_id );
		if ( ! $scan ) {
			return;
		}
		$this->scan_repository->complete( $scan );

		// Clean up cursors.
		delete_option( "ylc_scan_{$scan_id}_cursor_discovery" );
		delete_option( "ylc_scan_{$scan_id}_cursor_checking" );

		/**
		 * Fires when a scan completes.
		 *
		 * @since 1.0.0
		 * @param int $scan_id Scan ID.
		 */
		do_action( 'ylc_scan_completed', $scan_id );

		// Schedule next automatic scan if enabled.
		$this->maybe_schedule_next_scan();
	}

	/**
	 * Pause a running scan.
	 *
	 * @since 1.0.0
	 * @param int $scan_id Scan ID.
	 * @return bool Whether pause was successful.
	 */
	public function pause_scan( int $scan_id ): bool {
		$scan = $this->scan_repository->find( $scan_id );

		if ( ! $scan || Scan::STATUS_RUNNING !== $scan->status ) {
			return false;
		}

		$this->scan_repository->pause( $scan );

		// Clear scheduled batch.
		wp_clear_scheduled_hook( self::CRON_HOOK, [ $scan_id ] );

		/**
		 * Fires when a scan is paused.
		 *
		 * @since 1.0.0
		 * @param int $scan_id Scan ID.
		 */
		do_action( 'ylc_scan_paused', $scan_id );

		return true;
	}

	/**
	 * Resume a paused scan.
	 *
	 * @since 1.0.0
	 * @param int $scan_id Scan ID.
	 * @return bool Whether resume was successful.
	 */
	public function resume_scan( int $scan_id ): bool {
		$scan = $this->scan_repository->find( $scan_id );

		if ( ! $scan || Scan::STATUS_PAUSED !== $scan->status ) {
			return false;
		}

		$this->scan_repository->resume( $scan );
		$this->schedule_next_batch( $scan_id );

		/**
		 * Fires when a scan is resumed.
		 *
		 * @since 1.0.0
		 * @param int $scan_id Scan ID.
		 */
		do_action( 'ylc_scan_resumed', $scan_id );

		return true;
	}

	/**
	 * Cancel a scan.
	 *
	 * @since 1.0.0
	 * @param int $scan_id Scan ID.
	 * @return bool Whether cancel was successful.
	 */
	public function cancel_scan( int $scan_id ): bool {
		$scan = $this->scan_repository->find( $scan_id );

		if ( ! $scan ) {
			return false;
		}

		if ( Scan::STATUS_COMPLETED === $scan->status ) {
			return false;
		}

		$this->scan_repository->cancel( $scan_id );

		// Clear scheduled batch.
		wp_clear_scheduled_hook( self::CRON_HOOK, [ $scan_id ] );

		// Clean up cursors.
		delete_option( "ylc_scan_{$scan_id}_cursor_discovery" );
		delete_option( "ylc_scan_{$scan_id}_cursor_checking" );

		/**
		 * Fires when a scan is cancelled.
		 *
		 * @since 1.0.0
		 * @param int $scan_id Scan ID.
		 */
		do_action( 'ylc_scan_cancelled', $scan_id );

		return true;
	}

	/**
	 * Schedule the next batch for processing.
	 *
	 * @since 1.0.0
	 * @param int $scan_id Scan ID.
	 * @return void
	 */
	private function schedule_next_batch( int $scan_id ): void {
		/**
		 * Filters the delay between batches in seconds.
		 *
		 * @since 1.0.0
		 * @param int $delay Default delay.
		 */
		$delay = apply_filters( 'ylc_batch_delay', 1 );

		if ( ! wp_next_scheduled( self::CRON_HOOK, [ $scan_id ] ) ) {
			wp_schedule_single_event(
				time() + $delay,
				self::CRON_HOOK,
				[ $scan_id ]
			);
		}
	}

	/**
	 * Maybe schedule the next automatic scan.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function maybe_schedule_next_scan(): void {
		$auto_scan = get_option( 'ylc_auto_scan_enabled', false );

		if ( ! $auto_scan ) {
			return;
		}

		$frequency = get_option( 'ylc_auto_scan_frequency', 'weekly' );
		$hook      = 'ylc_auto_scan';

		// Clear existing.
		wp_clear_scheduled_hook( $hook );

		// Schedule next.
		wp_schedule_event( time() + $this->get_frequency_seconds( $frequency ), $frequency, $hook );
	}

	/**
	 * Get seconds for a frequency.
	 *
	 * @since 1.0.0
	 * @param string $frequency Frequency name.
	 * @return int Seconds.
	 */
	private function get_frequency_seconds( string $frequency ): int {
		$schedules = [
			'hourly'     => HOUR_IN_SECONDS,
			'twicedaily' => 12 * HOUR_IN_SECONDS,
			'daily'      => DAY_IN_SECONDS,
			'weekly'     => WEEK_IN_SECONDS,
		];

		return $schedules[ $frequency ] ?? WEEK_IN_SECONDS;
	}

	/**
	 * Get cursor for a scan phase.
	 *
	 * @since 1.0.0
	 * @param int    $scan_id Scan ID.
	 * @param string $phase   Phase name.
	 * @return int Cursor value.
	 */
	private function get_scan_cursor( int $scan_id, string $phase ): int {
		return (int) get_option( "ylc_scan_{$scan_id}_cursor_{$phase}", 0 );
	}

	/**
	 * Set cursor for a scan phase.
	 *
	 * @since 1.0.0
	 * @param int    $scan_id Scan ID.
	 * @param string $phase   Phase name.
	 * @param int    $cursor  Cursor value.
	 * @return void
	 */
	private function set_scan_cursor( int $scan_id, string $phase, int $cursor ): void {
		update_option( "ylc_scan_{$scan_id}_cursor_{$phase}", $cursor, false );
	}

	/**
	 * Log progress.
	 *
	 * @since 1.0.0
	 * @param int       $scan_id    Scan ID.
	 * @param ScanState $state      Current state.
	 * @param float     $batch_time Batch processing time.
	 * @return void
	 */
	private function log_progress( int $scan_id, ScanState $state, float $batch_time ): void {
		if ( ! defined( 'YLC_DEBUG' ) || ! YLC_DEBUG ) {
			return;
		}

		$message = sprintf(
			'Scan %d: %s phase - %d/%d (%.1f%%) - batch: %d items in %.2fs',
			$scan_id,
			$state->phase,
			$state->processed,
			$state->total,
			$state->get_progress(),
			$state->last_batch_count,
			$batch_time
		);

		error_log( '[YLC] ' . $message );
	}

	/**
	 * Get current scan status summary.
	 *
	 * @since 1.0.0
	 * @return array|null Status array or null if no scan.
	 */
	public function get_status(): ?array {
		$scan = $this->scan_repository->get_running();

		if ( ! $scan ) {
			$scan = $this->scan_repository->get_last_completed();
		}

		if ( ! $scan ) {
			return null;
		}

		$progress = 0.0;
		$total    = 0;
		$done     = 0;

		if ( Scan::PHASE_DISCOVERY === $scan->current_phase ) {
			$total    = $scan->total_posts;
			$done     = $scan->processed_posts;
			$progress = $total > 0 ? min( ( $done / $total ) * 50.0, 50.0 ) : 0.0;
		} elseif ( Scan::PHASE_CHECKING === $scan->current_phase ) {
			// Use current pending count + checked for accurate total.
			$pending  = $this->url_repository->count_by_status( \Jeremie\YokoLinkChecker\Model\Url::STATUS_PENDING );
			$done     = $scan->checked_urls;
			$total    = $pending + $done; // Dynamic total.
			$progress = 50.0 + ( $total > 0 ? min( ( $done / $total ) * 50.0, 50.0 ) : 0.0 );
		} elseif ( Scan::STATUS_COMPLETED === $scan->status ) {
			$progress = 100.0;
		}

		return [
			'scan_id'      => $scan->id,
			'status'       => $scan->status,
			'phase'        => $scan->current_phase,
			'progress'     => round( $progress, 1 ),
			'total_posts'  => $scan->total_posts,
			'posts_done'   => $scan->processed_posts,
			'total_urls'   => $scan->total_urls,
			'urls_checked' => $scan->checked_urls,
			'started_at'   => $scan->started_at,
			'completed_at' => $scan->completed_at,
		];
	}
}
