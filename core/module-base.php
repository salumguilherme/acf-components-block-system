<?php

	namespace ERDC\Core;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Module_Base
	 *
	 * Replaces Elementor\Core\Base\Module as the base class every module extends. The
	 * plugin only ever used three things from Elementor's version - instance(),
	 * is_active() and get_name() - so this is a like-for-like replacement rather than a
	 * reimplementation of Elementor's module system.
	 *
	 * is_active() defaults to "ACF Pro is present". Elementor's version returned a bare
	 * true, so every module loaded even with ACF deactivated and then failed further in;
	 * ACF is now the plugin's only hard dependency, so declaring it here once means no
	 * module has to guard for it individually.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ERDC\Core
	 */
	abstract class Module_Base {

		/**
		 * Instances, keyed by concrete class name.
		 *
		 * @var Module_Base[]
		 */
		private static $_instances = [];

		/**
		 * get_name function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return string
		 */
		abstract public function get_name();

		/**
		 * is_active function
		 *
		 * Whether this module should be loaded at all. A module with a dependency beyond
		 * ACF overrides this and calls parent::is_active() as well, so the ACF check is
		 * never accidentally dropped.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return bool
		 */
		public static function is_active() {
			return function_exists('acf_add_local_field_group');
		}

		/**
		 * instance function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return static
		 */
		public static function instance() {

			if(!isset(self::$_instances[static::class])) {
				self::$_instances[static::class] = new static();
			}

			return self::$_instances[static::class];

		}

		/**
		 * Constructor
		 *
		 * Present so subclasses can keep calling parent::__construct() unchanged.
		 */
		public function __construct() {}

	}
