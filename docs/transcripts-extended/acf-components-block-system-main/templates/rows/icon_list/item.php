<?php
	/**
	 * Flexible layout row item: Icon List
	 *
	 * Override at {theme}/acbs/rows/icon_list/item.php
	 *
	 * Included once per repeater row from inside the loop in rows/icon_list.php, so the
	 * item's fields are in scope through get_sub_field().
	 *
	 * NO .fl-card HERE, unlike every other item partial in the plugin. For this layout the
	 * card wraps the whole list, in rows/icon_list.php: the row's content is the list, not
	 * the individual entries. It was on the <li> and drew one white box per item.
	 *
	 * `column_alignment` is a ROW-level field for this layout (layout_columns_alignment on
	 * the wrapper), not a per-item one, so there is no alignment class to emit here. The
	 * stylesheet reads it off the section.
	 *
	 * The icon accepts SVG only and returns an array, and is rendered at 'full' because an
	 * SVG has no intermediate sizes. Its intrinsic width and height are left on the <img>
	 * deliberately: the sheet sizes by height with `width: auto`, so the browser needs the
	 * real ratio to compute the width, and these icons genuinely differ (24x24, 24x18).
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	$acbs_icon    = get_sub_field( 'icon' );
	$acbs_content = (string) get_sub_field( 'content' );

?><li class="fl-icon-list-item">
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
