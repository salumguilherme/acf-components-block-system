<?php

	/**
	 * Public render API.
	 *
	 * Two entry points, one implementation: a theme calls the template tag, anything
	 * programmatic goes through the class.
	 *
	 *     acbs_render_rows();
	 *     acbs_render_rows([ 'field' => 'page_sections', 'source' => $post_id ]);
	 *
	 * The plugin registers no shortcodes. Rows are placed by the Page Builder page
	 * template, or by a theme calling the tag directly - an editor never types a tag into
	 * post content, because on a Page Builder page there is no post content to type into.
	 *
	 * This file holds plain functions, so it is required by the module rather than
	 * autoloaded - the autoloader only resolves classes.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	use ACBS\Plugin;
	use ACBS\Modules\FlexibleLayoutTemplate\Rows\Row;
	use ACBS\Modules\FlexibleLayoutTemplate\Rows\Template_Loader;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	if(!function_exists('acbs_get_rows')) {

		/**
		 * acbs_get_rows function
		 *
		 * The rendered rows, as a string.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param array $args 'field' and 'source', both optional.
		 *
		 * @return string
		 */
		function acbs_get_rows(array $args = []) {
			return Plugin::instance()->renderer()->render($args);
		}

	}

	if(!function_exists('acbs_render_rows')) {

		/**
		 * acbs_render_rows function
		 *
		 * Echoes the rendered rows. The renderer itself returns a string; only this tag
		 * writes to the output.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param array $args 'field' and 'source', both optional.
		 */
		function acbs_render_rows(array $args = []) {
			echo acbs_get_rows($args); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- row templates escape their own output.
		}

	}

	if(!function_exists('acbs_row_part')) {

				/**
		 * acbs_unique_id function
		 *
		 * A DOM id that is unique for the rest of this request.
		 *
		 * WHY THIS IS A FUNCTION AND NOT A `static` IN THE TEMPLATE. Both accordion
		 * templates used to declare `static $n = 0;` at the top of the file and increment
		 * it. That works in an ordinary function and does NOT work here: a template is
		 * pulled in with `include` from inside Wrapper::include_template(), and a static
		 * declared in included code is re-initialised on every include rather than
		 * persisting across them. Every columned_content column therefore rendered
		 * `fl-column-accordion-1`, so four panels shared one id and every trigger's
		 * aria-controls pointed at the first of them.
		 *
		 * It failed silently in the worst way: the markup validates, the first panel still
		 * opens, and assistive technology follows aria-controls to the wrong element.
		 *
		 * The counter lives in this function's own scope, which is a real function called
		 * normally, so it behaves the way the template author expected in the first place.
		 * Ids are per prefix, so accordions and columns number independently.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $prefix
		 *
		 * @return string
		 */
		function acbs_unique_id($prefix = 'acbs') {

			static $counts = [];

			$prefix = '' !== (string) $prefix ? (string) $prefix : 'acbs';

			$counts[$prefix] = isset($counts[$prefix]) ? $counts[$prefix] + 1 : 1;

			return $prefix.'-'.$counts[$prefix];

		}

/**
		 * acbs_row_part function
		 *
		 * Renders a shared template part - parts/{name}.php - with $row in scope. A
		 * missing part prints nothing.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $name
		 * @param Row    $row
		 */
		function acbs_row_part($name, Row $row) {
			Template_Loader::render_part($name, $row);
		}

	}

	if(!function_exists('acbs_row_partial')) {

		/**
		 * acbs_row_partial function
		 *
		 * Renders one of the row's own sub-templates - rows/{layout}/item.php - from inside
		 * that row's have_rows() loop:
		 *
		 *     while(have_rows('columns')) {
		 *         the_row();
		 *         acbs_row_partial('item', $row);
		 *     }
		 *
		 * The item's fields are read inside the partial through get_sub_field(), because the
		 * loop is still active while it renders. There is no item object to pass.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $name
		 * @param Row    $row
		 */
		function acbs_row_partial($name, Row $row) {
			Template_Loader::render_partial($row, $name);
		}

	}
