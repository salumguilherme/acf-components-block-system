<?php
	/**
	 * Flexible layout row item: Icon Leaders
	 *
	 * Override at {theme}/acbs/rows/icon_leaders/item.php
	 *
	 * One icon-led point. Included once per repeater row by acbs_row_partial(), from
	 * inside the loop in rows/icon_leaders.php - so the row's own fields are in scope
	 * through get_sub_field() and there is no item object to unpack.
	 *
	 * The icon field accepts SVG only (`mime_types => svg`), and is declared
	 * `return_format => array`, so it arrives as an attachment array rather than a URL.
	 * It is rendered at 'full' deliberately: an SVG has no intermediate sizes, and asking
	 * for one returns the full file with a misleading srcset attached.
	 *
	 * The link is optional by design - the field says "Leave blank to not link" - so the
	 * item is a plain <div> when there is no URL rather than an <a> with an empty href,
	 * which is a real accessibility defect and not a cosmetic one.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	$acbs_icon    = get_sub_field( 'icon' );
	$acbs_title   = (string) get_sub_field( 'title' );
	$acbs_content = (string) get_sub_field( 'content' );
	$acbs_link    = (string) get_sub_field( 'link_url' );

?><li class="fl-leader">
	<?php if ( '' !== $acbs_link ) : ?>
		<a class="fl-leader-inner" href="<?php echo esc_url( $acbs_link ); ?>">
	<?php else : ?>
		<div class="fl-leader-inner">
	<?php endif; ?>

		<?php if ( ! empty( $acbs_icon['ID'] ) ) : ?>
			<span class="fl-leader-media">
				<?php echo wp_get_attachment_image( $acbs_icon['ID'], 'full', false, [ 'class' => 'fl-leader-icon' ] ); ?>
			</span>
		<?php endif; ?>

		<?php if ( '' !== $acbs_title ) : ?>
			<h3 class="fl-leader-title"><?php echo esc_html( $acbs_title ); ?></h3>
		<?php endif; ?>

		<?php if ( '' !== $acbs_content ) : ?>
			<?php // A textarea, so wpautop supplies the paragraphs the editor did not type. ?>
			<div class="fl-leader-content"><?php echo wp_kses_post( wpautop( $acbs_content ) ); ?></div>
		<?php endif; ?>

	<?php echo '' !== $acbs_link ? '</a>' : '</div>'; ?>
</li>
