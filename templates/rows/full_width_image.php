<?php
	/**
	 * Flexible layout row: Full Width Image
	 *
	 * Override at {theme}/acbs/rows/full_width_image.php, or full-width-image.php.
	 *
	 * The image itself is NOT printed here. It is the section's background, applied by
	 * rows/full_width_image.css, and the darkening gradient over it is a pseudo-element fed
	 * by the `--fl-overlay-bg` custom property that Module::layout_wrapper_style() emits.
	 * Both of those reach the <section>, which this template sits inside rather than owns,
	 * so there is nothing for the markup to do but the content that sits on top.
	 *
	 * That split is deliberate. The overlay used to render from `acbs/wrapper/before_container`,
	 * a hook fired by templates/wrapper.php - which a theme REPLACES wholesale, so any theme
	 * that had copied that file lost the overlay silently. A custom property emitted by a
	 * CLASS survives a copied wrapper, because the copy still calls $row->wrapper_style().
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

?><div class="fl-full-width-image-content">
	<?php echo wp_kses_post(get_sub_field('written_content')); ?>
	<?php acbs_row_part( 'buttons', $row ); ?>
</div>