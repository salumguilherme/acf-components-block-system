<?php

	namespace ERDC\Modules\PageHeader\Fields;

	use ERDC\Modules\FlexibleLayoutTemplate\Fields\Component_Rule;
	use ERDC\Modules\FlexibleLayoutTemplate\Fields\Site_Fields_Base;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Site_Fields
	 *
	 * Merges any field group a site has tagged with "Flexible Layout Component = Page
	 * Header" into the plugin's own Page Header fields, so a site can override what ships
	 * here or add to it without editing the plugin. A site field sharing a `name` with
	 * one of ours replaces it in place, keeping field order; anything else is appended.
	 *
	 * The rule value is matched strictly against Component_Rule::PAGE_HEADER, so a group
	 * tagged for Page Content is never pulled in here, and vice versa.
	 *
	 * Page Header itself is deliberately NOT a Flexible_Layout_Component (its parent
	 * field group has a real per-site location, is directly editable, and bails out of
	 * registering when that location is empty - see Field_Group), but the site-override
	 * merge is identical regardless of how the parent group registers, so this shares
	 * Site_Fields_Base with the components that are.
	 *
	 * @version 1.0.27
	 * @since   1.0.22
	 * @package ERDC\Modules\PageHeader\Fields
	 */
	class Site_Fields extends Site_Fields_Base {

		/**
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return string
		 */
		public static function location_value(): string {
			return Component_Rule::PAGE_HEADER;
		}

		/**
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return string
		 */
		public static function group_key(): string {
			return Field_Group::GROUP_KEY;
		}

	}
