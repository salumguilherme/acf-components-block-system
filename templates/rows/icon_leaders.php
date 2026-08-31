<?php
	/**
	 * Flexible layout row: Icon Leaders
	 *
	 * Override at {theme}/acbs/rows/icon_leaders.php
	 *
	 * A row of icon-led points: an icon, a heading, a line or two of text, optionally
	 * linked. Identical in structure to image_cards_grid - intro, then a grid of items
	 * from whichever of the three sources the editor chose - and deliberately so: the only
	 * differences between the two are which fields the items map to, which lives in
	 * Items_Source::DEFAULTS rather than in either template.
	 *
	 * `layout_columns` and `columns_alignment` are on the wrapper already, as
	 * fl-loop-grid-columns-{n} and fl-loop-grid-columns-align-{x}.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	acbs_row_part( 'intro', $row );

	$acbs_items = acbs_row_items( $row );

	if ( ! $acbs_items->count() ) {
		return;
	}

?><ul class="fl-grid fl-leaders">
	<?php $acbs_items->render(); ?>
</ul>
