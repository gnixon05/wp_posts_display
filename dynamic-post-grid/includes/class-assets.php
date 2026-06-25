<?php
/**
 * Asset registration + conditional enqueue.
 *
 * Styles/scripts are registered early but only enqueued when an element or
 * shortcode actually renders on the page (DPG_Assets::mark_needed()).
 *
 * @package DynamicPostGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DPG_Assets.
 */
class DPG_Assets {

	/**
	 * Whether assets have been requested for this page.
	 *
	 * @var bool
	 */
	private static $needed = false;

	/**
	 * Whether assets have already been enqueued.
	 *
	 * @var bool
	 */
	private static $enqueued = false;

	/**
	 * Hook up registration.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register (not enqueue) the handles with versioning.
	 */
	public static function register() {
		wp_register_style(
			'dynamic-post-grid',
			DPG_URL . 'assets/css/dynamic-post-grid.css',
			array(),
			DPG_VERSION
		);

		wp_register_script(
			'dynamic-post-grid',
			DPG_URL . 'assets/js/dynamic-post-grid.js',
			array(),
			DPG_VERSION,
			true
		);

		wp_localize_script(
			'dynamic-post-grid',
			'DPG_Data',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'dpg_ajax' ),
				'i18n'    => array(
					'loading' => __( 'Loading&hellip;', 'dynamic-post-grid' ),
					'error'   => __( 'Something went wrong. Please try again.', 'dynamic-post-grid' ),
				),
			)
		);
	}

	/**
	 * Mark assets as needed and enqueue them if registration has run.
	 *
	 * Safe to call from inside the_content render: enqueuing before wp_footer
	 * still results in scripts/styles being printed.
	 */
	public static function mark_needed() {
		self::$needed = true;
		self::enqueue();
	}

	/**
	 * Enqueue the registered handles once.
	 */
	public static function enqueue() {
		if ( self::$enqueued || ! self::$needed ) {
			return;
		}
		// If called before registration (rare), register on the fly.
		if ( ! wp_style_is( 'dynamic-post-grid', 'registered' ) ) {
			self::register();
		}
		wp_enqueue_style( 'dynamic-post-grid' );
		wp_enqueue_script( 'dynamic-post-grid' );
		self::$enqueued = true;
	}
}
