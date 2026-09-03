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
	 * ICONS ARE INLINE <svg>, NOT <img>, so their `fill="currentColor"` paths inherit the
	 * button's own text colour through every state and variant. Button_Icons does the
	 * reading and the sanitising; this template only decides which side of the label the
	 * markup goes on. See that class for why an <img> cannot work here.
	 *
	 * The icon is wrapped in a fixed-size box rather than being sized directly, so icons
	 * drawn at different proportions all occupy the same footprint and a row of buttons
	 * keeps one baseline. `fl-has-icon` on the <a> is what switches the button to flex -
	 * without it every button on the site would change layout for a feature most of them
	 * do not use.
	 *
	 * The markup is echoed rather than passed through esc_html(): it has already been
	 * through Button_Icons, which prepares the plugin's own files and runs wp_kses()
	 * against an SVG allowlist over anything an editor uploaded. Escaping it here would
	 * print the tags to the page.
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

			$acbs_icon = ACBS\Modules\FlexibleLayoutTemplate\Fields\Button_Icons::render(
				get_sub_field( 'button_icon' ),
				get_sub_field( 'button_icon_svg' )
			);

			// Position only matters when there is something to position. It also defaults
			// to `after` rather than to whatever get_sub_field() returned, because a row
			// saved before this field existed returns false for it, and false is not
			// 'before'.
			$acbs_icon_position = (string) get_sub_field( 'button_icon_position' );
			$acbs_icon_position = 'before' === $acbs_icon_position ? 'before' : 'after';

			$acbs_class = 'btn btn-' . ( $acbs_outline ? 'outline-' : '' ) . $acbs_style;

			if ( '' !== $acbs_icon ) {
				$acbs_class .= ' fl-has-icon fl-icon-' . $acbs_icon_position;
				$acbs_icon   = '<span class="fl-button-icon-box">' . $acbs_icon . '</span>';
			}

			$acbs_label = '<span class="fl-button-text">' . esc_html( $acbs_text ) . '</span>';

			printf(
				'<a class="%1$s" href="%2$s">%3$s</a>',
				esc_attr( $acbs_class ),
				esc_url( $acbs_link ),
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- label is escaped above; icon is sanitised in Button_Icons.
				'before' === $acbs_icon_position ? $acbs_icon . $acbs_label : $acbs_label . $acbs_icon
			);

		}
	?>
</div>
