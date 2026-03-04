<?php
/**
 * Links List Table class.
 *
 * Extends WP_List_Table for displaying broken links.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Admin;

use YokoLinkChecker\Repository\LinkRepository;
use YokoLinkChecker\Model\Url;
use WP_List_Table;

// Load WP_List_Table if not available.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Links list table class.
 *
 * @since 1.0.0
 */
class LinksListTable extends WP_List_Table {

	/**
	 * Link repository instance.
	 *
	 * @var LinkRepository
	 */
	private LinkRepository $link_repository;

	/**
	 * Status filter.
	 *
	 * @var string
	 */
	private string $status_filter = 'broken';

	/**
	 * Items per page.
	 *
	 * @var int
	 */
	private int $per_page = 20;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param LinkRepository $link_repository Link repository.
	 */
	public function __construct( LinkRepository $link_repository ) {
		$this->link_repository = $link_repository;

		parent::__construct(
			array(
				'singular' => __( 'Link', 'yoko-link-checker' ),
				'plural'   => __( 'Links', 'yoko-link-checker' ),
				'ajax'     => true,
			)
		);
	}

	/**
	 * Set status filter.
	 *
	 * @since 1.0.0
	 * @param string $status Status to filter by.
	 * @return void
	 */
	public function set_filter( string $status ): void {
		$this->status_filter = $status;
	}

	/**
	 * Get columns.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_columns(): array {
		return array(
			'cb'           => '<input type="checkbox" />',
			'url'          => __( 'URL', 'yoko-link-checker' ),
			'status'       => __( 'Status', 'yoko-link-checker' ),
			'http_code'    => __( 'Code', 'yoko-link-checker' ),
			'source'       => __( 'Source', 'yoko-link-checker' ),
			'anchor_text'  => __( 'Anchor Text', 'yoko-link-checker' ),
			'last_checked' => __( 'Last Checked', 'yoko-link-checker' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_sortable_columns(): array {
		return array(
			'url'          => array( 'url', false ),
			'status'       => array( 'status', false ),
			'http_code'    => array( 'http_code', false ),
			'last_checked' => array( 'last_checked', true ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_bulk_actions(): array {
		return array(
			'ignore'   => __( 'Ignore', 'yoko-link-checker' ),
			'unignore' => __( 'Un-ignore', 'yoko-link-checker' ),
			'recheck'  => __( 'Recheck', 'yoko-link-checker' ),
		);
	}

	/**
	 * Prepare items.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function prepare_items(): void {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Process bulk actions.
		$this->process_bulk_action();

		// Get current page.
		$current_page = $this->get_pagenum();

		// Build query args.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- List table parameters don't require nonce.
		$args = array(
			'per_page' => $this->per_page,
			'page'     => $current_page,
			'orderby'  => isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'last_checked',
			'order'    => isset( $_GET['order'] ) ? strtoupper( sanitize_key( $_GET['order'] ) ) : 'DESC',
		);

		if ( 'all' !== $this->status_filter ) {
			$args['status'] = $this->status_filter;
		}

		// Search.
		if ( ! empty( $_GET['s'] ) ) {
			$args['search'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
		}
		// phpcs:enable

		// Get items.
		$this->items = $this->link_repository->get_links_with_urls( $args );

		// Get total count.
		$total_items = $this->link_repository->count_links_with_status( $args['status'] ?? null );

		// Set pagination.
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $this->per_page,
				'total_pages' => ceil( $total_items / $this->per_page ),
			)
		);
	}

	/**
	 * Process bulk actions.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function process_bulk_action(): void {
		$action = $this->current_action();

		if ( ! $action ) {
			return;
		}

		// Verify nonce.
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
			return;
		}

		// Use manage_options as fallback if custom caps don't exist.
		if ( ! current_user_can( 'yoko_lc_manage_scans' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$link_ids = isset( $_REQUEST['link_ids'] ) && is_array( $_REQUEST['link_ids'] )
			? array_map( 'absint', $_REQUEST['link_ids'] )
			: array();

		if ( empty( $link_ids ) ) {
			return;
		}

		$processed = 0;

		switch ( $action ) {
			case 'ignore':
				foreach ( $link_ids as $link_id ) {
					$this->link_repository->update( $link_id, array( 'ignored' => 1 ) );
					++$processed;
				}
				break;

			case 'unignore':
				foreach ( $link_ids as $link_id ) {
					$this->link_repository->update( $link_id, array( 'ignored' => 0 ) );
					++$processed;
				}
				break;

			case 'recheck':
				/**
				 * Fires when links should be rechecked.
				 *
				 * @since 1.0.0
				 * @param array $link_ids Link IDs to recheck.
				 */
				do_action( 'yoko_lc_recheck_links', $link_ids );
				$processed = count( $link_ids );
				break;
		}

		// Redirect to avoid re-processing on refresh.
		if ( $processed > 0 ) {
			$redirect_url = remove_query_arg( array( 'action', 'action2', 'link_ids', '_wpnonce' ) );
			$redirect_url = add_query_arg( 'bulk_processed', $processed, $redirect_url );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Column checkbox.
	 *
	 * @since 1.0.0
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="link_ids[]" value="%d" />',
			absint( $item->link_id )
		);
	}

	/**
	 * Column URL.
	 *
	 * @since 1.0.0
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_url( $item ): string {
		$url           = esc_url( $item->url );
		$url_display   = esc_html( $this->truncate_url( $item->url, 60 ) );
		$actions_nonce = wp_create_nonce( "yoko_lc_action_{$item->link_id}" );

		$actions = array(
			'view' => sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				$url,
				__( 'Visit', 'yoko-link-checker' )
			),
		);

		if ( empty( $item->ignored ) ) {
			$actions['ignore'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'action'   => 'ignore',
							'link_id'  => $item->link_id,
							'_wpnonce' => $actions_nonce,
						)
					)
				),
				__( 'Ignore', 'yoko-link-checker' )
			);
		} else {
			$actions['unignore'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'action'   => 'unignore',
							'link_id'  => $item->link_id,
							'_wpnonce' => $actions_nonce,
						)
					)
				),
				__( 'Un-ignore', 'yoko-link-checker' )
			);
		}

		$output = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer" title="%s">%s</a>',
			$url,
			esc_attr( $item->url ),
			$url_display
		);

		if ( ! empty( $item->ignored ) ) {
			$output .= ' <span class="ylc-ignored-badge">' . esc_html__( 'Ignored', 'yoko-link-checker' ) . '</span>';
		}

		return $output . $this->row_actions( $actions );
	}

	/**
	 * Column status.
	 *
	 * @since 1.0.0
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_status( $item ): string {
		$status_labels = array(
			Url::STATUS_PENDING  => __( 'Pending', 'yoko-link-checker' ),
			Url::STATUS_VALID    => __( 'Valid', 'yoko-link-checker' ),
			Url::STATUS_REDIRECT => __( 'Redirect', 'yoko-link-checker' ),
			Url::STATUS_BROKEN   => __( 'Broken', 'yoko-link-checker' ),
			Url::STATUS_WARNING  => __( 'Warning', 'yoko-link-checker' ),
			Url::STATUS_BLOCKED  => __( 'Blocked', 'yoko-link-checker' ),
			Url::STATUS_TIMEOUT  => __( 'Timeout', 'yoko-link-checker' ),
			Url::STATUS_ERROR    => __( 'Error', 'yoko-link-checker' ),
		);

		// Status descriptions for tooltips.
		$status_descriptions = array(
			Url::STATUS_WARNING  => __( 'The server returned a response that may indicate a problem. This could be a temporary issue or the site may block automated requests.', 'yoko-link-checker' ),
			Url::STATUS_BLOCKED  => __( 'The request was blocked by the destination server. Many social media sites block automated link checking.', 'yoko-link-checker' ),
			Url::STATUS_TIMEOUT  => __( 'The request timed out waiting for a response. The server may be slow or unreachable.', 'yoko-link-checker' ),
			Url::STATUS_ERROR    => __( 'A connection error occurred. This may be a DNS, SSL, or network issue.', 'yoko-link-checker' ),
			Url::STATUS_REDIRECT => __( 'This URL redirects to a different location. The link still works, but you may want to update it to the final destination.', 'yoko-link-checker' ),
		);

		$label = $status_labels[ $item->status ] ?? $item->status;

		// Build tooltip from description and/or error message.
		$tooltip_parts = array();

		if ( isset( $status_descriptions[ $item->status ] ) ) {
			$tooltip_parts[] = $status_descriptions[ $item->status ];
		}

		if ( ! empty( $item->error_message ) ) {
			$tooltip_parts[] = $item->error_message;
		}

		$tooltip = implode( "\n\n", $tooltip_parts );

		if ( $tooltip ) {
			return sprintf(
				'<span class="ylc-status ylc-status-%s" title="%s">%s</span>',
				esc_attr( $item->status ),
				esc_attr( $tooltip ),
				esc_html( $label )
			);
		}

		return sprintf(
			'<span class="ylc-status ylc-status-%s">%s</span>',
			esc_attr( $item->status ),
			esc_html( $label )
		);
	}

	/**
	 * Column HTTP code.
	 *
	 * @since 1.0.0
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_http_code( $item ): string {
		$code = (int) $item->http_code;

		if ( 0 === $code ) {
			return '<span class="ylc-code ylc-code-unknown">—</span>';
		}

		$class = 'ylc-code';
		if ( $code >= 200 && $code < 300 ) {
			$class .= ' ylc-code-success';
		} elseif ( $code >= 300 && $code < 400 ) {
			$class .= ' ylc-code-redirect';
		} elseif ( $code >= 400 && $code < 500 ) {
			$class .= ' ylc-code-client-error';
		} elseif ( $code >= 500 ) {
			$class .= ' ylc-code-server-error';
		}

		$title = '';
		if ( ! empty( $item->error_message ) ) {
			$title = esc_attr( $item->error_message );
		}

		return sprintf(
			'<span class="%s" title="%s">%d</span>',
			esc_attr( $class ),
			$title,
			$code
		);
	}

	/**
	 * Column source.
	 *
	 * @since 1.0.0
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_source( $item ): string {
		$post_id = (int) $item->post_id;

		if ( ! $post_id ) {
			return '—';
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			// translators: %d is the deleted post ID.
			return sprintf( __( 'Deleted post #%d', 'yoko-link-checker' ), $post_id );
		}

		$edit_link = get_edit_post_link( $post_id );
		$view_link = get_permalink( $post_id );

		$output = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $edit_link ),
			esc_html( $this->truncate_text( $post->post_title, 40 ) )
		);

		$output .= ' <a href="' . esc_url( $view_link ) . '" target="_blank" class="ylc-view-post" title="' . esc_attr__( 'View', 'yoko-link-checker' ) . '">↗</a>';

		return $output;
	}

	/**
	 * Column anchor text.
	 *
	 * @since 1.0.0
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_anchor_text( $item ): string {
		if ( empty( $item->anchor_text ) ) {
			return '<em>' . esc_html__( '(none)', 'yoko-link-checker' ) . '</em>';
		}

		return esc_html( $this->truncate_text( $item->anchor_text, 50 ) );
	}

	/**
	 * Column last checked.
	 *
	 * @since 1.0.0
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_last_checked( $item ): string {
		if ( empty( $item->last_checked ) ) {
			return __( 'Never', 'yoko-link-checker' );
		}

		$timestamp = strtotime( $item->last_checked );

		return sprintf(
			'<span title="%s">%s</span>',
			esc_attr( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) ),
			/* translators: %s: human-readable time difference */
			sprintf( __( '%s ago', 'yoko-link-checker' ), human_time_diff( $timestamp ) )
		);
	}

	/**
	 * Default column handler.
	 *
	 * @since 1.0.0
	 * @param object $item        Item.
	 * @param string $column_name Column name.
	 * @return string
	 */
	protected function column_default( $item, $column_name ): string {
		return isset( $item->$column_name ) ? esc_html( (string) $item->$column_name ) : '—';
	}

	/**
	 * Display when no items.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function no_items(): void {
		if ( 'broken' === $this->status_filter ) {
			esc_html_e( 'No broken links found. Great job!', 'yoko-link-checker' );
		} else {
			esc_html_e( 'No links found.', 'yoko-link-checker' );
		}
	}

	/**
	 * Extra table nav.
	 *
	 * @since 1.0.0
	 * @param string $which Which navigation (top or bottom).
	 * @return void
	 */
	protected function extra_tablenav( $which ): void {
		if ( 'top' !== $which ) {
			return;
		}
		?>
		<div class="alignleft actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=yoko-link-checker-results&action=export&_wpnonce=' . wp_create_nonce( 'yoko_lc_export' ) ) ); ?>" 
				class="button">
				<?php esc_html_e( 'Export CSV', 'yoko-link-checker' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Truncate URL for display.
	 *
	 * @since 1.0.0
	 * @param string $url    URL.
	 * @param int    $length Max length.
	 * @return string
	 */
	private function truncate_url( string $url, int $length ): string {
		if ( strlen( $url ) <= $length ) {
			return $url;
		}

		// Remove protocol for display.
		$display = preg_replace( '#^https?://#', '', $url );

		if ( strlen( $display ) <= $length ) {
			return $display;
		}

		return substr( $display, 0, $length - 3 ) . '...';
	}

	/**
	 * Truncate text for display.
	 *
	 * @since 1.0.0
	 * @param string $text   Text.
	 * @param int    $length Max length.
	 * @return string
	 */
	private function truncate_text( string $text, int $length ): string {
		if ( strlen( $text ) <= $length ) {
			return $text;
		}

		return substr( $text, 0, $length - 3 ) . '...';
	}
}
