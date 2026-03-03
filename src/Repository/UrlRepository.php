<?php
/**
 * URL Repository.
 *
 * Handles CRUD operations for the ylc_urls table.
 * Manages unique URLs with deduplication via hash.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Repository;

use YokoLinkChecker\Model\Url;
use YokoLinkChecker\Util\UrlNormalizer;

/**
 * URL repository for database operations.
 *
 * @since 1.0.0
 */
final class UrlRepository {

	/**
	 * URL normalizer instance.
	 *
	 * @var UrlNormalizer
	 */
	private UrlNormalizer $normalizer;

	/**
	 * Database table name.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param UrlNormalizer $normalizer URL normalizer instance.
	 */
	public function __construct( UrlNormalizer $normalizer ) {
		global $wpdb;

		$this->normalizer = $normalizer;
		$this->table      = $wpdb->prefix . 'ylc_urls';
	}

	/**
	 * Find URL by ID.
	 *
	 * @since 1.0.0
	 * @param int $id URL ID.
	 * @return Url|null
	 */
	public function find( int $id ): ?Url {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$id
			)
		);

		return $row ? Url::from_row( $row ) : null;
	}

	/**
	 * Find URL by normalized URL hash.
	 *
	 * @since 1.0.0
	 * @param string $url_hash SHA-256 hash of normalized URL.
	 * @return Url|null
	 */
	public function find_by_hash( string $url_hash ): ?Url {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE url_hash = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$url_hash
			)
		);

		return $row ? Url::from_row( $row ) : null;
	}

	/**
	 * Find or create URL from raw URL string.
	 *
	 * Handles normalization and internal detection automatically.
	 *
	 * @since 1.0.0
	 * @param string $raw_url The raw URL as found in content.
	 * @return Url|null The URL entity, or null if URL is invalid.
	 */
	public function find_or_create_from_raw( string $raw_url ): ?Url {
		// Skip empty or invalid URLs.
		$raw_url = trim( $raw_url );
		if ( empty( $raw_url ) || '#' === $raw_url || 0 === strpos( $raw_url, 'javascript:' ) || 0 === strpos( $raw_url, 'mailto:' ) || 0 === strpos( $raw_url, 'tel:' ) ) {
			return null;
		}

		// Normalize the URL.
		$normalized = $this->normalizer->normalize( $raw_url );
		if ( empty( $normalized ) ) {
			return null;
		}

		// Determine if internal.
		$is_internal = $this->normalizer->is_internal( $normalized );

		return $this->find_or_create( $raw_url, $normalized, $is_internal );
	}

	/**
	 * Find or create URL by normalized URL.
	 *
	 * @since 1.0.0
	 * @param string $original_url   The original URL as found.
	 * @param string $normalized_url The normalized URL.
	 * @param bool   $is_internal    Whether URL is internal.
	 * @return Url The existing or newly created URL.
	 */
	public function find_or_create( string $original_url, string $normalized_url, bool $is_internal ): Url {
		$url_hash = $this->normalizer->hash( $normalized_url );

		// Try to find existing.
		$existing = $this->find_by_hash( $url_hash );

		if ( $existing ) {
			return $existing;
		}

		// Create new URL record.
		$url                 = new Url();
		$url->url_hash       = $url_hash;
		$url->url            = $original_url;
		$url->url_normalized = $normalized_url;
		$url->is_internal    = $is_internal;
		$url->status         = Url::STATUS_PENDING;
		$url->first_seen     = current_time( 'mysql' );

		return $this->insert( $url );
	}

	/**
	 * Insert a new URL.
	 *
	 * @since 1.0.0
	 * @param Url $url URL entity.
	 * @return Url URL with ID populated.
	 */
	public function insert( Url $url ): Url {
		global $wpdb;

		$data = $url->to_row();

		// Ensure first_seen is set.
		if ( empty( $data['first_seen'] ) ) {
			$data['first_seen'] = current_time( 'mysql' );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$this->table,
			$data,
			$this->get_format( $data )
		);

		$url->id = (int) $wpdb->insert_id;

		return $url;
	}

	/**
	 * Update an existing URL.
	 *
	 * @since 1.0.0
	 * @param Url $url URL entity.
	 * @return bool Whether update succeeded.
	 */
	public function update( Url $url ): bool {
		global $wpdb;

		if ( null === $url->id ) {
			return false;
		}

		$data = $url->to_row();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			$data,
			array( 'id' => $url->id ),
			$this->get_format( $data ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete a URL and all its links.
	 *
	 * @since 1.0.0
	 * @param int $id URL ID.
	 * @return bool Whether deletion succeeded.
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$this->table,
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Get URLs needing checking.
	 *
	 * @since 1.0.0
	 * @param int $limit      Maximum URLs to return.
	 * @param int $after_id   Start after this ID (for cursor pagination).
	 * @param int $scan_id    Optional scan ID for phase tracking.
	 * @return array<Url>
	 */
	public function get_pending( int $limit = 20, int $after_id = 0, int $scan_id = 0 ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} 
				WHERE status = %s 
				AND is_ignored = 0 
				AND id > %d 
				ORDER BY id ASC 
				LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				Url::STATUS_PENDING,
				$after_id,
				$limit
			)
		);

		return array_map( fn( $row ) => Url::from_row( $row ), $rows );
	}

	/**
	 * Get URLs due for recheck.
	 *
	 * @since 1.0.0
	 * @param int $limit Maximum URLs to return.
	 * @return array<Url>
	 */
	public function get_due_for_recheck( int $limit = 20 ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} 
				WHERE next_check IS NOT NULL 
				AND next_check <= %s 
				AND is_ignored = 0 
				ORDER BY next_check ASC 
				LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				current_time( 'mysql' ),
				$limit
			)
		);

		return array_map( fn( $row ) => Url::from_row( $row ), $rows );
	}

	/**
	 * Get URLs by status.
	 *
	 * @since 1.0.0
	 * @param string $status Status to filter by.
	 * @param int    $limit  Maximum URLs to return.
	 * @param int    $offset Offset for pagination.
	 * @return array<Url>
	 */
	public function get_by_status( string $status, int $limit = 50, int $offset = 0 ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} 
				WHERE status = %s 
				ORDER BY last_checked DESC 
				LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$status,
				$limit,
				$offset
			)
		);

		return array_map( fn( $row ) => Url::from_row( $row ), $rows );
	}

	/**
	 * Count URLs by status.
	 *
	 * @since 1.0.0
	 * @param string|null $status Optional status to filter by.
	 * @return int
	 */
	public function count( ?string $status = null ): int {
		global $wpdb;

		if ( $status ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table} WHERE status = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$status
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table}" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	/**
	 * Count all URLs. Alias for count().
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function count_all(): int {
		return $this->count();
	}

	/**
	 * Count URLs by specific status. Alias for count().
	 *
	 * @since 1.0.0
	 * @param string $status Status to count.
	 * @return int
	 */
	public function count_by_status( string $status ): int {
		return $this->count( $status );
	}

	/**
	 * Get status counts.
	 *
	 * @since 1.0.0
	 * @return array<string, int>
	 */
	public function get_status_counts(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		$counts = array_fill_keys( Url::STATUSES, 0 );

		foreach ( $rows as $row ) {
			$counts[ $row->status ] = (int) $row->count;
		}

		return $counts;
	}

	/**
	 * Mark URL as ignored.
	 *
	 * @since 1.0.0
	 * @param int         $id     URL ID.
	 * @param string|null $reason Optional reason.
	 * @return bool
	 */
	public function mark_ignored( int $id, ?string $reason = null ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			array(
				'is_ignored'    => 1,
				'ignore_reason' => $reason,
			),
			array( 'id' => $id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Unmark URL as ignored.
	 *
	 * @since 1.0.0
	 * @param int $id URL ID.
	 * @return bool
	 */
	public function unmark_ignored( int $id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			array(
				'is_ignored'    => 0,
				'ignore_reason' => null,
			),
			array( 'id' => $id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Reset all URLs to pending state.
	 *
	 * @since 1.0.0
	 * @return int Number of URLs reset.
	 */
	public function reset_all(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table} SET status = %s, http_code = NULL, final_url = NULL, 
				redirect_count = 0, response_time = NULL, error_type = NULL, error_message = NULL 
				WHERE is_ignored = 0", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				Url::STATUS_PENDING
			)
		);

		return $result !== false ? (int) $result : 0;
	}

	/**
	 * Count pending URLs.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function count_pending(): int {
		return $this->count( Url::STATUS_PENDING );
	}

	/**
	 * Get sprintf format array for data.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Data array.
	 * @return array<string>
	 */
	private function get_format( array $data ): array {
		$formats = array();

		foreach ( $data as $key => $value ) {
			if ( is_int( $value ) || in_array( $key, array( 'is_internal', 'is_ignored', 'check_count', 'redirect_count', 'response_time', 'http_code' ), true ) ) {
				$formats[] = '%d';
			} else {
				$formats[] = '%s';
			}
		}

		return $formats;
	}
}
