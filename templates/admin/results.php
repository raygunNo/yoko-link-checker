<?php
/**
 * Results admin template.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 *
 * @var string                                   $status_filter Current status filter.
 * @var array                                    $filters       Available filters.
 * @var \YokoLinkChecker\Admin\ResultsPage       $this          Results page instance.
 */

defined( 'ABSPATH' ) || exit;

$yoko_lc_list_table  = $this->get_list_table();
$yoko_lc_current_url = admin_url( 'admin.php?page=yoko-link-checker-results' );
?>

<div class="wrap ylc-results">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Link Reports', 'yoko-link-checker' ); ?></h1>
	
	<hr class="wp-header-end">

	<!-- Status Filter Tabs -->
	<ul class="subsubsub">
		<?php
		$yoko_lc_filter_links = array();
		foreach ( $filters as $yoko_lc_status => $yoko_lc_label ) {
			$yoko_lc_url   = add_query_arg( 'status', $yoko_lc_status, $yoko_lc_current_url );
			$yoko_lc_class = ( $status_filter === $yoko_lc_status ) ? 'current' : '';

			$yoko_lc_filter_links[] = sprintf(
				'<li class="ylc-filter-%s"><a href="%s" class="%s">%s</a></li>',
				esc_attr( $yoko_lc_status ),
				esc_url( $yoko_lc_url ),
				esc_attr( $yoko_lc_class ),
				esc_html( $yoko_lc_label )
			);
		}
		echo implode( ' | ', $yoko_lc_filter_links ); // phpcs:ignore WordPress.Security.EscapeOutput
		?>
	</ul>

	<form id="ylc-links-filter" method="get">
		<input type="hidden" name="page" value="yoko-link-checker-results">
		<input type="hidden" name="status" value="<?php echo esc_attr( $status_filter ); ?>">
		
		<?php
		$yoko_lc_list_table->search_box( __( 'Search URLs', 'yoko-link-checker' ), 'ylc-search' );
		$yoko_lc_list_table->display();
		?>
	</form>
</div>

<!-- Recheck Modal -->
<div id="ylc-recheck-modal" class="ylc-modal" style="display: none;">
	<div class="ylc-modal-content">
		<span class="ylc-modal-close">&times;</span>
		<h3><?php esc_html_e( 'Rechecking URL...', 'yoko-link-checker' ); ?></h3>
		<div class="ylc-modal-body">
			<span class="spinner is-active"></span>
			<p id="ylc-recheck-url"></p>
		</div>
	</div>
</div>
