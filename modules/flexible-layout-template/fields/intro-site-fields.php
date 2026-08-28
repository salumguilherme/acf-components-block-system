<?php

	namespace ERDC\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Intro_Site_Fields
	 *
	 * Merges any field group a site has tagged with "Flexible Layout Component = Intro"
	 * into the plugin's Intro Section Fields - a site field named the same as one of
	 * section_title/section_content replaces it in place, anything else is appended.
	 *
	 * Intro's own fields (Tab, Section Title, Section Content) are already top-level, so
	 * the inherited merge() applies to them directly with nothing to override - unlike
	 * Buttons_Site_Fields, whose fields live inside a repeater's sub_fields.
	 *
	 * @version 1.0.27
	 * @since   1.0.22
	 * @package ERDC\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Intro_Site_Fields extends Site_Fields_Base {

		/**
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return string
		 */
		public static function location_value(): string {
			return Component_Rule::INTRO;
		}

		/**
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return string
		 */
		public static function group_key(): string {
			return Intro::GROUP_KEY;
		}

	}
