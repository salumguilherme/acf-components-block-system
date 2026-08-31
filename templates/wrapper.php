<?php
	/**
	 * Flexible layout row wrapper.
	 *
	 * Wraps every row. Override at {theme}/acbs/wrapper.php.
	 *
	 * A theme template REPLACES this file rather than layering on top of it - there is no
	 * sane way to layer two PHP files. Row STYLING is the other way round: the plugin's
	 * sheet always loads and the theme's is enqueued after it, as a dependency. That
	 * difference is deliberate.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	$acbs_wrapper_id = $row->wrapper_id();

?><section class="<?php echo esc_attr( $row->wrapper_class() ); ?>"<?php echo '' !== $acbs_wrapper_id ? ' id="' . esc_attr( $acbs_wrapper_id ) . '"' : ''; ?>>
	<div class="fl-container container">
		<?php echo $row->content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- row templates escape their own output. ?>
	</div>
</section>
