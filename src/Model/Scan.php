<?php
/**
 * Scan entity model.
 *
 * Represents a scan run and its state.
 * Maps to the yoko_lc_scans database table.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Scan run entity.
 *
 * @since 1.0.0
 */
final class Scan {

	/**
	 * Scan status constants.
	 */
	public const STATUS_PENDING   = 'pending';
	public const STATUS_RUNNING   = 'running';
	public const STATUS_PAUSED    = 'paused';
	public const STATUS_COMPLETED = 'completed';
	public const STATUS_FAILED    = 'failed';
	public const STATUS_CANCELLED = 'cancelled';

	/**
	 * Scan type constants.
	 */
	public const TYPE_FULL = 'full';

	/**
	 * Scan phase constants.
	 */
	public const PHASE_DISCOVERY = 'discovery';
	public const PHASE_CHECKING  = 'checking';

	/**
	 * Scan ID.
	 *
	 * @var int|null
	 */
	public ?int $id = null;

	/**
	 * Scan status.
	 *
	 * @var string
	 */
	public string $status = self::STATUS_PENDING;

	/**
	 * Scan type.
	 *
	 * @var string
	 */
	public string $scan_type = self::TYPE_FULL;

	/**
	 * When scan started.
	 *
	 * @var string|null
	 */
	public ?string $started_at = null;

	/**
	 * When scan completed.
	 *
	 * @var string|null
	 */
	public ?string $completed_at = null;

	/**
	 * Total posts to scan.
	 *
	 * @var int
	 */
	public int $total_posts = 0;

	/**
	 * Posts processed so far.
	 *
	 * @var int
	 */
	public int $processed_posts = 0;

	/**
	 * Total URLs found.
	 *
	 * @var int
	 */
	public int $total_urls = 0;

	/**
	 * URLs checked so far.
	 *
	 * @var int
	 */
	public int $checked_urls = 0;

	/**
	 * Last processed post ID (for resumption).
	 *
	 * @var int
	 */
	public int $last_post_id = 0;

	/**
	 * Last processed URL ID (for resumption).
	 *
	 * @var int
	 */
	public int $last_url_id = 0;

	/**
	 * Current scan phase.
	 *
	 * @var string
	 */
	public string $current_phase = self::PHASE_DISCOVERY;

	/**
	 * Error message if failed.
	 *
	 * @var string|null
	 */
	public ?string $error_message = null;

	/**
	 * Scan options (JSON stored).
	 *
	 * @var array<string, mixed>
	 */
	public array $options = array();

	/**
	 * Create from database row.
	 *
	 * @since 1.0.0
	 * @param object $row Database row object.
	 * @return self
	 */
	public static function from_row( object $row ): self {
		$scan = new self();

		$scan->id              = isset( $row->id ) ? (int) $row->id : null;
		$scan->status          = $row->status ?? self::STATUS_PENDING;
		$scan->scan_type       = $row->scan_type ?? self::TYPE_FULL;
		$scan->started_at      = $row->started_at ?? null;
		$scan->completed_at    = $row->completed_at ?? null;
		$scan->total_posts     = (int) ( $row->total_posts ?? 0 );
		$scan->processed_posts = (int) ( $row->processed_posts ?? 0 );
		$scan->total_urls      = (int) ( $row->total_urls ?? 0 );
		$scan->checked_urls    = (int) ( $row->checked_urls ?? 0 );
		$scan->last_post_id    = (int) ( $row->last_post_id ?? 0 );
		$scan->last_url_id     = (int) ( $row->last_url_id ?? 0 );
		$scan->current_phase   = $row->current_phase ?? self::PHASE_DISCOVERY;
		$scan->error_message   = $row->error_message ?? null;

		// Decode JSON options.
		if ( ! empty( $row->options ) ) {
			$decoded = json_decode( $row->options, true );
			if ( is_array( $decoded ) ) {
				$scan->options = $decoded;
			}
		}

		return $scan;
	}

	/**
	 * Convert to database row array.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function to_row(): array {
		return array(
			'status'          => $this->status,
			'scan_type'       => $this->scan_type,
			'started_at'      => $this->started_at,
			'completed_at'    => $this->completed_at,
			'total_posts'     => $this->total_posts,
			'processed_posts' => $this->processed_posts,
			'total_urls'      => $this->total_urls,
			'checked_urls'    => $this->checked_urls,
			'last_post_id'    => $this->last_post_id,
			'last_url_id'     => $this->last_url_id,
			'current_phase'   => $this->current_phase,
			'error_message'   => $this->error_message,
			'options'         => wp_json_encode( $this->options ),
		);
	}
}
