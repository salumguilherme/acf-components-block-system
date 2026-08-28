<?php

	namespace ERDC\Modules\FlexibleLayoutTemplate\Rows;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Assets
	 *
	 * Per-row styles and scripts, enqueued in the footer for the rows that actually
	 * rendered.
	 *
	 * Elementor only enqueued a widget's assets when that widget was on the page, and
	 * that behaviour has to be rebuilt. The obvious approach - work out which layouts are
	 * on the page before wp_head - has a timing problem: rows are only discovered when
	 * they are looped, which happens inside the template, long after the head is printed.
	 *
	 * So the assets follow the rows instead: each row type DECLARES its handles, the
	 * renderer records what it rendered, and everything is enqueued on
	 * wp_enqueue_scripts' footer pass. wp_enqueue_style() called after wp_head prints its
	 * <link> in the footer, which browsers honour. Nested and late-discovered rows are
	 * covered by construction rather than by a fallback.
	 *
	 * The cost is that a footer stylesheet applies after first paint, so a row can flash
	 * unstyled. That is why only the per-row sheets go here: the sheets that need no
	 * discovery at all - tokens, Bootstrap, structure - are enqueued on every page in the
	 * head as normal, which caps the flash to row-specific rules.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ERDC\Modules\FlexibleLayoutTemplate\Rows
	 */
	class Assets {

		/**
		 * @var Assets|null
		 */
		private static $instance = null;

		/**
		 * Row types recorded this request, keyed by layout name.
		 *
		 * @var Row_Type[]
		 */
		private $recorded = [];

		/**
		 * Whether the footer pass has already run.
		 *
		 * @var bool
		 */
		private $flushed = false;

		/**
		 * instance function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return Assets
		 */
		public static function instance() {

			if(is_null(self::$instance)) {
				self::$instance = new self();
			}

			return self::$instance;

		}

		/**
		 * register function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function register() {

			// Registers the per-row handles that have a built file behind them, so a row
			// type declaring 'acbs-row-full_width_content' does not have to know where the
			// file lives or whether it exists yet.
			add_action('wp_enqueue_scripts', [$this, 'register_row_assets'], 5);

			// wp_footer at 5, comfortably before core's wp_print_footer_scripts at 20 -
			// which is what prints the late styles a footer enqueue produces.
			add_action('wp_footer', [$this, 'flush'], 5);

		}

		/**
		 * record function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row_Type $type
		 */
		public function record(Row_Type $type) {

			$name = $type->name();

			if(isset($this->recorded[$name])) {
				return;
			}

			$this->recorded[$name] = $type;

			// A row discovered after the footer pass has already run - a shortcode in a
			// widget area, say - enqueues immediately instead of being dropped. That still
			// works up to wp_print_footer_scripts; past it the sheet is lost, so say so.
			if($this->flushed) {

				$this->enqueue($type);

				if(defined('WP_DEBUG') && WP_DEBUG) {
					trigger_error(
						sprintf(
							'ACBS: row layout "%s" rendered after the footer asset pass and was enqueued late.%s',
							$name,
							did_action('wp_print_footer_scripts') ? ' Styles had already been printed, so its stylesheet did not load.' : ''
						),
						E_USER_NOTICE
					);
				}

			}

		}

		/**
		 * register_row_assets function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function register_row_assets() {

			foreach(Row_Registry::all() as $name => $type) {

				foreach($type->styles() as $handle) {

					if(wp_style_is($handle, 'registered')) {
						continue;
					}

					$relative = 'assets/css/rows/'.$name.'.css';

					// Phase 04 builds these. Until then there is nothing to register, and a
					// missing sheet is not an error - it is a row with no styling yet.
					if(!file_exists(ACBS_PATH.$relative)) {
						continue;
					}

					wp_register_style($handle, ACBS_URL.$relative, ['erdc-frontend'], ACBS_VERSION, 'all');

				}

				foreach($type->scripts() as $handle) {

					if(wp_script_is($handle, 'registered')) {
						continue;
					}

					$relative = 'assets/js/rows/'.$name.'.js';

					if(!file_exists(ACBS_PATH.$relative)) {
						continue;
					}

					wp_register_script($handle, ACBS_URL.$relative, [], ACBS_VERSION, true);

				}

			}

		}

		/**
		 * flush function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function flush() {

			if($this->flushed) {
				return;
			}

			$this->flushed = true;

			foreach($this->recorded as $type) {
				$this->enqueue($type);
			}

		}

		/**
		 * enqueue function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row_Type $type
		 */
		private function enqueue(Row_Type $type) {

			/**
			 * Filters whether the plugin's own base sheet for a layout is enqueued at all,
			 * for a site that wants to style a row from nothing.
			 *
			 * @param bool     $enqueue
			 * @param string   $layout
			 * @param Row_Type $type
			 */
			$enqueue_default = (bool) apply_filters('acbs/styles/enqueue_default', true, $type->name(), $type);

			if($enqueue_default) {

				foreach($type->styles() as $handle) {

					if(wp_style_is($handle, 'registered')) {
						wp_enqueue_style($handle);
					}

				}

			}

			// A theme's own sheet for the row layers ON TOP of the plugin's rather than
			// replacing it, declared as a dependency so the order is a promise rather than a
			// side effect of enqueue order. Note this is the opposite of how TEMPLATES
			// resolve, where the theme's file replaces the plugin's - deliberately, since
			// there is no sane way to layer two PHP files.
			$this->enqueue_theme_styles($type);

			foreach($type->scripts() as $handle) {

				if(wp_script_is($handle, 'registered')) {
					wp_enqueue_script($handle);
				}

			}

		}

		/**
		 * enqueue_theme_styles function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row_Type $type
		 */
		private function enqueue_theme_styles(Row_Type $type) {

			$name = $type->name();
			$relative = 'acbs/css/rows/'.$name.'.css';

			foreach([get_stylesheet_directory() => get_stylesheet_directory_uri(), get_template_directory() => get_template_directory_uri()] as $dir => $uri) {

				if(!file_exists($dir.'/'.$relative)) {
					continue;
				}

				$handle = Row_Type_Base::STYLE_PREFIX.$name.'-theme';

				if(!wp_style_is($handle, 'registered')) {
					wp_register_style($handle, $uri.'/'.$relative, $type->styles(), ACBS_VERSION, 'all');
				}

				wp_enqueue_style($handle);

				// Child theme wins; the parent's copy is not also loaded.
				break;

			}

		}

	}
