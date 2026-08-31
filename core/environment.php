<?php

	namespace ACBS\Core;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Environment
	 *
	 * Small checks about the site's environment that several otherwise-unrelated
	 * modules need to make the same decision from (e.g. whether WooCommerce-dependent
	 * flexible layouts/templates should be offered at all).
	 *
	 * @version 1.0.17
	 * @since   1.0.17
	 * @package ACBS\Core
	 */
	class Environment {

		/**
		 * is_woocommerce_active function
		 *
		 * @version 1.0.17
		 * @since   1.0.17
		 * @return bool
		 */
		public static function is_woocommerce_active() {
			return class_exists('WooCommerce');
		}

	}
