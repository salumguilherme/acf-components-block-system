<?php
	/**
	 * Flexible layout row: Accordions
	 *
	 * Override at {theme}/acbs/rows/accordions.php
	 *
	 * Reference: Figma "Untitled", node 32:890.
	 *
	 * NOT BOOTSTRAP'S ACCORDION any more. It used to emit `.accordion` / `data-bs-toggle`
	 * and lean on Bootstrap's collapse plugin, which worked on this site only because the
	 * child theme happens to load bootstrap.bundle.min.js. The plugin ships Bootstrap's
	 * CSS and none of its JS, so on any other theme the markup rendered and nothing ever
	 * opened - a row that is inert rather than merely unstyled. The row now carries its
	 * own script through the per-row JS pipeline and depends on nothing outside the
	 * plugin.
	 *
	 * THE CHEVRONS ARE CSS MASKS, not inline SVG. Both files are drawn in
	 * `assets/svg/`, and the sheet paints them with `background-color: currentColor`
	 * through a mask, the same technique the editor colour button uses for its toolbar
	 * icon. Three things fall out of that: the icon follows the title's colour (green when
	 * open, per the design) with no second rule; swapping down for up is one CSS
	 * declaration keyed on `aria-expanded`, so the JS never touches the icon; and the
	 * template does not have to reach into Button_Icons, whose ICONS list is deliberately
	 * both a choice list and a path allowlist for the BUTTON field and has no business
	 * growing entries that no button offers.
	 *
	 * PANELS RENDER COLLAPSED, so this row needs its script to be usable. That is not a
	 * regression - the Bootstrap version was equally JS-dependent - and the alternative,
	 * rendering open and collapsing on load, flashes every answer of an FAQ on every page
	 * load because row scripts are footer scripts.
	 *
	 * The id has to be unique per row on the page. get_row_index() is NOT used: it is a
	 * position, not an identity, and renumbers when an editor disables a row - see
	 * CLAUDE.md 05.4. The wrapper id is stable when an editor has set one, so it is
	 * preferred, and the fallback is a per-request counter.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.1
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	acbs_row_part( 'intro', $row );

	if ( ! have_rows( 'accordions' ) ) {
		return;
	}

	// Same fix as columned_content: a `static` in an included template does not survive
	// between includes, so a page with two accordions rows gave both the same base id.
	// Only visible with more than one such row, which is why it went unnoticed.
	$acbs_id = $row->wrapper_id();
	$acbs_id = '' !== $acbs_id ? $acbs_id . '-accordion' : acbs_unique_id( 'fl-accordion' );
	$acbs_i  = 0;

?><div class="fl-accordions" id="<?php echo esc_attr( $acbs_id ); ?>">
	<?php while ( have_rows( 'accordions' ) ) : the_row(); $acbs_i++; ?>
		<?php
			$acbs_title   = (string) get_sub_field( 'title' );
			$acbs_content = (string) get_sub_field( 'content' );
			$acbs_panel   = $acbs_id . '-' . $acbs_i;
			$acbs_label   = $acbs_panel . '-label';
		?>
		<div class="fl-accordion-item">
			<h3 class="fl-accordion-heading">
				<button class="fl-accordion-trigger" type="button" id="<?php echo esc_attr( $acbs_label ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $acbs_panel ); ?>">
					<span class="fl-accordion-title"><?php echo esc_html( $acbs_title ); ?></span>
					<span class="fl-accordion-icon" aria-hidden="true"></span>
				</button>
			</h3>
			<div class="fl-accordion-panel" id="<?php echo esc_attr( $acbs_panel ); ?>" role="region" aria-labelledby="<?php echo esc_attr( $acbs_label ); ?>" hidden>
				<div class="fl-accordion-body"><?php echo wp_kses_post( apply_filters( 'the_content', $acbs_content ) ); ?></div>
			</div>
		</div>
	<?php endwhile; ?>
</div>
