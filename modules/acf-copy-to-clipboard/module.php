<?php

	namespace ACBS\Modules\AcfCopyToClipboard;

	use ACBS\Core\Module_Base as Base_Module;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Module
	 *
	 * Adds a small "copy field name to clipboard" icon next to every ACF field on
	 * post edit and options pages, so a developer can grab a field's name without
	 * having to open the field group editor to look it up.
	 *
	 * Deliberately hooked on `acf/input/admin_enqueue_scripts` (the same hook ACF
	 * itself uses to load its own input assets on post-edit and options screens) -
	 * that hook never fires on the ACF field group editor screen (which instead uses
	 * `acf/field_group/admin_enqueue_scripts`), so field group edit pages are
	 * excluded for free, without needing a screen check of our own.
	 *
	 * Switched on by default; see Settings for the "ACF Copy to clipboard" toggle on
	 * the plugin's Flexible Content tab.
	 *
	 * @version 1.0.28
	 * @since   1.0.28
	 * @package ACBS\Modules\AcfCopyToClipboard
	 */
	class Module extends Base_Module {

		/**
		 * get_name function
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 * @return string
		 */
		public function get_name() {
			return 'acf-copy-to-clipboard';
		}

		/**
		 * Constructor
		 */
		public function __construct() {

			parent::__construct();

			// The settings section is registered whether or not the module is switched
			// on, otherwise there would be no way to switch it back on. Priority 21 puts
			// it after ACBS\Modules\Settings creates the tab at 20.
			add_action('acbs/admin/settings', [Settings::class, 'register'], 21);

			if(!function_exists('acf_add_local_field_group')) {
				return;
			}

			add_action('acf/input/admin_enqueue_scripts', [$this, 'enqueue_assets']);

		}

		/**
		 * enqueue_assets function
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 */
		public function enqueue_assets() {

			if(!Settings::is_enabled()) {
				return;
			}

			wp_enqueue_style(
				'erdc-acf-copy-to-clipboard',
				ACBS_URL.'assets/css/acf-copy-to-clipboard.css',
				[],
				ACBS_VERSION
			);

			wp_enqueue_script(
				'erdc-acf-copy-to-clipboard',
				ACBS_URL.'assets/js/acf-copy-to-clipboard.js',
				['jquery', 'acf-input'],
				ACBS_VERSION,
				true
			);

			wp_localize_script('erdc-acf-copy-to-clipboard', 'erdcAcfCopyToClipboard', [
				'tooltip' => esc_html__('Copy field name to clipboard', 'erdc'),
			]);

		}

	}
