<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Site_Fields_Base
	 *
	 * The one implementation of "merge a site's own field group into one of the plugin's
	 * own field sets". A site extends or overrides what the plugin ships by tagging its
	 * own field group with a Component_Rule value; every component that supports this
	 * resolves those groups, rekeys their fields, and folds them in identically.
	 *
	 * This used to be three near-identical copies (Buttons_Site_Fields,
	 * Intro_Site_Fields, PageHeader\Fields\Site_Fields), which had already visibly
	 * drifted: only Page Header's copy recursed into a field's nested `layouts`, and
	 * only Page Header's copy guarded `function_exists('acf_get_field_groups')`. This
	 * class takes the UNION of the protections found across all three rather than their
	 * lowest common denominator, so every component now gets both.
	 *
	 * The merge itself lives in Field_Merge, shared with the other two mechanisms that
	 * let a site contribute fields through a tagged group (the "Flexible Layout Row" rule
	 * and Page Content layouts), so field ordering behaves the same in all three.
	 *
	 * A subclass normally declares nothing but its two Component_Identity accessors.
	 * Buttons_Site_Fields is the one that overrides merge(), because its site fields
	 * belong to the ROW fields inside its repeater rather than to a flat top-level list.
	 *
	 * @version 1.0.27
	 * @since   1.0.27
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	abstract class Site_Fields_Base implements Component_Identity {

		/**
		 * @return string The Component_Rule value a site tags its group with.
		 */
		abstract public static function location_value(): string;

		/**
		 * @return string The plugin's own field group key for this component.
		 */
		abstract public static function group_key(): string;

		/**
		 * merge function
		 *
		 * Folds every contributor group's fields into $fields in turn. Field_Merge decides
		 * what replaces what and where each field ends up.
		 *
		 * @version 1.0.27
		 * @since   1.0.27
		 *
		 * @param array $fields The plugin's own base fields for this component.
		 *
		 * @return array
		 */
		public static function merge(array $fields): array {

			if(!function_exists('acf_get_field_groups')) {
				return $fields;
			}

			foreach(static::get_contributor_groups() as $group) {

				$site_fields = static::rekey_fields(acf_get_fields($group));

				if(empty($site_fields)) {
					continue;
				}

				$fields = Field_Merge::merge($fields, $site_fields);

			}

			return $fields;

		}

		/**
		 * get_contributor_groups function
		 *
		 * Ordered so that merging them in sequence (see merge()) resolves a same-name
		 * conflict between two site groups by Order No. - lower number wins. See
		 * Contributor_Groups for the full reasoning.
		 *
		 * @version 1.0.27
		 * @since   1.0.27
		 * @return array
		 */
		protected static function get_contributor_groups(): array {
			return Contributor_Groups::get(static::location_value(), [static::group_key()], Component_Rule::RULE_NAME);
		}

		/**
		 * rekey_fields function
		 *
		 * Gives every field pulled in from a site's group a fresh key before it is used
		 * under one of our own field groups.
		 *
		 * ACF's runtime field cache is keyed by field key alone, and reading the site's
		 * group has already warmed that cache with each field pointing at the SITE's
		 * parent. Re-using those keys verbatim would resolve straight back to that cached
		 * copy and ACF would drop the field when rebuilding our group. Values are stored
		 * against a field's name, not its key, so this does not affect saved data.
		 *
		 * Recurses into both `sub_fields` (repeaters, groups) and a nested
		 * flexible_content field's `layouts`, so a site can contribute an arbitrarily
		 * nested structure and every key inside it is rekeyed. Uses
		 * Site_Layouts::SITE_KEY_SUFFIX rather than its own literal, so this stays in
		 * lockstep with Conditional_Logic::is_site_field(), which recognises a
		 * site-contributed field by exactly that suffix.
		 *
		 * @version 1.0.27
		 * @since   1.0.27
		 *
		 * @param array $fields
		 *
		 * @return array
		 */
		protected static function rekey_fields(array $fields): array {

			foreach($fields as &$field) {

				$field['key'] = $field['key'].Site_Layouts::SITE_KEY_SUFFIX;

				if(!empty($field['sub_fields']) && is_array($field['sub_fields'])) {
					$field['sub_fields'] = static::rekey_fields($field['sub_fields']);
				}

				if(!empty($field['layouts']) && is_array($field['layouts'])) {

					foreach($field['layouts'] as &$layout) {

						if(!empty($layout['sub_fields'])) {
							$layout['sub_fields'] = static::rekey_fields($layout['sub_fields']);
						}

					}

				}

			}

			return $fields;

		}

	}
