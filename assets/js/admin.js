/**
 * Yoko Link Checker Admin Scripts
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

/* global jQuery, ylcAdmin */

(function($) {
	'use strict';

	/**
	 * Status polling interval in milliseconds.
	 */
	const POLL_INTERVAL = 3000;

	/**
	 * Current polling timer.
	 */
	let pollTimer = null;

	/**
	 * Initialize the admin scripts.
	 */
	function init() {
		bindEvents();
		maybeStartPolling();
	}

	/**
	 * Bind event handlers.
	 */
	function bindEvents() {
		// Scan controls
		$(document).on('click', '.ylc-start-scan', handleStartScan);
		$(document).on('click', '.ylc-pause-scan', handlePauseScan);
		$(document).on('click', '.ylc-resume-scan', handleResumeScan);
		$(document).on('click', '.ylc-cancel-scan', handleCancelScan);

		// Link actions
		$(document).on('click', '.ylc-recheck-url', handleRecheckUrl);
		$(document).on('click', '.ylc-ignore-link', handleIgnoreLink);

		// Data management
		$(document).on('click', '.ylc-clear-data', handleClearData);

		// Modal close
		$(document).on('click', '.ylc-modal-close', closeModal);
		$(document).on('click', '.ylc-modal', function(e) {
			if (e.target === this) {
				closeModal();
			}
		});
	}

	/**
	 * Start polling if a scan is running.
	 */
	function maybeStartPolling() {
		const $status = $('#ylc-scan-status');
		if ($status.find('.ylc-scanning').length > 0) {
			startPolling();
		}
	}

	/**
	 * Start status polling.
	 */
	function startPolling() {
		if (pollTimer) {
			return;
		}

		pollTimer = setInterval(pollStatus, POLL_INTERVAL);
	}

	/**
	 * Stop status polling.
	 */
	function stopPolling() {
		if (pollTimer) {
			clearInterval(pollTimer);
			pollTimer = null;
		}
	}

	/**
	 * Poll for scan status.
	 */
	function pollStatus() {
		$.ajax({
			url: ylcAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'yoko_lc_get_scan_status',
				nonce: ylcAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					updateScanStatus(response.data);
				}
			}
		});
	}

	/**
	 * Update scan status display.
	 *
	 * @param {Object} data Status data.
	 */
	function updateScanStatus(data) {
		if (!data.running && data.status && data.status.status === 'completed') {
			stopPolling();
			location.reload();
			return;
		}

		if (!data.running && !data.status) {
			stopPolling();
			return;
		}

		if (data.running) {
			const $progressFill = $('.ylc-progress-fill');
			const $progressText = $('.ylc-progress-text');
			const $scanPhase = $('.ylc-scan-phase');

			$progressFill.css('width', data.progress + '%');
			$progressText.text(Math.round(data.progress * 10) / 10 + '%');
			$scanPhase.text('Phase: ' + capitalizeFirst(data.status.phase));
		}
	}

	/**
	 * Handle start scan click.
	 *
	 * @param {Event} e Click event.
	 */
	function handleStartScan(e) {
		e.preventDefault();

		if (!confirm(ylcAdmin.strings.confirmStart)) {
			return;
		}

		const $button = $(this);
		$button.prop('disabled', true).text(ylcAdmin.strings.scanning);

		$.ajax({
			url: ylcAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'yoko_lc_start_scan',
				nonce: ylcAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert((response.data && response.data.message) || ylcAdmin.strings.error);
					$button.prop('disabled', false).text('Start New Scan');
				}
			},
			error: function() {
				alert(ylcAdmin.strings.error);
				$button.prop('disabled', false).text('Start New Scan');
			}
		});
	}

	/**
	 * Handle pause scan click.
	 *
	 * @param {Event} e Click event.
	 */
	function handlePauseScan(e) {
		e.preventDefault();

		const $button = $(this);
		const scanId = $button.data('scan-id');

		$button.prop('disabled', true);

		$.ajax({
			url: ylcAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'yoko_lc_pause_scan',
				scan_id: scanId,
				nonce: ylcAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					stopPolling();
					location.reload();
				} else {
					alert((response.data && response.data.message) || ylcAdmin.strings.error);
					$button.prop('disabled', false);
				}
			},
			error: function() {
				alert(ylcAdmin.strings.error);
				$button.prop('disabled', false);
			}
		});
	}

	/**
	 * Handle resume scan click.
	 *
	 * @param {Event} e Click event.
	 */
	function handleResumeScan(e) {
		e.preventDefault();

		const $button = $(this);
		const scanId = $button.data('scan-id');

		$button.prop('disabled', true);

		$.ajax({
			url: ylcAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'yoko_lc_resume_scan',
				scan_id: scanId,
				nonce: ylcAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert((response.data && response.data.message) || ylcAdmin.strings.error);
					$button.prop('disabled', false);
				}
			},
			error: function() {
				alert(ylcAdmin.strings.error);
				$button.prop('disabled', false);
			}
		});
	}

	/**
	 * Handle cancel scan click.
	 *
	 * @param {Event} e Click event.
	 */
	function handleCancelScan(e) {
		e.preventDefault();

		if (!confirm(ylcAdmin.strings.confirmCancel)) {
			return;
		}

		const $button = $(this);
		const scanId = $button.data('scan-id');

		$button.prop('disabled', true);

		$.ajax({
			url: ylcAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'yoko_lc_cancel_scan',
				scan_id: scanId,
				nonce: ylcAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					stopPolling();
					location.reload();
				} else {
					alert((response.data && response.data.message) || ylcAdmin.strings.error);
					$button.prop('disabled', false);
				}
			},
			error: function() {
				alert(ylcAdmin.strings.error);
				$button.prop('disabled', false);
			}
		});
	}

	/**
	 * Handle recheck URL click.
	 *
	 * @param {Event} e Click event.
	 */
	function handleRecheckUrl(e) {
		e.preventDefault();

		const $link = $(this);
		const urlId = $link.data('url-id');
		const url = $link.data('url');
		const $row = $link.closest('tr');

		// Show modal
		$('#ylc-recheck-url').text(url);
		$('#ylc-recheck-modal').show();

		$.ajax({
			url: ylcAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'yoko_lc_recheck_url',
				url_id: urlId,
				nonce: ylcAdmin.nonce
			},
			success: function(response) {
				closeModal();
				if (response.success && response.data.url) {
					// Update row status
					const $statusCell = $row.find('.ylc-status');
					$statusCell
						.removeClass()
						.addClass('ylc-status ylc-status-' + response.data.url.status)
						.text(capitalizeFirst(response.data.url.status));

					const $codeCell = $row.find('.ylc-code');
					$codeCell.text(response.data.url.http_code || '—');
				}
			},
			error: function() {
				closeModal();
				alert(ylcAdmin.strings.error);
			}
		});
	}

	/**
	 * Handle ignore link click.
	 *
	 * @param {Event} e Click event.
	 */
	function handleIgnoreLink(e) {
		e.preventDefault();

		if (!confirm(ylcAdmin.strings.confirmIgnore)) {
			return;
		}

		const $link = $(this);
		const linkId = $link.data('link-id');
		const $row = $link.closest('tr');

		$.ajax({
			url: ylcAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'yoko_lc_ignore_link',
				link_id: linkId,
				nonce: ylcAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					$row.fadeOut(function() {
						$(this).remove();
					});
				} else {
					alert((response.data && response.data.message) || ylcAdmin.strings.error);
				}
			},
			error: function() {
				alert(ylcAdmin.strings.error);
			}
		});
	}

	/**
	 * Close modal.
	 */
	function closeModal() {
		$('.ylc-modal').hide();
	}

	/**
	 * Handle clear data click.
	 *
	 * @param {Event} e Click event.
	 */
	function handleClearData(e) {
		e.preventDefault();

		if (!confirm(ylcAdmin.strings.confirmClear || 'Are you sure you want to delete all scan data? This cannot be undone.')) {
			return;
		}

		const $button = $(this);
		$button.prop('disabled', true).text(ylcAdmin.strings.clearing || 'Clearing...');

		$.ajax({
			url: ylcAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'yoko_lc_clear_data',
				nonce: ylcAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message || 'All data cleared.');
					location.reload();
				} else {
					alert((response.data && response.data.message) || ylcAdmin.strings.error);
					$button.prop('disabled', false).text(ylcAdmin.strings.clearData || 'Clear All Scan Data');
				}
			},
			error: function() {
				alert(ylcAdmin.strings.error);
				$button.prop('disabled', false).text(ylcAdmin.strings.clearData || 'Clear All Scan Data');
			}
		});
	}

	/**
	 * Capitalize first letter.
	 *
	 * @param {string} str String to capitalize.
	 * @return {string} Capitalized string.
	 */
	function capitalizeFirst(str) {
		if (!str) return '';
		return str.charAt(0).toUpperCase() + str.slice(1);
	}

	// Initialize on document ready
	$(document).ready(init);

})(jQuery);
