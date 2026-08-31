<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Rows;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Row
	 *
	 * One flexible content row, as a value object handed to a template.
	 *
	 * This is deliberately thin. It carries the things a template cannot get from ACF
	 * (which layout this is, where it sits, what it is being rendered from) and nothing
	 * else: the row's own field VALUES are still read through get_sub_field(), because
	 * ACF's own accessors are the only thing that formats image arrays, galleries, link
	 * arrays and the Buttons/Intro field types, and because the row's ACF loop is still
	 * active while the template renders. See CLAUDE.md section 05.
	 *
	 * Note what is NOT here: an id. get_row_index() is a position, not an identity - it
	 * is 1-based, ACF runs array_values() over the row set, and flexible content's
	 * load_value() drops editor-disabled rows, so disabling one row renumbers every row
	 * after it. Nothing here pretends otherwise; use position() for display and never as
	 * an anchor target.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Rows
	 */
	class Row {

		/**
		 * @var string
		 */
		private $layout;

		/**
		 * @var int
		 */
		private $position;

		/**
		 * @var int|string
		 */
		private $source;

		/**
		 * @var string
		 */
		private $field;

		/**
		 * @var Row_Type
		 */
		private $type;

		/**
		 * Memoised wrapper attributes. Both run filters, so they are computed once per
		 * row rather than once per read - a template is free to ask twice.
		 *
		 * @var array|null
		 */
		private $wrapper_classes = null;

		/**
		 * @var string|null
		 */
		private $wrapper_id = null;

		/**
		 * Constructor
		 *
		 * @param string     $layout   ACF layout name, e.g. 'full_width_content'.
		 * @param int        $position 1-based position within the field, for display only.
		 * @param int|string $source   The normalised ACF post id being rendered from.
		 * @param string     $field    The flexible content field name.
		 * @param Row_Type   $type     The registered row type for this layout.
		 */
		public function __construct($layout, $position, $source, $field, Row_Type $type) {

			$this->layout = (string) $layout;
			$this->position = (int) $position;
			$this->source = $source;
			$this->field = (string) $field;
			$this->type = $type;

		}

		/**
		 * layout function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function layout() {
			return $this->layout;
		}

		/**
		 * label function
		 *
		 * The layout's human label, from the registry rather than the template, which is
		 * what makes every stub template genuinely identical.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function label() {
			return $this->type->label();
		}

		/**
		 * position function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return int
		 */
		public function position() {
			return $this->position;
		}

		/**
		 * source function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return int|string
		 */
		public function source() {
			return $this->source;
		}

		/**
		 * field function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function field() {
			return $this->field;
		}

		/**
		 * type function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return Row_Type
		 */
		public function type() {
			return $this->type;
		}

		/**
		 * wrapper_classes function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		public function wrapper_classes() {

			if(is_null($this->wrapper_classes)) {
				$this->wrapper_classes = Wrapper::classes($this);
			}

			return $this->wrapper_classes;

		}

		/**
		 * wrapper_class function
		 *
		 * The classes as one attribute-ready string.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function wrapper_class() {
			return implode(' ', $this->wrapper_classes());
		}

		/**
		 * wrapper_id function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function wrapper_id() {

			if(is_null($this->wrapper_id)) {
				$this->wrapper_id = Wrapper::id($this);
			}

			return $this->wrapper_id;

		}

		/**
		 * content function
		 *
		 * The row's own output: the five action points, whose default content handler
		 * includes the row's template. This is what a wrapper template echoes.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function content() {
			return Wrapper::content($this);
		}

		/**
		 * supports function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $feature 'intro', 'buttons', 'items'
		 *
		 * @return bool
		 */
		public function supports($feature) {
			return in_array($feature, $this->type->supports(), true);
		}

	}
