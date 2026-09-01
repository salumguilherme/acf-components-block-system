<?php
	/**
	 * Flexible layout row item: Columned Content
	 *
	 * Override at {theme}/acbs/rows/columned_content/item.php
	 *
	 * One column: an optional SVG icon, a wysiwyg body, and optionally its own buttons.
	 * Included once per repeater row from the loop in rows/columned_content.php, so the
	 * item's fields are in scope through get_sub_field() and there is no item object to
	 * unpack.
	 *
	 * `column_alignment` is PER ITEM, separate from the row-level grid alignment, so one
	 * column can centre while its neighbour stays left. `default` DEFERS: the class is
	 * still emitted so a theme has something to hang a rule on, but neither structure.scss
	 * nor this layout's sheet gives it one, which is what lets the value inherit from the
	 * row's own fl-loop-grid-columns-align-* and from the theme otherwise.
	 *
	 * .fl-card is emitted whether or not cards are on. layout_display is a SECTION field -
	 * it puts fl-card-box on the <section> - and the styling hangs off `.fl-card-box
	 * .fl-card`, so the switch is the section's business. Reading layout_display here
	 * cannot work: get_sub_field() looks in the ACTIVE loop, which inside this partial is
	 * the columns repeater, and the columns repeater has no such sub field. It returned
	 * false for every column and the card class was never emitted - silently, because a
	 * missing name and an empty value are the same false.
	 *
	 * THE ICON USED TO RETURN AN ID, NOT AN ARRAY. This field is declared
	 * `return_format => array` like its counterpart on icon_list, but it was declared `''`
	 * until recently, and ACF's acf_field_image::format_value() returns the bare integer
	 * for anything that is neither 'url' nor 'array'. Both shapes are accepted below so an
	 * override or a site field group can declare either without this template quietly
	 * rendering nothing.
	 *
	 * 'full' is the right size for an SVG: there are no intermediate files, and asking for
	 * one returns the full image with a misleading srcset attached.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	$acbs_icon    = get_sub_field( 'icon' );
	$acbs_content = (string) get_sub_field( 'content' );
	$acbs_align   = (string) get_sub_field( 'column_alignment' );
	$acbs_align   = '' !== $acbs_align ? $acbs_align : 'default';

	$acbs_icon_id = 0;

	if ( is_array( $acbs_icon ) && ! empty( $acbs_icon['ID'] ) ) {
		$acbs_icon_id = (int) $acbs_icon['ID'];
	} elseif ( is_numeric( $acbs_icon ) ) {
		$acbs_icon_id = (int) $acbs_icon;
	}

?><li class="fl-column fl-card fl-align-<?php echo esc_attr( $acbs_align ); ?>">
	<?php if ( 0 !== $acbs_icon_id ) : ?>
		<span class="fl-column-media">
			<?php echo wp_get_attachment_image( $acbs_icon_id, 'full', false, [ 'class' => 'fl-column-icon' ] ); ?>
		</span>
	<?php endif; ?>

	<?php if ( '' !== $acbs_content ) : ?>
		<div class="fl-column-content"><?php echo wp_kses_post( apply_filters( 'the_content', $acbs_content ) ); ?></div>
	<?php endif; ?>

	<?php acbs_row_part( 'buttons', $row ); ?>
</li>
