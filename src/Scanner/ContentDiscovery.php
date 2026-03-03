<?php
/**
 * Content Discovery service.
 *
 * Discovers posts and content to scan for links.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Scanner;

use WP_Post;

/**
 * Content discovery service.
 *
 * @since 1.0.0
 */
final class ContentDiscovery {

	/**
	 * Get scannable post types.
	 *
	 * @since 1.0.0
	 * @return array<string>
	 */
	public function get_post_types(): array {
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		// Remove attachment post type.
		unset( $post_types['attachment'] );

		/**
		 * Filters the post types to scan.
		 *
		 * @since 1.0.0
		 * @param array<string> $post_types Post type slugs.
		 */
		return apply_filters( 'yoko_lc_scannable_post_types', array_values( $post_types ) );
	}

	/**
	 * Get scannable post statuses.
	 *
	 * @since 1.0.0
	 * @return array<string>
	 */
	public function get_post_statuses(): array {
		$statuses = array( 'publish' );

		/**
		 * Filters the post statuses to scan.
		 *
		 * @since 1.0.0
		 * @param array<string> $statuses Post status slugs.
		 */
		return apply_filters( 'yoko_lc_scannable_post_statuses', $statuses );
	}

	/**
	 * Count total scannable posts.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function count_posts(): int {
		global $wpdb;

		$post_types = $this->get_post_types();
		$statuses   = $this->get_post_statuses();

		if ( empty( $post_types ) || empty( $statuses ) ) {
			return 0;
		}

		$type_placeholders   = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
		$status_placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		$params = array_merge( $post_types, $statuses );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} 
				WHERE post_type IN ({$type_placeholders}) 
				AND post_status IN ({$status_placeholders})",
				$params
			)
		);
		// phpcs:enable
	}

	/**
	 * Get a batch of posts for scanning.
	 *
	 * Uses cursor-based pagination for efficient large dataset handling.
	 *
	 * @since 1.0.0
	 * @param int $after_id    Get posts with ID greater than this.
	 * @param int $batch_size  Number of posts to return.
	 * @return array<WP_Post>
	 */
	public function get_batch( int $after_id = 0, int $batch_size = 50 ): array {
		global $wpdb;

		$post_types = $this->get_post_types();
		$statuses   = $this->get_post_statuses();

		if ( empty( $post_types ) || empty( $statuses ) ) {
			return array();
		}

		$type_placeholders   = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
		$status_placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		$params = array_merge( $post_types, $statuses, array( $after_id, $batch_size ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} 
				WHERE post_type IN ({$type_placeholders}) 
				AND post_status IN ({$status_placeholders}) 
				AND ID > %d 
				ORDER BY ID ASC 
				LIMIT %d",
				$params
			)
		);
		// phpcs:enable

		if ( empty( $post_ids ) ) {
			return array();
		}

		// Fetch full post objects.
		$posts = array_filter(
			array_map(
				fn( $id ) => get_post( (int) $id ),
				$post_ids
			)
		);

		return array_values( $posts );
	}

	/**
	 * Check if a post is scannable.
	 *
	 * @since 1.0.0
	 * @param WP_Post|int $post Post object or ID.
	 * @return bool
	 */
	public function is_scannable( $post ): bool {
		if ( is_int( $post ) ) {
			$post = get_post( $post );
		}

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		$post_types = $this->get_post_types();
		$statuses   = $this->get_post_statuses();

		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return false;
		}

		if ( ! in_array( $post->post_status, $statuses, true ) ) {
			return false;
		}

		/**
		 * Filters whether a specific post is scannable.
		 *
		 * @since 1.0.0
		 * @param bool    $scannable Whether the post is scannable.
		 * @param WP_Post $post      The post object.
		 */
		return apply_filters( 'yoko_lc_is_post_scannable', true, $post );
	}

	/**
	 * Get posts modified since a given date.
	 *
	 * Used for incremental scans.
	 *
	 * @since 1.0.0
	 * @param string $since      Date string (MySQL format).
	 * @param int    $after_id   Get posts with ID greater than this.
	 * @param int    $batch_size Number of posts to return.
	 * @return array<WP_Post>
	 */
	public function get_modified_since( string $since, int $after_id = 0, int $batch_size = 50 ): array {
		global $wpdb;

		$post_types = $this->get_post_types();
		$statuses   = $this->get_post_statuses();

		if ( empty( $post_types ) || empty( $statuses ) ) {
			return array();
		}

		$type_placeholders   = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
		$status_placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		$params = array_merge( $post_types, $statuses, array( $since, $after_id, $batch_size ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} 
				WHERE post_type IN ({$type_placeholders}) 
				AND post_status IN ({$status_placeholders}) 
				AND post_modified >= %s 
				AND ID > %d 
				ORDER BY ID ASC 
				LIMIT %d",
				$params
			)
		);
		// phpcs:enable

		if ( empty( $post_ids ) ) {
			return array();
		}

		$posts = array_filter(
			array_map(
				fn( $id ) => get_post( (int) $id ),
				$post_ids
			)
		);

		return array_values( $posts );
	}
}
