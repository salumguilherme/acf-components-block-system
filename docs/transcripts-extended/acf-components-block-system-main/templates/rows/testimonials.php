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
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	acbs_row_part( 'intro', $row );

	if ( ! have_rows( 'testimonials' ) ) {
		return;
	}

?><ul class="fl-grid fl-testimonials">
	<?php
		while ( have_rows( 'testimonials' ) ) {
			the_row();
			acbs_row_partial( 'item', $row );
		}
	?>
</ul>
