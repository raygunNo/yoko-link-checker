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
	 * Find or create link.
	 *
	 * @since 1.0.0
	 * @param Link $link Link entity to find or create.
	 * @return Link The existing or newly created link.
	 */
	public function find_or_create( Link $link ): Link {
		$existing = $this->find_existing(
			$link->url_id,
			$link->source_id,
			$link->source_type,
			$link->source_field
		);

		if ( $existing ) {
			// Update existing link with new data.
			$existing->anchor_text   = $link->anchor_text;
			$existing->link_context  = $link->link_context;
			$existing->link_position = $link->link_position;
			$existing->updated_at    = current_time( 'mysql' );
			$this->update( $existing );
			return $existing;
		}

		return $this->insert( $link );
	}

	/**
	 * Insert a new link.
	 *
	 * @since 1.0.0
	 * @param Link $link Link entity.
	 * @return Link Link with ID populated.
	 */
	public function insert( Link $link ): Link {
		global $wpdb;

		$now = current_time( 'mysql' );

		if ( empty( $link->created_at ) ) {
			$link->created_at = $now;
		}
		$link->updated_at = $now;

		$data = $link->to_row();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$this->table,
			$data,
			$this->get_format( $data )
		);

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
	 * Delete a link.
	 *
	 * @since 1.0.0
	 * @param int $id Link ID.
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
	 * Delete all links for a source.
	 *
	 * @since 1.0.0
	 * @param int    $source_id   Source post ID.
	 * @param string $source_type Source post type.
	 * @return int Number of links deleted.
	 */
	public function delete_by_source( int $source_id, string $source_type ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$this->table,
			array(
				'source_id'   => $source_id,
				'source_type' => $source_type,
			),
			array( '%d', '%s' )
		);

		return false !== $result ? (int) $result : 0;
	}

	/**
	 * Get links for a URL with joined URL data.
	 *
	 * @since 1.0.0
	 * @param int $url_id URL ID.
	 * @return array<Link>
	 */
	public function get_by_url( int $url_id ): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are safe.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.*, u.url, u.url_normalized, u.status as url_status, u.http_code, u.final_url, u.error_message as url_error 
				FROM {$this->table} l 
				JOIN {$this->urls_table} u ON l.url_id = u.id 
				WHERE l.url_id = %d 
				ORDER BY l.source_id ASC",
				$url_id
			)
		);
		// phpcs:enable

		return array_map(
			function ( $row ) {
				$link = Link::from_row( $row );
				// Attach URL data.
				$link->url = Url::from_row( $row );
				return $link;
			},
			$rows
		);
	}

	/**
	 * Get links for a source.
	 *
	 * @since 1.0.0
	 * @param int    $source_id   Source post ID.
	 * @param string $source_type Source post type.
	 * @return array<Link>
	 */
	public function get_by_source( int $source_id, string $source_type ): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are safe.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.*, u.url, u.url_normalized, u.status as url_status, u.http_code, u.final_url, u.error_message as url_error 
				FROM {$this->table} l 
				JOIN {$this->urls_table} u ON l.url_id = u.id 
				WHERE l.source_id = %d AND l.source_type = %s 
				ORDER BY l.link_position ASC",
				$source_id,
				$source_type
			)
		);
		// phpcs:enable

		return array_map(
			function ( $row ) {
				$link      = Link::from_row( $row );
				$link->url = Url::from_row( $row );
				return $link;
			},
			$rows
		);
	}

	/**
	 * Get links with problems (joined with URL status).
	 *
	 * @since 1.0.0
	 * @param array<string>        $statuses Statuses to include.
	 * @param int                  $limit    Maximum links to return.
	 * @param int                  $offset   Offset for pagination.
	 * @param array<string, mixed> $filters Additional filters.
	 * @return array<Link>
	 */
	public function get_with_status( array $statuses, int $limit = 50, int $offset = 0, array $filters = array() ): array {
		global $wpdb;

		// Build status placeholders.
		$status_placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		// Build base query.
		$sql = "SELECT l.*, u.id as url_id, u.url, u.url_normalized, u.status as url_status, 
				u.http_code, u.final_url, u.error_type, u.error_message as url_error, 
				u.is_internal, u.last_checked, u.is_ignored 
				FROM {$this->table} l 
				JOIN {$this->urls_table} u ON l.url_id = u.id 
				WHERE u.status IN ({$status_placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$params = $statuses;

		// Apply filters.
		if ( ! empty( $filters['is_internal'] ) ) {
			$sql     .= ' AND u.is_internal = %d';
			$params[] = 1;
		} elseif ( isset( $filters['is_internal'] ) && false === $filters['is_internal'] ) {
			$sql     .= ' AND u.is_internal = %d';
			$params[] = 0;
		}

		if ( ! empty( $filters['source_type'] ) ) {
			$sql     .= ' AND l.source_type = %s';
			$params[] = $filters['source_type'];
		}

		if ( ! empty( $filters['is_ignored'] ) ) {
			$sql     .= ' AND u.is_ignored = %d';
			$params[] = 1;
		} elseif ( empty( $filters['include_ignored'] ) ) {
			$sql     .= ' AND u.is_ignored = %d';
			$params[] = 0;
		}

		$sql     .= ' ORDER BY u.last_checked DESC, l.id DESC LIMIT %d OFFSET %d';
		$params[] = $limit;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );

		return array_map(
			function ( $row ) {
				$link      = Link::from_row( $row );
				$link->url = Url::from_row( $row );
				return $link;
			},
			$rows
		);
	}

	/**
	 * Count links with status.
	 *
	 * @since 1.0.0
	 * @param array<string>        $statuses Statuses to count.
	 * @param array<string, mixed> $filters Additional filters.
	 * @return int
	 */
	public function count_with_status( array $statuses, array $filters = array() ): int {
		global $wpdb;

		$status_placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		$sql = "SELECT COUNT(*) FROM {$this->table} l 
				JOIN {$this->urls_table} u ON l.url_id = u.id 
				WHERE u.status IN ({$status_placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$params = $statuses;

		if ( ! empty( $filters['is_internal'] ) ) {
			$sql     .= ' AND u.is_internal = %d';
			$params[] = 1;
		} elseif ( isset( $filters['is_internal'] ) && false === $filters['is_internal'] ) {
			$sql     .= ' AND u.is_internal = %d';
			$params[] = 0;
		}

		if ( ! empty( $filters['source_type'] ) ) {
			$sql     .= ' AND l.source_type = %s';
			$params[] = $filters['source_type'];
		}

		if ( empty( $filters['include_ignored'] ) ) {
			$sql     .= ' AND u.is_ignored = %d';
			$params[] = 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
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
	 * Get all links for CSV export.
	 *
	 * @since 1.0.3
	 * @return array<\stdClass>
	 */
	public function get_all_for_export(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT 
				u.url,
				u.status,
				u.http_code,
				u.error_message,
				u.last_checked,
				l.anchor_text as link_text,
				l.source_id,
				l.source_type,
				p.post_title,
				p.post_type
			FROM {$this->table} l
			JOIN {$this->urls_table} u ON l.url_id = u.id
			LEFT JOIN {$wpdb->posts} p ON l.source_id = p.ID AND l.source_type = 'post'
			ORDER BY u.status ASC, u.url ASC"
		);

		return $rows ? $rows : array();
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
