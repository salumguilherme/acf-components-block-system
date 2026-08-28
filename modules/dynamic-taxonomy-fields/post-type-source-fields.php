<?php

	namespace ERDC\Modules\DynamicTaxonomyFields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Post_Type_Source_Fields
	 *
	 * Mirrors Taxonomy_Source_Fields for the "Post Type" branch of the Source
	 * (Repeater / Taxonomy / Post Type) button group - a Post Type select field, and
	 * one or more Posts field(s) whose choices reload via AJAX whenever the selected
	 * post type changes (see assets/js/dynamic-taxonomy-fields.js).
	 *
	 * Add further layouts to FIELD_PAIRS (or via the
	 * `erdc/dynamic_taxonomy_fields/post_type_pairs` filter) as more Source: Post Type
	 * layouts are introduced.
	 *
	 * @version 1.0.19
	 * @since   1.0.19
	 * @package ERDC\Modules\DynamicTaxonomyFields
	 */
	class Post_Type_Source_Fields {

		/**
		 * Each pair maps one Post Type select field to the Posts field(s) that reload
		 * against it.
		 * @var array
		 */
		const FIELD_PAIRS = [
			[
				// Icon Leaders
				'post_type' => 'field_e23707a1e766f',
				'posts' => ['field_4bd1752b9b882'],
			],
			[
				// Image Cards Simple Grid
				'post_type' => 'field_9c255d6ac8676',
				'posts' => ['field_442f28d244b0c'],
			],
			[
				// Image Cards Multi Grid - Featured Post and Posts both reload together
				'post_type' => 'field_a06e74ceab19b',
				'posts' => ['field_aee1c8edee822', 'field_89a44260a4ad5'],
			],
		];

		/**
		 * get_pairs function
		 *
		 * @version 1.0.19
		 * @since   1.0.19
		 * @return array
		 */
		public static function get_pairs() {
			return apply_filters('erdc/dynamic_taxonomy_fields/post_type_pairs', self::FIELD_PAIRS);
		}

		/**
		 * register function
		 *
		 * @version 1.0.19
		 * @since   1.0.19
		 */
		public static function register() {

			foreach(self::get_pairs() as $pair) {

				add_filter('acf/load_field/key='.$pair['post_type'], [__CLASS__, 'populate_post_type_choices']);

				foreach($pair['posts'] as $posts_key) {
					add_filter('acf/prepare_field/key='.$posts_key, [__CLASS__, 'populate_post_choices']);
				}

			}

		}

		/**
		 * populate_post_type_choices function
		 *
		 * Populates a Post Type select field's choices with every public post type.
		 *
		 * @version 1.0.19
		 * @since   1.0.19
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		public static function populate_post_type_choices($field) {

			if(self::is_editing_field_group()) {
				return $field;
			}

			$choices = [];

			foreach(get_post_types(['public' => true], 'objects') as $post_type) {
				$choices[$post_type->name] = $post_type->label;
			}

			$field['choices'] = $choices;

			return $field;

		}

		/**
		 * populate_post_choices function
		 *
		 * Turns a Posts `post_object` field into a plain `select` field (so its choices
		 * can be reloaded via AJAX as the paired Post Type field changes), seeded with
		 * the titles of whichever post IDs are already saved. Its `multiple` setting is
		 * left as-is since `select` honours it the same way `post_object` does.
		 *
		 * @version 1.0.19
		 * @since   1.0.19
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		public static function populate_post_choices($field) {

			if(self::is_editing_field_group()) {
				return $field;
			}

			$field['type'] = 'select';
			$field['ui'] = 1;
			$field['ajax'] = 0;
			$field['allow_null'] = 0;
			$field['placeholder'] = '';
			$field['create_options'] = 0;
			$field['save_options'] = 0;
			$field['choices'] = [];

			foreach((array) ($field['value'] ?? []) as $post_id) {

				$post = get_post(intval($post_id));

				if($post) {
					$field['choices'][$post->ID] = get_the_title($post);
				}

			}

			return $field;

		}

		/**
		 * is_editing_field_group function
		 *
		 * @version 1.0.19
		 * @since   1.0.19
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
