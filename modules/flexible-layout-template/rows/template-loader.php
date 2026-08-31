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
	 * Resolution goes through locate_template(), so child theme then parent theme then
	 * plugin works - per site on multisite, since each site has its own active theme -
	 * without reimplementing any of it.
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

				// Child theme, then parent theme.
				$located = locate_template(self::THEME_DIR.'/'.$candidate);

				if($located) {
					$path = $located;
					break;
				}

				// Then the plugin's own.
				$plugin_path = ACBS_PATH.'templates/'.$candidate;

				if(file_exists($plugin_path)) {
					$path = $plugin_path;
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

				$located = locate_template(self::THEME_DIR.'/'.$candidate);

				if(!$located && file_exists(ACBS_PATH.'templates/'.$candidate)) {
					$located = ACBS_PATH.'templates/'.$candidate;
				}

				self::$resolved[$key] = (string) $located;

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

				$located = locate_template(self::THEME_DIR.'/'.$candidate);

				if(!$located && file_exists(ACBS_PATH.'templates/'.$candidate)) {
					$located = ACBS_PATH.'templates/'.$candidate;
				}

				self::$resolved[$key] = (string) $located;

			}

			return self::$resolved[$key];

		}

	}
