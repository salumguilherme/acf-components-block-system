<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Rows;

	use ACBS\Modules\FlexibleLayoutTemplate\Fields\Page_Content;
	use ACBS\Modules\FlexibleLayoutTemplate\Source_Resolver;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Renderer
	 *
	 * The have_rows() loop, and nothing else. No parallel iteration, no second read of
	 * the field, no reimplementation of anything ACF already does.
	 *
	 * The renderer RETURNS a string rather than writing to the output buffer. The widget
	 * this replaces echoed from half a dozen places, which is what made its caching, its
	 * debug instrumentation and its asset capture so tangled; markup that comes back as a
	 * value is testable and composable.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Rows
	 */
	class Renderer {

		/**
		 * Layouts whose default content handler has been attached.
		 *
		 * @var array
		 */
		private $handled = [];

		/**
		 * render function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param array $args 'field' (default 'page_sections') and 'source' (default: the
		 *                    queried object, resolved by Source_Resolver).
		 *
		 * @return string
		 */
		public function render(array $args = []) {

			$field = !empty($args['field']) ? (string) $args['field'] : 'page_sections';
			$source = Source_Resolver::resolve($args['source'] ?? null);

			if(!function_exists('have_rows') || !have_rows($field, $source)) {
				return '';
			}

			$output = '';

			// This loop is never broken out of, so ACF pops it from its own stack when the
			// rows run out - which is the ONLY thing that pops it. See CLAUDE.md 05.2.
			while(have_rows($field, $source)) {

				the_row();

				$output .= $this->render_row(get_row_layout(), get_row_index(), $source, $field);

			}

			return self::wrap($output);

		}

		/**
		 * wrap function
		 *
		 * The single top-level element around a whole set of rows.
		 *
		 * There is exactly one of these per render, not one per row - the per-row <section>
		 * comes from templates/wrapper.php. It exists because it is the SCOPE the plugin's
		 * Bootstrap is compiled behind: every selector in assets/css/rows-bootstrap.css is
		 * rewritten to sit under `.acbs`, so a row gets stock Bootstrap regardless of what the
		 * surrounding site has done to its own copy, and nothing outside this element is
		 * touched. Remove `acbs` and the row stylesheets stop matching anything.
		 *
		 * `fl-acbs` IS NO LONGER A STYLING SCOPE (02/09/2026) - the stylesheets are scoped to
		 * `.acbs` alone now - but it is still emitted and still load-bearing, because
		 * src/js/rows.js finds rows with `.fl-acbs .fl-section`. A site that filters it away
		 * therefore keeps its CSS and silently loses its row JavaScript.
		 *
		 * An empty render emits nothing rather than an empty wrapper, so a page whose rows
		 * were all skipped leaves no stray element behind.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $rows
		 *
		 * @return string
		 */
		public static function wrap($rows) {

			if('' === $rows) {
				return '';
			}

			/**
			 * Filters the classes on the top-level row wrapper.
			 *
			 * `acbs` is what the compiled Bootstrap and every row stylesheet are scoped to;
			 * `fl-acbs` is what the row runtime queries. Add to this list; removing `acbs`
			 * breaks all row styling and removing `fl-acbs` stops rows announcing themselves.
			 *
			 * @param array $classes
			 */
			$classes = (array) apply_filters('acbs/rows/wrapper_classes', ['acbs', 'fl-acbs']);
			$classes = array_values(array_unique(array_filter(array_map('sanitize_html_class', $classes))));

			return '<div class="'.esc_attr(implode(' ', $classes)).'">'.$rows.'</div>';

		}

		/**
		 * render_row function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string     $layout
		 * @param int        $position
		 * @param int|string $source
		 * @param string     $field
		 *
		 * @return string
		 */
		private function render_row($layout, $position, $source, $field) {

			$type = Row_Registry::get($layout);

			// A layout that has been removed from the definition set leaves its saved rows
			// behind, hollowed out: flexible content's load_value() keys the definitions by
			// name and skips any row whose layout is no longer defined, so the row survives,
			// still reports its old layout name, still counts toward the loop, and carries no
			// sub field values at all.
			//
			// Skipping the row ENTIRELY - wrapper included - is the only correct answer.
			// Letting it fall through the template cascade to rows/default.php renders
			// nothing inside a real <section>, which prints a stray gap carrying the row's
			// padding and background and reads as a styling bug rather than as deleted
			// content. See CLAUDE.md 05.7; page 11 "Rental Fleet" is the live test case.
			if(is_null($type)) {

				if(defined('WP_DEBUG') && WP_DEBUG) {
					trigger_error(
						sprintf(
							'ACBS: no row type is registered for layout "%s" (source %s, position %d). The row was skipped; its saved data is still there.',
							$layout,
							(string) $source,
							(int) $position
						),
						E_USER_NOTICE
					);
				}

				return '';

			}

			$row = new Row($layout, $position, $source, $field, $type);

			/**
			 * Filters whether to render this row at all. This layers ON TOP of ACF's own
			 * behaviour: rows an editor has toggled off never reach here, because
			 * load_value() has already dropped them.
			 *
			 * @param bool $show
			 * @param Row  $row
			 */
			if(!apply_filters('acbs/row/show', true, $row)) {
				return '';
			}

			$this->ensure_content_handler($layout);

			// Declared handles are collected here and enqueued in the footer, once, for the
			// rows that actually rendered.
			Assets::instance()->record($type);

			return Wrapper::render($row);

		}

		/**
		 * ensure_content_handler function
		 *
		 * Attaches the default `acbs/row/{layout}/content` handler once per layout. A site
		 * that wants to take a row's content over entirely removes this action rather than
		 * copying a template file.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $layout
		 */
		private function ensure_content_handler($layout) {

			if(isset($this->handled[$layout])) {
				return;
			}

			$this->handled[$layout] = true;

			add_action("acbs/row/{$layout}/content", [Wrapper::class, 'render_content']);

		}

		/**
		 * layouts_on_page function
		 *
		 * The ordered list of layout names saved against a source, read WITHOUT hydrating
		 * a single sub field value.
		 *
		 * This deliberately goes under ACF's value layer to acf_get_metadata(), because
		 * for a flexible content field the raw meta IS the ordered array of layout names.
		 * get_field($field, $id, false) would look like the cheap option and is not:
		 * load_value() walks every layout's sub fields calling acf_get_value() on each, so
		 * "unformatted" hydrates the entire page.
		 *
		 * Not used by the render path, which discovers layouts as it renders them. This is
		 * here for the opt-in head pre-pass described in the plan, and for anything that
		 * needs to know what is on a page without rendering it.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param int|string|null $source
		 * @param string          $field
		 *
		 * @return array
		 */
		public static function layouts_on_page($source = null, $field = 'page_sections') {

			if(!function_exists('acf_get_metadata')) {
				return [];
			}

			$source = Source_Resolver::resolve($source);
			$meta = acf_get_metadata($source, $field);

			return is_array($meta) ? array_values(array_filter($meta, 'is_string')) : [];

		}

	}
