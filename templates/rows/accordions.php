<?php
	/**
	 * Flexible layout row: Accordions
	 *
	 * Override at {theme}/acbs/rows/accordions.php
	 *
	 * Uses Bootstrap's accordion markup and its collapse JS, both of which the plugin's
	 * scoped Bootstrap already carries. No card here: this layout declares no
	 * layout_display field, so it has no Grid & Display tab at all.
	 *
	 * The id has to be unique per row on the page, because Bootstrap's data-bs-parent
	 * targets it to close siblings. get_row_index() is NOT used: it is a position, not an
	 * identity, and renumbers when an editor disables a row - see CLAUDE.md 05.4. The
	 * wrapper id is stable when an editor has set one, so it is preferred, and the
	 * fallback is a per-request counter that only has to be unique within the page.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	acbs_row_part( 'intro', $row );

	if ( ! have_rows( 'accordions' ) ) {
		return;
	}

	static $acbs_accordion_n = 0;
	$acbs_accordion_n++;

	$acbs_id = $row->wrapper_id();
	$acbs_id = '' !== $acbs_id ? $acbs_id . '-accordion' : 'fl-accordion-' . $acbs_accordion_n;
	$acbs_i  = 0;

?><div class="fl-accordions accordion" id="<?php echo esc_attr( $acbs_id ); ?>">
	<?php while ( have_rows( 'accordions' ) ) : the_row(); $acbs_i++; ?>
		<?php
			$acbs_title   = (string) get_sub_field( 'title' );
			$acbs_content = (string) get_sub_field( 'content' );
			$acbs_item    = $acbs_id . '-' . $acbs_i;
		?>
		<div class="accordion-item">
			<h3 class="accordion-header">
				<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr( $acbs_item ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $acbs_item ); ?>">
					<?php echo esc_html( $acbs_title ); ?>
				</button>
			</h3>
			<div id="<?php echo esc_attr( $acbs_item ); ?>" class="accordion-collapse collapse" data-bs-parent="#<?php echo esc_attr( $acbs_id ); ?>">
				<div class="accordion-body"><?php echo wp_kses_post( apply_filters( 'the_content', $acbs_content ) ); ?></div>
			</div>
		</div>
	<?php endwhile; ?>
</div>
