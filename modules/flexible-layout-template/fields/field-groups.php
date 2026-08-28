<?php

	namespace ERDC\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Field_Groups
	 *
	 * Entry point for the plugin's local (PHP) ACF field group registrations, so the
	 * "Page Content" flexible layout structure ships with the plugin instead of living
	 * only in a given site's database.
	 *
	 * @version 1.0.6
	 * @since   1.0.6
	 * @package ERDC\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Field_Groups {

		/**
		 * register function
		 *
		 * @version 1.0.27
		 * @since   1.0.6
		 */
		public static function register() {
			Flexible_Layout_Components::register_all();
			Page_Content::register();
			Common_Fields::register();
		}

		/**
		 * register_location_rules function
		 *
		 * @version 1.0.7
		 * @since   1.0.7
		 */
		public static function register_location_rules() {
			Location_Rule::register();
			Layout_Row_Rule::register();
			Component_Rule::register();
			Conditional_Logic::register();
		}

		/**
		 * register_field_types function
		 *
		 * Registers the dedicated ACF field type of every Flexible_Layout_Component that
		 * has one, so a site builder can add it directly to a custom flexible layout
		 * instead of a Clone field. See Buttons_Field_Type's docblock for why: ACF's own
		 * field-group editor screen permanently materializes a seamless Clone resolving
		 * to a single field into a disconnected duplicate the moment it renders, and
		 * there is no reliable way to intercept that after the fact - so these field
		 * types avoid the Clone mechanism entirely for this use case.
		 *
		 * Hooked on `acf/include_field_types` at priority 20, after ACF Pro registers
		 * its own field types (including Repeater and Group, which these extend) at
		 * priority 5.
		 *
		 * @version 1.0.27
		 * @since   1.0.24
		 */
		public static function register_field_types() {
			Flexible_Layout_Components::register_field_types();
		}

	}
