<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate;

	use ACBS\Modules\FlexibleLayoutTemplate\Fields\Page_Content;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Layouts_Export
	 *
	 * Adds a "Download Flexible Layouts" section to the bottom of the plugin's
	 * Flexible Content tab: a single button that downloads a JSON file describing
	 * every flexible layout currently available on this site.
	 *
	 * "Available" means the fully assembled set - the plugin's own default layouts
	 * (minus anything disabled via Settings), plus any layout or field a site has
	 * added purely through tagging its own ACF field group (Site_Layouts,
	 * Common_Fields' "Flexible Layout Row" merge). Rather than re-deriving that
	 * ourselves, this reads the live `page_sections` field back via acf_get_field() -
	 * the exact same field ACF hands the page_sections editor - so the export can
	 * never drift from what a site builder actually sees.
	 *
	 * @version 1.0.31
	 * @since   1.0.31
	 * @package ACBS\Modules\FlexibleLayoutTemplate
	 */
	class Layouts_Export {

		/**
		 * admin_post_ action name, also used as the nonce action.
		 */
		const ACTION = 'erdc_export_flexible_layouts';

		/**
		 * Tab this section is added to, owned by ACBS\Modules\Settings.
		 */
		const TAB_ID = 'erdc-flexible-content';

		/**
		 * Section id within that tab.
		 */
		const SECTION_ID = 'erdc_export_flexible_layouts';

		/**
		 * register function
		 *
		 * Hooked after every other section on the tab (priority 30, where the rest of
		 * the plugin registers at 20/21) purely so this ends up last - sections render
		 * in the order they were added, and there is no other ordering hook the settings
		 * page offers.
		 *
		 * @version 1.0.31
		 * @since   1.0.31
		 */
		public static function register() {

			add_action('acbs/admin/settings', [__CLASS__, 'register_section'], 30);
			add_action('admin_post_'.self::ACTION, [__CLASS__, 'handle_export']);

		}

		/**
		 * register_section function
		 *
		 * @version 1.0.31
		 * @since   1.0.31
		 *
		 * @param \ACBS\Core\Admin\Settings_Page $settings
		 */
		public static function register_section($settings) {

			$settings->add_section(self::TAB_ID, self::SECTION_ID, [
				'callback' => [__CLASS__, 'render_section'],
				'fields' => [],
			]);

		}

		/**
		 * render_section function
		 *
		 * Renders both the heading and the button in one callback (rather than via
		 * add_field(), which wraps everything in a <table class="form-table"> row
		 * meant for label/value settings pairs) - the whole page is already one big
		 * <form action="options.php">, so a plain link styled as a button is used
		 * instead of a nested <form> for the download itself.
		 *
		 * @version 1.0.31
		 * @since   1.0.31
		 */
		public static function render_section() {

			$url = wp_nonce_url(
				admin_url('admin-post.php?action='.self::ACTION),
				self::ACTION
			);

			?>
			<hr>
			<h2><?php esc_html_e('Download Flexible Layouts', 'acbs'); ?></h2>
			<p class="e-experiment__description">
				<?php esc_html_e('Download a JSON copy of every flexible layout available on this site - the layouts this plugin ships, plus any this site has added or extended through its own ACF field groups.', 'acbs'); ?>
			</p>
			<p>
				<a href="<?php echo esc_url($url); ?>" class="button"><?php esc_html_e('Download JSON', 'acbs'); ?></a>
			</p>
			<?php

		}

		/**
		 * handle_export function
		 *
		 * @version 1.0.31
		 * @since   1.0.31
		 */
		public static function handle_export() {

			if(!current_user_can('manage_options')) {
				wp_die(esc_html__('You are not allowed to do this.', 'acbs'), 403);
			}

			check_admin_referer(self::ACTION);

			$layouts = self::get_layouts();

			$filename = sanitize_file_name(get_bloginfo('name')).'-flexible-layouts-'.gmdate('Y-m-d').'.json';

			$data = [
				'site' => home_url(),
				'generated_at' => gmdate('c'),
				'plugin_version' => defined('ACBS_VERSION') ? ACBS_VERSION : null,
				'layouts' => $layouts,
			];

			nocache_headers();
			header('Content-Type: application/json; charset=utf-8');
			header('Content-Disposition: attachment; filename="'.$filename.'"');

			echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

			exit;

		}

		/**
		 * get_layouts function
		 *
		 * Reads the live `page_sections` field via acf_get_field(), so the export goes
		 * through ACF's own field-loading pipeline - including Common_Fields' injection
		 * of Intro, the "Content"/"Other Settings" tabs, and any "Flexible Layout Row"
		 * contributed fields - the exact structure a site builder sees, not a partial
		 * rebuild of it.
		 *
		 * Falls back to the unassembled layout set (still including anything a site has
		 * added via Site_Layouts, just without the common/injected fields) if ACF isn't
		 * active or the field somehow isn't registered yet.
		 *
		 * @version 1.0.31
		 * @since   1.0.31
		 *
		 * @return array
		 */
		private static function get_layouts() {

			if(function_exists('acf_get_field')) {

				$field = acf_get_field(Page_Content::FIELD_KEY);

				if(!empty($field['layouts'])) {
					return $field['layouts'];
				}

			}

			return Page_Content::get_current_layouts();

		}

	}
