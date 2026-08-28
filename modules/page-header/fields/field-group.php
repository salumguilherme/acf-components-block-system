<?php

	namespace ERDC\Modules\PageHeader\Fields;

	use ERDC\Modules\FlexibleLayoutTemplate\Fields\Buttons_Field_Type;
	use ERDC\Modules\PageHeader\Settings;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Field_Group
	 *
	 * Registers the "Page Header" field group - the baseline header controls every site
	 * running this plugin gets, which a site can then override or extend by tagging its
	 * own field group with the "Flexible Layout = Page Header" location rule (see
	 * Site_Fields).
	 *
	 * Its location is every public post type and taxonomy, minus whatever is ticked in
	 * the plugin's settings, so a site turns the header OFF somewhere rather than having
	 * to opt in everywhere.
	 *
	 * The keys here were regenerated rather than carried over from the site this group
	 * was originally exported from, so the plugin cannot collide with an identical group
	 * still sitting in an older site's database. Values are stored against each field's
	 * name, so nothing saved is affected.
	 *
	 * @version 1.0.22
	 * @since   1.0.22
	 * @package ERDC\Modules\PageHeader\Fields
	 */
	class Field_Group {

		/**
		 * Field group key.
		 */
		const GROUP_KEY = 'group_ce6dacdddfc11';

		/**
		 * Key of the Header Type field, which the conditional fields below key off.
		 */
		const HEADER_TYPE_KEY = 'field_b3b6329fcbc36';

		/**
		 * get_base_fields function
		 *
		 * @version 1.0.22
		 * @since   1.0.22
		 * @return array
		 */
		public static function get_base_fields() {

			$only_when_cta = [
				[
					[
						'field' => self::HEADER_TYPE_KEY,
						'operator' => '==',
						'value' => 'cta',
					],
				],
			];

			return [
				[
					'key' => self::HEADER_TYPE_KEY,
					'label' => 'Header Type',
					'name' => 'header_type',
					'type' => 'button_group',
					'choices' => [
						'standard' => 'Standard',
						'cta' => 'Call to Action',
						'centered' => 'Centered Standard',
						'none' => 'No Page Header',
					],
					'default_value' => 'standard',
					'return_format' => 'value',
					'allow_null' => 0,
					'layout' => 'horizontal',
				],
				[
					'key' => 'field_482e940157cba',
					'label' => 'Page Title',
					'name' => 'page_title',
					'type' => 'text',
					'instructions' => 'Leave blank to use the page\'s title',
				],
				[
					'key' => 'field_8f8963502348e',
					'label' => 'Written Content',
					'name' => 'header_written_content',
					'type' => 'wysiwyg',
					'tabs' => 'all',
					'toolbar' => 'full',
					'media_upload' => 1,
					'delay' => 1,
				],
				[
					'key' => 'field_829d2286e22c5',
					'label' => 'Header Image',
					'name' => 'header_image',
					'type' => 'image',
					'instructions' => 'Leave blank to use the featured image or default as fallback.',
					'conditional_logic' => $only_when_cta,
					'return_format' => 'array',
					'library' => 'all',
					'preview_size' => 'thumbnail',
				],
				[
					'key' => 'field_ad13f4d278517',
					'label' => 'Background Video URL',
					'name' => 'header_video',
					'type' => 'text',
					'conditional_logic' => $only_when_cta,
				],
				[
					// A genuine Buttons Repeater field, not a Clone of the plugin's Buttons
					// group - a seamless clone's own conditional_logic is lost when ACF
					// flattens it (confirmed directly: the flattened field carried no
					// data-conditions attribute at all and stayed visible regardless of
					// Header Type, so its always-present, always-empty row template blocked
					// saving on its required sub-fields). A real field's own
					// conditional_logic has no such flattening step, so it just works - see
					// Buttons_Field_Type's own docblock for why a real field type sidesteps
					// ACF's Clone mechanism entirely rather than working around it.
					//
					// 'name' is 'header_cta_buttons' (not 'header_cta') so this reads/writes
					// the exact same meta key the old clone saved under (prefix_name=1 on a
					// seamless clone of Buttons::GROUP_KEY's own "buttons" field produced
					// that same compound name) - no migration needed for already-saved data.
					'key' => 'field_9b9b642628d84',
					'label' => 'Buttons',
					'name' => 'header_cta_buttons',
					'type' => Buttons_Field_Type::NAME,
					'conditional_logic' => $only_when_cta,
					'layout' => 'row',
				],
			];

		}

		/**
		 * get_location function
		 *
		 * Every public post type and taxonomy except those excluded in the settings.
		 *
		 * @version 1.0.22
		 * @since   1.0.22
		 * @return array
		 */
		public static function get_location() {

			$excluded = Settings::get_exclusions();
			$location = [];

			foreach(get_post_types(['public' => true]) as $post_type) {

				if('attachment' === $post_type || in_array($post_type, $excluded, true)) {
					continue;
				}

				$location[] = [
					[
						'param' => 'post_type',
						'operator' => '==',
						'value' => $post_type,
					],
				];

			}

			foreach(get_taxonomies(['public' => true]) as $taxonomy) {

				if(in_array($taxonomy, $excluded, true)) {
					continue;
				}

				$location[] = [
					[
						'param' => 'taxonomy',
						'operator' => '==',
						'value' => $taxonomy,
					],
				];

			}

			return apply_filters('erdc/page_header/location', $location);

		}

		/**
		 * register function
		 *
		 * @version 1.0.22
		 * @since   1.0.22
		 */
		public static function register() {

			$location = self::get_location();

			// Everything is excluded, so there is nowhere to show this. Registering with an
			// empty location would place it on every screen, which is the opposite.
			if(empty($location)) {
				return;
			}

			$fields = apply_filters('erdc/page_header/fields', self::get_base_fields());
			$fields = Site_Fields::merge($fields);

			acf_add_local_field_group([
				'key' => self::GROUP_KEY,
				'title' => 'Header',
				'fields' => $fields,
				'location' => $location,
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'left',
				'instruction_placement' => 'label',
				'active' => true,
			]);

		}

	}
