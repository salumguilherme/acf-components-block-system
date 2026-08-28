<?php

	namespace ERDC\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Interface Flexible_Layout_Component
	 *
	 * Everything the plugin needs to know about one "Flexible Layout Component" - a field
	 * set that is not itself a flexible layout, ships as a hidden clone-source field
	 * group, and can be extended by a site tagging its own group with the matching
	 * Component_Rule value. Buttons and Intro are the two today.
	 *
	 * Implementing this and adding one line to Flexible_Layout_Components::MODULES is the
	 * whole cost of a new component. Before this existed, adding Intro alongside Buttons
	 * meant hand-editing six places (its own field-group class, its site-fields class, a
	 * Component_Rule constant plus dropdown label, a line in Field_Groups::register(), a
	 * line in Field_Groups::register_field_types(), and a line in Conditional_Logic's
	 * $sets) with nothing to catch a missed one. Same idea as
	 * ERDC\Core\Modules_Manager one level up, and as ACF's own
	 * acf_register_field_type().
	 *
	 * NOT for Page Header: it computes a real per-site location, registers as a directly
	 * editable group rather than a hidden clone source, and declines to register at all
	 * when its location comes back empty (see PageHeader\Fields\Field_Group). It shares
	 * only Component_Identity, via Site_Fields_Base.
	 *
	 * @version 1.0.27
	 * @since   1.0.27
	 * @package ERDC\Modules\FlexibleLayoutTemplate\Fields
	 */
	interface Flexible_Layout_Component extends Component_Identity {

		/**
		 * The component's own fields, before the `filter_tag()` filter and before any
		 * site override is merged in.
		 *
		 * @return array
		 */
		public static function get_base_fields(): array;

		/**
		 * Title of the ACF field group this registers as.
		 *
		 * @return string
		 */
		public static function group_title(): string;

		/**
		 * Label for this component's entry in the "Flexible Layout Component" location
		 * rule dropdown.
		 *
		 * @return string
		 */
		public static function label(): string;

		/**
		 * Name of the filter applied to get_base_fields(), letting a site adjust them in
		 * code rather than through a tagged field group.
		 *
		 * @return string
		 */
		public static function filter_tag(): string;

		/**
		 * Class name of the Site_Fields_Base subclass that merges a site's tagged group
		 * into this component's fields.
		 *
		 * @return string
		 */
		public static function site_fields_class(): string;

		/**
		 * Class name of this component's dedicated ACF field type, or null when it has
		 * none. A field type lets a site builder drop the component straight into their
		 * own custom layout instead of using a Clone field - see Buttons_Field_Type for
		 * why that matters.
		 *
		 * @return string|null
		 */
		public static function field_type_class(): ?string;

		/**
		 * The component's own fields to offer as conditional-logic targets in the field
		 * group editor. Usually the same as get_base_fields() run through filter_tag(),
		 * but a component whose fields are nested (Buttons wraps its in a repeater)
		 * returns the inner, conditionable ones instead. Empty array to offer nothing.
		 *
		 * @return array
		 */
		public static function conditional_logic_fields(): array;

		/**
		 * Heading this component's fields appear under in the conditional-logic field
		 * picker.
		 *
		 * Deliberately separate from label(): the two already differ today ("Buttons"
		 * here vs "Buttons Repeater" in the location dropdown, "Section Intro" here vs
		 * "Intro" there), and collapsing them onto one string would silently rewrite
		 * copy in a UI nobody asked to change.
		 *
		 * @return string
		 */
		public static function conditional_logic_label(): string;

	}
