<?php

	namespace ACBS\Modules\AcfCopyToClipboard;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Settings
	 *
	 * Adds the "ACF Copy to clipboard" section to the plugin's Flexible Content tab
	 * a single switch for the whole module.
	 *
	 * @version 1.0.28
	 * @since   1.0.28
	 * @package ACBS\Modules\AcfCopyToClipboard
	 */
	class Settings {

		/**
		 * Option holding the on/off switch. Absent means enabled, so both a fresh
		 * install and an update from a version predating this setting default to on
		 * without anything needing to be seeded.
		 */
		const ENABLED_OPTION = 'erdc_acf_copy_to_clipboard_enabled';

		/**
		 * Tab this section is added to, owned by ACBS\Modules\Settings.
		 */
		const TAB_ID = 'erdc-flexible-content';

		/**
		 * Section id within that tab.
		 */
		const SECTION_ID = 'erdc_acf_copy_to_clipboard';

		/**
		 * is_enabled function
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 * @return bool
		 */
		public static function is_enabled() {
			return 'yes' === get_option(self::ENABLED_OPTION, 'yes');
		}

		/**
		 * register function
		 *
		 * Hooked after ACBS\Modules\Settings has created the tab.
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 *
		 * @param \ACBS\Core\Admin\Settings_Page $settings
		 */
		public static function register($settings) {

			$settings->add_section(self::TAB_ID, self::SECTION_ID, [
				'callback' => [__CLASS__, 'render_section_heading'],
				'fields' => [],
			]);

			$settings->add_field(self::TAB_ID, self::SECTION_ID, 'acf_copy_to_clipboard_enabled', [
				'label' => esc_html__('ACF Copy to clipboard', 'acbs'),
				'full_field_id' => self::ENABLED_OPTION,
				'field_args' => [
					'type' => 'checkbox',
					'value' => 'yes',
					'std' => 'yes',
					'sub_desc' => esc_html__('Show copy field name to clipboard icon on ACF fields', 'acbs'),
				],
			]);

		}

		/**
		 * render_section_heading function
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 */
		public static function render_section_heading() {
			?>
			<hr>
			<h2><?php esc_html_e('ACF Copy to clipboard', 'acbs'); ?></h2>
			<p class="e-experiment__description"><?php esc_html_e('Adds a small icon next to each ACF field, on post edit and options pages, that copies the field name to the clipboard.', 'acbs'); ?></p>
			<?php
		}

	}
