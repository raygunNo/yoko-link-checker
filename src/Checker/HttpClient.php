<?php
/**
 * HTTP Client wrapper.
 *
 * Wraps WordPress HTTP API with additional features for link checking.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Checker;

use WP_Error;

/**
 * HTTP client for URL checking.
 *
 * @since 1.0.0
 */
final class HttpClient {

	/**
	 * Default request timeout in seconds.
	 *
	 * @var int
	 */
	private int $timeout;

	/**
	 * Maximum redirects to follow.
	 *
	 * @var int
	 */
	private int $max_redirects;

	/**
	 * User agent string.
	 *
	 * @var string
	 */
	private string $user_agent;

	/**
	 * Whether to verify SSL certificates.
	 *
	 * @var bool
	 */
	private bool $verify_ssl;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param int    $timeout       Request timeout.
	 * @param int    $max_redirects Maximum redirects.
	 * @param string $user_agent    User agent string.
	 * @param bool   $verify_ssl    Whether to verify SSL.
	 */
	/**
	 * Connection timeout in seconds.
	 *
	 * @var int
	 */
	private int $connect_timeout;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param int    $timeout         Request timeout in seconds.
	 * @param int    $max_redirects   Maximum number of redirects to follow.
	 * @param string $user_agent      User agent string.
	 * @param bool   $verify_ssl      Whether to verify SSL certificates.
	 * @param int    $connect_timeout Connection timeout in seconds.
	 */
	public function __construct(
		int $timeout = 8,
		int $max_redirects = 3,
		string $user_agent = '',
		bool $verify_ssl = true,
		int $connect_timeout = 5
	) {
		$this->timeout         = $timeout;
		$this->connect_timeout = $connect_timeout;
		$this->max_redirects   = $max_redirects;
		$this->user_agent      = $user_agent ? $user_agent : $this->get_default_user_agent();
		$this->verify_ssl      = $verify_ssl;
	}

	/**
	 * Perform a HEAD request.
	 *
	 * @since 1.0.0
	 * @param string $url URL to check.
	 * @return array{response: array|WP_Error, time: int}
	 */
	public function head( string $url ): array {
		$start = microtime( true );

		$response = wp_remote_head(
			$url,
			$this->get_request_args()
		);

		$time = (int) round( ( microtime( true ) - $start ) * 1000 );

		return array(
			'response' => $response,
			'time'     => $time,
		);
	}

	/**
	 * Perform a GET request.
	 *
	 * @since 1.0.0
	 * @param string $url URL to check.
	 * @return array{response: array|WP_Error, time: int}
	 */
	public function get( string $url ): array {
		$start = microtime( true );

		$response = wp_remote_get(
			$url,
			$this->get_request_args()
		);

		$time = (int) round( ( microtime( true ) - $start ) * 1000 );

		return array(
			'response' => $response,
			'time'     => $time,
		);
	}

	/**
	 * Get request arguments.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	private function get_request_args(): array {
		$args = array(
			'timeout'         => $this->timeout,
			'connect_timeout' => $this->connect_timeout,
			'redirection'     => $this->max_redirects,
			'user-agent'      => $this->user_agent,
			'sslverify'       => $this->verify_ssl,
			'blocking'        => true,
			'headers'         => array(
				'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
				'Accept-Language' => 'en-US,en;q=0.5',
				'Connection'      => 'close',
			),
		);

		/**
		 * Filters HTTP request arguments.
		 *
		 * @since 1.0.0
		 * @param array $args Request arguments.
		 */
		return apply_filters( 'yoko_lc_http_request_args', $args );
	}

	/**
	 * Get default user agent.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_default_user_agent(): string {
		$version = defined( 'YOKO_LC_VERSION' ) ? YOKO_LC_VERSION : '1.0.0';
		return "YokoLinkChecker/{$version} (WordPress Link Checker; +https://example.com)";
	}

	/**
	 * Check if response is a WP_Error.
	 *
	 * @since 1.0.0
	 * @param mixed $response Response to check.
	 * @return bool
	 */
	public function is_error( $response ): bool {
		return is_wp_error( $response );
	}

	/**
	 * Get response code.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed>|WP_Error $response Response.
	 * @return int|null
	 */
	public function get_response_code( $response ): ?int {
		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );

		return $code ? (int) $code : null;
	}

	/**
	 * Get response headers.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed>|WP_Error $response Response.
	 * @return array<string, string>
	 */
	public function get_headers( $response ): array {
		if ( is_wp_error( $response ) ) {
			return array();
		}

		$headers = wp_remote_retrieve_headers( $response );

		if ( $headers instanceof \Requests_Utility_CaseInsensitiveDictionary || $headers instanceof \WpOrg\Requests\Utility\CaseInsensitiveDictionary ) {
			return $headers->getAll();
		}

		return is_array( $headers ) ? $headers : array();
	}

	/**
	 * Get final URL after redirects.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed>|WP_Error $response    Response.
	 * @param string                        $original_url Original URL.
	 * @return string
	 */
	public function get_final_url( $response, string $original_url ): string {
		if ( is_wp_error( $response ) ) {
			return $original_url;
		}

		// Check for redirect history.
		$http_response = $response['http_response'] ?? null;

		if ( $http_response && method_exists( $http_response, 'get_response_object' ) ) {
			$response_obj = $http_response->get_response_object();
			if ( $response_obj && isset( $response_obj->url ) ) {
				return $response_obj->url;
			}
		}

		// Look for Location header (shouldn't be present if followed).
		$headers = $this->get_headers( $response );

		// If we got here with a redirect code, use Location.
		$code = $this->get_response_code( $response );
		if ( $code >= 300 && $code < 400 && ! empty( $headers['location'] ) ) {
			return $headers['location'];
		}

		return $original_url;
	}

	/**
	 * Count redirects from response.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed>|WP_Error $response Response.
	 * @return int
	 */
	public function count_redirects( $response ): int {
		if ( is_wp_error( $response ) ) {
			return 0;
		}

		$http_response = $response['http_response'] ?? null;

		if ( $http_response && method_exists( $http_response, 'get_response_object' ) ) {
			$response_obj = $http_response->get_response_object();
			if ( $response_obj && isset( $response_obj->history ) ) {
				return count( $response_obj->history );
			}
		}

		return 0;
	}

	/**
	 * Extract error type from WP_Error.
	 *
	 * @since 1.0.0
	 * @param WP_Error $error Error object.
	 * @return string
	 */
	public function get_error_type( WP_Error $error ): string {
		$code    = $error->get_error_code();
		$message = strtolower( $error->get_error_message() );

		// Map common error patterns.
		if ( 'http_request_failed' === $code ) {
			if ( str_contains( $message, 'ssl' ) || str_contains( $message, 'certificate' ) ) {
				return 'ssl_error';
			}
			if ( str_contains( $message, 'resolve' ) || str_contains( $message, 'dns' ) ) {
				return 'dns_error';
			}
			if ( str_contains( $message, 'timed out' ) || str_contains( $message, 'timeout' ) ) {
				return 'timeout';
			}
			if ( str_contains( $message, 'connection' ) || str_contains( $message, 'refused' ) ) {
				return 'connection_error';
			}
		}

		return $code ? $code : 'unknown_error';
	}
}
