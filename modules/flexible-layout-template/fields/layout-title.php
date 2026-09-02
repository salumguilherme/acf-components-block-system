<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined('ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Layout_Title
	 *
	 * Makes a collapsed page_sections row identifiable at a glance, which on a page with a
	 * dozen rows it otherwise is not: every Accordions row is called "Accordions".
	 *
	 * Two halves, and only the first is ours.
	 *
	 * 1. A COMPUTED TITLE, through ACF's `layout_title` filter. The row's own content is
	 *    the best label it has, so the first heading in section_content is used, falling
	 *    back to the section's HTML id, falling back to nothing - in which case the layout
	 *    label stands alone exactly as before.
	 *
	 * 2. A MANUAL RENAME, which is ACF's, not ours. ACF Pro has shipped layout renaming
	 *    since 6.5: a hidden `acf_fc_layout_custom_label` input per row, saved with the
	 *    post rather than over ajax, persisted in `_page_sections_layout_meta`, and
	 *    reachable from the "More layout actions" menu. This class adds a second, faster
	 *    way to reach it - an inline edit button on the row handle - and nothing else. The
	 *    rename itself goes through ACF's own `renameLayout()`, so there is one
	 *    implementation and one place the value is stored.
	 *
	 * WHICH FILTER, AND WHY IT MATTERS. The `key=` variant is used rather than the bare
	 * `acf/fields/flexible_content/layout_title`, so this can only ever affect
	 * page_sections. A site is free to register flexible content fields of its own and a
	 * blanket filter would rewrite their titles too.
	 *
	 * WHEN IT RUNS. On render, and again over ajax whenever a row is COLLAPSED - ACF's
	 * `renderLayout()` posts the row's current values to
	 * `wp_ajax_acf/fields/flexible_content/layout_title` and swaps the result in. So an
	 * editor who types a heading and folds the row up sees the new title immediately. Note
	 * ACF's own branch there: on a row that carries a manual rename the computed title is
	 * written to `.acf-fc-layout-original-title` instead, so it shows in brackets beside
	 * the custom one rather than replacing it.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Layout_Title {

		/**
		 * Longest computed prefix before it is trimmed. The row handle is a single line in
		 * a narrow column, so a full heading would push the layout label out of sight -
		 * which defeats the point, since the label is what says what the row IS.
		 */
		const MAX_LENGTH = 60;

		/**
		 * register function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function register() {

			add_filter('acf/fields/flexible_content/layout_title/key='.Page_Content::FIELD_KEY, [__CLASS__, 'filter_title'], 10, 4);

		}

		/**
		 * filter_title function
		 *
		 * Prepends the computed prefix to the layout label.
		 *
		 * PREPENDS rather than replaces, deliberately. The label is the only thing that
		 * says which layout a row is, and an editor scanning for "the stats row" needs it
		 * as much as they need to tell two stats rows apart. With no prefix available the
		 * title is returned untouched, so a row with no heading and no id looks exactly as
		 * it did before this class existed.
		 *
		 * $title arrives already run through esc_html() by ACF, and the return value goes
		 * through acf_esc_html() afterwards, so the prefix is escaped here to match: kses
		 * leaves an existing entity alone, so escaping once on each side is correct and
		 * does not double up.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $title
		 * @param array  $field
		 * @param array  $layout
		 * @param int    $i
		 *
		 * @return string
		 */
		public static function filter_title($title, $field, $layout, $i) {

			$prefix = self::prefix();

			if('' === $prefix) {
				return $title;
			}

			/**
			 * Filters what sits between the computed prefix and the layout label.
			 *
			 * @param string $separator
			 */
			$separator = (string) apply_filters('acbs/layout_title/separator', '–');

			return esc_html($prefix).' '.esc_html($separator).' '.$title;

		}

		/**
		 * prefix function
		 *
		 * The first of: the row's leading heading, the row's HTML id, nothing.
		 *
		 * Reads through get_sub_field() because ACF sets up a loop around this filter -
		 * see Layout::get_title(), which calls acf_add_loop() with the row's value before
		 * applying it and acf_remove_loop() afterwards. Both fields come from components
		 * injected into every layout, so both are always present.
		 *
		 * UNFORMATTED, deliberately. get_sub_field() would otherwise run the wysiwyg
		 * through acf_the_content for every row on every admin page load, to produce
		 * markup this method only wants to run a regular expression over.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		private static function prefix() {

			$heading = self::first_heading((string) get_sub_field('section_content', false));

			if('' !== $heading) {
				return $heading;
			}

			$id = trim((string) get_sub_field('section_container_id', false));

			if('' !== $id) {
				// An editor may or may not have typed the hash; the title shows one either
				// way, because that is what makes it read as an anchor rather than a word.
				return '#'.ltrim($id, '#');
			}

			return '';

		}

		/**
		 * first_heading function
		 *
		 * The text of the first h1-h6 in a block of wysiwyg HTML.
		 *
		 * A regular expression rather than DOMDocument: this runs once per row per admin
		 * page load, the input is a fragment rather than a document, and loading a
		 * fragment into DOMDocument means either a wrapper element or a stream of
		 * warnings about missing html/body. The pattern is deliberately narrow - it looks
		 * for a heading open tag, anything, and the matching close tag - and everything it
		 * captures is put through wp_strip_all_tags() immediately afterwards, so a nested
		 * <span class="text-primary"> (which is how a two-tone heading is written here)
		 * contributes its text and nothing else.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $html
		 *
		 * @return string
		 */
		private static function first_heading($html) {

			if('' === trim($html)) {
				return '';
			}

			if(!preg_match('#<h([1-6])\b[^>]*>(.*?)</h\1\s*>#is', $html, $match)) {
				return '';
			}

			$text = wp_strip_all_tags($match[2]);

			// The stored value is already entity-encoded by the editor, so &#8217; has to
			// come back to a character before it is measured or it counts as seven.
			$text = html_entity_decode($text, ENT_QUOTES, get_bloginfo('charset'));

			// A heading written across two lines carries the newline into the attribute.
			$text = trim(preg_replace('/\s+/u', ' ', $text));

			if('' === $text) {
				return '';
			}

			if(function_exists('mb_strlen') && mb_strlen($text) > self::MAX_LENGTH) {
				return rtrim(mb_substr($text, 0, self::MAX_LENGTH)).'…';
			}

			if(!function_exists('mb_strlen') && strlen($text) > self::MAX_LENGTH) {
				return rtrim(substr($text, 0, self::MAX_LENGTH)).'…';
			}

			return $text;

		}

	}
