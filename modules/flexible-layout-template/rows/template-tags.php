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

	use ERDC\Plugin;

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
