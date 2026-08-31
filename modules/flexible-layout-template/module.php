<?php
	
	namespace ACBS\Modules\FlexibleLayoutTemplate;
	
	use ACBS\Core\Module_Base as Base_Module;
	use ACBS\Modules\FlexibleLayoutTemplate\Fields\Field_Groups;
	use ACBS\Modules\FlexibleLayoutTemplate\Fields\Common_Fields;
	use ACBS\Modules\FlexibleLayoutTemplate\Fields\Page_Content;
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
			add_shortcode('acbs_rows', 'acbs_rows_shortcode');
			
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
			
		}
		
		/**
		 * register_assets function
		 *
		 * @version 1.0.13
		 * @since   1.0.13
		 */
		public function register_assets() {
			wp_register_style('erdc-frontend', ACBS_URL.'assets/css/frontend.css', [], ACBS_VERSION, 'all');
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
		 * `erdc_disable_layout_intro` filter, so which layouts actually need this can change
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
			if(!empty(get_sub_field('vertical_padding_mobile')) && get_sub_field('vertical_padding_mobile') != 'default') {
				$classes[] = 'fl-p-mobile-'.get_sub_field('vertical_padding_mobile');
			}
			
			// Grid type
			if(!empty(get_sub_field('grid_type'))) {
				$classes[] = 'fl-loop-grid-dynamic';
				$classes[] = 'fl-loop-grid-'.get_sub_field('grid_type');
			}
			
			// IF we have a layout columns layout
			if(get_sub_field('layout_columns')) {
				$classes[] = 'fl-loop-grid-columns-'.get_sub_field('layout_columns');
			}
			
			// IF we have a layout columns alignment
			if(get_sub_field('columns_alignment')) {
				$classes[] = 'fl-loop-grid-columns-align-'.get_sub_field('columns_alignment');
			}
			
			// Image fit
			if(!empty(get_sub_field('image_fit'))) {
				$classes[] = 'fl-image-fit-'.get_sub_field('image_fit');
			}
			
			return $classes;
			
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
