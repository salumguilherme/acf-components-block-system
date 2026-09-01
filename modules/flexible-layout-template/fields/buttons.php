<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Buttons
	 *
	 * Declares the "Buttons" component: the "buttons" repeater every flexible layout can
	 * embed. Its field group is never shown on a post edit screen itself - it exists purely
	 * as a clone source - and is registered generically from this declaration by
	 * Flexible_Layout_Components::register_all() rather than by a register() method here.
	 *
	 * A site can override or extend the fields available on EACH BUTTON ROW by tagging
	 * its own field group with "Flexible Layout Component = Buttons Repeater" (see
	 * Buttons_Site_Fields) - a field named the same as one of
	 * button_text/button_link/button_style replaces that row field, and anything else is
	 * added as a new row field alongside them. The repeater itself (its label, layout,
	 * min/max etc.) is never touched by this - only what is INSIDE each row.
	 *
	 * @version 1.0.27
	 * @since   1.0.6
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Buttons implements Flexible_Layout_Component {

		/**
		 * Field group key, referenced by every layout that clones this group.
		 *
		 * Titled "Buttons - ERDC" so it is distinguishable from the plain "Buttons"
		 * group older sites already have in their database. Both can coexist: unrelated
		 * field groups on those sites may still clone theirs, so it is never removed.
		 */
		const GROUP_KEY = 'group_b99bcf0767134';

		/**
		 * Key of the "buttons" repeater - the field a site's own contributed fields are
		 * merged into (as row fields), not the field group itself.
		 */
		const REPEATER_KEY = 'field_59bdb88314690';

		/**
		 * Key of the `button_icon` field.
		 *
		 * A constant because two sibling fields target it in their conditional logic, and a
		 * condition whose target key does not exist does not error - ACF simply never shows
		 * the field. A typo in a literal would hide Icon Position and Icon SVG permanently
		 * and silently, which is the exact failure mode this codebase is full of.
		 *
		 * ONE WAY THIS STILL BREAKS, worth knowing before someone hits it. A site that tags
		 * a field group "Flexible Layout Component = Buttons Repeater" and puts a field
		 * NAMED button_icon in it replaces ours by name (Field_Merge), and the replacement
		 * carries the site's own key plus Site_Layouts::SITE_KEY_SUFFIX. ICON_KEY then
		 * points at a field that is no longer in the repeater, so Icon Position and Icon SVG
		 * disappear - silently, because that is what an unsatisfiable condition does.
		 * Overriding the OTHER two fields, or adding new ones, is unaffected.
		 */
		const ICON_KEY = 'field_6a96aaa86a2c3';

		/**
		 * get_base_fields function
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 * @return array
		 */
		public static function get_base_fields(): array {

			return [
				[
					'key' => self::REPEATER_KEY,
					'label' => 'Buttons',
					'name' => 'buttons',
					'type' => 'repeater',
					'instructions' => '',
					'required' => 0,
					'layout' => 'row',
					'pagination' => 0,
					'min' => 0,
					'max' => 0,
					'collapsed' => '',
					'button_label' => '+ New Button',
					'rows_per_page' => 20,
					'sub_fields' => [
						[
							'key' => 'field_9ab0577ee568d',
							'label' => 'Button Text',
							'name' => 'button_text',
							'type' => 'text',
							'required' => 1,
						],
						[
							'key' => 'field_4c3bc60908296',
							'label' => 'Button Link',
							'name' => 'button_link',
							'type' => 'text',
							'required' => 1,
						],
						[
							'key' => 'field_7393b34321944',
							'label' => 'Button Style',
							'name' => 'button_style',
							'type' => 'button_group',
							'required' => 1,
							// Outline is no longer baked into the style. It used to double the
							// list - primary/secondary/tertiary each with an -outline twin - and
							// every new colour would have doubled it again. `button_outline`
							// below carries that axis on its own, so a style and its outline
							// variant are one choice plus one toggle.
							'choices' => [
								'primary' => 'Primary',
								'secondary' => 'Secondary',
								'tertiary' => 'Tertiary',
								'white' => 'White',
							],
							'default_value' => 'primary',
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_6a9621c4b7e12',
							'label' => 'Outline',
							'name' => 'button_outline',
							'type' => 'true_false',
							'required' => 0,
							'message' => '',
							'default_value' => 0,
							// Supplied with these two the way round they are: the toggle reads
							// "Solid" when on and "Outline" when off, which is the opposite of what
							// the field name suggests. Kept verbatim rather than silently corrected -
							// flip the two strings if it was a slip.
							'ui_on_text' => 'Outline',
							'ui_off_text' => 'Solid',
							'ui' => 1,
						],

						// The icon trio. Keys are as supplied and are NOT rewritten per layout,
						// which is what lets the two conditions below keep pointing at
						// ICON_KEY: a layout embeds this repeater as a seamless clone, and
						// while acf_clone_field() does rewrite each sub field's key to
						// "{clone key}_{key}", acf_field_clone::acf_prepare_field() restores
						// the original from __key before the field is rendered - explicitly so
						// that conditional logic resolves ("the original key will later be
						// restored by acf/prepare_field allowing conditional logic JS to work",
						// class-acf-field-clone.php). Prefixing them the way Grid_Display does
						// would break the conditions, not protect them.
						[
							'key' => self::ICON_KEY,
							'label' => 'Icon',
							'name' => 'button_icon',
							'type' => 'button_group',
							'required' => 1,
							// One list, shared with the renderer, so a choice cannot exist
							// without a file behind it - see Button_Icons.
							'choices' => Button_Icons::choices(),
							'default_value' => Button_Icons::NONE,
							'return_format' => 'value',
							'allow_null' => 0,
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_6a96ad75b8703',
							'label' => 'Icon Position',
							'name' => 'button_icon_position',
							'type' => 'button_group',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => self::ICON_KEY,
										'operator' => '!=',
										'value' => Button_Icons::NONE,
									],
								],
							],
							'choices' => [
								'before' => 'Before Text',
								'after' => 'After Text',
							],
							'default_value' => 'after',
							'return_format' => 'value',
							'allow_null' => 0,
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_6a96ab286a2c4',
							'label' => 'Icon SVG',
							'name' => 'button_icon_svg',
							'type' => 'image',
							'instructions' => 'SVG paths and elements must have fill="currentColor" in order to inherit the text colour of the button.',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => self::ICON_KEY,
										'operator' => '==',
										'value' => Button_Icons::CUSTOM,
									],
								],
							],
							// 'array', matching every other image field in the plugin. The
							// renderer accepts a bare id too, but only so a site that
							// re-declares this field does not silently render nothing.
							'return_format' => 'array',
							'library' => 'all',
							'mime_types' => 'svg',
							'preview_size' => 'thumbnail',
						],
					],
				],
			];

		}

		/**
		 * Flexible_Layout_Component implementation
		 *
		 * @version 1.0.27
		 * @since   1.0.27
		 */
		public static function group_key(): string {
			return self::GROUP_KEY;
		}

		public static function group_title(): string {
			return 'Buttons - ERDC';
		}

		public static function location_value(): string {
			return Component_Rule::BUTTONS;
		}

		public static function label(): string {
			return __('Buttons Repeater', 'erdc');
		}

		public static function filter_tag(): string {
			return 'erdc/buttons/fields';
		}

		public static function site_fields_class(): string {
			return Buttons_Site_Fields::class;
		}

		public static function field_type_class(): ?string {
			return Buttons_Field_Type::class;
		}

		/**
		 * get_base_row_fields function
		 *
		 * The plugin's OWN button row fields - the repeater's sub_fields, with the
		 * `erdc/buttons/fields` filter applied but WITHOUT any site-tagged override merged
		 * in. Deliberately not Buttons_Site_Fields::get_row_fields(): a site field
		 * replacing one of ours by name would otherwise take its place here too, and this
		 * is used to describe what the PLUGIN offers (see conditional_logic_fields()).
		 *
		 * Locates the repeater by key rather than assuming it is the first top-level
		 * field, matching Buttons_Site_Fields::find_repeater_index()'s reasoning - so this
		 * keeps working if the group's field list is ever extended above the repeater.
		 *
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return array
		 */
		public static function get_base_row_fields(): array {

			foreach(Flexible_Layout_Components::get_filtered_base_fields(self::class) as $field) {

				if(($field['key'] ?? '') === self::REPEATER_KEY) {
					return $field['sub_fields'] ?? [];
				}

			}

			return [];

		}

		/**
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return array
		 */
		public static function conditional_logic_fields(): array {
			return self::get_base_row_fields();
		}

		/**
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return string
		 */
		public static function conditional_logic_label(): string {
			return __('Buttons', 'erdc');
		}

	}
