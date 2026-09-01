<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Button_Icons
	 *
	 * The icons a button can carry, and the code that renders one.
	 *
	 * Choices and rendering live in ONE class for the same reason Colour_Palette does: an
	 * icon key is only meaningful if there is a file behind it, and a list of choices kept
	 * somewhere else is a list that eventually offers something that renders nothing.
	 *
	 * INLINE <svg>, NOT <img>. The whole point of these icons is that their paths carry
	 * `fill="currentColor"`, so an icon inherits the button's text colour - through the
	 * resting state, the hover state, the outline variants and the dark-ground overrides,
	 * with no per-variant icon files. An <img> is an independent document and inherits
	 * nothing, so it would need one file per colour per state. Inlining is not a
	 * micro-optimisation here, it is the requirement.
	 *
	 * NO PER-SITE SVG LOOKUP, deliberately. The bundled icons are the plugin's, shipped in
	 * assets/svg, and a theme cannot add to or replace them - that is what the `custom`
	 * choice is for, where an editor uploads their own SVG per button. The CHOICE LIST is
	 * still filterable through `erdc/buttons/fields` like every other field setting, which
	 * means it is possible to filter in a key with no file behind it; see the note on
	 * render() for what happens then.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	final class Button_Icons {

		/**
		 * No icon. Not a file - the button renders text only.
		 */
		const NONE = 'none';

		/**
		 * An editor-supplied SVG, from the `button_icon_svg` upload field rather than
		 * from assets/svg.
		 */
		const CUSTOM = 'custom';

		/**
		 * Where the bundled files live, relative to the plugin root.
		 */
		const DIR = 'assets/svg/';

		/**
		 * The bundled icons: choice value => label. Every key except `none` and `custom`
		 * must have a matching {key}.svg in assets/svg.
		 *
		 * Source files are src/svg/*.svg and are copied to assets/svg by the webpack
		 * build, so this list, that directory and the build all move together.
		 *
		 * @var array
		 */
		const ICONS = [
			'arrow-right'   => 'Arrow ->',
			'arrow-left'    => 'Arrow <-',
			'download'      => 'Download',
			'phone'         => 'Phone',
			'chevron-down'  => 'Angle ˅',
			'chevron-right' => 'Angle >',
			'external'      => 'External',
			'chat'          => 'Chat',
		];

		/**
		 * Rendered markup, memoised per request and keyed by icon key or attachment id.
		 *
		 * A page can easily carry the same icon on a dozen buttons, and each one is a file
		 * read plus a sanitise pass. Reading it once is the difference between one disk hit
		 * per icon and one per button.
		 *
		 * @var array
		 */
		private static $cache = [];

		/**
		 * choices function
		 *
		 * The button_icon field's choice list, in display order: None first, the bundled
		 * icons, then Custom.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		public static function choices(): array {

			return array_merge(
				[self::NONE => 'None'],
				self::ICONS,
				[self::CUSTOM => 'Custom']
			);

		}

		/**
		 * render function
		 *
		 * The inline <svg> for one button, or an empty string.
		 *
		 * Returns '' - never a placeholder, never a broken image - for every reason an icon
		 * might not resolve: `none`, an empty key, `custom` with nothing uploaded, a file
		 * that is missing or unreadable, an upload that is not actually an SVG, or a key
		 * filtered into the choice list with no file behind it. A button with no icon is a
		 * perfectly good button; a button with a broken one is not.
		 *
		 * THAT LAST CASE IS THE TRAP. `erdc/buttons/fields` can add a choice, but nothing
		 * adds a FILE - there is no per-site icon directory by design. A filtered-in key
		 * therefore renders nothing at all, and does it silently, so it warns under
		 * WP_DEBUG rather than leaving someone to wonder why their new option does nothing.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param  string $key        The button_icon value.
		 * @param  mixed  $attachment The button_icon_svg value, when $key is `custom`.
		 * @return string
		 */
		public static function render($key, $attachment = null): string {

			$key = is_string($key) ? $key : '';

			if('' === $key || self::NONE === $key) {
				return '';
			}

			if(self::CUSTOM === $key) {
				return self::render_custom($attachment);
			}

			if(isset(self::$cache[$key])) {
				return self::$cache[$key];
			}

			$file = self::path($key);
			$svg  = '' !== $file ? self::read($file) : '';

			if('' === $svg) {

				self::warn(sprintf(
					'no icon file for "%s". The bundled icons are %s - a choice filtered into button_icon needs a file in %s, and there is no per-site lookup (use the "custom" choice instead).',
					$key,
					implode(', ', array_keys(self::ICONS)),
					self::DIR
				));

				return self::$cache[$key] = '';

			}

			// The bundled files are the plugin's own, so they are prepared rather than
			// sanitised - see prepare(). An editor upload takes the other path.
			return self::$cache[$key] = self::prepare($svg);

		}

		/**
		 * render_custom function
		 *
		 * An editor-uploaded SVG. Accepts either an attachment array (the field returns
		 * `array`) or a bare id, so a site that re-declares the field as `return_format =>
		 * id` still renders - the same tolerance columned_content's icon needed.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param  mixed $attachment
		 * @return string
		 */
		private static function render_custom($attachment): string {

			$id = 0;

			if(is_array($attachment) && !empty($attachment['ID'])) {
				$id = (int) $attachment['ID'];
			} elseif(is_numeric($attachment)) {
				$id = (int) $attachment;
			}

			if(0 === $id) {
				return '';
			}

			$cache_key = 'attachment:'.$id;

			if(isset(self::$cache[$cache_key])) {
				return self::$cache[$cache_key];
			}

			// The field restricts uploads to SVG, but a field's mime_types setting is a
			// validation rule on the UPLOAD - it says nothing about an id that arrived some
			// other way (an import, a direct meta write, a site that re-declared the field).
			// Reading whatever file that id points at and inlining it would be the actual
			// vulnerability, so the type is checked here rather than assumed.
			if('image/svg+xml' !== get_post_mime_type($id)) {
				return self::$cache[$cache_key] = '';
			}

			$file = (string) get_attached_file($id);
			$svg  = '' !== $file ? self::read($file) : '';

			if('' === $svg) {
				return self::$cache[$cache_key] = '';
			}

			// SANITISED, not merely prepared. This file came from whoever can upload media,
			// and it is being inlined into the page rather than loaded into the isolated
			// document an <img> would give it - so a <script>, an onload= or a
			// <foreignObject> in it would execute in the page's own origin.
			return self::$cache[$cache_key] = self::prepare(self::sanitise($svg));

		}

		/**
		 * path function
		 *
		 * The bundled file for an icon key, or '' if the key is not one of ours.
		 *
		 * The key is matched against ICONS rather than being concatenated into a path.
		 * Building a path out of an unvalidated value is how a `../` in a filtered choice
		 * would read a file outside the icon directory.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param  string $key
		 * @return string
		 */
		private static function path($key): string {

			if(!isset(self::ICONS[$key])) {
				return '';
			}

			return ACBS_PATH.self::DIR.$key.'.svg';

		}

		/**
		 * read function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param  string $file
		 * @return string
		 */
		private static function read($file): string {

			if(!is_readable($file)) {
				return '';
			}

			$contents = file_get_contents($file); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin/upload file, not a remote request.

			return is_string($contents) ? trim($contents) : '';

		}

		/**
		 * prepare function
		 *
		 * Strips the parts of a standalone SVG document that have no business inside an
		 * HTML page, then stamps the root element with the plugin's own class and the
		 * accessibility attributes a decorative icon needs.
		 *
		 * The icon sits beside a button's own text, so it is decorative by definition:
		 * aria-hidden keeps a screen reader from announcing it twice, and focusable="false"
		 * keeps older Edge/IE from putting an inline SVG in the tab order.
		 *
		 * width/height on the root are left alone - the stylesheet sizes the icon from the
		 * box around it, and stripping them would only matter if the CSS failed to load,
		 * in which case the attributes are the better fallback.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param  string $svg
		 * @return string
		 */
		private static function prepare($svg): string {

			// XML prolog, doctype and comments. A prolog inside HTML is rendered as text,
			// and both it and a doctype are meaningless once the file is not a document.
			$svg = preg_replace('/<\?xml[^>]*\?>/i', '', $svg);
			$svg = preg_replace('/<!DOCTYPE[^>]*>/i', '', $svg);
			$svg = preg_replace('/<!--.*?-->/s', '', $svg);
			$svg = trim((string) $svg);

			if('' === $svg || 0 !== stripos($svg, '<svg')) {
				return '';
			}

			// Stamp the root tag. Only the FIRST <svg is touched - a nested one is part of
			// the artwork.
			$svg = preg_replace(
				'/<svg\b/i',
				'<svg class="fl-button-icon" aria-hidden="true" focusable="false"',
				$svg,
				1
			);

			return (string) $svg;

		}

		/**
		 * sanitise function
		 *
		 * wp_kses() against an SVG allowlist, for editor-uploaded files only.
		 *
		 * ON CASE. kses lowercases element and attribute names, so `clipPath` comes back
		 * `clippath` and `viewBox` comes back `viewbox`. That is fine, and only fine
		 * BECAUSE the markup is inlined into an HTML document: the HTML parser carries a
		 * case-adjustment table for foreign content and maps both back to their SVG
		 * spelling. The same string written into a standalone .svg file, or into XHTML,
		 * would not render. Both spellings are listed below so the allowlist matches either
		 * way round rather than depending on when kses lowercases.
		 *
		 * Not listed, on purpose: script, foreignObject, style, animate and the a element.
		 * The first three execute or import; animate can drive an href; and a link inside a
		 * button is a nested interactive control.
		 *
		 * The allowlist is NOT sufficient on its own for the executable ones - kses drops a
		 * disallowed tag but keeps its text - so strip_containers() removes those elements
		 * and their contents first. See that method.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param  string $svg
		 * @return string
		 */
		private static function sanitise($svg): string {

			$shared = [
				'class' => true, 'id' => true, 'style' => true, 'transform' => true,
				'fill' => true, 'fill-rule' => true, 'fill-opacity' => true,
				'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true,
				'stroke-linejoin' => true, 'stroke-dasharray' => true,
				'stroke-dashoffset' => true, 'stroke-opacity' => true, 'stroke-miterlimit' => true,
				'opacity' => true, 'clip-rule' => true, 'clip-path' => true,
				'mask' => true, 'filter' => true, 'color' => true, 'display' => true,
			];

			$allowed = [
				'svg' => $shared + [
					'xmlns' => true, 'xmlns:xlink' => true,
					'viewbox' => true, 'viewBox' => true,
					'width' => true, 'height' => true, 'x' => true, 'y' => true,
					'preserveaspectratio' => true, 'preserveAspectRatio' => true,
					'role' => true, 'aria-hidden' => true, 'focusable' => true,
					'fill-rule' => true, 'version' => true,
				],
				'g'       => $shared,
				'path'    => $shared + ['d' => true, 'pathlength' => true],
				'circle'  => $shared + ['cx' => true, 'cy' => true, 'r' => true],
				'ellipse' => $shared + ['cx' => true, 'cy' => true, 'rx' => true, 'ry' => true],
				'rect'    => $shared + ['x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true],
				'line'    => $shared + ['x1' => true, 'y1' => true, 'x2' => true, 'y2' => true],
				'polyline' => $shared + ['points' => true],
				'polygon'  => $shared + ['points' => true],
				'defs'     => $shared,
				'symbol'   => $shared + ['viewbox' => true, 'viewBox' => true],
				'title'    => [],
				'desc'     => [],
				'clippath' => $shared + ['clippathunits' => true],
				'clipPath' => $shared + ['clipPathUnits' => true],
				'lineargradient' => $shared + ['x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'gradientunits' => true, 'gradienttransform' => true],
				'linearGradient' => $shared + ['x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'gradientUnits' => true, 'gradientTransform' => true],
				'radialgradient' => $shared + ['cx' => true, 'cy' => true, 'r' => true, 'fx' => true, 'fy' => true, 'gradientunits' => true, 'gradienttransform' => true],
				'radialGradient' => $shared + ['cx' => true, 'cy' => true, 'r' => true, 'fx' => true, 'fy' => true, 'gradientUnits' => true, 'gradientTransform' => true],
				'stop'     => $shared + ['offset' => true, 'stop-color' => true, 'stop-opacity' => true],
			];

			/**
			 * The SVG elements and attributes an editor-uploaded icon may use.
			 *
			 * Widen this at your own risk: whatever survives is inlined into the page, in
			 * the page's own origin.
			 *
			 * @param array $allowed
			 */
			$allowed = apply_filters('acbs/button_icon/allowed_html', $allowed);

			return wp_kses(self::strip_containers($svg), $allowed);

		}

		/**
		 * strip_containers function
		 *
		 * Removes <script>, <style> and <foreignObject> together with everything inside
		 * them, BEFORE wp_kses() runs.
		 *
		 * This is not belt and braces, it is a real gap. kses removes a disallowed TAG and
		 * keeps its CONTENTS as text - so leaving these to the allowlist alone turned
		 *
		 *     <script>alert('xss')</script>
		 *     <style>* { background: url(javascript:alert(1)) }</style>
		 *
		 * into two bare text nodes sitting inside the <svg>, `javascript:` and all. Inside
		 * SVG's content model a loose text node is not rendered and not executed, so it was
		 * inert - but it is other people's script text being echoed into our page, and the
		 * only thing standing between inert and executable is where the markup happens to
		 * land. Verified against WordPress's real wp_kses(), not a stand-in.
		 *
		 * Unclosed forms are covered by the second pattern: `<script src=x>` with no closing
		 * tag would otherwise slip past a greedy pair match.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param  string $svg
		 * @return string
		 */
		private static function strip_containers($svg): string {

			$tags = ['script', 'style', 'foreignObject', 'iframe', 'object', 'embed', 'animate', 'set'];

			foreach($tags as $tag) {
				$svg = preg_replace('#<'.$tag.'\b[^>]*>.*?</'.$tag.'\s*>#is', '', (string) $svg);
				$svg = preg_replace('#<'.$tag.'\b[^>]*/?>#is', '', (string) $svg);
			}

			return (string) $svg;

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
				trigger_error('ACBS button icons: '.$message, E_USER_WARNING);
			}

		}

	}
