<?php
	/**
	 * The plugin's own constants, declared for static analysis only.
	 *
	 * NOT LOADED AT RUNTIME. This file is listed in phpstan.neon under scanFiles; the real
	 * definitions live in the main plugin file.
	 *
	 * WHY ONLY SOME OF THEM NEED THIS. PHPStan evaluates a `define()` whose value it can
	 * work out for itself, so `ACBS_VERSION` (a literal) and `ACBS__FILE__` (`__FILE__`)
	 * resolve from the main file with no help. `ACBS_PATH` and `ACBS_URL` are
	 * `plugin_dir_path()` / `plugin_dir_url()` calls, which it will not evaluate, so it
	 * reported them as undefined 30 times over.
	 *
	 * ACBS_UPDATER_TOKEN is not defined in this repository at all - it comes from each
	 * site's wp-config.php - so it is declared here for completeness.
	 *
	 * The values are placeholders and deliberately not trusted: every constant whose value
	 * depends on the environment is also listed in `dynamicConstantNames` in phpstan.neon.
	 * That part matters. Without it PHPStan takes the literal in the main file as gospel
	 * and reports the guard `if(!defined('ACBS_UPDATE_REPO') || !ACBS_UPDATE_REPO)` as
	 * always false - which it is for the shipped default and is NOT for a site that
	 * defines the constant empty in wp-config.php, since that is loaded first and
	 * `define()` does not redeclare.
	 */

	define('ACBS_PATH', '/path/to/plugin/');
	define('ACBS_URL', 'https://example.test/wp-content/plugins/plugin/');
	define('ACBS_UPDATER_TOKEN', '');
