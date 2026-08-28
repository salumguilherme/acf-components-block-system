<?php

	namespace ERDC\Modules\DynamicTaxonomyFields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Ajax
	 *
	 * Handles the AJAX requests that reload a Terms/Posts field's choices whenever its
	 * paired Taxonomy/Post Type field changes (see Taxonomy_Source_Fields,
	 * Post_Type_Source_Fields and assets/js/dynamic-taxonomy-fields.js).
	 *
	 * get_term_choices() is the one of the two that can be asked for more than one
	 * taxonomy at once (a Taxonomy field configured to select multiple values) and, when
	 * it is, groups its response by taxonomy - see that method.
	 *
	 * @version 1.0.28
	 * @since   1.0.18
	 * @package ERDC\Modules\DynamicTaxonomyFields
	 */
	class Ajax {

		/**
		 * Used as both the wp_ajax action name and the nonce action for the Terms reload.
		 * @var string
		 */
		const TERMS_ACTION = 'erdc_taxonomy_source_terms';

		/**
		 * Used as both the wp_ajax action name and the nonce action for the Posts reload.
		 * @var string
		 */
		const POSTS_ACTION = 'erdc_post_type_source_posts';

		/**
		 * Used as both the wp_ajax action name and the nonce action for reloading a
		 * Taxonomy field'''s OWN choices, restricted to whatever post type(s) are
		 * currently selected in its paired post_type_source field(s) - see
		 * Taxonomy_Source_Fields and the 'post_type_source' pair key.
		 * @var string
		 */
		const POST_TYPE_TAXONOMIES_ACTION = 'erdc_post_type_source_taxonomies';

		/**
		 * get_term_choices function
		 *
		 * A Taxonomy field posts a single slug when it is a plain Select, or an array of
		 * them when it is a Select with "Select multiple values?" on - jQuery's own .val()
		 * already returns an array for a <select multiple>, and its default param
		 * serialization turns that into value[]=a&value[]=b, which PHP parses back into an
		 * array with no extra work on either end.
		 *
		 * Whether the RESPONSE is grouped is decided from what actually came through, not
		 * from the field's own config: one valid taxonomy (whether the field could hold
		 * more or not) stays a flat list; more than one groups the response by taxonomy, so
		 * a multi-select field with only one taxonomy picked still gets the plain,
		 * ungrouped choices a single-select field would.
		 *
		 * @version 1.0.28
		 * @since   1.0.18
		 */
		public static function get_term_choices() {

			check_ajax_referer(self::TERMS_ACTION, 'nonce');

			self::require_capability();

			$taxonomies = self::sanitize_taxonomies($_POST['value'] ?? '');

			if(empty($taxonomies)) {
				wp_send_json_error('Invalid taxonomy');
			}

			if(count($taxonomies) === 1) {
				wp_send_json_success([
					'grouped' => false,
					'choices' => self::get_flat_term_choices($taxonomies[0]),
				]);
			}

			$groups = [];

			foreach($taxonomies as $taxonomy) {

				$options = self::get_flat_term_choices($taxonomy);

				if(empty($options)) {
					continue;
				}

				$taxonomy_object = get_taxonomy($taxonomy);

				$groups[] = [
					'label' => $taxonomy_object ? $taxonomy_object->label : $taxonomy,
					'options' => $options,
				];

			}

			wp_send_json_success([
				'grouped' => true,
				'choices' => $groups,
			]);

		}

		/**
		 * require_capability function
		 *
		 * A valid nonce proves the request came from a page we rendered; it does not prove
		 * the person sending it is allowed to read the site's terms and post titles. These
		 * three endpoints only ever populate choices on an ACF admin field, so anyone who
		 * cannot edit content has no business calling them.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		private static function require_capability() {

			if(!current_user_can('edit_posts')) {
				wp_send_json_error('Forbidden', 403);
			}

		}

		/**
		 * sanitize_taxonomies function
		 *
		 * Normalises the posted value to an array (a single Select posts a string, a
		 * multi-select an array) and keeps only real, public taxonomy slugs, deduplicated.
		 *
		 * @version 1.0.28
		 * @since   1.0.20
		 *
		 * @param string|array $value
		 *
		 * @return array
		 */
		private static function sanitize_taxonomies($value) {

			$taxonomies = [];

			foreach((array) $value as $candidate) {

				$candidate = sanitize_text_field($candidate);

				if($candidate !== '' && taxonomy_exists($candidate) && !in_array($candidate, $taxonomies, true)) {
					$taxonomies[] = $candidate;
				}

			}

			return $taxonomies;

		}

		/**
		 * get_flat_term_choices function
		 *
		 * One taxonomy's own terms, flattened - the same shape returned for a single
		 * taxonomy today, factored out so both the flat and grouped responses build each
		 * taxonomy's own option list identically.
		 *
		 * @version 1.0.28
		 * @since   1.0.20
		 *
		 * @param string $taxonomy
		 *
		 * @return array
		 */
		private static function get_flat_term_choices($taxonomy) {

			$choices = [];

			foreach(self::flatten_hierarchy(self::get_taxonomy_hierarchy($taxonomy)) as $term_id => $name) {
				$choices[] = [
					'label' => $name,
					'value' => $term_id,
				];
			}

			return $choices;

		}

		/**
		 * get_post_choices function
		 *
		 * @version 1.0.19
		 * @since   1.0.19
		 */
		public static function get_post_choices() {

			check_ajax_referer(self::POSTS_ACTION, 'nonce');

			self::require_capability();

			$post_type = sanitize_text_field($_POST['value'] ?? '');

			if(!post_type_exists($post_type)) {
				wp_send_json_error('Invalid post type');
			}

			$posts = get_posts([
				'post_type' => $post_type,
				'post_status' => 'publish',
				'numberposts' => -1,
				'orderby' => 'title',
				'order' => 'ASC',
			]);

			$choices = [];

			foreach($posts as $post) {
				$choices[] = [
					'label' => get_the_title($post),
					'value' => $post->ID,
				];
			}

			wp_send_json_success($choices);

		}

		/**
		 * get_taxonomies_for_post_types function
		 *
		 * A Taxonomy field's own choices, restricted to the taxonomies actually
		 * registered against the given post type(s) - one more level of filtering above
		 * get_term_choices(): that one narrows a Terms field by taxonomy, this one
		 * narrows the Taxonomy field itself by post type. Post type(s) are posted the
		 * same way taxonomies are for get_term_choices() - a single slug from a plain
		 * Select, or an array from a multi-select or from combining several separate
		 * post_type_source fields client-side (see dynamic-taxonomy-fields.js).
		 *
		 * Always a flat, deduplicated union across every post type given - unlike
		 * get_term_choices(), there is no per-source grouping here, since this is
		 * narrowing ONE field's choices rather than merging several taxonomies' terms.
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 */
		public static function get_taxonomies_for_post_types() {

			check_ajax_referer(self::POST_TYPE_TAXONOMIES_ACTION, 'nonce');

			self::require_capability();

			$post_types = self::sanitize_post_types($_POST['value'] ?? '');

			if(empty($post_types)) {
				wp_send_json_error('Invalid post type');
			}

			$taxonomy_slugs = [];

			foreach($post_types as $post_type) {

				foreach(get_object_taxonomies($post_type) as $taxonomy_slug) {

					if(!in_array($taxonomy_slug, $taxonomy_slugs, true)) {
						$taxonomy_slugs[] = $taxonomy_slug;
					}

				}

			}

			$choices = [];

			foreach($taxonomy_slugs as $taxonomy_slug) {

				$taxonomy = get_taxonomy($taxonomy_slug);

				// Matches populate_taxonomy_choices()'s own restriction to public
				// taxonomies, so a post-type-filtered list is never broader than the
				// unfiltered one a site would otherwise see.
				if(!$taxonomy || !$taxonomy->public) {
					continue;
				}

				$choices[] = [
					'label' => $taxonomy->label,
					'value' => $taxonomy_slug,
				];

			}

			wp_send_json_success([
				'grouped' => false,
				'choices' => $choices,
			]);

		}

		/**
		 * sanitize_post_types function
		 *
		 * Normalises the posted value to an array (a single Select posts a string, a
		 * multi-select or combined multi-source value an array) and keeps only real
		 * post type slugs, deduplicated - mirrors sanitize_taxonomies().
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 *
		 * @param string|array $value
		 *
		 * @return array
		 */
		private static function sanitize_post_types($value) {

			$post_types = [];

			foreach((array) $value as $candidate) {

				$candidate = sanitize_text_field($candidate);

				if($candidate !== '' && post_type_exists($candidate) && !in_array($candidate, $post_types, true)) {
					$post_types[] = $candidate;
				}

			}

			return $post_types;

		}

		/**
		 * get_taxonomy_hierarchy function
		 *
		 * @version 1.0.18
		 * @since   1.0.18
		 *
		 * @param string $taxonomy
		 * @param int    $parent
		 *
		 * @return array
		 */
		private static function get_taxonomy_hierarchy($taxonomy, $parent = 0) {

			$terms = get_terms([
				'taxonomy' => $taxonomy,
				'parent' => $parent,
				'hide_empty' => false,
				'orderby' => 'name',
				'order' => 'ASC',
			]);

			$children = [];

			foreach($terms as $term) {
				$term->children = self::get_taxonomy_hierarchy($taxonomy, $term->term_id);
				$children[$term->term_id] = $term;
			}

			return $children;

		}

		/**
		 * flatten_hierarchy function
		 *
		 * Flattens the nested term hierarchy into a single [term_id => indented name]
		 * list, preserving parent/child order.
		 *
		 * @version 1.0.18
		 * @since   1.0.18
		 *
		 * @param array $terms
		 * @param int   $level
		 *
		 * @return array
		 */
		private static function flatten_hierarchy(array $terms, $level = 0) {

			$flattened = [];

			foreach($terms as $term) {

				$flattened[$term->term_id] = str_repeat('– ', $level).$term->name;

				if(!empty($term->children)) {
					$flattened = $flattened + self::flatten_hierarchy($term->children, $level + 1);
				}

			}

			return $flattened;

		}

	}

	