<?php
	/**
	 * Flexible layout row: Testimonials
	 *
	 * Override at {theme}/acbs/rows/testimonials.php
	 *
	 * A grid of quotes. A repeater layout, so a `card` display cards each testimonial -
	 * see rows/testimonials/item.php.
	 *
	 * Note the layout name is plural. It was `testimonial` before this field set; the
	 * template, its partial and the layout all moved together, and any row saved under the
	 * old name is orphaned by design - the renderer skips a layout it has no definition
	 * for rather than rendering an empty section. See CLAUDE.md 05.7.
	 *
	 * WITH CARDS ON, THE GRID BECOMES A CAROUSEL BELOW DESKTOP. The `<ul>` is unchanged in
	 * either arrangement: all this template adds is one wrapper element around it, carrying
	 * the two responsive column counts as data. The row script turns that into a Swiper
	 * below 992px and leaves the CSS grid alone above it - so the desktop layout is exactly
	 * what it was, and a page with JavaScript off keeps a working grid at every width.
	 *
	 * WHY THE WRAPPER IS ONLY EMITTED FOR CARDS. Seamless testimonials have no edge to
	 * slide, so a carousel of them reads as text sliding around inside a section. The
	 * wrapper is the whole switch: no wrapper, nothing for the script to find, and the
	 * script does not read `layout_display` itself.
	 *
	 * `layout_display` IS READ HERE AND NOT IN THE ITEM PARTIAL, and that is not a style
	 * preference. It is a SECTION field, so `get_sub_field()` only resolves it against the
	 * row's own loop; called from inside the `testimonials` repeater it resolves against
	 * the nested loop, finds nothing and returns false, exactly as it does for a field that
	 * does not exist. columned_content shipped with that bug and its cards never painted.
	 *
	 * THE SAME TRAP BY A SECOND ROUTE, which is why every section field below is read
	 * BEFORE the have_rows() guard rather than after it. `have_rows()` used as a truth test
	 * does not just answer the question: on a repeater with rows it OPENS the nested loop
	 * and leaves it on the stack (CLAUDE.md 05.2). Every `get_sub_field()` after that point
	 * resolves against the repeater instead of the row, so reading `layout_display` below
	 * the guard returns false for a row that plainly has cards on, and the carousel simply
	 * never appears. Caught here by diffing the served markup for the wrapper element,
	 * not by anything failing.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.1
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	acbs_row_part( 'intro', $row );

	$acbs_slider = 'card' === (string) get_sub_field( 'layout_display' );

	// Grid & Display gives this layout all three counts. The tablet and mobile steps are
	// optional, and their fallbacks are RESOLVED HERE rather than in the script, mirroring
	// what structure.scss already does for the grid: an unset tablet value steps a
	// three-up-or-wider grid down to two and leaves anything narrower alone, and an unset
	// mobile value is one. Resolving them here is what keeps the script from having to know
	// the project's breakpoints or its fallback rules - it takes two finished numbers and
	// adds the peek.
	$acbs_cols    = (int) get_sub_field( 'layout_columns' );
	$acbs_cols_sm = (int) get_sub_field( 'layout_columns_sm' );
	$acbs_cols_xs = (int) get_sub_field( 'layout_columns_xs' );

	$acbs_cols    = $acbs_cols > 0 ? $acbs_cols : 3;
	$acbs_cols_sm = $acbs_cols_sm > 0 ? $acbs_cols_sm : ( $acbs_cols >= 3 ? 2 : $acbs_cols );
	$acbs_cols_xs = $acbs_cols_xs > 0 ? $acbs_cols_xs : 1;

	if ( ! have_rows( 'testimonials' ) ) {
		return;
	}

	if ( $acbs_slider ) {
		printf(
			'<div class="fl-testimonials-slider" data-columns-sm="%d" data-columns-xs="%d">',
			(int) $acbs_cols_sm,
			(int) $acbs_cols_xs
		);
	}

?><ul class="fl-grid fl-testimonials">
	<?php
		while ( have_rows( 'testimonials' ) ) {
			the_row();
			acbs_row_partial( 'item', $row );
		}
	?>
</ul><?php

	if ( $acbs_slider ) {
		echo '</div>';
	}
