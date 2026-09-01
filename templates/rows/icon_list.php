<?php
	/**
	 * Flexible layout row: Icon List
	 *
	 * Override at {theme}/acbs/rows/icon_list.php
	 *
	 * A grid of icon-and-text pairs. A repeater layout, so a `card` display puts a card
	 * around EACH item rather than around the row - see rows/icon_list/item.php, where
	 * .fl-card sits.
	 *
	 * Columns and alignment come off the wrapper as classes; the grid is a CSS concern.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	acbs_row_part( 'intro', $row );

	if ( ! have_rows( 'icon_list' ) ) {
		return;
	}

?><ul class="fl-grid fl-icon-list">
	<?php
		while ( have_rows( 'icon_list' ) ) {
			the_row();
			acbs_row_partial( 'item', $row );
		}
	?>
</ul>
