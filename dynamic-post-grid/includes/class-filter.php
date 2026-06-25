<?php
/**
 * Filter bar: taxonomy discovery + bar markup (Part 4).
 *
 * The bar is fully configurable — the admin assigns any number of taxonomies
 * registered on the queried post type; each renders as a labelled "All"-default
 * dropdown. A free-text keyword field runs an `s=` search. No taxonomy is
 * hardcoded.
 *
 * @package DynamicPostGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DPG_Filter.
 */
class DPG_Filter {

	/**
	 * Render the filter bar for an element instance.
	 *
	 * @param array  $atts        Clean attributes.
	 * @param string $instance_id Instance DOM id.
	 * @param array  $runtime     Active filter values (paged, tax, s).
	 * @return string HTML.
	 */
	public static function render_bar( $atts, $instance_id, $runtime = array() ) {
		if ( 'yes' !== $atts['filter_enable'] ) {
			return '';
		}

		$taxonomies = self::resolve_taxonomies( $atts );
		$labels     = self::parse_labels( $atts['filter_labels'] );
		$active_tax = isset( $runtime['tax'] ) ? (array) $runtime['tax'] : array();
		$active_s   = isset( $runtime['s'] ) ? (string) $runtime['s'] : '';

		// Nothing to show? Bail rather than render an empty bar.
		if ( empty( $taxonomies ) && 'yes' !== $atts['filter_search'] ) {
			return '';
		}

		// Build dropdown descriptors for the template.
		$dropdowns = array();
		foreach ( $taxonomies as $tax ) {
			$tax_obj = get_taxonomy( $tax );
			if ( ! $tax_obj ) {
				continue;
			}
			$terms = self::get_terms_for( $tax, $atts );
			if ( empty( $terms ) ) {
				continue;
			}
			$dropdowns[] = array(
				'taxonomy' => $tax,
				'label'    => isset( $labels[ $tax ] ) ? $labels[ $tax ] : $tax_obj->labels->singular_name,
				'name'     => 'dpg_' . $tax,
				'terms'    => $terms,
				'selected' => isset( $active_tax[ $tax ] ) ? array_map( 'intval', (array) $active_tax[ $tax ] ) : array(),
			);
		}

		$search_label = $atts['filter_search_label'] ? $atts['filter_search_label'] : __( 'Search', 'dynamic-post-grid' );

		// Render the template in an isolated scope.
		ob_start();
		$show_search = ( 'yes' === $atts['filter_search'] );
		$apply_mode  = $atts['filter_apply'];
		include DPG_DIR . 'templates/filter-bar.php';
		return ob_get_clean();
	}

	/**
	 * Resolve which taxonomies to show. If the admin specified some, use those;
	 * otherwise fall back to the public taxonomies on the queried post type.
	 *
	 * @param array $atts Attributes.
	 * @return string[] Taxonomy slugs.
	 */
	public static function resolve_taxonomies( $atts ) {
		if ( $atts['filter_taxonomies'] ) {
			return array_filter( array_map( 'sanitize_key', explode( ',', $atts['filter_taxonomies'] ) ) );
		}

		// Auto-discover: public taxonomies on the first post type, minus formats.
		$post_types = $atts['post_type'];
		$found      = array();
		foreach ( (array) $post_types as $pt ) {
			foreach ( get_object_taxonomies( $pt, 'objects' ) as $tax_obj ) {
				if ( $tax_obj->public && $tax_obj->show_ui && 'post_format' !== $tax_obj->name ) {
					$found[ $tax_obj->name ] = true;
				}
			}
		}
		return array_keys( $found );
	}

	/**
	 * Get terms for a taxonomy honouring the "used vs all" scope.
	 *
	 * @param string $tax  Taxonomy slug.
	 * @param array  $atts Attributes.
	 * @return WP_Term[]
	 */
	public static function get_terms_for( $tax, $atts ) {
		$args = array(
			'taxonomy'   => $tax,
			'hide_empty' => ( 'all' !== $atts['filter_terms_scope'] ),
			'orderby'    => 'name',
			'order'      => 'ASC',
		);
		/**
		 * Filter the get_terms args used to populate a filter dropdown.
		 *
		 * @param array  $args Term query args.
		 * @param string $tax  Taxonomy.
		 * @param array  $atts Attributes.
		 */
		$args  = apply_filters( 'dpg_filter_terms_args', $args, $tax, $atts );
		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) ) {
			return array();
		}
		return $terms;
	}

	/**
	 * Parse a "tax:Label, tax2:Label2" string into a map.
	 *
	 * @param string $raw Raw label string.
	 * @return array
	 */
	private static function parse_labels( $raw ) {
		$map = array();
		if ( ! $raw ) {
			return $map;
		}
		foreach ( preg_split( '/[,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY ) as $pair ) {
			$parts = explode( ':', $pair, 2 );
			if ( count( $parts ) === 2 ) {
				$map[ sanitize_key( trim( $parts[0] ) ) ] = sanitize_text_field( trim( $parts[1] ) );
			}
		}
		return $map;
	}
}
