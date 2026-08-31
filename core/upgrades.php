<?php

	namespace ACBS\Core;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Upgrades
	 *
	 * One-shot data cleanup, run once per plugin version and then never again.
	 *
	 * The Elementor decoupling deleted ten modules, and several of them owned options or
	 * transients that nothing reads any more. Left in place they are harmless but
	 * misleading - and one of them, the row cache's version counter, is an AUTOLOADED
	 * option, so it is read into memory on every single request of every page load for
	 * a cache that no longer exists.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Core
	 */
	class Upgrades {

		/**
		 * Option holding the last version whose upgrade routine has run. Deliberately the
		 * same key the dropped kit-defaults module used, so a site upgrading from ERDC
		 * does not re-run against a fresh-looking install.
		 */
		const VERSION_OPTION = 'erdc_version';

		/**
		 * Options the decoupling orphaned.
		 *
		 * `erdc_flexible_layout_row_versions` was autoloaded; the rest are one-off flags
		 * owned by legacy-migration and kit-defaults.
		 */
		const ORPHANED_OPTIONS = [
			'erdc_fresh_install',
			'erdc_pending_modals',
			'erdc_legacy_migration',
			'erdc_intro_migration',
			'erdc_intro_notice_dismissed',
			'erdc_flexible_layout_row_versions',
		];

		/**
		 * Prefix of the dropped row cache's transients, as they appear in wp_options.
		 * See the deleted Row_Cache for why these only exist on sites with no persistent
		 * object cache: elsewhere the transient never reached the table at all, and the
		 * object cache group expires on its own.
		 */
		const CACHE_TRANSIENT_PREFIX = '_fl_cache_';

		/**
		 * maybe_run function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function maybe_run() {

			$stored = get_option(self::VERSION_OPTION);

			if(ACBS_VERSION === $stored) {
				return;
			}

			self::delete_orphaned_options();
			self::delete_row_cache_transients();

			update_option(self::VERSION_OPTION, ACBS_VERSION, true);

		}

		/**
		 * delete_orphaned_options function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		private static function delete_orphaned_options() {

			foreach(self::ORPHANED_OPTIONS as $option) {
				delete_option($option);
			}

		}

		/**
		 * delete_row_cache_transients function
		 *
		 * Deleted through delete_transient() rather than a DELETE over wp_options, so the
		 * matching timeout rows go with them and any persistent cache entry is invalidated
		 * too.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		private static function delete_row_cache_transients() {

			global $wpdb;

			$names = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
					$wpdb->esc_like('_transient_'.self::CACHE_TRANSIENT_PREFIX).'%'
				)
			);

			foreach($names as $name) {
				delete_transient(substr($name, strlen('_transient_')));
			}

		}

	}
