<?php

	namespace ERDC\Modules\PageHeader;

	use ERDC\Core\Module_Base as Base_Module;
	use ERDC\Modules\PageHeader\Fields\Field_Group;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Module
	 *
	 * Provides a baseline "Header" ACF field group across the site, in the same spirit as
	 * the Page Content module: the plugin ships a starting point that each site can
	 * override or extend, rather than something fixed.
	 *
	 * Two settings live on the plugin's Flexible Content tab (see Settings): a switch for
	 * the module, and the post types and taxonomies to keep the field group away from.
	 *
	 * Sites override the fields themselves by tagging a field group with the "Flexible
	 * Layout = Page Header" location rule, which is matched separately from Page Content
	 * so the two never pick up each other's groups.
	 *
	 * @version 1.0.22
	 * @since   1.0.22
	 * @package ERDC\Modules\PageHeader
	 */
	class Module extends Base_Module {

		/**
		 * get_name function
		 *
		 * @version 1.0.22
		 * @since   1.0.22
		 * @return string
		 */
		public function get_name() {
			return 'page-header';
		}

		/**
		 * Constructor
		 */
		public function __construct() {

			parent::__construct();

			// The settings section is registered whether or not the module is switched on,
			// otherwise there would be no way to switch it back on. Priority 21 puts it after
			// ERDC\Modules\Settings creates the tab at 20.
			add_action('acbs/admin/settings', [Settings::class, 'register'], 21);

			if(!function_exists('acf_add_local_field_group')) {
				return;
			}

			// Registered on `init` at a late priority rather than on `acf/init`, which fires
			// at priority 4-5 - before WooCommerce and most plugins and themes have declared
			// their post types and taxonomies at the default priority 10. Building the
			// location any earlier silently omits them, and would also let the settings
			// screen offer exclusions for targets the group never covered.
			add_action('init', [$this, 'register_field_group'], 20);

		}

		/**
		 * register_field_group function
		 *
		 * @version 1.0.22
		 * @since   1.0.22
		 */
		public function register_field_group() {

			if(!Settings::is_enabled()) {
				return;
			}

			Field_Group::register();

		}

	}
