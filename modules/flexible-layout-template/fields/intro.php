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
	 * layout defining its own copy of Section Content.
	 *
	 * SECTION TITLE WAS REMOVED. The component used to carry a plain-text `section_title`
	 * beside the wysiwyg, rendered as an <h2> by parts/intro.php. It is gone: the heading
	 * now lives inside section_content, where the editor can choose its level and mark up
	 * a two-tone heading with the toolbar's brand colour button rather than hand-typing a
	 * <span> into a text input. One field, one place, and the level is no longer fixed at
	 * h2 for every row on the site.
	 *
	 * Registered generically from this declaration by
	 * Flexible_Layout_Components::register_all() rather than by a register() method here.
	 *
	 * A site can override or extend these fields by tagging its own field group with
	 * "Flexible Layout Component = Intro" (see Intro_Site_Fields) - a field named
	 * section_content replaces that field, and anything else is added alongside it. A
	 * layout can opt out of Intro entirely via the `acbs_disable_layout_intro` filter (see
	 * Common_Fields).
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
		 * Fixed field key of Intro's content field (see get_base_fields()) - referenced by
		 * anything that needs to compute a layout's Intro-derived key without a live
		 * lookup, since Common_Fields injects this field directly rather than through
		 * ACF's own clone-resolution pipeline (see Common_Fields::get_intro_fields() for
		 * why) and so there is no live clone field to resolve it from.
		 *
		 * SECTION_TITLE_KEY sat beside this one and is gone with the field. It had no
		 * caller left: its only consumer was Legacy_Migration\Intro_Migration, which went
		 * with the Elementor decoupling.
		 */
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
					'key' => self::SECTION_CONTENT_KEY,
					'label' => 'Section Content',
					'name' => 'section_content',
					'type' => 'wysiwyg',
					'wrapper' => [
						'class' => 'section-intro-end',
					],
					'tabs' => 'all',
					'toolbar' => 'basic',
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
			return __('Intro', 'acbs');
		}

		public static function filter_tag(): string {
			return 'acbs/intro/fields';
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
			return __('Section Intro', 'acbs');
		}

	}
