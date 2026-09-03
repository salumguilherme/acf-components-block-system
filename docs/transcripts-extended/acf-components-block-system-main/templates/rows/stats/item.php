<?php
	/**
	 * Flexible layout row item: Stats
	 *
	 * Override at {theme}/acbs/rows/stats/item.php
	 *
	 * One figure: the stat, a subtitle, a body, and optionally its own buttons.
	 * `column_alignment` is a PER-ITEM field here, separate from the row-level grid
	 * alignment, so one stat can centre while its neighbour stays left.
	 *
	 * The stat is an <h4>. It was a <p> carrying two spans, on the reasoning that a figure
	 * is a value rather than a heading - defensible, but it left the item with no heading at
	 * all, so a screen reader moving by headings skipped the whole row. <h4> also gives the
	 * theme's own type scale something to hang on, which is the arrangement everywhere else
	 * in this plugin: the theme owns the family and weight, the row sheet sets only the size.
	 *
	 * The subtitle is a sibling of the stat rather than a child of it. It used to be nested
	 * inside the stat's own `if`, so an item with a subtitle but no stat rendered neither -
	 * silently, since both fields are optional and neither is required by ACF.
	 *
	 * Subtitle and content are wrapped in .fl-stat-body because the design groups them: 14px
	 * between the stat and the pair, 12px between the two of them. Two gaps need two flex
	 * containers.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	$acbs_stat     = (string) get_sub_field( 'stat' );
	$acbs_subtitle = (string) get_sub_field( 'subtitle' );
	$acbs_content  = (string) get_sub_field( 'content' );
	$acbs_align    = (string) get_sub_field( 'column_alignment' );
	$acbs_align    = '' !== $acbs_align ? $acbs_align : 'default';

?><li class="fl-stat fl-card fl-align-<?php echo esc_attr( $acbs_align ); ?>">
	<?php if ( '' !== $acbs_stat ) : ?>
		<h4 class="fl-stat-value"><?php echo esc_html( $acbs_stat ); ?></h4>
	<?php endif; ?>

	<?php if ( '' !== $acbs_subtitle || '' !== $acbs_content ) : ?>
		<div class="fl-stat-body">
			<?php if ( '' !== $acbs_subtitle ) : ?>
				<h5 class="fl-stat-subtitle"><?php echo esc_html( $acbs_subtitle ); ?></h5>
			<?php endif; ?>
			<?php if ( '' !== $acbs_content ) : ?>
				<div class="fl-stat-content"><?php echo wp_kses_post( apply_filters( 'the_content', $acbs_content ) ); ?></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php acbs_row_part( 'buttons', $row ); ?>
</li>
