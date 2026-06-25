<?php
/**
 * Nonce-protected AJAX endpoints: filter (replace) + load-more (append).
 *
 * Both endpoints rebuild the same base WP_Query from the element config that
 * travelled with the request, then layer on sanitised runtime filters, and
 * render markup through the shared DPG_Render path — so AJAX output is byte
 * identical to the initial server render.
 *
 * @package DynamicPostGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DPG_Ajax.
 */
class DPG_Ajax {

	/**
	 * Register the admin-ajax actions (logged-in + nopriv).
	 */
	public static function init() {
		add_action( 'wp_ajax_dpg_filter', array( __CLASS__, 'handle_filter' ) );
		add_action( 'wp_ajax_nopriv_dpg_filter', array( __CLASS__, 'handle_filter' ) );

		add_action( 'wp_ajax_dpg_load_more', array( __CLASS__, 'handle_load_more' ) );
		add_action( 'wp_ajax_nopriv_dpg_load_more', array( __CLASS__, 'handle_load_more' ) );
	}

	/**
	 * Filter request: returns the full grid inner markup (replace), plus the
	 * pagination state so "load more" keeps working after a filter is applied.
	 */
	public static function handle_filter() {
		$atts = self::verify_and_get_atts();

		$runtime = array(
			'paged' => 1,
			'tax'   => self::read_tax_filters( $atts ),
			's'     => self::read_search( $atts ),
		);

		$query = DPG_Query::build( $atts, $runtime );

		$html = DPG_Render::render_grid_inner( $atts, $query, 1 );

		wp_send_json_success(
			array(
				'html'      => $html,
				'found'     => (int) $query->found_posts,
				'max_pages' => (int) $query->max_num_pages,
				'page'      => 1,
			)
		);
	}

	/**
	 * Load-more request: returns just the next page's cards (append).
	 */
	public static function handle_load_more() {
		$atts = self::verify_and_get_atts();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified in verify_and_get_atts().
		$paged = isset( $_POST['paged'] ) ? max( 1, (int) $_POST['paged'] ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$runtime = array(
			'paged' => $paged,
			'tax'   => self::read_tax_filters( $atts ),
			's'     => self::read_search( $atts ),
		);

		$query = DPG_Query::build( $atts, $runtime );

		if ( ! $query->have_posts() ) {
			wp_send_json_success(
				array(
					'html'      => '',
					'page'      => $paged,
					'max_pages' => (int) $query->max_num_pages,
					'done'      => true,
				)
			);
		}

		$html = DPG_Render::render_cards( $atts, $query, $paged );

		wp_send_json_success(
			array(
				'html'      => $html,
				'page'      => $paged,
				'max_pages' => (int) $query->max_num_pages,
				'done'      => ( $paged >= (int) $query->max_num_pages ),
			)
		);
	}

	/* ----------------------------------------------------------------- *
	 * Shared request handling
	 * ----------------------------------------------------------------- */

	/**
	 * Verify the nonce and return the sanitised element attributes carried by
	 * the request. Dies with a JSON error on failure.
	 *
	 * @return array Clean attributes.
	 */
	private static function verify_and_get_atts() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce checked here.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'dpg_ajax' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'dynamic-post-grid' ) ), 403 );
		}

		$raw_config = isset( $_POST['config'] ) ? wp_unslash( $_POST['config'] ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$config = json_decode( $raw_config, true );
		if ( ! is_array( $config ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'dynamic-post-grid' ) ), 400 );
		}

		// Re-sanitise everything that arrived from the client.
		return DPG_Query::sanitize( $config );
	}

	/**
	 * Read + sanitise taxonomy filter selections from the request, restricted to
	 * the taxonomies this element actually exposes.
	 *
	 * @param array $atts Clean attributes.
	 * @return array tax => int[] term ids.
	 */
	private static function read_tax_filters( $atts ) {
		$allowed = DPG_Filter::resolve_taxonomies( $atts );
		$out     = array();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified upstream.
		$raw = isset( $_POST['filters'] ) ? wp_unslash( $_POST['filters'] ) : array();
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( is_string( $raw ) ) {
			$decoded = json_decode( $raw, true );
			$raw     = is_array( $decoded ) ? $decoded : array();
		}
		if ( ! is_array( $raw ) ) {
			return $out;
		}

		foreach ( $allowed as $tax ) {
			$tax = sanitize_key( $tax );
			if ( ! taxonomy_exists( $tax ) ) {
				continue;
			}
			// Accept either "tax" or "dpg_tax" keys.
			$value = null;
			if ( isset( $raw[ $tax ] ) ) {
				$value = $raw[ $tax ];
			} elseif ( isset( $raw[ 'dpg_' . $tax ] ) ) {
				$value = $raw[ 'dpg_' . $tax ];
			}
			if ( null === $value || '' === $value ) {
				continue;
			}
			$ids = array_filter( array_map( 'intval', (array) $value ) );
			if ( $ids ) {
				$out[ $tax ] = array_values( $ids );
			}
		}
		return $out;
	}

	/**
	 * Read + sanitise the keyword search term from the request.
	 *
	 * @param array $atts Clean attributes.
	 * @return string
	 */
	private static function read_search( $atts ) {
		if ( 'yes' !== $atts['filter_search'] ) {
			return '';
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified upstream.
		$s = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		return $s;
	}
}
