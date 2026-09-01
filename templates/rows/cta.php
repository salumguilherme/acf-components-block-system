<?php
	/**
	 * Flexible layout row: Call to Action
	 *
	 * Override at {theme}/acbs/rows/cta.php
	 *
	 * A block of content and its buttons, either side by side or stacked - display_type
	 * chooses which.
	 *
	 * Not a repeater layout, so when layout_display is `card` the card is this single
	 * content block. The intro stays outside it, which is why acbs_row_part() is called
	 * before .fl-card opens.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	acbs_row_part( 'intro', $row );

	$acbs_content = (string) get_sub_field( 'written_content' );
	$acbs_type    = (string) get_sub_field( 'display_type' );
	$acbs_type    = '' !== $acbs_type ? $acbs_type : 'columns';

?><div class="fl-cta fl-card fl-cta-<?php echo esc_attr( $acbs_type ); ?>">
	<?php if ( '' !== $acbs_content ) : ?>
		<div class="fl-cta-content"><?php echo wp_kses_post( apply_filters( 'the_content', $acbs_content ) ); ?></div>
	<?php endif; ?>
	<?php acbs_row_part( 'buttons', $row ); ?>
</div>
