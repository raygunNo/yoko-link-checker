<?php
/**
 * Scan State value object.
 *
 * Represents the current state of a scan for progress tracking.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Scanner;

/**
 * Scan state value object.
 *
 * @since 1.0.0
 */
final class ScanState {

	/**
	 * Current phase.
	 *
	 * @var string
	 */
	public string $phase;

	/**
	 * Total items in current phase.
	 *
	 * @var int
	 */
	public int $total;

	/**
	 * Processed items in current phase.
	 *
	 * @var int
	 */
	public int $processed;

	/**
	 * Last cursor ID (for resumption).
	 *
	 * @var int
	 */
	public int $cursor;

	/**
	 * Whether phase is complete.
	 *
	 * @var bool
	 */
	public bool $complete;

	/**
	 * Items processed in last batch.
	 *
	 * @var int
	 */
	public int $last_batch_count;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param string $phase            Current phase.
	 * @param int    $total            Total items.
	 * @param int    $processed        Processed items.
	 * @param int    $cursor           Last cursor ID.
	 * @param bool   $complete         Whether complete.
	 * @param int    $last_batch_count Items in last batch.
	 */
	public function __construct(
		string $phase,
		int $total,
		int $processed,
		int $cursor,
		bool $complete = false,
		int $last_batch_count = 0
	) {
		$this->phase            = $phase;
		$this->total            = $total;
		$this->processed        = $processed;
		$this->cursor           = $cursor;
		$this->complete         = $complete;
		$this->last_batch_count = $last_batch_count;
	}

	/**
	 * Get progress percentage.
	 *
	 * @since 1.0.0
	 * @return float
	 */
	public function get_progress(): float {
		if ( 0 === $this->total ) {
			return $this->complete ? 100.0 : 0.0;
		}

		return min( 100.0, ( $this->processed / $this->total ) * 100.0 );
	}

	/**
	 * Check if there are more items to process.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function has_more(): bool {
		return ! $this->complete && $this->processed < $this->total;
	}

	/**
	 * Create state for discovery phase.
	 *
	 * @since 1.0.0
	 * @param int  $total     Total posts.
	 * @param int  $processed Processed posts.
	 * @param int  $cursor    Last post ID.
	 * @param bool $complete  Whether complete.
	 * @param int  $batch     Last batch count.
	 * @return self
	 */
	public static function discovery(
		int $total,
		int $processed,
		int $cursor,
		bool $complete = false,
		int $batch = 0
	): self {
		return new self( 'discovery', $total, $processed, $cursor, $complete, $batch );
	}

	/**
	 * Create state for checking phase.
	 *
	 * @since 1.0.0
	 * @param int  $total     Total URLs.
	 * @param int  $processed Checked URLs.
	 * @param int  $cursor    Last URL ID.
	 * @param bool $complete  Whether complete.
	 * @param int  $batch     Last batch count.
	 * @return self
	 */
	public static function checking(
		int $total,
		int $processed,
		int $cursor,
		bool $complete = false,
		int $batch = 0
	): self {
		return new self( 'checking', $total, $processed, $cursor, $complete, $batch );
	}
}
