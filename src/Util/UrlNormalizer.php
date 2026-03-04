<?php
/**
 * URL Normalizer utility.
 *
 * Normalizes URLs for consistent storage, deduplication,
 * and comparison. Handles relative URLs, fragments,
 * query string ordering, and encoding.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Util;

/**
 * URL normalization service.
 *
 * @since 1.0.0
 */
final class UrlNormalizer {

	/**
	 * Site home URL for resolving relative URLs.
	 *
	 * @var string
	 */
	private string $home_url;

	/**
	 * Parsed components of home URL.
	 *
	 * @var array<string, mixed>
	 */
	private array $home_parts;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param string $home_url The site's home URL.
	 */
	public function __construct( string $home_url ) {
		$this->home_url   = untrailingslashit( $home_url );
		$parsed           = wp_parse_url( $this->home_url );
		$this->home_parts = $parsed ? $parsed : array();
	}

	/**
	 * Normalize a URL.
	 *
	 * @since 1.0.0
	 * @param string      $url     The URL to normalize.
	 * @param string|null $context Optional base URL for resolving relative URLs.
	 * @return string|null Normalized URL, or null if invalid.
	 */
	public function normalize( string $url, ?string $context = null ): ?string {
		$url = trim( $url );

		if ( '' === $url ) {
			return null;
		}

		// Handle special cases that should be skipped.
		if ( $this->is_skippable( $url ) ) {
			return null;
		}

		// Resolve relative URLs.
		$url = $this->resolve_relative( $url, $context );

		if ( null === $url ) {
			return null;
		}

		// Parse the URL.
		$parts = wp_parse_url( $url );

		if ( false === $parts || ! isset( $parts['host'] ) ) {
			return null;
		}

		// Rebuild normalized URL.
		return $this->rebuild_url( $parts );
	}

	/**
	 * Check if a URL should be skipped entirely.
	 *
	 * @since 1.0.0
	 * @param string $url The URL to check.
	 * @return bool Whether the URL should be skipped.
	 */
	private function is_skippable( string $url ): bool {
		// Skip fragment-only URLs.
		if ( str_starts_with( $url, '#' ) ) {
			return true;
		}

		// Skip javascript: URLs.
		if ( str_starts_with( strtolower( $url ), 'javascript:' ) ) {
			return true;
		}

		// Skip mailto: URLs.
		if ( str_starts_with( strtolower( $url ), 'mailto:' ) ) {
			return true;
		}

		// Skip tel: URLs.
		if ( str_starts_with( strtolower( $url ), 'tel:' ) ) {
			return true;
		}

		// Skip data: URLs.
		if ( str_starts_with( strtolower( $url ), 'data:' ) ) {
			return true;
		}

		// Skip empty or invalid starts.
		if ( str_starts_with( $url, '{' ) || str_starts_with( $url, '[' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Resolve a relative URL to absolute.
	 *
	 * @since 1.0.0
	 * @param string      $url     The URL to resolve.
	 * @param string|null $context Base URL context.
	 * @return string|null Absolute URL or null if unable to resolve.
	 */
	private function resolve_relative( string $url, ?string $context = null ): ?string {
		// If URL has a scheme, it's already absolute.
		if ( preg_match( '#^[a-z][a-z0-9+.\-]*://#i', $url ) ) {
			return $url;
		}

		// Handle protocol-relative URLs (//example.com/path).
		if ( str_starts_with( $url, '//' ) ) {
			$scheme = $this->home_parts['scheme'] ?? 'https';
			return $scheme . ':' . $url;
		}

		// Determine base URL for resolution.
		$base = $context ?? $this->home_url;

		// Handle root-relative URLs (/path/to/page).
		if ( str_starts_with( $url, '/' ) ) {
			$base_parts = wp_parse_url( $base );
			if ( ! $base_parts || ! isset( $base_parts['host'] ) ) {
				$base_parts = $this->home_parts;
			}

			$scheme = $base_parts['scheme'] ?? 'https';
			$host   = $base_parts['host'] ?? '';
			$port   = isset( $base_parts['port'] ) ? ':' . $base_parts['port'] : '';

			return $scheme . '://' . $host . $port . $url;
		}

		// Handle truly relative URLs (page.html, ../page.html).
		$base_parts = wp_parse_url( $base );
		if ( ! $base_parts || ! isset( $base_parts['host'] ) ) {
			return null;
		}

		$scheme    = $base_parts['scheme'] ?? 'https';
		$host      = $base_parts['host'];
		$port      = isset( $base_parts['port'] ) ? ':' . $base_parts['port'] : '';
		$base_path = $base_parts['path'] ?? '/';

		// Remove filename from base path to get directory.
		$dir_path = substr( $base_path, 0, (int) strrpos( $base_path, '/' ) + 1 );

		// Combine directory with relative URL.
		$new_path = $dir_path . $url;

		// Resolve ../ and ./ segments.
		$new_path = $this->resolve_path( $new_path );

		return $scheme . '://' . $host . $port . $new_path;
	}

	/**
	 * Resolve path segments (../ and ./).
	 *
	 * @since 1.0.0
	 * @param string $path Path to resolve.
	 * @return string Resolved path.
	 */
	private function resolve_path( string $path ): string {
		$segments = explode( '/', $path );
		$result   = array();

		foreach ( $segments as $segment ) {
			if ( '.' === $segment ) {
				continue;
			}

			if ( '..' === $segment ) {
				array_pop( $result );
			} else {
				$result[] = $segment;
			}
		}

		$resolved = implode( '/', $result );

		// Ensure leading slash.
		if ( ! str_starts_with( $resolved, '/' ) ) {
			$resolved = '/' . $resolved;
		}

		return $resolved;
	}

	/**
	 * Rebuild URL from parsed parts with normalization.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $parts Parsed URL parts.
	 * @return string Normalized URL.
	 */
	private function rebuild_url( array $parts ): string {
		// Normalize scheme to lowercase.
		$scheme = strtolower( $parts['scheme'] ?? 'https' );

		// Normalize host to lowercase.
		$host = strtolower( $parts['host'] ?? '' );

		// Handle port (remove default ports).
		$port = '';
		if ( isset( $parts['port'] ) ) {
			$port_num = (int) $parts['port'];
			if (
				( 'http' === $scheme && 80 !== $port_num ) ||
				( 'https' === $scheme && 443 !== $port_num )
			) {
				$port = ':' . $port_num;
			}
		}

		// Normalize path.
		$path = $parts['path'] ?? '/';
		$path = $this->normalize_path( $path );

		// Normalize query string (sort parameters).
		$query = '';
		if ( ! empty( $parts['query'] ) ) {
			$query = '?' . $this->normalize_query( $parts['query'] );
		}

		// Strip fragment (not relevant for HTTP requests).
		// Fragments are intentionally excluded from normalized URL.

		return $scheme . '://' . $host . $port . $path . $query;
	}

	/**
	 * Normalize URL path component.
	 *
	 * @since 1.0.0
	 * @param string $path Path to normalize.
	 * @return string Normalized path.
	 */
	private function normalize_path( string $path ): string {
		// Ensure leading slash.
		if ( ! str_starts_with( $path, '/' ) ) {
			$path = '/' . $path;
		}

		// Decode safe characters that don't need encoding.
		$path = rawurldecode( $path );

		// Re-encode path properly.
		$segments = explode( '/', $path );
		$segments = array_map( 'rawurlencode', $segments );
		$path     = implode( '/', $segments );

		// Collapse multiple slashes.
		$path = preg_replace( '#/+#', '/', $path );

		// Remove trailing slash for files (keep for directories).
		// This is configurable; for now we preserve trailing slashes.

		return $path ? $path : '/';
	}

	/**
	 * Normalize query string.
	 *
	 * Sorts parameters alphabetically for consistent hashing.
	 *
	 * @since 1.0.0
	 * @param string $query Query string (without leading ?).
	 * @return string Normalized query string.
	 */
	private function normalize_query( string $query ): string {
		parse_str( $query, $params );

		// Remove empty parameters.
		$params = array_filter(
			$params,
			fn( $value ) => '' !== $value && null !== $value
		);

		// Sort alphabetically.
		ksort( $params );

		return http_build_query( $params, '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * Generate a hash for a normalized URL.
	 *
	 * @since 1.0.0
	 * @param string $normalized_url The normalized URL.
	 * @return string SHA-256 hash.
	 */
	public function hash( string $normalized_url ): string {
		return hash( 'sha256', $normalized_url );
	}

	/**
	 * Check if a URL is internal to the site.
	 *
	 * @since 1.0.0
	 * @param string $url The URL to check.
	 * @return bool Whether the URL is internal.
	 */
	public function is_internal( string $url ): bool {
		$url_parts = wp_parse_url( $url );

		if ( ! $url_parts || ! isset( $url_parts['host'] ) ) {
			return false;
		}

		$url_host  = strtolower( $url_parts['host'] );
		$home_host = strtolower( $this->home_parts['host'] ?? '' );

		// Remove www. for comparison.
		$url_host  = preg_replace( '/^www\./', '', $url_host );
		$home_host = preg_replace( '/^www\./', '', $home_host );

		return $url_host === $home_host;
	}
}
