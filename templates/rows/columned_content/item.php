<?php
	/**
	 * Flexible layout row item: columns
	 *
	 * Override at {theme}/acbs/rows/columned_content/item.php
	 *
	 * One figure. `column_alignment` is a PER-ITEM field here, separate from the row-level
	 * grid alignment, so one stat can centre while its neighbour stays left.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	
	if ( ! defined( 'ABSPATH' ) ) { exit; }
	
	$acbs_icon     = (string) get_sub_field( 'stat' );
	$acbs_content  = (string) get_sub_field( 'content' );
	$acbs_align    = (string) get_sub_field( 'column_alignment' );
	$acbs_align    = '' !== $acbs_align ? $acbs_align : 'default';
	$acbs_card      = get_sub_field( 'layout_display' ) == 'card' ? 'fl-card' : '';

?><li class="fl-columned-content <?php echo $acbs_card; ?> fl-align-<?php echo esc_attr( $acbs_align ); ?>">
<?php if ( '' !== $acbs_content ) : ?>
	<div class="fl-columned-content"><?php echo wp_kses_post( apply_filters( 'the_content', $acbs_content ) ); ?></div>
<?php endif; ?>
<?php acbs_row_part( 'buttons', $row ); ?>
	</li>
<?php
