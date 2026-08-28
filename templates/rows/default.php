<?php
	/**
	 * Terminal template candidate.
	 *
	 * Deliberately empty: it exists so template resolution can never fail, and it renders
	 * nothing so a layout with no template of its own produces no output rather than a
	 * PHP notice.
	 *
	 * It is NOT what a removed layout falls through to - a row whose layout has no
	 * registered row type is skipped entirely, wrapper included, before resolution ever
	 * runs. See CLAUDE.md section 05.7.
	 *
	 * @var ERDC\Modules\FlexibleLayoutTemplate\Rows\Row $row
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) { exit; }
