<?php
	/**
	 * Flexible layout row item: Image Cards Simple Grid
	 *
	 * Override at {theme}/acbs/rows/image_cards_grid/item.php
	 *
	 * One card. Rendered once per item by Items_Source::render(), whatever the row's
	 * `source` field selects - a repeater row, a term, or a post. Nothing below branches
	 * on which, because $item already did: see the Item class for how a term's name, a
	 * post's title and a repeater row's title field all answer title().
	 *
	 * Where the source is the repeater, this file runs with that row's own ACF loop
	 * active, so get_sub_field() reads the card's fields directly if you need one the
	 * Item accessors do not cover.
	 *
	 * The link is optional by design - all three layouts label the field "Leave blank to
	 * not link" - so the card is a plain <div> when there is no URL rather than an <a>
	 * with an empty href, which is a real accessibility defect and not a cosmetic one.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row  $row
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Item $item
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	$acbs_link   = $item->link();
	$acbs_title  = $item->title();
	$acbs_image  = $item->image_html( 'medium_large', [ 'class' => 'fl-card-image' ] );
	$acbs_target = $item->link_target();

?><li class="fl-card">
	<?php if ( '' !== $acbs_link ) : ?>
		<a class="fl-card-inner" href="<?php echo esc_url( $acbs_link ); ?>"<?php echo '' !== $acbs_target ? ' target="' . esc_attr( $acbs_target ) . '" rel="noopener"' : ''; ?>>
	<?php else : ?>
		<div class="fl-card-inner">
	<?php endif; ?>

		<?php if ( '' !== $acbs_image ) : ?>
			<figure class="fl-card-media"><?php echo $acbs_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped by Item::image_html(). ?></figure>
		<?php endif; ?>

		<?php if ( '' !== $acbs_title ) : ?>
			<h3 class="fl-card-title"><?php echo esc_html( $acbs_title ); ?></h3>
		<?php endif; ?>

	<?php echo '' !== $acbs_link ? '</a>' : '</div>'; ?>
</li>
