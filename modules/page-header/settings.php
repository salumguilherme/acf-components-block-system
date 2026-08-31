<?php

	namespace ACBS\Modules\PageHeader;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Settings
	 *
	 * Adds the "Page Header" section to the plugin's Flexible Content tab: a switch for the whole module, and the list of post types and
	 * taxonomies to keep the field group away from.
	 *
	 * Note the list is a list of EXCLUSIONS. Nothing ticked means the Page Header shows
	 * everywhere it can, which is what a site wants by default; ticking a post type or
	 * taxonomy hides the field group there.
	 *
	 * @version 1.0.22
	 * @since   1.0.22
	 * @package ACBS\Modules\PageHeader
	 */
	class Settings {

		/**
		 * Option holding the on/off switch. Absent means enabled, so both a fresh install
		 * and an update from a version predating this setting default to on without
		 * anything needing to be seeded.
		 */
		const ENABLED_OPTION = 'erdc_page_header_enabled';

		/**
		 * Option holding the excluded post type and taxonomy names.
		 */
		const EXCLUSIONS_OPTION = 'erdc_page_header_exclusions';

		/**
		 * Tab this section is added to, owned by ACBS\Modules\Settings.
		 */
		const TAB_ID = 'erdc-flexible-content';

		/**
		 * Section id within that tab.
		 */
		const SECTION_ID = 'erdc_page_header';

		/**
		 * is_enabled function
		 *
		 * @version 1.0.22
		 * @since   1.0.22
		 * @return bool
		 */
		public static function is_enabled() {
			return 'yes' === get_option(self::ENABLED_OPTION, 'yes');
		}

		/**
		 * get_exclusions function
		 *
		 * @version 1.0.22
		 * @since   1.0.22
		 * @return array
		 */
		public static function get_exclusions() {

			$stored = get_option(self::EXCLUSIONS_OPTION, []);

			// An unticked list posts nothing at all, which WordPress stores as an empty
			// string rather than an empty array.
			return is_array($stored) ? $stored : [];

		}

		/**
		 * register function
		 *
		 * Hooked after ACBS\Modules\Settings has created the tab.
		 *
		 * @version 1.0.22
		 * @since   1.0.22
		 *
		 * @param \ACBS\Core\Admin\Settings_Page $settings
		 */
		public static function register($settings) {

			$settings->add_section(self::TAB_ID, self::SECTION_ID, [
				'callback' => [__CLASS__, 'render_section_heading'],
				'fields' => [],
			]);

			$settings->add_field(self::TAB_ID, self::SECTION_ID, 'page_header_enabled', [
				'label' => esc_html__('Page Header Fields', 'erdc'),
				'full_field_id' => self::ENABLED_OPTION,
				'field_args' => [
					'type' => 'checkbox',
					'value' => 'yes',
					'std' => 'yes',
					'sub_desc' => esc_html__('Enable the Page Header', 'erdc'),
				],
			]);

			$settings->add_field(self::TAB_ID, self::SECTION_ID, 'page_header_exclusions', [
				'label' => esc_html__('Hide Page Header On', 'erdc'),
				'full_field_id' => self::EXCLUSIONS_OPTION,
				'render' => [__CLASS__, 'render_exclusions'],
				'field_args' => [
					'type' => 'erdc_exclusions',
				],
				'setting_args' => [
					'type' => 'array',
					'sanitize_callback' => [__CLASS__, 'sanitise_exclusions'],
				],
			]);

		}

		/**
		 * render_section_heading function
		 *
		 * @version 1.0.22
		 * @since   1.0.22
		 */
		public static function render_section_heading() {
			?>
			<hr>
			<h2><?php esc_html_e('Page Header', 'erdc'); ?></h2>
			<p class="e-experiment__description"><?php esc_html_e('A base set of header fields for this site, which each site can extend or override through ACF.', 'erdc'); ?></p>
			<?php
		}

		/**
		 * render_exclusions function
		 *
		 * @version 1.0.22
		 * @since   1.0.22
		 */
		public static function render_exclusions() {

			$excluded = self::get_exclusions();
			$name = self::EXCLUSIONS_OPTION;

			?>
			<div class="erdc-page-header-exclusions"<?php echo self::is_enabled() ? '' : ' style="display:none"'; ?>>

				<p class="description" style="margin-bottom:8px">
					<?php esc_html_e('Tick anywhere the Page Header fields should NOT appear. Leave everything unticked to show them everywhere.', 'erdc'); ?>
				</p>

				<?php foreach(self::get_targets() as $heading => $items) : ?>

					<?php if(empty($items)) { continue; } ?>

					<p style="margin:10px 0 4px"><strong><?php echo esc_html($heading); ?></strong></p>

					<?php foreach($items as $value => $label) : ?>
						<label style="display:inline-block;min-width:220px;margin:0 12px 4px 0">
							<input type="checkbox"
								name="<?php echo esc_attr($name); ?>[]"
								value="<?php echo esc_attr($value); ?>"
								<?php checked(in_array($value, $excluded, true)); ?> />
							<?php echo esc_html($label); ?>
							<code style="opacity:.6"><?php echo esc_html($value); ?></code>
						</label>
					<?php endforeach; ?>

				<?php endforeach; ?>

			</div>
			<?php

		}

		/**
		 * get_targets function
		 *
		 * The post types and taxonomies the Page Header could be shown on, which is the
		 * same set Field_Group builds its location from.
		 *
		 * @version 1.0.22
		 * @since   1.0.22
		 * @return array
		 */
		public static function get_targets() {

			$post_types = [];
			$taxonomies = [];

			foreach(get_post_types(['public' => true], 'objects') as $post_type) {

				if('attachment' === $post_type->name) {
					continue;
				}

				$post_types[$post_type->name] = $post_type->label;

			}

			foreach(get_taxonomies(['public' => true], 'objects') as $taxonomy) {
				$taxonomies[$taxonomy->name] = $taxonomy->label;
			}

			return [
				esc_html__('Post Types', 'erdc') => $post_types,
				esc_html__('Taxonomies', 'erdc') => $taxonomies,
			];

		}

		/**
		 * sanitise_exclusions function
		 *
		 * @version 1.0.22
		 * @since   1.0.22
		 *
		 * @param mixed $value
		 *
		 * @return array
		 */
		public static function sanitise_exclusions($value) {

			if(!is_array($value)) {
				return [];
			}

			$allowed = [];

			foreach(self::get_targets() as $items) {
				$allowed = array_merge($allowed, array_keys($items));
			}

			return array_values(array_intersect(array_map('sanitize_key', $value), $allowed));

		}

	}
