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

	$acbs_wrapper_id    = $row->wrapper_id();
	$acbs_wrapper_style = $row->wrapper_style();

?>
<?php do_action( 'acbs/wrapper/before_section', $row); ?>
<section class="<?php echo esc_attr( $row->wrapper_class() ); ?>"<?php
	echo '' !== $acbs_wrapper_id ? ' id="' . esc_attr( $acbs_wrapper_id ) . '"' : '';
	// Already run through safecss_filter_attr() / custom-property validation in
	// Wrapper::style(); esc_attr here is the second pass, not the only one.
	echo '' !== $acbs_wrapper_style ? ' style="' . esc_attr( $acbs_wrapper_style ) . '"' : '';
?>>
	<?php do_action( 'acbs/wrapper/before_container', $row); ?>
	<div class="fl-container container">
		<?php echo $row->content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- row templates escape their own output. ?>
	</div>
	<?php do_action( 'acbs/wrapper/after_container', $row); ?>
</section>
<?php do_action( 'acbs/wrapper/after_section', $row); ?>
