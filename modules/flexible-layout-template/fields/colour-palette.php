<?php

	namespace ACBS\Modules\FlexibleLayoutTemplate\Fields;

	if(!defined( 'ABSPATH')) {
		exit; // Exit if accessed directly.
	}

	/**
	 * Class Colour_Palette
	 *
	 * The one list of background colours an editor can choose from, shared by the two
	 * fields that offer one: Section BG Colour (Other_Settings) and Card BG Colour
	 * (Grid_Display).
	 *
	 * It exists because those two lists had drifted apart - the section offered accents
	 * the card did not, the card offered a custom colour the section did not - and an
	 * editor setting a background met a different palette depending on which control they
	 * happened to be using. One source, two consumers, and the difference between them is
	 * now exactly the one difference that is real: a card can take a custom colour.
	 *
	 * Every key is a class - `fl-bg-{key}` on a section, `fl-card-bg-{key}` on a card - and
	 * structure.scss carries a rule for each. `default` is the exception: emitted like the
	 * rest, deliberately without a rule, so it stays transparent and shows whatever is
	 * behind it.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package ACBS\Modules\FlexibleLayoutTemplate\Fields
	 */
	class Colour_Palette {

		/**
		 * choices function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array value => label, in the order the buttons appear.
		 */
		public static function choices(): array {

			$choices = [
				'default' => __('Transparent', 'erdc'),
				'white' => __('White', 'erdc'),
				'light' => __('Light', 'erdc'),
				'lighter' => __('Lighter', 'erdc'),
				'accent-1' => __('Accent 1', 'erdc'),
				'accent-2' => __('Accent 2', 'erdc'),
				'dark' => __('Dark', 'erdc'),
				'darker' => __('Darker', 'erdc'),
				'primary' => __('Primary', 'erdc'),
				'secondary' => __('Secondary', 'erdc'),
				'tertiary' => __('Tertiary', 'erdc'),
			];

			/**
			 * Filters the shared background colour palette.
			 *
			 * THE filter for relabelling colours site-wide: return the array with your own
			 * labels and both Section BG Colour and Card BG Colour pick them up. Keys are
			 * class names, so rename labels freely but change a key only if you are also
			 * adding the matching CSS rule.
			 *
			 * Order is display order. Removing a key removes the option but not any value
			 * already saved against it - ACF does not rewrite stored values to match a
			 * narrowed choice list, so an existing row keeps its colour and its class.
			 *
			 * @param array $choices value => label.
			 */
			return (array) apply_filters('acbs/colour_palette/choices', $choices);

		}

	}
