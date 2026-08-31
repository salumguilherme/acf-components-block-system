<?php
	/**
	 * Section intro.
	 *
	 * Shared by every layout Common_Fields injects Intro into, which is all of them
	 * except the four listed in Common_Fields::default_disabled_layouts(). Override at
	 * {theme}/acbs/parts/intro.php.
	 *
	 * Prints nothing at all when both fields are empty - not an empty <header>, which
	 * would carry the row's spacing and read as a gap the editor cannot get rid of.
	 *
	 * section_content is a wysiwyg, so it goes through the_content filters for
	 * wpautop, shortcodes and embeds, exactly as post content does, and is then
	 * restricted to post-safe HTML rather than trusted outright.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	$acbs_title   = get_sub_field( 'section_title' );
	$acbs_content = get_sub_field( 'section_content' );

	if ( empty( $acbs_title ) && empty( $acbs_content ) ) {
		return;
	}

?><header class="fl-intro">
	<?php if ( ! empty( $acbs_title ) ) : ?>
		<h2 class="fl-intro-title"><?php echo esc_html( $acbs_title ); ?></h2>
	<?php endif; ?>
	<?php if ( ! empty( $acbs_content ) ) : ?>
		<div class="fl-intro-content"><?php echo wp_kses_post( apply_filters( 'the_content', $acbs_content ) ); ?></div>
	<?php endif; ?>
</header>
