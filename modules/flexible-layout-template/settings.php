<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate;

	use ACBS\Modules\FlexibleLayoutTemplate\Fields\Page_Content;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Settings
	 *
	 * Adds the "Disable Default Flexible Layouts" section to the plugin's Flexible
	 * Content tab: a checkbox per layout the plugin ships, letting a site drop whichever
	 * ones it doesn't want without touching this file.
	 *
	 * Ticking a layout here only stops the PLUGIN'S OWN copy of it from being added -
	 * a site can still add its own field group with a layout of the same name (tagged
	 * "Flexible Layout = Page Content" or otherwise), and it is unaffected by this list
	 * since Page_Content removes the disabled layout before anything from a site is
	 * merged in (see Page_Content::remove_disabled_layouts()).
	 *
	 * @version 1.0.30
	 * @since   1.0.30
	 * @package ACBS\Modules\FlexibleLayoutTemplate
	 */
	class Settings {

		/**
		 * Option holding the disabled layout names.
		 */
		const DISABLED_OPTION = 'erdc_disabled_flexible_layouts';

		/**
		 * Tab this section is added to, owned by ACBS\Modules\Settings.
		 */
		const TAB_ID = 'erdc-flexible-content';

		/**
		 * Section id within that tab.
		 */
		const SECTION_ID = 'erdc_disabled_flexible_layouts';

		/**
		 * get_disabled function
		 *
		 * @version 1.0.30
		 * @since   1.0.30
		 * @return array Layout names (the `name` property, not the label).
		 */
		public static function get_disabled() {

			$stored = get_option(self::DISABLED_OPTION, []);

			// An unticked list posts nothing at all, which WordPress stores as an empty
			// string rather than an empty array.
			return is_array($stored) ? $stored : [];

		}

		/**
		 * register function
		 *
		 * Hooked after ACBS\Modules\Settings has created the tab.
		 *
		 * @version 1.0.30
		 * @since   1.0.30
		 *
		 * @param \ACBS\Core\Admin\Settings_Page $settings
		 */
		public static function register($settings) {

			$settings->add_section(self::TAB_ID, self::SECTION_ID, [
				'callback' => [__CLASS__, 'render_section_heading'],
				'fields' => [],
			]);

			$settings->add_field(self::TAB_ID, self::SECTION_ID, 'disabled_flexible_layouts', [
				'label' => esc_html__('Disable Default Flexible Layouts', 'erdc'),
				'full_field_id' => self::DISABLED_OPTION,
				'render' => [__CLASS__, 'render_layout_list'],
				// Carried through to field_args and from there to the field's <tr>, via WP
				// core's do_settings_fields(). Elementor's page gated on this top-level key
				// and appended the one below, so both were set; Settings_Page accepts either.
				'class' => 'erdc-disabled-layouts-row',
				'field_args' => [
					'type' => 'erdc_disabled_layouts',
					'class' => 'erdc-disabled-layouts-row',
				],
				'setting_args' => [
					'type' => 'array',
					'sanitize_callback' => [__CLASS__, 'sanitise_disabled'],
				],
			]);

		}

		/**
		 * render_section_heading function
		 *
		 * @version 1.0.30
		 * @since   1.0.30
		 */
		public static function render_section_heading() {
			?>
			<hr>
			<h2><?php esc_html_e('Disable Default Flexible Layouts', 'erdc'); ?></h2>
			<p class="e-experiment__description"><?php esc_html_e("Check the default flexible layouts you'd like to not include in your site. You can still add them yourself via the ACF Add Field Group page.", 'erdc'); ?></p>
			<?php
		}

		/**
		 * render_layout_list function
		 *
		 * @version 1.0.30
		 * @since   1.0.30
		 */
		public static function render_layout_list() {

			$disabled = self::get_disabled();
			$name = self::DISABLED_OPTION;

			?>
			<style>
				/* Scoped to this one settings row via the field's own <tr> class (see
				   register()), rather than a bare .form-table selector that would also
				   widen every other field's label/value gap on the page. */
				tr.erdc-disabled-layouts-row > td {
					padding-left: 24px;
				}
			</style>
			<div class="erdc-disabled-flexible-layouts">

				<?php foreach(self::get_available_layouts() as $layout_name => $label) : ?>
					<label style="display:block;margin:0 0 8px">
						<input type="checkbox"
							name="<?php echo esc_attr($name); ?>[]"
							value="<?php echo esc_attr($layout_name); ?>"
							<?php checked(in_array($layout_name, $disabled, true)); ?> />
						<?php echo esc_html($label); ?>
						<code style="opacity:.6"><?php echo esc_html($layout_name); ?></code>
					</label>
				<?php endforeach; ?>

			</div>
			<?php

		}

		/**
		 * get_available_layouts function
		 *
		 * @version 1.0.30
		 * @since   1.0.30
		 * @return array Layout name => label, in the plugin's own default order.
		 */
		public static function get_available_layouts() {

			$layouts = [];

			foreach(Page_Content::get_base_layouts() as $layout) {
				$layouts[$layout['name']] = $layout['label'];
			}

			return $layouts;

		}

		/**
		 * sanitise_disabled function
		 *
		 * @version 1.0.30
		 * @since   1.0.30
		 *
		 * @param mixed $value
		 *
		 * @return array
		 */
		public static function sanitise_disabled($value) {

			if(!is_array($value)) {
				return [];
			}

			$allowed = array_keys(self::get_available_layouts());

			return array_values(array_intersect(array_map('sanitize_key', $value), $allowed));

		}

	}
