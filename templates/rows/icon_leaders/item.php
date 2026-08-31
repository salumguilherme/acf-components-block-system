<?php
	/**
	 * Flexible layout row item: Icon Leaders
	 *
	 * Override at {theme}/acbs/rows/icon_leaders/item.php
	 *
	 * One icon-led point. Rendered once per item by Items_Source::render() whatever the
	 * row's `source` selects; $item has already reconciled a repeater row, a term and a
	 * post into the same four accessors.
	 *
	 * The icon field is restricted to SVG uploads (`mime_types => svg` in Page_Content),
	 * which is why it is asked for at 'full' - an SVG has no intermediate sizes, and
	 * requesting one gets the full file back anyway with a misleading srcset attached.
	 *
	 * The content field is a textarea here but a description or an excerpt when the source
	 * is a term or a post, so it goes through wpautop for line breaks and then wp_kses_post
	 * rather than being branched on: plain text survives both unchanged.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row  $row
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Item $item
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	$acbs_link    = $item->link();
	$acbs_title   = $item->title();
	$acbs_content = $item->content();
	$acbs_icon    = $item->image_html( 'full', [ 'class' => 'fl-leader-icon' ] );
	$acbs_target  = $item->link_target();

?><li class="fl-leader">
	<?php if ( '' !== $acbs_link ) : ?>
		<a class="fl-leader-inner" href="<?php echo esc_url( $acbs_link ); ?>"<?php echo '' !== $acbs_target ? ' target="' . esc_attr( $acbs_target ) . '" rel="noopener"' : ''; ?>>
	<?php else : ?>
		<div class="fl-leader-inner">
	<?php endif; ?>

		<?php if ( '' !== $acbs_icon ) : ?>
			<span class="fl-leader-media"><?php echo $acbs_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped by Item::image_html(). ?></span>
		<?php endif; ?>

		<?php if ( '' !== $acbs_title ) : ?>
			<h3 class="fl-leader-title"><?php echo esc_html( $acbs_title ); ?></h3>
		<?php endif; ?>

		<?php if ( '' !== $acbs_content ) : ?>
			<div class="fl-leader-content"><?php echo wp_kses_post( wpautop( $acbs_content ) ); ?></div>
		<?php endif; ?>

	<?php echo '' !== $acbs_link ? '</a>' : '</div>'; ?>
</li>
