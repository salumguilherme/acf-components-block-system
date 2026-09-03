(function ($) {
	'use strict';

	var cfg = window.erdcAcfCopyToClipboard;

	if (!cfg || typeof acf === 'undefined') return;

	var ICON_SVG = '<svg viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">' +
		'<path d="M15 0.5H3C1.61929 0.5 0.5 1.61929 0.5 3V15C0.5 16.3807 1.61929 17.5 3 17.5H15C16.3807 17.5 17.5 16.3807 17.5 15V3C17.5 1.61929 16.3807 0.5 15 0.5Z" fill="#EAECF0" stroke="#CCD0D4"/>' +
		'<path class="erdc-acf-copy-field-name__glyph" fill-rule="evenodd" clip-rule="evenodd" d="M8.09091 4C7.3378 4 6.72727 4.61052 6.72727 5.36364V6.72727H5.36364C4.61052 6.72727 4 7.3378 4 8.09091V12.6364C4 13.3895 4.61052 14 5.36364 14H9.90909C10.6622 14 11.2727 13.3895 11.2727 12.6364V11.2727H12.6364C13.3895 11.2727 14 10.6622 14 9.90909V5.36364C14 4.61052 13.3895 4 12.6364 4H8.09091ZM11.2727 10.3636H12.6364C12.8874 10.3636 13.0909 10.1601 13.0909 9.90909V5.36364C13.0909 5.1126 12.8874 4.90909 12.6364 4.90909H8.09091C7.83987 4.90909 7.63636 5.1126 7.63636 5.36364V6.72727H9.90909C10.6622 6.72727 11.2727 7.3378 11.2727 8.09091V10.3636ZM4.90909 8.09091C4.90909 7.83987 5.1126 7.63636 5.36364 7.63636H9.90909C10.1601 7.63636 10.3636 7.83987 10.3636 8.09091V12.6364C10.3636 12.8874 10.1601 13.0909 9.90909 13.0909H5.36364C5.1126 13.0909 4.90909 12.8874 4.90909 12.6364V8.09091Z" fill="#008DD3"/>' +
		'</svg>';

	/**
	 * Copies text to the clipboard, falling back to a hidden textarea + execCommand
	 * on contexts where the async Clipboard API isn't available (e.g. non-HTTPS).
	 */
	function copyText(text) {

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text);
			return;
		}

		var $temp = $('<textarea readonly></textarea>')
			.css({ position: 'fixed', top: '-9999px', left: '-9999px' })
			.val(text)
			.appendTo('body')
			.select();

		document.execCommand('copy');

		$temp.remove();

	}

	/**
	 * Adds the copy icon to every not-yet-processed ACF field within $scope. Scoped
	 * to $scope (rather than always re-querying the whole document) so a freshly
	 * appended repeater/flexible content/group row only gets processed for its own
	 * fields, matching the acf.addAction('append', ...) convention used elsewhere in
	 * this plugin's ACF-facing scripts.
	 */
	function addIcons($scope) {

		// ACF's own 'ready' action fires with no argument (unlike 'append', which
		// always passes the newly inserted $el) - fall back to the whole document.
		($scope && $scope.length ? $scope : $(document)).find('.acf-field').each(function () {

			var $field = $(this);
			var name = $field.data('name');
			var $label = $field.find('> .acf-label > label');

			if (!name || !$label.length || $label.find('> .erdc-acf-copy-field-name').length) return;

			var $icon = $(ICON_SVG)
				.wrap('<span class="erdc-acf-copy-field-name" data-erdc-tooltip="' + cfg.tooltip + '" role="button" tabindex="0" aria-label="' + cfg.tooltip + '"></span>')
				.parent()
				.attr('data-name', name);

			$label.append($icon);

		});

	}

	$(document).on('keydown', '.erdc-acf-copy-field-name', function (e) {

		if (e.key !== 'Enter' && e.key !== ' ') return;

		e.preventDefault();
		$(this).trigger('click');

	});

	$(document).on('click', '.erdc-acf-copy-field-name', function (e) {

		e.preventDefault();

		var $icon = $(this);

		copyText($icon.data('name'));

		$icon.addClass('is-copied');

		// The rapid-in/slow-out animation is entirely CSS-driven (see
		// acf-copy-to-clipboard.css): adding .is-copied plays the fast transition to
		// green, and removing it shortly after lets the base, slower transition fade
		// it back to its original colour.
		setTimeout(function () {
			$icon.removeClass('is-copied');
		}, 150);

	});

	acf.addAction('ready', function ($el) {
		addIcons($el);
	});

	acf.addAction('append', function ($el) {
		addIcons($el);
	});

})(jQuery);
