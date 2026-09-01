<?php
	/**
	 * Flexible layout row item: Icon List
	 *
	 * Override at {theme}/acbs/rows/icon_list/item.php
	 *
	 * Included once per repeater row from inside the loop in rows/icon_list.php, so the
	 * item's fields are in scope through get_sub_field().
	 *
	 * .fl-card is where a `card` display lands for this layout. It is present whether or
	 * not cards are on: the styling hangs off `.fl-card-box .fl-card`, so the switch is
	 * the section's business and this template never has to check it.
	 *
	 * The icon accepts SVG only and returns an array, and is rendered at 'full' because an
	 * SVG has no intermediate sizes.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	$acbs_icon    = get_sub_field( 'icon' );
	$acbs_content = (string) get_sub_field( 'content' );

?><li class="fl-icon-list-item fl-card">
	<?php if ( ! empty( $acbs_icon['ID'] ) ) : ?>
		<span class="fl-icon-list-media">
			<?php echo wp_get_attachment_image( $acbs_icon['ID'], 'full', false, [ 'class' => 'fl-icon-list-icon' ] ); ?>
		</span>
	<?php endif; ?>
	<?php if ( '' !== $acbs_content ) : ?>
		<?php // A plain text field, so escaped rather than filtered. ?>
		<span class="fl-icon-list-content"><?php echo esc_html( $acbs_content ); ?></span>
	<?php endif; ?>
</li>
