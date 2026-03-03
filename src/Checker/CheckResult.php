<?php
/**
 * Check Result value object.
 *
 * Represents the result of checking a URL.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Checker;

/**
 * Check result value object.
 *
 * @since 1.0.0
 */
final class CheckResult {

	/**
	 * The checked URL.
	 *
	 * @var string
	 */
	public string $url;

	/**
	 * Classified status.
	 *
	 * @var string
	 */
	public string $status;

	/**
	 * HTTP status code (if applicable).
	 *
	 * @var int|null
	 */
	public ?int $http_code;

	/**
	 * Final URL after redirects.
	 *
	 * @var string|null
	 */
	public ?string $final_url;

	/**
	 * Number of redirects followed.
	 *
	 * @var int
	 */
	public int $redirect_count;

	/**
	 * Response time in milliseconds.
	 *
	 * @var int|null
	 */
	public ?int $response_time;

	/**
	 * Error type identifier.
	 *
	 * @var string|null
	 */
	public ?string $error_type;

	/**
	 * Error message.
	 *
	 * @var string|null
	 */
	public ?string $error_message;

	/**
	 * Response headers.
	 *
	 * @var array<string, string>
	 */
	public array $headers;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param string                $url            The checked URL.
	 * @param string                $status         Classified status.
	 * @param int|null              $http_code      HTTP status code.
	 * @param string|null           $final_url      Final URL after redirects.
	 * @param int                   $redirect_count Number of redirects.
	 * @param int|null              $response_time  Response time in ms.
	 * @param string|null           $error_type     Error type identifier.
	 * @param string|null           $error_message  Error message.
	 * @param array<string, string> $headers Response headers.
	 */
	public function __construct(
		string $url,
		string $status,
		?int $http_code = null,
		?string $final_url = null,
		int $redirect_count = 0,
		?int $response_time = null,
		?string $error_type = null,
		?string $error_message = null,
		array $headers = array()
	) {
		$this->url            = $url;
		$this->status         = $status;
		$this->http_code      = $http_code;
		$this->final_url      = $final_url;
		$this->redirect_count = $redirect_count;
		$this->response_time  = $response_time;
		$this->error_type     = $error_type;
		$this->error_message  = $error_message;
		$this->headers        = $headers;
	}

	/**
	 * Create a success result.
	 *
	 * @since 1.0.0
	 * @param string                $url           Checked URL.
	 * @param int                   $http_code     HTTP code.
	 * @param string|null           $final_url     Final URL.
	 * @param int                   $redirect_count Redirects.
	 * @param int                   $response_time Response time.
	 * @param array<string, string> $headers Headers.
	 * @return self
	 */
	public static function success(
		string $url,
		int $http_code,
		?string $final_url = null,
		int $redirect_count = 0,
		int $response_time = 0,
		array $headers = array()
	): self {
		$status = 'valid';

		// Mark as redirect if URL changed.
		if ( $final_url && $final_url !== $url ) {
			$status = 'redirect';
		}

		return new self(
			$url,
			$status,
			$http_code,
			$final_url,
			$redirect_count,
			$response_time,
			null,
			null,
			$headers
		);
	}

	/**
	 * Create an error result.
	 *
	 * @since 1.0.0
	 * @param string   $url           Checked URL.
	 * @param string   $status        Status classification.
	 * @param string   $error_type    Error type.
	 * @param string   $error_message Error message.
	 * @param int|null $http_code     HTTP code if applicable.
	 * @param int|null $response_time Response time.
	 * @return self
	 */
	public static function error(
		string $url,
		string $status,
		string $error_type,
		string $error_message,
		?int $http_code = null,
		?int $response_time = null
	): self {
		return new self(
			$url,
			$status,
			$http_code,
			null,
			0,
			$response_time,
			$error_type,
			$error_message
		);
	}

	/**
	 * Check if result is valid (no problems).
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_valid(): bool {
		return 'valid' === $this->status;
	}

	/**
	 * Check if result is a redirect.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_redirect(): bool {
		return 'redirect' === $this->status;
	}

	/**
	 * Check if result indicates a problem.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function has_problem(): bool {
		return in_array(
			$this->status,
			array( 'broken', 'warning', 'blocked', 'timeout', 'error' ),
			true
		);
	}

	/**
	 * Get a summary string for the result.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_summary(): string {
		if ( $this->error_message ) {
			return $this->error_message;
		}

		if ( $this->http_code ) {
			return sprintf( 'HTTP %d', $this->http_code );
		}

		return $this->status;
	}
}
