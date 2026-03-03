<?php
/**
 * Dashboard admin template.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 *
 * @var array                                    $stats           Link statistics.
 * @var array|null                               $scan_status     Current scan status.
 * @var array                                    $recent_broken   Recent broken links.
 * @var array                                    $status_breakdown Status breakdown data.
 * @var \YokoLinkChecker\Admin\DashboardPage     $this            Dashboard page instance.
 */

defined( 'ABSPATH' ) || exit;

$is_scanning = $scan_status && 'running' === $scan_status['status'];
?>

<div class="wrap ylc-dashboard">
	<h1><?php esc_html_e( 'Link Checker Dashboard', 'yoko-link-checker' ); ?></h1>

	<!-- Scan Control Section -->
	<div class="ylc-card ylc-scan-control">
		<h2><?php esc_html_e( 'Scan', 'yoko-link-checker' ); ?></h2>
		
		<div class="ylc-scan-status" id="ylc-scan-status">
			<?php if ( $is_scanning ) : ?>
				<div class="ylc-scanning">
					<span class="spinner is-active"></span>
					<span class="ylc-scan-phase">
						<?php
						printf(
							/* translators: %s: scan phase name */
							esc_html__( 'Phase: %s', 'yoko-link-checker' ),
							esc_html( ucfirst( $scan_status['phase'] ) )
						);
						?>
					</span>
					<div class="ylc-progress-bar">
						<div class="ylc-progress-fill" style="width: <?php echo esc_attr( $scan_status['progress'] ); ?>%"></div>
					</div>
					<span class="ylc-progress-text"><?php echo esc_html( round( $scan_status['progress'], 1 ) ); ?>%</span>
				</div>
			<?php else : ?>
				<p>
					<?php esc_html_e( 'Last scan:', 'yoko-link-checker' ); ?>
					<strong><?php echo esc_html( $this->format_last_scan( $stats['last_scan'] ) ); ?></strong>
					<?php if ( $stats['last_scan'] ) : ?>
						(<?php echo esc_html( $this->format_scan_duration( $stats['last_scan'] ) ); ?>)
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</div>

		<div class="ylc-scan-actions">
			<?php if ( $is_scanning ) : ?>
				<button type="button" class="button ylc-pause-scan" data-scan-id="<?php echo esc_attr( $scan_status['scan_id'] ); ?>">
					<?php esc_html_e( 'Pause', 'yoko-link-checker' ); ?>
				</button>
				<button type="button" class="button ylc-cancel-scan" data-scan-id="<?php echo esc_attr( $scan_status['scan_id'] ); ?>">
					<?php esc_html_e( 'Cancel', 'yoko-link-checker' ); ?>
				</button>
			<?php elseif ( $scan_status && 'paused' === $scan_status['status'] ) : ?>
				<button type="button" class="button button-primary ylc-resume-scan" data-scan-id="<?php echo esc_attr( $scan_status['scan_id'] ); ?>">
					<?php esc_html_e( 'Resume Scan', 'yoko-link-checker' ); ?>
				</button>
				<button type="button" class="button ylc-cancel-scan" data-scan-id="<?php echo esc_attr( $scan_status['scan_id'] ); ?>">
					<?php esc_html_e( 'Cancel', 'yoko-link-checker' ); ?>
				</button>
			<?php else : ?>
				<button type="button" class="button button-primary ylc-start-scan">
					<?php esc_html_e( 'Start New Scan', 'yoko-link-checker' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</div>

	<!-- Stats Grid -->
	<div class="ylc-stats-grid">
		<div class="ylc-stat-card ylc-stat-total">
			<div class="ylc-stat-number"><?php echo esc_html( number_format_i18n( $stats['total_urls'] ) ); ?></div>
			<div class="ylc-stat-label"><?php esc_html_e( 'Total URLs', 'yoko-link-checker' ); ?></div>
		</div>
		
		<div class="ylc-stat-card ylc-stat-broken">
			<div class="ylc-stat-number"><?php echo esc_html( number_format_i18n( $stats['broken'] ) ); ?></div>
			<div class="ylc-stat-label"><?php esc_html_e( 'Broken', 'yoko-link-checker' ); ?></div>
			<?php if ( $stats['broken'] > 0 ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=yoko-link-checker-results&status=broken' ) ); ?>" class="ylc-stat-link">
					<?php esc_html_e( 'View all', 'yoko-link-checker' ); ?> →
				</a>
			<?php endif; ?>
		</div>
		
		<div class="ylc-stat-card ylc-stat-warning">
			<div class="ylc-stat-number"><?php echo esc_html( number_format_i18n( $stats['warnings'] ) ); ?></div>
			<div class="ylc-stat-label"><?php esc_html_e( 'Warnings', 'yoko-link-checker' ); ?></div>
			<?php if ( $stats['warnings'] > 0 ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=yoko-link-checker-results&status=warning' ) ); ?>" class="ylc-stat-link">
					<?php esc_html_e( 'View all', 'yoko-link-checker' ); ?> →
				</a>
			<?php endif; ?>
		</div>
		
		<div class="ylc-stat-card ylc-stat-redirect">
			<div class="ylc-stat-number"><?php echo esc_html( number_format_i18n( $stats['redirects'] ) ); ?></div>
			<div class="ylc-stat-label"><?php esc_html_e( 'Redirects', 'yoko-link-checker' ); ?></div>
		</div>
		
		<div class="ylc-stat-card ylc-stat-valid">
			<div class="ylc-stat-number"><?php echo esc_html( number_format_i18n( $stats['valid'] ) ); ?></div>
			<div class="ylc-stat-label"><?php esc_html_e( 'Valid', 'yoko-link-checker' ); ?></div>
		</div>
		
		<div class="ylc-stat-card ylc-stat-pending">
			<div class="ylc-stat-number"><?php echo esc_html( number_format_i18n( $stats['pending'] ) ); ?></div>
			<div class="ylc-stat-label"><?php esc_html_e( 'Pending', 'yoko-link-checker' ); ?></div>
		</div>
	</div>

	<!-- Recent Broken Links -->
	<?php if ( ! empty( $recent_broken ) ) : ?>
	<div class="ylc-card ylc-recent-broken">
		<h2>
			<?php esc_html_e( 'Recent Broken Links', 'yoko-link-checker' ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=yoko-link-checker-results&status=broken' ) ); ?>" class="ylc-view-all">
				<?php esc_html_e( 'View all', 'yoko-link-checker' ); ?> →
			</a>
		</h2>
		
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'URL', 'yoko-link-checker' ); ?></th>
					<th><?php esc_html_e( 'Code', 'yoko-link-checker' ); ?></th>
					<th><?php esc_html_e( 'Source', 'yoko-link-checker' ); ?></th>
					<th><?php esc_html_e( 'Last Checked', 'yoko-link-checker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $recent_broken as $link ) : ?>
				<tr>
					<td>
						<a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( $link['url'] ); ?>">
							<?php echo esc_html( wp_trim_words( $link['url'], 8, '...' ) ); ?>
						</a>
					</td>
					<td>
						<span class="ylc-code ylc-code-client-error"><?php echo esc_html( $link['http_code'] ?: '—' ); ?></span>
					</td>
					<td>
						<?php if ( $link['source_id'] && $link['post_title'] ) : ?>
							<a href="<?php echo esc_url( get_edit_post_link( $link['source_id'] ) ); ?>">
								<?php echo esc_html( wp_trim_words( $link['post_title'], 5, '...' ) ); ?>
							</a>
						<?php else : ?>
							—
						<?php endif; ?>
					</td>
					<td>
						<?php
						if ( $link['last_checked'] ) {
							echo esc_html( human_time_diff( strtotime( $link['last_checked'] ) ) . ' ' . __( 'ago', 'yoko-link-checker' ) );
						} else {
							esc_html_e( 'Never', 'yoko-link-checker' );
						}
						?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php elseif ( $stats['total_urls'] > 0 ) : ?>
	<div class="ylc-card ylc-no-broken">
		<p class="ylc-success-message">
			<span class="dashicons dashicons-yes-alt"></span>
			<?php esc_html_e( 'No broken links found! Your site is in good shape.', 'yoko-link-checker' ); ?>
		</p>
	</div>
	<?php else : ?>
	<div class="ylc-card ylc-no-data">
		<p>
			<?php esc_html_e( 'No links have been scanned yet. Start a scan to check your site for broken links.', 'yoko-link-checker' ); ?>
		</p>
	</div>
	<?php endif; ?>

</div>
