<?php

	namespace ERDC\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Common_Fields
	 *
	 * Injects the fields shared by every flexible layout - Intro's own Section Title/
	 * Content with their own "Intro" tab, a "Content" tab, and Other Settings' own
	 * background/padding/section ID controls under an "Other Settings" tab - into the
	 * `page_sections` field's layouts. The "Other Settings" fields originally migrated
	 * from the five-starter-child theme's includes/acf.php so they'd ship with the plugin
	 * instead of a specific site's theme; both Intro (1.0.23) and Other Settings (1.0.28)
	 * later became Flexible_Layout_Component implementations, so a site can override or
	 * extend either the same way it already could Buttons.
	 *
	 * Both Intro's and Other Settings' fields are injected already-flattened
	 * (get_intro_fields(), get_common_fields()) rather than as a literal `type => clone`
	 * field: ACF only flattens a seamless clone via its `acf/load_fields` filter, which
	 * fires when a parent's fields are loaded through `acf_get_fields()` - the path a
	 * database-backed field group's fields take. A locally-registered flexible_content
	 * field's layouts are plain PHP arrays that never go through that function
	 * (Layout::sub_fields() in ACF Pro's own source renders `$this->layout['sub_fields']`
	 * directly), so a clone field placed there would render as an inert, empty box
	 * instead of unwrapping into its fields - confirmed by direct testing before this was
	 * written. Building the flattened result ourselves sidesteps that entirely.
	 *
	 * Unlike Intro, Other Settings' fields keep their same literal keys across every
	 * layout rather than being key-prefixed per layout - that already worked before this
	 * became a component and nothing here needed to change it.
	 *
	 * @version 1.0.28
	 * @since   1.0.6
	 * @package ERDC\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Common_Fields {

		/**
		 * register function
		 *
		 * @version 1.0.6
		 * @since   1.0.6
		 */
		public static function register() {
			add_filter('acf/load_field/key=field_6a0a99f262aaf', [__CLASS__, 'inject_common_fields']);
			add_filter('erdc_disable_layout_intro', [__CLASS__, 'default_disabled_layouts']);
		}

		/**
		 * default_disabled_layouts function
		 *
		 * The plugin's own default exclusions from `erdc_disable_layout_intro` - a site
		 * can still add more (or, since the filter deals in a plain array, remove one of
		 * these) via its own callback on the same filter.
		 *
		 * @version 1.0.23
		 * @since   1.0.23
		 *
		 * @param array $disabled
		 *
		 * @return array
		 */
		public static function default_disabled_layouts($disabled) {

			return array_merge($disabled, [
				'full_width_image_cta',
				'full_width_image',
				'columned_content',
				'full_width_content',
			]);

		}

		/**
		 * inject_common_fields function
		 *
		 * Adds the common fields to every layout of the page_sections flexible content
		 * field, except when editing the field group itself in wp-admin (so the field
		 * group editor doesn't show the injected fields twice).
		 *
		 * Intro is added to every layout unconditionally - including a site's own
		 * custom layouts added via Site_Layouts - unless its name is in
		 * `erdc_disable_layout_intro`.
		 *
		 * @version 1.0.23
		 * @since   1.0.6
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		public static function inject_common_fields($field) {

			if(!function_exists('get_current_screen')) {

			} else {
				$screen = get_current_screen();

				if(!is_admin() || ($screen && !empty($screen->id) && $screen->id == 'acf-field-group')) {
					return $field;
				}
			}

			$disabled = apply_filters('erdc_disable_layout_intro', []);

			// Adds the common fields to each layout
			foreach($field['layouts'] as $parent_index => &$layout) {

				$layout_name = $layout['name'] ?? '';
				$intro = in_array($layout_name, $disabled, true) ? [] : self::get_intro_fields($layout_name);

				$leading_tab = [
					[
						'key' => 'field_386c3075730ea',
						'label' => 'Content',
						'name' => '',
						'type' => 'tab',
						'placement' => 'top',
					],
				];

				$layout_row_fields = self::get_layout_row_fields($layout_name);

				$layout['sub_fields'] = array_merge($leading_tab, $layout['sub_fields'], $intro, $layout_row_fields, self::get_common_fields());

			}

			return $field;

		}

		/**
		 * get_layout_row_fields function
		 *
		 * Fields a site has added via the "Flexible Layout Row" location rule
		 * (Layout_Row_Rule) - a group tagged "All Flexible Layouts" applies to every
		 * layout, a group tagged with one specific layout's name applies only there.
		 * "All" groups are merged first and specific-layout groups after, so a
		 * specific-layout field of the same name overrides an "all" one - via Field_Merge,
		 * the same merge (and the same key rekeying) as every other place a site
		 * contributes fields through a tagged group. "Specific-layout" here includes both a
		 * group tagged "== $layout_name" and one tagged "!= some other layout" (see
		 * Contributor_Groups::matches()), so a site can add a field to every layout
		 * except one without tagging every other layout individually.
		 *
		 * Landed in a layout's own sub_fields immediately before "Other Settings" (see
		 * inject_common_fields()), so these fields appear under that layout's own
		 * "Content" tab alongside its own fields.
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 *
		 * @param string $layout_name
		 *
		 * @return array
		 */
		public static function get_layout_row_fields($layout_name) {

			$fields = [];

			$groups = array_merge(
				Contributor_Groups::get(Layout_Row_Rule::ALL, [], Layout_Row_Rule::RULE_NAME),
				Contributor_Groups::matches(Layout_Row_Rule::RULE_NAME, $layout_name)
			);

			foreach($groups as $group) {

				$site_fields = self::rekey_fields(acf_get_fields($group));

				if(empty($site_fields)) {
					continue;
				}

				$fields = Field_Merge::merge($fields, $site_fields);

			}

			return $fields;

		}

		/**
		 * rekey_fields function
		 *
		 * Gives every field pulled in from a site's group a fresh key before it is used
		 * in a layout's own sub_fields - see Buttons_Site_Fields::rekey_fields() for why.
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 *
		 * @param array $fields
		 *
		 * @return array
		 */
		private static function rekey_fields(array $fields) {

			foreach($fields as &$field) {

				$field['key'] = $field['key'].'_site';

				if(!empty($field['sub_fields']) && is_array($field['sub_fields'])) {
					$field['sub_fields'] = self::rekey_fields($field['sub_fields']);
				}

			}

			return $fields;

		}

		/**
		 * should_add_intro function
		 *
		 * @version 1.0.23
		 * @since   1.0.23
		 *
		 * @param string $layout_name
		 *
		 * @return bool
		 */
		public static function should_add_intro($layout_name) {
			return !in_array($layout_name, apply_filters('erdc_disable_layout_intro', []), true);
		}

		/**
		 * get_intro_fields function
		 *
		 * Intro's own fields (its "Intro" tab, Section Title, Section Content, plus
		 * anything a site has merged in via Intro_Site_Fields), each key-prefixed with
		 * `field_intro_clone_<layout name>_` - the identical scheme ACF's own seamless
		 * clone would have produced, so this reads and migrates the same way a real
		 * clone's flattened output would (see Legacy_Migration\Intro_Migration).
		 *
		 * Each field is run through acf_validate_field() before being returned. A field
		 * reaching a layout's sub_fields the normal way (defined directly in Page_Content,
		 * or resolved through ACF's own clone/database pipeline) always arrives already
		 * validated; these are hand-built arrays that never pass through that pipeline,
		 * so without this they are missing derived properties ACF itself relies on -
		 * `_name` in particular, which acf_field_flexible_content::format_value() keys a
		 * row's final, name-indexed output by. Without it, PHP's implicit null-to-''
		 * coercion collapses every one of these fields onto the same empty string array
		 * key, and get_field() silently returns none of them - confirmed by tracing
		 * ACF's own format_value() directly before writing this line.
		 *
		 * @version 1.0.23
		 * @since   1.0.23
		 *
		 * @param string $layout_name
		 *
		 * @return array
		 */
		public static function get_intro_fields($layout_name) {

			$fields = apply_filters('erdc/intro/fields', Intro::get_base_fields());
			$fields = Intro_Site_Fields::merge($fields);

			$prefix = 'field_intro_clone_'.$layout_name.'_';

			foreach($fields as &$field) {
				$field['key'] = $prefix.$field['key'];
				$field = acf_validate_field($field);
			}

			return $fields;

		}


		/**
		 * get_common_fields function
		 *
		 * The "Other Settings" tab and the fields under it, shared by every layout - Other
		 * Settings' own fields (see Other_Settings::get_base_fields()), run through its
		 * `filter_tag()` filter and merged with anything a site has tagged "Flexible
		 * Layout Component = Other Settings" (see Other_Settings_Site_Fields), exactly the
		 * same composition get_intro_fields() does for Intro.
		 *
		 * Exposed separately from inject_common_fields() because that method deliberately
		 * does nothing on the field group edit screen, while Conditional_Logic needs this
		 * list precisely there in order to offer these fields as conditions.
		 *
		 * @version 1.0.28
		 * @since   1.0.26
		 * @return array
		 */
		public static function get_common_fields() {

			$fields = Flexible_Layout_Components::get_filtered_base_fields(Other_Settings::class);

			return Other_Settings_Site_Fields::merge($fields);

		}

	}