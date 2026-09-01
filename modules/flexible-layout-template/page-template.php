<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate;

	use ACBS\Modules\FlexibleLayoutTemplate\Rows\Template_Loader;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Page_Template
	 *
	 * Adds "Page Builder" to the Template dropdown on the page editor, and serves it from
	 * the plugin rather than from the theme.
	 *
	 * A theme's page templates are discovered by scanning the theme directory for a
	 * `Template Name` file header, so a plugin cannot ship one that way. It registers the
	 * entry through `theme_page_templates` instead, and then has to serve the file itself,
	 * because the slug WordPress stores in `_wp_page_template` is only ever looked for
	 * inside the theme. Two halves of one feature: register() without serve() gives an
	 * option that silently falls back to page.php.
	 *
	 * The template file is overridable at {theme}/acbs/page-builder.php, the same cascade
	 * row templates use.
	 *
	 * **The shipped file deliberately carries no `Template Name` header.** WP_Theme's
	 * scan is `get_files('php', 1, true)` - depth 1, which includes one level of
	 * subdirectory - so a theme override sitting at {theme}/acbs/page-builder.php WITH a
	 * header would be picked up by that scan as well as by the filter here, and the
	 * dropdown would show "Page Builder" twice, once resolving to the theme's copy and
	 * once to ours. Leaving the header off means the override is served through this class
	 * either way, and the entry stays single.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate
	 */
	class Page_Template {

		/**
		 * The value stored in `_wp_page_template`. Prefixed rather than the bare
		 * `page-builder.php`, so it cannot collide with a real theme file of that name -
		 * a page assigned to ours would otherwise start resolving to the theme's the day
		 * someone adds one.
		 */
		const SLUG = 'acbs-page-builder.php';

		/**
		 * Path under the theme / the plugin's templates directory.
		 */
		const FILE = 'page-builder.php';

		/**
		 * Records the one-shot assignment of this template to pre-existing pages. Its
		 * presence is the "already ran" flag; its `ids` are what changed, so the run can be
		 * audited or undone. See migrate_existing_pages().
		 */
		const MIGRATION_OPTION = 'erdc_page_template_migration';

		/**
		 * register function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function register() {

			add_filter('theme_page_templates', [__CLASS__, 'add_template']);
			add_filter('template_include', [__CLASS__, 'serve']);

			// One-shot, and admin-only on purpose - see migrate_existing_pages().
			add_action('admin_init', [__CLASS__, 'maybe_migrate_existing_pages']);

		}

		/**
		 * maybe_migrate_existing_pages function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function maybe_migrate_existing_pages() {

			if(false !== get_option(self::MIGRATION_OPTION)) {
				return;
			}

			// A content write, so it waits for somebody who could have made the same change
			// by hand. On a site where nobody with the capability ever opens wp-admin there
			// is also nobody for whom the field group's absence is a problem.
			if(!current_user_can('edit_pages')) {
				return;
			}

			self::migrate_existing_pages();

		}

		/**
		 * migrate_existing_pages function
		 *
		 * Assigns this template to every page that already has flexible layout rows saved
		 * against it and has not had a template chosen.
		 *
		 * Needed because the Page Content field group's location rule is now
		 * `page_template == acbs-page-builder.php`. Pages built before that rule existed
		 * carry their rows in postmeta but no `_wp_page_template`, so the builder would
		 * simply not appear on them - the data is intact and invisible, which is the worst
		 * of both. This puts them back in reach.
		 *
		 * What it deliberately does NOT touch:
		 *
		 * - A page that already names a template. That is somebody's explicit choice, and
		 *   overwriting it would silently change how their page renders. Rows on such a page
		 *   stay unreachable until someone switches it over by hand, which is the correct
		 *   trade: a visible gap beats a silent redesign.
		 * - Anything that is not a published-or-editable page: revisions carry the same
		 *   postmeta and are excluded by post_type, and trash and auto-drafts are excluded by
		 *   status. Reaching into the trash to modify content nobody asked about is
		 *   surprising, so a restored page needs the template set by hand.
		 * - Pages whose `page_sections` is empty. An empty flexible content field stores ''
		 *   or an empty serialised array; neither is a page anyone built.
		 *
		 * The ids it changed are recorded in the option, which is both the "already ran"
		 * flag and the undo list. Delete the option to run it again.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array The post ids that were changed.
		 */
		public static function migrate_existing_pages() {

			global $wpdb;

			$ids = $wpdb->get_col(
				$wpdb->prepare(
					// The `sections` alias is not decoration: `rows` is a reserved word in
					// MySQL 8, and using it here returns nothing rather than erroring - which
					// would have made this migration look like it had run and found no work.
					"SELECT p.ID
					   FROM {$wpdb->posts} p
					   INNER JOIN {$wpdb->postmeta} sections
					           ON sections.post_id = p.ID AND sections.meta_key = %s
					   LEFT JOIN {$wpdb->postmeta} tpl
					          ON tpl.post_id = p.ID AND tpl.meta_key = '_wp_page_template'
					  WHERE p.post_type = 'page'
					    AND p.post_status NOT IN ('auto-draft', 'trash')
					    AND sections.meta_value <> ''
					    AND sections.meta_value <> %s
					    AND (tpl.meta_id IS NULL OR tpl.meta_value = '' OR tpl.meta_value = 'default')",
					'page_sections',
					'a:0:{}'
				)
			);

			$migrated = [];

			foreach($ids as $id) {

				$id = (int) $id;

				if(update_post_meta($id, '_wp_page_template', self::SLUG)) {
					$migrated[] = $id;
				}

			}

			// Not autoloaded: it is read once per admin request until it exists, and never
			// after that.
			add_option(
				self::MIGRATION_OPTION,
				[
					'ran' => gmdate('c'),
					'template' => self::SLUG,
					'ids' => $migrated,
				],
				'',
				false
			);

			return $migrated;

		}

		/**
		 * add_template function
		 *
		 * Puts the entry in the dropdown. This filter is also what the REST controller
		 * validates a saved `template` value against - see
		 * WP_REST_Posts_Controller::check_template() - so without it the block editor
		 * would reject the value on save rather than merely not offering it.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param array $templates
		 *
		 * @return array
		 */
		public static function add_template($templates) {

			if(!is_array($templates)) {
				$templates = [];
			}

			$templates[self::SLUG] = __('Page Builder', 'erdc');

			return $templates;

		}

		/**
		 * serve function
		 *
		 * Swaps in our file for a page assigned the template.
		 *
		 * Hooked to `template_include` rather than `page_template` on purpose: it is the
		 * last filter in wp-includes/template-loader.php, so it runs after the whole
		 * hierarchy has been resolved and cannot be undone by anything downstream.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $template
		 *
		 * @return string
		 */
		public static function serve($template) {

			if(!is_singular() || self::SLUG !== get_page_template_slug()) {
				return $template;
			}

			$located = self::locate();

			return '' !== $located ? $located : $template;

		}

		/**
		 * locate function
		 *
		 * Child theme, then parent theme, then the plugin.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string Absolute path, or '' if even the plugin's copy is missing.
		 */
		public static function locate() {

			$located = locate_template(Template_Loader::THEME_DIR.'/'.self::FILE);

			if(!$located && file_exists(ACBS_PATH.'templates/'.self::FILE)) {
				$located = ACBS_PATH.'templates/'.self::FILE;
			}

			return (string) $located;

		}

	}
