<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Grid_Display_Site_Fields
	 *
	 * Merges any field group a site has tagged with "Flexible Layout Component = Grid &
	 * Display" into the plugin's Grid & Display fields - a site field named the same as
	 * one of the layout_* fields replaces it in place, anything else is appended.
	 *
	 * Note the two-step: this changes what the FIELDS are, while
	 * `acbs/grid_display/layout_fields` changes which of them a given layout takes. A site
	 * adding a field here still has to name it in that filter for any layout to show it.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Grid_Display_Site_Fields extends Site_Fields_Base {

		/**
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public static function location_value(): string {
			return Component_Rule::GRID_DISPLAY;
		}

		/**
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public static function group_key(): string {
			return Grid_Display::GROUP_KEY;
		}

	}
