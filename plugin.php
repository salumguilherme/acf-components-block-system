<?php
	
	namespace ERDC;
	
	use ERDC\Core\Modules_Manager;
	use ERDC\Core\Upgrades;
	use ERDC\Modules\FlexibleLayoutTemplate\Rows\Renderer;
	use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
	
	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}
	
	// Plugin wrapper class
	class Plugin {
		
		/**
		 * @var Plugin
		 */
		private static $_instance;
		
		/**
		 * @var Modules_Manager
		 */
		public $modules_manager;
		
		/**
		 * @var Renderer|null
		 */
		private $renderer = null;
		
		/**
		 * @var PucFactory
		 */
		public $updateChecker;
		
		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function __clone() {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf( 'Cloning instances of the singleton "%s" class is forbidden.', get_class( $this ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'1.0.0'
			);
		}
		
		/**
		 * Disable unserializing of the class
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function __wakeup() {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf( 'Unserializing instances of the singleton "%s" class is forbidden.', get_class( $this ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'1.0.0'
			);
		}
		
		/**
		 * Autoloads our classes
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $class
		 */
		public function autoload($class) {
			
			// Ensures correct class name
			if(0 !== strpos($class, __NAMESPACE__)) {
				return;
			}
			
			// Checks if our class already exists
			if(!class_exists($class)) {
				
				// defines filename
				$filename = strtolower(
					preg_replace(
						['/^' . __NAMESPACE__ . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/'],
						[ '', '$1-$2', '-', DIRECTORY_SEPARATOR ],
						$class
					)
				);
				
				$filename = ERDC_PATH.$filename.'.php';
				
				// If the file exists
				if(is_readable($filename)) {
					include($filename);
				}
				
			}
			
		}
		
		/**
		 * renderer function
		 *
		 * The flexible layout row renderer. Lazily built so nothing loads the render
		 * layer on a request that never renders a row.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return Renderer
		 */
		public function renderer() {
			
			if(is_null($this->renderer)) {
				$this->renderer = new Renderer();
			}
			
			return $this->renderer;
			
		}
		
		/**
		 * instance function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return Plugin|self
		 */
		public static function instance() {
			
			// Creates our instance if not already set
			if(is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			
			// returns instance
			return self::$_instance;
			
		}
		
		/**
		 * init function
		 *
		 * Builds the modules manager. Called straight from the constructor, which runs on
		 * plugins_loaded, and NOT on `init` - ACF fires `acf/init` from inside `init` at
		 * priority 5 (verified in advanced-custom-fields-pro/acf.php: `add_action('init',
		 * array($this,'init'), 5)`, whose last statement is `do_action('acf/init')`).
		 * Hooking our own bootstrap at init:5 would leave whether our `acf/init` listeners
		 * are registered before ACF fires it down to plugin load order, so every field
		 * group would register or silently not register depending on the alphabet.
		 * plugins_loaded is unambiguously earlier, and ACF's api functions already exist by
		 * then because acf.php calls acf()->initialize() at include time.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		private function init() {
			
			// Initiates our modules manager - this class is responsible for loading our modules
			$this->modules_manager = new Modules_Manager();
			
			// Action for possible future functionality
			do_action('acbs/init');
			
			// Deprecated alias, kept for one release for client snippets.
			do_action('erdc/init');
			
		}
		
		/**
		 * Plugin constructor
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		private function __construct() {
			
			// Sets up our auto classes
			spl_autoload_register([$this, 'autoload']);
			
			// Sets up updater
			$this->updater();
			
			// One-shot data cleanup for anything a previous version left behind
			Upgrades::maybe_run();
			
			// Loads the modules
			$this->init();
			
		}
		
		/**
		 * updater function
		 *
		 * @version 1.0.28
		 * @since   1.0.0
		 */
		private function updater() {
			
			// OFF BY DEFAULT, DELIBERATELY. Read this before switching it on.
			//
			// On 28/08/2026 this method destroyed the fork. The plugin header had been
			// reset to 1.0.0 for the fork while the repository below - the ORIGINAL ERDC
			// repo, which this fork no longer tracks - was still publishing 1.0.4. wp-admin
			// therefore offered "1.0.0 -> 1.0.4" as a legitimate update, and WordPress's
			// plugin upgrader does what it always does: it deletes the entire plugin
			// directory before installing. That took the whole 1.0.36 codebase, the phases
			// 00-02 work, and the .git directory that lived inside the plugin folder.
			//
			// Two independent things have to be true before this is safe to re-enable:
			//
			//   1. The repository URL points at THIS fork, not at ERDC. Pointing a fork's
			//      update checker at its upstream means upstream can overwrite the fork at
			//      any time, and a lower fork version turns that into an automatic
			//      downgrade rather than an obvious error.
			//   2. The fork's version is above every version that repository has ever
			//      published, so a stale tag cannot read as an upgrade.
			//
			// Define ACBS_UPDATE_REPO in wp-config.php with the fork's repository URL to
			// turn it on. There is no default value on purpose: a constant someone has to
			// type is a decision, whereas a hardcoded URL is an accident waiting to repeat.
			if(!defined('ACBS_UPDATE_REPO') || !ACBS_UPDATE_REPO) {
				return;
			}
			
			if(!class_exists('\YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
				require ACBS_PATH.'vendor/autoload.php';
			}
			
			$this->updateChecker = PucFactory::buildUpdateChecker(
				ACBS_UPDATE_REPO,
				ACBS_PATH.'elementor-repeater-and-dynamic-conditions-addon.php',
				'elementor-repeater-and-dynamic-conditions-addon'
			);
			
			$this->updateChecker->setBranch('main');
			
			// ERDC_UPDATER_TOKEN authenticates against a private update repository and is
			// expected to be defined in wp-config.php. A site that hasn't set it still
			// loads and runs normally - it simply won't receive updates - so this warns
			// whoever can actually fix it rather than calling setAuthentication() with an
			// undefined constant, which would fatal every request on PHP 8.
			if(defined('ERDC_UPDATER_TOKEN')) {
				$this->updateChecker->setAuthentication(ERDC_UPDATER_TOKEN);
			} elseif(is_admin()) {
				add_action('admin_notices', [$this, 'render_missing_updater_token_notice']);
			}
			
		}
		
		/**
		 * render_missing_updater_token_notice function
		 *
		 * Only shown to users who can actually act on it - defining the constant means
		 * editing wp-config.php, which requires server/hosting access wp-admin alone
		 * doesn't grant, so this is gated the same way changing site-wide settings would
		 * be rather than shown to every logged-in user.
		 *
		 * @version 1.0.28
		 * @since   1.0.28
		 */
		public function render_missing_updater_token_notice() {
			
			if(!current_user_can('manage_options')) {
				return;
			}
			
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php
						printf(
							/* translators: 1: opening code tag, 2: constant name, 3: closing code tag */
							esc_html__('ACF Components Block System: the %1$s%2$s%3$s constant is not defined in wp-config.php. Without it, this site will not receive future plugin updates - add it to wp-config.php to keep updates working.', 'erdc'),
							'<code>',
							'ERDC_UPDATER_TOKEN',
							'</code>'
						);
					?>
				</p>
			</div>
			<?php
			
		}
		
	}
	
	// inits our plugins instance
	Plugin::instance();