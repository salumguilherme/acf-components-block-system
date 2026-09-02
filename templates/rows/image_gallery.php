<?php
	/**
	 * Flexible layout row: Image Gallery
	 *
	 * Override at {theme}/acbs/rows/image_gallery.php
	 *
	 * Reference: Figma "Untitled", node 40:1541.
	 *
	 * A Swiper carousel that DELIBERATELY OVERFLOWS ITS CONTAINER. The first slide lines up
	 * with the left edge of `.fl-container`, and the slides that follow run out past the
	 * right edge to the window. That is what makes it read as a strip that continues rather
	 * than a row that has been cut - and it is why the sheet sets `overflow: visible` on
	 * the swiper, which is not Swiper's own default.
	 *
	 * THE COLUMN COUNT IS PASSED AS DATA, not read from the wrapper classes. The section
	 * carries fl-loop-grid-columns-{n} and its -sm / -xs steps, and the script could parse
	 * them - but those describe a CSS grid, and Swiper needs the same numbers as a
	 * `slidesPerView` per breakpoint, with tablet and mobile carrying an extra .4 so the
	 * next slide peeks in. Deriving that from a class name means the script has to know the
	 * project's breakpoints AND the +0.4 rule; passing the three numbers leaves it knowing
	 * neither.
	 *
	 * ASPECT RATIO is a per-row field rather than a fixed value, and it reaches CSS as a
	 * custom property instead of a width and a height on the image. Every slide is the same
	 * shape at every screen size, so one declaration on the slide governs the lot and the
	 * image simply covers it.
	 *
	 * LAZY LOADING is native: `loading="lazy"` on each image, which is what Swiper 14 reads.
	 * It has no lazy module of its own any more - it watches for images marked this way and
	 * preloads the neighbouring slides through `lazyPreloadPrevNext`. wp_get_attachment_image()
	 * adds the attribute itself, so the template only has to not fight it.
	 *
	 * THE SLIDE IS AN <a>, not a <div> wrapping one. PhotoSwipe reads the href and the
	 * dimensions off the gallery's children, and Swiper needs those same elements to carry
	 * `.swiper-slide` - so one element has to be both.
	 *
	 * `data-pswp-width` / `data-pswp-height` are the FULL image's real dimensions, which is
	 * how PhotoSwipe sizes and zooms its frame before the image has downloaded.
	 *
	 * `data-cropped="true"` is the one attribute that earns its place here. The thumbnail is
	 * `object-fit: cover` inside a fixed ratio, so what the visitor sees is a CROP of the
	 * image PhotoSwipe is about to open. Without this, the zoom animation interpolates
	 * between the full image's shape and the thumbnail's box and the picture visibly
	 * stretches on open and close; with it, PhotoSwipe animates from the cropped area
	 * instead. This is exactly the case that made a cover-fit thumbnail look wrong before.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	acbs_row_part( 'intro', $row );

	$acbs_images = get_sub_field( 'images' );

	if ( empty( $acbs_images ) || ! is_array( $acbs_images ) ) {
		return;
	}

	/**
	 * The slide shape, as a CSS aspect-ratio value.
	 *
	 * The field is `width:height` because that is how a person says it. CSS wants
	 * `width / height`, and it will not accept anything else - so an unparseable value
	 * falls back to the design's own ratio rather than emitting a declaration the browser
	 * drops, which would collapse every slide to nothing.
	 */
	$acbs_ratio = trim( (string) get_sub_field( 'aspect_ratio' ) );
	$acbs_ratio = str_replace( [ ':', 'x', '/' ], ' ', $acbs_ratio );
	$acbs_parts = preg_split( '/\s+/', $acbs_ratio, -1, PREG_SPLIT_NO_EMPTY );

	$acbs_w = isset( $acbs_parts[0] ) && is_numeric( $acbs_parts[0] ) ? (float) $acbs_parts[0] : 0;
	$acbs_h = isset( $acbs_parts[1] ) && is_numeric( $acbs_parts[1] ) ? (float) $acbs_parts[1] : 1;

	if ( $acbs_w <= 0 || $acbs_h <= 0 ) {
		$acbs_w = 1.42;
		$acbs_h = 1;
	}

	// Grid & Display gives this layout the three column counts and nothing else. Each falls
	// back to the design's own arrangement rather than to 1, so a row saved before these
	// fields existed still looks like a gallery.
	$acbs_cols    = (int) get_sub_field( 'layout_columns' );
	$acbs_cols_sm = (int) get_sub_field( 'layout_columns_sm' );
	$acbs_cols_xs = (int) get_sub_field( 'layout_columns_xs' );

	$acbs_cols    = $acbs_cols > 0 ? $acbs_cols : 3;
	$acbs_cols_sm = $acbs_cols_sm > 0 ? $acbs_cols_sm : 2;
	$acbs_cols_xs = $acbs_cols_xs > 0 ? $acbs_cols_xs : 1;

	$acbs_id = acbs_unique_id( 'fl-gallery' );

?><div class="fl-gallery swiper" id="<?php echo esc_attr( $acbs_id ); ?>"
	style="--fl-gallery-ratio: <?php echo esc_attr( $acbs_w . ' / ' . $acbs_h ); ?>"
	data-columns="<?php echo esc_attr( $acbs_cols ); ?>"
	data-columns-sm="<?php echo esc_attr( $acbs_cols_sm ); ?>"
	data-columns-xs="<?php echo esc_attr( $acbs_cols_xs ); ?>">

	<div class="swiper-wrapper">
		<?php foreach ( $acbs_images as $acbs_image ) : ?>
			<?php
				$acbs_image_id = 0;

				if ( is_array( $acbs_image ) && ! empty( $acbs_image['ID'] ) ) {
					$acbs_image_id = (int) $acbs_image['ID'];
				} elseif ( is_numeric( $acbs_image ) ) {
					$acbs_image_id = (int) $acbs_image;
				}

				if ( 0 === $acbs_image_id ) {
					continue;
				}
			?>
			<?php
				// The full image behind the lightbox, and its real dimensions for
				// data-lg-size. wp_get_attachment_image_src() returns [ url, width,
				// height ], and 'full' is the original upload rather than any registered
				// size - which is the point: the slide shows a crop, the lightbox shows
				// the photograph.
				$acbs_full = wp_get_attachment_image_src( $acbs_image_id, 'image-1440' );

				if ( ! $acbs_full ) {
					continue;
				}
			?>
			<a class="swiper-slide fl-gallery-slide"
				href="<?php echo esc_url( $acbs_full[0] ); ?>"
				data-pswp-width="<?php echo esc_attr( (int) $acbs_full[1] ); ?>"
				data-pswp-height="<?php echo esc_attr( (int) $acbs_full[2] ); ?>"
				data-cropped="true">
				<?php
					// image-720x324 is the size asked for. Note it is a HARD CROP at
					// 2.22:1 while a slide is 1.42:1 by default, so object-fit trims it
					// further at the sides - see the row sheet. The lightbox is unaffected:
					// it opens the full image.
					echo wp_get_attachment_image(
						$acbs_image_id,
						'image-720',
						false,
						[
							'class'    => 'fl-gallery-image',
							'loading'  => 'lazy',
							'decoding' => 'async',
						]
					);
				?>
			</a>
		<?php endforeach; ?>
	</div>

</div>
