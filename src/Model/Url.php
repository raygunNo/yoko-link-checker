<?php
/**
 * URL entity model.
 *
 * Represents a unique URL and its check status.
 * Maps to the yoko_lc_urls database table.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Model;

/**
 * URL entity.
 *
 * @since 1.0.0
 */
final class Url {

	/**
	 * URL status constants.
	 */
	public const STATUS_PENDING  = 'pending';
	public const STATUS_VALID    = 'valid';
	public const STATUS_REDIRECT = 'redirect';
	public const STATUS_BROKEN   = 'broken';
	public const STATUS_WARNING  = 'warning';
	public const STATUS_BLOCKED  = 'blocked';
	public const STATUS_TIMEOUT  = 'timeout';
	public const STATUS_ERROR    = 'error';

	/**
	 * All possible statuses.
	 *
	 * @var array<string>
	 */
	public const STATUSES = array(
		self::STATUS_PENDING,
		self::STATUS_VALID,
		self::STATUS_REDIRECT,
		self::STATUS_BROKEN,
		self::STATUS_WARNING,
		self::STATUS_BLOCKED,
		self::STATUS_TIMEOUT,
		self::STATUS_ERROR,
	);

	/**
	 * Problem statuses (not valid).
	 *
	 * @var array<string>
	 */
	public const PROBLEM_STATUSES = array(
		self::STATUS_BROKEN,
		self::STATUS_WARNING,
		self::STATUS_BLOCKED,
		self::STATUS_TIMEOUT,
		self::STATUS_ERROR,
	);

	/**
	 * URL ID.
	 *
	 * @var int|null
	 */
	public ?int $id = null;

	/**
	 * SHA-256 hash of normalized URL.
	 *
	 * @var string
	 */
	public string $url_hash = '';

	/**
	 * Original URL as found in content.
	 *
	 * @var string
	 */
	public string $url = '';

	/**
	 * Normalized URL for checking.
	 *
	 * @var string
	 */
	public string $url_normalized = '';

	/**
	 * Whether URL is internal to the site.
	 *
	 * @var bool
	 */
	public bool $is_internal = false;

	/**
	 * Check status.
	 *
	 * @var string
	 */
	public string $status = self::STATUS_PENDING;

	/**
	 * HTTP status code from last check.
	 *
	 * @var int|null
	 */
	public ?int $http_code = null;

	/**
	 * Final URL after following redirects.
	 *
	 * @var string|null
	 */
	public ?string $final_url = null;

	/**
	 * Number of redirects followed.
	 *
	 * @var int
	 */
	public int $redirect_count = 0;

	/**
	 * Response time in milliseconds.
	 *
	 * @var int|null
	 */
	public ?int $response_time = null;

	/**
	 * Error type identifier.
	 *
	 * @var string|null
	 */
	public ?string $error_type = null;

	/**
	 * Error message.
	 *
	 * @var string|null
	 */
	public ?string $error_message = null;

	/**
	 * Number of times URL has been checked.
	 *
	 * @var int
	 */
	public int $check_count = 0;

	/**
	 * When URL was first seen.
	 *
	 * @var string
	 */
	public string $first_seen = '';

	/**
	 * When URL was last checked.
	 *
	 * @var string|null
	 */
	public ?string $last_checked = null;

	/**
	 * When URL should next be checked.
	 *
	 * @var string|null
	 */
	public ?string $next_check = null;

	/**
	 * Whether URL is ignored.
	 *
	 * @var bool
	 */
	public bool $is_ignored = false;

	/**
	 * Reason for ignoring.
	 *
	 * @var string|null
	 */
	public ?string $ignore_reason = null;

	/**
	 * Create from database row.
	 *
	 * @since 1.0.0
	 * @param object $row Database row object.
	 * @return self
	 */
	public static function from_row( object $row ): self {
		$url = new self();

		// Support both direct queries (id) and joined queries (url_db_id or url_id).
		if ( isset( $row->url_db_id ) ) {
			$url->id = (int) $row->url_db_id;
		} elseif ( isset( $row->url_id ) && is_numeric( $row->url_id ) ) {
			$url->id = (int) $row->url_id;
		} else {
			$url->id = isset( $row->id ) ? (int) $row->id : null;
		}

		$url->url_hash       = $row->url_hash ?? '';
		$url->url            = $row->url ?? '';
		$url->url_normalized = $row->url_normalized ?? '';
		$url->is_internal    = (bool) ( $row->is_internal ?? false );
		// Support aliased status from joined queries.
		$url->status         = $row->url_status ?? $row->status ?? self::STATUS_PENDING;
		$url->http_code      = isset( $row->http_code ) ? (int) $row->http_code : null;
		$url->final_url      = $row->final_url ?? null;
		$url->redirect_count = (int) ( $row->redirect_count ?? 0 );
		$url->response_time  = isset( $row->response_time ) ? (int) $row->response_time : null;
		$url->error_type     = $row->error_type ?? null;
		// Support aliased error_message from joined queries.
		$url->error_message = $row->url_error ?? $row->error_message ?? null;
		$url->check_count   = (int) ( $row->check_count ?? 0 );
		$url->first_seen    = $row->first_seen ?? '';
		$url->last_checked  = $row->last_checked ?? null;
		$url->next_check    = $row->next_check ?? null;
		$url->is_ignored    = (bool) ( $row->is_ignored ?? false );
		$url->ignore_reason = $row->ignore_reason ?? null;

		return $url;
	}

	/**
	 * Convert to database row array.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function to_row(): array {
		return array(
			'url_hash'       => $this->url_hash,
			'url'            => $this->url,
			'url_normalized' => $this->url_normalized,
			'is_internal'    => $this->is_internal ? 1 : 0,
			'status'         => $this->status,
			'http_code'      => $this->http_code,
			'final_url'      => $this->final_url,
			'redirect_count' => $this->redirect_count,
			'response_time'  => $this->response_time,
			'error_type'     => $this->error_type,
			'error_message'  => $this->error_message,
			'check_count'    => $this->check_count,
			'first_seen'     => $this->first_seen,
			'last_checked'   => $this->last_checked,
			'next_check'     => $this->next_check,
			'is_ignored'     => $this->is_ignored ? 1 : 0,
			'ignore_reason'  => $this->ignore_reason,
		);
	}

	/**
	 * Check if URL has a problem status.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function has_problem(): bool {
		return in_array( $this->status, self::PROBLEM_STATUSES, true );
	}

	/**
	 * Check if URL needs checking.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function needs_check(): bool {
		if ( $this->is_ignored ) {
			return false;
		}

		if ( self::STATUS_PENDING === $this->status ) {
			return true;
		}

		if ( null === $this->next_check ) {
			return false;
		}

		return strtotime( $this->next_check ) <= time();
	}

	/**
	 * Get human-readable status label.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_status_label(): string {
		$labels = array(
			self::STATUS_PENDING  => __( 'Pending', 'yoko-link-checker' ),
			self::STATUS_VALID    => __( 'Valid', 'yoko-link-checker' ),
			self::STATUS_REDIRECT => __( 'Redirect', 'yoko-link-checker' ),
			self::STATUS_BROKEN   => __( 'Broken', 'yoko-link-checker' ),
			self::STATUS_WARNING  => __( 'Warning', 'yoko-link-checker' ),
			self::STATUS_BLOCKED  => __( 'Blocked', 'yoko-link-checker' ),
			self::STATUS_TIMEOUT  => __( 'Timeout', 'yoko-link-checker' ),
			self::STATUS_ERROR    => __( 'Error', 'yoko-link-checker' ),
		);

		return $labels[ $this->status ] ?? $this->status;
	}
}
