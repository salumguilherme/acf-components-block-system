<?php
	/**
	 * Flexible layout row: Sticky CTA
	 *
	 * Override at {theme}/acbs/rows/sticky-cta.php
	 *
	 * Reference: Figma "Untitled", nodes 45:7186 (desktop) and 45:7234 (up to 992px).
	 *
	 * A bar that floats over the page and slides in once a trigger fires. THE SECTION IS THE
	 * BAR: `fl-bg-*` paints it, the vertical padding step sizes it, and `.fl-container` sets
	 * the content width - so the floating behaviour belongs to the section, not to anything
	 * this file renders. That is why the position and breakpoint classes come from
	 * Module::layout_wrapper_classes() instead: a template only ever renders INSIDE the
	 * container and cannot reach the element that has to be positioned.
	 *
	 * WHAT THIS FILE OWNS is the bar's contents and the trigger CONFIGURATION, the latter as
	 * data attributes on `.fl-sticky-cta`. The script reads them from here and applies its
	 * behaviour to the section, which is the element `onRowReady` hands it. Passing finished
	 * values as data is the same arrangement image_gallery uses for its column counts, and
	 * for the same reason: the script ends up knowing nothing about ACF.
	 *
	 * NO INTRO PART IS RENDERED, and the field is not registered either - `sticky_cta` is in
	 * Common_Fields::intro_exclusions(). A bar has nowhere to put a section heading; its
	 * heading is `written_content`, inside the bar.
	 *
	 * THREE LAYOUT SHAPES, driven by whether there is an image and by
	 * layout_columns_alignment, which the section already carries as
	 * `fl-loop-grid-columns-align-*`. This template emits no alignment class of its own and
	 * no shape class either - the sheet reads the existing alignment class and the presence
	 * of `.fl-sticky-cta-media`, so there is one source of truth for the alignment and
	 * nothing here to keep in step with it.
	 *
	 * THE CLOSE BUTTON SITS IN A RAIL, and the rail is the awkward part of this row. The tab
	 * has to escape the section's vertical padding (it is drawn wholly above a bottom bar,
	 * wholly below a top one) while staying aligned to the CONTAINER's horizontal edge. Those
	 * are two different containing blocks: the section for the vertical, the container for
	 * the horizontal. One absolutely positioned element cannot resolve against both, so the
	 * rail is positioned against the section and carries `fl-container container` itself, so
	 * its width, max-width and gutters track the row's real container - including any change
	 * a theme makes to it. Reproducing the container's geometry in a `right:` value would
	 * have been a second copy of it, wrong the day the theme's container moved.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	$acbs_content = (string) get_sub_field( 'written_content' );
	$acbs_image   = get_sub_field( 'image' );

	// The trigger. `x_value` carries three different kinds of value depending on the mode -
	// a pixel or vh distance, a number of seconds, or a CSS selector - so it is passed
	// through verbatim and the script parses it per mode. It is NOT cast or validated here:
	// `required` guarantees only that it is not empty, and a selector cannot be checked in
	// PHP at all.
	$acbs_trigger = (string) get_sub_field( 'show_cta_on' );
	$acbs_trigger = '' !== $acbs_trigger ? $acbs_trigger : 'load';
	$acbs_x_value = (string) get_sub_field( 'x_value' );

	// How often it may slide in. 0 means always, which is why this is emitted even when it
	// is zero rather than skipped: an absent attribute would be indistinguishable from an
	// unsaved field, and the script's fallback has to be the permissive one.
	$acbs_count  = (int) get_sub_field( 'display_count' );
	$acbs_period = (string) get_sub_field( 'display_period' );
	$acbs_period = '' !== $acbs_period ? $acbs_period : 'per_session';

	$acbs_position = (string) get_sub_field( 'vertical_position' );
	$acbs_position = '' !== $acbs_position ? $acbs_position : 'bottom';

?><div class="fl-sticky-cta"
	data-trigger="<?php echo esc_attr( $acbs_trigger ); ?>"
	data-trigger-value="<?php echo esc_attr( $acbs_x_value ); ?>"
	data-display-count="<?php echo esc_attr( (string) $acbs_count ); ?>"
	data-display-period="<?php echo esc_attr( $acbs_period ); ?>"
	data-position="<?php echo esc_attr( $acbs_position ); ?>">

	<?php
		// The rail is first in the markup so a keyboard user reaches the close button before
		// the bar's own link, which is the order a dismissible overlay wants: the way out
		// comes before the content. It is positioned, so source order does not affect where
		// it draws.
	?>
	<div class="fl-sticky-cta-close-rail fl-container container">
		<button type="button" class="fl-sticky-cta-close" data-acbs-sticky-close>
			<span class="fl-sticky-cta-close-icon" aria-hidden="true"></span>
			<span class="visually-hidden"><?php esc_html_e( 'Close', 'acbs' ); ?></span>
		</button>
	</div>

	<?php if ( is_array( $acbs_image ) && ! empty( $acbs_image['ID'] ) ) : ?>
		<?php
			// `return_format => array` on this field, so the ID is available - the trap
			// being that a field declared with an empty return format hands back the bare
			// attachment id and `$acbs_image['ID']` would index an integer and render
			// nothing, which is exactly how columned_content's icon failed.
			//
			// No size is forced: the sheet caps the height at 180 / 115 / 75px per
			// breakpoint and lets the width follow, so a hard crop here would fight it.
		?>
		<div class="fl-sticky-cta-media">
			<?php
				echo wp_get_attachment_image(
					(int) $acbs_image['ID'],
					'image-720',
					false,
					[
						'class'    => 'fl-sticky-cta-image',
						'loading'  => 'lazy',
						'decoding' => 'async',
					]
				);
			?>
		</div>
	<?php endif; ?>

	<div class="fl-sticky-cta-body">
		<?php if ( '' !== $acbs_content ) : ?>
			<div class="fl-sticky-cta-content"><?php echo wp_kses_post( apply_filters( 'the_content', $acbs_content ) ); ?></div>
		<?php endif; ?>
		<?php acbs_row_part( 'buttons', $row ); ?>
	</div>

</div>
