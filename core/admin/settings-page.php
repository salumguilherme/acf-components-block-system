<?php

	namespace ACBS\Core\Admin;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Settings_Page
	 *
	 * The plugin's own admin settings page, as a submenu of ACF's top-level menu.
	 *
	 * This replaces Elementor's Settings page, which the plugin used to register five
	 * separate sections into. Rather than rewrite those five call sites, this exposes the
	 * same three method signatures Elementor's `\Elementor\Settings` did - add_tab(),
	 * add_section() and add_field() - so a registrant changes one hook name and nothing
	 * else. The hook is `acbs/admin/settings`, and the priorities the registrants use are
	 * load-bearing exactly as they were before: the tab is created at 20, sections at 21,
	 * and Layouts_Export at 30 so its download button renders last.
	 *
	 * Rendering goes through WordPress's own Settings API, so nonces, the capability
	 * check, sanitisation callbacks and the "Settings saved" notice are all core's rather
	 * than hand-rolled.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Core\Admin
	 */
	class Settings_Page {

		/**
		 * Menu slug of this page.
		 */
		const PAGE_SLUG = 'acbs-settings';

		/**
		 * ACF's own top-level menu, which this page hangs off.
		 */
		const PARENT_SLUG = 'edit.php?post_type=acf-field-group';

		/**
		 * Capability required to see and save the page.
		 */
		const CAPABILITY = 'manage_options';

		/**
		 * Registered tabs.
		 *
		 * tab_id => [ 'label' => string, 'sections' => [ section_id => [ 'callback' =>
		 * callable|null, 'fields' => [ field_id => args ] ] ] ]
		 *
		 * @var array
		 */
		private $tabs = [];

		/**
		 * Whether the `acbs/admin/settings` collection pass has already run.
		 *
		 * @var bool
		 */
		private $collected = false;

		/**
		 * @var Settings_Page|null
		 */
		private static $instance = null;

		/**
		 * instance function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return Settings_Page
		 */
		public static function instance() {

			if(is_null(self::$instance)) {
				self::$instance = new self();
			}

			return self::$instance;

		}

		/**
		 * register function
		 *
		 * Called once from the Settings module.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function register() {

			add_action('admin_menu', [$this, 'add_page']);
			add_action('admin_init', [$this, 'register_settings']);

		}

		/**
		 * collect function
		 *
		 * Fires the registration action once and caches the result. Both admin_menu and
		 * admin_init need the same tab structure, and a registrant that ran twice would
		 * register its settings twice.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		private function collect() {

			if($this->collected) {
				return;
			}

			$this->collected = true;

			do_action('acbs/admin/settings', $this);

		}

		/* ---------------------------------------------------------------------------
		 * Registration facade - signatures match \Elementor\Settings
		 * ------------------------------------------------------------------------ */

		/**
		 * add_tab function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $tab_id
		 * @param array  $args  'label' and optionally 'sections'.
		 */
		public function add_tab($tab_id, array $args = []) {

			if(isset($this->tabs[$tab_id])) {
				return;
			}

			$this->tabs[$tab_id] = [
				'label' => $args['label'] ?? $tab_id,
				'sections' => [],
			];

			foreach(($args['sections'] ?? []) as $section_id => $section_args) {
				$this->add_section($tab_id, $section_id, is_array($section_args) ? $section_args : []);
			}

		}

		/**
		 * add_section function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $tab_id
		 * @param string $section_id
		 * @param array  $args   'callback' renders the section heading; 'fields' is
		 *                       optional and takes the same shape add_field() builds.
		 */
		public function add_section($tab_id, $section_id, array $args = []) {

			// A section registered against a tab nobody created would render nowhere, and
			// silently: the registrant's priority is probably wrong.
			if(!isset($this->tabs[$tab_id])) {

				if(defined('WP_DEBUG') && WP_DEBUG) {
					trigger_error(
						sprintf('ACBS: settings section "%s" was added to unknown tab "%s".', $section_id, $tab_id),
						E_USER_WARNING
					);
				}

				return;

			}

			if(!isset($this->tabs[$tab_id]['sections'][$section_id])) {
				$this->tabs[$tab_id]['sections'][$section_id] = [
					'callback' => $args['callback'] ?? null,
					'fields' => [],
				];
			}

			foreach(($args['fields'] ?? []) as $field_id => $field_args) {
				$this->add_field($tab_id, $section_id, $field_id, is_array($field_args) ? $field_args : []);
			}

		}

		/**
		 * add_field function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $tab_id
		 * @param string $section_id
		 * @param string $field_id
		 * @param array  $args   'label', 'full_field_id' (the option name), optionally
		 *                       'render' (callable), 'class', 'field_args' and
		 *                       'setting_args'.
		 */
		public function add_field($tab_id, $section_id, $field_id, array $args = []) {

			if(!isset($this->tabs[$tab_id]['sections'][$section_id])) {

				if(defined('WP_DEBUG') && WP_DEBUG) {
					trigger_error(
						sprintf('ACBS: settings field "%s" was added to unknown section "%s/%s".', $field_id, $tab_id, $section_id),
						E_USER_WARNING
					);
				}

				return;

			}

			$this->tabs[$tab_id]['sections'][$section_id]['fields'][$field_id] = $args;

		}

		/* ---------------------------------------------------------------------------
		 * WordPress plumbing
		 * ------------------------------------------------------------------------ */

		/**
		 * add_page function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function add_page() {

			$this->collect();

			if(empty($this->tabs)) {
				return;
			}

			add_submenu_page(
				self::PARENT_SLUG,
				esc_html__('ACF Components Block System', 'erdc'),
				esc_html__('Components', 'erdc'),
				self::CAPABILITY,
				self::PAGE_SLUG,
				[$this, 'render_page']
			);

		}

		/**
		 * register_settings function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function register_settings() {

			$this->collect();

			foreach($this->tabs as $tab_id => $tab) {

				$page = $this->page_id($tab_id);

				foreach($tab['sections'] as $section_id => $section) {

					add_settings_section(
						$section_id,
						'',
						$section['callback'] ? $section['callback'] : '__return_null',
						$page
					);

					foreach($section['fields'] as $field_id => $field) {

						$option = $field['full_field_id'] ?? $field_id;

						register_setting(
							$this->option_group($tab_id),
							$option,
							$this->setting_args($field)
						);

						$field_args = $field['field_args'] ?? [];
						$field_args['acbs_option'] = $option;
						$field_args['acbs_render'] = $field['render'] ?? null;

						// WP core's do_settings_fields() puts $args['class'] on the field's <tr>,
						// which is how a custom field row styles itself.
						if(!empty($field['class']) && empty($field_args['class'])) {
							$field_args['class'] = $field['class'];
						}

						add_settings_field(
							$field_id,
							$field['label'] ?? '',
							[$this, 'render_field'],
							$page,
							$section_id,
							$field_args
						);

					}

				}

			}

		}

		/**
		 * setting_args function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		private function setting_args(array $field) {

			$args = $field['setting_args'] ?? [];

			// A setting with no sanitize_callback is stored raw, which for anything typed
			// by hand is not acceptable. Registrants that need something other than a
			// scalar (the two checkbox lists) already declare their own.
			if(!isset($args['sanitize_callback'])) {
				$args['sanitize_callback'] = 'sanitize_text_field';
			}

			return $args;

		}

		/**
		 * render_field function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param array $args
		 */
		public function render_field($args) {

			// A field with its own renderer owns its markup entirely, exactly as it did on
			// Elementor's page.
			if(!empty($args['acbs_render']) && is_callable($args['acbs_render'])) {
				call_user_func($args['acbs_render']);
				return;
			}

			$option = $args['acbs_option'];
			$type = $args['type'] ?? 'text';
			$current = get_option($option, $args['std'] ?? '');

			if('checkbox' === $type) {

				$value = $args['value'] ?? 'yes';

				?>
				<label>
					<input type="checkbox"
						name="<?php echo esc_attr($option); ?>"
						value="<?php echo esc_attr($value); ?>"
						<?php checked($current, $value); ?> />
					<?php
						if(!empty($args['sub_desc'])) {
							echo esc_html($args['sub_desc']);
						}
					?>
				</label>
				<?php

			} else {

				?>
				<input type="text"
					class="regular-text"
					name="<?php echo esc_attr($option); ?>"
					value="<?php echo esc_attr(is_scalar($current) ? $current : ''); ?>" />
				<?php

			}

			if(!empty($args['desc'])) {
				echo '<p class="description">'.esc_html($args['desc']).'</p>';
			}

		}

		/**
		 * render_page function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function render_page() {

			if(!current_user_can(self::CAPABILITY)) {
				wp_die(esc_html__('You are not allowed to do this.', 'erdc'), 403);
			}

			$this->collect();

			$tab_ids = array_keys($this->tabs);
			$requested = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';
			$active = in_array($requested, $tab_ids, true) ? $requested : reset($tab_ids);

			?>
			<div class="wrap">

				<h1><?php echo esc_html(get_admin_page_title()); ?></h1>

				<style>
					/* The section headings carried over from the Elementor settings page use
					   Elementor's own description class. Rather than edit five registrants to
					   change one class name, give it the same treatment core gives
					   .description. */
					.acbs-settings .e-experiment__description {
						color: #646970;
						font-size: 13px;
						font-style: italic;
					}
					.acbs-settings h2 { margin-bottom: .4em; }
				</style>

				<?php if(count($tab_ids) > 1) : ?>
					<h2 class="nav-tab-wrapper">
						<?php foreach($this->tabs as $tab_id => $tab) : ?>
							<a href="<?php echo esc_url(add_query_arg(['page' => self::PAGE_SLUG, 'tab' => $tab_id], admin_url(self::PARENT_SLUG))); ?>"
								class="nav-tab<?php echo $tab_id === $active ? ' nav-tab-active' : ''; ?>">
								<?php echo esc_html($tab['label']); ?>
							</a>
						<?php endforeach; ?>
					</h2>
				<?php endif; ?>

				<form method="post" action="options.php" class="acbs-settings">
					<?php
						settings_fields($this->option_group($active));
						do_settings_sections($this->page_id($active));
						submit_button();
					?>
				</form>

			</div>
			<?php

		}

		/**
		 * page_id function
		 *
		 * The Settings API "page" a tab's sections are registered against.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $tab_id
		 *
		 * @return string
		 */
		private function page_id($tab_id) {
			return self::PAGE_SLUG.'-'.$tab_id;
		}

		/**
		 * option_group function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $tab_id
		 *
		 * @return string
		 */
		private function option_group($tab_id) {
			return 'acbs_'.str_replace('-', '_', $tab_id);
		}

		/**
		 * url function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $tab_id
		 *
		 * @return string
		 */
		public static function url($tab_id = '') {

			$args = ['page' => self::PAGE_SLUG];

			if('' !== $tab_id) {
				$args['tab'] = $tab_id;
			}

			return add_query_arg($args, admin_url(self::PARENT_SLUG));

		}

	}
