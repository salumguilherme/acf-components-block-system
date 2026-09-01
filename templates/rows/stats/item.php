<?php
	/**
	 * Flexible layout row item: Stats
	 *
	 * Override at {theme}/acbs/rows/stats/item.php
	 *
	 * One figure. `column_alignment` is a PER-ITEM field here, separate from the row-level
	 * grid alignment, so one stat can centre while its neighbour stays left.
	 *
	 * The stat and its subtitle are wrapped together so they can be treated as one block
	 * typographically without the markup guessing at heading levels: the figure is not a
	 * heading, it is a value.
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
		<p class="fl-stat-figure">
			<span class="fl-stat-value"><?php echo esc_html( $acbs_stat ); ?></span>
			<?php if ( '' !== $acbs_subtitle ) : ?>
				<span class="fl-stat-subtitle"><?php echo esc_html( $acbs_subtitle ); ?></span>
			<?php endif; ?>
		</p>
	<?php endif; ?>
	<?php if ( '' !== $acbs_content ) : ?>
		<div class="fl-stat-content"><?php echo wp_kses_post( apply_filters( 'the_content', $acbs_content ) ); ?></div>
	<?php endif; ?>
	<?php acbs_row_part( 'buttons', $row ); ?>
</li>
