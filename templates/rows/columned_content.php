<?php
	/**
	 * Flexible layout row: Columned Content
	 *
	 * Stub. Override at {theme}/acbs/rows/columned_content.php
	 *
	 * Prints the layout's label and nothing else. The wrapper is already printing the
	 * <section>, the container and the row classes around this, so a stub only has to
	 * prove which file the cascade reached.
	 *
	 * The label is read off $row rather than hardcoded, which is what makes all fifteen
	 * stubs genuinely identical: any difference between two of them is a bug, and a stub
	 * that still prints the right title after the registry changes is proving the registry
	 * rather than the file.
	 *
	 * @var ACBS\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

?><ul class="fl-grid fl-columned-content">
	<?php
		while ( have_rows( 'columns' ) ) {
			the_row();
			acbs_row_partial( 'item', $row );
		}
	?>
</ul>
