<?php

	namespace ERDC\Modules\FlexibleLayoutTemplate\Rows;

	use ERDC\Modules\FlexibleLayoutTemplate\Fields\Common_Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Layout_Row_Type
	 *
	 * A row type built from an existing `page_sections` layout definition array.
	 *
	 * This is how all 15 base layouts (and anything a site contributes through the
	 * "Flexible Layout" location rule) become row types without moving a single field
	 * definition out of Page_Content. That sequencing is deliberate: the field keys, the
	 * contributor-group merge, Common_Fields' tab injection and the conditional-logic
	 * rewriting are all load-bearing and currently correct, so fields() returns its slice
	 * of the existing array and the registry is responsible only for template, assets and
	 * wrapper. Moving field definitions into per-layout classes is a later, optional
	 * refactor with a real regression risk attached.
	 *
	 * A layout that needs behaviour of its own graduates to its own Row_Type class and
	 * registers over the top of this one.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ERDC\Modules\FlexibleLayoutTemplate\Rows
	 */
	class Layout_Row_Type extends Row_Type_Base {

		/**
		 * The layout definition, as it appears in Page_Content::get_base_layouts().
		 *
		 * @var array
		 */
		private $definition;

		/**
		 * Constructor
		 *
		 * @param array $definition
		 */
		public function __construct(array $definition) {
			$this->definition = $definition;
		}

		/**
		 * name function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function name() {
			return (string) ($this->definition['name'] ?? '');
		}

		/**
		 * label function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function label() {

			$label = $this->definition['label'] ?? '';

			return '' !== $label ? (string) $label : parent::label();

		}

		/**
		 * fields function
		 *
		 * The layout's own sub fields, exactly as Page_Content declares them - NOT the
		 * assembled set. Intro, the Content/Other Settings tabs and any contributed
		 * "Flexible Layout Row" fields are injected by Common_Fields on acf/load_field
		 * and are none of this class's business.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		public function fields() {
			return $this->definition['sub_fields'] ?? [];
		}

		/**
		 * supports function
		 *
		 * Derived from the definition rather than declared, so a site-contributed layout
		 * gets the same answers as a base one without registering anything extra.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		public function supports() {

			$supports = [];

			if(class_exists(Common_Fields::class) && Common_Fields::should_add_intro($this->name())) {
				$supports[] = 'intro';
			}

			$names = $this->field_names();

			if(in_array('buttons', $names, true)) {
				$supports[] = 'buttons';
			}

			// A `source` field is what makes a layout a loop grid: it selects repeater,
			// taxonomy or post type as the origin of the items it iterates.
			if(in_array('source', $names, true)) {
				$supports[] = 'items';
			}

			return $supports;

		}

		/**
		 * field_names function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		private function field_names() {

			$names = [];

			foreach($this->fields() as $field) {

				if(isset($field['name'])) {
					$names[] = $field['name'];
				}

			}

			return $names;

		}

	}
