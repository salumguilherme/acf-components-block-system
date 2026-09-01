<?php
	/**
	 * Flexible layout row: Icon Leaders
	 *
	 * Override at {theme}/acbs/rows/icon_leaders.php
	 *
	 * A row of icon-led points: an icon, a heading, a line or two of text, optionally
	 * linked. The items come from the layout's own `icon_leaders` repeater and nowhere
	 * else - the taxonomy/post-type `source` selector this layout used to carry is gone.
	 *
	 * The loop below is a NESTED have_rows(), called with no post id. That is not a
	 * shorthand: inside an active page_sections row, ACF resolves `icon_leaders` as a sub
	 * field of the current layout and opens a child loop, so the repeater is already
	 * loaded and already formatted. Passing a post id would take a different branch and
	 * break the moment these rows are rendered for something other than the global post.
	 * See CLAUDE.md section 05.3.
	 *
	 * The loop is never broken out of, so ACF pops it from its own stack when the rows run
	 * out - the only thing that does. If you add an early exit, call reset_rows() first.
	 *
	 * Columns and alignment are not read here: `layout_columns` and
	 * `layout_columns_alignment` are already on the wrapper as fl-loop-grid-columns-{n}
	 * and fl-loop-grid-columns-align-{x}, so the grid is a CSS concern.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	acbs_row_part( 'intro', $row );

	// An empty repeater renders no <ul> at all rather than an empty one, so a row whose
	// items were removed leaves the intro standing and nothing else. have_rows() returns
	// false for an empty value, so this is the whole check.
	if ( ! have_rows( 'icon_leaders' ) ) {
		return;
	}

?><ul class="fl-grid fl-leaders">
	<?php
		while ( have_rows( 'icon_leaders' ) ) {
			the_row();
			acbs_row_partial( 'item', $row );
		}
	?>
</ul>
