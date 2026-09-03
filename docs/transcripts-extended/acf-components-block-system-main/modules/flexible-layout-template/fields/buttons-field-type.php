<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Buttons_Field_Type
	 *
	 * A genuine ACF field type - "Buttons Repeater" - that a site builder picks directly
	 * from the Type dropdown when adding a field to their own custom flexible layout,
	 * instead of adding a Clone field pointed at the "Buttons - ERDC" group.
	 *
	 * This exists because a seamless Clone field that resolves to a single field (a
	 * repeater, group, or flexible_content field, whether targeted directly or via a
	 * group that happens to contain only that one field) gets flattened by ACF's own
	 * field-group editor screen (`admin_field_group::mb_fields()` builds the editor's
	 * field list via `acf_get_fields()`, which runs the clone's own flattening filter)
	 * before the browser ever sees it - the editor shows and lets you edit what looks
	 * like a real, independent repeater, not a clone. Confirmed directly (not assumed)
	 * against ACF Pro's own source before writing this: there is no reliable save-time
	 * PHP hook that can detect or repair this, since a clone's identity is already gone
	 * by the time any save-related filter fires.
	 *
	 * This field type sidesteps the whole class of confusion rather than guarding
	 * against it: there is no Clone field involved anywhere, so there is nothing for
	 * ACF's editor to flatten. Its row fields (Buttons_Site_Fields::get_row_fields() -
	 * the same base-plus-site-override fields the registry clones into every
	 * standard layout) are injected in load_field() on every load, and never read from
	 * or written back to this field's own stored settings - update_field() (inherited)
	 * and duplicate_field() (overridden) both strip sub_fields before anything is
	 * saved. There is structurally no path by which this field's row fields end up as
	 * literal, duplicable database rows.
	 *
	 * A site still customises row fields the same way as before: tag a field group with
	 * "Flexible Layout Component = Buttons Repeater" (see Buttons_Site_Fields) rather
	 * than editing this field.
	 *
	 * @version 1.0.24
	 * @since   1.0.24
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Buttons_Field_Type extends \acf_field_repeater {

		/**
		 * Field type name, as it will be stored in the `type` column of any field using it.
		 */
		const NAME = 'erdc_buttons_repeater';

		/**
		 * initialize function
		 *
		 * acf_field_repeater::initialize() itself sets $this->name (to 'repeater') before
		 * registering its own type-scoped filters (add_field_filter() tags each hook with
		 * the CURRENT $this->name at the moment it's called) - so calling it first and
		 * renaming afterwards would leave those two filters registered under 'repeater'
		 * rather than ours. They're re-registered below, under our own name, once the
		 * rename has happened.
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 */
		public function initialize() {

			parent::initialize();

			$this->name = self::NAME;
			$this->label = __('Buttons Repeater', 'acbs');
			$this->category = 'layout';
			$this->description = __("The plugin's standard Buttons repeater (Button Text, Button Link, Button Style), ready to use in a custom flexible layout without a Clone field. Its row fields come from the plugin, or a site's own override tagged \"Flexible Layout Component = Buttons Repeater\" - never from this field's own settings, so there is nothing here to duplicate or get out of sync.", 'acbs');

			$this->add_field_filter('acf/prepare_field_for_export', [$this, 'prepare_field_for_export']);
			$this->add_field_filter('acf/prepare_field_for_import', [$this, 'prepare_field_for_import']);

		}

		/**
		 * load_field function
		 *
		 * Overrides (rather than extends) acf_field_repeater::load_field(): that method
		 * populates sub_fields by querying the database for child field posts parented
		 * under this field's own ID via acf_get_fields(). This field is never meant to
		 * have any - its row fields always come from Buttons_Site_Fields - so this
		 * replaces that lookup outright rather than layering on top of it.
		 *
		 * Each row field is run through acf_validate_field(), for the same reason
		 * Common_Fields::get_intro_fields() does: a hand-built field array is missing
		 * `_name` and other derived properties acf_validate_field() normally backfills,
		 * and acf_field_repeater::format_value() keys each row's final output by
		 * `_name` - without it, values silently fail to resolve. Confirmed directly:
		 * before this was added, every row field here came back with `_name` missing.
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		public function load_field($field) {

			$field['min'] = (int) $field['min'];
			$field['max'] = (int) $field['max'];

			$sub_fields = Buttons_Site_Fields::get_row_fields();

			foreach($sub_fields as &$sub_field) {
				$sub_field = acf_validate_field($sub_field);
			}

			$field['sub_fields'] = $sub_fields;

			if(empty($field['rows_per_page']) || (int) $field['rows_per_page'] < 1) {
				$field['rows_per_page'] = 20;
			}

			if('' === $field['button_label']) {
				$field['button_label'] = __('Add Row', 'acbs');
			}

			return $field;

		}

		/**
		 * render_field_settings function
		 *
		 * The "General" tab. Deliberately does not call the parent implementation: that
		 * renders an editable Sub Fields list backed by real database rows under this
		 * field, which is exactly the mechanism this field type exists to avoid. Layout
		 * is kept, since it is a presentation choice with no bearing on row fields.
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 *
		 * @param array $field
		 */
		public function render_field_settings($field) {

			?>
			<div class="acf-field" data-name="erdc_buttons_repeater_notice">
				<div class="acf-label">
					<label><?php esc_html_e('Row Fields', 'acbs'); ?></label>
				</div>
				<div class="acf-input">
					<p class="description">
						<?php esc_html_e('Button Text, Button Link and Button Style - the same row fields as every other Buttons repeater in this plugin. To add or change one, tag your own field group with "Flexible Layout Component = Buttons Repeater" rather than editing this field.', 'acbs'); ?>
					</p>
				</div>
			</div>
			<?php

			acf_render_field_setting($field, [
				'label' => __('Layout', 'acbs'),
				'instructions' => '',
				'class' => 'acf-repeater-layout',
				'type' => 'radio',
				'name' => 'layout',
				'layout' => 'horizontal',
				'choices' => [
					'table' => __('Table', 'acbs'),
					'block' => __('Block', 'acbs'),
					'row' => __('Row', 'acbs'),
				],
			]);

		}

		/**
		 * render_field_presentation_settings function
		 *
		 * The "Presentation" tab. The parent implementation's "Collapsed" setting lets a
		 * site pick a sub field to preview when a row is collapsed, populated client-side
		 * from the visible Sub Fields list this field type deliberately doesn't render -
		 * so it would only ever offer an empty choice here. Button Label alone is kept.
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 *
		 * @param array $field
		 */
		public function render_field_presentation_settings($field) {

			acf_render_field_setting($field, [
				'label' => __('Button Label', 'acbs'),
				'instructions' => '',
				'type' => 'text',
				'name' => 'button_label',
				'placeholder' => __('Add Row', 'acbs'),
			]);

		}

		/**
		 * duplicate_field function
		 *
		 * acf_field_repeater::duplicate_field() extracts sub_fields from $field and
		 * explicitly persists a fresh copy of them under the new field's own ID via
		 * acf_duplicate_fields() - which, for this field type, would create exactly the
		 * kind of literal, duplicable database rows this exists to avoid, the moment
		 * someone duplicates the field. update_field() (inherited, unchanged) already
		 * strips sub_fields the same way for the ordinary save path.
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		public function duplicate_field($field) {

			unset($field['sub_fields']);

			return $field;

		}

		/**
		 * register function
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 */
		public static function register() {
			acf_register_field_type(__CLASS__);
		}

	}
