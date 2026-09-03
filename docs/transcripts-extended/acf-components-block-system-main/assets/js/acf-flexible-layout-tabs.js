(function ($) {
	'use strict';

	var cfg = window.erdcFlexibleLayoutTabs;

	if (!cfg || !Array.isArray(cfg.layouts) || !cfg.layouts.length || typeof acf === 'undefined') return;

	/**
	 * A layout that clones Intro (Section Title/Content) starts with an "Intro" tab,
	 * which ACF activates by default - but the fields site builders actually add to a
	 * layout live under the "Content" tab that follows it. This selects "Content"
	 * instead, but ONLY the moment a layout is freshly added: an existing, already-saved
	 * row keeps whatever tab ACF itself would show (its own remembered/default choice),
	 * exactly mirroring the new-vs-existing-row distinction already used for reloading
	 * Source-dependent selects (see dynamic-taxonomy-fields.js).
	 */
	function selectContentTab($layout) {
		var $tab = $layout
			.find('> .acf-fields > .acf-tab-wrap:first .acf-tab-group > li > a')
			.filter(function () {
				return $.trim($(this).text()) === 'Content';
			})
			.first();

		if ($tab.length) $tab.trigger('click');
	}

	// Deferred: ACF's own tab group picks its default tab as part of the same append
	// pass, after every 'append' callback (including this one) has run - selecting a
	// tab here directly is undone immediately afterwards. Mirrors repairSavedRules()'s
	// same deferral in acf-conditional-logic.js, for the same reason.
	acf.addAction('append', function ($el) {
		var $layout = $el.is('.layout') ? $el : $el.find('.layout[data-layout]').addBack('.layout[data-layout]');

		$layout.each(function () {
			var $this = $(this);
			if (cfg.layouts.indexOf($this.attr('data-layout')) !== -1) setTimeout(function () { selectContentTab($this); }, 0);
		});
	});

}(jQuery));
