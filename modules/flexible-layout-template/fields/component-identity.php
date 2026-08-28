<?php

	namespace ERDC\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Interface Component_Identity
	 *
	 * The two facts every "Flexible Layout Component" target is identified by: which
	 * Component_Rule value a site tags its own field group with to extend it, and the
	 * key of the plugin's own field group for it (excluded from that lookup, so the
	 * plugin's group can never match itself).
	 *
	 * Deliberately its own tiny interface rather than folded into
	 * Flexible_Layout_Component: Site_Fields_Base needs exactly these two and nothing
	 * else, and Page Header - which is NOT a Flexible_Layout_Component (see that
	 * interface for why) - still needs to supply them. Splitting them out means the
	 * identity contract Site_Fields_Base leans on is compiler-enforced for every
	 * participant, including Page Header, instead of being an informal naming
	 * coincidence between two unrelated abstractions that could silently drift apart.
	 *
	 * @version 1.0.27
	 * @since   1.0.27
	 * @package ERDC\Modules\FlexibleLayoutTemplate\Fields
	 */
	interface Component_Identity {

		/**
		 * The Component_Rule value a site tags its own field group with to extend this
		 * component's fields.
		 *
		 * @return string
		 */
		public static function location_value(): string;

		/**
		 * The key of the plugin's own field group for this component.
		 *
		 * @return string
		 */
		public static function group_key(): string;

	}
