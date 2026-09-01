<?php
	/**
	 * Flexible layout row item: Testimonials
	 *
	 * Override at {theme}/acbs/rows/testimonials/item.php
	 *
	 * One quote. <blockquote> with <figcaption> for the attribution, which is the markup
	 * screen readers and search engines both understand - the author is not part of the
	 * quotation, so it sits outside <blockquote> rather than inside it.
	 *
	 * The role line is only rendered alongside an author. On its own it would be a
	 * job title attached to nobody.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	$acbs_quote  = (string) get_sub_field( 'testimonial_content' );
	$acbs_author = (string) get_sub_field( 'testimonial_author' );
	$acbs_role   = (string) get_sub_field( 'testimonial_author_role' );
	$acbs_align  = (string) get_sub_field( 'column_alignment' );
	$acbs_align  = '' !== $acbs_align ? $acbs_align : 'default';

?><li class="fl-testimonial fl-card fl-align-<?php echo esc_attr( $acbs_align ); ?>">
	<figure class="fl-testimonial-figure">
		<?php if ( '' !== $acbs_quote ) : ?>
			<blockquote class="fl-testimonial-quote"><?php echo wp_kses_post( apply_filters( 'the_content', $acbs_quote ) ); ?></blockquote>
		<?php endif; ?>
		<?php if ( '' !== $acbs_author ) : ?>
			<figcaption class="fl-testimonial-author">
				<span class="fl-testimonial-name"><?php echo esc_html( $acbs_author ); ?></span>
				<?php if ( '' !== $acbs_role ) : ?>
					<span class="fl-testimonial-role"><?php echo esc_html( $acbs_role ); ?></span>
				<?php endif; ?>
			</figcaption>
		<?php endif; ?>
	</figure>
</li>
