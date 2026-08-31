<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Rows;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Interface Row_Type
	 *
	 * One layout, answering for itself: its fields, its template, its assets and its
	 * wrapper contributions.
	 *
	 * This is a PUBLIC API. Client add-ons register row types through Row_Registry - that
	 * is how the WooCommerce layouts come back if a client needs them - so this contract
	 * and Row have to stay stable. Implement Row_Type_Base rather than this interface
	 * directly unless there is a reason not to.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Rows
	 */
	interface Row_Type {

		/**
		 * The ACF layout name, e.g. 'image_cards_grid'.
		 *
		 * @return string
		 */
		public function name();

		/**
		 * The human label, e.g. 'Image Cards - Grid'.
		 *
		 * @return string
		 */
		public function label();

		/**
		 * The layout's ACF sub fields.
		 *
		 * @return array
		 */
		public function fields();

		/**
		 * Template path relative to templates/rows/, without the extension - normally
		 * just the layout name.
		 *
		 * @return string
		 */
		public function template();

		/**
		 * Style handles this row needs. Declared, not enqueued: Assets enqueues them in
		 * the footer for the rows that actually rendered.
		 *
		 * @return array
		 */
		public function styles();

		/**
		 * Script handles this row needs, same contract as styles().
		 *
		 * @return array
		 */
		public function scripts();

		/**
		 * Extra wrapper classes for one row. Called with the row's ACF loop active, so
		 * get_sub_field() works.
		 *
		 * @param Row $row
		 *
		 * @return array
		 */
		public function wrapper_classes(Row $row);

		/**
		 * Features this layout has: 'intro', 'buttons', 'items'.
		 *
		 * @return array
		 */
		public function supports();

	}
