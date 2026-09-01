<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Grid_Display
	 *
	 * Declares the "Grid & Display" component: how a row lays its content out and whether
	 * that content sits in a card. The third shared component alongside Intro and Other
	 * Settings, and injected the same way - Common_Fields puts its tab immediately after
	 * Intro's.
	 *
	 * Unlike the other two, this one is NOT injected into every layout. Each layout
	 * declares which of these fields it takes (see LAYOUTS), and a layout that takes none
	 * gets no tab at all rather than an empty one. full_width_image has no grid to
	 * configure and no card to sit in, so a "Grid & Display" tab there would be a
	 * dead end.
	 *
	 * WHY THE OVERRIDES EXIST
	 *
	 * These fields arrived duplicated across twelve layout definitions, and the copies had
	 * drifted: `layout_columns` had five distinct definitions between them, differing in
	 * label ("Grid Layout (Columns)" / "Layout Columns" / "Columns"), in default (3, 4 and
	 * 7) and in range (1-6 for most, 1-8 for the two galleries). Some of that drift was
	 * accidental and some of it is real - a logo wall genuinely wants eight across where a
	 * stats grid does not - so rather than flattening it or preserving it wholesale, the
	 * canonical definition lives in get_base_fields() and only the differences that carry
	 * meaning live in LAYOUTS.
	 *
	 * Three kinds of drift were dropped as copy-paste damage rather than intent:
	 *
	 * 1. `return_format` and `layout` empty on the testimonials and logo_gallery copies,
	 *    where every other copy said 'value' and 'horizontal'. Empty return_format makes
	 *    a button_group return the label instead of the value.
	 * 2. conditional_logic missing its `value` on six of the twelve card-colour fields
	 *    (cta, icon_list, contact_page_form and testimonials, both fields each). ACF
	 *    compares against '' in that state, so "Card BG Colour" would show when Content
	 *    Box was NOT set to card - backwards. The two correct copies (columned_content,
	 *    stats) are what the canonical version follows.
	 * 3. `columns_alignment` was the only field in the group without the `layout_` prefix.
	 *    It is `layout_columns_alignment` here, so the whole group shares one prefix and
	 *    Common_Fields can recognise a Grid & Display field by name alone.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Grid_Display implements Flexible_Layout_Component {

		/**
		 * Field group key.
		 */
		const GROUP_KEY = 'group_6a9620d1a4c37';

		/**
		 * Which fields each layout takes, in the order they should appear.
		 *
		 * A layout absent from here, or mapped to an empty array, gets no Grid & Display
		 * tab. Adjust per site through the `acbs/grid_display/layout_fields` filter rather
		 * than by editing this - see fields_for_layout().
		 */
		const LAYOUTS = [
			'columned_content' => ['layout_columns', 'layout_columns_sm', 'layout_columns_xs', 'layout_columns_alignment', 'layout_display', 'layout_display_bg', 'layout_display_bg_colour'],
			'cta' => ['layout_display', 'layout_display_bg', 'layout_display_bg_colour'],
			'icon_list' => ['layout_columns', 'layout_columns_sm', 'layout_columns_xs', 'layout_columns_alignment', 'layout_display', 'layout_display_bg', 'layout_display_bg_colour'],
			'contact_page_form' => ['layout_display', 'layout_display_bg', 'layout_display_bg_colour'],
			'image_gallery' => ['layout_columns', 'layout_columns_sm', 'layout_columns_xs'],
			'logo_gallery' => ['layout_columns', 'layout_columns_sm', 'layout_columns_xs'],
			'stats' => ['layout_columns', 'layout_columns_sm', 'layout_columns_xs', 'layout_columns_alignment', 'layout_display', 'layout_display_bg', 'layout_display_bg_colour'],
			'testimonials' => ['layout_columns', 'layout_columns_sm', 'layout_columns_xs', 'layout_columns_alignment', 'layout_display', 'layout_display_bg', 'layout_display_bg_colour'],
		];

		/**
		 * Per-layout differences from the canonical definitions, keyed layout => field =>
		 * the settings that differ. Everything not named here comes from get_base_fields().
		 *
		 * Only meaningful differences belong here. If a value is the same as canonical,
		 * leave it out - an override that restates the default is a line that stops being
		 * true the day the default changes.
		 */
		const OVERRIDES = [

			// Galleries lay out small, uniform items, so they go up to eight across and
			// start at seven. Their responsive steps deliberately have no default: an
			// unset tablet/mobile value falls back to the desktop count.
			'image_gallery' => [
				'layout_columns' => ['label' => 'Columns', 'choices' => self::COLUMNS_8, 'default_value' => 7],
				'layout_columns_sm' => ['label' => 'Columns - Tablet', 'choices' => self::COLUMNS_8, 'default_value' => ''],
				'layout_columns_xs' => ['label' => 'Columns - Mobile', 'choices' => self::COLUMNS_8, 'default_value' => ''],
			],

			'logo_gallery' => [
				'layout_columns' => ['label' => 'Columns', 'choices' => self::COLUMNS_8, 'default_value' => 7],
				'layout_columns_sm' => ['label' => 'Columns - Tablet', 'choices' => self::COLUMNS_8, 'default_value' => ''],
				'layout_columns_xs' => ['label' => 'Columns - Mobile', 'choices' => self::COLUMNS_8, 'default_value' => ''],
			],

		];

		/**
		 * 1-6, the range every layout but the two galleries offers.
		 */
		const COLUMNS_6 = [1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6'];

		/**
		 * 1-8, for the galleries.
		 */
		const COLUMNS_8 = [1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8'];

		/**
		 * card_bg_choices function
		 *
		 * The shared palette, plus `custom`.
		 *
		 * `custom` is the one entry a section background does not have, and the one entry
		 * with no CSS rule behind it: it reveals layout_display_bg_colour, whose value is
		 * emitted as an inline --fl-card-box-bg on the section by
		 * Module::layout_wrapper_style(). It goes last because it is the escape hatch, not a
		 * peer of the brand colours.
		 *
		 * `default` (Transparent) is kept rather than filtered out. A transparent card is not
		 * a contradiction - it still has the card's padding and rounding, just no fill - and
		 * dropping it here would put the two palettes back out of step, which is the thing
		 * Colour_Palette exists to prevent.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		public static function card_bg_choices(): array {

			$choices = Colour_Palette::choices();
			$choices['custom'] = __('Custom', 'acbs');

			/**
			 * Filters the Card BG Colour choices.
			 *
			 * Relabel site-wide through `acbs/colour_palette/choices` instead; this is for
			 * narrowing the list to just this field.
			 *
			 * @param array $choices
			 */
			return (array) apply_filters('acbs/grid_display/bg_choices', $choices);

		}

		/**
		 * get_base_fields function
		 *
		 * The canonical definition of every field in the group, tab first. Common_Fields
		 * never injects this whole list - fields_for_layout() selects from it - but the
		 * component registers it in full so a site tagging its own group against
		 * "Flexible Layout Component = Grid & Display" has the complete set to override.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		public static function get_base_fields(): array {

			return [
				[
					'key' => 'field_6a9620d1a4c38',
					'label' => 'Grid & Display',
					'name' => '',
					'type' => 'tab',
					'placement' => 'top',
				],
				[
					'key' => 'field_6a9620d1a4c39',
					'label' => 'Grid Layout (Columns)',
					'name' => 'layout_columns',
					'type' => 'button_group',
					'required' => 1,
					'choices' => self::COLUMNS_6,
					'default_value' => 3,
					'return_format' => 'value',
					'allow_null' => 0,
					'layout' => 'horizontal',
				],
				[
					'key' => 'field_6a9620d1a4c3a',
					'label' => 'Grid Layout (Columns) - Tablet',
					'name' => 'layout_columns_sm',
					'type' => 'button_group',
					'required' => 0,
					'choices' => self::COLUMNS_6,
					'default_value' => 2,
					'return_format' => 'value',
					'allow_null' => 1,
					'layout' => 'horizontal',
				],
				[
					'key' => 'field_6a9620d1a4c3b',
					'label' => 'Grid Layout (Columns) - Mobile',
					'name' => 'layout_columns_xs',
					'type' => 'button_group',
					'required' => 0,
					'choices' => self::COLUMNS_6,
					'default_value' => 1,
					'return_format' => 'value',
					'allow_null' => 1,
					'layout' => 'horizontal',
				],
				[
					'key' => 'field_6a9620d1a4c3c',
					'label' => 'Grid Alignment',
					'name' => 'layout_columns_alignment',
					'type' => 'button_group',
					'instructions' => 'How the columns align relative to the content width when the number of columns is lower than the layout.',
					'required' => 1,
					'choices' => [
						'default' => 'Left',
						'center' => 'Center',
						'right' => 'Right',
					],
					'default_value' => 'center',
					'return_format' => 'value',
					'allow_null' => 0,
					'layout' => 'horizontal',
				],
				[
					'key' => self::DISPLAY_KEY,
					'label' => 'Content Box',
					'name' => 'layout_display',
					'type' => 'button_group',
					'required' => 1,
					'choices' => [
						'default' => 'Seamless (default)',
						'card' => 'Card',
					],
					'default_value' => 'default',
					'return_format' => 'value',
					'allow_null' => 0,
					'layout' => 'horizontal',
				],
				[
					'key' => self::DISPLAY_BG_KEY,
					'label' => 'Card BG Colour',
					'name' => 'layout_display_bg',
					'type' => 'button_group',
					'required' => 1,
					// The `value` here is the fix described in the class comment: six of
					// the twelve supplied copies omitted it, which inverts the condition.
					'conditional_logic' => [
						[
							[
								'field' => self::DISPLAY_KEY,
								'operator' => '==',
								'value' => 'card',
							],
						],
					],
					'choices' => self::card_bg_choices(),
					'default_value' => 'light',
					'return_format' => 'value',
					'allow_null' => 0,
					'layout' => 'horizontal',
				],
				[
					'key' => 'field_6a9620d1a4c3f',
					'label' => 'Card BG Custom Colour',
					'name' => 'layout_display_bg_colour',
					'type' => 'color_picker',
					'required' => 1,
					// Both conditions, so choosing Custom and then switching Content Box
					// back to Seamless hides this too.
					'conditional_logic' => [
						[
							[
								'field' => self::DISPLAY_BG_KEY,
								'operator' => '==',
								'value' => 'custom',
							],
							[
								'field' => self::DISPLAY_KEY,
								'operator' => '==',
								'value' => 'card',
							],
						],
					],
					'default_value' => '#FFFFFF',
					'enable_opacity' => 1,
					'return_format' => 'string',
					'show_custom_palette' => 0,
					'show_color_wheel' => 1,
				],
			];

		}

		/**
		 * Keys referenced by conditional logic, so the two card fields point at the real
		 * Content Box field rather than a hardcoded string repeated three times.
		 */
		const DISPLAY_KEY = 'field_6a9620d1a4c3d';
		const DISPLAY_BG_KEY = 'field_6a9620d1a4c3e';

		/**
		 * fields_for_layout function
		 *
		 * The Grid & Display fields one layout takes, tab included, ready to merge into
		 * that layout's sub_fields. Empty array when the layout takes none, which is what
		 * suppresses the tab.
		 *
		 * Keys are prefixed per layout for the same reason Intro's are (see
		 * Common_Fields::get_intro_fields()): ACF's runtime field cache is keyed by field
		 * key alone, so the same key appearing under twelve different layouts resolves
		 * every one of them back to whichever was registered last.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $layout_name
		 *
		 * @return array
		 */
		public static function fields_for_layout($layout_name) {

			$wanted = self::LAYOUTS[$layout_name] ?? [];

			/**
			 * Filters which Grid & Display fields a layout takes.
			 *
			 * Return an empty array to drop the tab from a layout entirely, or add field
			 * names to give a layout controls it does not have by default. Names not in
			 * the component are ignored.
			 *
			 * @param array  $wanted      Field names, in display order.
			 * @param string $layout_name
			 */
			$wanted = (array) apply_filters('acbs/grid_display/layout_fields', $wanted, $layout_name);

			if(empty($wanted)) {
				return [];
			}

			$base = Flexible_Layout_Components::get_filtered_base_fields(self::class);
			$base = Grid_Display_Site_Fields::merge($base);

			$by_name = [];
			$tab = null;

			foreach($base as $field) {

				if('tab' === ($field['type'] ?? '')) {
					$tab = $field;
					continue;
				}

				if(!empty($field['name'])) {
					$by_name[$field['name']] = $field;
				}

			}

			$fields = [];
			$overrides = self::OVERRIDES[$layout_name] ?? [];
			$prefix = 'field_grid_'.$layout_name.'_';

			foreach($wanted as $name) {

				if(!isset($by_name[$name])) {
					continue;
				}

				$field = array_merge($by_name[$name], $overrides[$name] ?? []);
				$field['key'] = $prefix.$field['key'];

				// Conditional logic points at sibling keys, which have just been
				// prefixed too, so the references have to move with them.
				if(!empty($field['conditional_logic']) && is_array($field['conditional_logic'])) {
					foreach($field['conditional_logic'] as $g => $group) {
						foreach($group as $c => $condition) {
							if(!empty($condition['field'])) {
								$field['conditional_logic'][$g][$c]['field'] = $prefix.$condition['field'];
							}
						}
					}
				}

				$fields[] = acf_validate_field($field);

			}

			if(empty($fields)) {
				return [];
			}

			// The tab is only worth emitting once there is something under it.
			$tab = $tab ?: [
				'key' => 'field_6a9620d1a4c38',
				'label' => 'Grid & Display',
				'name' => '',
				'type' => 'tab',
				'placement' => 'top',
			];

			$tab['key'] = $prefix.$tab['key'];

			array_unshift($fields, acf_validate_field($tab));

			return $fields;

		}

		/**
		 * Flexible_Layout_Component implementation
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function group_key(): string {
			return self::GROUP_KEY;
		}

		public static function group_title(): string {
			return 'Grid & Display Fields - ERDC';
		}

		public static function location_value(): string {
			return Component_Rule::GRID_DISPLAY;
		}

		public static function label(): string {
			return __('Grid & Display', 'acbs');
		}

		public static function filter_tag(): string {
			return 'acbs/grid_display/fields';
		}

		public static function site_fields_class(): string {
			return Grid_Display_Site_Fields::class;
		}

		public static function field_type_class(): ?string {
			return null;
		}

		/**
		 * Flat fields, so the conditionable ones are its own - the tab is dropped
		 * downstream by Conditional_Logic::is_usable_as_condition().
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		public static function conditional_logic_fields(): array {
			return Flexible_Layout_Components::get_filtered_base_fields(self::class);
		}

		/**
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public static function conditional_logic_label(): string {
			return __('Grid & Display', 'acbs');
		}

	}
