<?php
/**
 * Gutenberg block wrapper (dynamic / server-rendered).
 *
 * The block registers `dpg/post-grid` whose render_callback delegates to the
 * shared DPG_Render::render() — so the block, the shortcode and the WPBakery
 * element all produce identical markup from one code path. Registration is
 * guarded so the plugin stays inert on WordPress builds without block support.
 *
 * @package DynamicPostGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DPG_Block.
 */
class DPG_Block {

	const NAME = 'dpg/post-grid';

	/**
	 * Hook registration onto init (block + asset handles).
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register the editor script/style handles and the block type itself.
	 */
	public static function register() {
		// Need block + metadata support (WP 5.5+ for from_metadata). Bail gracefully otherwise.
		if ( ! function_exists( 'register_block_type_from_metadata' ) && ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$block_dir = DPG_DIR . 'block';
		if ( ! file_exists( $block_dir . '/block.json' ) ) {
			return;
		}

		// Editor script: no build step — relies on the wp.* globals.
		wp_register_script(
			'dpg-block',
			DPG_URL . 'assets/js/dpg-block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			DPG_VERSION,
			true
		);

		// Editor preview styling reuses the front-end stylesheet.
		wp_register_style(
			'dpg-block-editor',
			DPG_URL . 'assets/css/dynamic-post-grid.css',
			array(),
			DPG_VERSION
		);

		$args = array( 'render_callback' => array( __CLASS__, 'render' ) );

		if ( function_exists( 'register_block_type_from_metadata' ) ) {
			register_block_type_from_metadata( $block_dir, $args );
		} else {
			// Older fallback: register by name with the attributes inlined.
			$meta = json_decode( file_get_contents( $block_dir . '/block.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			register_block_type(
				self::NAME,
				array_merge(
					$args,
					array(
						'attributes'    => isset( $meta['attributes'] ) ? $meta['attributes'] : array(),
						'editor_script' => 'dpg-block',
						'editor_style'  => 'dpg-block-editor',
					)
				)
			);
		}
	}

	/**
	 * Server render callback. Delegates to the shared render path.
	 *
	 * @param array         $attributes Block attributes (typed per block.json).
	 * @param string        $content    Inner content (unused — dynamic block).
	 * @param WP_Block|null $block      Block instance (unused).
	 * @return string
	 */
	public static function render( $attributes, $content = '', $block = null ) {
		$attributes = is_array( $attributes ) ? $attributes : array();
		return DPG_Render::render( $attributes );
	}
}
