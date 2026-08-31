<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Rows;

	use ACBS\Modules\FlexibleLayoutTemplate\Fields\Page_Content;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Row_Registry
	 *
	 * layout name => Row_Type. This is a PUBLIC API: a client add-on registers its own
	 * row type here, which is how the WooCommerce layouts return if a client needs them.
	 *
	 *     add_action('acbs/rows/register', function($registry) {
	 *         $registry::register(new My_Product_Feed_Row_Type());
	 *     });
	 *
	 * A layout with no row type registered against it is NOT rendered - see
	 * Renderer::render() and CLAUDE.md section 05.7 for why skipping the row entirely,
	 * wrapper included, is the only correct answer for a layout that has been removed
	 * from the set while rows are still saved against it.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Rows
	 */
	class Row_Registry {

		/**
		 * @var Row_Type[]
		 */
		private static $types = [];

		/**
		 * @var bool
		 */
		private static $booted = false;

		/**
		 * boot function
		 *
		 * Builds a Layout_Row_Type for every layout currently available on this site, then
		 * lets add-ons register over the top or add their own.
		 *
		 * Called on `init` at priority 5 - after ACF's own init at 5 has fired acf/init and
		 * the field groups exist, since get_current_layouts() runs the whole merge pipeline
		 * including the contributor groups. Registration is idempotent.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function boot() {

			if(self::$booted) {
				return;
			}

			self::$booted = true;

			foreach(Page_Content::get_current_layouts() as $definition) {

				if(empty($definition['name'])) {
					continue;
				}

				self::register(new Layout_Row_Type($definition));

			}

			do_action('acbs/rows/register', __CLASS__);

		}

		/**
		 * register function
		 *
		 * Replaces any row type already registered under the same layout name, so an
		 * add-on can take over a base layout as well as add a new one.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row_Type $type
		 */
		public static function register(Row_Type $type) {

			$name = $type->name();

			if('' === $name) {
				return;
			}

			self::$types[$name] = $type;

		}

		/**
		 * unregister function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $name
		 */
		public static function unregister($name) {
			unset(self::$types[$name]);
		}

		/**
		 * get function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $name
		 *
		 * @return Row_Type|null
		 */
		public static function get($name) {

			// A row can be rendered before init:5 in principle (a shortcode in an early
			// template part), so make sure the base set exists before answering.
			self::boot();

			return self::$types[$name] ?? null;

		}

		/**
		 * has function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $name
		 *
		 * @return bool
		 */
		public static function has($name) {
			return !is_null(self::get($name));
		}

		/**
		 * all function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return Row_Type[]
		 */
		public static function all() {

			self::boot();

			return self::$types;

		}

	}
