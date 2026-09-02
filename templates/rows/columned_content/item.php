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
	 * THE ACCORDION. With `column_accordion` ticked the column becomes a disclosure: the
	 * icon and `column_accordion_title` form the button, and the content and buttons move
	 * into the panel it opens. Everything else about the column is unchanged, including
	 * `.fl-card` - the design draws these as cards but the accordion does not depend on
	 * that, and a row with Content Box off gets the same toggle without the box.
	 *
	 * It renders open or closed from `column_accordion_initial_status` rather than being
	 * corrected by JS on load: a panel that starts open and is closed by a script flashes,
	 * and one that starts closed and is opened by a script arrives late.
	 *
	 * A TITLE IS REQUIRED FOR THE ACCORDION TO RENDER, even though the field is not marked
	 * required. A disclosure button with no label has no accessible name, which is worse
	 * than no disclosure at all - so a column with the box ticked and no title falls back
	 * to rendering normally rather than producing an unusable control.
	 *
	 * The id has to be unique per column on the page and is a per-request counter.
	 * get_row_index() is NOT used: it is a position, not an identity, and renumbers when an
	 * editor disables a row - see CLAUDE.md 05.4.
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

	$acbs_title     = trim( (string) get_sub_field( 'column_accordion_title' ) );
	$acbs_accordion = (bool) get_sub_field( 'column_accordion' ) && '' !== $acbs_title;
	$acbs_open      = 'open' === (string) get_sub_field( 'column_accordion_initial_status' );

	// acbs_unique_id() rather than a `static` counter in this file. A static declared in
	// an included template is re-initialised on every include, so the counter never got
	// past 1 and every column produced the same id - four panels sharing one id, and every
	// trigger's aria-controls pointing at the first of them. See the function's docblock.
	$acbs_panel = acbs_unique_id( 'fl-column-accordion' );
	$acbs_label = $acbs_panel . '-label';

	$acbs_classes = 'fl-column fl-card fl-align-' . $acbs_align . ( $acbs_accordion ? ' fl-column-has-accordion' : '' );

?><li class="<?php echo esc_attr( $acbs_classes ); ?>">

	<?php if ( $acbs_accordion ) : ?>

		<h4 class="fl-column-accordion-heading">
			<button class="fl-column-accordion-trigger" type="button" id="<?php echo esc_attr( $acbs_label ); ?>" aria-expanded="<?php echo $acbs_open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $acbs_panel ); ?>">
				<span class="fl-column-accordion-lead">
					<?php if ( 0 !== $acbs_icon_id ) : ?>
						<span class="fl-column-media">
							<?php echo wp_get_attachment_image( $acbs_icon_id, 'full', false, [ 'class' => 'fl-column-icon' ] ); ?>
						</span>
					<?php endif; ?>
					<span class="fl-column-accordion-title"><?php echo esc_html( $acbs_title ); ?></span>
				</span>
				<span class="fl-column-accordion-icon" aria-hidden="true"></span>
			</button>
		</h4>

		<div class="fl-column-accordion-panel" id="<?php echo esc_attr( $acbs_panel ); ?>" role="region" aria-labelledby="<?php echo esc_attr( $acbs_label ); ?>"<?php echo $acbs_open ? '' : ' hidden'; ?>>
			<div class="fl-column-accordion-body">
				<?php if ( '' !== $acbs_content ) : ?>
					<div class="fl-column-content"><?php echo wp_kses_post( apply_filters( 'the_content', $acbs_content ) ); ?></div>
				<?php endif; ?>
				<?php acbs_row_part( 'buttons', $row ); ?>
			</div>
		</div>

	<?php else : ?>

		<?php if ( 0 !== $acbs_icon_id ) : ?>
			<span class="fl-column-media">
				<?php echo wp_get_attachment_image( $acbs_icon_id, 'full', false, [ 'class' => 'fl-column-icon' ] ); ?>
			</span>
		<?php endif; ?>

		<?php if ( '' !== $acbs_content ) : ?>
			<div class="fl-column-content"><?php echo wp_kses_post( apply_filters( 'the_content', $acbs_content ) ); ?></div>
		<?php endif; ?>

		<?php acbs_row_part( 'buttons', $row ); ?>

	<?php endif; ?>

</li>
