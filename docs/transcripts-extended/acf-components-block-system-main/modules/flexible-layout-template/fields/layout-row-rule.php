<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Layout_Row_Rule
	 *
	 * Registers a "Flexible Layout Row" ACF location rule (grouped under Forms,
	 * alongside Location_Rule) that lets a site builder add a field to one specific
	 * flexible layout - or to every layout, present and future - without building a
	 * whole parallel flexible-content structure of their own the way tagging a group
	 * "Flexible Layout = Page Content" (Location_Rule/Site_Layouts) requires. That
	 * mechanism stays exactly as it was for sites already using it, or for adding a
	 * brand new layout wholesale; this one is for adding a single field to layouts
	 * that already exist.
	 *
	 * Purely a tag, like Location_Rule - it never matches a real edit screen, since a
	 * tagged group's fields are merged into the relevant layout's own sub_fields by
	 * Common_Fields::inject_common_fields() instead of being displayed on their own.
	 *
	 * @version 1.0.24
	 * @since   1.0.24
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Layout_Row_Rule extends \ACF_Location {

		const RULE_NAME = 'erdc_flexible_layout_row';

		/**
		 * Value stored against this rule when a site tags its field group to add a field
		 * to every flexible layout, present and future, rather than one specific layout.
		 */
		const ALL = 'all';

		/**
		 * initialize function
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 */
		public function initialize() {
			$this->name = self::RULE_NAME;
			$this->label = __('Flexible Layout Row', 'acbs');
			$this->category = 'forms';
		}

		/**
		 * match function
		 *
		 * This rule never places a field group on a real edit screen - a tagged field
		 * group's fields are merged into the target layout(s)' own sub_fields instead,
		 * see Common_Fields::inject_common_fields().
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
		 * "All Flexible Layouts" first, followed by one entry per currently registered
		 * layout - computed via Page_Content::get_current_layouts(), the exact same
		 * pipeline register() itself uses to build the real page_sections field, so a
		 * renamed, added or removed layout is reflected here immediately with no caching
		 * layer of its own to go stale - including a layout added purely through the ACF
		 * admin UI (tagging a site's own group "Flexible Layout = Page Content"), not
		 * just one added via the `acbs/flexible_layout/layouts` code filter.
		 *
		 * @version 1.0.25
		 * @since   1.0.24
		 *
		 * @param array $rule
		 *
		 * @return array
		 */
		public function get_values($rule) {

			$values = [self::ALL => __('All Flexible Layouts', 'acbs')];

			foreach(Page_Content::get_current_layouts() as $layout) {

				if(empty($layout['name'])) {
					continue;
				}

				$values[$layout['name']] = $layout['label'] ?? $layout['name'];

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
