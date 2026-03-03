<?php
/**
 * Settings admin template.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 *
 * @var array $settings Current settings.
 */

defined( 'ABSPATH' ) || exit;

// Get all public post types.
$post_types = get_post_types( [ 'public' => true ], 'objects' );
?>

<div class="wrap ylc-settings">
	<h1><?php esc_html_e( 'Link Checker Settings', 'yoko-link-checker' ); ?></h1>

	<?php settings_errors( 'ylc_settings' ); ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'ylc_settings', 'ylc_settings_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<!-- Post Types -->
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Post Types to Scan', 'yoko-link-checker' ); ?></label>
					</th>
					<td>
						<fieldset>
							<?php foreach ( $post_types as $post_type ) : ?>
								<label>
									<input type="checkbox" 
									       name="ylc_post_types[]" 
									       value="<?php echo esc_attr( $post_type->name ); ?>"
									       <?php checked( in_array( $post_type->name, $settings['post_types'], true ) ); ?>>
									<?php echo esc_html( $post_type->labels->name ); ?>
								</label><br>
							<?php endforeach; ?>
							<p class="description">
								<?php esc_html_e( 'Select which post types to check for broken links.', 'yoko-link-checker' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>

				<!-- Check Timeout -->
				<tr>
					<th scope="row">
						<label for="ylc_check_timeout"><?php esc_html_e( 'Request Timeout', 'yoko-link-checker' ); ?></label>
					</th>
					<td>
						<input type="number" 
						       id="ylc_check_timeout" 
						       name="ylc_check_timeout" 
						       value="<?php echo esc_attr( $settings['check_timeout'] ); ?>"
						       min="5" 
						       max="120" 
						       step="1"
						       class="small-text">
						<span><?php esc_html_e( 'seconds', 'yoko-link-checker' ); ?></span>
						<p class="description">
							<?php esc_html_e( 'How long to wait for a response when checking URLs. Increase if you get many timeout errors.', 'yoko-link-checker' ); ?>
						</p>
					</td>
				</tr>

				<!-- Auto Scan -->
				<tr>
					<th scope="row">
						<label for="ylc_auto_scan_enabled"><?php esc_html_e( 'Automatic Scanning', 'yoko-link-checker' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" 
							       id="ylc_auto_scan_enabled" 
							       name="ylc_auto_scan_enabled" 
							       value="1"
							       <?php checked( $settings['auto_scan_enabled'] ); ?>>
							<?php esc_html_e( 'Enable automatic scheduled scans', 'yoko-link-checker' ); ?>
						</label>
					</td>
				</tr>

				<!-- Scan Frequency -->
				<tr class="ylc-auto-scan-option" <?php echo ! $settings['auto_scan_enabled'] ? 'style="display:none;"' : ''; ?>>
					<th scope="row">
						<label for="ylc_auto_scan_frequency"><?php esc_html_e( 'Scan Frequency', 'yoko-link-checker' ); ?></label>
					</th>
					<td>
						<select id="ylc_auto_scan_frequency" name="ylc_auto_scan_frequency">
							<option value="hourly" <?php selected( $settings['auto_scan_frequency'], 'hourly' ); ?>>
								<?php esc_html_e( 'Hourly', 'yoko-link-checker' ); ?>
							</option>
							<option value="twicedaily" <?php selected( $settings['auto_scan_frequency'], 'twicedaily' ); ?>>
								<?php esc_html_e( 'Twice Daily', 'yoko-link-checker' ); ?>
							</option>
							<option value="daily" <?php selected( $settings['auto_scan_frequency'], 'daily' ); ?>>
								<?php esc_html_e( 'Daily', 'yoko-link-checker' ); ?>
							</option>
							<option value="weekly" <?php selected( $settings['auto_scan_frequency'], 'weekly' ); ?>>
								<?php esc_html_e( 'Weekly', 'yoko-link-checker' ); ?>
							</option>
						</select>
						<p class="description">
							<?php esc_html_e( 'How often to automatically scan for broken links.', 'yoko-link-checker' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button( __( 'Save Settings', 'yoko-link-checker' ) ); ?>
	</form>

	<hr>

	<!-- Danger Zone -->
	<div class="ylc-danger-zone">
		<h2><?php esc_html_e( 'Danger Zone', 'yoko-link-checker' ); ?></h2>
		<p><?php esc_html_e( 'These actions cannot be undone.', 'yoko-link-checker' ); ?></p>
		
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Clear All Data', 'yoko-link-checker' ); ?></th>
					<td>
						<button type="button" class="button button-secondary ylc-clear-data">
							<?php esc_html_e( 'Clear All Scan Data', 'yoko-link-checker' ); ?>
						</button>
						<p class="description">
							<?php esc_html_e( 'Delete all URLs and scan history. This will not affect your posts.', 'yoko-link-checker' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Toggle auto-scan options.
	$('#ylc_auto_scan_enabled').on('change', function() {
		$('.ylc-auto-scan-option').toggle(this.checked);
	});
});
</script>
