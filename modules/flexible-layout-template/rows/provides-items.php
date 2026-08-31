<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Rows;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Interface Provides_Items
	 *
	 * An OPTIONAL companion to Row_Type, for a layout whose rows iterate items.
	 *
	 * Separate from Row_Type on purpose. Row_Type is a public API that client add-ons
	 * already implement, so adding a method to it would break every one of them at once;
	 * an interface a row type opts into is additive, and Items_Source only ever asks
	 * `instanceof`.
	 *
	 * A row type does not need this to have items. The three core layouts that iterate -
	 * icon_leaders, image_cards_grid, image_cards_multi_grid - are plain Layout_Row_Type
	 * instances and get their specs from Items_Source::DEFAULTS, because three subclasses
	 * existing only to return a config array would be three more files to keep in step
	 * with page-content.php. This is for a client add-on registering a row type of its
	 * own, which has nowhere else to put the answer.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Rows
	 */
	interface Provides_Items {

		/**
		 * The layout's item spec. See Items_Source::spec() for the shape and
		 * Items_Source::DEFAULTS for worked examples.
		 *
		 * @return array
		 */
		public function items_spec();

	}
