<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Buttons_Site_Fields
	 *
	 * Merges any field group a site has tagged with "Flexible Layout Component = Buttons
	 * Repeater" into the ROW FIELDS of the plugin's "buttons" repeater - not the field
	 * group itself - so a site can add or override what's available on each button (an
	 * icon, a target attribute, whatever) without editing the plugin. A site field named
	 * the same as one of the repeater's own row fields (button_text, button_link,
	 * button_style) replaces that row field in place; anything else is appended as a new
	 * row field. The repeater's own settings (its label, layout, min/max etc.) are never
	 * touched by this - only what is INSIDE each row.
	 *
	 * That "inside each row" part is the only reason this class carries any code at all:
	 * Site_Fields_Base merges into a flat top-level field list, so merge() is overridden
	 * here to locate the repeater first and merge into its sub_fields instead. Every
	 * other component (Intro, Page Header) inherits the base unchanged.
	 *
	 * Two consumers: merge() is used by Flexible_Layout_Components::register_all() for
	 * the "buttons" clone source group, and get_row_fields() is used by
	 * Buttons_Field_Type - the field type a site adds directly to its own custom layout
	 * instead of a Clone field (see that class for why) - which has no wrapping field
	 * group, just the row fields on their own.
	 *
	 * @version 1.0.27
	 * @since   1.0.28
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Buttons_Site_Fields extends Site_Fields_Base {

		/**
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return string
		 */
		public static function location_value(): string {
			return Component_Rule::BUTTONS;
		}

		/**
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return string
		 */
		public static function group_key(): string {
			return Buttons::GROUP_KEY;
		}

		/**
		 * merge function
		 *
		 * Overrides the base to redirect the merge one level down, into the repeater's
		 * own row fields. Everything else about the merge - which groups contribute, in
		 * what order, rekeying, replace-by-name-or-append - is the base's, unchanged.
		 *
		 * @version 1.0.27
		 * @since   1.0.28
		 *
		 * @param array $fields The plugin's own base fields (the "buttons" repeater).
		 *
		 * @return array
		 */
		public static function merge(array $fields): array {

			$repeater_index = self::find_repeater_index($fields);

			if($repeater_index === null) {
				return $fields;
			}

			$fields[$repeater_index]['sub_fields'] = parent::merge($fields[$repeater_index]['sub_fields'] ?? []);

			return $fields;

		}

		/**
		 * get_row_fields function
		 *
		 * The button row fields on their own - base plus any site override/append -
		 * without needing a whole "buttons" field group wrapped around them. This is what
		 * Buttons_Field_Type uses: it has no field group of its own to merge(), just a
		 * single repeater field that needs its row fields.
		 *
		 * Rebuilds the base the same way Flexible_Layout_Components::register_all() does
		 * (base fields through the `erdc/buttons/fields` filter) rather than reading
		 * Buttons::GROUP_KEY back out of ACF, so this works identically whether or not
		 * that field group has registered yet.
		 *
		 * @version 1.0.27
		 * @since   1.0.24
		 * @return array
		 */
		public static function get_row_fields() {

			$fields = apply_filters('erdc/buttons/fields', Buttons::get_base_fields());
			$repeater_index = self::find_repeater_index($fields);

			if($repeater_index === null) {
				return [];
			}

			return parent::merge($fields[$repeater_index]['sub_fields'] ?? []);

		}

		/**
		 * find_repeater_index function
		 *
		 * Matched by key rather than assumed to be the only (or first) top-level field,
		 * so this keeps working if the group's field list is ever extended above the
		 * repeater itself.
		 *
		 * @version 1.0.29
		 * @since   1.0.29
		 *
		 * @param array $fields
		 *
		 * @return int|string|null
		 */
		private static function find_repeater_index(array $fields) {

			foreach($fields as $index => $field) {

				if(($field['key'] ?? '') === Buttons::REPEATER_KEY) {
					return $index;
				}

			}

			return null;

		}

	}
