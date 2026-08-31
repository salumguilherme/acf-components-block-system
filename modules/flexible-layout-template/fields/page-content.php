<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

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
		 * `erdc/flexible_layout/layouts` filter, without editing this file.
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
				'layout_62a2d618d2d25' => [
					'key' => 'layout_62a2d618d2d25',
					'label' => 'Content and Image - 2 Columns',
					'name' => 'content_left_image_right',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_605090e968af8',
							'label' => 'Hero Image',
							'name' => 'image',
							'type' => 'image',
							'required' => 1,
							'return_format' => 'array',
							'preview_size' => 'thumbnail',
						],
						[
							'key' => 'field_9014150923f43',
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
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_448e208b76962',
							'label' => 'Written Content',
							'name' => 'written_content',
							'type' => 'wysiwyg',
							'required' => 1,
						],
						[
							'key' => 'field_02bc4ebd7cea2',
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
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_4094ef3febc6b',
							'label' => 'Buttons',
							'name' => 'buttons',
							'type' => 'clone',
							'clone' => [ 'group_b99bcf0767134' ],
							'display' => 'seamless',
							'layout' => 'block',
							'prefix_label' => 0,
							'prefix_name' => 0,
						],
					],
				],
				'layout_9c525e01eaa5d' => [
					'key' => 'layout_9c525e01eaa5d',
					'label' => 'Contact Page Form',
					'name' => 'contact_page_form',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_e6dba9007c988',
							'label' => 'Contact Form ID',
							'name' => 'contact_form_id',
							'type' => 'number',
							'instructions' => 'Gravity Forms ID',
							'required' => 1,
						],
					],
				],
				'layout_e5135c453a824' => [
					'key' => 'layout_e5135c453a824',
					'label' => 'Content - Full Width',
					'name' => 'full_width_content',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_0b99917417027',
							'label' => 'Content Alignment',
							'name' => 'alignment',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								'default' => 'Default (Center)',
								'left' => 'Left',
								'right' => 'Right',
							],
							'default_value' => 'default',
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_96e0153b027c4',
							'label' => 'Written Content',
							'name' => 'written_content',
							'type' => 'wysiwyg',
							'required' => 1,
						],
						[
							'key' => 'field_10fd858b1b508',
							'label' => 'Block Background',
							'name' => 'block_background',
							'type' => 'button_group',
							'choices' => [
								'none' => 'None',
								'light' => 'Light',
								'primary' => 'Pink',
								'secondary' => 'Black',
							],
							'default_value' => 'none',
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_909e830a51e71',
							'label' => 'Buttons',
							'name' => 'buttons',
							'type' => 'clone',
							'clone' => [ 'group_b99bcf0767134' ],
							'display' => 'seamless',
							'layout' => 'block',
							'prefix_label' => 0,
							'prefix_name' => 0,
						],
					],
				],
				'layout_10ef365464ad7' => [
					'key' => 'layout_10ef365464ad7',
					'label' => 'Columned Content',
					'name' => 'columned_content',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_d72aad201ccbe',
							'label' => 'Layout Columns',
							'name' => 'layout_columns',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								1 => '1',
								2 => '2',
								3 => '3',
								4 => '4',
								5 => '5',
								6 => '6',
							],
							'default_value' => 4,
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_1b65bac2d132a',
							'label' => 'Grid Alignment',
							'name' => 'columns_alignment',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								'default' => 'Default (left)',
								'center' => 'Center',
								'right' => 'Right',
							],
							'default_value' => 'default',
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_6290895f79db7',
							'label' => 'Columns',
							'name' => 'columns',
							'type' => 'repeater',
							'required' => 1,
							'layout' => 'block',
							'min' => 1,
							'max' => 0,
							'button_label' => 'Add Column',
							'sub_fields' => [
								[
									'key' => 'field_cca1b86660eb5',
									'label' => 'Content',
									'name' => 'content',
									'type' => 'wysiwyg',
									'required' => 1,
								],
								[
									'key' => 'field_39471707ed726',
									'label' => 'Column Content Alignment',
									'name' => 'column_alignment',
									'type' => 'button_group',
									'required' => 1,
									'choices' => [
										'default' => 'Default (left)',
										'center' => 'Center',
										'right' => 'Right',
									],
									'default_value' => 'default',
									'return_format' => 'value',
									'layout' => 'horizontal',
								],
								[
									'key' => 'field_df269ba818b74',
									'label' => 'Buttons',
									'name' => 'buttons',
									'type' => 'clone',
									'clone' => [ 'group_b99bcf0767134' ],
									'display' => 'seamless',
									'layout' => 'block',
									'prefix_label' => 0,
									'prefix_name' => 0,
								],
							],
						],
					],
				],
				'layout_8ac2f0e38eac3' => [
					'key' => 'layout_8ac2f0e38eac3',
					'label' => 'Full Width Content with Read More',
					'name' => 'full_width_content_with_read_more',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_9c4583062682b',
							'label' => 'Content Alignment',
							'name' => 'alignment',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								'default' => 'Default (Center)',
								'left' => 'Left',
								'right' => 'Right',
							],
							'default_value' => 'default',
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_869dc1f32c8ad',
							'label' => 'Written Content',
							'name' => 'written_content',
							'type' => 'wysiwyg',
							'required' => 1,
						],
						[
							'key' => 'field_916a9fce1b315',
							'label' => 'Toggled Content',
							'name' => 'toggled_content',
							'type' => 'wysiwyg',
							'required' => 1,
						],
						[
							'key' => 'field_5d45ef784fa47',
							'label' => 'Read More Button Text',
							'name' => 'read_more_button_text',
							'type' => 'text',
							'required' => 1,
							'default_value' => 'Read more',
						],
						[
							'key' => 'field_b3f159c9acff0',
							'label' => 'Block Background',
							'name' => 'block_background',
							'type' => 'button_group',
							'choices' => [
								'none' => 'None',
								'light' => 'Light',
								'primary' => 'Pink',
								'secondary' => 'Black',
							],
							'default_value' => 'none',
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_22e88aa06c62d',
							'label' => 'Buttons',
							'name' => 'buttons',
							'type' => 'clone',
							'clone' => [ 'group_b99bcf0767134' ],
							'display' => 'seamless',
							'layout' => 'block',
							'prefix_label' => 0,
							'prefix_name' => 0,
						],
					],
				],
				'layout_415d92edf68ec' => [
					'key' => 'layout_415d92edf68ec',
					'label' => 'Full Width Image',
					'name' => 'full_width_image',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_7e539163797f2',
							'label' => 'image',
							'name' => 'image',
							'type' => 'image',
							'instructions' => 'This image spans the full width of the page. If a height is set the image is cropped and stays centred on the page. If no height, the image resizes in proportion to browsers window.',
							'required' => 1,
							'return_format' => 'array',
							'preview_size' => 'thumbnail',
						],
						[
							'key' => 'field_b7ca3f9027bb5',
							'label' => 'Height (Desktop)',
							'name' => 'height_desktop',
							'type' => 'number',
						],
						[
							'key' => 'field_1c102456eaea9',
							'label' => 'Height (Tablet)',
							'name' => 'height_tablet',
							'type' => 'number',
						],
						[
							'key' => 'field_c376a7b751a51',
							'label' => 'Height (Mobile)',
							'name' => 'height_mobile',
							'type' => 'number',
						],
					],
				],
				'layout_e492592d9efe2' => [
					'key' => 'layout_e492592d9efe2',
					'label' => 'Full Width Image CTA',
					'name' => 'full_width_image_cta',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_3be8724e67c52',
							'label' => 'Image',
							'name' => 'image_bg',
							'type' => 'image',
							'required' => 1,
							'return_format' => 'array',
							'min_width' => 1440,
							'preview_size' => 'thumbnail',
						],
						[
							'key' => 'field_cb076f535b1d4',
							'label' => 'Content',
							'name' => 'content',
							'type' => 'wysiwyg',
						],
						[
							'key' => 'field_738d2fbf6442f',
							'label' => 'Buttons',
							'name' => 'buttons',
							'type' => 'clone',
							'clone' => [ 'group_b99bcf0767134' ],
							'display' => 'seamless',
							'layout' => 'block',
							'prefix_label' => 0,
							'prefix_name' => 0,
						],
					],
				],
				'layout_9fe9e7341fa31' => [
					'key' => 'layout_9fe9e7341fa31',
					'label' => 'Icon Leaders',
					'name' => 'icon_leaders',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_c47048ac9cde8',
							'label' => 'Source',
							'name' => 'source',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								'repeater' => 'Repeater Field',
								'taxonomy' => 'Taxonomy',
								'post_type' => 'Post Type',
							],
							'default_value' => 'repeater',
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_e480d6695a2a0',
							'label' => 'Icon Leaders',
							'name' => 'icon_leaders',
							'type' => 'repeater',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_c47048ac9cde8',
										'operator' => '==',
										'value' => 'repeater',
									],
								],
							],
							'layout' => 'row',
							'min' => 0,
							'max' => 0,
							'button_label' => 'Add Row',
							'sub_fields' => [
								[
									'key' => 'field_3323d21e045a2',
									'label' => 'Icon',
									'name' => 'icon',
									'type' => 'image',
									'required' => 1,
									'return_format' => 'array',
									'mime_types' => 'svg',
									'preview_size' => 'thumbnail',
								],
								[
									'key' => 'field_780d4735efea1',
									'label' => 'Title',
									'name' => 'title',
									'type' => 'text',
								],
								[
									'key' => 'field_8e920e805af9c',
									'label' => 'Content',
									'name' => 'content',
									'type' => 'textarea',
								],
								[
									'key' => 'field_e03c2d56c8dc6',
									'label' => 'Link URL',
									'name' => 'link_url',
									'type' => 'text',
									'instructions' => 'Leave blank to not link',
								],
							],
						],
						[
							'key' => 'field_403dff56b7aa4',
							'label' => 'Taxonomy',
							'name' => 'icon_taxonomy',
							'type' => 'select',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_c47048ac9cde8',
										'operator' => '==',
										'value' => 'taxonomy',
									],
								],
							],
							'choices' => [
								'category' => 'Categories',
								'post_tag' => 'Tags',
								'post_format' => 'Formats',
								'product_brand' => 'Brands',
								'product_cat' => 'Product categories',
								'product_tag' => 'Product tags',
								'product_shipping_class' => 'Product shipping classes',
								'pa_who-we-work-with' => 'Product Solution',
								'project-type' => 'Types',
								'project-style' => 'Styles',
								'project-size' => 'Sizes',
								'project-features' => 'Features',
								'faq-tag' => 'Tags',
							],
							'return_format' => 'value',
						],
						[
							'key' => 'field_6c78cbeb5b2d0',
							'label' => 'Terms',
							'name' => 'icon_terms',
							'type' => 'taxonomy',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_c47048ac9cde8',
										'operator' => '==',
										'value' => 'taxonomy',
									],
								],
							],
							'taxonomy' => 'category',
							'return_format' => 'id',
							'field_type' => 'multi_select',
						],
						[
							'key' => 'field_e23707a1e766f',
							'label' => 'Post Type',
							'name' => 'icon_post_type',
							'type' => 'select',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_c47048ac9cde8',
										'operator' => '==',
										'value' => 'post_type',
									],
								],
							],
							'choices' => [
								'post' => 'Posts',
							],
							'return_format' => 'value',
						],
						[
							'key' => 'field_4bd1752b9b882',
							'label' => 'Posts',
							'name' => 'icon_posts',
							'type' => 'post_object',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_c47048ac9cde8',
										'operator' => '==',
										'value' => 'post_type',
									],
								],
							],
							'post_type' => '',
							'post_status' => ['publish'],
							'taxonomy' => '',
							'return_format' => 'id',
							'multiple' => 1,
							'allow_null' => 0,
							'ui' => 1,
						],
						[
							'key' => 'field_6ea4c72aaa177',
							'label' => 'Layout Columns',
							'name' => 'layout_columns',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								1 => '1',
								2 => '2',
								3 => '3',
								4 => '4',
								5 => '5',
								6 => '6',
							],
							'default_value' => 4,
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_09bef940c47d0',
							'label' => 'Columns Alignment',
							'name' => 'columns_alignment',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								'default' => 'Default (left)',
								'center' => 'Center',
								'right' => 'Right',
							],
							'default_value' => 'default',
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
					],
				],
				'layout_24713f678159f' => [
					'key' => 'layout_24713f678159f',
					'label' => 'Image Cards Simple Grid',
					'name' => 'image_cards_grid',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_0d0b3d6e270f0',
							'label' => 'Source',
							'name' => 'source',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								'repeater' => 'Repeater Field',
								'taxonomy' => 'Taxonomy',
								'post_type' => 'Post Type',
							],
							'default_value' => 'repeater',
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_f9858cb070ddd',
							'label' => 'Cards',
							'name' => 'cards',
							'type' => 'repeater',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_0d0b3d6e270f0',
										'operator' => '==',
										'value' => 'repeater',
									],
								],
							],
							'layout' => 'row',
							'min' => 1,
							'max' => 0,
							'button_label' => 'Add Row',
							'sub_fields' => [
								[
									'key' => 'field_6fc34dba4c8c3',
									'label' => 'Image',
									'name' => 'image',
									'type' => 'image',
									'instructions' => '1 : 1 aspect ratio',
									'required' => 1,
									'return_format' => 'url',
									'preview_size' => 'thumbnail',
								],
								[
									'key' => 'field_27b5105b4836d',
									'label' => 'Title',
									'name' => 'title',
									'type' => 'text',
									'required' => 1,
								],
								[
									'key' => 'field_ea147cd2b891b',
									'label' => 'Link URL',
									'name' => 'link_url',
									'type' => 'text',
									'instructions' => 'Leave blank to not link',
								],
							],
						],
						[
							'key' => 'field_1445c55d22a50',
							'label' => 'Taxonomy',
							'name' => 'taxonomy',
							'type' => 'select',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_0d0b3d6e270f0',
										'operator' => '==',
										'value' => 'taxonomy',
									],
								],
							],
							'choices' => [
								'category' => 'Categories',
								'post_tag' => 'Tags',
								'post_format' => 'Formats',
								'product_brand' => 'Brands',
								'product_cat' => 'Product categories',
								'product_tag' => 'Product tags',
								'product_shipping_class' => 'Product shipping classes',
								'pa_who-we-work-with' => 'Product Solution',
								'project-type' => 'Types',
								'project-style' => 'Styles',
								'project-size' => 'Sizes',
								'project-features' => 'Features',
								'faq-tag' => 'Tags',
							],
							'return_format' => 'value',
						],
						[
							'key' => 'field_ffcef58b1d6ca',
							'label' => 'Terms',
							'name' => 'terms',
							'type' => 'taxonomy',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_0d0b3d6e270f0',
										'operator' => '==',
										'value' => 'taxonomy',
									],
								],
							],
							'taxonomy' => 'category',
							'return_format' => 'id',
							'field_type' => 'multi_select',
						],
						[
							'key' => 'field_9c255d6ac8676',
							'label' => 'Post Type',
							'name' => 'post_type',
							'type' => 'select',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_0d0b3d6e270f0',
										'operator' => '==',
										'value' => 'post_type',
									],
								],
							],
							'choices' => [
								'post' => 'Posts',
							],
							'return_format' => 'value',
						],
						[
							'key' => 'field_442f28d244b0c',
							'label' => 'Posts',
							'name' => 'posts',
							'type' => 'post_object',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_0d0b3d6e270f0',
										'operator' => '==',
										'value' => 'post_type',
									],
								],
							],
							'post_type' => '',
							'post_status' => ['publish'],
							'taxonomy' => '',
							'return_format' => 'id',
							'multiple' => 1,
							'allow_null' => 0,
							'ui' => 1,
						],
						[
							'key' => 'field_00daad5bcaa3b',
							'label' => 'Layout Columns',
							'name' => 'layout_columns',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								1 => '1',
								2 => '2',
								3 => '3',
								4 => '4',
								5 => '5',
								6 => '6',
							],
							'default_value' => 4,
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_f17aaaa23b2ea',
							'label' => 'Columns Alignment',
							'name' => 'columns_alignment',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								'default' => 'Default (left)',
								'center' => 'Center',
								'right' => 'Right',
							],
							'default_value' => 'default',
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
					],
				],
				'layout_46b6710f8e4e5' => [
					'key' => 'layout_46b6710f8e4e5',
					'label' => 'Image Cards With Sublinks Grid',
					'name' => 'image_cards_with_sublinks_grid',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_59d61394b6304',
							'label' => 'Cards',
							'name' => 'cards',
							'type' => 'repeater',
							'required' => 1,
							'layout' => 'row',
							'min' => 1,
							'max' => 0,
							'button_label' => 'Add Row',
							'sub_fields' => [
								[
									'key' => 'field_aa5cf2d26913a',
									'label' => 'Image',
									'name' => 'image',
									'type' => 'image',
									'instructions' => 'Landscape images preferred',
									'required' => 1,
									'return_format' => 'array',
									'preview_size' => 'thumbnail',
								],
								[
									'key' => 'field_9e63e01654b4e',
									'label' => 'Title',
									'name' => 'title',
									'type' => 'text',
									'required' => 1,
								],
								[
									'key' => 'field_81d5dc95eda01',
									'label' => 'Link URL',
									'name' => 'link_url',
									'type' => 'text',
									'instructions' => 'Leave blank to not link',
								],
								[
									'key' => 'field_156c9e0c5f1e1',
									'label' => 'Sublinks',
									'name' => 'sublinks',
									'type' => 'repeater',
									'layout' => 'table',
									'min' => 0,
									'max' => 0,
									'button_label' => 'Add Sublink',
									'sub_fields' => [
										[
											'key' => 'field_1aa1a8dc85d7d',
											'label' => 'Link Text',
											'name' => 'text',
											'type' => 'text',
											'required' => 1,
										],
										[
											'key' => 'field_3305593ec49fa',
											'label' => 'Link Url',
											'name' => 'link_url',
											'type' => 'text',
											'required' => 1,
										],
									],
								],
							],
						],
						[
							'key' => 'field_78ee33f07f67f',
							'label' => 'Layout Columns',
							'name' => 'layout_columns',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								1 => '1',
								2 => '2',
								3 => '3',
								4 => '4',
								5 => '5',
								6 => '6',
							],
							'default_value' => 4,
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_64ec7218cbfb2',
							'label' => 'Columns Alignment',
							'name' => 'columns_alignment',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								'default' => 'Default (left)',
								'center' => 'Center',
								'right' => 'Right',
							],
							'default_value' => 'default',
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
					],
				],
				'layout_87790ef7fbdda' => [
					'key' => 'layout_87790ef7fbdda',
					'label' => 'Image Cards Multi Grid',
					'name' => 'image_cards_multi_grid',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_a72e6afdbe6bd',
							'label' => 'Source',
							'name' => 'source',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								'repeater' => 'Repeater Field',
								'taxonomy' => 'Taxonomy',
								'post_type' => 'Post Type',
							],
							'default_value' => 'repeater',
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_957515051058e',
							'label' => 'Featured Card',
							'name' => 'featured_card',
							'type' => 'repeater',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_a72e6afdbe6bd',
										'operator' => '==',
										'value' => 'repeater',
									],
								],
							],
							'layout' => 'row',
							'min' => 1,
							'max' => 1,
							'button_label' => 'Add Row',
							'sub_fields' => [
								[
									'key' => 'field_bace2e355d93d',
									'label' => 'Image Type',
									'name' => 'image_type',
									'type' => 'button_group',
									'required' => 1,
									'choices' => [
										'full' => 'Full Width',
										'cropped' => 'Cropped Top Right Alignment',
									],
									'default_value' => 'full',
									'return_format' => 'value',
									'layout' => 'horizontal',
								],
								[
									'key' => 'field_ea672d5e380d5',
									'label' => 'Image',
									'name' => 'image',
									'type' => 'image',
									'instructions' => '1 : 1 aspect ratio',
									'required' => 1,
									'return_format' => 'array',
									'preview_size' => 'thumbnail',
								],
								[
									'key' => 'field_b4e5e69e72ce3',
									'label' => 'Title',
									'name' => 'title',
									'type' => 'text',
									'required' => 1,
								],
								[
									'key' => 'field_e7a4c0ca821ed',
									'label' => 'Short Description',
									'name' => 'description',
									'type' => 'textarea',
								],
								[
									'key' => 'field_059aca29d7853',
									'label' => 'Link URL',
									'name' => 'link_url',
									'type' => 'text',
									'instructions' => 'Leave blank to not link',
								],
							],
						],
						[
							'key' => 'field_f3cbae58c16d6',
							'label' => 'Cards',
							'name' => 'cards',
							'type' => 'repeater',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_a72e6afdbe6bd',
										'operator' => '==',
										'value' => 'repeater',
									],
								],
							],
							'layout' => 'row',
							'min' => 1,
							'max' => 0,
							'button_label' => 'Add Row',
							'sub_fields' => [
								[
									'key' => 'field_99d39227b72fa',
									'label' => 'Image',
									'name' => 'image',
									'type' => 'image',
									'instructions' => '1 : 1 aspect ratio',
									'required' => 1,
									'return_format' => 'array',
									'preview_size' => 'thumbnail',
								],
								[
									'key' => 'field_03879b7291ca8',
									'label' => 'Title',
									'name' => 'title',
									'type' => 'text',
									'required' => 1,
								],
								[
									'key' => 'field_e00e8b8851c44',
									'label' => 'Link URL',
									'name' => 'link_url',
									'type' => 'text',
									'instructions' => 'Leave blank to not link',
								],
							],
						],
						[
							'key' => 'field_0d19f13fb9dc3',
							'label' => 'Taxonomy',
							'name' => 'taxonomy',
							'type' => 'select',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_a72e6afdbe6bd',
										'operator' => '==',
										'value' => 'taxonomy',
									],
								],
							],
							'choices' => [
								'category' => 'Categories',
								'post_tag' => 'Tags',
								'post_format' => 'Formats',
								'product_brand' => 'Brands',
								'product_cat' => 'Product categories',
								'product_tag' => 'Product tags',
								'product_shipping_class' => 'Product shipping classes',
								'pa_who-we-work-with' => 'Product Solution',
								'project-type' => 'Types',
								'project-style' => 'Styles',
								'project-size' => 'Sizes',
								'project-features' => 'Features',
								'faq-tag' => 'Tags',
							],
							'return_format' => 'value',
						],
						[
							'key' => 'field_faf8e580d547f',
							'label' => 'Featured Term',
							'name' => 'featured_term',
							'type' => 'taxonomy',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_a72e6afdbe6bd',
										'operator' => '==',
										'value' => 'taxonomy',
									],
								],
							],
							'taxonomy' => 'category',
							'return_format' => 'id',
							'field_type' => 'select',
						],
						[
							'key' => 'field_92ac688424e39',
							'label' => 'Terms',
							'name' => 'terms',
							'type' => 'taxonomy',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_a72e6afdbe6bd',
										'operator' => '==',
										'value' => 'taxonomy',
									],
								],
							],
							'taxonomy' => 'category',
							'return_format' => 'id',
							'field_type' => 'multi_select',
						],
						[
							'key' => 'field_a06e74ceab19b',
							'label' => 'Post Type',
							'name' => 'post_type',
							'type' => 'select',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_a72e6afdbe6bd',
										'operator' => '==',
										'value' => 'post_type',
									],
								],
							],
							'choices' => [
								'post' => 'Posts',
							],
							'return_format' => 'value',
						],
						[
							'key' => 'field_aee1c8edee822',
							'label' => 'Featured Post',
							'name' => 'featured_post',
							'type' => 'post_object',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_a72e6afdbe6bd',
										'operator' => '==',
										'value' => 'post_type',
									],
								],
							],
							'post_type' => '',
							'post_status' => ['publish'],
							'taxonomy' => '',
							'return_format' => 'id',
							'multiple' => 0,
							'allow_null' => 0,
							'ui' => 1,
						],
						[
							'key' => 'field_89a44260a4ad5',
							'label' => 'Posts',
							'name' => 'posts',
							'type' => 'post_object',
							'required' => 1,
							'conditional_logic' => [
								[
									[
										'field' => 'field_a72e6afdbe6bd',
										'operator' => '==',
										'value' => 'post_type',
									],
								],
							],
							'post_type' => '',
							'post_status' => ['publish'],
							'taxonomy' => '',
							'return_format' => 'id',
							'multiple' => 1,
							'allow_null' => 0,
							'ui' => 1,
						],
						[
							'key' => 'field_7c2f089221be4',
							'label' => 'Background Detail Variation',
							'name' => 'svg_bg',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								'var-1' => 'Variation 1',
								'var-2' => 'Variation 2',
								'var-3' => 'Variation 3',
							],
							'default_value' => 'var-1',
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_ee0ba71ddb28b',
							'label' => 'Featured Card Alignment',
							'name' => 'feature_align',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								'fs-feature-align-left' => 'Left',
								'fs-feature-align-right' => 'Right',
							],
							'default_value' => 'fs-feature-align-left',
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
					],
				],
				'layout_56d50e6ff4793' => [
					'key' => 'layout_56d50e6ff4793',
					'label' => 'Image Gallery',
					'name' => 'image_gallery',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_3eae489152606',
							'label' => 'Images',
							'name' => 'images',
							'type' => 'gallery',
							'required' => 1,
							'return_format' => 'array',
							'insert' => 'append',
							'preview_size' => 'medium',
						],
					],
				],
				'layout_90ce1263718e1' => [
					'key' => 'layout_90ce1263718e1',
					'label' => 'Logo Gallery',
					'name' => 'logo_gallery',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_7447e9e43df85',
							'label' => 'Logos',
							'name' => 'logos',
							'type' => 'gallery',
							'required' => 1,
							'return_format' => 'array',
							'insert' => 'append',
							'preview_size' => 'thumbnail',
						],
						[
							'key' => 'field_a48bbf068f50f',
							'label' => 'Columns',
							'name' => 'columns',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								1 => '1',
								2 => '2',
								3 => '3',
								4 => '4',
								5 => '5',
								6 => '6',
								7 => '7',
								8 => '8',
							],
							'default_value' => 7,
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
					],
				],
				'layout_fe97a16efab27' => [
					'key' => 'layout_fe97a16efab27',
					'label' => 'Team Members Grid',
					'name' => 'team_members_grid',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_59364396a7e55',
							'label' => 'Team Members',
							'name' => 'team_members',
							'type' => 'repeater',
							'required' => 1,
							'layout' => 'block',
							'min' => 1,
							'max' => 0,
							'collapsed' => 'field_037da203997a8',
							'button_label' => 'Add Team Member',
							'sub_fields' => [
								[
									'key' => 'field_ed6cf7cc7045c',
									'label' => 'Profile Picture',
									'name' => 'profile_picture',
									'type' => 'image',
									'instructions' => '1 : 1 Square ratio',
									'return_format' => 'array',
									'preview_size' => 'thumbnail',
								],
								[
									'key' => 'field_037da203997a8',
									'label' => 'Name',
									'name' => 'name',
									'type' => 'text',
									'required' => 1,
								],
								[
									'key' => 'field_95f1a3d009181',
									'label' => 'Position',
									'name' => 'position',
									'type' => 'text',
									'required' => 1,
								],
								[
									'key' => 'field_3186f5e4fcb5d',
									'label' => 'Bio',
									'name' => 'bio',
									'type' => 'wysiwyg',
								],
							],
						],
						
						[
							'key' => 'field_d82aad201ccbe',
							'label' => 'Layout Columns',
							'name' => 'layout_columns',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								1 => '1',
								2 => '2',
								3 => '3',
								4 => '4',
								5 => '5',
								6 => '6',
							],
							'default_value' => 4,
							'return_format' => 'value',
							'layout' => 'horizontal',
						],
						[
							'key' => 'field_1c65bac2d132a',
							'label' => 'Grid Alignment',
							'name' => 'columns_alignment',
							'type' => 'button_group',
							'required' => 1,
							'choices' => [
								'default' => 'Default (left)',
								'center' => 'Center',
								'right' => 'Right',
							],
							'default_value' => 'default',
							'return_format' => 'value',
							'layout' => 'horizontal',
						]
					],
				],
				'layout_ecdc48d38461d' => [
					'key' => 'layout_ecdc48d38461d',
					'label' => 'Testimonials',
					'name' => 'testimonial',
					'display' => 'row',
					'min' => '',
					'max' => '',
					'sub_fields' => [
						[
							'key' => 'field_6d64cf98c7b06',
							'label' => 'Testimonials',
							'name' => 'testimonials',
							'type' => 'repeater',
							'layout' => 'table',
							'min' => 0,
							'max' => 0,
							'button_label' => 'Add Row',
							'sub_fields' => [
								[
									'key' => 'field_ffb3093373433',
									'label' => 'Testimonial Content',
									'name' => 'testimonial_content',
									'type' => 'wysiwyg',
									'required' => 1,
								],
								[
									'key' => 'field_6f36c2b17aacd',
									'label' => 'Testimonial Author',
									'name' => 'testimonial_author',
									'type' => 'text',
								],
								[
									'key' => 'field_2bda20f4b92ba',
									'label' => 'Author Role',
									'name' => 'testimonial_author_role',
									'type' => 'text',
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
		 * then the code-based `erdc/flexible_layout/layouts` extension filter, then sorted.
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
			$layouts = apply_filters('erdc/flexible_layout/layouts', $layouts);
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

			// Broad, cross-site default location - every public post type and taxonomy - so a
			// fresh install of the plugin works everywhere out of the box. Narrow this per site
			// via the `erdc/flexible_layout/location` filter (e.g. from a future per-site admin
			// panel) rather than editing this file.
			$location = [];

			foreach(get_post_types(['public' => true]) as $post_type) {
				$location[] = [
					[
						'param' => 'post_type',
						'operator' => '==',
						'value' => $post_type,
					],
				];
			}

			foreach(get_taxonomies(['public' => true]) as $taxonomy) {
				$location[] = [
					[
						'param' => 'taxonomy',
						'operator' => '==',
						'value' => $taxonomy,
					],
				];
			}

			$location = apply_filters('erdc/flexible_layout/location', $location);

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
			]);

		}

	}
