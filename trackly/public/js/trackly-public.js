/**
 * Trackly Analytics Frontend Admin Panel Script
 */
(function($) {
	'use strict';

	let isSelectorMode = false;
	let hoveredElement = null;
	let heatmapActive = false;

	$(document).ready(function() {
		if (parseInt(tracklyPublicData.is_admin) === 1) {
			initAdminFloatingPanel();
		}
	});

	/**
	 * Premium Non-blocking Toast Notification System
	 */
	function showToast(message, type = 'success') {
		$('#trackly-stats-bar-wrapper .trackly-toast').remove();
		const $toast = $('<div class="trackly-toast"></div>').text(message);
		
		// Color scheme styles
		const bgColor = type === 'error' ? 'rgba(244, 63, 94, 0.95)' : 'rgba(16, 185, 129, 0.95)';
		
		$toast.css({
			position: 'fixed',
			bottom: '30px',
			left: '30px',
			background: bgColor,
			color: '#ffffff',
			padding: '14px 24px',
			borderRadius: '12px',
			boxShadow: '0 20px 40px -10px rgba(0, 0, 0, 0.3)',
			backdropFilter: 'blur(10px)',
			'-webkit-backdrop-filter': 'blur(10px)',
			zIndex: 1000000,
			fontFamily: "'Outfit', sans-serif",
			fontSize: '13px',
			fontWeight: '600',
			opacity: 0,
			transform: 'translateY(20px)',
			transition: 'all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)'
		});

		$('#trackly-stats-bar-wrapper').append($toast);

		setTimeout(() => {
			$toast.css({ opacity: 1, transform: 'translateY(0)' });
		}, 50);

		setTimeout(() => {
			$toast.css({ opacity: 0, transform: 'translateY(20px)' });
			setTimeout(() => $toast.remove(), 300);
		}, 3500);
	}

	/**
	 * Setup and display admin panel interaction
	 */
	function initAdminFloatingPanel() {
		const $toggleBtn = $('#trackly-stats-toggle-btn');
		const $panel = $('#trackly-stats-panel');
		const $minimizeBtn = $('#trackly-panel-minimize-btn');
		const $tabs = $('.trackly-panel-tab');

		$toggleBtn.on('click', function() {
			$panel.toggleClass('active');
			$toggleBtn.fadeOut(200);
			loadPageStats();
		});

		$minimizeBtn.on('click', function() {
			$panel.removeClass('active');
			$toggleBtn.fadeIn(200);
		});

		$tabs.on('click', function() {
			const tabId = $(this).data('tab');
			$tabs.removeClass('active');
			$(this).addClass('active');

			$('.trackly-panel-tab-content').removeClass('active');
			$('#trackly-tab-' + tabId).addClass('active');
		});

		$('#trackly-toggle-heatmap-btn').on('click', toggleHeatmap);
		$('#trackly-clear-heatmap-btn').on('click', clearHeatmapDots);

		$('#trackly-start-selector-btn').on('click', startSelectorMode);
		$('#trackly-cancel-event-btn').on('click', cancelSelectorMode);
		$('#trackly-save-event-btn').on('click', saveCustomEvent);
	}

	/**
	 * Fetch page stats via WP REST API and run recommendations engine
	 */
	function loadPageStats() {
		$.ajax({
			url: tracklyPublicData.rest_url + '/page-stats',
			method: 'GET',
			data: { url: tracklyPublicData.page_url },
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', tracklyPublicData.rest_nonce);
			},
			success: function(res) {
				if (res.success && res.report.rows && res.report.rows.length > 0) {
					const metrics = res.report.rows[0].metricValues;
					const views = parseInt(metrics[0].value);
					const users = parseInt(metrics[1].value);
					const bounce = parseFloat(metrics[2].value);
					const duration = parseInt(metrics[3].value);

					$('#trackly-p-views').text(views.toLocaleString());
					$('#trackly-p-users').text(users.toLocaleString());
					$('#trackly-p-bounce').text((bounce * 100).toFixed(1) + '%');
					
					const mins = Math.floor(duration / 60);
					const secs = duration % 60;
					$('#trackly-p-duration').text(mins + ':' + (secs < 10 ? '0' : '') + secs);

					generateAIInsights(views, users, bounce, duration);
				} else {
					$('#trackly-p-views').text('0');
					$('#trackly-p-users').text('0');
					$('#trackly-p-bounce').text('0%');
					$('#trackly-p-duration').text('0:00');
					generateAIInsights(0, 0, 0, 0);
				}
			}
		});
	}

	/**
	 * Context-aware recommendations engine
	 */
	function generateAIInsights(views, users, bounce, duration) {
		const $insights = $('#trackly-ai-insights-content');
		$insights.empty();

		if (views === 0) {
			$insights.append(`
				<div class="ai-insight-item">
					<span class="dashicons dashicons-warning ai-icon red"></span>
					<div class="ai-text">
						<strong>Waiting for Data</strong>
						<p>Not enough traffic data has been collected for this page yet. You can test with demo mode or verify your GA4 integration.</p>
					</div>
				</div>
			`);
			return;
		}

		if (bounce > 0.55) {
			$insights.append(`
				<div class="ai-insight-item">
					<span class="dashicons dashicons-flag ai-icon red"></span>
					<div class="ai-text">
						<strong>High Bounce Rate (%${(bounce * 100).toFixed(1)})</strong>
						<p>Visitors are leaving the page quickly. Check if the content aligns with the page title, or place an engaging CTA at the bottom of the page.</p>
					</div>
				</div>
			`);
		} else {
			$insights.append(`
				<div class="ai-insight-item">
					<span class="dashicons dashicons-yes-alt ai-icon cyan"></span>
					<div class="ai-text">
						<strong>Healthy Bounce Rate (%${(bounce * 100).toFixed(1)})</strong>
						<p>Your visitors are eager to browse. Congratulations, the content is achieving its goal!</p>
					</div>
				</div>
			`);
		}

		if (duration < 90) {
			$insights.append(`
				<div class="ai-insight-item">
					<span class="dashicons dashicons-clock ai-icon purple"></span>
					<div class="ai-text">
						<strong>Low Time on Page (${duration}sn)</strong>
						<p>Visitors might not be reading the page. Shorten the introductory paragraph and make the page more engaging with visuals.</p>
					</div>
				</div>
			`);
		} else {
			$insights.append(`
				<div class="ai-insight-item">
					<span class="dashicons dashicons-clock ai-icon cyan"></span>
					<div class="ai-text">
						<strong>Great Time on Page (${Math.floor(duration/60)}dk ${duration%60}sn)</strong>
						<p>Users are reading your content in detail. You can use this page to gather newsletter subscriptions.</p>
					</div>
				</div>
			`);
		}

		if (views > 100) {
			$insights.append(`
				<div class="ai-insight-item">
					<span class="dashicons dashicons-lightbulb ai-icon purple"></span>
					<div class="ai-text">
						<strong>Conversion Measurement Suggestion</strong>
						<p>This page has been viewed ${views} times! Run the <strong>Event Builder</strong> in the adjacent tab to track buttons on the page.</p>
					</div>
				</div>
			`);
		}
	}

	/**
	 * Toggle Click Heatmap Overlay
	 */
	function toggleHeatmap() {
		const $btn = $('#trackly-toggle-heatmap-btn');
		if (heatmapActive) {
			clearHeatmapDots();
			$btn.html('<span class="dashicons dashicons-visibility"></span> Show Heatmap').removeClass('secondary');
			$('.heatmap-info-stats').fadeOut(200);
			heatmapActive = false;
		} else {
			$btn.text('Loading...');
			fetchHeatmapData();
		}
	}

	/**
	 * Fetch recorded clicks and render heatmap indicators
	 */
	function fetchHeatmapData() {
		$.ajax({
			url: tracklyPublicData.rest_url + '/clicks',
			method: 'GET',
			data: { url: tracklyPublicData.page_url },
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', tracklyPublicData.rest_nonce);
			},
			success: function(res) {
				if (res.success && res.clicks.length > 0) {
					renderHeatmap(res.clicks);
					$('#trackly-heatmap-click-count').text(res.clicks.length);
					$('.heatmap-info-stats').fadeIn(200);
					
					$('#trackly-toggle-heatmap-btn').html('<span class="dashicons dashicons-hidden"></span> Hide Heatmap').addClass('secondary');
					heatmapActive = true;
				} else {
					showToast('No click records found for this page yet.', 'error');
					$('#trackly-toggle-heatmap-btn').html('<span class="dashicons dashicons-visibility"></span> Show Heatmap');
				}
			},
			error: function() {
				showToast('An error occurred while fetching click data.', 'error');
				$('#trackly-toggle-heatmap-btn').html('<span class="dashicons dashicons-visibility"></span> Show Heatmap');
			}
		});
	}

	/**
	 * Render percentage-normalized click dots.
	 * Explicitly sets body position to relative to prevent coordinate shifts.
	 */
	function renderHeatmap(clicks) {
		clearHeatmapDots();
		
		const $overlay = $('<div id="trackly-heatmap-overlay"></div>');
		const fragment = document.createDocumentFragment();
		
		clicks.forEach(function(click) {
			const dot = document.createElement('div');
			dot.className = 'trackly-heatmap-dot';
			dot.style.left = click.click_x_pct + '%';
			dot.style.top = click.click_y_pct + '%';
			fragment.appendChild(dot);
		});
		
		$overlay.append(fragment);
		$('body').append($overlay);
	}

	function clearHeatmapDots() {
		$('#trackly-heatmap-overlay').remove();
		if (heatmapActive) {
			$('#trackly-toggle-heatmap-btn').html('<span class="dashicons dashicons-visibility"></span> Show Heatmap').removeClass('secondary');
			$('.heatmap-info-stats').fadeOut(200);
			heatmapActive = false;
		}
	}

	/**
	 * GA4 Event Builder Selector Mode
	 */
	function startSelectorMode() {
		isSelectorMode = true;
		window.tracklySelectorModeActive = true; // Block click tracker global logging
		$('#trackly-stats-panel').removeClass('active');

		$('body').css('cursor', 'crosshair');

		$(document).on('mouseover.tracklySelector', handleSelectorMouseOver);
		$(document).on('mouseout.tracklySelector', handleSelectorMouseOut);
		$(document).on('click.tracklySelector', handleSelectorClick);
	}

	function handleSelectorMouseOver(e) {
		if ($(e.target).closest('#trackly-stats-bar-wrapper').length) return;
		hoveredElement = e.target;
		$(hoveredElement).addClass('trackly-selector-hovered');
	}

	function handleSelectorMouseOut(e) {
		if (hoveredElement) {
			$(hoveredElement).removeClass('trackly-selector-hovered');
			hoveredElement = null;
		}
	}

	function handleSelectorClick(e) {
		if ($(e.target).closest('#trackly-stats-bar-wrapper').length) return;

		e.preventDefault();
		e.stopPropagation();

		const selector = window.tracklyGetUniqueSelector(e.target);
		$(e.target).removeClass('trackly-selector-hovered');
		exitSelectorMode();

		$('#trackly-selected-selector-display').text(selector);
		$('#trackly-p-event-name').val('');
		
		$('#trackly-builder-setup').hide();
		$('#trackly-builder-form').show();
		
		$('#trackly-stats-panel').addClass('active');
	}

	function cancelSelectorMode() {
		$('#trackly-builder-setup').show();
		$('#trackly-builder-form').hide();
	}

	function exitSelectorMode() {
		isSelectorMode = false;
		window.tracklySelectorModeActive = false; // Re-enable click tracker
		$('body').css('cursor', 'default');
		
		$(document).off('mouseover.tracklySelector');
		$(document).off('mouseout.tracklySelector');
		$(document).off('click.tracklySelector');
	}



	/**
	 * Save custom event mapping to database
	 */
	function saveCustomEvent() {
		const selector = $('#trackly-selected-selector-display').text();
		const eventName = $('#trackly-p-event-name').val().trim();

		if (!eventName) {
			showToast('Please enter a valid event name.', 'error');
			return;
		}

		// Relaxed XSS Check (Allows quotes for valid selectors like input[type="text"])
		if (/[<>]/.test(selector)) {
			showToast('Invalid CSS Selector.', 'error');
			return;
		}

		$.ajax({
			url: tracklyPublicData.rest_url + '/save-event',
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({
				selector: selector,
				event_name: eventName
			}),
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', tracklyPublicData.rest_nonce);
			},
			success: function(res) {
				if (res.success) {
					showToast(`Success! "${eventName}" GA4 event saved.`, 'success');
					cancelSelectorMode();
				} else {
					showToast('An error occurred while saving the event.', 'error');
				}
			},
			error: function() {
				showToast('A server error occurred.', 'error');
			}
		});
	}

})(jQuery);
