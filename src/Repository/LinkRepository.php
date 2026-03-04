<?php
/**
 * Link Repository.
 *
 * Handles CRUD operations for the yoko_lc_links table.
 * Manages link occurrences in content.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Repository;

defined( 'ABSPATH' ) || exit;

use YokoLinkChecker\Model\Link;
use YokoLinkChecker\Model\Url;

/**
 * Link repository for database operations.
 *
 * @since 1.0.0
 */
final class LinkRepository {

	/**
	 * Database table name.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * URLs table name.
	 *
	 * @var string
	 */
	private string $urls_table;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;

		$this->table      = $wpdb->prefix . 'yoko_lc_links';
		$this->urls_table = $wpdb->prefix . 'yoko_lc_urls';
	}

	/**
	 * Find link by ID.
	 *
	 * @since 1.0.0
	 * @param int $id Link ID.
	 * @return Link|null
	 */
	public function find( int $id ): ?Link {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$id
			)
		);

		return $row ? Link::from_row( $row ) : null;
	}

	/**
	 * Find existing link by unique key.
	 *
	 * @since 1.0.0
	 * @param int    $url_id       URL ID.
	 * @param int    $source_id    Source post ID.
	 * @param string $source_type  Source post type.
	 * @param string $source_field Source field.
	 * @return Link|null
	 */
	public function find_existing( int $url_id, int $source_id, string $source_type, string $source_field ): ?Link {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} 
				WHERE url_id = %d AND source_id = %d AND source_type = %s AND source_field = %s",
				$url_id,
				$source_id,
				$source_type,
				$source_field
			)
		);
		// phpcs:enable

		return $row ? Link::from_row( $row ) : null;
	}

	/**
	 * Find existing links for multiple tuples in a single query.
	 *
	 * Returns existing links keyed by a composite key of
	 * "url_id:source_id:source_type:source_field".
	 *
	 * @since 1.0.11
	 * @param array<array{url_id: int, source_id: int, source_type: string, source_field: string}> $criteria Array of lookup tuples.
	 * @return array<string, Link> Links keyed by composite key.
	 */
	public function find_existing_batch( array $criteria ): array {
		if ( empty( $criteria ) ) {
			return array();
		}

		global $wpdb;

		// Build OR conditions for each tuple.
		$conditions = array();
		$params     = array();
		foreach ( $criteria as $tuple ) {
			$conditions[] = '(url_id = %d AND source_id = %d AND source_type = %s AND source_field = %s)';
			$params[]     = $tuple['url_id'];
			$params[]     = $tuple['source_id'];
			$params[]     = $tuple['source_type'];
			$params[]     = $tuple['source_field'];
		}

		$where = implode( ' OR ', $conditions );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe.
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $where uses placeholders built above.
		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders are built dynamically.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE {$where}",
				...$params
			)
		);
		// phpcs:enable

		$result = array();
		foreach ( $rows as $row ) {
			$link = Link::from_row( $row );
			$key  = $link->url_id . ':' . $link->source_id . ':' . $link->source_type . ':' . $link->source_field;

			$result[ $key ] = $link;
		}

		return $result;
	}

	/**
	 * Insert a new link.
	 *
	 * @since 1.0.0
	 * @param Link $link Link entity.
	 * @return Link|null Link with ID populated, or null on failure.
	 */
	public function insert( Link $link ): ?Link {
		global $wpdb;

		$now = current_time( 'mysql' );

		if ( empty( $link->created_at ) ) {
			$link->created_at = $now;
		}
		$link->updated_at = $now;

		$data = $link->to_row();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$this->table,
			$data,
			$this->get_format( $data )
		);

		if ( false === $result ) {
			return null;
		}

		$link->id = (int) $wpdb->insert_id;

		return $link;
	}

	/**
	 * Update an existing link.
	 *
	 * @since 1.0.0
	 * @param Link $link Link entity.
	 * @return bool Whether update succeeded.
	 */
	public function update( Link $link ): bool {
		global $wpdb;

		if ( null === $link->id ) {
			return false;
		}

		$link->updated_at = current_time( 'mysql' );
		$data             = $link->to_row();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			$data,
			array( 'id' => $link->id ),
			$this->get_format( $data ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Count total links.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function count(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table}" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	/**
	 * Get links with URL data for list table display.
	 * Returns flat stdClass objects for direct use in WP_List_Table.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<\stdClass>
	 */
	public function get_links_with_urls( array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'per_page' => 20,
			'page'     => 1,
			'orderby'  => 'last_checked',
			'order'    => 'DESC',
			'status'   => null,
			'search'   => '',
		);

		$args   = wp_parse_args( $args, $defaults );
		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// Build base query with aliased columns for list table compatibility.
		$sql = "SELECT 
				l.id as link_id,
				l.source_id as post_id,
				l.source_type,
				l.source_field,
				l.anchor_text,
				l.link_context,
				u.id as url_id,
				u.url,
				u.url_normalized,
				u.status,
				u.http_code,
				u.final_url,
				u.error_type,
				u.error_message,
				u.is_internal,
				u.last_checked,
				u.is_ignored as ignored,
				u.response_time
				FROM {$this->table} l 
				JOIN {$this->urls_table} u ON l.url_id = u.id 
				WHERE 1=1"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$params = array();

		// Filter by status.
		if ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) {
			$sql     .= ' AND u.status = %s';
			$params[] = $args['status'];
		}

		// Search filter.
		if ( ! empty( $args['search'] ) ) {
			$search   = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$sql     .= ' AND (u.url LIKE %s OR l.anchor_text LIKE %s)';
			$params[] = $search;
			$params[] = $search;
		}

		// Exclude ignored unless specifically requested.
		if ( empty( $args['include_ignored'] ) ) {
			$sql     .= ' AND u.is_ignored = %d';
			$params[] = 0;
		}

		// Order by.
		$allowed_orderby = array( 'url', 'status', 'http_code', 'last_checked', 'post_id' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'last_checked';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		// Map orderby to actual columns.
		if ( 'url' === $orderby ) {
			$orderby = 'u.url';
		} elseif ( 'status' === $orderby ) {
			$orderby = 'u.status';
		} elseif ( 'last_checked' === $orderby ) {
			$orderby = 'u.last_checked';
		} elseif ( 'http_code' === $orderby ) {
			$orderby = 'u.http_code';
		} elseif ( 'post_id' === $orderby ) {
			$orderby = 'l.source_id';
		}

		$sql .= " ORDER BY {$orderby} {$order}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Pagination.
		$sql     .= ' LIMIT %d OFFSET %d';
		$params[] = $args['per_page'];
		$params[] = $offset;

		// Execute query.
		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $sql );
		}

		return $rows ? $rows : array();
	}

	/**
	 * Count links with optional status filter.
	 *
	 * @since 1.0.0
	 * @param string|null $status Status to filter by, or null for all.
	 * @return int
	 */
	public function count_links_with_status( ?string $status = null ): int {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are safe.
		$sql = "SELECT COUNT(DISTINCT l.id) 
				FROM {$this->table} l 
				JOIN {$this->urls_table} u ON l.url_id = u.id 
				WHERE u.is_ignored = 0";
		// phpcs:enable

		if ( ! empty( $status ) && 'all' !== $status ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $sql is built safely above.
			return (int) $wpdb->get_var(
				$wpdb->prepare( $sql . ' AND u.status = %s', $status )
			);
			// phpcs:enable
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Stream links for CSV export using chunked queries.
	 *
	 * Uses keyset (cursor-based) pagination and yields rows one at a time via
	 * a PHP generator, ensuring constant memory usage and O(n) query performance
	 * regardless of dataset size.
	 *
	 * @since 1.0.9
	 * @since 1.0.10 Switched from LIMIT/OFFSET to keyset pagination for O(n) performance.
	 * @param int $chunk_size Number of rows to fetch per database query. Default 1000.
	 * @return \Generator<int, \stdClass> Yields stdClass row objects with source_url populated.
	 */
	public function stream_for_export( int $chunk_size = 1000 ): \Generator {
		global $wpdb;

		$chunk_size = max( 1, $chunk_size );
		$last_id    = 0;

		do {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are safe.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT
						l.id,
						u.url,
						u.status,
						u.http_code,
						u.error_message,
						u.last_checked,
						l.anchor_text as link_text,
						l.source_id,
						l.source_type,
						p.post_title,
						p.post_type,
						p.guid as source_guid
					FROM {$this->table} l
					JOIN {$this->urls_table} u ON l.url_id = u.id
					LEFT JOIN {$wpdb->posts} p ON l.source_id = p.ID AND l.source_type = 'post'
					WHERE l.id > %d
					ORDER BY l.id ASC
					LIMIT %d",
					$last_id,
					$chunk_size
				)
			);
			// phpcs:enable

			if ( empty( $rows ) ) {
				break;
			}

			// Prime post caches in a single query to avoid N+1 get_permalink() calls.
			$post_ids = array();
			foreach ( $rows as $row ) {
				if ( ! empty( $row->source_id ) && 'post' === $row->source_type ) {
					$post_ids[] = (int) $row->source_id;
				}
			}
			if ( ! empty( $post_ids ) ) {
				_prime_post_caches( array_unique( $post_ids ), true, false );
			}

			foreach ( $rows as $row ) {
				if ( ! empty( $row->source_id ) && 'post' === $row->source_type ) {
					$row->source_url = get_permalink( (int) $row->source_id );
				} else {
					$row->source_url = '';
				}
				yield $row;
			}

			$last_id    = (int) end( $rows )->id;
			$rows_count = count( $rows );
		} while ( $rows_count === $chunk_size );
	}

	/**
	 * Get recent broken links with source and post data.
	 *
	 * Returns broken URLs joined with their link occurrences and post titles.
	 *
	 * @since 1.0.8
	 * @param int $limit Maximum broken links to return.
	 * @return array<array<string, mixed>>
	 */
	public function get_recent_broken( int $limit = 10 ): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are safe.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.id, u.url, u.http_code, u.error_message, u.last_checked,
				        l.source_id, l.source_type, l.anchor_text,
				        p.post_title
				 FROM {$this->urls_table} u
				 LEFT JOIN {$this->table} l ON l.id = (
				     SELECT l2.id FROM {$this->table} l2 WHERE l2.url_id = u.id LIMIT 1
				 )
				 LEFT JOIN {$wpdb->posts} p ON l.source_id = p.ID
				 WHERE u.status = %s
				 ORDER BY u.last_checked DESC
				 LIMIT %d",
				Url::STATUS_BROKEN,
				$limit
			)
		);
		// phpcs:enable

		$broken = array();

		foreach ( $results as $row ) {
			$source_id  = $row->source_id ? (int) $row->source_id : 0;
			$post_title = '';

			if ( $source_id ) {
				$post_title = $row->post_title ?? '';
			}

			$broken[] = array(
				'id'            => (int) $row->id,
				'url'           => $row->url,
				'http_code'     => (int) $row->http_code,
				'error_message' => $row->error_message,
				'last_checked'  => $row->last_checked,
				'source_id'     => $source_id,
				'source_type'   => $row->source_type ?? '',
				'post_title'    => $post_title,
				'anchor_text'   => $row->anchor_text,
			);
		}

		return $broken;
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
			if ( is_int( $value ) || in_array( $key, array( 'url_id', 'source_id', 'link_position' ), true ) ) {
				$formats[] = '%d';
			} else {
				$formats[] = '%s';
			}
		}

		return $formats;
	}
}
