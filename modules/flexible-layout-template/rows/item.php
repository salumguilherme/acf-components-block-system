<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Rows;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Item
	 *
	 * One thing in a row's grid, whatever it actually is underneath: a repeater row, a
	 * term, or a post.
	 *
	 * This exists so an item template does not open with a three-way branch. A card is a
	 * picture, a title, some words and maybe a link no matter where those came from, and
	 * three of the fifteen layouts let an editor switch between the three origins from a
	 * button group in the same row - so the branch, written once here, would otherwise be
	 * written again in every item template and every override a site writes.
	 *
	 * What it deliberately does NOT do is hold values. A repeater item reads through
	 * get_sub_field() at the moment it is asked, because its ACF loop is active while the
	 * item template renders and ACF's own accessors are the only thing that formats image
	 * arrays, link arrays and the Buttons/Intro field types. See CLAUDE.md section 05.
	 *
	 * The image accessors are the reason this class is worth having at all. `image` is
	 * declared `return_format => url` in image_cards_grid, `array` in
	 * image_cards_multi_grid and `array` (as `icon`) in icon_leaders, a term's image is an
	 * ACF field on the term, and a post's is its featured image - four shapes for the same
	 * idea. image_id(), image_url() and image_html() flatten all of them, and a URL-format
	 * field degrades honestly: there is no attachment id to recover from a URL, so
	 * image_id() returns 0 and image_html() falls back to a plain <img> with no srcset.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Rows
	 */
	class Item {

		/**
		 * A row of the layout's own repeater. Its ACF loop is active.
		 */
		const REPEATER = 'repeater';

		/**
		 * A WP_Term.
		 */
		const TERM = 'term';

		/**
		 * A WP_Post.
		 */
		const POST = 'post';

		/**
		 * @var string One of REPEATER, TERM, POST.
		 */
		private $kind;

		/**
		 * The underlying object: null for a repeater item (there is no object, only the
		 * active ACF loop), a WP_Term or a WP_Post otherwise.
		 *
		 * @var \WP_Term|\WP_Post|null
		 */
		private $object;

		/**
		 * Sub field name per role - 'image', 'title', 'link', 'content'. Comes from the
		 * layout's item spec; see Items_Source::spec().
		 *
		 * @var array
		 */
		private $map;

		/**
		 * 1-based position in the item list, for display only. Same caveat as
		 * Row::position(): a position is not an identity.
		 *
		 * @var int
		 */
		private $position;

		/**
		 * Constructor
		 *
		 * @param string                 $kind
		 * @param \WP_Term|\WP_Post|null $object
		 * @param array                  $map
		 * @param int                    $position
		 */
		public function __construct($kind, $object, array $map, $position = 0) {

			$this->kind = (string) $kind;
			$this->object = $object;
			$this->map = $map;
			$this->position = (int) $position;

		}

		/**
		 * kind function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function kind() {
			return $this->kind;
		}

		/**
		 * is function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $kind
		 *
		 * @return bool
		 */
		public function is($kind) {
			return $this->kind === $kind;
		}

		/**
		 * object function
		 *
		 * The WP_Term or WP_Post behind this item, for a template that needs something
		 * the accessors here do not cover. Null for a repeater item.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return \WP_Term|\WP_Post|null
		 */
		public function object() {
			return $this->object;
		}

		/**
		 * position function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return int
		 */
		public function position() {
			return $this->position;
		}

		/**
		 * title function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function title() {

			switch($this->kind) {

				case self::TERM:
					return $this->object instanceof \WP_Term ? $this->object->name : '';

				case self::POST:
					return $this->object instanceof \WP_Post ? get_the_title($this->object) : '';

			}

			return (string) $this->sub('title');

		}

		/**
		 * content function
		 *
		 * A term's description and a post's excerpt are the natural counterparts of a
		 * repeater item's own content field. Both can contain markup, so a template
		 * escapes or filters this the same way it would any other editor content.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function content() {

			switch($this->kind) {

				case self::TERM:
					return $this->object instanceof \WP_Term ? $this->object->description : '';

				case self::POST:
					return $this->object instanceof \WP_Post ? get_the_excerpt($this->object) : '';

			}

			return (string) $this->sub('content');

		}

		/**
		 * link function
		 *
		 * The item's URL, or '' for an item that should not be linked. A repeater item's
		 * link field is deliberately optional - every one of the three layouts labels it
		 * "Leave blank to not link" - so '' is a normal answer, not a failure.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function link() {

			switch($this->kind) {

				case self::TERM:

					if(!$this->object instanceof \WP_Term) {
						return '';
					}

					$link = get_term_link($this->object);

					// get_term_link() returns WP_Error for a term whose taxonomy has been
					// unregistered since the row was saved.
					return is_wp_error($link) ? '' : (string) $link;

				case self::POST:
					return $this->object instanceof \WP_Post ? (string) get_permalink($this->object) : '';

			}

			$link = $this->sub('link');

			// A repeater link field may be a plain text URL (all three core layouts) or an
			// ACF link array, if a site has swapped the field type through a contributor
			// group. Accept both.
			if(is_array($link)) {
				$link = $link['url'] ?? '';
			}

			return (string) $link;

		}

		/**
		 * link_target function
		 *
		 * Only ever non-empty for an ACF link array that carries one.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function link_target() {

			if(!$this->is(self::REPEATER)) {
				return '';
			}

			$link = $this->sub('link');

			return is_array($link) && !empty($link['target']) ? (string) $link['target'] : '';

		}

		/**
		 * has_link function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return bool
		 */
		public function has_link() {
			return '' !== $this->link();
		}

		/**
		 * image function
		 *
		 * The raw value, in whatever shape the underlying field returns - an ACF image
		 * array, an attachment id, or a URL string. A template normally wants image_html()
		 * or image_url() instead; this is here for one that needs something specific out
		 * of the array.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return mixed
		 */
		public function image() {

			switch($this->kind) {

				case self::TERM:

					if(!$this->object instanceof \WP_Term || !function_exists('get_field')) {
						return null;
					}

					return get_field($this->image_field(), $this->object);

				case self::POST:

					if(!$this->object instanceof \WP_Post) {
						return null;
					}

					$thumbnail = get_post_thumbnail_id($this->object);

					if($thumbnail) {
						return (int) $thumbnail;
					}

					// No featured image: fall back to an ACF field of the same name the
					// repeater uses, so a post type that carries its card image in a custom
					// field rather than the featured image still renders.
					return function_exists('get_field') ? get_field($this->image_field(), $this->object->ID) : null;

			}

			return $this->sub('image');

		}

		/**
		 * image_id function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return int 0 when there is no attachment id to be had - including for a field
		 *             declared `return_format => url`, where the id is genuinely gone.
		 */
		public function image_id() {

			$image = $this->image();

			if(is_array($image)) {
				return (int) ($image['ID'] ?? 0);
			}

			return is_numeric($image) ? (int) $image : 0;

		}

		/**
		 * image_url function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $size
		 *
		 * @return string
		 */
		public function image_url($size = 'large') {

			$id = $this->image_id();

			if($id) {
				return (string) wp_get_attachment_image_url($id, $size);
			}

			$image = $this->image();

			// A url-format field. The requested size cannot be honoured, because the size
			// lives in the attachment metadata and there is no id left to look it up with.
			return is_string($image) ? $image : '';

		}

		/**
		 * image_alt function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public function image_alt() {

			$image = $this->image();

			if(is_array($image) && isset($image['alt'])) {
				return (string) $image['alt'];
			}

			$id = $this->image_id();

			if($id) {
				return (string) get_post_meta($id, '_wp_attachment_image_alt', true);
			}

			// Nothing recorded. An alt that repeats the visible title is worse than an
			// empty one, so the caller gets '' and the card's own text carries the meaning.
			return '';

		}

		/**
		 * image_html function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $size
		 * @param array  $attr Extra attributes, e.g. ['class' => 'fl-card-image'].
		 *
		 * @return string '' when the item has no image, so a template can print this
		 *                unconditionally.
		 */
		public function image_html($size = 'large', array $attr = []) {

			$id = $this->image_id();

			if($id) {
				return wp_get_attachment_image($id, $size, false, $attr);
			}

			$url = $this->image_url($size);

			if('' === $url) {
				return '';
			}

			// No id, so no srcset and no intrinsic dimensions. Built by hand rather than
			// dropped, since a url-format field is a legitimate configuration.
			$attr = array_merge(['alt' => $this->image_alt(), 'loading' => 'lazy'], $attr);
			$out = '';

			foreach($attr as $name => $value) {
				$out .= ' '.esc_attr($name).'="'.esc_attr($value).'"';
			}

			return '<img src="'.esc_url($url).'"'.$out.'>';

		}

		/**
		 * sub function
		 *
		 * A mapped sub field of the active repeater row.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $role 'image', 'title', 'link' or 'content'.
		 *
		 * @return mixed
		 */
		public function sub($role) {

			$name = $this->map[$role] ?? '';

			if('' === $name || !function_exists('get_sub_field')) {
				return null;
			}

			return get_sub_field($name);

		}

		/**
		 * image_field function
		 *
		 * The field name to look for on a term or a post, taken from the same map the
		 * repeater uses - so a layout whose repeater calls its image `icon` looks for
		 * `icon` on the term too.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		private function image_field() {
			return (string) ($this->map['image'] ?? 'image');
		}

	}
