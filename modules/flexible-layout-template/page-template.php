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
		 * register function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function register() {

			add_filter('theme_page_templates', [__CLASS__, 'add_template']);
			add_filter('template_include', [__CLASS__, 'serve']);

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
