<?php
	/**
	 * Flexible layout row: Logo Gallery
	 *
	 * Override at {theme}/acbs/rows/logo_gallery.php
	 *
	 * A wall of partner logos: the intro, then the `logos` gallery field laid out on the
	 * shared `.fl-grid`. The column count is not read here - layout_columns and its -sm /
	 * -xs steps are already on the wrapper as fl-loop-grid-columns-{n}, so the grid is a
	 * CSS concern and this template stays the same at one across or eight.
	 *
	 * A gallery is a plain array, not a repeater, so there is no have_rows() and nothing
	 * to reset. get_sub_field() returns the whole set in one call.
	 *
	 * IMAGE SIZE. `medium` (300px on the long edge), never `full`, and deliberately not one
	 * of the site's cropped sizes. Two reasons, and they pull in the same direction:
	 *
	 *   - Logos arrive at whatever proportion their owner supplies - the current set runs
	 *     from 296x80 to 228x228 - so any hard-cropped size would trim a wordmark. Every
	 *     `image-*x*` size on this install crops; `medium` scales.
	 *   - The rendered cell is ~186px wide at the design's six-across, so 300px covers it
	 *     with room for a denser screen. `large` and `medium_large` are both wider than the
	 *     source files, so they would only ever resolve back to the original anyway.
	 *
	 * wp_get_attachment_image() falls back to the full file when a size was never generated,
	 * which is what happens to the logos narrower than 300px. That is the intended path, not
	 * a failure - it is the same image either way.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	acbs_row_part( 'intro', $row );

	$acbs_logos = get_sub_field( 'logos' );

	// An empty gallery renders no <ul> at all rather than an empty one, so a row whose
	// logos were removed leaves the intro standing and nothing else.
	if ( empty( $acbs_logos ) || ! is_array( $acbs_logos ) ) {
		return;
	}

?><ul class="fl-grid fl-logos">
	<?php foreach ( $acbs_logos as $acbs_logo ) : ?>
		<?php
			if ( empty( $acbs_logo['ID'] ) ) {
				continue;
			}

			$acbs_logo_id = (int) $acbs_logo['ID'];

			// An SVG carries no intrinsic width or height in its attachment meta, so
			// wp_get_attachment_image() emits width="1" height="1" and the browser lays the
			// logo out as a 1px box. The size argument is meaningless for one as well: there
			// are no intermediate files. So SVGs get a hand-built <img> and everything else
			// goes through core, which brings srcset with it.
			$acbs_is_svg = 'image/svg+xml' === get_post_mime_type( $acbs_logo_id );
		?>
		<li class="fl-logo">
			<?php if ( $acbs_is_svg ) : ?>
				<img
					class="fl-logo-image"
					src="<?php echo esc_url( wp_get_attachment_url( $acbs_logo_id ) ); ?>"
					alt="<?php echo esc_attr( (string) get_post_meta( $acbs_logo_id, '_wp_attachment_image_alt', true ) ); ?>"
					loading="lazy"
					decoding="async"
				>
			<?php else : ?>
				<?php
					echo wp_get_attachment_image(
						$acbs_logo_id,
						'medium',
						false,
						[
							'class'    => 'fl-logo-image',
							'loading'  => 'lazy',
							'decoding' => 'async',
						]
					);
				?>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
