<?php

	namespace ERDC\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Field_Merge
	 *
	 * Folds the fields of a site's own ACF field group into one of the plugin's field
	 * sets. Every mechanism that lets a site extend the plugin through a tagged field
	 * group shares this: the "Flexible Layout Component" rule (Site_Fields_Base, so
	 * Buttons/Intro/Page Header), the "Flexible Layout Row" rule (Common_Fields), and
	 * "Flexible Layout = Page Content" layouts (Site_Layouts).
	 *
	 * A site field sharing a `name` with one of ours REPLACES it; anything else is added.
	 * Where each one ends up is the interesting part:
	 *
	 * - A replacement normally keeps OUR position, so overriding one field can't quietly
	 *   reshuffle a layout the site never touched.
	 * - But from the first field the user genuinely ADDED onward, their ordering is
	 *   clearly deliberate, so it wins: any replacement sitting below an added field
	 *   moves out of our slot and into the position the user put it in.
	 *
	 * Worked through against the four cases this was specified with, where
	 * section_content/section_intro are ours and "new" is the site's own:
	 *
	 *   [section_content, new]                          -> nothing moves; new is appended.
	 *   [new, section_content, section_intro]            -> both move, below new, in that order.
	 *   [section_content, section_intro]                 -> nothing moves; no additions at all.
	 *   [section_content, new, section_intro, new2]      -> only section_intro moves;
	 *                                                       section_content is above the
	 *                                                       first addition so it stays put.
	 *
	 * @version 1.0.28
	 * @since   1.0.28
	 * @package ERDC\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Field_Merge {

		/**
		 * merge function
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 *
		 * @param array $base_fields The plugin's own fields.
		 * @param array $site_fields The site's fields, in the order the site put them in.
		 *
		 * @return array
		 */
		public static function merge(array $base_fields, array $site_fields) {

			// Fixed against the field list as it stands BEFORE anything is merged, so it
			// reflects what the site is replacing versus adding rather than shifting as we
			// go.
			$reorder_from = self::first_added_index($base_fields, $site_fields);

			$tail = [];

			foreach($site_fields as $index => $site_field) {

				$base_index = self::find_index($base_fields, $site_field['name'] ?? '');

				// Replaces one of ours with nothing newly added above it: keep our position.
				if($base_index !== null && ($reorder_from === null || $index < $reorder_from)) {
					$base_fields[$base_index] = $site_field;
					continue;
				}

				// Newly added, or a replacement the site placed below something newly added -
				// either way it belongs in the site's ordering from here on, so vacate our
				// slot and let it fall into place at the end.
				if($base_index !== null) {
					unset($base_fields[$base_index]);
				}

				$tail[] = $site_field;

			}

			return array_merge(array_values($base_fields), $tail);

		}

		/**
		 * first_added_index function
		 *
		 * Position, within the SITE's field list, of the first field that is genuinely an
		 * addition rather than a replacement - the point from which the site's ordering
		 * takes over. Null when the site only replaces fields, in which case nothing moves.
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 *
		 * @param array $base_fields
		 * @param array $site_fields
		 *
		 * @return int|string|null
		 */
		private static function first_added_index(array $base_fields, array $site_fields) {

			foreach($site_fields as $index => $site_field) {

				if(self::find_index($base_fields, $site_field['name'] ?? '') === null) {
					return $index;
				}

			}

			return null;

		}

		/**
		 * find_index function
		 *
		 * Position of the field named $name, or null if we have no such field.
		 *
		 * A field with no name at all - a tab, message or accordion - can never replace
		 * anything, since there is nothing to match it on, so it always reads as an
		 * addition. Site_Layouts' own copy of this merge used to compare names without that
		 * guard, which meant a site's tab silently replaced the plugin's first tab instead
		 * of being added alongside it; routing it through here fixes that too.
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 *
		 * @param array  $base_fields
		 * @param string $name
		 *
		 * @return int|string|null
		 */
		private static function find_index(array $base_fields, $name) {

			if($name === '' || $name === null) {
				return null;
			}

			foreach($base_fields as $index => $base_field) {

				if(($base_field['name'] ?? '') === $name) {
					return $index;
				}

			}

			return null;

		}

	}
