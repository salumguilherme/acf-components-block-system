<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Rows;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Row_Type_Base
	 *
	 * Sensible defaults for every Row_Type, so an implementation only overrides what is
	 * actually different about its layout.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Rows
	 */
	abstract class Row_Type_Base implements Row_Type {

		/**
		 * Style handle prefix. The matching sheet is only enqueued if it exists - see
		 * Assets - so a layout with no styling of its own costs nothing.
		 */
		const STYLE_PREFIX = 'acbs-row-';

		/**
		 * label function
		 *
		 * Falls back to the layout name title-cased, so a row type that forgets a label
		 * still renders something recognisable rather than an empty heading.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function label() {
			return ucwords(str_replace('_', ' ', $this->name()));
		}

		/**
		 * fields function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		public function fields() {
			return [];
		}

		/**
		 * template function
		 *
		 * The layout name verbatim. ACF layout names are snake_case, so the template is
		 * full_width_content.php - no kebab-case conversion, deliberately: a
		 * transformation in the filename is one someone has to reverse mentally every
		 * time they go looking for the file.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function template() {
			return $this->name();
		}

		/**
		 * styles function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		public function styles() {
			return [self::STYLE_PREFIX.$this->name()];
		}

		/**
		 * scripts function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		public function scripts() {
			return [];
		}

		/**
		 * wrapper_classes function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row $row
		 *
		 * @return array
		 */
		public function wrapper_classes(Row $row) {
			return [];
		}

		/**
		 * supports function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		public function supports() {
			return [];
		}

	}
