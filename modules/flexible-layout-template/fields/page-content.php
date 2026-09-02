<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	use ACBS\Modules\FlexibleLayoutTemplate\Page_Template;
	use ACBS\Modules\FlexibleLayoutTemplate\Settings;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Page_Content
	 *
	 * Registers the "Page Content" field group - the `page_sections` flexible content
	 * field that every Flexible Layout document renders a row from.
	 *
	 * @version 1.0.6
	 * @since   1.0.6
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Page_Content {

		/**
		 * Field group key.
		 *
		 * Note this is NOT the value stored against the "Flexible Layout" location rule -
		 * see Location_Rule::PAGE_CONTENT, which is deliberately independent of it.
		 */
		const GROUP_KEY = 'group_aad6274c059f8';

		/**
		 * Key of the `page_sections` flexible_content field itself. See the "DO NOT
		 * CHANGE THIS KEY" comment on that field in register() - exposed here so other
		 * classes (e.g. Layouts_Export) can resolve the live field via acf_get_field()
		 * without duplicating the literal.
		 */
		const FIELD_KEY = 'field_6a0a99f262aaf';

		/**
		 * The base set of layouts shipped with the plugin.
		 *
		 * Per-site custom layouts can be appended (or these can be adjusted) via the
		 * `acbs/flexible_layout/layouts` filter, without editing this file.
		 *
		 * Note: the common "Section Content" / "Other Settings" tab fields (background,
		 * padding, section ID etc.) are intentionally NOT part of these layouts - they are
		 * injected into every layout by Common_Fields via `acf/load_field`.
		 *
		 * @version 1.0.6
		 * @since   1.0.6
		 * @return array
		 */
		public static function get_base_layouts() {

			return [
				'layout_7a0fd9862bc95' => [
					'key' => 'layout_7a0fd9862bc95',
					'label' => 'Accordions',
					'name' => 'accordions',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_6a96141b996f5',
							'label' => 'Accordions',
							'name' => 'accordions',
							'type' => 'repeater',
							'required' => 1,
							'layout' => 'block',
							'min' => 1,
							'max' => 0,
							'collapsed' => '',
							'button_label' => 'Add Accordion',
							'sub_fields' => [
								[
									'key' => 'field_6a961425996f6',
									'label' => 'Title',
									'name' => 'title',
									'type' => 'text',
									'required' => 1,
									'default_value' => '',
									'maxlength' => '',
									'placeholder' => '',
									'prepend' => '',
									'append' => '',
								],
								[
									'key' => 'field_6a9614ba996f8',
									'label' => 'Content',
									'name' => 'content',
									'type' => 'wysiwyg',
									'required' => 1,
									'default_value' => '',
									'tabs' => 'all',
									'toolbar' => 'basic',
									'media_upload' => 1,
									'delay' => 0,
								],
							],
						],
					],
				],
				'layout_7a6fd8a3a6ddb' => [
					'key' => 'layout_7a6fd8a3a6ddb',
					'label' => 'Columned Content',
					'name' => 'columned_content',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_7a6fd936a6de3',
							'label' => 'Content Columns',
							'name' => 'columns',
							'type' => 'repeater',
							'instructions' => 'Add one row for each column in your content',
							'required' => 1,
							'wrapper' => [
								'width' => '100',
								'class' => '',
								'id' => '',
							],
							'layout' => 'block',
							'min' => 1,
							'max' => 0,
							'collapsed' => '',
							'button_label' => 'Add Column',
							'sub_fields' => [
								[
									'key' => 'field_6a97ae207d538',
									'label' => 'Enable Accordion',
									'name' => 'column_accordion',
									'type' => 'true_false',
									'instructions' => 'When checked, the content of the column will be placed in an accordion toggle.',
									'required' => 0,
									'message' => 'Display the content of this column in an accordion',
									'default_value' => 0,
									'ui' => 0,
									'ui_on_text' => '',
									'ui_off_text' => '',
								],
								[
									'key' => 'field_6a9611670d868',
									'label' => 'Icon',
									'name' => 'icon',
									'type' => 'image',
									'required' => 0,
									// 'array', matching the identical icon field on icon_list. It was
									// '' - and ACF treats anything that is not 'url' or 'array' as
									// "return the bare attachment ID", so this one field returned an
									// int where its sibling returned an array, and the $icon['ID']
									// idiom that template uses rendered nothing at all here.
									'return_format' => 'array',
									'library' => 'all',
									'min_width' => '',
									'min_height' => '',
									'min_size' => '',
									'max_width' => '',
									'max_height' => '',
									'max_size' => '',
									'mime_types' => 'svg',
									'preview_size' => 'thumbnail',
								],
								[
									'key' => 'field_6a97aef29038d',
									'label' => 'Status on Page Load',
									'name' => 'column_accordion_initial_status',
									'type' => 'button_group',
									'instructions' => 'Select if this accrodion is open or closed when the page loads',
									'required' => 1,
									// Targets the toggle's KEY, and `==` against "1" rather than `!=empty`. ACF
									// registers condition types per FIELD TYPE, and true_false ships only `==` and
									// `!=` against a single "Checked" choice. An operator it does not register for
									// the type falls back to the base acf.Condition, whose match() returns false
									// unconditionally - so the wrong operator here would hide these two fields
									// permanently rather than simply ignoring the rule.
									'conditional_logic' => [
										[
											[
												'field' => 'field_6a97ae207d538',
												'operator' => '==',
												'value' => '1',
											],
										],
									],
									'choices' => [
										'default' => 'Closed',
										'open' => 'Open',
									],
									'default_value' => 'default',
									'return_format' => 'value',
									'allow_null' => 0,
									'layout' => 'horizontal',
								],
								[
									'key' => 'field_6a97ae8676310',
									'label' => 'Accordion Title',
									'name' => 'column_accordion_title',
									'type' => 'text',
									'required' => 0,
									'conditional_logic' => [
										[
											[
												'field' => 'field_6a97ae207d538',
												'operator' => '==',
												'value' => '1',
											],
										],
									],
									'default_value' => '',
									'maxlength' => '',
									'placeholder' => '',
									'prepend' => '',
									'append' => '',
								],
								[
									'key' => 'field_7a6fd966a6de4',
									'label' => 'Content',
									'name' => 'content',
									'type' => 'wysiwyg',
									'required' => 1,
									'default_value' => '',
									'tabs' => 'all',
									'toolbar' => 'basic',
									'media_upload' => 1,
									'delay' => 1,
								],
								[
									'key' => 'field_7a6fdd877847d',
									'label' => 'Content Alignment',
									'name' => 'column_alignment',
									'type' => 'button_group',
									'instructions' => 'Alignment of the content within the column',
									'required' => 1,
									'choices' => [
										'default' => 'Inherit',
										'center' => 'Center',
										'right' => 'Right',
									],
									'default_value' => 'default',
									'return_format' => 'value',
									'allow_null' => 0,
									'layout' => 'horizontal',
								],
								[
									'key' => 'field_7a6fd8a3a6ddf',
									'label' => 'Buttons',
									'name' => 'buttons',
									'type' => 'clone',
									'clone' => [
										'group_b99bcf0767134',
									],
									'display' => 'seamless',
									'layout' => 'block',
									'prefix_label' => 0,
									'prefix_name' => 0,
								],
							],
						],
					],
				],
				'layout_6a961819e525f' => [
					'key' => 'layout_6a961819e525f',
					'label' => 'Call to Action',
					'name' => 'cta',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_6a961819e5261',
							'label' => 'Content',
							'name' => 'written_content',
							'type' => 'wysiwyg',
							'instructions' => 'Add one row for each column in your content',
							'required' => 0,
							'wrapper' => [
								'width' => '100',
								'class' => '',
								'id' => '',
							],
							'default_value' => '',
							'tabs' => 'all',
							'toolbar' => 'basic',
							'media_upload' => 1,
							'delay' => 0,
						],
						[
							'key' => 'field_6a96186be526d',
							'label' => 'Buttons',
							'name' => 'buttons',
							'type' => 'clone',
							'clone' => [
								'group_b99bcf0767134',
							],
							'display' => 'seamless',
							'layout' => 'block',
							'prefix_label' => 0,
							'prefix_name' => 0,
						],
						[
							'key' => 'field_6a9618bde526e',
							'label' => 'Display Type',
							'name' => 'display_type',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								'columns' => 'Left and Right',
								'stacked' => 'Stacked',
							],
							'default_value' => 'columns',
							'return_format' => 'value',
							'allow_null' => 0,
							'layout' => 'horizontal',
						],
					],
				],
				'layout_7a0d19786f938' => [
					'key' => 'layout_7a0d19786f938',
					'label' => 'Content and Image - 2 Columns',
					'name' => 'content_left_image_right',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_7a0d19c06f93d',
							'label' => 'Hero Image',
							'name' => 'image',
							'type' => 'image',
							'required' => 1,
							'return_format' => 'array',
							'library' => 'all',
							'min_width' => '',
							'min_height' => '',
							'min_size' => '',
							'max_width' => '',
							'max_height' => '',
							'max_size' => '',
							'mime_types' => '',
							'preview_size' => 'thumbnail',
						],
						[
							'key' => 'field_7a0d1e05b115f',
							'label' => 'Image fit',
							'name' => 'image_fit',
							'type' => 'button_group',
							'instructions' => 'Contain will resize the image to its proportion within the box. Cover will fill the image in the space box.',
							'required' => 1,
							'choices' => [
								'cover' => 'Cover',
								'contain' => 'Contain',
							],
							'default_value' => 'cover',
							'return_format' => 'value',
							'allow_null' => 0,
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_7a0d19876f93b',
							'label' => 'Content',
							'name' => 'written_content',
							'type' => 'wysiwyg',
							'required' => 1,
							'default_value' => '',
							'tabs' => 'all',
							'toolbar' => 'basic',
							'media_upload' => 1,
							'delay' => 0,
						],
						[
							'key' => 'field_7a28ac69bd8eb',
							'label' => 'Content Alignment',
							'name' => 'content_alignment',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								'left' => 'Left',
								'right' => 'Right',
							],
							'default_value' => 'left',
							'return_format' => 'value',
							'allow_null' => 0,
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_7a0d19916f93c',
							'label' => 'Buttons',
							'name' => 'buttons',
							'type' => 'clone',
							'clone' => [
								'group_b99bcf0767134',
							],
							'display' => 'seamless',
							'layout' => 'block',
							'prefix_label' => 0,
							'prefix_name' => 0,
						],
					],
				],
				'layout_6a9616f0e5241' => [
					'key' => 'layout_6a9616f0e5241',
					'label' => 'Icon List',
					'name' => 'icon_list',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_6a9616f0e5242',
							'label' => 'Icon List',
							'name' => 'icon_list',
							'type' => 'repeater',
							'instructions' => 'Add one row for each column in your content',
							'required' => 1,
							'wrapper' => [
								'width' => '100',
								'class' => '',
								'id' => '',
							],
							'layout' => 'block',
							'min' => 1,
							'max' => 0,
							'collapsed' => '',
							'button_label' => 'Add Stat',
							'sub_fields' => [
								[
									'key' => 'field_6a9616f0e5243',
									'label' => 'Icon',
									'name' => 'icon',
									'type' => 'image',
									'required' => 1,
									'return_format' => 'array',
									'library' => 'all',
									'min_width' => '',
									'min_height' => '',
									'min_size' => '',
									'max_width' => '',
									'max_height' => '',
									'max_size' => '',
									'mime_types' => 'svg',
									'preview_size' => 'thumbnail',
								],
								[
									'key' => 'field_6a9616f0e5245',
									'label' => 'Content',
									'name' => 'content',
									'type' => 'text',
									'required' => 1,
									'default_value' => '',
									'maxlength' => '',
									'placeholder' => '',
									'prepend' => '',
									'append' => '',
								],
							],
						],
					],
				],
				'layout_6a9619c47c32e' => [
					'key' => 'layout_6a9619c47c32e',
					'label' => 'Enquiry Form',
					'name' => 'contact_page_form',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_6a96124e996eb',
							'label' => 'Content',
							'name' => 'written_content',
							'type' => 'wysiwyg',
							'required' => 0,
							'default_value' => '',
							'tabs' => 'all',
							'toolbar' => 'basic',
							'media_upload' => 1,
							'delay' => 1,
						],
						[
							'key' => 'field_7a2b6a17f86bc',
							'label' => 'Contact form embed or shortcode',
							'name' => 'contact_form_id',
							'type' => 'textarea',
							'instructions' => 'Gravity Forms ID',
							'required' => 1,
							'default_value' => '',
							'maxlength' => '',
							'rows' => '',
							'placeholder' => '',
							'new_lines' => '',
						],
					],
				],
				'layout_7a29c29b5e07b' => [
					'key' => 'layout_7a29c29b5e07b',
					'label' => 'Full Width Image',
					'name' => 'full_width_image',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_7a29c2b05e080',
							'label' => 'Image',
							'name' => 'image',
							'type' => 'image',
							'instructions' => 'This image spans the full width of the page. If a height is set the image is cropped and stays centred on the page. If no height, the image resizes in proportion to browsers window.',
							'required' => 0,
							'return_format' => 'array',
							'library' => 'all',
							'min_width' => '',
							'min_height' => '',
							'min_size' => '',
							'max_width' => '',
							'max_height' => '',
							'max_size' => '',
							'mime_types' => '',
							'preview_size' => 'thumbnail',
						],
						[
							'key' => 'field_6a9619fb7c339',
							'label' => 'Enable Overlay',
							'name' => 'overlay',
							'type' => 'true_false',
							'required' => 0,
							'message' => 'Dark overlay over image',
							'default_value' => 1,
							'ui_on_text' => '',
							'ui_off_text' => '',
							'ui' => 1,
						],
						[
							'key' => 'field_b4d5436647875',
							'label' => 'Overlay Colour',
							'name' => 'overlay_colour',
							'type' => 'color_picker',
							'required' => 1,
							// TWO GROUPS, WHICH IS AN OR - and both are needed.
							//
							// ACF resolves a rule to a condition type by (field type,
							// operator) and falls back to the base acf.Condition when the
							// pair matches nothing. That base's match() returns false
							// unconditionally, so an unsupported operator does not degrade
							// to "always show", it hides the field permanently.
							//
							// `!=empty` is registered for text-ish and choice fields but
							// NOT for true_false, which only ships `==` and `!=` against a
							// single "Checked" choice. So `!=empty` alone is right for a
							// site that has overridden `overlay` to another field type,
							// and would silently hide this field wherever `overlay` is
							// still the true_false declared above. The second group covers
							// that case; whichever group applies, the other simply never
							// matches.
							//
							// The target is the field KEY, not the name: ACF looks
							// conditional logic up by key, and a name here resolves to
							// nothing - which is the same permanent hide by another route.
							'conditional_logic' => [
								[
									[
										'field' => 'field_6a9619fb7c339',
										'operator' => '!=empty',
									],
								],
								[
									[
										'field' => 'field_6a9619fb7c339',
										'operator' => '==',
										'value' => '1',
									],
								],
							],
							'default_value' => 'rgba(0, 0, 0, .7)',
							'enable_opacity' => 1,
							'return_format' => 'string',
							'allow_in_bindings' => 0,
							'show_custom_palette' => 0,
							'show_color_wheel' => 1,
							'custom_palette_source' => '',
							'palette_colors' => '',
						],
						[
							'key' => 'field_6a9617d6e525d',
							'label' => 'Content',
							'name' => 'written_content',
							'type' => 'wysiwyg',
							'required' => 0,
							'default_value' => '',
							'tabs' => 'all',
							'toolbar' => 'basic',
							'media_upload' => 1,
							'delay' => 1,
						],
						[
							'key' => 'field_6a9617eae525e',
							'label' => 'Buttons',
							'name' => 'buttons',
							'type' => 'clone',
							'clone' => [
								'group_b99bcf0767134',
							],
							'display' => 'seamless',
							'layout' => 'block',
							'prefix_label' => 0,
							'prefix_name' => 0,
						],
					],
				],
				'layout_6a9613b4996f0' => [
					'key' => 'layout_6a9613b4996f0',
					'label' => 'Image Gallery',
					'name' => 'image_gallery',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_7a27882490e3f',
							'label' => 'Images',
							'name' => 'images',
							'type' => 'gallery',
							'required' => 1,
							'return_format' => 'array',
							'library' => 'all',
							'min' => '',
							'max' => '',
							'min_width' => '',
							'min_height' => '',
							'min_size' => '',
							'max_width' => '',
							'max_height' => '',
							'max_size' => '',
							'mime_types' => '',
							'insert' => 'append',
							'preview_size' => 'thumbnail',
						],
					],
				],
				'layout_7a0fc00c1defb' => [
					'key' => 'layout_7a0fc00c1defb',
					'label' => 'Logo Gallery',
					'name' => 'logo_gallery',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_7a0fc2bc1df02',
							'label' => 'Logos',
							'name' => 'logos',
							'type' => 'gallery',
							'required' => 1,
							'return_format' => 'array',
							'library' => 'all',
							'min' => '',
							'max' => '',
							'min_width' => '',
							'min_height' => '',
							'min_size' => '',
							'max_width' => '',
							'max_height' => '',
							'max_size' => '',
							'mime_types' => '',
							'insert' => 'append',
							'preview_size' => 'thumbnail',
						],
					],
				],
				'layout_6a96167bdea0a' => [
					'key' => 'layout_6a96167bdea0a',
					'label' => 'Stats',
					'name' => 'stats',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_6a96167bdea0c',
							'label' => 'Stats',
							'name' => 'stats',
							'type' => 'repeater',
							'instructions' => 'Add one row for each column in your content',
							'required' => 1,
							'wrapper' => [
								'width' => '100',
								'class' => '',
								'id' => '',
							],
							'layout' => 'block',
							'min' => 1,
							'max' => 0,
							'collapsed' => '',
							'button_label' => 'Add Stat',
							'sub_fields' => [
								[
									'key' => 'field_6a96167bdea0d',
									'label' => 'Stat',
									'name' => 'stat',
									'type' => 'text',
									'required' => 1,
									'default_value' => '',
									'maxlength' => '',
									'placeholder' => '',
									'prepend' => '',
									'append' => '',
								],
								[
									'key' => 'field_6a9616addea18',
									'label' => 'Stat Subtitle',
									'name' => 'subtitle',
									'type' => 'text',
									'required' => 0,
									'default_value' => '',
									'maxlength' => '',
									'placeholder' => '',
									'prepend' => '',
									'append' => '',
								],
								[
									'key' => 'field_6a96167bdea0e',
									'label' => 'Content',
									'name' => 'content',
									'type' => 'wysiwyg',
									'required' => 1,
									'default_value' => '',
									'tabs' => 'all',
									'toolbar' => 'basic',
									'media_upload' => 1,
									'delay' => 1,
								],
								[
									'key' => 'field_6a96167bdea0f',
									'label' => 'Content Alignment',
									'name' => 'column_alignment',
									'type' => 'button_group',
									'instructions' => 'Alignment of the content within the column',
									'required' => 1,
									'choices' => [
										'default' => 'Inherit',
										'center' => 'Center',
										'right' => 'Right',
									],
									'default_value' => 'default',
									'return_format' => 'value',
									'allow_null' => 0,
									'layout' => 'horizontal',
								],
								[
									'key' => 'field_6a96167bdea10',
									'label' => 'Buttons',
									'name' => 'buttons',
									'type' => 'clone',
									'clone' => [
										'group_b99bcf0767134',
									],
									'display' => 'seamless',
									'layout' => 'block',
									'prefix_label' => 0,
									'prefix_name' => 0,
								],
							],
						],
					],
				],
				'layout_6a961789e524f' => [
					'key' => 'layout_6a961789e524f',
					'label' => 'Testimonials',
					'name' => 'testimonials',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_7a28d406deb31',
							'label' => 'Testimonials',
							'name' => 'testimonials',
							'type' => 'repeater',
							'required' => 1,
							'layout' => 'block',
							'min' => 1,
							'max' => 0,
							'collapsed' => '',
							'button_label' => 'Add Testimonial',
							'sub_fields' => [
								[
									'key' => 'field_7a27a09f9bedf',
									'label' => 'Testimonial Content',
									'name' => 'testimonial_content',
									'type' => 'wysiwyg',
									'required' => 1,
									'default_value' => '',
									'tabs' => 'all',
									'toolbar' => 'basic',
									'media_upload' => 1,
									'delay' => 0,
								],
								[
									'key' => 'field_7a27a0dc9bee0',
									'label' => 'Author',
									'name' => 'testimonial_author',
									'type' => 'text',
									'required' => 0,
									'default_value' => '',
									'maxlength' => '',
									'placeholder' => '',
									'prepend' => '',
									'append' => '',
								],
								[
									'key' => 'field_7a27a0ec9bee1',
									'label' => 'Author Position/Role/Context',
									'name' => 'testimonial_author_role',
									'type' => 'text',
									'required' => 0,
									'default_value' => '',
									'maxlength' => '',
									'placeholder' => '',
									'prepend' => '',
									'append' => '',
								],
								[
									'key' => 'field_6a961a5e7c33a',
									'label' => 'Content Alignment',
									'name' => 'column_alignment',
									'type' => 'button_group',
									'instructions' => 'Alignment of the content within the column',
									'required' => 1,
									'choices' => [
										'default' => 'Inherit',
										'center' => 'Center',
										'right' => 'Right',
									],
									'default_value' => 'default',
									'return_format' => 'value',
									'allow_null' => 0,
									'layout' => 'horizontal',
								],
							],
						],
					],
				],
			];

		}


		/**
		 * remove_disabled_layouts function
		 *
		 * Drops any layout ticked off in the plugin's "Disable Default Flexible Layouts"
		 * setting, before anything from a site is merged in - so a site can still add its
		 * own layout of the same name (see Settings and Site_Layouts::merge()): with the
		 * plugin's copy removed here, find_matching_layout_key() finds nothing to match
		 * against and the site's own layout is simply added fresh, on its own terms.
		 *
		 * Public since 1.0.22 so Conditional_Logic can apply the exact same exclusion
		 * when listing Page Content's layouts as condition targets, rather than keeping a
		 * second copy of this check.
		 *
		 * @version 1.0.22
		 * @since   1.0.30
		 *
		 * @param array $layouts
		 *
		 * @return array
		 */
		public static function remove_disabled_layouts(array $layouts) {

			$disabled = Settings::get_disabled();

			if(empty($disabled)) {
				return $layouts;
			}

			foreach($layouts as $key => $layout) {

				if(in_array($layout['name'], $disabled, true)) {
					unset($layouts[$key]);
				}

			}

			return $layouts;

		}

		/**
		 * get_current_layouts function
		 *
		 * The full, current set of layouts, in the exact order register() itself builds
		 * the real page_sections field: base layouts, WooCommerce-only ones dropped if
		 * inactive, anything disabled via the plugin's "Disable Default Flexible Layouts"
		 * setting removed, then merged with any layout a site has added purely through
		 * tagging its own field group "Flexible Layout = Page Content" (Site_Layouts),
		 * then the code-based `acbs/flexible_layout/layouts` extension filter, then sorted.
		 *
		 * Anything that needs "every layout currently available on this site" - not just
		 * the plugin's own hardcoded set - should call this rather than rebuilding a
		 * second, partial copy of this pipeline: Layout_Row_Rule::get_values() and the
		 * tab-default JS (Module::enqueue_flexible_layout_tabs_assets()) both used to skip
		 * the Site_Layouts::merge() step, so a layout added purely through the ACF admin
		 * UI never showed up as an option in "Flexible Layout Row" - confirmed and fixed
		 * by routing both through this one method instead.
		 *
		 * Conditional_Logic::get_page_content_fields() is a deliberate exception: it wants
		 * only the plugin's OWN fields (ACF's own field group editor already offers a
		 * site's own fields as condition targets natively), so it intentionally keeps
		 * reading straight from get_base_layouts() rather than calling this.
		 *
		 * @version 1.0.25
		 * @since   1.0.25
		 *
		 * @return array
		 */
		public static function get_current_layouts() {

			// Base layouts, then merged with any site-added layouts tagged via the "Flexible
			// Layout" ACF location rule (see Site_Layouts), then finally the code-based
			// extension filter - so a filter can still see and adjust the fully combined set.
			$layouts = self::get_base_layouts();
			$layouts = self::remove_disabled_layouts($layouts);
			$layouts = Site_Layouts::merge($layouts);
			$layouts = apply_filters('acbs/flexible_layout/layouts', $layouts);
			$layouts = Site_Layouts::sort($layouts);

			return $layouts;

		}

		/**
		 * register function
		 *
		 * @version 1.0.25
		 * @since   1.0.6
		 */
		public static function register() {

			$layouts = self::get_current_layouts();

			// Pages using the plugin's own "Page Builder" template, and nothing else.
			//
			// This replaces the previous location - every public post type and every public
			// taxonomy - which put the builder on every edit screen on the site whether or not
			// anything rendered it. Rows only render where something calls acbs_render_rows(),
			// and the template that does is the one this rule names, so the field group now
			// appears exactly where it has an effect.
			//
			// `page_template` is ACF's own rule and its object_subtype is 'page', so restricting
			// the group to the page post type falls out of the rule rather than needing a
			// post_type rule beside it. It reads $screen['page_template'] before falling back to
			// the saved _wp_page_template meta (see ACF_Location_Post_Template::match()), which
			// means the builder appears and disappears as an editor switches template in the
			// dropdown, with no save in between.
			//
			// The value comes from wp_get_theme()->get_page_templates(), which runs the
			// `theme_page_templates` filter - the same filter Page_Template registers through -
			// so ACF sees our entry even though no file in the theme declares it.
			//
			// Widen this per site through the filter below rather than editing this file.
			$location = [
				[
					[
						'param' => 'page_template',
						'operator' => '==',
						'value' => Page_Template::SLUG,
					],
				],
			];

			$location = apply_filters('acbs/flexible_layout/location', $location);

			acf_add_local_field_group([
				'key' => self::GROUP_KEY,
				'title' => 'Page Content',
				'fields' => [
					[
						// DO NOT CHANGE THIS KEY.
						//
						// Every post that has ever saved page_sections carries a `_page_sections`
						// postmeta row holding this exact key, and ACF resolves the field from
						// that reference. There is no working fallback: with the reference
						// dangling, get_field('page_sections') returns the raw layout-name array
						// instead of formatted rows, so every existing page silently stops
						// rendering (verified against real content before this was pinned).
						//
						// Every OTHER key in this file - layouts and sub fields alike - was
						// regenerated so the plugin can no longer collide with the identical keys
						// older sites have in their own database, which is safe precisely because
						// sub-field references are resolved by name once this parent resolves.
						'key' => self::FIELD_KEY,
						'label' => '',
						'name' => 'page_sections',
						'type' => 'flexible_content',
						'layouts' => $layouts,
						'button_label' => 'Add Section',
					],
				],
				'location' => $location,
				'position' => 'acf_after_title',
				'style' => 'seamless',
				// The rows ARE the page's content, so the post content editor above them is a
				// second, competing place to put content that nothing on the front end renders -
				// the Page Builder template calls acbs_render_rows() and never the_content().
				//
				// Worth knowing: ACF implements hide_on_screen by emitting CSS against classic
				// editor metabox ids (`#postdivrich` here - see acf_get_field_group_style()), so
				// this takes effect in the classic editor only. It is inert under the block
				// editor, where that element does not exist.
				'hide_on_screen' => ['the_content'],
			]);

		}

	}
