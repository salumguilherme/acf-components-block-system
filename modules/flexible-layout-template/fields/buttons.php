<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Buttons
	 *
	 * Declares the "Buttons" component: the "buttons" repeater every flexible layout can
	 * embed. Its field group is never shown on a post edit screen itself - it exists purely
	 * as a clone source - and is registered generically from this declaration by
	 * Flexible_Layout_Components::register_all() rather than by a register() method here.
	 *
	 * A site can override or extend the fields available on EACH BUTTON ROW by tagging
	 * its own field group with "Flexible Layout Component = Buttons Repeater" (see
	 * Buttons_Site_Fields) - a field named the same as one of
	 * button_text/button_link/button_style replaces that row field, and anything else is
	 * added as a new row field alongside them. The repeater itself (its label, layout,
	 * min/max etc.) is never touched by this - only what is INSIDE each row.
	 *
	 * @version 1.0.27
	 * @since   1.0.6
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Buttons implements Flexible_Layout_Component {

		/**
		 * Field group key, referenced by every layout that clones this group.
		 *
		 * Titled "Buttons - ERDC" so it is distinguishable from the plain "Buttons"
		 * group older sites already have in their database. Both can coexist: unrelated
		 * field groups on those sites may still clone theirs, so it is never removed.
		 */
		const GROUP_KEY = 'group_b99bcf0767134';

		/**
		 * Key of the "buttons" repeater - the field a site's own contributed fields are
		 * merged into (as row fields), not the field group itself.
		 */
		const REPEATER_KEY = 'field_59bdb88314690';

		/**
		 * get_base_fields function
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 * @return array
		 */
		public static function get_base_fields(): array {

			return [
				[
					'key' => self::REPEATER_KEY,
					'label' => 'Buttons',
					'name' => 'buttons',
					'type' => 'repeater',
					'instructions' => '',
					'required' => 0,
					'layout' => 'row',
					'pagination' => 0,
					'min' => 0,
					'max' => 0,
					'collapsed' => '',
					'button_label' => '+ New Button',
					'rows_per_page' => 20,
					'sub_fields' => [
						[
							'key' => 'field_9ab0577ee568d',
							'label' => 'Button Text',
							'name' => 'button_text',
							'type' => 'text',
							'required' => 1,
						],
						[
							'key' => 'field_4c3bc60908296',
							'label' => 'Button Link',
							'name' => 'button_link',
							'type' => 'text',
							'required' => 1,
						],
						[
							'key' => 'field_7393b34321944',
							'label' => 'Button Style',
							'name' => 'button_style',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								'primary' => 'Primary',
								'secondary' => 'Secondary',
								'tertiary' => 'Tertiary',
								'primary-outline' => 'Primary Outline',
								'secondary-outline' => 'Secondary Outline',
								'tertiary-outline' => 'Tertiary Outline',
							],
							'default_value' => 'primary',
							'return_format' => 'value',
							'layout' => 'horizontal',
						]
					],
				],
			];

		}

		/**
		 * Flexible_Layout_Component implementation
		 *
		 * @version 1.0.27
		 * @since   1.0.27
		 */
		public static function group_key(): string {
			return self::GROUP_KEY;
		}

		public static function group_title(): string {
			return 'Buttons - ERDC';
		}

		public static function location_value(): string {
			return Component_Rule::BUTTONS;
		}

		public static function label(): string {
			return __('Buttons Repeater', 'erdc');
		}

		public static function filter_tag(): string {
			return 'erdc/buttons/fields';
		}

		public static function site_fields_class(): string {
			return Buttons_Site_Fields::class;
		}

		public static function field_type_class(): ?string {
			return Buttons_Field_Type::class;
		}

		/**
		 * get_base_row_fields function
		 *
		 * The plugin's OWN button row fields - the repeater's sub_fields, with the
		 * `erdc/buttons/fields` filter applied but WITHOUT any site-tagged override merged
		 * in. Deliberately not Buttons_Site_Fields::get_row_fields(): a site field
		 * replacing one of ours by name would otherwise take its place here too, and this
		 * is used to describe what the PLUGIN offers (see conditional_logic_fields()).
		 *
		 * Locates the repeater by key rather than assuming it is the first top-level
		 * field, matching Buttons_Site_Fields::find_repeater_index()'s reasoning - so this
		 * keeps working if the group's field list is ever extended above the repeater.
		 *
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return array
		 */
		public static function get_base_row_fields(): array {

			foreach(Flexible_Layout_Components::get_filtered_base_fields(self::class) as $field) {

				if(($field['key'] ?? '') === self::REPEATER_KEY) {
					return $field['sub_fields'] ?? [];
				}

			}

			return [];

		}

		/**
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return array
		 */
		public static function conditional_logic_fields(): array {
			return self::get_base_row_fields();
		}

		/**
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return string
		 */
		public static function conditional_logic_label(): string {
			return __('Buttons', 'erdc');
		}

	}
