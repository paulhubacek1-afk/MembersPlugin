/**
 * Members Plugin – reliable WordPress Media Library picker for the settings
 * fields "Default Profile Photo" and "Default Cover Photo" (and every other
 * media field on the plugin settings pages).
 *
 * The bundled core binds its handler only once on page load and only if the
 * media scripts were already available. This delegated handler always works;
 * it does nothing when the core handler has already handled the click.
 */
(function ($) {
	'use strict';

	if (!$) {
		return;
	}

	var frame = null;

	function siblings(button, selector) {
		return button.closest('.um-media-upload').find(selector);
	}

	$(document).on('click', '.um-media-upload .um-set-image', function (e) {
		// The core handler already opened the media library for this click.
		if (e.isDefaultPrevented()) {
			return;
		}

		var button = $(this);
		e.preventDefault();

		if (typeof wp === 'undefined' || !wp.media) {
			window.alert('Die WordPress-Mediathek konnte nicht geladen werden. Bitte die Seite neu laden.');
			return;
		}

		if (frame) {
			frame.off('select');
		} else {
			frame = wp.media({
				title: button.data('upload_frame') || 'Bild auswählen',
				button: { text: (wp.i18n && wp.i18n.__) ? wp.i18n.__('Select', 'ultimate-member') : 'Auswählen' },
				library: { type: 'image' },
				multiple: false
			});
		}

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			var thumbnail = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

			siblings(button, '.icon_preview').attr('src', attachment.url).show();
			siblings(button, '.um-media-upload-data-id').val(attachment.id);
			siblings(button, '.um-media-upload-data-width').val(attachment.width);
			siblings(button, '.um-media-upload-data-height').val(attachment.height);
			siblings(button, '.um-media-upload-data-thumbnail').val(thumbnail);
			siblings(button, '.um-media-upload-data-url').val(attachment.url).trigger('change');
			siblings(button, '.um-media-upload-url').val(attachment.url);

			siblings(button, '.um-clear-image').show();
			button.hide();

			$(document).trigger('um_media_upload_select', [button, attachment]);
		});

		frame.open();
	});

	$(document).on('click', '.um-media-upload .um-clear-image', function (e) {
		if (e.isDefaultPrevented()) {
			return;
		}
		e.preventDefault();

		var clear = $(this);
		var field = siblings(clear, '.um-media-upload-data-url');
		var defaultUrl = field.data('default');
		if (typeof defaultUrl === 'undefined') {
			defaultUrl = '';
		}

		siblings(clear, '.um-set-image').show();
		clear.hide();
		siblings(clear, '.icon_preview').attr('src', defaultUrl).toggle('' !== defaultUrl);
		siblings(clear, '.um-media-upload-data-id, .um-media-upload-data-width, .um-media-upload-data-height, .um-media-upload-data-thumbnail').val('');
		field.val(defaultUrl).trigger('change');
		siblings(clear, '.um-media-upload-url').val(defaultUrl);

		$(document).trigger('um_media_upload_clear', clear);
	});
}(window.jQuery));
