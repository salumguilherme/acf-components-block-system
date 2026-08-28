<?php

	namespace ERDC\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Site_Layouts
	 *
	 * Discovers any ACF field group tagged with the Location_Rule "Flexible Layout"
	 * rule and merges its flexible_content layouts into the plugin's own page_sections
	 * layouts, so a site builder can add per-site layouts (or extra fields on top of a
	 * common layout) purely through the ACF admin UI.
	 *
	 * @version 1.0.7
	 * @since   1.0.7
	 * @package ERDC\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Site_Layouts {

		/**
		 * merge function
		 *
		 * @version 1.0.8
		 * @since   1.0.7
		 *
		 * @param array $layouts The plugin's own base layouts, keyed by layout key.
		 *
		 * @return array
		 */
		public static function merge(array $layouts) {

			foreach(self::get_contributor_groups() as $group) {

				$flexible_field = self::find_flexible_content_field($group);

				if(!$flexible_field || empty($flexible_field['layouts'])) {
					continue;
				}

				foreach($flexible_field['layouts'] as $site_layout) {

					// Re-key every field pulled in from the site's group before use. ACF's
					// runtime field cache is keyed by field "key" alone (acf_get_store('fields')),
					// and scanning the site's own group above (acf_get_fields()) already warmed
					// that cache with each field tagged to the SITE's own parent/parent_layout.
					// Re-using those fields verbatim under our own field group would later
					// resolve straight back to that stale cached copy - whose parent_layout
					// points at the site's layout, not ours - and ACF would silently drop the
					// field when rebuilding our layout. A fresh key sidesteps the cache entirely.
					$site_layout['sub_fields'] = self::rekey_fields($site_layout['sub_fields'] ?? []);

					$layouts = self::merge_layout($layouts, $site_layout);
				}

			}

			return $layouts;

		}

		/**
		 * Suffix appended to every key pulled in from a site's own field group.
		 */
		const SITE_KEY_SUFFIX = '_site';

		/**
		 * rekey_fields function
		 *
		 * Recursively assigns each field (and any nested sub_fields/layouts) a new,
		 * stable key derived from its original one. Field values are stored by "name",
		 * not "key", so this is safe and does not affect saved data.
		 *
		 * Conditional logic is rewritten alongside the keys. A rule stores the key of the
		 * field it watches, so renaming a field without renaming the rules that point at
		 * it leaves those rules watching a key that no longer exists, and the field they
		 * belong to simply never appears. Rules pointing at one of the plugin's own fields
		 * are deliberately left alone: those keys are not rekeyed, so they already resolve.
		 *
		 * @version 1.0.26
		 * @since   1.0.8
		 *
		 * @param array $fields
		 *
		 * @return array
		 */
		private static function rekey_fields(array $fields) {

			return self::apply_rekey($fields, self::collect_keys($fields));

		}

		/**
		 * collect_keys function
		 *
		 * Every key belonging to the site's own group, gathered before anything is
		 * renamed so conditional logic can tell a site field from one of ours.
		 *
		 * @version 1.0.26
		 * @since   1.0.26
		 *
		 * @param array $fields
		 *
		 * @return array
		 */
		private static function collect_keys(array $fields) {

			$keys = [];

			foreach($fields as $field) {

				if(!empty($field['key'])) {
					$keys[] = $field['key'];
				}

				if(!empty($field['sub_fields']) && is_array($field['sub_fields'])) {
					$keys = array_merge($keys, self::collect_keys($field['sub_fields']));
				}

				if(!empty($field['layouts']) && is_array($field['layouts'])) {
					foreach($field['layouts'] as $nested_layout) {
						if(!empty($nested_layout['sub_fields'])) {
							$keys = array_merge($keys, self::collect_keys($nested_layout['sub_fields']));
						}
					}
				}

			}

			return $keys;

		}

		/**
		 * apply_rekey function
		 *
		 * @version 1.0.26
		 * @since   1.0.26
		 *
		 * @param array $fields
		 * @param array $site_keys
		 *
		 * @return array
		 */
		private static function apply_rekey(array $fields, array $site_keys) {

			foreach($fields as &$field) {

				$field['key'] = $field['key'].self::SITE_KEY_SUFFIX;

				if(!empty($field['conditional_logic']) && is_array($field['conditional_logic'])) {
					$field['conditional_logic'] = self::rekey_conditional_logic($field['conditional_logic'], $site_keys);
				}

				if(!empty($field['sub_fields']) && is_array($field['sub_fields'])) {
					$field['sub_fields'] = self::apply_rekey($field['sub_fields'], $site_keys);
				}

				if(!empty($field['layouts']) && is_array($field['layouts'])) {
					foreach($field['layouts'] as &$nested_layout) {
						if(!empty($nested_layout['sub_fields'])) {
							$nested_layout['sub_fields'] = self::apply_rekey($nested_layout['sub_fields'], $site_keys);
						}
					}
				}

			}

			return $fields;

		}

		/**
		 * rekey_conditional_logic function
		 *
		 * @version 1.0.26
		 * @since   1.0.26
		 *
		 * @param array $conditional_logic
		 * @param array $site_keys
		 *
		 * @return array
		 */
		private static function rekey_conditional_logic(array $conditional_logic, array $site_keys) {

			foreach($conditional_logic as &$rule_group) {

				if(!is_array($rule_group)) {
					continue;
				}

				foreach($rule_group as &$rule) {

					if(empty($rule['field']) || !in_array($rule['field'], $site_keys, true)) {
						continue;
					}

					$rule['field'] = $rule['field'].self::SITE_KEY_SUFFIX;

				}

			}

			return $conditional_logic;

		}

		/**
		 * sort function
		 *
		 * Sorts layouts alphabetically by label, so the "Add Section" picker in ACF
		 * stays predictable regardless of the order layouts were merged in.
		 *
		 * @version 1.0.8
		 * @since   1.0.8
		 *
		 * @param array $layouts
		 *
		 * @return array
		 */
		public static function sort(array $layouts) {

			uasort($layouts, function($a, $b) {
				return strcasecmp($a['label'], $b['label']);
			});

			return $layouts;

		}

		/**
		 * merge_layout function
		 *
		 * Matches a site layout against the current layouts by name OR label. If a match
		 * is found, the site layout's fields are merged into it (same-named fields are
		 * replaced with the site's version, new ones appended) rather than duplicating the
		 * layout, and the site's own label for it replaces ours - so a site can rename how
		 * a layout reads in the "Add Section" picker just by naming their own layout the
		 * same as ours and giving it the label they want. Otherwise, the site layout is
		 * added as a brand new layout, keeping its own label as-is.
		 *
		 * Called with groups already ordered by Contributor_Groups::get(), so when two
		 * site groups both target the same layout, this simply runs twice and the later
		 * call - the lower Order No. group - is what's left standing, for the label same
		 * as for the fields.
		 *
		 * @version 1.0.28
		 * @since   1.0.8
		 *
		 * @param array $layouts
		 * @param array $site_layout
		 *
		 * @return array
		 */
		private static function merge_layout(array $layouts, array $site_layout) {

			$matched_key = self::find_matching_layout_key($layouts, $site_layout);

			if($matched_key === null) {
				$layouts[$site_layout['key']] = $site_layout;
				return $layouts;
			}

			if(!empty($site_layout['label'])) {
				$layouts[$matched_key]['label'] = $site_layout['label'];
			}

			$layouts[$matched_key]['sub_fields'] = Field_Merge::merge(
				$layouts[$matched_key]['sub_fields'],
				$site_layout['sub_fields']
			);

			return $layouts;

		}

		/**
		 * find_matching_layout_key function
		 *
		 * @version 1.0.8
		 * @since   1.0.8
		 *
		 * @param array $layouts
		 * @param array $site_layout
		 *
		 * @return string|int|null
		 */
		private static function find_matching_layout_key(array $layouts, array $site_layout) {

			foreach($layouts as $key => $layout) {
				if($layout['name'] === $site_layout['name'] || $layout['label'] === $site_layout['label']) {
					return $key;
				}
			}

			return null;

		}

		/**
		 * get_contributor_groups function
		 *
		 * Ordered so that merging them in sequence (see merge()) resolves a same-name
		 * conflict between two site groups by Order No. - lower number wins. See
		 * Contributor_Groups for the full reasoning.
		 *
		 * @version 1.0.28
		 * @since   1.0.7
		 * @return array
		 */
		private static function get_contributor_groups() {
			return Contributor_Groups::get(Location_Rule::PAGE_CONTENT);
		}

		/**
		 * find_flexible_content_field function
		 *
		 * @version 1.0.7
		 * @since   1.0.7
		 *
		 * @param array $group
		 *
		 * @return array|null
		 */
		private static function find_flexible_content_field(array $group) {

			foreach(acf_get_fields($group) as $field) {
				if($field['type'] === 'flexible_content') {
					return $field;
				}
			}

			return null;

		}

	}
