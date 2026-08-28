(function ($) {
	'use strict';

	var cfg = window.erdcConditionalLogic;

	if (!cfg || !cfg.sets || typeof acf === 'undefined') return;

	/**
	 * Which set of plugin fields, if any, applies to the group being edited.
	 *
	 * Decided from the group's CURRENT location rules rather than from what was saved, so
	 * that pointing a brand new group at Page Content (or switching it to Page Header)
	 * takes effect straight away without a reload. A group aimed at neither gets nothing:
	 * its conditional logic is left entirely to ACF.
	 */
	function activeSet() {
		var match = null;

		// Matched on input name, not on markup. ACF renders location rules and conditional
		// logic rules with the identical .rule-groups / .rule-group / .rule structure, so
		// selecting on those classes also picks up every conditional logic row on the page -
		// whose "param" is a field key rather than a location parameter. The name prefix is
		// the only thing that reliably separates the two.
		$('select[name^="acf_field_group[location]"][name$="[param]"]').each(function () {
			if (cfg.ruleNames.indexOf(this.value) === -1) return;

			var prefix = this.name.slice(0, -'[param]'.length);
			var operator = $('[name="' + prefix + '[operator]"]').val();
			var value = $('[name="' + prefix + '[value]"]').val();

			if (operator !== '==' || !value || !cfg.sets[value]) return;

			match = value;
			return false;
		});

		return match;
	}

	// Groups of fields for the currently applicable set, plus a flat lookup by field key.
	var groups = [];
	var lookup = {};

	function refreshSet() {
		var key = activeSet();

		groups = key ? cfg.sets[key] : [];
		lookup = {};

		groups.forEach(function (group) {
			group.fields.forEach(function (field) {
				lookup[field.key] = $.extend({}, field, { layout: group.layout });
			});
		});

		return key;
	}

	function isPluginField(key) {
		return !!(key && lookup[key]);
	}

	/**
	 * Which of `groups` a rule inside `$contextEl` should actually be offered - narrows
	 * a Page Content set down to the ONE layout `$contextEl` lives inside (plus every
	 * 'shared' group, e.g. Buttons/Section Intro/Other Settings, which apply to every
	 * layout), so the dropdown stops listing every layout's fields at once. Sets with no
	 * per-layout groups at all (Page Header, Buttons, Section Intro as a LOCATION) have
	 * nothing to narrow and are returned unfiltered - unchanged from before this existed.
	 *
	 * Falls back to the full, unfiltered list (not just the shared groups) whenever the
	 * context can't be resolved to one of OUR layouts specifically - either because
	 * `$contextEl` isn't inside any flexible-content layout at all, or because it's
	 * inside one whose name/label doesn't match anything we know (a site's own custom
	 * layout, or one not yet renamed). Silently narrowing to "shared only" in that case
	 * would hide fields a real rule might already target.
	 */
	function visibleGroups($contextEl) {
		if (!groups.length) return groups;

		var hasLayoutGroups = groups.some(function (g) { return g.kind === 'layout'; });
		if (!hasLayoutGroups) return groups;

		var ctx = layoutContextFor($contextEl);

		var matched = ctx && groups.filter(function (g) {
			return g.kind === 'layout' && ((ctx.name && g.name === ctx.name) || (ctx.label && g.label === ctx.label));
		});

		if (!matched || !matched.length) return groups;

		// Matched layout's own fields first, then the shared groups (Buttons, Section
		// Intro, Other Settings) - same field-group-first ordering the PHP side already
		// produces for the unfiltered list.
		return matched.concat(groups.filter(function (g) { return g.kind === 'shared'; }));
	}

	/**
	 * Which of our known layouts (if any) `$el` is nested inside, read from the
	 * flexible-content layout settings wrapper ACF itself renders
	 * (class-acf-field-flexible-content.php's render_field_settings()). `data-layout-
	 * name`/`data-layout-label` are kept live by ACF Pro's own field-group JS on blur of
	 * the Layout Name/Label inputs, so reading them here (rather than the inputs' .val()
	 * directly) is safe and already-sanitised. Returns null when `$el` isn't inside any
	 * flexible-content layout at all (e.g. a plain top-level field).
	 */
	function layoutContextFor($el) {
		var $layout = $el.closest('.acf-field-setting-fc_layout');
		if (!$layout.length) return null;

		return {
			name: $layout.attr('data-layout-name') || $layout.find('.layout-name').first().val() || '',
			label: $layout.attr('data-layout-label') || $layout.find('.layout-label').first().val() || ''
		};
	}

	/**
	 * ACF builds the rule's field dropdown from acf.getFieldObjects(), which only sees
	 * fields in the group being edited. It has just run by the time this fires, so the
	 * plugin's fields are appended to what it produced rather than replacing it.
	 *
	 * `groupsToShow` is only ever the (possibly layout-narrowed) options to render - it
	 * must never be used for isPluginField()/applyToRule()'s own lookups, which stay
	 * against the full, unfiltered set (see the comment above repairSavedRules()).
	 */
	function appendPluginFields($select, groupsToShow) {
		if (!$select.length || !groupsToShow || !groupsToShow.length) return;

		// ACF rebuilds this select from scratch each time it renders, wiping anything added
		// before, so the guard is per render rather than once per select.
		if (!$select.find('optgroup[data-erdc]').length) {
			groupsToShow.forEach(function (group) {
				var $optgroup = $('<optgroup>', { label: group.layout, 'data-erdc': '1' });
				group.fields.forEach(function (field) {
					$optgroup.append($('<option>', { value: field.key, text: field.label }));
				});
				$select.append($optgroup);
			});
		}

		// The rule's saved target lives on the row as data-field and is what ACF itself
		// treats as authoritative. When it names one of our fields, ACF's own render will
		// have failed to find it and quietly fallen back to the first option - so restore
		// it now that the real option exists, otherwise saving would rewrite the rule.
		var wanted = $select.closest('.rule').attr('data-field');

		if (wanted && isPluginField(wanted) && $select.val() !== wanted) {
			$select.val(wanted);
		}
	}

	/**
	 * Operator and value are normally derived from the selected field's field object,
	 * which does not exist for our fields. acf.getConditionTypes() only needs the type
	 * string, so the operator list is built the same way ACF would; the value input is
	 * built from the choices sent through from PHP.
	 */
	function renderOperator($rule, field) {
		var $operator = $rule.find('.condition-rule-operator');
		if (!$operator.length) return;

		var wanted = $rule.attr('data-operator') || $operator.val();
		var options = [];

		(acf.getConditionTypes({ fieldType: field.type }) || []).forEach(function (type) {
			options.push({ id: type.prototype.operator, text: type.prototype.label });
		});

		if (!options.length) return;

		acf.renderSelect($operator, options);
		if (wanted && options.some(function (o) { return o.id === wanted; })) $operator.val(wanted);
	}

	function renderValue($rule, field) {
		var $td = $rule.find('td.value');
		var $input = $rule.find('.condition-rule-value');
		if (!$td.length || !$input.length) return;

		var wanted = $rule.attr('data-value');
		if (wanted === null || typeof wanted === 'undefined') wanted = $input.val();

		var name = $input.attr('name');
		var cls = ($input.attr('class') || '').replace(/select2[^\s]*/g, '').trim();

		// A field with fixed choices gets a select of exactly those; anything else keeps a
		// free text input, which is what ACF falls back to as well.
		var $replacement;
		if (field.choices && field.choices.length) {
			$replacement = $('<select>', { name: name, 'class': cls });
			field.choices.forEach(function (choice) {
				$replacement.append($('<option>', { value: choice.value, text: choice.label }));
			});
		} else {
			$replacement = $('<input>', { type: 'text', name: name, 'class': cls });
		}

		if ($input.hasClass('select2-hidden-accessible')) {
			try { $input.select2('destroy'); } catch (e) {}
		}
		$td.find('.select2-container').remove();
		$input.replaceWith($replacement);

		if (wanted) $replacement.val(wanted);
	}

	function applyToRule($rule) {
		var key = $rule.find('.condition-rule-field').val();
		if (!isPluginField(key)) return;

		renderOperator($rule, lookup[key]);
		renderValue($rule, lookup[key]);
	}

	// Bound after ACF's own handlers (this script depends on acf-field-group), so each
	// runs once ACF has finished with the same event.
	$(document)
		.on('focus', '.condition-rule-field', function () {
			refreshSet();
			appendPluginFields($(this), visibleGroups($(this)));
		})
		.on('change', '.condition-rule-field', function () {
			var $rule = $(this).closest('.rule');
			// ACF has already re-rendered operator/value from its own field objects; for our
			// fields that produces nothing usable, so replace both.
			applyToRule($rule);
		});

	// Changing the group's own location changes which set applies, so drop the options
	// already added and let them rebuild from the new set on next render.
	var currentSet = refreshSet();

	$(document).on('change', 'select[name^="acf_field_group[location]"]', function () {
		var next = refreshSet();

		if (next === currentSet) return;

		currentSet = next;
		$('.condition-rule-field').find('optgroup[data-erdc]').remove();
	});

	// Renaming a layout doesn't re-render the fields nested inside it, so a dropdown
	// already built against that layout's old name/label would otherwise keep showing
	// the wrong (or empty) context indefinitely. Scoped to the one layout that changed -
	// dropped here, then naturally rebuilt correctly next time each is focused.
	$(document).on(
		'blur',
		'.acf-field-setting-fc_layout .layout-name, .acf-field-setting-fc_layout .layout-label',
		function () {
			$(this).closest('.acf-field-setting-fc_layout')
				.find('.condition-rule-field')
				.find('optgroup[data-erdc]').remove();
		}
	);

	/**
	 * Repairs every rule already on the page whose saved target is one of our fields.
	 *
	 * ACF renders those rules before this runs and, finding no field object for the saved
	 * key, leaves the dropdown showing whichever field happened to be first. Left alone,
	 * saving the group would silently replace the rule with that wrong field.
	 */
	function repairSavedRules() {
		refreshSet();

		if (!groups.length) return;

		$('.rule').each(function () {
			var $rule = $(this);
			if (!isPluginField($rule.attr('data-field'))) return;

			var $select = $rule.find('.condition-rule-field');
			appendPluginFields($select, visibleGroups($select));
			applyToRule($rule);
		});
	}

	// Deferred: ACF renders its rules synchronously during its own ready pass, so running
	// in the same tick would be undone immediately afterwards.
	acf.addAction('ready', function () {
		setTimeout(repairSavedRules, 0);
	});

	// Field objects render their settings lazily when opened, producing rules that were
	// never on the page during the ready pass.
	acf.addAction('open_field_object', function () {
		setTimeout(repairSavedRules, 0);
	});

	// The one that matters for data safety. ACF re-renders a rule whenever it feels the
	// need, and each time it cannot resolve one of our fields it leaves the value input
	// empty - which would then be saved over a perfectly good rule. Repairing in the
	// capture phase puts the right values back before any of ACF's own submit handling,
	// whatever happened earlier in the page's life.
	var form = document.getElementById('post');

	if (form) {
		form.addEventListener('submit', repairSavedRules, true);
	}

}(jQuery));
