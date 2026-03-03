<?php
/**
 * Main Plugin orchestrator class.
 *
 * Acts as a lightweight service container and coordinates
 * initialization of all plugin components.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker;

use YokoLinkChecker\Admin\AdminController;
use YokoLinkChecker\Admin\AjaxHandler;
use YokoLinkChecker\Admin\DashboardPage;
use YokoLinkChecker\Admin\ResultsPage;
use YokoLinkChecker\Checker\HttpClient;
use YokoLinkChecker\Checker\StatusClassifier;
use YokoLinkChecker\Checker\UrlChecker;
use YokoLinkChecker\Extractor\ExtractorRegistry;
use YokoLinkChecker\Extractor\HtmlExtractor;
use YokoLinkChecker\Repository\LinkRepository;
use YokoLinkChecker\Repository\ScanRepository;
use YokoLinkChecker\Repository\UrlRepository;
use YokoLinkChecker\Scanner\BatchProcessor;
use YokoLinkChecker\Scanner\ContentDiscovery;
use YokoLinkChecker\Scanner\ScanOrchestrator;
use YokoLinkChecker\Util\UrlNormalizer;
use YokoLinkChecker\Util\UrlValidator;

/**
 * Plugin container and orchestrator.
 *
 * Provides lazy-loaded access to all plugin services.
 * Services are instantiated on first access and cached.
 *
 * @since 1.0.0
 */
final class Plugin {

	/**
	 * Service container for lazy-loaded instances.
	 *
	 * @var array<string, object>
	 */
	private array $services = array();

	/**
	 * Whether the plugin has been booted.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Boot the plugin.
	 *
	 * Initializes hooks and loads components.
	 * Safe to call multiple times; will only boot once.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		// Check if database needs setup (in case activation hook didn't run).
		$this->maybe_run_activation();

		// Initialize admin components only in admin context.
		if ( is_admin() ) {
			$this->admin_controller()->register();
		}

		// Register cron hooks (available in all contexts for WP-Cron).
		$this->register_cron_hooks();

		/**
		 * Fires after the plugin has fully booted.
		 *
		 * @since 1.0.0
		 * @param Plugin $plugin The plugin instance.
		 */
		do_action( 'yoko_lc_booted', $this );
	}

	/**
	 * Maybe run activation if not yet done.
	 *
	 * This handles cases where the plugin was updated or
	 * the activation hook didn't run properly.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function maybe_run_activation(): void {
		$schema_version = get_option( 'yoko_lc_schema_version', '' );

		if ( YOKO_LC_VERSION !== $schema_version ) {
			require_once YOKO_LC_PLUGIN_DIR . 'src/Activator.php';
			\YokoLinkChecker\Activator::activate();
			update_option( 'yoko_lc_schema_version', YOKO_LC_VERSION );
		}
	}

	/**
	 * Register WP-Cron hooks for background processing.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_cron_hooks(): void {
		add_action( 'yoko_lc_process_scan_batch', array( $this, 'handle_cron_batch' ), 10, 1 );
	}

	/**
	 * Handle a cron-triggered scan batch.
	 *
	 * @since 1.0.0
	 * @param int $scan_id The scan ID to process.
	 * @return void
	 */
	public function handle_cron_batch( int $scan_id ): void {
		$this->scan_orchestrator()->process_batch( $scan_id );
	}

	/**
	 * Get the URL normalizer service.
	 *
	 * @since 1.0.0
	 * @return UrlNormalizer
	 */
	public function url_normalizer(): UrlNormalizer {
		return $this->get_service(
			UrlNormalizer::class,
			fn() => new UrlNormalizer( home_url() )
		);
	}

	/**
	 * Get the URL validator service.
	 *
	 * @since 1.0.0
	 * @return UrlValidator
	 */
	public function url_validator(): UrlValidator {
		return $this->get_service(
			UrlValidator::class,
			fn() => new UrlValidator()
		);
	}

	/**
	 * Get the URL repository.
	 *
	 * @since 1.0.0
	 * @return UrlRepository
	 */
	public function url_repository(): UrlRepository {
		return $this->get_service(
			UrlRepository::class,
			fn() => new UrlRepository( $this->url_normalizer() )
		);
	}

	/**
	 * Get the link repository.
	 *
	 * @since 1.0.0
	 * @return LinkRepository
	 */
	public function link_repository(): LinkRepository {
		return $this->get_service(
			LinkRepository::class,
			fn() => new LinkRepository()
		);
	}

	/**
	 * Get the scan repository.
	 *
	 * @since 1.0.0
	 * @return ScanRepository
	 */
	public function scan_repository(): ScanRepository {
		return $this->get_service(
			ScanRepository::class,
			fn() => new ScanRepository()
		);
	}

	/**
	 * Get the extractor registry.
	 *
	 * @since 1.0.0
	 * @return ExtractorRegistry
	 */
	public function extractor_registry(): ExtractorRegistry {
		return $this->get_service(
			ExtractorRegistry::class,
			function () {
				$registry = new ExtractorRegistry();

				// Register core extractors.
				$registry->register( new HtmlExtractor( $this->url_normalizer() ) );

				/**
				 * Filters the registered extractors.
				 *
				 * @since 1.0.0
				 * @param ExtractorRegistry $registry The extractor registry.
				 */
				do_action( 'yoko_lc_register_extractors', $registry );

				return $registry;
			}
		);
	}

	/**
	 * Get the HTTP client.
	 *
	 * @since 1.0.0
	 * @return HttpClient
	 */
	public function http_client(): HttpClient {
		return $this->get_service(
			HttpClient::class,
			fn() => new HttpClient()
		);
	}

	/**
	 * Get the status classifier.
	 *
	 * @since 1.0.0
	 * @return StatusClassifier
	 */
	public function status_classifier(): StatusClassifier {
		return $this->get_service(
			StatusClassifier::class,
			fn() => new StatusClassifier()
		);
	}

	/**
	 * Get the URL checker service.
	 *
	 * @since 1.0.0
	 * @return UrlChecker
	 */
	public function url_checker(): UrlChecker {
		return $this->get_service(
			UrlChecker::class,
			fn() => new UrlChecker(
				$this->http_client(),
				$this->status_classifier()
			)
		);
	}

	/**
	 * Get the content discovery service.
	 *
	 * @since 1.0.0
	 * @return ContentDiscovery
	 */
	public function content_discovery(): ContentDiscovery {
		return $this->get_service(
			ContentDiscovery::class,
			fn() => new ContentDiscovery()
		);
	}

	/**
	 * Get the batch processor.
	 *
	 * @since 1.0.0
	 * @return BatchProcessor
	 */
	public function batch_processor(): BatchProcessor {
		return $this->get_service(
			BatchProcessor::class,
			fn() => new BatchProcessor(
				$this->content_discovery(),
				$this->extractor_registry(),
				$this->url_repository(),
				$this->link_repository(),
				$this->scan_repository(),
				$this->url_checker()
			)
		);
	}

	/**
	 * Get the scan orchestrator.
	 *
	 * @since 1.0.0
	 * @return ScanOrchestrator
	 */
	public function scan_orchestrator(): ScanOrchestrator {
		return $this->get_service(
			ScanOrchestrator::class,
			fn() => new ScanOrchestrator(
				$this->batch_processor(),
				$this->scan_repository(),
				$this->url_repository(),
				$this->content_discovery()
			)
		);
	}

	/**
	 * Get the dashboard page.
	 *
	 * @since 1.0.0
	 * @return DashboardPage
	 */
	public function dashboard_page(): DashboardPage {
		return $this->get_service(
			DashboardPage::class,
			fn() => new DashboardPage(
				$this->url_repository(),
				$this->scan_repository(),
				$this->scan_orchestrator()
			)
		);
	}

	/**
	 * Get the results page.
	 *
	 * @since 1.0.0
	 * @return ResultsPage
	 */
	public function results_page(): ResultsPage {
		return $this->get_service(
			ResultsPage::class,
			fn() => new ResultsPage( $this->link_repository() )
		);
	}

	/**
	 * Get the admin controller.
	 *
	 * @since 1.0.0
	 * @return AdminController
	 */
	public function admin_controller(): AdminController {
		return $this->get_service(
			AdminController::class,
			fn() => new AdminController(
				$this->dashboard_page(),
				$this->results_page(),
				$this->ajax_handler()
			)
		);
	}

	/**
	 * Get the AJAX handler.
	 *
	 * @since 1.0.0
	 * @return AjaxHandler
	 */
	public function ajax_handler(): AjaxHandler {
		return $this->get_service(
			AjaxHandler::class,
			fn() => new AjaxHandler(
				$this->scan_orchestrator(),
				$this->batch_processor(),
				$this->url_repository(),
				$this->link_repository()
			)
		);
	}

	/**
	 * Get or create a service instance.
	 *
	 * @since 1.0.0
	 * @template T
	 * @param class-string<T> $key     Service identifier (typically class name).
	 * @param callable():T    $factory Factory function to create the service.
	 * @return T
	 */
	private function get_service( string $key, callable $factory ): object { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
		if ( ! isset( $this->services[ $key ] ) ) {
			$this->services[ $key ] = $factory();
		}

		return $this->services[ $key ];
	}

	/**
	 * Get plugin version.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function version(): string {
		return YOKO_LC_VERSION;
	}

	/**
	 * Get plugin directory path.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function path(): string {
		return YOKO_LC_PLUGIN_DIR;
	}

	/**
	 * Get plugin URL.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function url(): string {
		return YOKO_LC_PLUGIN_URL;
	}
}
