<?php
	
	namespace ACBS\Core;
	
	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}
	
	/**
	 * Class Modules_Manager
	 *
	 * Loads the plugin's modules. Enable or disable one by editing self::MODULES.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Core
	 */
	final class Modules_Manager {
		
		/**
		 * @var array
		 */
		private $modules = [];
		
		/**
		 * Modules loaded by the plugin.
		 *
		 * The ten Elementor-dependent modules that used to sit in this list were removed
		 * with the Elementor decoupling - see CLAUDE.md section 07 for what each one did
		 * and where its salvageable parts went.
		 *
		 * @var array
		 */
		const MODULES = [
			'flexible-layout-template',
			'dynamic-taxonomy-fields',
			'theme-settings',
			'page-header',
			'settings',
			'acf-copy-to-clipboard'
		];
		
		public function __construct() {
			
			// Loads each module
			foreach(self::MODULES as $module_name) {
				
				$class_name = $this->module_class_name($module_name);
				
				// A name in the list above with no class behind it used to be a fatal, since
				// is_active() was called on it straight away. Skipping it with a WP_DEBUG
				// notice keeps a typo (or a half-removed module) from taking the site down.
				if(!class_exists($class_name)) {
					
					if(defined('WP_DEBUG') && WP_DEBUG) {
						trigger_error(
							sprintf('ACBS: module "%s" is listed in Modules_Manager::MODULES but %s does not exist.', $module_name, $class_name),
							E_USER_WARNING
						);
					}
					
					continue;
					
				}
				
				// If is active
				if($class_name::is_active()) {
					$this->modules[$module_name] = $class_name::instance();
				}
				
			}
			
		}
		
		/**
		 * get_modules function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string|null $module_name Omit for every loaded module.
		 *
		 * @return Module_Base|Module_Base[]|null
		 */
		public function get_modules($module_name = null) {
			
			if(is_null($module_name)) {
				return $this->modules;
			}
			
			return $this->modules[$module_name] ?? null;
			
		}
		
		/**
		 * module_class_name function
		 *
		 * 'flexible-layout-template' => '\ACBS\Modules\FlexibleLayoutTemplate\Module'
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param string $module_name
		 *
		 * @return string
		 */
		private function module_class_name($module_name) {
			
			$class_name = str_replace('-', ' ', $module_name);
			$class_name = str_replace(' ', '', ucwords($class_name));
			
			return '\ACBS\Modules\\'.$class_name.'\Module';
			
		}
		
	}
