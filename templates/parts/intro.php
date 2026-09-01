<?php
	/**
	 * Section intro.
	 *
	 * Shared by every layout Common_Fields injects Intro into, which is all of them
	 * except the four listed in Common_Fields::default_disabled_layouts(). Override at
	 * {theme}/acbs/parts/intro.php.
	 *
	 * Prints nothing at all when the field is empty - not an empty <header>, which would
	 * carry the row's spacing and read as a gap the editor cannot get rid of.
	 *
	 * ONE FIELD, NOT TWO. There used to be a plain-text `section_title` rendered here as
	 * an <h2>, with section_content beneath it. The title field is gone and the heading
	 * now lives inside section_content itself. Two things get better for that: the editor
	 * chooses the heading level rather than every intro on the site being an h2, and a
	 * two-tone heading is marked up with the toolbar's brand colour button instead of
	 * hand-typing a <span class="text-primary"> into a text input.
	 *
	 * section_content is a wysiwyg, so it goes through the_content filters - wpautop for
	 * paragraphs, wptexturize, embeds - exactly as post content does, and is then
	 * restricted to post-safe HTML rather than trusted outright. That is WordPress's own
	 * content pipeline rather than anything the plugin registers; the plugin itself adds
	 * no shortcodes.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	$acbs_content = get_sub_field( 'section_content' );

	if ( empty( $acbs_content ) ) {
		return;
	}

?><header class="fl-intro">
	<div class="fl-intro-content"><?php echo wp_kses_post( apply_filters( 'the_content', $acbs_content ) ); ?></div>
</header>
