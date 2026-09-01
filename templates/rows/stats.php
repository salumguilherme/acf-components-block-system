<?php
	/**
	 * Flexible layout row: Stats
	 *
	 * Override at {theme}/acbs/rows/stats.php
	 *
	 * A grid of figures, each with a subtitle, supporting copy and its own buttons. A
	 * repeater layout, so a `card` display cards each stat - see rows/stats/item.php.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	acbs_row_part( 'intro', $row );

	if ( ! have_rows( 'stats' ) ) {
		return;
	}

?><ul class="fl-grid fl-stats">
	<?php
		while ( have_rows( 'stats' ) ) {
			the_row();
			acbs_row_partial( 'item', $row );
		}
	?>
</ul>
