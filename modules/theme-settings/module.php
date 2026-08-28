<?php

	namespace ERDC\Modules\ThemeSettings;

	use ERDC\Core\Module_Base as Base_Module;
	use ERDC\Modules\ThemeSettings\Fields\Field_Group;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Module
	 *
	 * Registers the "Theme Settings" ACF options page (Settings > Theme Settings) and
	 * the field group shipped with it, so every site running this plugin gets the same
	 * baseline set of theme-wide settings.
	 *
	 * A site adds to or overrides these fields by tagging its own field group
	 * "Flexible Layout Component = Theme Settings", the same mechanism Page Header,
	 * Buttons and Intro use (see Fields\Site_Fields, merge()). A site's field sharing
	 * a `name` with one of ours replaces it in place; anything else is appended.
	 * Pointing a plain field group directly at this options page (no tag) still
	 * works too - a shared field name wins the same way, keeping older sites that
	 * already built these fields by hand working unchanged - but the plugin can then
	 * only drop its own copy rather than fold the site's field into position (see
	 * Fields\Site_Fields, remove_claimed()).
	 *
	 * @version 1.0.29
	 * @since   1.0.20
	 * @package ERDC\Modules\ThemeSettings
	 */
	class Module extends Base_Module {

		/**
		 * Menu slug of the options page - also the value ACF stores in an
		 * `options_page` location rule pointing at it.
		 */
		const MENU_SLUG = 'theme-settings';

		/**
		 * get_name function
		 *
		 * @version 1.0.20
		 * @since   1.0.20
		 * @return string
		 */
		public function get_name() {
			return 'theme-settings';
		}

		/**
		 * Constructor
		 */
		public function __construct() {

			parent::__construct();

			if(!function_exists('acf_add_options_page')) {
				return;
			}

			add_action('acf/init', [$this, 'register_options_page']);
			add_action('acf/init', [Field_Group::class, 'register']);

		}

		/**
		 * register_options_page function
		 *
		 * @version 1.0.20
		 * @since   1.0.20
		 */
		public function register_options_page() {

			acf_add_options_page([
				'page_title' => __('Theme Settings', 'erdc'),
				'menu_title' => __('Theme Settings', 'erdc'),
				'menu_slug' => self::MENU_SLUG,
				'parent_slug' => 'options-general.php',
				'capability' => 'manage_options',
				'redirect' => false,
			]);

		}

	}
