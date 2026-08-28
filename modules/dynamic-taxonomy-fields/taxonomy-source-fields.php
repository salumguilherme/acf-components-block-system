<?php

	namespace ERDC\Modules\DynamicTaxonomyFields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Taxonomy_Source_Fields
	 *
	 * Each pair below maps one Taxonomy select field to the Terms field(s) that should
	 * reload against it (see Page_Content's "Source: Taxonomy" layouts). Add further
	 * pairs here, or via the `erdc/dynamic_taxonomy_fields/pairs` filter, as more
	 * layouts adopt the Repeater/Taxonomy source pattern.
	 *
	 * A pair may also carry an optional 'post_type_source' key - an array of one or
	 * more field key(s) whose currently-selected post type(s) restrict the Taxonomy
	 * field's OWN choices to only the taxonomies actually registered on them, e.g.:
	 *
	 *     [
	 *         'post_type_source' => ['field_...'],
	 *         'taxonomy' => 'field_...',
	 *         'terms' => ['field_...'],
	 *     ]
	 *
	 * This filtering is entirely client-side (see dynamic-taxonomy-fields.js and
	 * Ajax::get_taxonomies_for_post_types()) - PHP's own populate_taxonomy_choices()
	 * below is unchanged and still seeds every public taxonomy, since ACF gives a
	 * `acf/load_field` callback no per-row context to know which post type a specific
	 * repeater row currently holds. A pair with no 'post_type_source' behaves exactly
	 * as before; omitting the key (rather than requiring an empty array) keeps every
	 * existing pair - the plugin's own three included - untouched.
	 *
	 * @version 1.0.28
	 * @since   1.0.18
	 * @package ERDC\Modules\DynamicTaxonomyFields
	 */
	class Taxonomy_Source_Fields {

		/**
		 * @var array
		 */
		const FIELD_PAIRS = [
			[
				// Icon Leaders
				'taxonomy' => 'field_403dff56b7aa4',
				'terms' => ['field_6c78cbeb5b2d0'],
			],
			[
				// Image Cards Simple Grid
				'taxonomy' => 'field_1445c55d22a50',
				'terms' => ['field_ffcef58b1d6ca'],
			],
			[
				// Image Cards Multi Grid
				'taxonomy' => 'field_0d19f13fb9dc3',
				'terms' => ['field_92ac688424e39', 'field_faf8e580d547f'],
			],
		];

		/**
		 * get_pairs function
		 *
		 * @version 1.0.18
		 * @since   1.0.18
		 * @return array
		 */
		public static function get_pairs() {
			return apply_filters('erdc/dynamic_taxonomy_fields/pairs', self::FIELD_PAIRS);
		}

		/**
		 * register function
		 *
		 * @version 1.0.18
		 * @since   1.0.18
		 */
		public static function register() {

			foreach(self::get_pairs() as $pair) {

				add_filter('acf/load_field/key='.$pair['taxonomy'], [__CLASS__, 'populate_taxonomy_choices']);

				foreach($pair['terms'] as $terms_key) {
					add_filter('acf/prepare_field/key='.$terms_key, [__CLASS__, 'populate_term_choices']);
				}

			}

		}

		/**
		 * populate_taxonomy_choices function
		 *
		 * Populates a Taxonomy select field's choices with every public taxonomy.
		 *
		 * @version 1.0.18
		 * @since   1.0.18
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		public static function populate_taxonomy_choices($field) {

			if(self::is_editing_field_group()) {
				return $field;
			}

			$choices = [];

			foreach(get_taxonomies(['public' => true], 'objects') as $taxonomy) {
				$choices[$taxonomy->name] = $taxonomy->label;
			}

			$field['choices'] = $choices;

			return $field;

		}

		/**
		 * populate_term_choices function
		 *
		 * Turns a Terms `taxonomy` field into a plain `select` field so its choices can be
		 * reloaded via AJAX as the paired Taxonomy field changes, seeded with the labels
		 * of whichever term IDs are already saved.
		 *
		 * @version 1.0.18
		 * @since   1.0.18
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		public static function populate_term_choices($field) {

			if(self::is_editing_field_group()) {
				return $field;
			}

			if($field['field_type'] == 'multi_select' || $field['field_type'] == 'checkbox') {
				$field['multiple'] = 1;
			}

			$field['type'] = 'select';
			$field['ui'] = 1;
			$field['ajax'] = 0;
			$field['allow_null'] = 0;
			$field['placeholder'] = '';
			$field['create_options'] = 0;
			$field['save_options'] = 0;
			$field['choices'] = [];

			foreach((array) ($field['value'] ?? []) as $term_id) {

				$term = get_term(intval($term_id));

				if($term && !is_wp_error($term)) {
					$field['choices'][$term->term_id] = $term->name;
				}

			}

			return $field;

		}

		/**
		 * is_editing_field_group function
		 *
		 * @version 1.0.18
		 * @since   1.0.18
		 * @return bool
		 */
		private static function is_editing_field_group() {

			if(!function_exists('get_current_screen')) {
				return false;
			}

			$screen = get_current_screen();

			return $screen && !empty($screen->id) && $screen->id == 'acf-field-group';

		}

	}
