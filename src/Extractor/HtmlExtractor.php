<?php
/**
 * HTML Link Extractor.
 *
 * Extracts links from HTML content using DOM parsing.
 * Handles anchor tags and optionally images.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Extractor;

use WP_Post;
use DOMDocument;
use DOMXPath;
use YokoLinkChecker\Util\UrlNormalizer;

/**
 * HTML content link extractor.
 *
 * @since 1.0.0
 */
final class HtmlExtractor implements ExtractorInterface {

	/**
	 * URL normalizer instance.
	 *
	 * @var UrlNormalizer
	 */
	private UrlNormalizer $normalizer;

	/**
	 * Whether to extract images.
	 *
	 * @var bool
	 */
	private bool $extract_images;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param UrlNormalizer $normalizer      URL normalizer.
	 * @param bool          $extract_images  Whether to extract images.
	 */
	public function __construct( UrlNormalizer $normalizer, bool $extract_images = true ) {
		$this->normalizer     = $normalizer;
		$this->extract_images = $extract_images;
	}

	/**
	 * Get extractor ID.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_id(): string {
		return 'html';
	}

	/**
	 * Get extractor name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name(): string {
		return __( 'HTML Content', 'yoko-link-checker' );
	}

	/**
	 * Check if extractor supports the post.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Post to check.
	 * @return bool
	 */
	public function supports( WP_Post $post ): bool {
		// Support all posts with content.
		return ! empty( $post->post_content );
	}

	/**
	 * Get processed fields.
	 *
	 * @since 1.0.0
	 * @return array<string>
	 */
	public function get_fields(): array {
		return array( 'post_content' );
	}

	/**
	 * Get priority.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function get_priority(): int {
		return 10;
	}

	/**
	 * Extract links from post.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Post to extract from.
	 * @return array<ExtractedLink>
	 */
	public function extract( WP_Post $post ): array {
		$content = $post->post_content;

		if ( empty( $content ) ) {
			return array();
		}

		// Get the post permalink for resolving relative URLs.
		$base_url = get_permalink( $post );

		$links = array();

		// Parse content as DOM.
		$dom = $this->create_dom( $content );

		if ( ! $dom ) {
			// Fallback to regex if DOM parsing fails.
			return $this->extract_with_regex( $content, $base_url );
		}

		// Extract anchors.
		$anchors = $this->extract_anchors( $dom, $base_url );
		$links   = array_merge( $links, $anchors );

		// Extract images if enabled.
		if ( $this->extract_images ) {
			$images = $this->extract_images( $dom, $base_url );
			$links  = array_merge( $links, $images );
		}

		return $links;
	}

	/**
	 * Create DOM document from HTML content.
	 *
	 * @since 1.0.0
	 * @param string $html HTML content.
	 * @return DOMDocument|null
	 */
	private function create_dom( string $html ): ?DOMDocument {
		if ( empty( trim( $html ) ) ) {
			return null;
		}

		$dom = new DOMDocument();

		// Suppress warnings from malformed HTML.
		$internal_errors = libxml_use_internal_errors( true );

		// Wrap content to ensure proper encoding.
		$wrapped = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>';

		$loaded = $dom->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		libxml_clear_errors();
		libxml_use_internal_errors( $internal_errors );

		if ( ! $loaded ) {
			return null;
		}

		return $dom;
	}

	/**
	 * Extract anchor links from DOM.
	 *
	 * @since 1.0.0
	 * @param DOMDocument  $dom      DOM document.
	 * @param string|false $base_url Base URL for relative links.
	 * @return array<ExtractedLink>
	 */
	private function extract_anchors( DOMDocument $dom, $base_url ): array {
		$xpath = new DOMXPath( $dom );
		$nodes = $xpath->query( '//a[@href]' );

		if ( false === $nodes ) {
			return array();
		}

		$links    = array();
		$position = 0;

		foreach ( $nodes as $node ) {
			$href = $node->getAttribute( 'href' );

			if ( empty( $href ) ) {
				continue;
			}

			// Get anchor text.
			$text = $this->get_node_text( $node );

			// Get all attributes.
			$attributes = array();
			foreach ( $node->attributes as $attr ) {
				$attributes[ $attr->name ] = $attr->value;
			}

			$link = ExtractedLink::from_anchor(
				$href,
				$text,
				'post_content',
				$position,
				$attributes
			);

			// Skip non-processable URLs.
			if ( ! $link->has_processable_url() ) {
				continue;
			}

			$links[] = $link;
			++$position;
		}

		return $links;
	}

	/**
	 * Extract image links from DOM.
	 *
	 * @since 1.0.0
	 * @param DOMDocument  $dom      DOM document.
	 * @param string|false $base_url Base URL for relative links.
	 * @return array<ExtractedLink>
	 */
	private function extract_images( DOMDocument $dom, $base_url ): array {
		$xpath = new DOMXPath( $dom );
		$nodes = $xpath->query( '//img[@src]' );

		if ( false === $nodes ) {
			return array();
		}

		$links    = array();
		$position = 0;

		foreach ( $nodes as $node ) {
			$src = $node->getAttribute( 'src' );

			if ( empty( $src ) ) {
				continue;
			}

			// Skip data URIs.
			if ( str_starts_with( $src, 'data:' ) ) {
				continue;
			}

			// Get alt text.
			$alt = $node->getAttribute( 'alt' );

			// Get all attributes.
			$attributes = array();
			foreach ( $node->attributes as $attr ) {
				$attributes[ $attr->name ] = $attr->value;
			}

			$links[] = ExtractedLink::from_image(
				$src,
				$alt ? $alt : null,
				'post_content',
				$position,
				$attributes
			);

			++$position;

			// Also extract srcset URLs if present.
			$srcset = $node->getAttribute( 'srcset' );
			if ( ! empty( $srcset ) ) {
				$srcset_urls = $this->parse_srcset( $srcset );
				foreach ( $srcset_urls as $srcset_url ) {
					$links[] = ExtractedLink::from_image(
						$srcset_url,
						$alt ? $alt : null,
						'post_content',
						$position,
						array( 'from_srcset' => 'true' )
					);
					++$position;
				}
			}
		}

		return $links;
	}

	/**
	 * Get text content from a DOM node.
	 *
	 * @since 1.0.0
	 * @param \DOMNode $node DOM node.
	 * @return string
	 */
	private function get_node_text( \DOMNode $node ): string {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM property.
		$text = $node->textContent ?? '';
		$text = trim( preg_replace( '/\s+/', ' ', $text ) );

		// Limit length.
		if ( mb_strlen( $text ) > 500 ) {
			$text = mb_substr( $text, 0, 500 );
		}

		return $text;
	}

	/**
	 * Parse srcset attribute into URLs.
	 *
	 * @since 1.0.0
	 * @param string $srcset Srcset attribute value.
	 * @return array<string>
	 */
	private function parse_srcset( string $srcset ): array {
		$urls  = array();
		$parts = explode( ',', $srcset );

		foreach ( $parts as $part ) {
			$part = trim( $part );
			// Format: "url descriptor" or just "url".
			$segments = preg_split( '/\s+/', $part, 2 );
			if ( ! empty( $segments[0] ) ) {
				$urls[] = $segments[0];
			}
		}

		return $urls;
	}

	/**
	 * Fallback regex extraction.
	 *
	 * Used when DOM parsing fails.
	 *
	 * @since 1.0.0
	 * @param string       $content  HTML content.
	 * @param string|false $base_url Base URL.
	 * @return array<ExtractedLink>
	 */
	private function extract_with_regex( string $content, $base_url ): array {
		$links = array();

		// Extract href attributes.
		preg_match_all(
			'/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is',
			$content,
			$matches,
			PREG_SET_ORDER | PREG_OFFSET_CAPTURE
		);

		foreach ( $matches as $match ) {
			$url  = $match[1][0];
			$text = wp_strip_all_tags( $match[2][0] );
			$pos  = $match[0][1];

			$trimmed_text = trim( $text );
			$link         = ExtractedLink::from_anchor(
				$url,
				$trimmed_text ? $trimmed_text : null,
				'post_content',
				$pos
			);

			if ( $link->has_processable_url() ) {
				$links[] = $link;
			}
		}

		// Extract img src if enabled.
		if ( $this->extract_images ) {
			preg_match_all(
				'/<img\s[^>]*src=["\']([^"\']+)["\'][^>]*>/is',
				$content,
				$img_matches,
				PREG_SET_ORDER | PREG_OFFSET_CAPTURE
			);

			foreach ( $img_matches as $match ) {
				$src = $match[1][0];
				$pos = $match[0][1];

				// Skip data URIs.
				if ( str_starts_with( $src, 'data:' ) ) {
					continue;
				}

				$links[] = ExtractedLink::from_image(
					$src,
					null,
					'post_content',
					$pos
				);
			}
		}

		return $links;
	}
}
