<?php
/**
 * URL Checker.
 *
 * Orchestrates URL checking using HTTP client and status classifier.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Checker;

use YokoLinkChecker\Model\Url;
use YokoLinkChecker\Util\Logger;

/**
 * URL checking service.
 *
 * @since 1.0.0
 */
final class UrlChecker {

	/**
	 * HTTP client instance.
	 *
	 * @var HttpClient
	 */
	private HttpClient $http_client;

	/**
	 * Status classifier instance.
	 *
	 * @var StatusClassifier
	 */
	private StatusClassifier $classifier;

	/**
	 * HTTP methods that don't support HEAD or are problematic.
	 * These sites block HEAD requests or are very slow, so go straight to GET.
	 *
	 * @var array<string>
	 */
	private const HEAD_UNSUPPORTED_PATTERNS = array(
		'linkedin.com',
		'facebook.com',
		'instagram.com',
		'twitter.com',
		'x.com',
		'amazon.com',
		'reddit.com',
		'pinterest.com',
		'tiktok.com',
		'cloudflare.com',
		'medium.com',
		'youtu.be',
		'youtube.com',
		'vimeo.com',
		'bit.ly',
		'goo.gl',
		't.co',
	);

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param HttpClient       $http_client HTTP client.
	 * @param StatusClassifier $classifier  Status classifier.
	 */
	public function __construct( HttpClient $http_client, StatusClassifier $classifier ) {
		$this->http_client = $http_client;
		$this->classifier  = $classifier;
	}

	/**
	 * Check a URL.
	 *
	 * @since 1.0.0
	 * @param string $url URL to check.
	 * @return CheckResult
	 */
	public function check( string $url ): CheckResult {
		Logger::debug( 'Checking URL', array( 'url' => $url ) );

		// Determine if we should skip HEAD and go straight to GET.
		$use_head = $this->should_use_head( $url );

		if ( $use_head ) {
			$result = $this->check_with_head( $url );

			// If HEAD was inconclusive, try GET.
			if ( $this->should_retry_with_get( $result ) ) {
				Logger::debug( 'HEAD inconclusive, retrying with GET', array( 'url' => $url ) );
				$result = $this->check_with_get( $url );
			}
		} else {
			$result = $this->check_with_get( $url );
		}

		Logger::debug(
			'Check complete',
			array(
				'url'       => $url,
				'status'    => $result->status,
				'http_code' => $result->http_code,
			)
		);

		return $result;
	}

	/**
	 * Check URL using HEAD request.
	 *
	 * @since 1.0.0
	 * @param string $url URL to check.
	 * @return CheckResult
	 */
	private function check_with_head( string $url ): CheckResult {
		$result = $this->http_client->head( $url );

		return $this->process_response( $url, $result['response'], $result['time'] );
	}

	/**
	 * Check URL using GET request.
	 *
	 * @since 1.0.0
	 * @param string $url URL to check.
	 * @return CheckResult
	 */
	private function check_with_get( string $url ): CheckResult {
		$result = $this->http_client->get( $url );

		return $this->process_response( $url, $result['response'], $result['time'] );
	}

	/**
	 * Process HTTP response into CheckResult.
	 *
	 * @since 1.0.0
	 * @param string           $url           Checked URL.
	 * @param array|\WP_Error  $response      HTTP response.
	 * @param int              $response_time Response time in ms.
	 * @return CheckResult
	 */
	private function process_response( string $url, $response, int $response_time ): CheckResult {
		// Handle errors.
		if ( is_wp_error( $response ) ) {
			$error_type    = $this->http_client->get_error_type( $response );
			$error_message = $response->get_error_message();

			$status = $this->classifier->classify(
				null,
				$error_type,
				$error_message,
				$url
			);

			return CheckResult::error(
				$url,
				$status,
				$error_type,
				$error_message,
				null,
				$response_time
			);
		}

		// Process successful response.
		$http_code      = $this->http_client->get_response_code( $response );
		$final_url      = $this->http_client->get_final_url( $response, $url );
		$redirect_count = $this->http_client->count_redirects( $response );
		$headers        = $this->http_client->get_headers( $response );

		$status = $this->classifier->classify(
			$http_code,
			null,
			null,
			$url,
			$final_url
		);

		// Check if URL changed to mark as redirect.
		if ( Url::STATUS_VALID === $status && $final_url !== $url ) {
			$status = Url::STATUS_REDIRECT;
		}

		return new CheckResult(
			$url,
			$status,
			$http_code,
			$final_url !== $url ? $final_url : null,
			$redirect_count,
			$response_time,
			null,
			null,
			$headers
		);
	}

	/**
	 * Determine if HEAD request should be used.
	 *
	 * @since 1.0.0
	 * @param string $url URL to check.
	 * @return bool
	 */
	private function should_use_head( string $url ): bool {
		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! $host ) {
			return true;
		}

		$host = strtolower( $host );

		// Check against known problematic sites.
		foreach ( self::HEAD_UNSUPPORTED_PATTERNS as $pattern ) {
			if ( str_contains( $host, $pattern ) ) {
				return false;
			}
		}

		/**
		 * Filters whether to use HEAD request for a URL.
		 *
		 * @since 1.0.0
		 * @param bool   $use_head Whether to use HEAD.
		 * @param string $url      The URL being checked.
		 */
		return apply_filters( 'ylc_use_head_request', true, $url );
	}

	/**
	 * Determine if we should retry with GET after HEAD.
	 *
	 * @since 1.0.0
	 * @param CheckResult $result HEAD result.
	 * @return bool
	 */
	private function should_retry_with_get( CheckResult $result ): bool {
		// Retry on 405 Method Not Allowed.
		if ( 405 === $result->http_code ) {
			return true;
		}

		// Retry on blocked/error that might be HEAD-specific.
		if ( Url::STATUS_BLOCKED === $result->status ) {
			return true;
		}

		// Retry on certain error types.
		if ( in_array( $result->error_type, array( 'http_request_failed' ), true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check multiple URLs.
	 *
	 * @since 1.0.0
	 * @param array<string> $urls URLs to check.
	 * @return array<string, CheckResult> Results keyed by URL.
	 */
	public function check_batch( array $urls ): array {
		$results = array();

		foreach ( $urls as $url ) {
			$results[ $url ] = $this->check( $url );

			// Small delay between requests to avoid rate limiting.
			usleep( 100000 ); // 100ms.
		}

		return $results;
	}

	/**
	 * Get the HTTP client.
	 *
	 * @since 1.0.0
	 * @return HttpClient
	 */
	public function get_http_client(): HttpClient {
		return $this->http_client;
	}

	/**
	 * Get the status classifier.
	 *
	 * @since 1.0.0
	 * @return StatusClassifier
	 */
	public function get_classifier(): StatusClassifier {
		return $this->classifier;
	}
}
