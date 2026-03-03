<?php
/**
 * URL Validator utility.
 *
 * Validates URLs before processing. Checks for valid
 * structure, supported schemes, and basic sanity.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Util;

/**
 * URL validation service.
 *
 * @since 1.0.0
 */
final class UrlValidator {

	/**
	 * Supported URL schemes for checking.
	 *
	 * @var array<string>
	 */
	private const CHECKABLE_SCHEMES = array( 'http', 'https' );

	/**
	 * Maximum URL length to process.
	 *
	 * @var int
	 */
	private const MAX_URL_LENGTH = 2048;

	/**
	 * Validate a URL for processing.
	 *
	 * @since 1.0.0
	 * @param string $url URL to validate.
	 * @return bool Whether the URL is valid for checking.
	 */
	public function is_valid( string $url ): bool {
		// Check length.
		if ( strlen( $url ) > self::MAX_URL_LENGTH ) {
			return false;
		}

		// Must not be empty.
		if ( '' === trim( $url ) ) {
			return false;
		}

		// Parse URL.
		$parts = wp_parse_url( $url );

		if ( false === $parts ) {
			return false;
		}

		// Must have scheme and host.
		if ( ! isset( $parts['scheme'], $parts['host'] ) ) {
			return false;
		}

		// Scheme must be http or https.
		if ( ! in_array( strtolower( $parts['scheme'] ), self::CHECKABLE_SCHEMES, true ) ) {
			return false;
		}

		// Host must be valid.
		if ( ! $this->is_valid_host( $parts['host'] ) ) {
			return false;
		}

		// Port must be valid if present.
		if ( isset( $parts['port'] ) && ! $this->is_valid_port( (int) $parts['port'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if a host is valid.
	 *
	 * @since 1.0.0
	 * @param string $host Host to validate.
	 * @return bool Whether the host is valid.
	 */
	private function is_valid_host( string $host ): bool {
		// Cannot be empty.
		if ( '' === $host ) {
			return false;
		}

		// Check for IP address.
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return true;
		}

		// Check for valid domain name pattern.
		// Allow IDN domains (punycode).
		$pattern = '/^([a-z0-9]([a-z0-9\-]*[a-z0-9])?\.)*[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/i';

		return (bool) preg_match( $pattern, $host );
	}

	/**
	 * Check if a port is valid.
	 *
	 * @since 1.0.0
	 * @param int $port Port number.
	 * @return bool Whether the port is valid.
	 */
	private function is_valid_port( int $port ): bool {
		return $port > 0 && $port <= 65535;
	}

	/**
	 * Check if a URL is checkable (can make HTTP request).
	 *
	 * @since 1.0.0
	 * @param string $url URL to check.
	 * @return bool Whether the URL can be checked via HTTP.
	 */
	public function is_checkable( string $url ): bool {
		return $this->is_valid( $url );
	}

	/**
	 * Get the reason a URL is invalid.
	 *
	 * @since 1.0.0
	 * @param string $url URL to check.
	 * @return string|null Error message or null if valid.
	 */
	public function get_invalid_reason( string $url ): ?string {
		if ( strlen( $url ) > self::MAX_URL_LENGTH ) {
			return __( 'URL exceeds maximum length', 'yoko-link-checker' );
		}

		if ( '' === trim( $url ) ) {
			return __( 'URL is empty', 'yoko-link-checker' );
		}

		$parts = wp_parse_url( $url );

		if ( false === $parts ) {
			return __( 'URL is malformed', 'yoko-link-checker' );
		}

		if ( ! isset( $parts['scheme'] ) ) {
			return __( 'URL has no scheme', 'yoko-link-checker' );
		}

		if ( ! in_array( strtolower( $parts['scheme'] ), self::CHECKABLE_SCHEMES, true ) ) {
			return sprintf(
				/* translators: %s: URL scheme */
				__( 'URL scheme "%s" is not supported', 'yoko-link-checker' ),
				$parts['scheme']
			);
		}

		if ( ! isset( $parts['host'] ) ) {
			return __( 'URL has no host', 'yoko-link-checker' );
		}

		if ( ! $this->is_valid_host( $parts['host'] ) ) {
			return __( 'URL host is invalid', 'yoko-link-checker' );
		}

		if ( isset( $parts['port'] ) && ! $this->is_valid_port( (int) $parts['port'] ) ) {
			return __( 'URL port is invalid', 'yoko-link-checker' );
		}

		return null;
	}

	/**
	 * Check if URL matches an exclusion pattern.
	 *
	 * @since 1.0.0
	 * @param string        $url      URL to check.
	 * @param array<string> $patterns Exclusion patterns (supports wildcards).
	 * @return bool Whether URL matches any pattern.
	 */
	public function matches_exclusion( string $url, array $patterns ): bool {
		foreach ( $patterns as $pattern ) {
			// Convert wildcard pattern to regex.
			$regex = '/^' . str_replace(
				array( '\*', '\?' ),
				array( '.*', '.' ),
				preg_quote( $pattern, '/' )
			) . '$/i';

			if ( preg_match( $regex, $url ) ) {
				return true;
			}
		}

		return false;
	}
}
