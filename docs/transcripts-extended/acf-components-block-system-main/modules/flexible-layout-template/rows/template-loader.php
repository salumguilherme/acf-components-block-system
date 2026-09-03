<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Rows;

	use ACBS\Modules\FlexibleLayoutTemplate\Source_Resolver;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Template_Loader
	 *
	 * Resolves a row to the PHP file that renders it, most specific candidate first.
	 *
	 * This replaces Elementor Theme Builder display conditions. A row used to be able to
	 * choose its own template by asking the Theme Builder which documents matched the row
	 * currently in scope, which is how one full_width_content row could render
	 * differently on a product page than on a blog post. A flat one-file-per-layout
	 * lookup would throw that away silently - the page still renders, with the wrong
	 * design - so the context becomes part of the filename instead:
	 *
	 *     rows/{layout}-{post_type}-{post_slug}.php
	 *     rows/{layout}-{post_type}.php
	 *     rows/{layout}-{context}.php        archive | tax | options | front-page
	 *     rows/{layout}.php
	 *     rows/default.php                   ships empty, terminal candidate
	 *
	 * Every candidate is also looked for with its underscores written as dashes, so
	 * `columned-content.php` renders the `columned_content` layout exactly as
	 * `columned_content.php` does. The spellings come from Filenames::variants(), which
	 * row stylesheets and row scripts share.
	 *
	 * The two rules that settles are worth stating precisely, because they pull in
	 * opposite directions:
	 *
	 *     underscore beats dash   WITHIN ONE DIRECTORY
	 *     child beats parent beats plugin   ACROSS directories, whatever the spelling
	 *
	 * So a theme's `acbs/rows/columned-content.php` still wins over the plugin's
	 * `templates/rows/columned_content.php`, and a child theme's dashed file still wins
	 * over a parent theme's underscored one.
	 *
	 * THAT ORDERING IS WHY THIS NO LONGER CALLS locate_template(). It searches child then
	 * parent for EACH candidate in turn, which is candidate-major: handed both spellings
	 * it would return the parent theme's underscore ahead of the child theme's dash, and
	 * the child theme would silently lose. Walking the directories ourselves is the only
	 * way to keep the directory the outer loop. Nothing else locate_template() does is
	 * used here - the theme-compat directory and `$load` were never in play - and the
	 * per-site behaviour on multisite is unchanged, because get_stylesheet_directory()
	 * is per site just as locate_template() was.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Rows
	 */
	class Template_Loader {

		/**
		 * Directory templates are looked for in, inside the theme.
		 */
		const THEME_DIR = 'acbs';

		/**
		 * Resolved paths, keyed by the candidate list. A 12-row page with a 5-candidate
		 * cascade is otherwise 60 file_exists() calls; memoised it is at most one pass per
		 * distinct layout per request.
		 *
		 * @var array
		 */
		private static $resolved = [];

		/**
		 * locate function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row $row
		 *
		 * @return string Absolute path, or '' if nothing was found.
		 */
		public static function locate(Row $row) {

			$candidates = self::candidates($row);
			$key = implode('|', $candidates);

			if(!isset(self::$resolved[$key])) {
				self::$resolved[$key] = self::find($candidates, $row);
			}

			return self::$resolved[$key];

		}

		/**
		 * candidates function
		 *
		 * Relative paths under rows/, most specific first.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row $row
		 *
		 * @return array
		 */
		public static function candidates(Row $row) {

			$template = $row->type()->template();
			$candidates = [];

			$queried = get_queried_object();

			if($queried instanceof \WP_Post) {
				$candidates[] = $template.'-'.$queried->post_type.'-'.$queried->post_name;
				$candidates[] = $template.'-'.$queried->post_type;
			} elseif($queried instanceof \WP_Term) {
				$candidates[] = $template.'-'.$queried->taxonomy.'-'.$queried->slug;
				$candidates[] = $template.'-'.$queried->taxonomy;
			}

			$context = Source_Resolver::context($row->source());

			if('' !== $context) {
				$candidates[] = $template.'-'.$context;
			}

			$candidates[] = $template;
			$candidates[] = 'default';

			$candidates = array_values(array_unique(array_map(function($candidate) {
				return 'rows/'.$candidate.'.php';
			}, $candidates)));

			/**
			 * Filters the candidate list, so a site can add or reorder candidates without
			 * replacing the resolution.
			 *
			 * @param array $candidates Relative paths under the templates directory.
			 * @param Row   $row
			 */
			return (array) apply_filters('acbs/template/candidates', $candidates, $row);

		}

		/**
		 * find function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param array $candidates
		 * @param Row   $row
		 *
		 * @return string
		 */
		private static function find(array $candidates, Row $row) {

			$path = '';

			foreach($candidates as $candidate) {

				$located = self::locate_file($candidate);

				if('' !== $located) {
					$path = $located;
					break;
				}

			}

			/**
			 * Filters the resolved path outright, for a client add-on shipping its own
			 * templates from outside the theme.
			 *
			 * @param string $path       Absolute path, or '' if nothing matched.
			 * @param array  $candidates
			 * @param Row    $row
			 */
			return (string) apply_filters('acbs/template/path', $path, $candidates, $row);

		}

		/**
		 * locate_file function
		 *
		 * Finds one relative candidate across the three directories, trying both spellings
		 * inside each before moving to the next. Directory is the outer loop and spelling
		 * the inner one, which is what makes a child theme's dashed file beat a parent
		 * theme's underscored one while an underscored file still wins inside any single
		 * directory.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $candidate Relative path under the templates directory.
		 *
		 * @return string Absolute path, or '' if nothing was found.
		 */
		public static function locate_file($candidate) {

			foreach(self::directories() as $directory) {

				foreach(Filenames::variants($candidate) as $variant) {

					$file = $directory.$variant;

					if(file_exists($file)) {
						return $file;
					}

				}

			}

			return '';

		}

		/**
		 * directories function
		 *
		 * Where templates are looked for, most specific first. The parent theme is skipped
		 * when it IS the active theme, so a non-child setup does not test the same path
		 * twice.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @return array Absolute directory paths, each with a trailing slash.
		 */
		private static function directories() {

			$stylesheet = trailingslashit(get_stylesheet_directory());
			$template = trailingslashit(get_template_directory());

			$directories = [$stylesheet.self::THEME_DIR.'/'];

			if($template !== $stylesheet) {
				$directories[] = $template.self::THEME_DIR.'/';
			}

			$directories[] = ACBS_PATH.'templates/';

			return $directories;

		}

		/**
		 * locate_part function
		 *
		 * A template part shared by every layout rather than owned by one, e.g.
		 * parts/intro.php. Same cascade as everything else, no fallback: a part either
		 * exists or nothing is printed.
		 *
		 * Intro is the reason this exists. Its Section Title and Section Content are
		 * injected into all but four of the fifteen layouts by Common_Fields, so without a
		 * shared part the same six lines would be copied into thirteen row templates, and
		 * a site wanting to change how an intro renders would have to override all
		 * thirteen to do it.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $name
		 *
		 * @return string
		 */
		public static function locate_part($name) {

			$candidate = 'parts/'.$name.'.php';
			$key = 'part:'.$candidate;

			if(!isset(self::$resolved[$key])) {
				self::$resolved[$key] = self::locate_file($candidate);
			}

			return self::$resolved[$key];

		}

		/**
		 * render_part function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $name
		 * @param Row    $row
		 */
		public static function render_part($name, Row $row) {

			$path = self::locate_part($name);

			if('' === $path) {
				return;
			}

			Wrapper::include_template($path, $row);

		}

		/**
		 * render_partial function
		 *
		 * Includes one of a row's own sub-templates - rows/{layout}/item.php and the like -
		 * with $row in scope and the ACF loop left exactly as it was found.
		 *
		 * Called from inside a row template's own have_rows() loop, so the item's fields are
		 * already in scope through get_sub_field(). Nothing is passed for the item itself;
		 * ACF's loop IS the item.
		 *
		 * A missing partial prints nothing and says so under WP_DEBUG, because a row whose
		 * items silently vanish is very hard to tell from a row with no items.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row    $row
		 * @param string $partial
		 */
		public static function render_partial(Row $row, $partial = 'item') {

			$path = self::locate_partial($row, $partial);

			if('' === $path) {

				if(defined('WP_DEBUG') && WP_DEBUG) {
					trigger_error(
						sprintf(
							'ACBS: row layout "%s" has no "%s" partial, so its items rendered nothing. Expected rows/%s/%s.php.',
							$row->layout(),
							$partial,
							$row->type()->template(),
							$partial
						),
						E_USER_NOTICE
					);
				}

				return;

			}

			Wrapper::include_template($path, $row);

		}

		/**
		 * locate_partial function
		 *
		 * A sub-template of a row, e.g. rows/image_cards_grid/item.php. Same cascade, no
		 * context specificity and no terminal fallback: a missing item template means
		 * nothing, not rows/default.php.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row    $row
		 * @param string $partial
		 *
		 * @return string
		 */
		public static function locate_partial(Row $row, $partial = 'item') {

			$candidate = 'rows/'.$row->type()->template().'/'.$partial.'.php';
			$key = 'partial:'.$candidate;

			if(!isset(self::$resolved[$key])) {
				self::$resolved[$key] = self::locate_file($candidate);
			}

			return self::$resolved[$key];

		}

	}
