<?php

	namespace ERDC\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Other_Settings
	 *
	 * Declares the "Other Settings Fields - ERDC" component. Like Intro, its field group
	 * is never shown on a post edit screen itself - it exists purely as a source of fields
	 * that Common_Fields injects, already flattened, into every flexible layout's "Other
	 * Settings" tab (see Common_Fields::get_common_fields()), instead of each layout
	 * defining its own copies of Section BG Colour/Section ID/Vertical Padding.
	 *
	 * Registered generically from this declaration by
	 * Flexible_Layout_Components::register_all() rather than by a register() method here.
	 *
	 * A site can override or extend these fields by tagging its own field group with
	 * "Flexible Layout Component = Other Settings" (see Other_Settings_Site_Fields) - a
	 * field named the same as one of section_bg/section_container_id/vertical_padding/
	 * vertical_padding_mobile replaces that field, and anything else is added alongside
	 * them.
	 *
	 * @version 1.0.28
	 * @since   1.0.28
	 * @package ERDC\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Other_Settings implements Flexible_Layout_Component {

		/**
		 * Field group key.
		 */
		const GROUP_KEY = 'group_98d051483ff9a';

		/**
		 * get_base_fields function
		 *
		 * The "Other Settings" tab and the fields under it, shared by every layout -
		 * migrated verbatim (same keys, so a site's existing saved values are unaffected)
		 * from what used to be Common_Fields::get_common_fields()'s own hardcoded array.
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 * @return array
		 */
		public static function get_base_fields(): array {

			return [
				[
					'key' => 'field_98d0514837ff9',
					'label' => 'Other Settings',
					'name' => '',
					'type' => 'tab',
					'placement' => 'top',
				],
				[
					'key' => 'field_451702fc6e09d',
					'label' => 'Section BG Colour',
					'name' => 'section_bg',
					'type' => 'button_group',
					'choices' => [
						'default' => 'Default (transparent)',
						'white' => 'White',
						'light' => 'Light',
						'lighter' => 'Lighter',
						'primary' => 'Primary',
						'dark' => 'Dark',
						'darker' => 'Darker',
					],
					'default_value' => 'darker',
					'return_format' => 'string',
				],
				[
					'key' => 'field_92902ad19c483',
					'label' => 'Section ID',
					'name' => 'section_container_id',
					'type' => 'text',
				],
				[
					'key' => 'field_9db9148a8830b',
					'label' => 'Vertical Padding',
					'name' => 'vertical_padding',
					'type' => 'select',
					'choices' => [
						'default' => 'Default',
						'sm' => 'Small',
						'lg' => 'Large',
						'bottom' => 'Bottom Padding Only',
						'top' => 'Top Padding Only',
						'top-sm' => 'Small Top Padding, Default Bottom Padding',
						'bottom-sm' => 'Small Bottom Padding, Default Top Padding',
						'none' => 'No Padding',
					],
					'default_value' => 'default',
					'return_format' => 'value',
				],
				[
					'key' => 'field_38b835071ff93',
					'label' => 'Vertical Padding (Mobile)',
					'name' => 'vertical_padding_mobile',
					'type' => 'select',
					'choices' => [
						'default' => 'Default',
						'sm' => 'Small',
						'lg' => 'Large',
						'bottom' => 'Bottom Padding Only',
						'top' => 'Top Padding Only',
						'top-sm' => 'Small Top Padding, Default Bottom Padding',
						'bottom-sm' => 'Small Bottom Padding, Default Top Padding',
						'none' => 'No Padding',
					],
					'default_value' => 'default',
					'return_format' => 'value',
				],
			];

		}

		/**
		 * Flexible_Layout_Component implementation
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 */
		public static function group_key(): string {
			return self::GROUP_KEY;
		}

		public static function group_title(): string {
			return 'Other Settings Fields - ERDC';
		}

		public static function location_value(): string {
			return Component_Rule::OTHER_SETTINGS;
		}

		public static function label(): string {
			return __('Other Settings', 'erdc');
		}

		public static function filter_tag(): string {
			return 'erdc/other_settings/fields';
		}

		public static function site_fields_class(): string {
			return Other_Settings_Site_Fields::class;
		}

		public static function field_type_class(): ?string {
			return null;
		}

		/**
		 * Other Settings' fields are already flat, so the conditionable ones are simply
		 * its own - the tab among them is dropped downstream by
		 * Conditional_Logic::is_usable_as_condition().
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 * @return array
		 */
		public static function conditional_logic_fields(): array {
			return Flexible_Layout_Components::get_filtered_base_fields(self::class);
		}

		/**
		 * @version 1.0.28
		 * @since   1.0.28
		 * @return string
		 */
		public static function conditional_logic_label(): string {
			return __('Other Settings', 'erdc');
		}

	}
