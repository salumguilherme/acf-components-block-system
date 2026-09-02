<?php
	
	namespace ACBS\Modules\FlexibleLayoutTemplate;
	
	use ACBS\Core\Module_Base as Base_Module;
	use ACBS\Modules\FlexibleLayoutTemplate\Fields\Field_Groups;
	use ACBS\Modules\FlexibleLayoutTemplate\Fields\Common_Fields;
	use ACBS\Modules\FlexibleLayoutTemplate\Fields\Page_Content;
	use ACBS\Modules\FlexibleLayoutTemplate\Fields\Layout_Title;
	use ACBS\Modules\FlexibleLayoutTemplate\Rows\Assets;
	use ACBS\Modules\FlexibleLayoutTemplate\Rows\Row;
	use ACBS\Modules\FlexibleLayoutTemplate\Rows\Row_Registry;
	
	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}
	
	/**
	 * Class Module
	 *
	 * Owns the `page_sections` field subsystem and the row render layer.
	 *
	 * The Elementor half of this module - the Flexible Layout document type, the theme
	 * location, the display conditions, the widget, the row cache and the six
	 * client-specific loop query filters - was removed with the decoupling. What is left
	 * is field registration, the wrapper's class and id contributions, and wiring the
	 * render layer up.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate
	 */
	class Module extends Base_Module {
		
		/**
		 * get_name function
		 *
		 * @version 1.0.5
		 * @since   1.0.0
		 * @return string
		 */
		/**
		 * Handle of the always-loaded row structure sheet. Every per-row sheet declares it
		 * as a dependency, so it is also what guarantees load order.
		 */
		const STRUCTURE_HANDLE = 'acbs-structure';

		public function get_name() {
			return 'flexible-layout-template';
		}
		
		/**
		 * Constructor
		 */
		public function __construct() {
			
			parent::__construct();
			
			// Registers the always-loaded front end CSS. Per-row sheets are registered and
			// enqueued by Rows\Assets, in the footer, as the rows are actually rendered.
			add_action('wp_enqueue_scripts', [$this, 'register_assets']);
			
			// The public render API - plain functions, so required rather than autoloaded
			require_once __DIR__.'/rows/template-tags.php';
			
			// Row types, and the footer asset collector that reads their declarations.
			// Priority 20 on init is after ACF's own init at 5 has fired acf/init, so the
			// field groups the registry reads through get_current_layouts() exist.
			add_action('init', [Row_Registry::class, 'boot'], 20);
			Assets::instance()->register();
			
			// Registers the plugin's local ACF field groups (Page Content, Buttons and their common fields)
			if(function_exists('acf_add_local_field_group')) {
				add_action('acf/init', [$this, 'register_location_rules']);
				add_action('acf/init', [$this, 'register_field_groups']);
				add_action('acf/include_field_types', [Field_Groups::class, 'register_field_types'], 20);
				add_action('acf/input/admin_enqueue_scripts', [$this, 'enqueue_flexible_layout_tabs_assets']);
				add_action('acf/input/admin_enqueue_scripts', [$this, 'enqueue_buttons_repeater_field_assets']);
				add_action('acf/input/admin_enqueue_scripts', [$this, 'enqueue_layout_title_assets']);

				// The computed layout title. Registered here rather than on acf/init
				// because it is a filter ACF applies at render time, not a registration.
				Layout_Title::register();
			}
			
			// "Disable Default Flexible Layouts" section on the plugin's Flexible Content tab
			add_action('acbs/admin/settings', [Settings::class, 'register'], 21);
			
			// "Download Flexible Layouts" section, at the bottom of the same tab.
			Layouts_Export::register();
			
			// "Page Builder" in the page editor's Template dropdown, served from the
			// plugin. Registered here rather than in core/, so it exists exactly as long as
			// the module that renders the rows does.
			Page_Template::register();
			
			// Adds our wrapper classes and ids
			add_filter('acbs/row/wrapper_classes', [$this, 'layout_wrapper_classes'], 10, 2);
			add_filter('acbs/row/wrapper_id', [$this, 'layout_wrapper_id'], 10, 2);
			add_filter('acbs/row/wrapper_style', [$this, 'layout_wrapper_style'], 10, 2);
			
			// Befopre container action - add field overlay
			
		}
		
		/**
		 * register_assets function
		 *
		 * @version 1.0.13
		 * @since   1.0.13
		 */
		public function register_assets() {

			$bootstrap = self::bootstrap_handle();

			// The plugin's own copy of Bootstrap, every selector rewritten to sit under
			// `.acbs.fl-acbs` at build time. Registered under a filterable handle because a site
			// whose theme already ships a scoped build can point us at theirs instead and stop
			// this one loading at all.
			if(!wp_style_is($bootstrap, 'registered')) {
				wp_register_style($bootstrap, ACBS_URL.'assets/css/rows-bootstrap.css', [], filemtime(ACBS_PATH.'/assets/css/rows-bootstrap.css'), 'all');
			}

			// Structure depends on Bootstrap, and that dependency is not cosmetic: it resets the
			// white background Bootstrap's rewritten `body` rules put on the wrapper, at the same
			// specificity, so it only wins by loading second.
			wp_register_style(self::STRUCTURE_HANDLE, ACBS_URL.'assets/css/structure.css', [$bootstrap], filemtime(ACBS_PATH.'/assets/css/structure.css'), 'all');

			// Head, not footer: neither of these needs a row to be discovered first, and a
			// 236KB stylesheet arriving after first paint is a visible reflow. They are only
			// enqueued where rows will actually render - see Rows\Assets for the footer pass that
			// covers a theme rendering rows from somewhere unexpected.
			if(self::will_render_rows()) {
				wp_enqueue_style(self::STRUCTURE_HANDLE);
			}

		}
		
		/**
		 * Converts a string representation of a color to its RGBA equivalent.
		 * This function processes color strings and returns an array containing
		 * the RGBA components. It handles color formats commonly used in web design.
		 *
		 * @param string $colorString The color string to be converted.
		 *
		 * @return array An array with 'red', 'green', 'blue', and 'alpha' components.
		 */
		public static function str_to_rgba(string $color, ?float $toAlpha = 1.0, ?float $fallbackAlpha = 1.0): ?string {
			
			$color = trim($color);
			
			// 1. Handle RGB or RGBA string inputs
			if (stripos($color, 'rgb') === 0) {
				// Extract all numeric/decimal values from the string
				preg_match_all('/[0-9.]+(%?)/', $color, $matches);
				$values = $matches[0] ?? [];
				
				if (count($values) < 3) {
					return null; // Invalid format
				}
				
				// Parse Red, Green, Blue
				$r = (int)$values[0];
				$g = (int)$values[1];
				$b = (int)$values[2];
				
				// Parse or assign Alpha
				$a = isset($values[3]) ? (float)$values[3] : ($fallbackAlpha ?? 1.0);
			
				if(isset($toAlpha)) {
					$a = (float)$toAlpha;
				}
				
				return "rgba($r, $g, $b, $a)";
			}
			
			// 2. Handle HEX string inputs
			$hex = ltrim($color, '#');
			$length = strlen($hex);
			
			// Normalize shorthand HEX formats
			if ($length === 3 || $length === 4) {
				$hex = preg_replace('/(.)/', '$1$1', $hex);
				$length = strlen($hex);
			}
			
			// Extract channels based on resulting length (6 or 8 characters)
			if ($length === 6) {
				list($r, $g, $b) = sscanf($hex, "%02x%02x%02x");
				$a = $fallbackAlpha ?? 1.0;
			} elseif ($length === 8) {
				list($r, $g, $b, $alphaHex) = sscanf($hex, "%02x%02x%02x%02x");
				// Convert 0-255 hex alpha channel to a 0-1 float scale
				$a = round($alphaHex / 255, 2);
			} else {
				return null; // Invalid HEX length
			}
			
			if(isset($toAlpha)) {
				$a = (float)$toAlpha;
			}
			
			return "rgba($r, $g, $b, $a)";
		}

		/**
		 * bootstrap_handle function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public static function bootstrap_handle() {

			/**
			 * Filters the style handle the plugin's scoped Bootstrap is registered under.
			 *
			 * Return a handle a site has already registered and the plugin will not register
			 * its own - the existing registration wins. Note the site's build has to be scoped
			 * the same way, to `.acbs.fl-acbs`, or the rows lose their grid.
			 *
			 * @param string $handle
			 */
			return (string) apply_filters('acbs/bootstrap/handle', 'acbs-bootstrap');

		}

		/**
		 * will_render_rows function
		 *
		 * Whether this request is one the base sheets are needed on, decided before any row
		 * has been looked at - which is the only reason it has to be a guess at all.
		 *
		 * The Page Builder template is the answer in every normal case, since that is what
		 * calls acbs_render_rows(). A theme calling the tag from somewhere else is covered by
		 * the footer pass instead, which knows for certain because a row has rendered by then.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return bool
		 */
		public static function will_render_rows() {

			$render = is_singular() && Page_Template::SLUG === get_page_template_slug();

			/**
			 * Filters whether the row base stylesheets are enqueued in the head for this
			 * request. A theme that renders rows outside the Page Builder template should
			 * return true here rather than rely on the footer fallback.
			 *
			 * @param bool $render
			 */
			return (bool) apply_filters('acbs/rows/enqueue_base_styles', $render);

		}
		
		/**
		 * register_field_groups function
		 *
		 * @version 1.0.6
		 * @since   1.0.6
		 */
		public function register_field_groups() {
			Field_Groups::register();
		}
		
		/**
		 * register_location_rules function
		 *
		 * @version 1.0.7
		 * @since   1.0.7
		 */
		public function register_location_rules() {
			Field_Groups::register_location_rules();
		}
		
		/**
		 * enqueue_flexible_layout_tabs_assets function
		 *
		 * A layout that clones Intro starts with an "Intro" tab, which ACF would activate
		 * by default - this makes a freshly added row default to "Content" instead, since
		 * that's where a site builder's own fields for that layout actually live. Existing,
		 * already-saved rows are untouched - see assets/js/acf-flexible-layout-tabs.js.
		 *
		 * The layout list is computed fresh each time via Page_Content::get_current_layouts()
		 * - the same full pipeline register() itself uses, including any layout a site has
		 * added purely through tagging its own group "Flexible Layout = Page Content" - rather
		 * than read from a fixed constant: since 1.0.23 every layout gets Intro by default
		 * (see Common_Fields::should_add_intro()), disable-able per layout via the
		 * `acbs_disable_layout_intro` filter, so which layouts actually need this can change
		 * at runtime.
		 *
		 * @version 1.0.25
		 * @since   1.0.22
		 */
		public function enqueue_flexible_layout_tabs_assets() {
			
			wp_enqueue_script(
				'erdc-flexible-layout-tabs',
				ACBS_URL.'assets/js/acf-flexible-layout-tabs.js',
				['jquery', 'acf-input'],
				ACBS_VERSION,
				true
			);
			
			$intro_layouts = [];
			
			foreach(Page_Content::get_current_layouts() as $layout) {
				
				if(Common_Fields::should_add_intro($layout['name'] ?? '')) {
					$intro_layouts[] = $layout['name'];
				}
				
			}
			
			wp_localize_script('erdc-flexible-layout-tabs', 'erdcFlexibleLayoutTabs', [
				'layouts' => $intro_layouts,
			]);
			
		}
		
		/**
		 * enqueue_buttons_repeater_field_assets function
		 *
		 * ACF's client-side field-type registry (acf.getFieldType()) looks up a field's
		 * JS behaviour by its literal `type` attribute alone - it has no equivalent to
		 * Buttons_Field_Type's PHP-side `extends \acf_field_repeater`. Without this, a
		 * field typed "erdc_buttons_repeater" gets ACF's generic base Field class and no
		 * Add Row/remove row/drag-reorder behaviour at all. See
		 * assets/js/acf-buttons-repeater-field.js for the fix (extends ACF's own
		 * Repeater field model under our own type name) and its docblock for how this
		 * was confirmed.
		 *
		 * Depends on 'acf-pro-input' (not just 'acf-input') specifically so
		 * acf.getFieldType('repeater') is guaranteed already registered by the time this
		 * script runs.
		 *
		 * @version 1.0.26
		 * @since   1.0.26
		 */
		public function enqueue_buttons_repeater_field_assets() {
			
			wp_enqueue_script(
				'erdc-buttons-repeater-field',
				ACBS_URL.'assets/js/acf-buttons-repeater-field.js',
				['acf-pro-input'],
				ACBS_VERSION,
				true
			);
			
		}
		
		/**
		 * enqueue_layout_title_assets function
		 *
		 * The inline rename button on a page_sections row handle. See
		 * Fields\Layout_Title for what this does and, more usefully, what it deliberately
		 * does NOT do - the rename itself is ACF's own feature and this only adds a second
		 * way to reach it.
		 *
		 * Depends on 'acf-pro-input', not 'acf-input', and for the same reason
		 * enqueue_buttons_repeater_field_assets() does: flexible content is a Pro field and
		 * its JS model - the one carrying renameLayout(), which this script calls rather
		 * than reimplementing - is registered by that handle. Depending on the free
		 * handle would load us before the model exists.
		 *
		 * The script still guards for window.acf and falls back to DOMContentLoaded, so a
		 * future ACF that moves the model does not leave a dead button behind.
		 *
		 * Versioned by filemtime rather than ACBS_VERSION: both files are hand-written and
		 * edited without a release, so keying the cache to the plugin version serves a
		 * stale file after every change.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function enqueue_layout_title_assets() {

			$js = 'assets/js/acf-layout-title.js';
			$css = 'assets/css/acf-layout-title.css';

			wp_enqueue_script(
				'acbs-layout-title',
				ACBS_URL.$js,
				['acf-pro-input'],
				self::asset_version($js),
				true
			);

			wp_localize_script('acbs-layout-title', 'acbsLayoutTitle', [
				'fieldKey' => Page_Content::FIELD_KEY,
				'i18n' => [
					'rename' => __('Rename this row', 'acbs'),
				],
			]);

			wp_enqueue_style(
				'acbs-layout-title',
				ACBS_URL.$css,
				[],
				self::asset_version($css)
			);

		}

		/**
		 * asset_version function
		 *
		 * filemtime, falling back to the plugin version if the file cannot be stat'd.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $relative
		 *
		 * @return string|int
		 */
		private static function asset_version($relative) {

			$time = @filemtime(ACBS_PATH.$relative);

			return false !== $time ? $time : ACBS_VERSION;

		}

		/**
		 * layout_wrapper_classes function
		 *
		 * The classes on a row's wrapper element. Every one of these reads through
		 * get_sub_field(), so this only works while the row's ACF loop is active - which
		 * it is: Wrapper calls the filter from inside the_row(). See CLAUDE.md section 05.
		 *
		 * The `fs-fl-` prefix these used to carry was a client prefix in a shared plugin
		 * and is now `fl-`; that is a breaking change for any site CSS targeting the old
		 * names, and a deliberate one.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param array $classes
		 * @param Row   $row
		 *
		 * @return array
		 */
		public function layout_wrapper_classes($classes, $row) {
			
			if(!is_array($classes)) {
				$classes = array_filter([$classes]);
			}
			
			// base classes
			$classes = array_merge($classes, [
				'fl-bg-'.get_sub_field('section_bg'),
				'fl-type-'.$row->layout(),
				'fl-item'
			]);
			
			// Vertical padding
			if(!empty(get_sub_field('vertical_padding')) && get_sub_field('vertical_padding') != 'default') {
				$classes[] = 'fl-p-'.get_sub_field('vertical_padding');
			}
			
			// Mobile vertical padding
			if(!empty(get_sub_field('vertical_padding_xs')) && get_sub_field('vertical_padding_xs') != 'default') {
				$classes[] = 'fl-p-xs-'.get_sub_field('vertical_padding_xs');
			}
			
			// Grid type
			if(!empty(get_sub_field('grid_type'))) {
				$classes[] = 'fl-loop-grid-dynamic';
				$classes[] = 'fl-loop-grid-'.get_sub_field('grid_type');
			}
			
			// Grid & Display. The column counts are three separate fields rather than one
			// responsive control, so they emit three independent classes; an unset tablet or
			// mobile value emits nothing and the CSS falls back to the next size up.
			if(get_sub_field('layout_columns')) {
				$classes[] = 'fl-loop-grid-columns-'.get_sub_field('layout_columns');
			}
			
			if(get_sub_field('layout_columns_sm')) {
				$classes[] = 'fl-loop-grid-columns-sm-'.get_sub_field('layout_columns_sm');
			}
			
			if(get_sub_field('layout_columns_xs')) {
				$classes[] = 'fl-loop-grid-columns-xs-'.get_sub_field('layout_columns_xs');
			}
			
			// Renamed from `columns_alignment` when the whole Grid & Display group took the
			// layout_ prefix. Reading the old name here emitted nothing and raised nothing -
			// the alignment class just quietly stopped appearing.
			if(get_sub_field('layout_columns_alignment')) {
				$classes[] = 'fl-loop-grid-columns-align-'.get_sub_field('layout_columns_alignment');
			}
			
			// Content Box. 'default' is seamless and needs no class of its own; only a card
			// changes anything, and its colour comes with it.
			if('card' === get_sub_field('layout_display')) {
			
				$classes[] = 'fl-card-box';
				
				if(get_sub_field('layout_display_bg')) {
					$classes[] = 'fl-card-bg-'.get_sub_field('layout_display_bg');
				}
			
			}
			
			// Image fit
			if(!empty(get_sub_field('image_fit'))) {
				$classes[] = 'fl-image-fit-'.get_sub_field('image_fit');
			}
			
			// Full Width Image
			if(get_row_layout() == 'full_width_image') {
				$classes[] = 'fl-loop-grid-columns-align-center';
				
				if(!empty(get_sub_field('overlay'))) {
					$classes[] = 'fl-full-width-image-overlay';
				}
			}
			
			return $classes;
			
		}
		
		/**
		 * layout_wrapper_style function
		 *
		 * The one thing about a row that cannot be a stylesheet rule: the colour an editor
		 * picks when layout_display_bg is set to `custom`. The other seven choices are named,
		 * so structure.scss carries them; this one arrives per row.
		 *
		 * Emitted as the same custom property the named rules set, so the card rule itself
		 * does not care where its colour came from.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param array $declarations
		 * @param Row   $row
		 *
		 * @return array
		 */
		public function layout_wrapper_style($declarations, $row) {
			
			//Default per field conditionals styles - can be overwritten per site as well
			if(get_row_layout() == 'full_width_image' && !empty(get_sub_field('image'))) {
				$image = get_sub_field('image');
				$declarations[] = 'background: url('.$image['url'].') no-repeat center center; background-position: 50% 50%; background-size: cover;';
			}

			// The overlay.
			//
			// A CUSTOM PROPERTY on the section, not a <div>, and emitted from here rather
			// than from an action in templates/wrapper.php. Both halves of that matter:
			//
			//   - The section already carries the image as its own background, so a second
			//     background-image here would replace it rather than sit over it. The
			//     overlay is drawn by a pseudo-element in the row sheet, which needs its
			//     colour handed to it and has nowhere else to read one from.
			//
			//   - This filter is called by Wrapper::style(), a PHP class. The wrapper
			//     TEMPLATE is overridable and a theme replaces it wholesale, so anything
			//     the plugin renders through an action fired in that file disappears on a
			//     theme that copied it. A copied wrapper.php still calls
			//     $row->wrapper_style(), so this survives.
			if(get_row_layout() == 'full_width_image' && !empty(get_sub_field('overlay'))) {

				$colour = get_sub_field('overlay_colour');
				$colour = empty($colour) ? 'rgba(0, 0, 0, .5)' : $colour;

				$transparent = self::str_to_rgba($colour, 0);
				$gradient = "linear-gradient(0deg, {$transparent} 11.93%, {$colour} 100%)";

				/**
				 * Filters the overlay's background.
				 *
				 * @param string $gradient
				 * @param mixed  $overlay The overlay toggle's value.
				 */
				$declarations[] = '--fl-overlay-bg: '.apply_filters('acbs/full_width_image/overlay', $gradient, get_sub_field('overlay'));

			}

			if('card' !== get_sub_field('layout_display') || 'custom' !== get_sub_field('layout_display_bg')) {
				return $declarations;
			}

			$colour = trim((string) get_sub_field('layout_display_bg_colour'));

			if('' === $colour) {
				return $declarations;
			}

			$declarations[] = '--fl-card-box-bg: '.$colour;

			return $declarations;

		}

		/**
		 * layout_wrapper_id function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $id
		 * @param Row    $row
		 *
		 * @return string
		 */
		public function layout_wrapper_id($id, $row) {
			
			if(get_sub_field('section_container_id')) {
				return get_sub_field('section_container_id');
			}
			
			return $id;
			
		}
		
		}
