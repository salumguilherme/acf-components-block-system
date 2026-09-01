<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Other_Settings_Site_Fields
	 *
	 * Merges any field group a site has tagged with "Flexible Layout Component = Other
	 * Settings" into the plugin's Other Settings Fields - a site field named the same as
	 * one of section_bg/section_container_id/vertical_padding/vertical_padding_xs
	 * replaces it in place, anything else is appended.
	 *
	 * Other Settings' own fields are already top-level, so the inherited merge() applies
	 * to them directly with nothing to override - same as Intro_Site_Fields.
	 *
	 * @version 1.0.28
	 * @since   1.0.28
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Other_Settings_Site_Fields extends Site_Fields_Base {

		/**
		 * @version 1.0.28
		 * @since   1.0.28
		 * @return string
		 */
		public static function location_value(): string {
			return Component_Rule::OTHER_SETTINGS;
		}

		/**
		 * @version 1.0.28
		 * @since   1.0.28
		 * @return string
		 */
		public static function group_key(): string {
			return Other_Settings::GROUP_KEY;
		}

	}
