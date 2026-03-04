<?php
/**
 * Batch Processor class.
 *
 * Handles batch processing of posts for link extraction
 * and URLs for status checking.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Scanner;

use YokoLinkChecker\Extractor\ExtractorRegistry;
use YokoLinkChecker\Repository\UrlRepository;
use YokoLinkChecker\Repository\LinkRepository;
use YokoLinkChecker\Repository\ScanRepository;
use YokoLinkChecker\Checker\UrlChecker;
use YokoLinkChecker\Model\Link;
use YokoLinkChecker\Model\Scan;
use YokoLinkChecker\Model\Url;
use WP_Post;

/**
 * Batch processor for scan operations.
 *
 * @since 1.0.0
 */
class BatchProcessor {

	/**
	 * Content discovery instance.
	 *
	 * @var ContentDiscovery
	 */
	private ContentDiscovery $content_discovery;

	/**
	 * Extractor registry instance.
	 *
	 * @var ExtractorRegistry
	 */
	private ExtractorRegistry $extractor_registry;

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
	 * Scan repository instance.
	 *
	 * @var ScanRepository
	 */
	private ScanRepository $scan_repository;

	/**
	 * URL checker instance.
	 *
	 * @var UrlChecker
	 */
	private UrlChecker $url_checker;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param ContentDiscovery  $content_discovery  Content discovery instance.
	 * @param ExtractorRegistry $extractor_registry Extractor registry instance.
	 * @param UrlRepository     $url_repository     URL repository instance.
	 * @param LinkRepository    $link_repository    Link repository instance.
	 * @param ScanRepository    $scan_repository    Scan repository instance.
	 * @param UrlChecker        $url_checker        URL checker instance.
	 */
	public function __construct(
		ContentDiscovery $content_discovery,
		ExtractorRegistry $extractor_registry,
		UrlRepository $url_repository,
		LinkRepository $link_repository,
		ScanRepository $scan_repository,
		UrlChecker $url_checker
	) {
		$this->content_discovery  = $content_discovery;
		$this->extractor_registry = $extractor_registry;
		$this->url_repository     = $url_repository;
		$this->link_repository    = $link_repository;
		$this->scan_repository    = $scan_repository;
		$this->url_checker        = $url_checker;
	}

	/**
	 * Process a batch of posts for link discovery.
	 *
	 * @since 1.0.0
	 * @param int $scan_id    Scan ID.
	 * @param int $after_id   Process posts after this ID.
	 * @param int $batch_size Number of posts to process.
	 * @return ScanState Current state after processing.
	 */
	public function process_discovery_batch( int $scan_id, int $after_id = 0, int $batch_size = 50 ): ScanState {
		if ( defined( 'YOKO_LC_DEBUG' ) && YOKO_LC_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( "[YLC Debug] process_discovery_batch - scan_id: $scan_id, after_id: $after_id, batch_size: $batch_size" );
		}

		$posts       = $this->content_discovery->get_batch( $after_id, $batch_size );
		$total_posts = $this->content_discovery->count_posts();
		$last_id     = $after_id;

		if ( defined( 'YOKO_LC_DEBUG' ) && YOKO_LC_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[YLC Debug] process_discovery_batch - Got ' . count( $posts ) . " posts, total_posts: $total_posts" );
		}

		foreach ( $posts as $post ) {
			$this->process_post( $scan_id, $post );
			$last_id = $post->ID;
		}

		$processed_count = count( $posts );
		$complete        = $processed_count < $batch_size;

		if ( defined( 'YOKO_LC_DEBUG' ) && YOKO_LC_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( "[YLC Debug] process_discovery_batch - processed_count: $processed_count, complete: " . ( $complete ? 'yes' : 'no' ) );
		}

		// Update scan state.
		$scan = $this->scan_repository->find( $scan_id );
		if ( $scan ) {
			$new_processed = $scan->processed_posts + $processed_count;
			if ( defined( 'YOKO_LC_DEBUG' ) && YOKO_LC_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( "[YLC Debug] process_discovery_batch - Updating progress: new_processed=$new_processed" );
			}
			$this->scan_repository->update_progress(
				$scan,
				Scan::PHASE_DISCOVERY,
				$new_processed,
				$last_id
			);

			return ScanState::discovery(
				$total_posts,
				$new_processed,
				$last_id,
				$complete,
				$processed_count
			);
		}

		return ScanState::discovery( $total_posts, 0, $last_id, $complete, $processed_count );
	}

	/**
	 * Process a single post for link extraction.
	 *
	 * @since 1.0.0
	 * @param int     $scan_id Scan ID.
	 * @param WP_Post $post    Post object.
	 * @return int Number of links extracted.
	 */
	private function process_post( int $scan_id, WP_Post $post ): int {
		/**
		 * Filters whether to skip scanning a post.
		 *
		 * @since 1.0.0
		 * @param bool    $skip Whether to skip.
		 * @param WP_Post $post Post object.
		 */
		if ( apply_filters( 'yoko_lc_skip_post', false, $post ) ) {
			return 0;
		}

		$extracted_links = $this->extractor_registry->extract_from_post( $post );
		$link_count      = 0;

		foreach ( $extracted_links as $extracted ) {
			// Find or create the URL record.
			$url = $this->url_repository->find_or_create_from_raw( $extracted->url );

			if ( ! $url || ! $url->id ) {
				continue;
			}

			// Check if link already exists for this source.
			$existing = $this->link_repository->find_existing(
				$url->id,
				$post->ID,
				$post->post_type,
				$extracted->field
			);

			if ( $existing ) {
				// Update existing link.
				$existing->anchor_text  = $extracted->text;
				$existing->link_context = $extracted->context;
				$existing->updated_at   = current_time( 'mysql' );
				$this->link_repository->update( $existing );
				continue;
			}

			// Create new link occurrence.
			$link                = new Link();
			$link->url_id        = $url->id;
			$link->source_id     = $post->ID;
			$link->source_type   = $post->post_type;
			$link->source_field  = $extracted->field;
			$link->anchor_text   = $extracted->text;
			$link->link_context  = $extracted->context;
			$link->link_position = $extracted->position;
			$link->created_at    = current_time( 'mysql' );
			$link->updated_at    = current_time( 'mysql' );

			$inserted = $this->link_repository->insert( $link );

			if ( $inserted && $inserted->id ) {
				++$link_count;
			}
		}

		/**
		 * Fires after a post has been processed for links.
		 *
		 * @since 1.0.0
		 * @param WP_Post $post       Post object.
		 * @param int     $link_count Number of links found.
		 * @param int     $scan_id    Scan ID.
		 */
		do_action( 'yoko_lc_post_processed', $post, $link_count, $scan_id );

		return $link_count;
	}

	/**
	 * Process a batch of URLs for status checking.
	 *
	 * @since 1.0.0
	 * @param int $scan_id    Scan ID.
	 * @param int $after_id   Process URLs after this ID.
	 * @param int $batch_size Number of URLs to check.
	 * @return ScanState Current state after processing.
	 */
	public function process_checking_batch( int $scan_id, int $after_id = 0, int $batch_size = 10 ): ScanState {
		$urls       = $this->url_repository->get_pending( $batch_size, $after_id );
		$total_urls = $this->url_repository->count_by_status( Url::STATUS_PENDING );
		$last_id    = $after_id;

		$actual_checked = 0;
		foreach ( $urls as $url ) {
			try {
				$this->check_url( $url );
				++$actual_checked;
			} catch ( \Throwable $e ) {
				// Log error but continue with other URLs.
				if ( defined( 'YOKO_LC_DEBUG' ) && YOKO_LC_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( '[YLC] URL check failed for ID ' . $url->id . ': ' . $e->getMessage() );
				}
				// Mark as error so we don't retry immediately.
				$url->status        = Url::STATUS_ERROR;
				$url->error_message = substr( $e->getMessage(), 0, 255 );
				$url->last_checked  = current_time( 'mysql' );
				$url->check_count   = ( $url->check_count ?? 0 ) + 1;
				try {
					$this->url_repository->update( $url );
					++$actual_checked;
				} catch ( \Throwable $update_error ) {
					if ( defined( 'YOKO_LC_DEBUG' ) && YOKO_LC_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						error_log( '[YLC] Failed to update URL ' . $url->id . ': ' . $update_error->getMessage() );
					}
				}
			}
			$last_id = $url->id;
		}

		$checked_count = $actual_checked;
		$complete      = $checked_count < $batch_size;

		// Update scan state.
		$scan = $this->scan_repository->find( $scan_id );
		if ( $scan ) {
			$new_checked = $scan->checked_urls + $checked_count;
			$this->scan_repository->update_progress(
				$scan,
				Scan::PHASE_CHECKING,
				$new_checked,
				$last_id
			);

			// Get total pending at scan start if available.
			$total_to_check = $total_urls + $new_checked;

			return ScanState::checking(
				$total_to_check,
				$new_checked,
				$last_id,
				$complete,
				$checked_count
			);
		}

		return ScanState::checking( $total_urls, 0, $last_id, $complete, $checked_count );
	}

	/**
	 * Check a single URL and update its status.
	 *
	 * @since 1.0.0
	 * @param Url $url URL model.
	 * @return void
	 */
	private function check_url( Url $url ): void {
		/**
		 * Filters whether to skip checking a URL.
		 *
		 * @since 1.0.0
		 * @param bool $skip Whether to skip.
		 * @param Url  $url  URL model.
		 */
		if ( apply_filters( 'yoko_lc_skip_url_check', false, $url ) ) {
			return;
		}

		$result = $this->url_checker->check( $url->url );

		// Update the URL model with the results.
		$url->status        = $result->status;
		$url->http_code     = $result->http_code;
		$url->final_url     = $result->redirect_url;
		$url->error_message = $result->error_message;
		$url->response_time = $result->response_time;
		$url->last_checked  = current_time( 'mysql' );
		$url->check_count   = ( $url->check_count ?? 0 ) + 1;

		$this->url_repository->update( $url );

		/**
		 * Fires after a URL has been checked.
		 *
		 * @since 1.0.0
		 * @param Url                              $url    URL model.
		 * @param \YokoLinkChecker\Checker\CheckResult $result Check result.
		 */
		do_action( 'yoko_lc_url_checked', $url, $result );
	}

	/**
	 * Recheck a specific URL.
	 *
	 * @since 1.0.0
	 * @param int $url_id URL ID.
	 * @return bool Whether recheck was successful.
	 */
	public function recheck_url( int $url_id ): bool {
		$url = $this->url_repository->find( $url_id );

		if ( ! $url ) {
			return false;
		}

		$this->check_url( $url );

		return true;
	}

	/**
	 * Get time estimate for remaining work.
	 *
	 * @since 1.0.0
	 * @param ScanState $state      Current state.
	 * @param float     $batch_time Time taken for last batch in seconds.
	 * @return int Estimated seconds remaining.
	 */
	public function estimate_remaining_time( ScanState $state, float $batch_time ): int {
		if ( $state->complete || 0 === $state->last_batch_count ) {
			return 0;
		}

		$remaining_items   = $state->total - $state->processed;
		$time_per_item     = $batch_time / $state->last_batch_count;
		$estimated_seconds = (int) ceil( $remaining_items * $time_per_item );

		return $estimated_seconds;
	}
}
