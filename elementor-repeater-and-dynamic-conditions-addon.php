<?php
	
	/*
	 * Plugin Name:       ACF Components Block System
	 * Plugin URI:        https://fivecreative.com.au
	 * Description:       Component block system for ACF Pro
	 * Version:           1.0.0-alpha
	 * Requires at least: 6.0
	 * Requires PHP:      8.0
	 * Author:            Guilherme Salum
	 * Author URI:        https://fivecreative.com.au
	 * License:           GPL v2 or later
	 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
	 * Text Domain:       acbs
	 * Domain Path:       /languages
	 * Requires Plugins:  advanced-custom-fields-pro
     */
	
	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}
	
	// Constants. ACBS_* is the plugin's own set; the ERDC_* aliases below are kept for
	// one release so client snippets referencing the old names keep working.
	define('ACBS_VERSION', '1.0.0-alpha');
	define('ACBS__FILE__', __FILE__);
	define('ACBS_PATH', plugin_dir_path(ACBS__FILE__));
	define('ACBS_URL', plugin_dir_url(ACBS__FILE__));
	
	// The repository Plugin::updater() checks. Guarded, so a site can point a particular
	// install somewhere else from wp-config.php - which is loaded before this file - without
	// tripping a "constant already defined" warning on every request. Read the comment on
	// Plugin::updater() before changing this: it must be THIS fork, never the upstream ERDC
	// repository, and the version above must stay ahead of every tag that repository has
	// published.
	if(!defined('ACBS_UPDATE_REPO')) {
		define('ACBS_UPDATE_REPO', 'https://github.com/salumguilherme/acf-components-block-system');
	}
	
	// The ERDC_VERSION / ERDC__FILE__ / ERDC_PATH / ERDC_URL aliases were dropped on
	// 02/09/2026 with the rest of the erdc prefix. They existed for one release so a client
	// snippet written against the pre-fork plugin kept working; nothing in the plugin, the
	// child theme or any snippet we ship reads them.

	// ACBS_UPDATER_TOKEN (a GitHub personal access token, scoped to this plugin's private
	// update repository) is expected to be defined in wp-config.php, not shipped in the
	// plugin's own source - Plugin::updater() checks for it at runtime and warns in
	// wp-admin if it's missing, rather than requiring it here or failing if it's absent.
	
	// Loads our plugin
	add_action('plugins_loaded', 'acbs_load_plugin');
	
	/**
	 * Loads the ACF Components Block System plugin.
	 *
	 * ACF Pro is the only hard dependency and WordPress enforces it on activation via
	 * the Requires Plugins header, so there is no Elementor check here any more and no
	 * admin notice: a site without ACF cannot activate the plugin in the first place,
	 * and Core\Module_Base::is_active() stops every module from loading if ACF is
	 * deactivated afterwards.
	 */
	function acbs_load_plugin() {
		require ACBS_PATH.'plugin.php';
	}
