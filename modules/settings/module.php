<?php

	namespace ERDC\Modules\Settings;

	use ERDC\Core\Module_Base as Base_Module;
	use ERDC\Core\Admin\Settings_Page;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Module
	 *
	 * Owns the plugin's admin settings page - a submenu of ACF's own menu - and creates
	 * the "Flexible Content" tab that every other module registers its sections into.
	 *
	 * This used to add a tab to Elementor's Settings page. The tab id, the section ids
	 * and the registrants' priorities are all unchanged, so only the hook name moved:
	 * `elementor/admin/after_create_settings/elementor` became `acbs/admin/settings`.
	 *
	 * @version 1.0.0
	 * @since   1.0.11
	 * @package ERDC\Modules\Settings
	 */
	class Module extends Base_Module {

		/**
		 * Tab ID on the plugin's settings page.
		 */
		const TAB_ID = 'erdc-flexible-content';

		/**
		 * get_name function
		 *
		 * @version 1.0.11
		 * @since   1.0.11
		 * @return string
		 */
		public function get_name() {
			return 'settings';
		}

		/**
		 * Constructor
		 */
		public function __construct() {

			parent::__construct();

			// Builds the page itself
			Settings_Page::instance()->register();

			// Priority 20 creates the tab before the sections that register at 21, and well
			// before Layouts_Export at 30 - the same ordering the Elementor page relied on.
			add_action('acbs/admin/settings', [$this, 'register_tab'], 20);

		}

		/**
		 * register_tab function
		 *
		 * @version 1.0.11
		 * @since   1.0.11
		 *
		 * @param Settings_Page $settings
		 */
		public function register_tab($settings) {

			$settings->add_tab(self::TAB_ID, [
				'label' => esc_html__('Flexible Content', 'erdc'),
				'sections' => [
					'intro' => [
						'callback' => [$this, 'render_intro_section'],
						'fields' => [],
					],
				],
			]);

		}

		/**
		 * render_intro_section function
		 *
		 * @version 1.0.11
		 * @since   1.0.11
		 */
		public function render_intro_section() {
			?>
			<h2><?php esc_html_e('Flexible Content', 'erdc'); ?></h2>
			<p class="e-experiment__description"><?php esc_html_e('Configure the Flexible Content settings for this site here.', 'erdc'); ?></p>
			<hr>
			<?php
		}

	}
