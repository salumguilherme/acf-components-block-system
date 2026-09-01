<?php

	namespace ACBS\Modules\EditorColours;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Palette
	 *
	 * The brand colours offered by the editor's text colour button, and the single place
	 * that decides what a valid entry looks like.
	 *
	 * Each key becomes a CLASS, not a colour: picking "primary" wraps the selection in
	 * `<span class="text-primary">`, and the hex is used only to draw the swatch in the
	 * toolbar and to colour the text inside the editor iframe. The front end never sees
	 * the hex - it resolves .text-primary out of the scoped Bootstrap build, which is what
	 * makes a retheme move existing content instead of stranding it on a frozen value.
	 *
	 * That is also the reason the hexes here are duplicated from _tokens.scss rather than
	 * read from it: they are a PREVIEW of a class, and the editor needs them in PHP, at
	 * admin render time, where the compiled stylesheet is not available to parse. If a
	 * token changes, the matching entry here has to change with it - see the note on
	 * `dark` below for what happens when the two drift.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\EditorColours
	 */
	final class Palette {

		/**
		 * The default palette: class key => label + swatch colour.
		 *
		 * WARNING ON `dark`. This says #171717, which is $heading-colour. The compiled
		 * `.text-dark` says #175e54, because $theme-colors maps 'dark' to $brand-primary
		 * in _tokens.scss ("dark and darker both resolve to primary today"). So this one
		 * swatch previews a colour the front end will not render. It is left as specified
		 * rather than quietly corrected, because the fix is a decision about the token,
		 * not about this file: changing $dark also moves every fl-bg-dark section.
		 *
		 * @var array
		 */
		const DEFAULTS = [
			'primary'   => [ 'label' => 'Primary',   'hex' => '#175e54' ],
			'secondary' => [ 'label' => 'Secondary', 'hex' => '#e35c49' ],
			'tertiary'  => [ 'label' => 'Tertiary',  'hex' => '#ffc550' ],
			'white'     => [ 'label' => 'White',     'hex' => '#ffffff' ],
			'dark'      => [ 'label' => 'Dark',      'hex' => '#171717' ],
			'light'     => [ 'label' => 'Light',     'hex' => '#f8fafc' ],
			'accent-1'  => [ 'label' => 'Accent 1',  'hex' => '#ede8c4' ],
			'accent-2'  => [ 'label' => 'Accent 2',  'hex' => '#e2c2c7' ],
		];

		/**
		 * colours function
		 *
		 * The filtered palette, validated. A theme may return fewer entries, more, or a
		 * completely different set - the button draws whatever comes back, in the order it
		 * comes back, so reordering the array reorders the swatches.
		 *
		 * Invalid entries are DROPPED rather than repaired. A key that is not a safe class
		 * fragment would emit a class the stylesheet cannot match, and a bad hex would draw
		 * an invisible swatch; both fail silently on the page, so they are caught here and
		 * reported under WP_DEBUG instead.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		public static function colours() {

			$filtered = apply_filters('acbs/editor/text_colours', self::DEFAULTS);

			if(!is_array($filtered)) {
				return [];
			}

			$valid = [];

			foreach($filtered as $key => $colour) {

				$key = is_string($key) ? $key : '';

				// The same character set sanitize_html_class() allows, minus the leading
				// digit and minus anything that would need escaping in a CSS selector. The
				// key is concatenated into `text-{key}`, so it has to survive as-is.
				if('' === $key || !preg_match('/^[a-z][a-z0-9_-]*$/', $key)) {
					self::warn(sprintf('dropped colour key "%s": expected lowercase letters, digits, - and _, starting with a letter.', $key));
					continue;
				}

				$hex   = isset($colour['hex']) ? (string) $colour['hex'] : '';
				$hex   = sanitize_hex_color($hex);
				$label = isset($colour['label']) ? (string) $colour['label'] : ucwords(str_replace(['-', '_'], ' ', $key));

				if(null === $hex || '' === $hex) {
					self::warn(sprintf('dropped colour "%s": "%s" is not a valid hex colour.', $key, isset($colour['hex']) ? $colour['hex'] : ''));
					continue;
				}

				$valid[$key] = [
					'label' => $label,
					'hex'   => $hex,
				];

			}

			return $valid;

		}

		/**
		 * for_js function
		 *
		 * The palette as a list, which is the shape the TinyMCE plugin wants: it renders
		 * the swatch grid in order, and an object keyed by colour would leave that order
		 * up to the JSON parser.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		public static function for_js() {

			$list = [];

			foreach(self::colours() as $key => $colour) {
				$list[] = [
					'key'   => $key,
					'label' => $colour['label'],
					'hex'   => $colour['hex'],
					'class' => 'text-' . $key,
				];
			}

			return $list;

		}

		/**
		 * warn function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $message
		 */
		private static function warn($message) {

			if(defined('WP_DEBUG') && WP_DEBUG) {
				trigger_error('ACBS editor colours: ' . $message, E_USER_WARNING);
			}

		}

	}
