<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Intro_Field_Type
	 *
	 * A genuine ACF field type - "Intro Fields" - that a site builder picks directly
	 * from the Type dropdown when adding a field to their own custom flexible layout,
	 * instead of adding a Clone field pointed at the "Intro Section Fields - ERDC"
	 * group. Same reasoning as Buttons_Field_Type (see that class's docblock for the
	 * confirmed ACF editor-rendering bug this avoids); this one extends ACF's own
	 * "Group" field type (`\acf_field__group`, note the double underscore - that is
	 * ACF's actual class name) rather than Repeater, since Intro's shape - a leading
	 * tab plus a flat Section Content field - doesn't repeat.
	 *
	 * Its fields (Intro::get_base_fields(), plus anything a site has merged in via
	 * Intro_Site_Fields) are injected in load_field() on every load, never read from or
	 * written back to this field's own stored settings - update_field() (inherited)
	 * and duplicate_field() (overridden) both strip sub_fields before anything is
	 * saved, exactly mirroring Buttons_Field_Type.
	 *
	 * A site still customises these fields the same way as before: tag a field group
	 * with "Flexible Layout Component = Intro" (see Intro_Site_Fields) rather than
	 * editing this field.
	 *
	 * @version 1.0.24
	 * @since   1.0.24
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Intro_Field_Type extends \acf_field__group {

		/**
		 * Field type name, as it will be stored in the `type` column of any field using it.
		 */
		const NAME = 'erdc_intro_fields';

		/**
		 * initialize function
		 *
		 * See Buttons_Field_Type::initialize() for why the two type-scoped filters are
		 * re-registered after the rename rather than left to parent::initialize().
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 */
		public function initialize() {

			parent::initialize();

			$this->name = self::NAME;
			$this->label = __('Intro Fields', 'acbs');
			$this->category = 'layout';
			$this->description = __("The plugin's standard Section Content field, ready to use in a custom flexible layout without a Clone field. Its fields come from the plugin, or a site's own override tagged \"Flexible Layout Component = Intro\" - never from this field's own settings, so there is nothing here to duplicate or get out of sync.", 'acbs');

			$this->add_field_filter('acf/prepare_field_for_export', [$this, 'prepare_field_for_export']);
			$this->add_field_filter('acf/prepare_field_for_import', [$this, 'prepare_field_for_import']);

		}

		/**
		 * load_field function
		 *
		 * Overrides (rather than extends) acf_field__group::load_field(): that method
		 * populates sub_fields by querying the database for child field posts parented
		 * under this field's own ID via acf_get_fields(). This field is never meant to
		 * have any - its fields always come from Intro/Intro_Site_Fields - so this
		 * replaces that lookup outright rather than layering on top of it.
		 *
		 * Each field is run through acf_validate_field(), for the same reason
		 * Common_Fields::get_intro_fields() does: a hand-built field array is missing
		 * `_name` and other derived properties acf_validate_field() normally backfills,
		 * and this field type's own format_value() (inherited from acf_field__group)
		 * keys its output by `_name` - without it, values silently fail to resolve.
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		public function load_field($field) {

			$fields = apply_filters('acbs/intro/fields', Intro::get_base_fields());
			$fields = Intro_Site_Fields::merge($fields);

			foreach($fields as &$sub_field) {
				$sub_field = acf_validate_field($sub_field);
			}

			$field['sub_fields'] = $fields;

			return $field;

		}

		/**
		 * render_field_settings function
		 *
		 * The "General" tab. Deliberately does not call the parent implementation: that
		 * renders an editable Sub Fields list backed by real database rows under this
		 * field, which is exactly the mechanism this field type exists to avoid.
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 *
		 * @param array $field
		 */
		public function render_field_settings($field) {

			?>
			<div class="acf-field" data-name="erdc_intro_fields_notice">
				<div class="acf-label">
					<label><?php esc_html_e('Fields', 'acbs'); ?></label>
				</div>
				<div class="acf-input">
					<p class="description">
						<?php esc_html_e('Section Content - the same field as every other Intro section in this plugin. To add or change one, tag your own field group with "Flexible Layout Component = Intro" rather than editing this field.', 'acbs'); ?>
					</p>
				</div>
			</div>
			<?php

			acf_render_field_setting($field, [
				'label' => __('Layout', 'acbs'),
				'instructions' => '',
				'type' => 'radio',
				'name' => 'layout',
				'layout' => 'horizontal',
				'choices' => [
					'block' => __('Block', 'acbs'),
					'table' => __('Table', 'acbs'),
					'row' => __('Row', 'acbs'),
				],
			]);

		}

		/**
		 * duplicate_field function
		 *
		 * acf_field__group::duplicate_field() extracts sub_fields from $field and
		 * explicitly persists a fresh copy of them under the new field's own ID via
		 * acf_duplicate_fields() - see Buttons_Field_Type::duplicate_field() for why
		 * this must be overridden the same way.
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
