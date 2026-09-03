<?php

	namespace ACBS\Modules\DynamicTaxonomyFields;

	use ACBS\Core\Module_Base as Base_Module;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Module
	 *
	 * Powers the "Source: Repeater / Taxonomy / Post Type" pattern used by several
	 * Flexible Layout layouts (see
	 * ACBS\Modules\FlexibleLayoutTemplate\Fields\Page_Content) - when "Taxonomy" is
	 * selected, a Taxonomy select field is shown, and picking a taxonomy there reloads
	 * a Terms field's choices via AJAX; when "Post Type" is selected, the same happens
	 * between a Post Type select field and a Posts field. Migrated from the
	 * five-starter-child theme's includes/acf.php + js/acf-icon-taxonomy.js.
	 *
	 * A Taxonomy pair can optionally also declare 'post_type_source' field key(s) (see
	 * Taxonomy_Source_Fields) - when it does, the Taxonomy field's OWN choices are, in
	 * turn, filtered down to whatever taxonomies are actually registered on the post
	 * type(s) currently selected there, one level up from the Taxonomy -> Terms reload.
	 *
	 * Kept as its own module (rather than folded into flexible-layout-template) since
	 * it will be extended with further source field pairs over time.
	 *
	 * @version 1.0.28
	 * @since   1.0.18
	 * @package ACBS\Modules\DynamicTaxonomyFields
	 */
	class Module extends Base_Module {

		/**
		 * get_name function
		 *
		 * @version 1.0.18
		 * @since   1.0.18
		 * @return string
		 */
		public function get_name() {
			return 'dynamic-taxonomy-fields';
		}

		/**
		 * Constructor
		 */
		public function __construct() {

			parent::__construct();

			if(!function_exists('acf_add_local_field_group')) {
				return;
			}

			add_action('acf/init', [Taxonomy_Source_Fields::class, 'register']);
			add_action('acf/init', [Post_Type_Source_Fields::class, 'register']);
			add_action('wp_ajax_'.Ajax::TERMS_ACTION, [Ajax::class, 'get_term_choices']);
			add_action('wp_ajax_'.Ajax::POSTS_ACTION, [Ajax::class, 'get_post_choices']);
			add_action('wp_ajax_'.Ajax::POST_TYPE_TAXONOMIES_ACTION, [Ajax::class, 'get_taxonomies_for_post_types']);
			add_action('acf/input/admin_enqueue_scripts', [$this, 'enqueue_assets']);

		}

		/**
		 * enqueue_assets function
		 *
		 * @version 1.0.28
		 * @since   1.0.18
		 */
		public function enqueue_assets() {

			wp_enqueue_script(
				'erdc-dynamic-taxonomy-fields',
				ACBS_URL.'assets/js/dynamic-taxonomy-fields.js',
				['jquery', 'acf-input'],
				ACBS_VERSION,
				true
			);

			wp_localize_script('erdc-dynamic-taxonomy-fields', 'erdcDynamicTaxonomyFields', [
				'ajaxurl' => admin_url('admin-ajax.php'),
				'groups' => [
					[
						'action' => Ajax::TERMS_ACTION,
						'nonce' => wp_create_nonce(Ajax::TERMS_ACTION),
						'pairs' => array_map(function($pair) {
							return ['parent' => $pair['taxonomy'], 'children' => $pair['terms']];
						}, Taxonomy_Source_Fields::get_pairs()),
					],
					[
						'action' => Ajax::POSTS_ACTION,
						'nonce' => wp_create_nonce(Ajax::POSTS_ACTION),
						'pairs' => array_map(function($pair) {
							return ['parent' => $pair['post_type'], 'children' => $pair['posts']];
						}, Post_Type_Source_Fields::get_pairs()),
					],
				],
				// A Taxonomy field's own choices, filtered by whatever post type(s) are
				// selected in its paired post_type_source field(s) - only present for
				// pairs that actually declare 'post_type_source' (see
				// Taxonomy_Source_Fields), everything else keeps showing every public
				// taxonomy exactly as before.
				'postTypeTaxonomyFilters' => [
					'action' => Ajax::POST_TYPE_TAXONOMIES_ACTION,
					'nonce' => wp_create_nonce(Ajax::POST_TYPE_TAXONOMIES_ACTION),
					'pairs' => array_values(array_filter(array_map(function($pair) {

						if(empty($pair['post_type_source'])) {
							return null;
						}

						return [
							'sources' => $pair['post_type_source'],
							'taxonomy' => $pair['taxonomy'],
						];

					}, Taxonomy_Source_Fields::get_pairs()))),
				],
			]);

		}

	}
