<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Component_Rule
	 *
	 * Registers a "Flexible Layout Component" ACF location rule (grouped under Forms,
	 * alongside Location_Rule and Layout_Row_Rule) for the plugin components that are
	 * not themselves flexible layouts - Page Header, Buttons, Intro, Other Settings and
	 * Theme Settings - so they no longer share a dropdown with Page Content, which is
	 * one.
	 *
	 * This rule's values (PAGE_HEADER, BUTTONS, INTRO) are the same opaque tokens that
	 * used to live on Location_Rule, moved here rather than duplicated: a site that
	 * already tagged a group "Flexible Layout = Buttons" before this rule existed has
	 * that exact string saved in its database and must retag it as "Flexible Layout
	 * Component = Buttons Repeater" to keep it working, the same way any other location
	 * rule change requires re-tagging.
	 *
	 * Purely a tag, like Location_Rule and Layout_Row_Rule - it never matches a real
	 * edit screen, since a tagged group's fields are merged into the relevant
	 * component's own fields by Buttons_Site_Fields, Intro_Site_Fields,
	 * PageHeader\Fields\Site_Fields or ThemeSettings\Fields\Site_Fields instead of
	 * being displayed on their own.
	 *
	 * @version 1.0.29
	 * @since   1.0.24
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Component_Rule extends \ACF_Location {

		const RULE_NAME = 'erdc_flexible_layout_component';

		/**
		 * Value stored against this rule when a site tags its field group as an override
		 * for the Page Header fields.
		 */
		const PAGE_HEADER = 'page_header';

		/**
		 * Value stored against this rule when a site tags its field group as an override
		 * for the Buttons clone field group.
		 */
		const BUTTONS = 'buttons';

		/**
		 * Value stored against this rule when a site tags its field group as an override
		 * for the Intro Section Fields clone field group.
		 */
		const INTRO = 'section_intro';

		/**
		 * Value stored against this rule when a site tags its field group as an override
		 * for the Other Settings Fields clone field group.
		 */
		const OTHER_SETTINGS = 'other_settings';

		/**
		 * Grid & Display - how a row lays out and whether its content sits in a card.
		 */
		const GRID_DISPLAY = 'grid_display';

		/**
		 * Value stored against this rule when a site tags its field group as an override
		 * for the Theme Settings options page field group.
		 */
		const THEME_SETTINGS = 'theme_settings';

		/**
		 * initialize function
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 */
		public function initialize() {
			$this->name = self::RULE_NAME;
			$this->label = __('Flexible Layout Component', 'acbs');
			$this->category = 'forms';
		}

		/**
		 * match function
		 *
		 * This rule never places a field group on a real edit screen - a tagged field
		 * group's fields are merged into the relevant component's own fields instead.
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 *
		 * @param array $rule
		 * @param array $screen
		 * @param array $field_group
		 *
		 * @return bool
		 */
		public function match($rule, $screen, $field_group) {
			return false;
		}

		/**
		 * get_values function
		 *
		 * Same value strings as Location_Rule's original PAGE_HEADER/BUTTONS/INTRO
		 * carried, unchanged - only their rule and dropdown labels move. Buttons' label
		 * becomes "Buttons Repeater" to match the field type of the same name.
		 *
		 * The component entries come from Flexible_Layout_Components::MODULES rather than
		 * being listed here, so registering a new component adds it to this dropdown with
		 * no edit to this file.
		 *
		 * @version 1.0.29
		 * @since   1.0.24
		 *
		 * @param array $rule
		 *
		 * @return array
		 */
		public function get_values($rule) {

			// Page Header and Theme Settings are not Flexible_Layout_Components (see that
			// interface), so they stay literal entries - the same way Page Content does on
			// Location_Rule.
			$values = [
				self::PAGE_HEADER => __('Page Header', 'acbs'),
				self::THEME_SETTINGS => __('Theme Settings', 'acbs'),
			];

			foreach(Flexible_Layout_Components::MODULES as $component) {
				$values[$component::location_value()] = $component::label();
			}

			return $values;

		}

		/**
		 * register function
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 */
		public static function register() {
			acf_register_location_type(__CLASS__);
		}

	}
