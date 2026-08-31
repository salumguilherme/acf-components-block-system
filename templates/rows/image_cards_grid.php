<?php
	/**
	 * Flexible layout row: Image Cards Simple Grid
	 *
	 * Override at {theme}/acbs/rows/image_cards_grid.php
	 *
	 * A grid of cards, each a picture, a title and an optional link. Where the cards come
	 * from is the editor's choice: the row's own repeater, a set of taxonomy terms, or a
	 * set of posts. That choice is resolved by acbs_row_items(), and the item template
	 * behind it is the same file in all three cases - see rows/image_cards_grid/item.php.
	 *
	 * The column count and alignment are NOT read here. `layout_columns` and
	 * `columns_alignment` are already on the wrapper as fl-loop-grid-columns-{n} and
	 * fl-loop-grid-columns-align-{x} (see Module::layout_wrapper_classes()), so the grid
	 * is a CSS concern and this file stays markup.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	acbs_row_part( 'intro', $row );

	$acbs_items = acbs_row_items( $row );

	// An empty grid prints no <ul> rather than an empty one: a row whose terms were
	// deleted, or whose posts were unpublished, should leave the intro standing and
	// nothing else.
	if ( ! $acbs_items->count() ) {
		return;
	}

?><ul class="fl-grid fl-cards">
	<?php $acbs_items->render(); ?>
</ul>
