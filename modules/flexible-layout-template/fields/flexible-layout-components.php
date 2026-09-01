<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Flexible_Layout_Components
	 *
	 * The registry of Flexible_Layout_Component implementations, and the one place that
	 * knows how to register them. Everything that used to name Buttons and Intro
	 * explicitly - Field_Groups, Component_Rule's dropdown, Conditional_Logic's sets -
	 * iterates MODULES instead, so adding a component is one class plus one line here.
	 *
	 * MODULES is a hand-maintained flat array rather than filesystem auto-discovery, on
	 * purpose: it matches ACBS\Core\Modules_Manager's own `const MODULES = [...]` one
	 * level up, keeps load order explicit and greppable, and means a half-finished class
	 * sitting in the fields directory can't accidentally register itself.
	 *
	 * @version 1.0.27
	 * @since   1.0.27
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Flexible_Layout_Components {

		/**
		 * Every registered component, in registration order.
		 *
		 * @var string[] Class names, each implementing Flexible_Layout_Component.
		 */
		const MODULES = [
			Buttons::class,
			Intro::class,
			Other_Settings::class,
			Grid_Display::class,
		];

		/**
		 * get_filtered_base_fields function
		 *
		 * A component's own fields with its `filter_tag()` filter applied - the single
		 * composition of those two, so no caller has to pair them up by hand.
		 *
		 * @version 1.0.27
		 * @since   1.0.27
		 *
		 * @param string $component Class name implementing Flexible_Layout_Component.
		 *
		 * @return array
		 */
		public static function get_filtered_base_fields($component) {
			return apply_filters($component::filter_tag(), $component::get_base_fields());
		}

		/**
		 * register_all function
		 *
		 * Registers every component's field group. Each is a clone SOURCE only - never
		 * shown on a post edit screen itself - so it registers inactive against a dummy
		 * location; what actually surfaces its fields is Common_Fields (for Intro) or a
		 * clone/field type pointing at it (for Buttons).
		 *
		 * The duplicate-key guard exists because the registry makes adding a component
		 * cheap enough that copy-pasting a group key from an existing one is a realistic
		 * mistake, and ACF gives no signal when it happens: acf_add_local_field_group()
		 * returns false and silently does nothing if the key is already registered
		 * (ACF Pro's own includes/local-fields.php), so the second component would simply
		 * never appear, with no error anywhere.
		 *
		 * @version 1.0.27
		 * @since   1.0.27
		 */
		public static function register_all() {

			$seen = [];

			foreach(self::MODULES as $component) {

				$group_key = $component::group_key();

				if(isset($seen[$group_key])) {

					_doing_it_wrong(
						__METHOD__,
						sprintf(
							/* translators: 1: component class name, 2: field group key, 3: the class that already claimed it */
							esc_html__('%1$s declares group key %2$s, which %3$s already registered. The later component will not appear until one of them is given its own key.', 'acbs'),
							esc_html($component),
							esc_html($group_key),
							esc_html($seen[$group_key])
						),
						'1.0.27'
					);

					continue;

				}

				$seen[$group_key] = $component;

				$site_fields_class = $component::site_fields_class();
				$fields = $site_fields_class::merge(self::get_filtered_base_fields($component));

				acf_add_local_field_group([
					'key' => $group_key,
					'title' => $component::group_title(),
					'fields' => $fields,
					'location' => [
						[
							[
								'param' => 'post_type',
								'operator' => '==',
								'value' => 'post',
							],
						],
					],
					'position' => 'normal',
					'style' => 'default',
					'active' => false,
				]);

			}

		}

		/**
		 * register_field_types function
		 *
		 * Registers the dedicated ACF field type of every component that has one.
		 *
		 * Must stay on `acf/include_field_types` at priority 20 (see Module) - these
		 * field types extend ACF Pro's own Repeater and Group, which register at priority
		 * 5, so anything earlier has nothing to extend.
		 *
		 * @version 1.0.27
		 * @since   1.0.27
		 */
		public static function register_field_types() {

			foreach(self::MODULES as $component) {

				$field_type_class = $component::field_type_class();

				if($field_type_class === null) {
					continue;
				}

				$field_type_class::register();

			}

		}

	}
