<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Contributor_Groups
	 *
	 * Finds the site-authored field groups tagged with a given value of a location
	 * rule - by default the "Flexible Layout" rule (Page Content, Page Header,
	 * Buttons, ...), shared by every place in the plugin that merges a site's own
	 * fields on top of its own - see Site_Layouts, PageHeader\Fields\Site_Fields and
	 * Buttons_Site_Fields. Layout_Row_Rule and Component_Rule reuse the same lookup
	 * and priority-ordering by passing their own rule name instead.
	 *
	 * Two different site groups can end up defining the same field name for the same
	 * target (two groups both tagged "Page Content", both defining a field called
	 * "subtitle" on the same layout, say). Left to chance, whichever group
	 * acf_get_field_groups() happens to return last would silently win. Instead, this
	 * always returns groups ordered so that merging them in sequence - last write wins,
	 * which is what every merge_fields() in this plugin already does - resolves such a
	 * conflict by each field group's own "Order No." (menu_order): the LOWER number
	 * wins, by being merged last, in every case a conflict happens to line up. So it
	 * looks like a genuine priority level to whoever set it, not an accident.
	 *
	 * @version 1.0.24
	 * @since   1.0.28
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Contributor_Groups {

		/**
		 * get function
		 *
		 * @version 1.0.24
		 * @since   1.0.28
		 *
		 * @param string $rule_value        One of the target rule's value constants.
		 * @param array  $exclude_group_keys Group keys to leave out even if tagged (typically
		 *                                   the plugin's own group for this same target, as a
		 *                                   defensive measure against it ever matching itself).
		 * @param string $rule_name          The location rule's own name (its `param`, as
		 *                                   stored in a tagged group's `location` rules).
		 *                                   Defaults to the original "Flexible Layout" rule,
		 *                                   so every call site predating Layout_Row_Rule and
		 *                                   Component_Rule keeps working unchanged.
		 *
		 * @return array Field groups tagged with $rule_value, active only, ordered so that
		 *               merging them in sequence resolves same-name conflicts by Order No.
		 *               (lower number wins).
		 */
		public static function get($rule_value, array $exclude_group_keys = [], $rule_name = Location_Rule::RULE_NAME) {

			if(!function_exists('acf_get_field_groups')) {
				return [];
			}

			$groups = [];

			foreach(acf_get_field_groups() as $group) {

				if(empty($group['active'])) {
					continue;
				}

				if(in_array($group['key'] ?? '', $exclude_group_keys, true)) {
					continue;
				}

				if(self::is_tagged($group, $rule_name, $rule_value)) {
					$groups[] = $group;
				}

			}

			return self::order_by_priority($groups);

		}

		/**
		 * is_tagged function
		 *
		 * @version 1.0.24
		 * @since   1.0.28
		 *
		 * @param array  $group
		 * @param string $rule_name
		 * @param string $rule_value
		 *
		 * @return bool
		 */
		private static function is_tagged(array $group, $rule_name, $rule_value) {

			foreach($group['location'] ?? [] as $rule_group) {

				foreach($rule_group as $rule) {

					if(
						($rule['param'] ?? '') === $rule_name &&
						($rule['operator'] ?? '') === '==' &&
						($rule['value'] ?? '') === $rule_value
					) {
						return true;
					}

				}

			}

			return false;

		}

		/**
		 * matches function
		 *
		 * Like get(), but honours a group tagged with "!=" against $target_value, not
		 * only a plain "==" tag - Layout_Row_Rule's "is not equal to" option, so a site
		 * can add a field to every layout except one without tagging every other layout
		 * individually. Every rule value predating Layout_Row_Rule (Page Content, Page
		 * Header, Buttons, Intro, and Component_Rule's values) is only ever tagged with
		 * "==", so get() has no need for this and is left as the simpler, exact-match
		 * lookup those call sites already rely on.
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 *
		 * @param string $rule_name
		 * @param string $target_value
		 * @param array  $exclude_group_keys
		 *
		 * @return array Field groups whose own rule for $rule_name evaluates to true
		 *               against $target_value, active only, ordered as get() orders them.
		 */
		public static function matches($rule_name, $target_value, array $exclude_group_keys = []) {

			if(!function_exists('acf_get_field_groups')) {
				return [];
			}

			$groups = [];

			foreach(acf_get_field_groups() as $group) {

				if(empty($group['active'])) {
					continue;
				}

				if(in_array($group['key'] ?? '', $exclude_group_keys, true)) {
					continue;
				}

				if(self::rule_matches($group, $rule_name, $target_value)) {
					$groups[] = $group;
				}

			}

			return self::order_by_priority($groups);

		}

		/**
		 * rule_matches function
		 *
		 * @version 1.0.24
		 * @since   1.0.24
		 *
		 * @param array  $group
		 * @param string $rule_name
		 * @param string $target_value
		 *
		 * @return bool
		 */
		private static function rule_matches(array $group, $rule_name, $target_value) {

			foreach($group['location'] ?? [] as $rule_group) {

				foreach($rule_group as $rule) {

					if(($rule['param'] ?? '') !== $rule_name) {
						continue;
					}

					$is_equal = ($rule['value'] ?? '') === $target_value;
					$is_negated = ($rule['operator'] ?? '==') === '!=';

					if($is_negated ? !$is_equal : $is_equal) {
						return true;
					}

				}

			}

			return false;

		}

		/**
		 * order_by_priority function
		 *
		 * Highest Order No. (menu_order) first, lowest last - the sequence every caller
		 * here merges groups in, so that whichever group has the lowest Order No. is
		 * merged last and its fields are the ones left standing on a same-name conflict.
		 * Groups sharing an Order No. keep whatever order ACF itself returned them in.
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 *
		 * @param array $groups
		 *
		 * @return array
		 */
		private static function order_by_priority(array $groups) {

			// usort is not stable before PHP 8.0, but this plugin requires PHP 8.0+
			// (see the main plugin file), where usort is guaranteed stable - equal
			// menu_order values keep their original relative order.
			usort($groups, function($a, $b) {
				return ($b['menu_order'] ?? 0) <=> ($a['menu_order'] ?? 0);
			});

			return $groups;

		}

	}
