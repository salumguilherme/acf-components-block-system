<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Rows;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Items_Source
	 *
	 * Turns a row's `source` button group into something an item template can loop.
	 *
	 * Three of the fifteen layouts let an editor choose where a grid's contents come from:
	 * a repeater the editor fills in by hand, a set of taxonomy terms, or a set of posts.
	 * Only three - icon_leaders, image_cards_grid and image_cards_multi_grid.
	 * team_members_grid and testimonial iterate a plain repeater with no choice attached
	 * and have no business here; they call have_rows() directly.
	 *
	 * The repeater branch is not a query and must never become one. Inside an active
	 * page_sections row, have_rows('cards') is detected by ACF as a CHILD loop of that
	 * row - flexible content's own acf/get_sub_field filter resolves the sub field against
	 * the active layout - so the repeater is already loaded, already formatted and already
	 * in scope. Reading it any other way would hydrate it a second time and lose the
	 * formatting. Verified against ACF Pro's api-template.php and
	 * class-acf-field-flexible-content.php. See CLAUDE.md section 05.3.
	 *
	 * Note that have_rows() is called here WITHOUT a post id, which is the form that
	 * triggers ACF's child-loop detection: the first branch of have_rows() moves down a
	 * level only when `empty($_post_id)` and the sub field exists. Passing the row's own
	 * source would usually work by a different path, and would break the moment the
	 * rendered source differs from the global post - rendering one page's rows inside
	 * another's template, for instance.
	 *
	 * USAGE
	 *
	 *     $items = Items_Source::for_row($row);
	 *
	 *     while($items->have_item()) {
	 *         $item = $items->the_item();
	 *         echo esc_html($item->title());
	 *     }
	 *
	 * deliberately shaped like ACF's own loop, or, for the common case of one partial per
	 * item:
	 *
	 *     Items_Source::for_row($row)->render();
	 *
	 * Breaking out of the while early is safe. In repeater mode it leaves a half-consumed
	 * loop on ACF's stack exactly as any other early exit would, and the Wrapper unwinds
	 * the stack back to its entry depth after the row's template returns - which is what
	 * that guard is for. Calling reset_rows() before the break is still the polite thing
	 * to do, and under WP_DEBUG the wrapper will tell you if you did not.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Rows
	 */
	class Items_Source {

		/**
		 * Per-layout specs for the layouts that ship with the plugin.
		 *
		 * Held here rather than in page-content.php because they describe how to READ the
		 * fields, not what the fields are, and page-content.php is already 1,590 lines of
		 * definition. Held here rather than in three Row_Type subclasses because a subclass
		 * whose entire body is a config array is a file that silently drifts out of step
		 * with the definitions it mirrors.
		 *
		 * Shape:
		 *
		 *   source     string  Field name of the button group selecting the mode.
		 *   taxonomy   string  Field name holding the taxonomy name, for mode 'taxonomy'.
		 *   post_type  string  Field name holding the post type, for mode 'post_type'.
		 *   map        array   Repeater sub field name per role, for Item.
		 *   variants   array   One entry per set of items the layout renders, each naming
		 *                      the repeater / terms / posts field for that set. Layouts
		 *                      with one grid have only 'items'; image_cards_multi_grid also
		 *                      has 'featured', whose fields hold a single value rather than
		 *                      a list.
		 *
		 * A layout absent from here still works: default_spec() derives the conventional
		 * names, which is what a site's own contributed layout will usually follow anyway.
		 */
		const DEFAULTS = [

			'image_cards_grid' => [
				'source' => 'source',
				'taxonomy' => 'taxonomy',
				'post_type' => 'post_type',
				'map' => [
					'image' => 'image',
					'title' => 'title',
					'link' => 'link_url',
					'content' => '',
				],
				'variants' => [
					'items' => [
						'repeater' => 'cards',
						'terms' => 'terms',
						'posts' => 'posts',
					],
				],
			],

			'icon_leaders' => [
				'source' => 'source',
				'taxonomy' => 'icon_taxonomy',
				'post_type' => 'icon_post_type',
				'map' => [
					'image' => 'icon',
					'title' => 'title',
					'link' => 'link_url',
					'content' => 'content',
				],
				'variants' => [
					'items' => [
						'repeater' => 'icon_leaders',
						'terms' => 'icon_terms',
						'posts' => 'icon_posts',
					],
				],
			],

			'image_cards_multi_grid' => [
				'source' => 'source',
				'taxonomy' => 'taxonomy',
				'post_type' => 'post_type',
				'map' => [
					'image' => 'image',
					'title' => 'title',
					'link' => 'link_url',
					'content' => 'description',
				],
				'variants' => [
					'featured' => [
						'repeater' => 'featured_card',
						'terms' => 'featured_term',
						'posts' => 'featured_post',
					],
					'items' => [
						'repeater' => 'cards',
						'terms' => 'terms',
						'posts' => 'posts',
					],
				],
			],

		];

		/**
		 * @var Row
		 */
		private $row;

		/**
		 * @var array
		 */
		private $spec;

		/**
		 * Which set of items this instance iterates - a key of the spec's `variants`.
		 *
		 * @var string
		 */
		private $variant;

		/**
		 * 'repeater', 'taxonomy' or 'post_type'.
		 *
		 * @var string
		 */
		private $mode;

		/**
		 * The resolved WP_Term or WP_Post list, for the two query modes. Null until built,
		 * and never built at all in repeater mode.
		 *
		 * @var array|null
		 */
		private $objects = null;

		/**
		 * Cursor into $objects.
		 *
		 * @var int
		 */
		private $index = 0;

		/**
		 * How many items have been handed out, 1-based, for Item::position().
		 *
		 * @var int
		 */
		private $position = 0;

		/**
		 * Constructor
		 *
		 * @param Row    $row
		 * @param array  $spec
		 * @param string $variant
		 */
		public function __construct(Row $row, array $spec, $variant = 'items') {

			$this->row = $row;
			$this->spec = $spec;
			$this->variant = (string) $variant;
			$this->mode = $this->resolve_mode();

		}

		/**
		 * for_row function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row    $row
		 * @param string $variant 'items', or 'featured' for image_cards_multi_grid.
		 *
		 * @return Items_Source
		 */
		public static function for_row(Row $row, $variant = 'items') {
			return new self($row, self::spec($row), $variant);
		}

		/**
		 * spec function
		 *
		 * The layout's item spec: the built-in default, overridden by the row type if it
		 * implements Provides_Items, then filtered.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row $row
		 *
		 * @return array
		 */
		public static function spec(Row $row) {

			$layout = $row->layout();
			$spec = self::DEFAULTS[$layout] ?? self::default_spec();

			$type = $row->type();

			if($type instanceof Provides_Items) {
				$spec = array_merge($spec, (array) $type->items_spec());
			}

			/**
			 * Filters a layout's item spec, so a site can point a layout's items at
			 * differently named fields without replacing its row type.
			 *
			 * @param array $spec
			 * @param Row   $row
			 */
			return (array) apply_filters('acbs/row/items_spec', $spec, $row);

		}

		/**
		 * default_spec function
		 *
		 * The conventional field names, for a layout with no entry in DEFAULTS - a site's
		 * own contributed layout, most likely, which will normally have been modelled on
		 * one of the three that ship here.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		public static function default_spec() {

			return [
				'source' => 'source',
				'taxonomy' => 'taxonomy',
				'post_type' => 'post_type',
				'map' => [
					'image' => 'image',
					'title' => 'title',
					'link' => 'link_url',
					'content' => 'content',
				],
				'variants' => [
					'items' => [
						'repeater' => 'items',
						'terms' => 'terms',
						'posts' => 'posts',
					],
				],
			];

		}

		/**
		 * mode function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string 'repeater', 'taxonomy' or 'post_type'.
		 */
		public function mode() {
			return $this->mode;
		}

		/**
		 * have_item function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return bool
		 */
		public function have_item() {

			if(Item::REPEATER === $this->kind()) {

				$repeater = $this->field('repeater');

				return '' !== $repeater && function_exists('have_rows') && have_rows($repeater);

			}

			return $this->index < count($this->objects());

		}

		/**
		 * the_item function
		 *
		 * Advances to the next item and returns it. In repeater mode this calls the_row(),
		 * so get_sub_field() reads the item's own fields from here until the next call.
		 *
		 * Only ever call this immediately after have_item() has returned true, exactly as
		 * with ACF's own the_row().
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return Item
		 */
		public function the_item() {

			$this->position++;

			if(Item::REPEATER === $this->kind()) {

				the_row();

				return new Item(Item::REPEATER, null, $this->map(), $this->position);

			}

			$objects = $this->objects();
			$object = $objects[$this->index] ?? null;

			$this->index++;

			return new Item($this->kind(), $object, $this->map(), $this->position);

		}

		/**
		 * count function
		 *
		 * How many items this source will yield.
		 *
		 * In repeater mode this reads the loop's value directly rather than iterating,
		 * because iterating to count would consume the loop the template is about to run.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return int
		 */
		public function count() {

			if(Item::REPEATER !== $this->kind()) {
				return count($this->objects());
			}

			$repeater = $this->field('repeater');

			if('' === $repeater || !function_exists('get_sub_field')) {
				return 0;
			}

			$rows = get_sub_field($repeater, false);

			return is_array($rows) ? count($rows) : 0;

		}

		/**
		 * render function
		 *
		 * Loops the items, including the row's item partial once each with $row and $item
		 * in scope. The common case, and the reason three layouts can share one loop.
		 *
		 * A missing partial is not an error - Template_Loader::locate_partial() has no
		 * terminal fallback - but it is silent, so it is reported under WP_DEBUG.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $partial Partial name under rows/{layout}/, default 'item'.
		 */
		public function render($partial = 'item') {

			$path = Template_Loader::locate_partial($this->row, $partial);

			if('' === $path) {

				if(defined('WP_DEBUG') && WP_DEBUG) {
					trigger_error(
						sprintf(
							'ACBS: row layout "%s" has no "%s" partial, so its items rendered nothing. Expected rows/%s/%s.php.',
							$this->row->layout(),
							$partial,
							$this->row->type()->template(),
							$partial
						),
						E_USER_NOTICE
					);
				}

				return;

			}

			$row = $this->row;

			while($this->have_item()) {

				$item = $this->the_item();

				include $path;

			}

		}

		/**
		 * kind function
		 *
		 * The Item kind this source yields, derived from the mode.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		private function kind() {

			switch($this->mode) {

				case 'taxonomy':
					return Item::TERM;

				case 'post_type':
					return Item::POST;

			}

			return Item::REPEATER;

		}

		/**
		 * resolve_mode function
		 *
		 * Read from the row's own `source` field. A layout with no such field - or a row
		 * saved before one was added - iterates its repeater, which is what the field's own
		 * default_value says too.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		private function resolve_mode() {

			$field = (string) ($this->spec['source'] ?? 'source');

			if('' === $field || !function_exists('get_sub_field')) {
				return 'repeater';
			}

			$mode = get_sub_field($field);

			return in_array($mode, ['repeater', 'taxonomy', 'post_type'], true) ? $mode : 'repeater';

		}

		/**
		 * objects function
		 *
		 * The resolved terms or posts, built once.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		private function objects() {

			if(is_null($this->objects)) {

				switch($this->mode) {

					case 'taxonomy':
						$this->objects = $this->query_terms();
						break;

					case 'post_type':
						$this->objects = $this->query_posts();
						break;

					default:
						$this->objects = [];

				}

			}

			return $this->objects;

		}

		/**
		 * query_terms function
		 *
		 * The selected terms, in the order the editor selected them.
		 *
		 * `orderby => include` is the whole point: an editor picking four categories in a
		 * multi_select has expressed an order, and name or term_id ordering would throw it
		 * away. ERDC did the same thing through an Elementor query filter.
		 *
		 * The empty guard is NOT redundant. WP_Term_Query applies `include` only when it is
		 * non-empty (see wp-includes/class-wp-term-query.php, `if (!empty($include))`), so
		 * passing an empty include asks for EVERY term in the taxonomy. ERDC's own filter
		 * set `$args['include'] = []` for the no-terms-selected case and therefore rendered
		 * the entire taxonomy; returning early is the fix.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		private function query_terms() {

			$taxonomy = (string) $this->sub_value($this->spec['taxonomy'] ?? 'taxonomy');
			$ids = $this->id_list($this->field('terms'));

			if('' === $taxonomy || empty($ids) || !taxonomy_exists($taxonomy)) {
				return [];
			}

			$args = [
				'taxonomy' => $taxonomy,
				'include' => $ids,
				'orderby' => 'include',
				'order' => 'ASC',
				'hide_empty' => false,
			];

			$terms = get_terms($this->filter_query($args, 'terms'));

			return is_wp_error($terms) ? [] : array_values($terms);

		}

		/**
		 * query_posts function
		 *
		 * The selected posts, in the order the editor selected them - same reasoning as
		 * query_terms(), and the same empty guard: WP_Query ignores an empty `post__in`
		 * and would return the whole post type.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		private function query_posts() {

			$post_type = (string) $this->sub_value($this->spec['post_type'] ?? 'post_type');
			$ids = $this->id_list($this->field('posts'));

			if(empty($ids)) {
				return [];
			}

			$args = [
				'post_type' => '' !== $post_type ? $post_type : 'any',
				'post__in' => $ids,
				'orderby' => 'post__in',
				'order' => 'ASC',
				'post_status' => 'publish',
				'posts_per_page' => count($ids),
				'ignore_sticky_posts' => true,
				'no_found_rows' => true,
			];

			return array_values(get_posts($this->filter_query($args, 'posts')));

		}

		/**
		 * filter_query function
		 *
		 * The one query filter, replacing the six client-specific ones ERDC carried. A
		 * client add-on narrows or reorders a row's items here - the product_cat
		 * `hide_from_feed` exclusion that used to be hardcoded in the module is exactly
		 * this kind of callback, and belongs in that client's add-on (decision 10).
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param array  $args
		 * @param string $for 'terms' or 'posts'.
		 *
		 * @return array
		 */
		private function filter_query(array $args, $for) {

			/**
			 * Filters the query behind a row's items.
			 *
			 * Called with the row's ACF loop active, so a callback can read the row's own
			 * fields through get_sub_field().
			 *
			 * @param array        $args
			 * @param string       $for     'terms' or 'posts'.
			 * @param Row          $row
			 * @param Items_Source $source
			 */
			return (array) apply_filters('acbs/row/items_query', $args, $for, $this->row, $this);

		}

		/**
		 * field function
		 *
		 * A field name from the active variant.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $role 'repeater', 'terms' or 'posts'.
		 *
		 * @return string
		 */
		private function field($role) {
			return (string) ($this->spec['variants'][$this->variant][$role] ?? '');
		}

		/**
		 * map function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		private function map() {
			return (array) ($this->spec['map'] ?? []);
		}

		/**
		 * sub_value function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $name
		 *
		 * @return mixed
		 */
		private function sub_value($name) {

			$name = (string) $name;

			if('' === $name || !function_exists('get_sub_field')) {
				return null;
			}

			return get_sub_field($name);

		}

		/**
		 * id_list function
		 *
		 * The ids held by a terms or posts field, as a list of positive integers.
		 *
		 * Both field types are declared `return_format => id`, but the featured variants of
		 * image_cards_multi_grid are single-value (`field_type => select`, `multiple => 0`)
		 * while the rest are multi, so a bare scalar is a normal value here rather than a
		 * mistake. ERDC handled this with an is_array() cast at each call site.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $name
		 *
		 * @return array
		 */
		private function id_list($name) {

			$value = $this->sub_value($name);

			if(empty($value)) {
				return [];
			}

			if(!is_array($value)) {
				$value = [$value];
			}

			$ids = [];

			foreach($value as $entry) {

				// return_format => id, but a site that switched a field to `object` returns
				// WP_Term or WP_Post instances instead.
				if($entry instanceof \WP_Term) {
					$entry = $entry->term_id;
				} elseif($entry instanceof \WP_Post) {
					$entry = $entry->ID;
				}

				if(is_numeric($entry) && (int) $entry > 0) {
					$ids[] = (int) $entry;
				}

			}

			return array_values(array_unique($ids));

		}

	}
