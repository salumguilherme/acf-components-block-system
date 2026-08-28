<?php

	namespace ERDC\Modules\FlexibleLayoutTemplate;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Source_Resolver
	 *
	 * Answers one question: WHOSE `page_sections` should this request read?
	 *
	 * Lifted out of the deleted repeater-dynamic-tags module (which held the only copy of
	 * this logic, duplicated again in the old Module) and kept with the render layer that
	 * uses it rather than in core/, since nothing else needs it.
	 *
	 * Two changes from the original:
	 *
	 * 1. The taxonomy branch no longer asks Elementor Pro's Taxonomy_Loop_Provider
	 *    whether we are inside a loop over terms. We own the loop now, so the term comes
	 *    from our own context (see push()/pop()) or from the queried object.
	 *
	 * 2. Everything is normalised through acf_get_valid_post_id() exactly once, and the
	 *    normalised value is what callers pass to have_rows(). ACF keys its loop stack on
	 *    "selector={$selector}/post_id={$post_id}" AFTER normalisation, so a WP_Term in
	 *    one call and "term_12" in the next would silently open a second loop over the
	 *    same field. Resolving once removes the possibility.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ERDC\Modules\FlexibleLayoutTemplate
	 */
	class Source_Resolver {

		/**
		 * Explicit source context, innermost last. Pushed by the renderer when it renders
		 * rows for something other than the queried object.
		 *
		 * @var array
		 */
		private static $stack = [];

		/**
		 * Memoised normalised ids, keyed by the raw source's own cache key.
		 *
		 * @var array
		 */
		private static $normalised = [];

		/**
		 * push function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param mixed $source
		 */
		public static function push($source) {
			self::$stack[] = $source;
		}

		/**
		 * pop function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function pop() {
			array_pop(self::$stack);
		}

		/**
		 * resolve function
		 *
		 * The normalised ACF post id to read from, ready to hand straight to have_rows().
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param mixed $source Optional explicit source: a post id, WP_Post, WP_Term,
		 *                      'options', or null to work it out from the request.
		 *
		 * @return int|string
		 */
		public static function resolve($source = null) {

			if(is_null($source) || '' === $source) {
				$source = !empty(self::$stack) ? end(self::$stack) : self::from_request();
			}

			return self::normalise($source);

		}

		/**
		 * normalise function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param mixed $source
		 *
		 * @return int|string
		 */
		public static function normalise($source) {

			// A falsy source means "whatever the global post is", which changes as the main
			// loop advances - so it is resolved fresh every time rather than memoised.
			if(empty($source)) {
				return function_exists('acf_get_valid_post_id') ? acf_get_valid_post_id($source) : $source;
			}

			$key = self::cache_key($source);

			if(!isset(self::$normalised[$key])) {
				self::$normalised[$key] = function_exists('acf_get_valid_post_id') ? acf_get_valid_post_id($source) : $source;
			}

			return self::$normalised[$key];

		}

		/**
		 * from_request function
		 *
		 * The object whose fields this request should render, before normalisation.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @return \WP_Post|\WP_Term|int|null
		 */
		public static function from_request() {

			// WooCommerce's shop archive has no post of its own; its layouts live on the
			// page assigned as the shop page. Optional dependency - guarded, not assumed.
			if(function_exists('is_shop') && function_exists('wc_get_page_id') && is_shop()) {

				$shop = get_post(wc_get_page_id('shop'));

				if($shop instanceof \WP_Post) {
					return $shop;
				}

			}

			// A post type archive or the blog index likewise has no edit screen, so its
			// layouts live on a stand-in page.
			if(is_post_type_archive() || is_home()) {
				return self::archive_source_page();
			}

			// Everything else - singular, term archive, front page - reads from whatever the
			// main query resolved to. A term archive therefore reads the term's own fields.
			$queried = get_queried_object();

			return ($queried instanceof \WP_Post || $queried instanceof \WP_Term) ? $queried : null;

		}

		/**
		 * archive_source_page function
		 *
		 * The published page standing in for the current archive, or null.
		 *
		 * Matching is done on the path rather than through url_to_postid(), which resolves
		 * an archive URL to the archive rewrite rule and never to a page.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @return \WP_Post|null
		 */
		public static function archive_source_page() {

			// Page for posts
			if(is_home() && !empty(get_option('page_for_posts'))) {

				$page = get_post(get_option('page_for_posts'));

				if($page instanceof \WP_Post && 'publish' === $page->post_status) {
					return $page;
				}

			}

			$path = self::archive_path();

			if('' === $path) {
				return null;
			}

			$page = get_page_by_path($path);

			return ($page instanceof \WP_Post && 'publish' === $page->post_status) ? $page : null;

		}

		/**
		 * archive_path function
		 *
		 * The current post type archive's URL as a site-relative path, with any
		 * subdirectory install prefix removed, ready for get_page_by_path().
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @return string
		 */
		public static function archive_path() {

			if(!is_post_type_archive()) {
				return '';
			}

			$post_type = get_query_var('post_type');

			if(is_array($post_type)) {
				$post_type = reset($post_type);
			}

			$link = $post_type ? get_post_type_archive_link($post_type) : '';

			if(!$link) {
				return '';
			}

			$path = trim((string) wp_parse_url($link, PHP_URL_PATH), '/');
			$home = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');

			// Subdirectory installs carry the install path on both, so strip it
			if('' !== $home && 0 === strpos($path, $home.'/')) {
				$path = substr($path, strlen($home) + 1);
			} elseif('' !== $home && $path === $home) {
				$path = '';
			}

			return $path;

		}

		/**
		 * context function
		 *
		 * The request context used as a template cascade candidate: 'archive', 'tax',
		 * 'options', 'front-page' or ''.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param mixed $source The already-normalised source.
		 *
		 * @return string
		 */
		public static function context($source = null) {

			if('option' === $source || 'options' === $source) {
				return 'options';
			}

			if(is_front_page()) {
				return 'front-page';
			}

			if(is_tax() || is_category() || is_tag()) {
				return 'tax';
			}

			if(is_post_type_archive() || is_home() || is_archive()) {
				return 'archive';
			}

			return '';

		}

		/**
		 * cache_key function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param mixed $source
		 *
		 * @return string
		 */
		private static function cache_key($source) {

			if($source instanceof \WP_Post) {
				return 'post:'.$source->ID;
			}

			if($source instanceof \WP_Term) {
				return 'term:'.$source->term_id;
			}

			if(is_scalar($source)) {
				return 'scalar:'.$source;
			}

			return 'null';

		}

	}
