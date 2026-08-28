(function ($) {
	'use strict';

	if (typeof acf === 'undefined') return;

	var RepeaterField = acf.getFieldType('repeater');

	if (!RepeaterField) return;

	/**
	 * Registers "erdc_buttons_repeater"'s client-side behaviour by extending ACF's own
	 * Repeater field model unchanged, just under our own type name.
	 *
	 * Buttons_Field_Type (the PHP field type) extends \acf_field_repeater, but ACF's
	 * client-side field-type registry has no equivalent inheritance: acf.getFieldType()
	 * looks up a field's JS behaviour by its literal `type` attribute alone, so without
	 * this, a field typed "erdc_buttons_repeater" falls back to ACF's generic base Field
	 * class - no Add Row / remove row / drag-reorder / collapsed-row behaviour at all.
	 * Confirmed directly in a live browser session before this file existed:
	 * acf.getFieldType('erdc_buttons_repeater') resolved to nothing, and every
	 * "Add Row" click on a Buttons Repeater field - nested inside another repeater or
	 * not - silently did nothing.
	 *
	 * Intro_Field_Type (extends \acf_field__group) needs no equivalent fix: ACF's own
	 * "Group" field type has no dedicated client-side model at all - confirmed the same
	 * way, acf.getFieldType('group') also resolves to nothing on stock ACF - so there is
	 * no behaviour to inherit or lose there.
	 */
	var ButtonsRepeaterField = RepeaterField.extend({
		type: 'erdc_buttons_repeater'
	});

	acf.registerFieldType(ButtonsRepeaterField);

}(jQuery));
