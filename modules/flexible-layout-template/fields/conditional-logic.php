<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	use ACBS\Modules\PageHeader\Fields\Field_Group as Page_Header_Field_Group;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Conditional_Logic
	 *
	 * Lets a site's own field group set conditional logic against the fields the plugin
	 * ships, not just against the fields that group defines itself.
	 *
	 * The rules themselves already work once saved: conditional logic is stored as the
	 * key of the field being watched, and by the time a layout renders, the site's fields
	 * and ours live side by side in it (see Site_Layouts). The gap is purely the field
	 * group editor - ACF builds the "field" dropdown of a conditional logic rule from
	 * acf.getFieldObjects(), which only ever sees field objects present in the DOM of the
	 * group currently being edited. Ours are in a different, PHP-registered group, so they
	 * are never offered.
	 *
	 * This hands the editor a description of the plugin's fields so the dropdown can list
	 * them, grouped by the layout they belong to.
	 *
	 * @version 1.0.26
	 * @since   1.0.26
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Conditional_Logic {

		/**
		 * register function
		 *
		 * @version 1.0.26
		 * @since   1.0.26
		 */
		public static function register() {
			add_action('acf/field_group/admin_enqueue_scripts', [__CLASS__, 'enqueue']);
		}

		/**
		 * enqueue function
		 *
		 * @version 1.0.27
		 * @since   1.0.26
		 */
		public static function enqueue() {

			// Keyed by the value stored against whichever of Location_Rule (Page Content)
			// or Component_Rule (Page Header, Buttons, Intro, Other Settings) is currently
			// set on the group, so the editor can pick the right set - and offer nothing at
			// all when the group is not pointed at any of them.
			$sets = array_merge(
				[
					Location_Rule::PAGE_CONTENT => self::get_page_content_fields(),
					Component_Rule::PAGE_HEADER => self::get_page_header_fields(),
				],
				self::get_component_groups()
			);

			$sets = array_filter($sets);

			if(empty($sets)) {
				return;
			}

			wp_enqueue_script(
				'erdc-acf-conditional-logic',
				ACBS_URL.'assets/js/acf-conditional-logic.js',
				['jquery', 'acf-field-group'],
				ACBS_VERSION,
				true
			);

			wp_localize_script('erdc-acf-conditional-logic', 'erdcConditionalLogic', [
				'ruleNames' => [Location_Rule::RULE_NAME, Component_Rule::RULE_NAME],
				'sets' => $sets,
			]);

		}

		/**
		 * get_page_header_fields function
		 *
		 * The Page Header fields, as a single group. Unlike Page Content these are a flat
		 * list rather than being split across layouts.
		 *
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return array
		 */
		private static function get_page_header_fields() {

			if(!class_exists(Page_Header_Field_Group::class)) {
				return [];
			}

			return self::describe_fields_as_group(
				apply_filters('acbs/page_header/fields', Page_Header_Field_Group::get_base_fields()),
				__('Page Header', 'acbs')
			);

		}

		/**
		 * describe_fields_as_group function
		 *
		 * Turns a flat list of the plugin's own fields into the one-group shape the field
		 * group editor's condition picker expects: unusable entries (tabs, messages,
		 * anything with no value) and any site-contributed field dropped, the rest
		 * described, wrapped under a single heading. Empty array when nothing survives, so
		 * the caller's array_filter() drops the set entirely.
		 *
		 * Shared by every Flexible_Layout_Component (via conditional_logic_fields() /
		 * conditional_logic_label()) and by Page Header - this used to be three
		 * copy-pasted loops.
		 *
		 * @version 1.0.27
		 * @since   1.0.27
		 *
		 * @param array  $fields
		 * @param string $label
		 *
		 * @return array
		 */
		private static function describe_fields_as_group(array $fields, $label) {

			$described = [];

			foreach($fields as $field) {

				if(self::is_site_field($field) || !self::is_usable_as_condition($field)) {
					continue;
				}

				$described[] = self::describe($field);

			}

			if(empty($described)) {
				return [];
			}

			return [
				[
					'layout' => $label,
					'fields' => $described,
				],
			];

		}

		/**
		 * get_component_groups function
		 *
		 * One condition-picker group per registered Flexible_Layout_Component, keyed by
		 * the Component_Rule value a site tags its own group with - so this is both the
		 * per-component entries of enqueue()'s $sets and (flattened) the cross-cutting
		 * "shared" groups every Page Content layout also carries.
		 *
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return array
		 */
		private static function get_component_groups() {

			$groups = [];

			foreach(Flexible_Layout_Components::MODULES as $component) {

				$groups[$component::location_value()] = self::describe_fields_as_group(
					$component::conditional_logic_fields(),
					$component::conditional_logic_label()
				);

			}

			return $groups;

		}

		/**
		 * describe function
		 *
		 * @version 1.0.27
		 * @since   1.0.27
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		private static function describe(array $field) {

			return [
				'key' => $field['key'],
				'label' => ($field['label'] ?? '') !== '' ? $field['label'] : $field['name'],
				'name' => $field['name'],
				'type' => $field['type'],
				'choices' => self::get_choices($field),
			];

		}

		/**
		 * get_page_content_fields function
		 *
		 * The plugin's own page_sections fields, grouped by layout - plus, appended after
		 * them, the cross-cutting groups every layout can also carry (Buttons, Section
		 * Intro, Other Settings), each marked 'kind' => 'shared' so the field group editor
		 * JS knows to always offer them regardless of which layout is in context (see
		 * visibleGroups() in acf-conditional-logic.js). Each per-layout group additionally
		 * carries the layout's own 'name' and 'label', so that same script can narrow the
		 * list down to just the layout the field being edited actually belongs to.
		 *
		 * Fields contributed by a site are skipped: the editor already offers those on its
		 * own, and re-offering them here would list them twice. They are recognisable by
		 * the suffix Site_Layouts gives every key it pulls in. Layouts disabled via the
		 * plugin's "Disable Default Flexible Layouts" setting are dropped entirely - they
		 * can never actually appear on a real site, so offering them as a condition target
		 * would only be misleading.
		 *
		 * @version 1.0.28
		 * @since   1.0.26
		 * @return array
		 */
		private static function get_page_content_fields() {

			// Read from the plugin's own definitions rather than from ACF's runtime store.
			// On the field group edit screen ACF does not expose the fields of a
			// PHP-registered group at all - acf_get_fields() on it comes back empty - and
			// that screen is exactly where this list is needed.
			//
			// Working from the definitions also gives precisely the right set: the plugin's
			// own fields, without the site's own contributions mixed back in.
			$layouts = apply_filters('acbs/flexible_layout/layouts', Page_Content::get_base_layouts());
			$layouts = Page_Content::remove_disabled_layouts($layouts);

			if(empty($layouts)) {
				return [];
			}

			$groups = [];

			foreach($layouts as $layout) {

				$fields = [];

				foreach($layout['sub_fields'] ?? [] as $sub_field) {

					if(self::is_site_field($sub_field) || !self::is_usable_as_condition($sub_field)) {
						continue;
					}

					$fields[] = self::describe($sub_field);

				}

				if(empty($fields)) {
					continue;
				}

				$groups[] = [
					'kind' => 'layout',
					'name' => $layout['name'] ?? '',
					'label' => $layout['label'] ?? $layout['name'] ?? '',
					'layout' => $layout['label'] ?? $layout['name'],
					'fields' => $fields,
				];

			}

			$shared_groups = array_values(self::get_component_groups());

			foreach($shared_groups as $shared) {
				$groups = array_merge($groups, self::mark_shared($shared));
			}

			return $groups;

		}

		/**
		 * mark_shared function
		 *
		 * @version 1.0.22
		 * @since   1.0.22
		 *
		 * @param array $groups
		 *
		 * @return array
		 */
		private static function mark_shared(array $groups) {

			foreach($groups as &$group) {
				$group['kind'] = 'shared';
			}

			return $groups;

		}

		/**
		 * is_site_field function
		 *
		 * @version 1.0.26
		 * @since   1.0.26
		 *
		 * @param array $field
		 *
		 * @return bool
		 */
		private static function is_site_field(array $field) {
			return substr($field['key'] ?? '', -strlen(Site_Layouts::SITE_KEY_SUFFIX)) === Site_Layouts::SITE_KEY_SUFFIX;
		}

		/**
		 * is_usable_as_condition function
		 *
		 * Tabs, messages and the like carry no value, so nothing can be conditional on
		 * them.
		 *
		 * @version 1.0.26
		 * @since   1.0.26
		 *
		 * @param array $field
		 *
		 * @return bool
		 */
		private static function is_usable_as_condition(array $field) {

			$unusable = ['tab', 'message', 'accordion', 'clone', 'flexible_content'];

			return !empty($field['name']) && !in_array($field['type'] ?? '', $unusable, true);

		}

		/**
		 * get_choices function
		 *
		 * @version 1.0.26
		 * @since   1.0.26
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		private static function get_choices(array $field) {

			if(empty($field['choices']) || !is_array($field['choices'])) {
				return [];
			}

			$choices = [];

			foreach($field['choices'] as $value => $label) {
				$choices[] = ['value' => (string) $value, 'label' => (string) $label];
			}

			return $choices;

		}

	}
