<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Rows;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Filenames
	 *
	 * The one definition of how a layout name may be spelled on disk.
	 *
	 * ACF layout names carry underscores and always will - they are field data, and §08 of
	 * the handbook has the list of erdc_* identifiers that turned out to be data rather
	 * than names. Filenames are not data, so a layout may be written either way:
	 *
	 *     templates/rows/columned_content.php   assets/css/rows/columned_content.css
	 *     templates/rows/columned-content.php   assets/css/rows/columned-content.css
	 *
	 * This exists as its own class rather than as a method on Template_Loader because
	 * three unrelated lookups need the same answer - row templates, row stylesheets and
	 * row scripts - and a rule that lives in one of them and is copied into the other two
	 * is a rule that drifts. It is three lines; the point is that there is only one copy.
	 *
	 * The rule it does NOT own is precedence, because that differs by consumer.
	 * Template_Loader ranks directory over spelling (a child theme's dashed file beats a
	 * parent theme's underscored one); Assets has no ranking to do between plugin and
	 * theme at all, since those two layer rather than compete.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Rows
	 */
	class Filenames {

		/**
		 * variants function
		 *
		 * The spellings one relative path may be written as, underscore first.
		 *
		 * The replacement runs over the WHOLE relative path rather than just the layout
		 * segment, so a row's own directory is covered too: rows/columned_content/item.php
		 * also answers to rows/columned-content/item.php. Nothing above the layout is at
		 * risk from that, because no directory the plugin owns has an underscore in it.
		 *
		 * A path with no underscore yields one variant, not two, so the common case costs
		 * a single file_exists() exactly as it did before.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $relative
		 *
		 * @return array
		 */
		public static function variants($relative) {

			$dashed = str_replace('_', '-', $relative);

			return $dashed === $relative ? [$relative] : [$relative, $dashed];

		}

	}
