<?php
/**
 * Extractor Registry.
 *
 * Manages registered link extractors and coordinates extraction.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Extractor;

use WP_Post;

/**
 * Extractor registry and coordinator.
 *
 * @since 1.0.0
 */
final class ExtractorRegistry {

	/**
	 * Registered extractors.
	 *
	 * @var array<ExtractorInterface>
	 */
	private array $extractors = array();

	/**
	 * Whether extractors are sorted by priority.
	 *
	 * @var bool
	 */
	private bool $sorted = false;

	/**
	 * Register an extractor.
	 *
	 * @since 1.0.0
	 * @param ExtractorInterface $extractor Extractor to register.
	 * @return void
	 */
	public function register( ExtractorInterface $extractor ): void {
		$this->extractors[ $extractor->get_id() ] = $extractor;
		$this->sorted                             = false;
	}

	/**
	 * Get extractors that support a post.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Post to check.
	 * @return array<ExtractorInterface>
	 */
	public function get_supporting( WP_Post $post ): array {
		$this->sort_extractors();

		return array_values(
			array_filter(
				$this->extractors,
				fn( ExtractorInterface $e ) => $e->supports( $post )
			)
		);
	}

	/**
	 * Extract links from a post using all supporting extractors.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Post to extract from.
	 * @return array<ExtractedLink>
	 */
	public function extract_from_post( WP_Post $post ): array {
		$extractors = $this->get_supporting( $post );
		$all_links  = array();
		$seen_urls  = array();

		foreach ( $extractors as $extractor ) {
			$links = $extractor->extract( $post );

			foreach ( $links as $link ) {
				// Basic deduplication within the same post.
				$key = $link->url . '|' . $link->field;

				if ( isset( $seen_urls[ $key ] ) ) {
					continue;
				}

				$seen_urls[ $key ] = true;
				$all_links[]       = $link;
			}
		}

		return $all_links;
	}

	/**
	 * Sort extractors by priority.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function sort_extractors(): void {
		if ( $this->sorted ) {
			return;
		}

		uasort(
			$this->extractors,
			fn( ExtractorInterface $a, ExtractorInterface $b ) => $a->get_priority() <=> $b->get_priority()
		);

		$this->sorted = true;
	}
}
