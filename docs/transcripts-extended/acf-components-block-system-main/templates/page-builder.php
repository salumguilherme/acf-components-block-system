<?php
	/**
	 * Page template: Page Builder
	 *
	 * Assigned from the Template dropdown on the page editor. Registered and served by
	 * Page_Template - a plugin's page template cannot be discovered by the theme scan.
	 *
	 * Override at {theme}/acbs/page-builder.php.
	 *
	 * Deliberately has no `Template Name` file header: the theme scan reaches one level of
	 * subdirectory, so a header here would make a theme override register a second,
	 * duplicate entry in the dropdown. See Page_Template's class comment.
	 *
	 * The page's own rows are the whole content. There is no the_content() call - a page
	 * built out of flexible layout rows has nothing in post_content, and printing an empty
	 * editor region above the rows is how a stray gap gets into every page on the site.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }

	get_header();

	acbs_render_rows();

	get_footer();
