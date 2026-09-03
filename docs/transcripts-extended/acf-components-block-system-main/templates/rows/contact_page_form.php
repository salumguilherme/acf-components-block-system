<?php
	/**
	 * Flexible layout row: Enquiry Form
	 *
	 * Override at {theme}/acbs/rows/contact_page_form.php
	 *
	 * Content plus an embedded form. `contact_form_id` is a free text field holding
	 * whatever the site's form plugin wants - a Gravity Forms shortcode, an embed, an id -
	 * so it goes through do_shortcode().
	 *
	 * That is WordPress's own shortcode expansion, not a shortcode the plugin registers:
	 * ACBS declares none. It is here because a form plugin's shortcode is the only way to
	 * get its form onto the page, and an editor pasting one into this field is the
	 * documented use of it.
	 *
	 * Not a repeater layout, so a `card` display wraps this whole block, intro excluded.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	acbs_row_part( 'intro', $row );

	$acbs_content = (string) get_sub_field( 'written_content' );
	$acbs_form    = trim( (string) get_sub_field( 'contact_form_id' ) );

?><div class="fl-enquiry fl-card">
	<?php if ( '' !== $acbs_content ) : ?>
		<div class="fl-enquiry-content"><?php echo wp_kses_post( apply_filters( 'the_content', $acbs_content ) ); ?></div>
	<?php endif; ?>
	<?php if ( '' !== $acbs_form ) : ?>
		<?php // Not escaped: a form plugin returns its own markup, and escaping it would print the form as text. ?>
		<div class="fl-enquiry-form"><?php echo do_shortcode( $acbs_form ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<?php endif; ?>
</div>
