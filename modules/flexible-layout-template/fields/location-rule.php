<?php

	namespace ERDC\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Location_Rule
	 *
	 * Registers a "Flexible Layout" ACF location rule (grouped under Forms) that a
	 * site builder can use to tag their own field group as a source of extra
	 * page_sections layouts. It is purely a tag - it never matches a real edit screen,
	 * since a tagged field group's content is merged into page_sections by Site_Layouts
	 * rather than displayed on its own.
	 *
	 * Used to also carry values for Page Header, Buttons and Intro - none of which are
	 * themselves a flexible layout - until those moved to their own rule, Component_Rule,
	 * so this dropdown no longer mixes "a source of layouts" with "an override for one
	 * specific component". See Component_Rule for the PAGE_HEADER/BUTTONS/INTRO values
	 * a site tagged before that move, which must be re-tagged under the new rule.
	 *
	 * @version 1.0.24
	 * @since   1.0.7
	 * @package ERDC\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Location_Rule extends \ACF_Location {

		const RULE_NAME = 'erdc_flexible_layout';

		/**
		 * Value stored against this rule when a site tags its field group as a source of
		 * Page Content layouts.
		 *
		 * This is an OPAQUE TOKEN, not a live field group key, and must not be changed.
		 * It happens to read like the Page Content group's original key because that is
		 * what it once was, and every site that has already tagged a group has this exact
		 * string saved in its database. The Page Content group key has since been
		 * regenerated (see Page_Content) and the two are now deliberately independent, so
		 * that regenerating keys again can never invalidate a site's saved rule.
		 */
		const PAGE_CONTENT = 'group_6a0aa59adeb43';

		/**
		 * initialize function
		 *
		 * @version 1.0.7
		 * @since   1.0.7
		 */
		public function initialize() {
			$this->name = self::RULE_NAME;
			$this->label = __('Flexible Layout', 'erdc');
			$this->category = 'forms';
		}

		/**
		 * match function
		 *
		 * This rule never places a field group on a real edit screen - a tagged field
		 * group's layouts are merged into page_sections instead, see Site_Layouts::merge().
		 *
		 * @version 1.0.7
		 * @since   1.0.7
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
		 * Page Header, Buttons and Intro moved to Component_Rule, since none of them are
		 * themselves a flexible layout - Page Content is what this rule actually is.
		 *
		 * @version 1.0.24
		 * @since   1.0.7
		 *
		 * @param array $rule
		 *
		 * @return array
		 */
		public function get_values($rule) {
			return [
				self::PAGE_CONTENT => 'Page Content',
			];
		}

		/**
		 * register function
		 *
		 * @version 1.0.7
		 * @since   1.0.7
		 */
		public static function register() {
			acf_register_location_type(__CLASS__);
		}

	}
