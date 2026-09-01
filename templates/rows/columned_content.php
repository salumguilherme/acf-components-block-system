<?php
	/**
	 * Flexible layout row: Columned Content
	 *
	 * Override at {theme}/acbs/rows/columned_content.php
	 *
	 * The intro, then the `columns` repeater laid out on the shared `.fl-grid`. The column
	 * count is not read here - layout_columns and its -sm / -xs steps are already on the
	 * wrapper as fl-loop-grid-columns-{n}, so the grid is a CSS concern and this template
	 * is the same at one across or four.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	acbs_row_part( 'intro', $row );

	// No columns renders no <ul> rather than an empty one, which would otherwise carry the
	// grid's gap and read as a gap under the intro that the editor cannot remove. This also
	// leaves the loop stack clean: have_rows() pops the loop itself when it returns false.
	if ( ! have_rows( 'columns' ) ) {
		return;
	}

?><ul class="fl-grid fl-columns">
	<?php
		while ( have_rows( 'columns' ) ) {
			the_row();
			acbs_row_partial( 'item', $row );
		}
	?>
</ul>
