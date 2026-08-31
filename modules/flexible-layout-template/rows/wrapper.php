<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Rows;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Wrapper
	 *
	 * Prints one row: the <section>, the container, the action points, and the row's
	 * template.
	 *
	 * Two things here are load-bearing and easy to break:
	 *
	 * 1. The row's ACF loop stays active while the template renders, so get_sub_field()
	 *    works inside it. That is not incidental - ACF's own accessors are the only thing
	 *    that formats image arrays, gallery objects, link arrays and the Buttons/Intro
	 *    field types, and every wrapper class the module contributes already reads
	 *    through get_sub_field(). There is no parallel data layer.
	 *
	 * 2. The loop depth is recorded before the template is included and unwound back down
	 *    to it afterwards. A template that breaks out of a nested while(have_rows())
	 *    without reset_rows() leaves a half-consumed loop on ACF's stack, and the next
	 *    have_rows() with the same key resumes it instead of starting fresh - so one badly
	 *    written template would corrupt every row after it. See CLAUDE.md section 05.2.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Rows
	 */
	class Wrapper {

		/**
		 * render function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row $row
		 *
		 * @return string
		 */
		public static function render(Row $row) {

			$path = self::locate_wrapper();

			// The wrapper is a template too, so a theme can change the element, the
			// container or the attribute order without touching PHP in the plugin.
			if('' !== $path) {

				ob_start();
				self::include_template($path, $row);

				return ob_get_clean();

			}

			// Only reachable if templates/wrapper.php has been deleted from the plugin and
			// the theme has not supplied one. Kept minimal and correct rather than absent,
			// so a broken install degrades to unstyled rather than to a blank page.
			$id = $row->wrapper_id();

			return '<section class="'.esc_attr($row->wrapper_class()).'"'
				.('' !== $id ? ' id="'.esc_attr($id).'"' : '')
				.'>'.$row->content().'</section>';

		}

		/**
		 * content function
		 *
		 * The five extension points every row emits. The default handler of
		 * `acbs/row/{layout}/content` includes the row's own template; a site can remove
		 * it and take the content over without copying a file, which is the whole point -
		 * WooCommerce's template system fails because copying into the theme is the only
		 * option and copied templates never get upstream fixes.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row $row
		 *
		 * @return string
		 */
		public static function content(Row $row) {

			$layout = $row->layout();

			ob_start();

			do_action('acbs/row/before', $row);
			do_action("acbs/row/{$layout}/before", $row);

			do_action("acbs/row/{$layout}/content", $row);

			do_action("acbs/row/{$layout}/after", $row);
			do_action('acbs/row/after', $row);

			return ob_get_clean();

		}

		/**
		 * locate_wrapper function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		public static function locate_wrapper() {

			$located = locate_template(Template_Loader::THEME_DIR.'/wrapper.php');

			if(!$located && file_exists(ACBS_PATH.'templates/wrapper.php')) {
				$located = ACBS_PATH.'templates/wrapper.php';
			}

			return (string) $located;

		}

		/**
		 * render_content function
		 *
		 * Default handler for `acbs/row/{layout}/content`, hooked by the Renderer.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row $row
		 */
		public static function render_content(Row $row) {

			$path = Template_Loader::locate($row);

			if(defined('WP_DEBUG') && WP_DEBUG) {

				printf(
					'<!-- acbs row %1$s: %2$s -->',
					esc_html($row->layout()),
					esc_html('' !== $path ? self::relative($path) : 'no template found among: '.implode(', ', Template_Loader::candidates($row)))
				);

			}

			if('' === $path) {
				return;
			}

			self::include_template($path, $row);

		}

		/**
		 * include_template function
		 *
		 * Includes a template with exactly one variable in scope - $row - and guarantees
		 * ACF's loop stack comes back at the depth it went in at.
		 *
		 * WooCommerce's loader does extract($args) here. Passing one object instead keeps
		 * templates greppable and lets static analysis see what they have.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $path
		 * @param Row    $row
		 */
		public static function include_template($path, Row $row) {

			$depth = self::loop_depth();

			include $path;

			self::unwind_loops($depth, $row);

		}

		/**
		 * loop_depth function
		 *
		 * How many loops are currently on ACF's stack. `acf()->loop->loops` is a public
		 * property; there is no accessor for the count.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return int
		 */
		public static function loop_depth() {

			if(!function_exists('acf') || !isset(acf()->loop)) {
				return 0;
			}

			return count(acf()->loop->loops);

		}

		/**
		 * unwind_loops function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param int $depth The depth to return to.
		 * @param Row $row   Only used for the debug message.
		 */
		private static function unwind_loops($depth, Row $row) {

			$leaked = self::loop_depth() - $depth;

			if($leaked <= 0) {
				return;
			}

			for($i = 0; $i < $leaked; $i++) {
				reset_rows();
			}

			if(defined('WP_DEBUG') && WP_DEBUG) {
				trigger_error(
					sprintf(
						'ACBS: the template for row layout "%s" left %d ACF loop(s) open. A while(have_rows()) that can exit early must call reset_rows() before it breaks.',
						$row->layout(),
						$leaked
					),
					E_USER_WARNING
				);
			}

		}

		/**
		 * classes function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row $row
		 *
		 * @return array
		 */
		public static function classes(Row $row) {

			$classes = array_merge(['fl-section'], $row->type()->wrapper_classes($row));

			/**
			 * Filters a row's wrapper classes. Called with the row's ACF loop active, so
			 * get_sub_field() works in a callback.
			 *
			 * @param array $classes
			 * @param Row   $row
			 */
			$classes = apply_filters('acbs/row/wrapper_classes', $classes, $row);

			// The filter used to be handed, and expected to return, a space-separated
			// string. Accept either so a client snippet written against the old contract
			// still works.
			if(!is_array($classes)) {
				$classes = explode(' ', (string) $classes);
			}

			$classes = array_filter(array_map('sanitize_html_class', $classes));

			return array_values(array_unique($classes));

		}

		/**
		 * id function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param Row $row
		 *
		 * @return string
		 */
		public static function id(Row $row) {

			/**
			 * Filters a row's wrapper id.
			 *
			 * @param string $id
			 * @param Row    $row
			 */
			$id = apply_filters('acbs/row/wrapper_id', '', $row);

			return is_scalar($id) ? sanitize_html_class((string) $id) : '';

		}

		/**
		 * relative function
		 *
		 * A template path relative to ABSPATH, for the debug comment.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $path
		 *
		 * @return string
		 */
		private static function relative($path) {
			return ltrim(str_replace(wp_normalize_path(ABSPATH), '', wp_normalize_path($path)), '/');
		}

	}
