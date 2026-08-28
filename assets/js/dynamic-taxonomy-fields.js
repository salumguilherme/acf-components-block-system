(function ($) {
	'use strict';

	var cfg = window.erdcDynamicTaxonomyFields;

	if (!cfg || !Array.isArray(cfg.groups)) return;

	/**
	 * Per-child-field cache of "what was selected while the parent held value X", so
	 * switching the parent away and back (taxonomy A -> B -> A) restores A's selection
	 * instead of leaving it empty. Lives only in the DOM element's jQuery data - nothing
	 * persisted - so it naturally resets on page load and never survives past a save.
	 */
	function getValueCache($childField) {
		var cache = $childField.data('erdcValueCache');
		if (!cache) {
			cache = {};
			$childField.data('erdcValueCache', cache);
		}
		return cache;
	}

	/**
	 * Moves each selected <option> so the selected subset ends up in DOM order matching
	 * `order`. A native <select multiple>'s .val() - which is what ends up saved, since
	 * these fields post as plain form fields - always returns selected options in DOM
	 * order regardless of the order they were clicked in, so this is what actually
	 * controls save order. Unselected options are left wherever they are; only the
	 * relative order of the selected ones matters for .val().
	 */
	function applySelectionOrder($select, order) {
		var byValue = {};

		$select.find('option').each(function () {
			byValue[this.value] = this;
		});

		$.each(order, function (i, value) {
			var option = byValue[value];
			// appendChild on a node already in the document moves it rather than cloning it,
			// so this both re-homes and re-orders in one step.
			if (option) $select.append(option);
		});
	}

	/**
	 * Keeps a field's tracked selection order in step with a user-driven change (picking
	 * or removing an item directly in the dropdown, as opposed to a taxonomy/post type
	 * switch, which is handled by loadChildren instead) and re-applies it to the DOM so
	 * the new order is what gets saved.
	 */
	function syncSelectionOrder($select) {
		if (!$select.prop('multiple')) return;

		var current = [].concat($select.val() || []);
		var order = ($select.data('erdcOrder') || []).filter(function (value) {
			return current.indexOf(value) !== -1;
		});

		// Anything newly selected wasn't in the tracked order yet - append it at the end,
		// which is "just picked" for a single selection (the normal case; simultaneous
		// multi-value changes fall back to DOM order between themselves).
		$.each(current, function (i, value) {
			if (order.indexOf(value) === -1) order.push(value);
		});

		$select.data('erdcOrder', order);
		applySelectionOrder($select, order);
	}

	// Delegated once for every field this module manages, rather than per-field: the
	// <select> itself is never replaced (only its <option>s are), so a single binding
	// keyed off a marker class set in loadChildren survives every reload.
	$(document).on('change', 'select.erdc-orderable', function () {
		syncSelectionOrder($(this));
	});

	/**
	 * Renders a reload response's choices into $select.
	 *
	 * A Taxonomy field posting a single taxonomy (whether it's a plain Select, or a
	 * multi-select with only one taxonomy currently picked) gets back a flat choices
	 * array, same as a Post Type reload always does - rendered as plain <option>s.
	 *
	 * A Taxonomy field with more than one taxonomy selected gets back { grouped: true,
	 * choices: [{label, options: [...]}, ...] } instead (see
	 * Ajax::get_term_choices()) - rendered as one <optgroup> per taxonomy.
	 *
	 * The selection-order tracking (erdc-orderable / applySelectionOrder) is skipped for
	 * a grouped result: it works by re-appending selected <option>s directly onto
	 * $select in click order, which would pull them out of their <optgroup> and flatten
	 * the very grouping this was asked for. Native <select multiple> save order then
	 * falls back to group order, which is deterministic and good enough for a case that
	 * was never about click order in the first place.
	 */
	function appendChoices($select, payload, wanted) {
		// Ajax::get_term_choices() always wraps its response as { grouped, choices } -
		// whether or not grouped is true - so unwrap whenever that shape is present, not
		// only when grouped happens to be true; Ajax::get_post_choices() still posts a
		// bare array, which is used as-is.
		var isWrapped = !!(payload && !$.isArray(payload) && ("choices" in payload));
		var grouped = isWrapped && !!payload.grouped;
		var choices = isWrapped ? payload.choices : payload;

		function appendOption($parent, option) {
			$parent.append(
				$('<option>', {
					value: option.value,
					text: option.label,
					selected: wanted.indexOf(String(option.value)) !== -1,
				})
			);
		}

		if (grouped) {
			$.each(choices, function (i, group) {
				var $optgroup = $('<optgroup>', { label: group.label });
				$.each(group.options, function (j, option) {
					appendOption($optgroup, option);
				});
				$select.append($optgroup);
			});
			$select.removeClass('erdc-orderable').removeData('erdcOrder');
			return;
		}

		$.each(choices, function (i, option) {
			appendOption($select, option);
		});

		// The choices above just arrived in query order (alphabetical/hierarchical),
		// which is meaningless for the previously-selected subset - restore the order
		// they were actually selected in (from cache, or the field's saved value on
		// first load) rather than leaving them in query order.
		$select.addClass('erdc-orderable').data('erdcOrder', wanted.slice());
		applySelectionOrder($select, wanted);
	}

	/**
	 * Reload the given child field(s) select with choices fetched for `value`.
	 *
	 * $parentField   — .acf-field wrapper of the field that was changed (Taxonomy or
	 *                  Post Type select)
	 * value          — the parent field's new value (taxonomy slug or post type slug)
	 * previousValue  — the parent field's value before this change, or null if there
	 *                  wasn't one (initial sync on page load / row append). Whatever is
	 *                  currently selected gets cached under this value before the reload,
	 *                  so switching back later can restore it.
	 * childKeys      — field key(s) of the child field(s) to reload
	 * action         — wp_ajax action to call for this reload
	 * nonce          — nonce to send with the AJAX request
	 * onChildLoaded  — optional; called once per child as ($childField) right after its
	 *                  Select2 is reinitialised. Used by the post_type_source -> taxonomy
	 *                  relationship to cascade a reload into the taxonomy -> terms chain
	 *                  below it, since the taxonomy field's own value can change as a side
	 *                  effect of narrowing its choices and nothing else would notice.
	 */
	function loadChildren($parentField, value, previousValue, childKeys, action, nonce, onChildLoaded) {
		// Flexible content layout rows have class "layout" (not "acf-layout").
		// Repeater rows use "acf-row". Fall back to ".acf-fields" if neither matches.
		var $row = $parentField.closest('.acf-row, .layout');
		if (!$row.length) $row = $parentField.closest('.acf-fields');

		$.each(childKeys, function (i, childKey) {

			var $childField = $row.find('.acf-field[data-key="' + childKey + '"]');
			// An empty array (every taxonomy deselected from a multi-select field) is
			// truthy in JS, so !value alone would not catch it and would fire a doomed
			// AJAX call for nothing selected.
			if (!$childField.length || !value || ($.isArray(value) && !value.length)) return;

			var $select = $childField.find('select');
			if (!$select.length) return;

			// Read current selection BEFORE destroying Select2 — the underlying
			// <select> element always reflects the real value even when Select2 is active.
			var currentValues = [].concat($select.val() || []);
			var cache = getValueCache($childField);

			// Remember the selection we're navigating away from, keyed by the value it
			// belonged to - unless this is the very first sync, where there is no "away
			// from" and currentValues is just what ACF rendered from the saved post.
			if (previousValue) {
				cache[previousValue] = currentValues;
			}

			// Restore a remembered selection for this value if we have one; otherwise fall
			// back to whatever is currently selected (covers the initial sync case above,
			// where the select already holds the field's saved value).
			var wanted = Object.prototype.hasOwnProperty.call(cache, value) ? cache[value] : currentValues;

			// Tear down the existing Select2 instance via ACF's field object so its
			// internal reference is cleared cleanly alongside the DOM cleanup.
			var acfField = acf.getField($childField);
			if (acfField && acfField.select2) {
				acfField.select2.destroy();
				acfField.select2 = false;
			} else if ($select.hasClass('select2-hidden-accessible')) {
				$select.select2('destroy');
			}
			$childField.find('.select2-container').remove();
			$select.removeClass('select2-hidden-accessible').prop('disabled', true).empty();

			$.post(cfg.ajaxurl, {
				action: action,
				value: value,
				nonce: nonce,
			}, function (res) {
				if (res.success && res.data) {
					appendChoices($select, res.data, wanted);
				}
			}).always(function () {
				$select.prop('disabled', false);

				// Reinitialise Select2 using ACF's own factory with the field's stored
				// settings — mirrors exactly what ACF does in the select field's initialize().
				if (acfField) {
					acfField.select2 = acf.newSelect2($select, {
						field: acfField,
						ajax: acfField.get('ajax') || false,
						multiple: !!acfField.get('multiple'),
						allowNull: !!acfField.get('allow_null'),
						placeholder: acfField.get('placeholder') || '',
						tags: acfField.get('create_options') || false,
					});
				}

				if (typeof onChildLoaded === 'function') onChildLoaded($childField);
			});

		});
	}

	$.each(cfg.groups, function (g, group) {

		$.each(group.pairs, function (i, pair) {

			var parentKey = pair.parent;
			var childKeys = pair.children;

			// Parent field changed: reload children for the new value. The value being left
			// gets cached (see loadChildren) and, if this same value was chosen before in
			// this row, its previous selection is restored rather than left empty.
			$(document).on(
				'change',
				'.acf-field[data-key="' + parentKey + '"] select',
				function () {
					var $select = $(this);
					var previousValue = $select.data('erdcPrevValue') || null;
					var value = $select.val();

					loadChildren($select.closest('.acf-field'), value, previousValue, childKeys, group.action, group.nonce);

					$select.data('erdcPrevValue', value);
				}
			);

			// On page load / new row appended: populate children for any row that already
			// has a parent value selected, preserving any previously saved selections.
			function syncScope($scope) {
				if (!$scope || typeof $scope.find !== 'function') return;
				$scope.find('.acf-field[data-key="' + parentKey + '"] select').each(function () {
					var $select = $(this);
					var value = $select.val();
					if (value) {
						loadChildren($select.closest('.acf-field'), value, null, childKeys, group.action, group.nonce);
						$select.data('erdcPrevValue', value);
					}
				});
			}

			// acf's own 'ready' action already fires from $(document).ready internally (with
			// no scope argument, hence the fallback below) - a separate binding here would
			// double-run this sync on every initial page load, emptying and repopulating
			// each select twice in close succession and leaving it with duplicate <option>s.
			acf.addAction('ready', function ($el) { syncScope($el || $(document)); });
			acf.addAction('append', function ($el) { syncScope($el || $(document)); });

		});

	});

	/**
	 * "post_type_source" -> Taxonomy: narrows a Taxonomy field's OWN choices to whatever
	 * taxonomies are registered on the post type(s) currently selected in one or more
	 * OTHER field(s) - one level up from the Taxonomy -> Terms reload above, and a
	 * genuinely different shape: several source fields can each contribute post types,
	 * all combined into one filter on a single Taxonomy field, rather than one parent
	 * driving one or more children.
	 *
	 * Only pairs that declare 'post_type_source' show up here at all (see
	 * Module::enqueue_assets()) - everything else keeps reloading exactly as above.
	 *
	 * On initial page load, a row that already has both a post_type_source AND a
	 * Taxonomy value fires two overlapping terms reloads: the taxonomy -> terms sync
	 * above (registered first, using the taxonomy field's as-saved value) and this
	 * block's own cascade (using the post-type-narrowed value). Both resolve to the
	 * same, correct final state - the cascade's onChildLoaded always re-triggers the
	 * terms reload last - but the two AJAX calls aren't sequenced against each other,
	 * so an unusually slow first response arriving after the second could briefly leave
	 * stale terms until the next real interaction. Accepted rather than added
	 * cross-referencing between the two configs to suppress the first sync entirely:
	 * self-correcting, not a data-loss risk, and this is already a fairly deep chain.
	 */
	if (cfg.postTypeTaxonomyFilters && $.isArray(cfg.postTypeTaxonomyFilters.pairs)) {

		var ptf = cfg.postTypeTaxonomyFilters;

		$.each(ptf.pairs, function (i, pair) {

			var sourceKeys = pair.sources;
			var taxonomyKey = pair.taxonomy;

			// Every currently-selected value across all of this pair's source fields in
			// $row, combined and deduplicated - an empty result means "nothing selected
			// yet", which leaves the Taxonomy field showing every public taxonomy rather
			// than emptying it out before the site builder has picked a post type.
			function combinedSourceValues($row) {
				var values = [];

				$.each(sourceKeys, function (j, key) {
					var $select = $row.find('.acf-field[data-key="' + key + '"] select');
					if (!$select.length) return;

					var v = $select.val();
					if (!v) return;

					values = values.concat($.isArray(v) ? v : [v]);
				});

				return values.filter(function (v, idx) { return values.indexOf(v) === idx; });
			}

			// $anyFieldInRow only needs to be something inside the right row/layout -
			// it's used purely to locate that row, same as loadChildren's $parentField.
			function reload($anyFieldInRow) {
				var $row = $anyFieldInRow.closest('.acf-row, .layout');
				if (!$row.length) $row = $anyFieldInRow.closest('.acf-fields');

				var $taxField = $row.find('.acf-field[data-key="' + taxonomyKey + '"]');
				if (!$taxField.length) return;

				var values = combinedSourceValues($row);
				if (!values.length) return;

				var previous = $taxField.data('erdcPrevSourceValues') || null;

				loadChildren($taxField, values, previous, [taxonomyKey], ptf.action, ptf.nonce, function ($reloadedField) {
					// Cascades into the Taxonomy -> Terms reload exactly as if the site
					// builder had picked the taxonomy themselves - whether or not the
					// taxonomy's own selected value actually changed, since there's no
					// cheap way to tell from here and an extra terms reload is harmless.
					$reloadedField.find('select').trigger('change');
				});

				$taxField.data('erdcPrevSourceValues', values);
			}

			$.each(sourceKeys, function (j, key) {
				$(document).on('change', '.acf-field[data-key="' + key + '"] select', function () {
					reload($(this).closest('.acf-field'));
				});
			});

			function syncScope($scope) {
				if (!$scope || typeof $scope.find !== 'function') return;
				$scope.find('.acf-field[data-key="' + taxonomyKey + '"]').each(function () {
					reload($(this));
				});
			}

			acf.addAction('ready', function ($el) { syncScope($el || $(document)); });
			acf.addAction('append', function ($el) { syncScope($el || $(document)); });

		});

	}

}(jQuery));
