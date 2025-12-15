/**
 * TAW Real Estate - Admin UI interactions
 *
 * Handles sidebar tab switching and media uploads inside the Property Details meta box.
 */

(function($) {
	'use strict';

	$(document).on('click', '.taw-re-tabs-nav li', function(e) {
		e.preventDefault();

		var $item = $(this);
		var tab   = $item.data('tab');
		var $panel = $item.closest('.taw-re-settings');

		// Switch active nav item.
		$item.addClass('is-active').siblings().removeClass('is-active');

		// Switch active tab.
		$panel.find('.taw-re-tab').removeClass('is-active');
		$panel.find('.taw-re-tab[data-tab="' + tab + '"]').addClass('is-active');
	});

	// Gallery image selection for Media tab.
	$(document).on('click', '.taw-re-media-upload', function(e) {
		e.preventDefault();

		var $button   = $(this);
		var $wrapper  = $button.closest('.taw-re-tab');
		var $input    = $wrapper.find('#taw_property_gallery_ids');
		var $preview  = $wrapper.find('.taw-re-media-preview');
		var frame;

		// Create the media frame.
		frame = wp.media({
			title: $button.data('title') || 'Select Gallery Images',
			button: {
				text: $button.data('button') || 'Use these images',
			},
			multiple: true,
		});

		frame.on('select', function() {
			var selection   = frame.state().get('selection');
			var existingVal = $input.val() || '';
			var ids         = [];

			if (existingVal) {
				existingVal.split(',').forEach(function(id) {
					id = id.trim();
					if (id) {
						ids.push(parseInt(id, 10));
					}
				});
			}

			selection.each(function(attachment) {
				attachment = attachment.toJSON();
				if (!attachment.id) {
					return;
				}
				if (ids.indexOf(attachment.id) !== -1) {
					return;
				}
				ids.push(attachment.id);
				var thumbUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
				var title    = attachment.title || '';
				var html     = ''
					+ '<div class="taw-re-media-item" data-id="' + attachment.id + '">'
					+ '  <div class="taw-re-media-thumb"><img src="' + thumbUrl + '" alt=""></div>'
					+ '  <button type="button" class="taw-re-media-remove" aria-label="Remove image"><span class="dashicons dashicons-trash"></span></button>';
				if (title) {
					html += '<div class="taw-re-media-title">' + title + '</div>';
				}
				html += '</div>';

				$preview.append(html);
			});

			$input.val(ids.join(','));
		});

		frame.open();
	});

	// Remove single image from gallery.
	$(document).on('click', '.taw-re-media-remove', function(e) {
		e.preventDefault();
		var $item   = $(this).closest('.taw-re-media-item');
		var $panel  = $item.closest('.taw-re-tab');
		var $input  = $panel.find('#taw_property_gallery_ids');

		$item.remove();

		// Rebuild ID list from remaining items.
		var ids = [];
		$panel.find('.taw-re-media-item').each(function() {
			var id = $(this).data('id');
			if (id) {
				ids.push(id);
			}
		});
		$input.val(ids.join(','));
	});

	// Number input increment/decrement buttons.
	$(document).on('click', '.taw-number-btn', function(e) {
		e.preventDefault();
		var $button = $(this);
		var targetId = $button.data('target');
		var $input = $('#' + targetId);
		
		if (!$input.length) {
			return;
		}

		var currentVal = parseFloat($input.val()) || 0;
		var step = parseFloat($input.attr('step')) || 1;
		var min = parseFloat($input.attr('min')) || 0;
		var newVal;

		if ($button.hasClass('taw-number-plus')) {
			newVal = currentVal + step;
		} else if ($button.hasClass('taw-number-minus')) {
			newVal = Math.max(min, currentVal - step);
		}

		// Round to appropriate decimal places based on step
		if (step < 1) {
			newVal = Math.round(newVal * 10) / 10;
		} else {
			newVal = Math.round(newVal);
		}

		$input.val(newVal).trigger('change');
	});

})(jQuery);





