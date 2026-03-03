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

$list_table = $this->get_list_table();
$current_url = admin_url( 'admin.php?page=yoko-link-checker-results' );
?>

<div class="wrap ylc-results">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Broken Links', 'yoko-link-checker' ); ?></h1>
	
	<hr class="wp-header-end">

	<!-- Status Filter Tabs -->
	<ul class="subsubsub">
		<?php
		$filter_links = [];
		foreach ( $filters as $status => $label ) {
			$url   = add_query_arg( 'status', $status, $current_url );
			$class = ( $status_filter === $status ) ? 'current' : '';
			
			$filter_links[] = sprintf(
				'<li class="ylc-filter-%s"><a href="%s" class="%s">%s</a></li>',
				esc_attr( $status ),
				esc_url( $url ),
				esc_attr( $class ),
				esc_html( $label )
			);
		}
		echo implode( ' | ', $filter_links ); // phpcs:ignore WordPress.Security.EscapeOutput
		?>
	</ul>

	<form id="ylc-links-filter" method="get">
		<input type="hidden" name="page" value="yoko-link-checker-results">
		<input type="hidden" name="status" value="<?php echo esc_attr( $status_filter ); ?>">
		
		<?php
		$list_table->search_box( __( 'Search URLs', 'yoko-link-checker' ), 'ylc-search' );
		$list_table->display();
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
