<?php

	namespace ACBS\Modules\EditorColours;

	use ACBS\Core\Module_Base as Base_Module;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Module
	 *
	 * Puts a brand text colour button in the classic editor's toolbar, and takes
	 * WordPress's own colour button away.
	 *
	 * WHY NOT JUST ADD OUR COLOURS TO THE EXISTING BUTTON. Because TinyMCE's built-in
	 * colour button writes an inline style, not a class. The format is declared in
	 * tinymce.min.js as:
	 *
	 *     forecolor: { inline: "span", styles: { color: "%value" } }
	 *
	 * so `textcolor_map` swatches would produce `<span style="color:#175E54">`. An inline
	 * hex is frozen at the moment the editor clicked it: retheme --primary and every page
	 * already written keeps the old green, and the plugin's own dark-ground rules
	 * (.fl-card-bg-primary h2 { color: inherit }) lose to it outright, which is how you get
	 * brand green text on a brand green card with no way to override it.
	 *
	 * WHAT MAKES THE CLASS VERSION WORK. TinyMCE runs format CLASSES through the same
	 * variable substitution as styles - `Ly(e.classes, function(e){ e = wc.replaceVars(e, h)
	 * ... addClass(n, e) })` in the formatter - so a format registered as
	 * `{ inline: 'span', classes: 'text-%value' }` and applied with `{value: 'primary'}`
	 * emits exactly `<span class="text-primary">`. That is the whole mechanism.
	 *
	 * WHERE THE SETTINGS LAND. ACF does not call wp_editor() per field: it renders one
	 * hidden `wp_editor('', 'acf_content')` and every wysiwyg field clones
	 * `tinyMCEPreInit.mceInit.acf_content`, overriding only toolbar1..4 from its own
	 * toolbar array. So `external_plugins`, `content_style` and the palette all reach ACF
	 * fields through the ordinary tiny_mce_before_init on that hidden editor, and both of
	 * ACF's toolbars are covered because ACF builds them by running mce_buttons and
	 * teeny_mce_buttons itself (class-acf-field-wysiwyg.php).
	 *
	 * Genuine teeny editors elsewhere in wp-admin take a DIFFERENT init filter,
	 * teeny_mce_before_init, which is why both are hooked.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\EditorColours
	 */
	class Module extends Base_Module {

		/**
		 * The TinyMCE plugin name, and the toolbar button name. Same string on purpose:
		 * TinyMCE looks a button up by the name its plugin registered.
		 */
		const HANDLE = 'acbstextcolour';

		/**
		 * The button this one replaces. Removed from every toolbar row.
		 */
		const REPLACES = 'forecolor';

		/**
		 * The button we sit after. Falls back to appending if the toolbar has no such
		 * button, which is what happens on a toolbar a site has rebuilt from scratch.
		 */
		const AFTER = 'alignright';

		/**
		 * get_name function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function get_name() {
			return 'editor-colours';
		}

		/**
		 * Constructor
		 */
		public function __construct() {

			parent::__construct();

			if(!is_admin()) {
				return;
			}

			add_filter('mce_external_plugins', [$this, 'register_plugin']);

			// Full toolbar (row 1) and teeny/Basic. ACF runs both of these filters when it
			// builds its own toolbar array, so one hook covers WordPress and ACF alike.
			add_filter('mce_buttons', [$this, 'add_button'], 10, 1);
			add_filter('teeny_mce_buttons', [$this, 'add_button'], 10, 1);

			// forecolor lives on row 2 of the Full toolbar by default, but it is stripped
			// from every row rather than just that one: a site that has moved it should not
			// get both buttons back.
			add_filter('mce_buttons', [$this, 'remove_replaced_button'], 11, 1);
			add_filter('mce_buttons_2', [$this, 'remove_replaced_button'], 11, 1);
			add_filter('mce_buttons_3', [$this, 'remove_replaced_button'], 11, 1);
			add_filter('mce_buttons_4', [$this, 'remove_replaced_button'], 11, 1);
			add_filter('teeny_mce_buttons', [$this, 'remove_replaced_button'], 11, 1);

			add_filter('tiny_mce_before_init', [$this, 'settings']);
			add_filter('teeny_mce_before_init', [$this, 'settings']);

			add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);

		}

		/**
		 * register_plugin function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param  array $plugins
		 * @return array
		 */
		public function register_plugin($plugins) {

			if(!is_array($plugins)) {
				return $plugins;
			}

			$plugins[self::HANDLE] = ACBS_URL . 'assets/js/mce-text-colour.js?ver=' . ACBS_VERSION;

			return $plugins;

		}

		/**
		 * add_button function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param  array $buttons
		 * @return array
		 */
		public function add_button($buttons) {

			if(!is_array($buttons) || in_array(self::HANDLE, $buttons, true)) {
				return $buttons;
			}

			// Nothing to colour with means no button at all, rather than a button whose
			// panel opens empty.
			if(!Palette::colours()) {
				return $buttons;
			}

			$position = array_search(self::AFTER, $buttons, true);

			if(false === $position) {
				$buttons[] = self::HANDLE;
				return $buttons;
			}

			array_splice($buttons, $position + 1, 0, [self::HANDLE]);

			return $buttons;

		}

		/**
		 * remove_replaced_button function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param  array $buttons
		 * @return array
		 */
		public function remove_replaced_button($buttons) {

			if(!is_array($buttons)) {
				return $buttons;
			}

			/**
			 * Whether to take WordPress's own colour button out of the toolbar.
			 *
			 * It goes by default: it offers a "Custom..." colour picker, so leaving it in
			 * place leaves an easier route to an arbitrary inline hex than the token button,
			 * and whoever finds it first stops using the tokens.
			 *
			 * @param bool $remove
			 */
			if(!apply_filters('acbs/editor/remove_forecolor', true)) {
				return $buttons;
			}

			return array_values(array_diff($buttons, [self::REPLACES]));

		}

		/**
		 * settings function
		 *
		 * Adds the palette and the in-iframe preview CSS to a TinyMCE init array.
		 *
		 * Both values go in as strings, because _WP_Editors::_parse_init() does not encode
		 * them: it wraps a plain string in double quotes verbatim, and passes anything that
		 * already looks like JSON ("[...]" or "{...}") straight through. So the palette is
		 * pre-encoded to survive, and the CSS has its double quotes stripped rather than
		 * escaped - a quote in there would close the option string and break the whole init
		 * object, taking every editor on the page with it.
		 *
		 * content_style rather than a stylesheet on mce_css, deliberately. It is generated
		 * from the same palette the button draws from, so the two cannot drift; it costs no
		 * request; and it colours the text WITHOUT pulling the scoped Bootstrap into the
		 * iframe, which would restyle the whole editor to match the front end - a much
		 * larger change than "show the colour", and not one that was asked for.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param  array $init
		 * @return array
		 */
		public function settings($init) {

			if(!is_array($init)) {
				return $init;
			}

			$colours = Palette::for_js();

			if(!$colours) {
				return $init;
			}

			$init['acbs_text_colours'] = wp_json_encode($colours);

			$css = '';

			foreach($colours as $colour) {
				$css .= sprintf('.%s{color:%s;}', $colour['class'], $colour['hex']);
			}

			// A white or near-white swatch on the editor's white canvas is invisible, so the
			// span gets a faint checkered ground INSIDE the editor only. It is a
			// content_style rule, so it exists in the iframe and never ships to the page.
			$css .= '.text-white{background-color:#8c8f94;}';

			$existing = isset($init['content_style']) ? (string) $init['content_style'] : '';
			$init['content_style'] = str_replace('"', '', $existing . $css);

			return $init;

		}

		/**
		 * enqueue_admin_styles function
		 *
		 * The toolbar button and its panel live OUTSIDE the editor iframe, so they cannot be
		 * styled by content_style and need a real admin stylesheet.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function enqueue_admin_styles() {

			if(!Palette::colours()) {
				return;
			}

			wp_enqueue_style(
				'acbs-mce-text-colour',
				ACBS_URL . 'assets/css/mce-text-colour.css',
				[],
				ACBS_VERSION
			);

			// The icon is a mask rather than a background-image, so its shape is painted in
			// currentColor and follows TinyMCE's own icon colour through hover, active and
			// disabled - a background-image would be a fixed-colour picture that stays put
			// while everything around it changes.
			wp_add_inline_style(
				'acbs-mce-text-colour',
				sprintf(
					'.mce-ico.mce-i-acbs-text-colour{--acbs-mce-icon:url(%s);}',
					esc_url(ACBS_URL . 'assets/images/acbs-text-colour.svg?ver=' . ACBS_VERSION)
				)
			);

		}

	}
