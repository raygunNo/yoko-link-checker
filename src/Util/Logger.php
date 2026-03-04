<?php
/**
 * Simple debug logger utility.
 *
 * Provides structured logging for debugging and diagnostics.
 * Logs are written to the WordPress debug.log when WP_DEBUG_LOG is enabled.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Util;

/**
 * Debug logger service.
 *
 * @since 1.0.0
 */
final class Logger {

	/**
	 * Log levels.
	 */
	public const DEBUG   = 'DEBUG';
	public const INFO    = 'INFO';
	public const WARNING = 'WARNING';
	public const ERROR   = 'ERROR';

	/**
	 * Log a debug message.
	 *
	 * @since 1.0.0
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context data.
	 * @return void
	 */
	public static function debug( string $message, array $context = array() ): void {
		self::log( self::DEBUG, $message, $context );
	}

	/**
	 * Log an info message.
	 *
	 * @since 1.0.0
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context data.
	 * @return void
	 */
	public static function info( string $message, array $context = array() ): void {
		self::log( self::INFO, $message, $context );
	}

	/**
	 * Log a warning message.
	 *
	 * @since 1.0.0
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context data.
	 * @return void
	 */
	public static function warning( string $message, array $context = array() ): void {
		self::log( self::WARNING, $message, $context );
	}

	/**
	 * Log an error message.
	 *
	 * @since 1.0.0
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context data.
	 * @return void
	 */
	public static function error( string $message, array $context = array() ): void {
		self::log( self::ERROR, $message, $context );
	}

	/**
	 * Log a message with specified level.
	 *
	 * @since 1.0.0
	 * @param string               $level   Log level.
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context data.
	 * @return void
	 */
	private static function log( string $level, string $message, array $context = array() ): void {
		// Only log if plugin debug mode is explicitly enabled.
		if ( ! defined( 'YOKO_LC_DEBUG' ) || ! YOKO_LC_DEBUG ) {
			return;
		}

		// Build log entry.
		$timestamp = gmdate( 'Y-m-d H:i:s' );
		$prefix    = "[YLC] [{$level}]";

		$log_message = "{$timestamp} {$prefix} {$message}";

		// Add context if present.
		if ( ! empty( $context ) ) {
			$log_message .= ' | Context: ' . wp_json_encode( $context, JSON_UNESCAPED_SLASHES );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $log_message );
	}

	/**
	 * Log an exception.
	 *
	 * @since 1.0.0
	 * @param \Throwable           $exception The exception to log.
	 * @param array<string, mixed> $context   Additional context data.
	 * @return void
	 */
	public static function exception( \Throwable $exception, array $context = array() ): void {
		$context['exception_class'] = get_class( $exception );
		$context['file']            = $exception->getFile();
		$context['line']            = $exception->getLine();
		$context['trace']           = $exception->getTraceAsString();

		self::error( $exception->getMessage(), $context );
	}
}
