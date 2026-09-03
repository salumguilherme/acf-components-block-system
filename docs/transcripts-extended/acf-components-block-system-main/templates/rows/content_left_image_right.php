<?php
	/**
	 * Flexible layout row: Content and Image - 2 Columns
	 *
	 * Override at {theme}/acbs/rows/content_left_image_right.php
	 *
	 * Two columns: a wysiwyg block with optional buttons, and a hero image. Which side the
	 * content sits on is `content_alignment` (left | right), and it is a COLUMN ORDER
	 * switch, not a text alignment - unlike `layout_columns_alignment` on the repeater
	 * layouts, this field has no `default` and no `center`, so there is nothing here to
	 * defer to the theme.
	 *
	 * The content is first in the DOM in both cases and the image is moved with `order`,
	 * so the heading is read before the picture whichever way round the row is drawn.
	 *
	 * The intro is rendered ABOVE the split, centred, exactly as every other layout does.
	 * The design draws a heading inside the content column, but that heading is part of
	 * `written_content` (a wysiwyg carrying its own <h2>), not the Intro component -
	 * confirmed against the live rows, whose section_content is empty. So the intro stays
	 * where it is everywhere else and simply renders nothing here. An editor who does fill
	 * it in gets a centred section heading over both columns, which is the consistent
	 * behaviour rather than a special case.
	 *
	 * IMAGE SIZE. `large` (1024px on the long edge) as the src, never `full`, and never one
	 * of the site's cropped sizes - `image-1024x768`, `image-1440x900` and the rest all
	 * recrop, and a hero photographed to a particular framing should not be trimmed by the
	 * template. The real work is done by srcset: wp_get_attachment_image() emits every
	 * registered size that shares the source's aspect ratio, which on this install means
	 * medium / medium_large / large / image-720 / image-1440 / full, and the explicit
	 * `sizes` below tells the browser the column is 607px from the tablet breakpoint up.
	 * A 2x desktop screen therefore pulls image-1440 and a phone pulls medium_large,
	 * without this template naming either.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	acbs_row_part( 'intro', $row );

	$acbs_image   = get_sub_field( 'image' );
	$acbs_content = (string) get_sub_field( 'written_content' );
	$acbs_align   = (string) get_sub_field( 'content_alignment' );

	// The field is required and defaults to `left`, so this only catches a row saved
	// before the field existed. `right` is the only other value the button group offers.
	$acbs_align = 'right' === $acbs_align ? 'right' : 'left';

	$acbs_image_id = ! empty( $acbs_image['ID'] ) ? (int) $acbs_image['ID'] : 0;

	// get_sub_field() rather than have_rows() for this test. have_rows() would PUSH a
	// buttons loop onto ACF's stack just to answer the question, leaving parts/buttons.php
	// to resume a loop it did not open - it works today, but it is the arrangement that
	// corrupts every following row the moment anything between here and there breaks out
	// early (see the loop rules). Reading the value is free of that.
	$acbs_buttons = get_sub_field( 'buttons' );

	// Both fields are required, but "required" is only enforced at save time - a row that
	// predates the field, or whose image was deleted from the library, still reaches here.
	// A single column is the honest fallback; the sheet lets either side stand alone.
	if ( 0 === $acbs_image_id && '' === $acbs_content ) {
		return;
	}

?><div class="fl-content-image fl-content-align-<?php echo esc_attr( $acbs_align ); ?>">

	<?php if ( '' !== $acbs_content || ! empty( $acbs_buttons ) ) : ?>
		<div class="fl-content-image-body">
			<?php if ( '' !== $acbs_content ) : ?>
				<div class="fl-content-image-text"><?php echo wp_kses_post( apply_filters( 'the_content', $acbs_content ) ); ?></div>
			<?php endif; ?>
			<?php acbs_row_part( 'buttons', $row ); ?>
		</div>
	<?php endif; ?>

	<?php if ( 0 !== $acbs_image_id ) : ?>
		<div class="fl-content-image-media">
			<?php
				echo wp_get_attachment_image(
					$acbs_image_id,
					'large',
					false,
					[
						'class'    => 'fl-content-image-img',
						'sizes'    => '(min-width: 992px) 607px, 100vw',
						'loading'  => 'lazy',
						'decoding' => 'async',
					]
				);
			?>
		</div>
	<?php endif; ?>

</div>
