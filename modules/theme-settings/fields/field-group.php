<?php

	namespace ERDC\Modules\ThemeSettings\Fields;

	use ERDC\Modules\ThemeSettings\Module;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Field_Group
	 *
	 * Registers the "Theme Settings" field group shipped with the plugin - the baseline
	 * set of theme-wide settings (contact details, social links, footer content, key
	 * pages) every site running this plugin gets.
	 *
	 * A site adds to or overrides this group by tagging its own field group "Flexible
	 * Layout Component = Theme Settings" (see Site_Fields::merge()) - the same
	 * mechanism Page Header, Buttons and Intro use - or, for sites that predate that
	 * rule, by pointing a plain field group directly at this options page, where a
	 * shared field name still wins but the plugin can only drop its own copy rather
	 * than fold the site's field into position (see Site_Fields::remove_claimed()).
	 * Code-based additions can use the `erdc/theme_settings/fields` filter.
	 *
	 * Note the field keys here deliberately differ from those in any hand-built version
	 * of this group a site may already have in its database: ACF's runtime field store
	 * is keyed by field key alone, so reusing them would make the two groups collide.
	 * Values are stored against each field's `name`, so fresh keys cost nothing.
	 *
	 * @version 1.0.29
	 * @since   1.0.20
	 * @package ERDC\Modules\ThemeSettings\Fields
	 */
	class Field_Group {

		/**
		 * Field group key.
		 */
		const GROUP_KEY = 'group_0eba3c3b07b67';

		/**
		 * get_base_fields function
		 *
		 * @version 1.0.20
		 * @since   1.0.20
		 * @return array
		 */
		private static function get_base_fields() {

			return [
				[
					'key' => 'field_8e58267ad9a23',
					'label' => 'General',
					'name' => '',
					'type' => 'tab',
					'placement' => 'top',
				],
				[
					'key' => 'field_6c1463c07496f',
					'label' => 'Default Header Image',
					'name' => 'default_header_image',
					'type' => 'image',
					'return_format' => 'array',
					'library' => 'all',
					'preview_size' => 'thumbnail',
				],
				[
					'key' => 'field_ee53c6eccf057',
					'label' => 'Phone Number',
					'name' => 'phone',
					'type' => 'text',
				],
				[
					'key' => 'field_d7314697b3dbf',
					'label' => 'Phone Number Link',
					'name' => 'phone_number_link',
					'type' => 'text',
				],
				[
					'key' => 'field_d29f980fb8632',
					'label' => 'Contact Email',
					'name' => 'contact_email',
					'type' => 'text',
					'instructions' => 'Leaving this blank will use the admin email in Settings > General',
				],
				[
					'key' => 'field_ea5dad5ba0abc',
					'label' => 'Address',
					'name' => 'address',
					'type' => 'textarea',
					'rows' => 3,
					'new_lines' => 'br',
				],
				[
					'key' => 'field_483ebf1e4bef3',
					'label' => 'Opening Hours',
					'name' => 'opening_hours',
					'type' => 'textarea',
					'rows' => 3,
					'new_lines' => 'br',
				],
				[
					'key' => 'field_5ab524fe473cc',
					'label' => 'Social',
					'name' => '',
					'type' => 'tab',
					'placement' => 'top',
				],
				[
					'key' => 'field_8dea443f95fda',
					'label' => 'Instagram URL',
					'name' => 'instagram',
					'type' => 'text',
				],
				[
					'key' => 'field_2612abd1b7738',
					'label' => 'Facebook URL',
					'name' => 'facebook',
					'type' => 'text',
				],
				[
					'key' => 'field_c972a433f2317',
					'label' => 'LinkedIn URL',
					'name' => 'linkedin',
					'type' => 'text',
				],
				[
					'key' => 'field_111ad9d4868d5',
					'label' => 'Twitter/X URL',
					'name' => 'twitter',
					'type' => 'text',
				],
				[
					'key' => 'field_d7cfc265d479b',
					'label' => 'Youtube URL',
					'name' => 'youtube',
					'type' => 'text',
				],
				[
					'key' => 'field_33bd11f82090b',
					'label' => 'Footer',
					'name' => '',
					'type' => 'tab',
					'placement' => 'top',
				],
				[
					'key' => 'field_44c237a4cf711',
					'label' => 'Footer Intro',
					'name' => 'footer_intro',
					'type' => 'wysiwyg',
					'tabs' => 'all',
					'toolbar' => 'full',
					'media_upload' => 0,
					'delay' => 1,
				],
				[
					'key' => 'field_9753b51773f10',
					'label' => 'Acknowledgement of Country',
					'name' => 'acknowledgement_of_country',
					'type' => 'textarea',
					'rows' => 4,
					'new_lines' => 'br',
				],
				[
					'key' => 'field_7ef6f49307a32',
					'label' => 'Key Pages & Categories',
					'name' => '',
					'type' => 'tab',
					'placement' => 'top',
				],
				[
					'key' => 'field_e9ccff321f46e',
					'label' => 'Contact Page',
					'name' => 'contact_page',
					'type' => 'post_object',
					'post_type' => '',
					'post_status' => '',
					'taxonomy' => '',
					'return_format' => 'object',
					'multiple' => 0,
					'allow_null' => 0,
					'ui' => 1,
				],
				[
					'key' => 'field_dd2c129736b8e',
					'label' => 'Blog Page',
					'name' => 'blog_page',
					'type' => 'post_object',
					'post_type' => ['page'],
					'post_status' => ['publish'],
					'taxonomy' => '',
					'return_format' => 'id',
					'multiple' => 0,
					'allow_null' => 0,
					'ui' => 1,
				],
			];

		}

		/**
		 * register function
		 *
		 * @version 1.0.29
		 * @since   1.0.20
		 */
		public static function register() {

			$fields = apply_filters('erdc/theme_settings/fields', self::get_base_fields());
			$fields = Site_Fields::remove_claimed($fields);
			$fields = Site_Fields::merge($fields);
			$fields = self::remove_empty_tabs($fields);

			// Nothing left to add - the site defines all of these itself, so stay out of
			// the way entirely rather than registering an empty metabox.
			if(empty($fields)) {
				return;
			}

			acf_add_local_field_group([
				'key' => self::GROUP_KEY,
				'title' => __('Theme Settings', 'erdc'),
				'fields' => $fields,
				'location' => [
					[
						[
							'param' => 'options_page',
							'operator' => '==',
							'value' => Module::MENU_SLUG,
						],
					],
				],
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'left',
				'instruction_placement' => 'label',
				'active' => true,
			]);

		}

		/**
		 * remove_empty_tabs function
		 *
		 * Drops any tab left with no fields under it once the site's own fields have
		 * been taken out, so a site that redefines everything in one tab doesn't end up
		 * staring at an empty one.
		 *
		 * @version 1.0.20
		 * @since   1.0.20
		 *
		 * @param array $fields
		 *
		 * @return array
		 */
		private static function remove_empty_tabs(array $fields) {

			$kept = [];

			foreach($fields as $index => $field) {

				if(($field['type'] ?? '') !== 'tab') {
					$kept[] = $field;
					continue;
				}

				// Look ahead: keep this tab only if a non-tab field follows it before the
				// next tab starts.
				$has_fields = false;

				foreach(array_slice($fields, $index + 1) as $following) {

					if(($following['type'] ?? '') === 'tab') {
						break;
					}

					$has_fields = true;
					break;

				}

				if($has_fields) {
					$kept[] = $field;
				}

			}

			return $kept;

		}

	}
