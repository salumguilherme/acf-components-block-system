<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Rows;

	use ACBS\Modules\FlexibleLayoutTemplate\Module;

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
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Rows
	 */
	class Assets {

		/**
		 * The row runtime: the action bus, and the code that announces each row on the
		 * page so scripts can attach behaviour to it. Enqueued once when any row renders,
		 * and declared as a dependency by every per-row script, plugin or theme, so
		 * window.acbs is guaranteed to exist before either runs.
		 */
		const RUNTIME_HANDLE = 'acbs-rows';

		/**
		 * @var Assets|null
		 */
		private static $instance = null;

		/**
		 * version function
		 *
		 * filemtime, falling back to ACBS_VERSION if the file cannot be stat'd.
		 *
		 * NOT ACBS_VERSION, and this cost half a day to find. Row assets are deployed on
		 * their own - `npm run deploy` uploads built files and nothing bumps the plugin
		 * version - so every deploy between releases reuses the same `?ver=` string. The
		 * CDN in front of staging serves these with `cache-control: public,
		 * max-age=31536000`, so the old bytes are kept for a YEAR against a URL that never
		 * changes: the page ends up running today's markup against yesterday's CSS and JS.
		 *
		 * It reads as a broken feature rather than a stale file, because the markup is
		 * right and the behaviour is not. When it happened, staging was serving a
		 * columned_content.css with no accordion rules in it at all.
		 *
		 * Module::register_assets() already versions structure and Bootstrap this way, and
		 * enqueue_theme_styles() does it for a theme's own files, for the same reason
		 * written out at more length there. The plugin's own row assets were simply never
		 * moved across.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $relative Path below the plugin root.
		 *
		 * @return string|int
		 */
		private static function version($relative) {

			$time = @filemtime(ACBS_PATH.$relative);

			return false !== $time ? $time : ACBS_VERSION;

		}

		/**
		 * locate function
		 *
		 * The plugin's own built file for a row, as a path below the plugin root, or ''
		 * when the row has no such file. Both spellings are tried, underscore first, so a
		 * source file added as src/css/rows/columned-content.scss is found exactly as
		 * src/css/rows/columned_content.scss is - webpack names the output after the
		 * source, and nothing in the build had to learn about this.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $relative
		 *
		 * @return string
		 */
		private static function locate($relative) {

			foreach(Filenames::variants($relative) as $variant) {

				if(file_exists(ACBS_PATH.$variant)) {
					return $variant;
				}

			}

			return '';

		}

		/**
		 * locate_theme function
		 *
		 * A theme's own file for a row: child theme first, then parent, and within each of
		 * them underscore before dash. Directory is the outer loop for the same reason it
		 * is in Template_Loader - a child theme's dashed file has to beat a parent theme's
		 * underscored one, or the child silently loses.
		 *
		 * Returns the absolute path and the URL together, because the caller needs the
		 * first to stat the file and the second to register it, and deriving one from the
		 * other afterwards is how the two drift apart.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $relative Path below the theme root, e.g. acbs/css/rows/cta.css.
		 *
		 * @return array|null ['file' => absolute path, 'url' => absolute URL], or null.
		 */
		private static function locate_theme($relative) {

			$directories = [get_stylesheet_directory() => get_stylesheet_directory_uri()];
			$directories[get_template_directory()] = get_template_directory_uri();

			foreach($directories as $dir => $uri) {

				foreach(Filenames::variants($relative) as $variant) {

					$file = $dir.'/'.$variant;

					if(file_exists($file)) {
						return ['file' => $file, 'url' => $uri.'/'.$variant];
					}

				}

			}

			return null;

		}

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

			// A row discovered after the footer pass has already run - a theme calling
			// acbs_render_rows() from inside its own footer, say - enqueues immediately
			// instead of being dropped. That still works up to wp_print_footer_scripts; past
			// it the sheet is lost, so say so.
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

			// Registered here, enqueued in flush() and only if a row actually rendered:
			// a page with no rows should not carry the runtime.
			if(!wp_script_is(self::RUNTIME_HANDLE, 'registered') && file_exists(ACBS_PATH.'assets/js/rows.js')) {
				wp_register_script(self::RUNTIME_HANDLE, ACBS_URL.'assets/js/rows.js', [], self::version('assets/js/rows.js'), true);
			}

			foreach(Row_Registry::all() as $name => $type) {

				foreach($type->styles() as $handle) {

					if(wp_style_is($handle, 'registered')) {
						continue;
					}

					// A missing sheet is not an error - it is a row with no styling yet.
					$relative = self::locate('assets/css/rows/'.$name.'.css');

					if('' === $relative) {
						continue;
					}

					wp_register_style($handle, ACBS_URL.$relative, [Module::STRUCTURE_HANDLE], self::version($relative), 'all');

				}

				foreach($type->scripts() as $handle) {

					if(wp_script_is($handle, 'registered')) {
						continue;
					}

					$relative = self::locate('assets/js/rows/'.$name.'.js');

					if('' === $relative) {
						continue;
					}

					// The runtime is a hard dependency: a row script calls
					// acbs.onRowReady() on its first line. Named only when it is actually
					// registered, because an unregistered dependency makes
					// WP_Dependencies::all_deps() drop the script in silence - the same
					// failure enqueue_theme_styles() documents for stylesheets.
					$deps = wp_script_is(self::RUNTIME_HANDLE, 'registered') ? [self::RUNTIME_HANDLE] : [];

					wp_register_script($handle, ACBS_URL.$relative, $deps, self::version($relative), true);

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

			// A row rendered from somewhere the head pass did not predict - a theme calling
			// acbs_render_rows() outside the Page Builder template - still needs the structure
			// sheet, and every row sheet declares it as a dependency, so enqueueing it here is
			// what stops those dependencies silently dropping the row's own CSS.
			if(!empty($this->recorded) && !wp_style_is(Module::STRUCTURE_HANDLE, 'enqueued')) {
				wp_enqueue_style(Module::STRUCTURE_HANDLE);
			}

			// Same reasoning for the runtime, and it is enqueued whether or not any row
			// on this page has a script of its own: it is what fires `acbs/row/ready`,
			// and a theme attaching behaviour to a row the plugin does not script is the
			// normal case, not an edge one.
			if(!empty($this->recorded) && wp_script_is(self::RUNTIME_HANDLE, 'registered')) {
				wp_enqueue_script(self::RUNTIME_HANDLE);
			}

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

			// The plugin sheets that were ACTUALLY enqueued, collected rather than assumed.
			// enqueue_theme_styles() needs to know which handles really exist so it can
			// depend on those and nothing else - see the two failure modes documented there.
			$base = [];

			if($enqueue_default) {

				foreach($type->styles() as $handle) {

					if(wp_style_is($handle, 'registered')) {
						wp_enqueue_style($handle);
						$base[] = $handle;
					}

				}

			}

			// A theme's own sheet for the row layers ON TOP of the plugin's rather than
			// replacing it, declared as a dependency so the order is a promise rather than a
			// side effect of enqueue order. Note this is the opposite of how TEMPLATES
			// resolve, where the theme's file replaces the plugin's - deliberately, since
			// there is no sane way to layer two PHP files.
			$this->enqueue_theme_styles($type, $base);

			$base_scripts = [];

			foreach($type->scripts() as $handle) {

				if(wp_script_is($handle, 'registered')) {
					wp_enqueue_script($handle);
					$base_scripts[] = $handle;
				}

			}

			$this->enqueue_theme_scripts($type, $base_scripts);

		}

		/**
		 * enqueue_theme_styles function
		 *
		 * A theme's own sheet for a row, from {theme}/acbs/css/rows/{layout}.css - or
		 * {layout} written with dashes, which resolves identically. Child theme first, then
		 * parent, and underscore before dash inside each of them; the file existing is the
		 * whole trigger, there is no hook to call.
		 *
		 * THE DEPENDENCY IS WHAT WAS ACTUALLY ENQUEUED, not what the row type declares, and
		 * that distinction is the whole point of this function. It used to pass
		 * `$type->styles()` straight through, which broke in two ways, both silently:
		 *
		 *   1. A layout REGISTERED BY A THEME has no sheet in the plugin, so
		 *      Assets::register_row_assets() never registers `acbs-row-{layout}` - it only
		 *      registers handles that have a built file behind them. Naming it as a
		 *      dependency then meant WP_Dependencies::all_deps() computed a non-empty
		 *      array_diff against the registered list, set $keep_going = false, and dropped
		 *      the stylesheet entirely. No warning, no output: a theme could put the file in
		 *      exactly the documented place and never see it load.
		 *
		 *   2. `acbs/styles/enqueue_default` returning false is supposed to drop the
		 *      plugin's sheet so a site can style a row from nothing. But a dependency is
		 *      enqueued whether or not anyone asked for it, so the theme sheet dragged the
		 *      plugin's back in through the dependency chain and the filter did nothing.
		 *
		 * Falling back to the structure handle keeps the ordering promise in both cases:
		 * structure already depends on the scoped Bootstrap, so a theme sheet still lands
		 * after both of them.
		 *
		 * VERSION IS filemtime, not ACBS_VERSION. A theme edits its own CSS on its own
		 * schedule and has no reason to bump the plugin's version, so keying the cache to
		 * ACBS_VERSION meant an edited theme sheet kept serving the old file - which reads
		 * as "my change did not deploy". Same approach Module uses for structure and
		 * Bootstrap.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row_Type $type
		 * @param array    $base Plugin style handles actually enqueued for this row.
		 */
		private function enqueue_theme_styles(Row_Type $type, array $base = []) {

			$name = $type->name();

			// Child theme wins; the parent's copy is not also loaded. The handle is keyed
			// to the LAYOUT name, so both spellings register under acbs-row-{layout}-theme
			// and a theme cannot end up loading two copies of its own sheet.
			$located = self::locate_theme('acbs/css/rows/'.$name.'.css');

			if(is_null($located)) {
				return;
			}

			$handle = Row_Type_Base::STYLE_PREFIX.$name.'-theme';

			if(!wp_style_is($handle, 'registered')) {

				$deps = $base;

				if(!$deps && wp_style_is(Module::STRUCTURE_HANDLE, 'registered')) {
					$deps = [Module::STRUCTURE_HANDLE];
				}

				$version = filemtime($located['file']);
				$version = false !== $version ? $version : ACBS_VERSION;

				wp_register_style($handle, $located['url'], $deps, $version, 'all');

			}

			wp_enqueue_style($handle);

		}

		/**
		 * enqueue_theme_scripts function
		 *
		 * A theme's own script for a row, from {theme}/acbs/js/rows/{layout}.js - or
		 * {layout} written with dashes. Child theme first, then parent; the file existing
		 * is the whole trigger, there is no hook to call. The mirror image of
		 * enqueue_theme_styles(), and it inherits both of that method's hard-won rules:
		 *
		 *   1. THE DEPENDENCY IS WHAT WAS ACTUALLY ENQUEUED. A layout the plugin does not
		 *      script has no `acbs-row-{layout}` registered, and naming an unregistered
		 *      handle makes WP_Dependencies::all_deps() set $keep_going = false and drop
		 *      the item silently. Falling back to the runtime handle keeps the ordering
		 *      promise either way, and the runtime is what a theme script actually needs:
		 *      it calls acbs.onRowReady().
		 *
		 *   2. VERSION IS filemtime. A theme edits its own JS without bumping the
		 *      plugin's version, so keying the cache to ACBS_VERSION serves the old file
		 *      after a deploy - which reads as "my change did not upload".
		 *
		 * Note this is additive where a theme TEMPLATE is a replacement. A theme script
		 * layers on the plugin's rather than replacing it, so a theme can extend a row the
		 * plugin already scripts without reimplementing it - which is the whole reason the
		 * runtime announces rows through a replaying subscription rather than a plain
		 * event.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row_Type $type
		 * @param array    $base Plugin script handles actually enqueued for this row.
		 */
		private function enqueue_theme_scripts(Row_Type $type, array $base = []) {

			$name = $type->name();

			// Child theme wins; the parent's copy is not also loaded.
			$located = self::locate_theme('acbs/js/rows/'.$name.'.js');

			if(is_null($located)) {
				return;
			}

			$handle = Row_Type_Base::SCRIPT_PREFIX.$name.'-theme';

			if(!wp_script_is($handle, 'registered')) {

				$deps = $base;

				if(!$deps && wp_script_is(self::RUNTIME_HANDLE, 'registered')) {
					$deps = [self::RUNTIME_HANDLE];
				}

				$version = filemtime($located['file']);
				$version = false !== $version ? $version : ACBS_VERSION;

				wp_register_script($handle, $located['url'], $deps, $version, true);

			}

			wp_enqueue_script($handle);

		}

	}
