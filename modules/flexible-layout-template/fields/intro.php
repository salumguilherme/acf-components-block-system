<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Intro
	 *
	 * Declares the "Intro Section Fields - ERDC" component. Like Buttons, its field group
	 * is never shown on a post edit screen itself - it exists purely as a source of fields that
	 * Common_Fields injects, already flattened and individually key-prefixed, into
	 * every flexible layout (see Common_Fields::get_intro_fields()), instead of each
	 * layout defining its own copies of Section Title/Content.
	 *
	 * Registered generically from this declaration by
	 * Flexible_Layout_Components::register_all() rather than by a register() method here.
	 *
	 * A site can override or extend these fields by tagging its own field group with
	 * "Flexible Layout Component = Intro" (see Intro_Site_Fields) - a field named the same
	 * as section_title/section_content replaces that field, and anything else is added
	 * alongside them. A layout can opt out of Intro entirely via the
	 * `erdc_disable_layout_intro` filter (see Common_Fields).
	 *
	 * @version 1.0.23
	 * @since   1.0.22
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Intro implements Flexible_Layout_Component {

		/**
		 * Field group key.
		 */
		const GROUP_KEY = 'group_6a84117e75d55';

		/**
		 * Fixed field keys of Intro's own title/content fields (see get_base_fields()) -
		 * referenced by anything that needs to compute a layout's Intro-derived key
		 * without a live lookup, since Common_Fields injects these fields directly rather
		 * than through ACF's own clone-resolution pipeline (see
		 * Common_Fields::get_intro_fields() for why) and so there is no live clone field
		 * to resolve them from. Used by Legacy_Migration\Intro_Migration.
		 */
		const SECTION_TITLE_KEY = 'field_6a83a9ad19c5a';
		const SECTION_CONTENT_KEY = 'field_6a83af53e2a15';

		/**
		 * get_base_fields function
		 *
		 * @version 1.0.22
		 * @since   1.0.22
		 * @return array
		 */
		public static function get_base_fields(): array {

			return [
				[
					'key' => 'field_6a8416008b38c',
					'label' => 'Intro',
					'name' => '',
					'type' => 'tab',
					'placement' => 'top',
				],
				[
					'key' => self::SECTION_TITLE_KEY,
					'label' => 'Section Title',
					'name' => 'section_title',
					'type' => 'text',
				],
				[
					'key' => self::SECTION_CONTENT_KEY,
					'label' => 'Section Content',
					'name' => 'section_content',
					'type' => 'wysiwyg',
					'wrapper' => [
						'class' => 'section-intro-end',
					],
					'tabs' => 'all',
					'toolbar' => 'full',
					'media_upload' => 1,
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
			return 'Intro Section Fields - ERDC';
		}

		public static function location_value(): string {
			return Component_Rule::INTRO;
		}

		public static function label(): string {
			return __('Intro', 'erdc');
		}

		public static function filter_tag(): string {
			return 'erdc/intro/fields';
		}

		public static function site_fields_class(): string {
			return Intro_Site_Fields::class;
		}

		public static function field_type_class(): ?string {
			return Intro_Field_Type::class;
		}

		/**
		 * Intro's fields are already flat, so the conditionable ones are simply its own -
		 * the tab among them is dropped downstream by
		 * Conditional_Logic::is_usable_as_condition().
		 *
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return array
		 */
		public static function conditional_logic_fields(): array {
			return Flexible_Layout_Components::get_filtered_base_fields(self::class);
		}

		/**
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return string
		 */
		public static function conditional_logic_label(): string {
			return __('Section Intro', 'erdc');
		}

	}
