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

	// A PRE-PASS, because the <ul> is printed before a single column has been read and the
	// class has to be on it. `fl-is-accordion` says at least one column in this row is a
	// toggle, which is the one thing a rule on the grid itself cannot work out from its
	// children.
	//
	// READ AS AN ARRAY, not through a second have_rows() loop. A plain foreach can stop at
	// the first match; an ACF loop cannot, because breaking out of one leaves it on the
	// stack half consumed and the render loop below would then resume it mid-way rather
	// than starting over - see CLAUDE.md 05.2. A completed have_rows() pass would be safe
	// but would have to walk every column to stay safe, which is the wrong trade for a
	// question answered by the first `true`.
	//
	// No id is consumed here: the pre-pass reads a field and never includes the partial,
	// so acbs_unique_id() is untouched and the columns still number from one.
	$acbs_columns = get_sub_field( 'columns' );
	$acbs_is_accordion = false;

	if ( is_array( $acbs_columns ) ) {

		foreach ( $acbs_columns as $acbs_column ) {

			if ( ! empty( $acbs_column['column_accordion'] ) ) {
				$acbs_is_accordion = true;
				break;
			}

		}

	}

	$acbs_grid_class = 'fl-grid fl-columns' . ( $acbs_is_accordion ? ' fl-is-accordion fl-columns-have-accordion' : '' );

?><ul class="<?php echo esc_attr( $acbs_grid_class ); ?>">
	<?php
		while ( have_rows( 'columns' ) ) {
			the_row();
			acbs_row_partial( 'item', $row );
		}
	?>
</ul>
