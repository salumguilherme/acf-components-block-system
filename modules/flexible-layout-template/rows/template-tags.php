<?php

	/**
	 * Public render API.
	 *
	 * Three entry points, one implementation: a theme calls the template tag, an editor
	 * uses the shortcode, anything programmatic goes through the class.
	 *
	 *     acbs_render_rows();
	 *     acbs_render_rows([ 'field' => 'page_sections', 'source' => $post_id ]);
	 *     echo do_shortcode('[acbs_rows]');
	 *
	 * This file holds plain functions, so it is required by the module rather than
	 * autoloaded - the autoloader only resolves classes.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	use ACBS\Plugin;
	use ACBS\Modules\FlexibleLayoutTemplate\Rows\Items_Source;
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

	if(!function_exists('acbs_rows_shortcode')) {

		/**
		 * acbs_rows_shortcode function
		 *
		 * [acbs_rows field="page_sections" source="12"]
		 *
		 * `source` accepts a post id, a term id as "term_12", or "options" - the same
		 * things ACF itself accepts - and is normalised by Source_Resolver either way.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param array $atts
		 *
		 * @return string
		 */
		function acbs_rows_shortcode($atts) {

			$atts = shortcode_atts([
				'field' => 'page_sections',
				'source' => '',
			], $atts, 'acbs_rows');

			return acbs_get_rows([
				'field' => sanitize_key($atts['field']),
				'source' => '' !== $atts['source'] ? sanitize_text_field($atts['source']) : null,
			]);

		}

	}

	if(!function_exists('acbs_row_items')) {

		/**
		 * acbs_row_items function
		 *
		 * The items of a row whose `source` field selects repeater, taxonomy or post type.
		 *
		 *     $items = acbs_row_items($row);
		 *
		 *     while($items->have_item()) {
		 *         $item = $items->the_item();
		 *     }
		 *
		 * Row templates go through this rather than naming Items_Source directly, so a
		 * template - including one a site has copied into its theme - never carries the
		 * plugin's namespace. That matters more than it looks: the namespace is still
		 * ACBS\ pending a rename to ACBS\, and a theme's copied templates are the one
		 * place a rename cannot reach.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row    $row
		 * @param string $variant 'items', or 'featured' for image_cards_multi_grid.
		 *
		 * @return Items_Source
		 */
		function acbs_row_items(Row $row, $variant = 'items') {
			return Items_Source::for_row($row, $variant);
		}

	}

	if(!function_exists('acbs_row_part')) {

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
