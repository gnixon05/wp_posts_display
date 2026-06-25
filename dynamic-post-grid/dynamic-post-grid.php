<?php
/**
 * Plugin Name:       Dynamic Post Grid + Filter
 * Plugin URI:        https://github.com/gnixon05/wp_posts_display
 * Description:        Renders posts, pages or any post type in a configurable grid with multiple card styles, an "Education / Featured Magazine" preset, and an optional AJAX multi-criteria filter bar. Registers a WPBakery element and an equivalent [dynamic_post_grid] shortcode. Coexists cleanly with the Salient theme.
 * Version:           1.1.0
 * Requires at least: 5.6
 * Requires PHP:      7.2
 * Author:            gnixon05
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dynamic-post-grid
 * Domain Path:       /languages
 *
 * @package DynamicPostGrid
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Constants
 * ---------------------------------------------------------------------- */
define( 'DPG_VERSION', '1.1.0' );
define( 'DPG_FILE', __FILE__ );
define( 'DPG_DIR', plugin_dir_path( __FILE__ ) );
define( 'DPG_URL', plugin_dir_url( __FILE__ ) );
define( 'DPG_BASENAME', plugin_basename( __FILE__ ) );

/* -------------------------------------------------------------------------
 * Includes
 * ---------------------------------------------------------------------- */
require_once DPG_DIR . 'includes/class-query.php';
require_once DPG_DIR . 'includes/class-render.php';
require_once DPG_DIR . 'includes/class-filter.php';
require_once DPG_DIR . 'includes/class-assets.php';
require_once DPG_DIR . 'includes/class-ajax.php';
require_once DPG_DIR . 'includes/class-shortcode.php';
require_once DPG_DIR . 'includes/class-wpbakery.php';
require_once DPG_DIR . 'includes/class-block.php';

/**
 * Main plugin bootstrap.
 */
final class DPG_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var DPG_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return DPG_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire up hooks.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'boot' ) );
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'dynamic-post-grid', false, dirname( DPG_BASENAME ) . '/languages' );
	}

	/**
	 * Initialise components.
	 */
	public function boot() {
		DPG_Assets::init();
		DPG_Ajax::init();
		DPG_Shortcode::init();
		DPG_WPBakery::init();
		DPG_Block::init();
	}
}

/**
 * Activation hook — nothing persistent to set up, but flush rewrite rules in
 * case a future REST route is registered. Kept lightweight & idempotent.
 */
function dpg_activate() {
	// Reserved for future use (custom tables, capabilities, etc.).
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'dpg_activate' );

/**
 * Deactivation hook.
 */
function dpg_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'dpg_deactivate' );

// Go.
DPG_Plugin::instance();
