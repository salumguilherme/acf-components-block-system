<?php

	namespace ACBS\Modules\ThemeSettings\Fields;

	use ACBS\Modules\FlexibleLayoutTemplate\Fields\Component_Rule;
	use ACBS\Modules\FlexibleLayoutTemplate\Fields\Contributor_Groups;
	use ACBS\Modules\FlexibleLayoutTemplate\Fields\Site_Fields_Base;
	use ACBS\Modules\ThemeSettings\Module;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Site_Fields
	 *
	 * Two ways a site adds to or overrides the Theme Settings options page, both
	 * handled here:
	 *
	 * 1. Tagging a field group "Flexible Layout Component = Theme Settings" - the
	 *    same mechanism Page Header, Buttons and Intro use. A tagged field sharing a
	 *    `name` with one of ours REPLACES it in place; anything else is appended
	 *    after our own fields. This is the recommended way to add a field, since it
	 *    keeps everything in the one "Theme Settings" metabox and lets a field
	 *    genuinely override one of ours (label, instructions, choices) rather than
	 *    only being able to remove it.
	 * 2. Pointing a plain field group directly at the `theme-settings` options page
	 *    (no tag), the way this module worked before Component_Rule existed. A field
	 *    name it shares with ours still wins, but the plugin can only stand down and
	 *    drop its own field - it has no way to fold the site's field into a specific
	 *    position, so it ends up in a second, separate metabox. Kept for sites that
	 *    already override fields this way; not needed for new overrides.
	 *
	 * @version 1.0.29
	 * @since   1.0.20
	 * @package ACBS\Modules\ThemeSettings\Fields
	 */
	class Site_Fields extends Site_Fields_Base {

		/**
		 * @version 1.0.29
		 * @since   1.0.29
		 * @return string
		 */
		public static function location_value(): string {
			return Component_Rule::THEME_SETTINGS;
		}

		/**
		 * @version 1.0.29
		 * @since   1.0.29
		 * @return string
		 */
		public static function group_key(): string {
			return Field_Group::GROUP_KEY;
		}

		/**
		 * get_claimed_names function
		 *
		 * Returns every top level field name already claimed by a site-authored,
		 * PLAIN field group on the Theme Settings options page - i.e. one that does
		 * not use the "Flexible Layout Component" tag. Tagged groups are excluded
		 * here because merge() already folds them in by name; scanning them here too
		 * would drop the plugin's field before merge() ever sees it, losing the
		 * in-place replacement merge() would otherwise give it.
		 *
		 * Only top level names are collected: values nested inside a repeater, group
		 * or flexible content field are stored under their parent's name, so they
		 * can't collide with one of ours at the options level.
		 *
		 * @version 1.0.29
		 * @since   1.0.20
		 * @return array
		 */
		public static function get_claimed_names() {

			if(!function_exists('acf_get_field_groups')) {
				return [];
			}

			$tagged_keys = wp_list_pluck(
				Contributor_Groups::get(self::location_value(), [self::group_key()], Component_Rule::RULE_NAME),
				'key'
			);

			$names = [];

			foreach(acf_get_field_groups() as $group) {

				// Our own group never counts as a site contribution.
				if(($group['key'] ?? '') === self::group_key()) {
					continue;
				}

				if(empty($group['active'])) {
					continue;
				}

				if(in_array($group['key'] ?? '', $tagged_keys, true)) {
					continue;
				}

				if(!self::targets_our_options_page($group)) {
					continue;
				}

				foreach(acf_get_fields($group) as $field) {

					if(!empty($field['name'])) {
						$names[] = $field['name'];
					}

				}

			}

			return array_unique($names);

		}

		/**
		 * targets_our_options_page function
		 *
		 * True when any of the group's location rules points at the Theme Settings
		 * options page.
		 *
		 * ACF stores an options page rule's value as the page's menu slug. Sites that
		 * registered their own options page under a different slug before adopting this
		 * plugin can have those slugs recognised here via the
		 * `erdc/theme_settings/options_page_slugs` filter, so their existing fields still
		 * take precedence.
		 *
		 * @version 1.0.20
		 * @since   1.0.20
		 *
		 * @param array $group
		 *
		 * @return bool
		 */
		private static function targets_our_options_page(array $group) {

			$slugs = apply_filters('erdc/theme_settings/options_page_slugs', [Module::MENU_SLUG]);

			foreach($group['location'] ?? [] as $rule_group) {

				foreach($rule_group as $rule) {

					if(
						($rule['param'] ?? '') === 'options_page' &&
						($rule['operator'] ?? '') === '==' &&
						in_array($rule['value'] ?? '', $slugs, true)
					) {
						return true;
					}

				}

			}

			return false;

		}

		/**
		 * remove_claimed function
		 *
		 * Drops any of the plugin's own fields whose name a plain (untagged) site
		 * field group has already claimed. See get_claimed_names() for why tagged
		 * groups are excluded from this.
		 *
		 * @version 1.0.20
		 * @since   1.0.20
		 *
		 * @param array $fields
		 *
		 * @return array
		 */
		public static function remove_claimed(array $fields) {

			$claimed = self::get_claimed_names();

			if(empty($claimed)) {
				return $fields;
			}

			$remaining = [];

			foreach($fields as $field) {

				if(!empty($field['name']) && in_array($field['name'], $claimed, true)) {
					continue;
				}

				$remaining[] = $field;

			}

			return $remaining;

		}

	}
