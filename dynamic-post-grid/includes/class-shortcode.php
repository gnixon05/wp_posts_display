<?php
/**
 * [dynamic_post_grid] shortcode — the canonical, framework-agnostic entry point.
 *
 * Accepts the same attribute set as the WPBakery element. WPBakery's vc_map
 * also resolves to this shortcode, so there is one render path.
 *
 * @package DynamicPostGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DPG_Shortcode.
 */
class DPG_Shortcode {

	const TAG = 'dynamic_post_grid';

	/**
	 * Register the shortcode.
	 */
	public static function init() {
		add_shortcode( self::TAG, array( __CLASS__, 'render' ) );
	}

	/**
	 * Render callback.
	 *
	 * @param array  $atts    Raw shortcode attributes.
	 * @param string $content Enclosed content (unused).
	 * @return string
	 */
	public static function render( $atts, $content = '' ) {
		$atts = is_array( $atts ) ? $atts : array();
		return DPG_Render::render( $atts );
	}
}
