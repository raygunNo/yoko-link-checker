<?php
/**
 * Results Page class.
 *
 * Handles the broken links results list display.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Admin;

use YokoLinkChecker\Repository\LinkRepository;
use YokoLinkChecker\Model\Url;

/**
 * Results page class.
 *
 * @since 1.0.0
 */
class ResultsPage {

	/**
	 * Link repository instance.
	 *
	 * @var LinkRepository
	 */
	private LinkRepository $link_repository;

	/**
	 * List table instance.
	 *
	 * @var LinksListTable|null
	 */
	private ?LinksListTable $list_table = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param LinkRepository $link_repository Link repository.
	 */
	public function __construct( LinkRepository $link_repository ) {
		$this->link_repository = $link_repository;
	}

	/**
	 * Render the results page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render(): void {
		// Handle actions.
		$this->handle_actions();

		// Get filter parameters.
		$status_filter = $this->get_status_filter();
		$filters       = $this->get_filters();

		// Create list table.
		$this->list_table = new LinksListTable( $this->link_repository );
		$this->list_table->set_filter( $status_filter );
		$this->list_table->prepare_items();

		include YLC_PLUGIN_DIR . 'templates/admin/results.php';
	}

	/**
	 * Handle page actions.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function handle_actions(): void {
		// Handle bulk actions from list table.
		if ( isset( $_POST['_wpnonce'] ) && isset( $_POST['action'] ) ) {
			// Bulk actions are handled by the list table.
			return;
		}

		// Handle single actions.
		if ( ! isset( $_GET['action'] ) || ! isset( $_GET['link_id'] ) ) {
			return;
		}

		$action  = sanitize_key( $_GET['action'] );
		$link_id = absint( $_GET['link_id'] );
		$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, "ylc_action_{$link_id}" ) ) {
			wp_die( esc_html__( 'Security check failed.', 'yoko-link-checker' ) );
		}

		if ( ! current_user_can( 'ylc_manage_scans' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'yoko-link-checker' ) );
		}

		switch ( $action ) {
			case 'ignore':
				$this->ignore_link( $link_id );
				break;

			case 'unignore':
				$this->unignore_link( $link_id );
				break;
		}

		// Redirect to remove action from URL.
		$redirect_url = remove_query_arg( [ 'action', 'link_id', '_wpnonce' ] );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Get current status filter.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_status_filter(): string {
		$valid_statuses = [
			'all',
			Url::STATUS_BROKEN,
			Url::STATUS_WARNING,
			Url::STATUS_REDIRECT,
			Url::STATUS_BLOCKED,
			Url::STATUS_TIMEOUT,
			Url::STATUS_PENDING,
			Url::STATUS_VALID,
		];

		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'broken';

		return in_array( $status, $valid_statuses, true ) ? $status : 'broken';
	}

	/**
	 * Get filter options for display.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_filters(): array {
		return [
			'all'      => __( 'All', 'yoko-link-checker' ),
			'broken'   => __( 'Broken', 'yoko-link-checker' ),
			'warning'  => __( 'Warning', 'yoko-link-checker' ),
			'redirect' => __( 'Redirect', 'yoko-link-checker' ),
			'blocked'  => __( 'Blocked', 'yoko-link-checker' ),
			'timeout'  => __( 'Timeout', 'yoko-link-checker' ),
			'pending'  => __( 'Pending', 'yoko-link-checker' ),
			'valid'    => __( 'Valid', 'yoko-link-checker' ),
		];
	}

	/**
	 * Ignore a link.
	 *
	 * @since 1.0.0
	 * @param int $link_id Link ID.
	 * @return void
	 */
	private function ignore_link( int $link_id ): void {
		$this->link_repository->update(
			$link_id,
			[ 'ignored' => 1 ]
		);

		/**
		 * Fires when a link is ignored.
		 *
		 * @since 1.0.0
		 * @param int $link_id Link ID.
		 */
		do_action( 'ylc_link_ignored', $link_id );
	}

	/**
	 * Unignore a link.
	 *
	 * @since 1.0.0
	 * @param int $link_id Link ID.
	 * @return void
	 */
	private function unignore_link( int $link_id ): void {
		$this->link_repository->update(
			$link_id,
			[ 'ignored' => 0 ]
		);

		/**
		 * Fires when a link is unignored.
		 *
		 * @since 1.0.0
		 * @param int $link_id Link ID.
		 */
		do_action( 'ylc_link_unignored', $link_id );
	}

	/**
	 * Get the list table instance.
	 *
	 * @since 1.0.0
	 * @return LinksListTable|null
	 */
	public function get_list_table(): ?LinksListTable {
		return $this->list_table;
	}
}
