<?php
/**
 * Extractor Interface.
 *
 * Defines the contract for link extraction implementations.
 * New extractors (Gutenberg, page builders, ACF, etc.) implement this interface.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Extractor;

use WP_Post;

/**
 * Link extractor interface.
 *
 * @since 1.0.0
 */
interface ExtractorInterface {

	/**
	 * Get unique identifier for this extractor.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Check if this extractor supports the given post.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Post to check.
	 * @return bool Whether this extractor can process the post.
	 */
	public function supports( WP_Post $post ): bool;

	/**
	 * Extract links from a post.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Post to extract links from.
	 * @return array<ExtractedLink> Extracted links.
	 */
	public function extract( WP_Post $post ): array;

	/**
	 * Get priority for this extractor.
	 *
	 * Lower numbers run first. Use this to control extraction order.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function get_priority(): int;
}
