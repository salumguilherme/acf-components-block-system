<?php
	/**
	 * Flexible layout row: Icon List
	 *
	 * Override at {theme}/acbs/rows/icon_list.php
	 *
	 * A list of icon-and-text pairs, laid out in columns.
	 *
	 * THE CARD WRAPS THE WHOLE LIST, not each item. This layout is the exception among the
	 * repeater layouts: its "content" is the list, so a `card` display draws one box around
	 * the lot. .fl-card used to sit on each <li>, which turned a nine-item list into nine
	 * white boxes. As everywhere else, the class is emitted whether or not cards are on and
	 * the styling hangs off `.fl-card-box .fl-card`, so the section owns the switch.
	 *
	 * COLUMNS ARE CSS MULTI-COLUMN, not a grid and not split in PHP. See the stylesheet for
	 * why; the short version is that it is the only option that fills column one before
	 * column two while leaving this ONE list rather than several. Nothing here reads the
	 * column count.
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

?><div class="fl-icon-list-box fl-card">
	<ul class="fl-grid fl-icon-list">
		<?php
			while ( have_rows( 'icon_list' ) ) {
				the_row();
				acbs_row_partial( 'item', $row );
			}
		?>
	</ul>
</div>
