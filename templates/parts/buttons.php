<?php
	/**
	 * Buttons.
	 *
	 * Shared by every layout that clones the Buttons component, and usable inside a
	 * repeater item as well as at row level - it loops whatever `buttons` resolves to in
	 * the current ACF scope. Override at {theme}/acbs/parts/buttons.php.
	 *
	 * The Buttons clone is seamless with prefix_name off, so the repeater keeps the plain
	 * name `buttons` wherever it is cloned in.
	 *
	 * Style and outline are two separate fields now: button_style names the colour
	 * (primary / secondary / tertiary / white) and button_outline flips it to the outline
	 * variant. They map straight onto the Bootstrap classes compiled from the brand
	 * palette in _tokens.scss - .btn-primary and .btn-outline-primary both exist because
	 * $theme-colors carries those keys.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	// Nothing at all rather than an empty flex container, which would still add its top
	// margin and read as a gap under the content.
	if ( ! have_rows( 'buttons' ) ) {
		return;
	}

?><div class="fl-buttons">
	<?php
		while ( have_rows( 'buttons' ) ) {

			the_row();

			$acbs_text = (string) get_sub_field( 'button_text' );
			$acbs_link = (string) get_sub_field( 'button_link' );

			// A button with no label is not a button. A button with no link is a dead
			// control, so it is skipped too rather than rendered as an <a> with no href.
			if ( '' === $acbs_text || '' === $acbs_link ) {
				continue;
			}

			$acbs_style   = (string) get_sub_field( 'button_style' );
			$acbs_style   = '' !== $acbs_style ? $acbs_style : 'primary';
			$acbs_outline = (bool) get_sub_field( 'button_outline' );

			$acbs_class = 'btn btn-' . ( $acbs_outline ? 'outline-' : '' ) . $acbs_style;

			printf(
				'<a class="%1$s" href="%2$s">%3$s</a>',
				esc_attr( $acbs_class ),
				esc_url( $acbs_link ),
				esc_html( $acbs_text )
			);

		}
	?>
</div>
