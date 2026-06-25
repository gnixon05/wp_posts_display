<?php
/**
 * Query builder + shared attribute schema for Dynamic Post Grid.
 *
 * This class is the single source of truth for the element's attributes:
 * defaults, sanitisation, and the translation of those attributes into a
 * WP_Query. The shortcode, WPBakery element and AJAX endpoints all funnel
 * through here so query behaviour never diverges between render paths.
 *
 * @package DynamicPostGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DPG_Query.
 */
class DPG_Query {

	/**
	 * Whitelisted orderby values (prevents arbitrary SQL ordering keys).
	 *
	 * @return string[]
	 */
	public static function allowed_orderby() {
		return array( 'date', 'modified', 'title', 'name', 'menu_order', 'rand', 'comment_count', 'meta_value', 'meta_value_num', 'ID', 'post__in' );
	}

	/**
	 * Whitelisted card styles / layout presets.
	 *
	 * @return string[]
	 */
	public static function allowed_styles() {
		return array( 'classic', 'overlay', 'minimal', 'magazine', 'education' );
	}

	/**
	 * Whitelisted hover effects.
	 *
	 * @return string[]
	 */
	public static function allowed_hover() {
		return array( 'none', 'zoom', 'overlay', 'lift' );
	}

	/**
	 * Whitelisted pagination modes.
	 *
	 * @return string[]
	 */
	public static function allowed_pagination() {
		return array( 'none', 'numbered', 'loadmore', 'infinite' );
	}

	/**
	 * Default attribute set shared by every entry point.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			// Source / query.
			'post_type'      => 'post',
			'posts_per_page' => 9,
			'offset'         => 0,
			'order'          => 'DESC',
			'orderby'        => 'date',
			'meta_key'       => '',
			'include_terms'  => '', // "tax:term-slug-or-id|term2; tax2:term3".
			'exclude_terms'  => '', // same syntax.
			'include_ids'    => '',
			'exclude_ids'    => '',
			'exclude_current'=> 'no',
			'sticky'         => 'default', // default | ignore | only.
			'meta_query'     => '', // JSON passthrough (advanced).

			// Layout.
			'style'          => 'classic',
			'columns'        => 3,
			'columns_tablet' => 2,
			'columns_mobile' => 1,
			'gap'            => 30,
			'mode'           => 'grid', // grid | carousel.

			// Card content / meta toggles.
			'show_image'     => 'yes',
			'image_size'     => 'large',
			'fallback_image' => '', // attachment ID or URL.
			'show_title'     => 'yes',
			'show_excerpt'   => 'yes',
			'excerpt_length' => 22,
			'show_date'      => 'yes',
			'show_author'    => 'no',
			'show_avatar'    => 'no',
			'show_category'  => 'yes',
			'show_readmore'  => 'no',
			'readmore_text'  => '',
			'hover'          => 'zoom',

			// Pagination.
			'pagination'     => 'none',
			'loadmore_text'  => '',

			// Filter bar (Part 4).
			'filter_enable'      => 'no',
			'filter_taxonomies'  => '', // comma list of taxonomy slugs.
			'filter_labels'      => '', // "tax:Label, tax2:Label2".
			'filter_search'      => 'yes',
			'filter_terms_scope' => 'used', // used | all.
			'filter_apply'       => 'live', // live | submit.
			'filter_search_label'=> '',
		);
	}

	/**
	 * Sanitise a raw attribute array (from shortcode, vc_map or AJAX/DOM).
	 *
	 * Never trust raw input: every value is cast / whitelisted here.
	 *
	 * @param array $raw Raw attributes.
	 * @return array Clean attributes merged over defaults.
	 */
	public static function sanitize( $raw ) {
		$d    = self::defaults();
		$raw  = is_array( $raw ) ? $raw : array();
		$atts = shortcode_atts( $d, $raw, 'dynamic_post_grid' );

		// Post type(s): only public, registered types are allowed.
		$atts['post_type'] = self::clean_post_types( $atts['post_type'] );

		$atts['posts_per_page'] = max( -1, (int) $atts['posts_per_page'] );
		if ( 0 === $atts['posts_per_page'] ) {
			$atts['posts_per_page'] = (int) $d['posts_per_page'];
		}
		$atts['offset']         = max( 0, (int) $atts['offset'] );
		$atts['order']          = ( 'ASC' === strtoupper( (string) $atts['order'] ) ) ? 'ASC' : 'DESC';
		$atts['orderby']        = in_array( $atts['orderby'], self::allowed_orderby(), true ) ? $atts['orderby'] : 'date';
		$atts['meta_key']       = sanitize_text_field( $atts['meta_key'] );

		$atts['include_terms']  = sanitize_text_field( $atts['include_terms'] );
		$atts['exclude_terms']  = sanitize_text_field( $atts['exclude_terms'] );
		$atts['include_ids']    = self::clean_id_list( $atts['include_ids'] );
		$atts['exclude_ids']    = self::clean_id_list( $atts['exclude_ids'] );
		$atts['exclude_current']= self::bool( $atts['exclude_current'] ) ? 'yes' : 'no';
		$atts['sticky']         = in_array( $atts['sticky'], array( 'default', 'ignore', 'only' ), true ) ? $atts['sticky'] : 'default';

		// Layout.
		$atts['style']          = in_array( $atts['style'], self::allowed_styles(), true ) ? $atts['style'] : 'classic';
		$atts['columns']        = min( 5, max( 1, (int) $atts['columns'] ) );
		$atts['columns_tablet'] = min( 5, max( 1, (int) $atts['columns_tablet'] ) );
		$atts['columns_mobile'] = min( 3, max( 1, (int) $atts['columns_mobile'] ) );
		$atts['gap']            = min( 120, max( 0, (int) $atts['gap'] ) );
		$atts['mode']           = ( 'carousel' === $atts['mode'] ) ? 'carousel' : 'grid';

		// Toggles.
		foreach ( array( 'show_image', 'show_title', 'show_excerpt', 'show_date', 'show_author', 'show_avatar', 'show_category', 'show_readmore' ) as $flag ) {
			$atts[ $flag ] = self::bool( $atts[ $flag ] ) ? 'yes' : 'no';
		}
		$atts['image_size']     = sanitize_text_field( $atts['image_size'] );
		$atts['fallback_image'] = sanitize_text_field( $atts['fallback_image'] );
		$atts['excerpt_length'] = max( 0, (int) $atts['excerpt_length'] );
		$atts['readmore_text']  = sanitize_text_field( $atts['readmore_text'] );
		$atts['hover']          = in_array( $atts['hover'], self::allowed_hover(), true ) ? $atts['hover'] : 'none';

		// Pagination.
		$atts['pagination']     = in_array( $atts['pagination'], self::allowed_pagination(), true ) ? $atts['pagination'] : 'none';
		$atts['loadmore_text']  = sanitize_text_field( $atts['loadmore_text'] );

		// Filter bar.
		$atts['filter_enable']       = self::bool( $atts['filter_enable'] ) ? 'yes' : 'no';
		$atts['filter_search']       = self::bool( $atts['filter_search'] ) ? 'yes' : 'no';
		$atts['filter_terms_scope']  = ( 'all' === $atts['filter_terms_scope'] ) ? 'all' : 'used';
		$atts['filter_apply']        = ( 'submit' === $atts['filter_apply'] ) ? 'submit' : 'live';
		$atts['filter_taxonomies']   = self::clean_taxonomies( $atts['filter_taxonomies'], $atts['post_type'] );
		$atts['filter_labels']       = sanitize_text_field( $atts['filter_labels'] );
		$atts['filter_search_label'] = sanitize_text_field( $atts['filter_search_label'] );

		return $atts;
	}

	/**
	 * Build a WP_Query from clean attributes plus optional runtime filters.
	 *
	 * @param array $atts    Clean attributes (already passed through sanitize()).
	 * @param array $runtime Optional runtime filters: paged, tax (array tax=>term ids), s.
	 * @return WP_Query
	 */
	public static function build( $atts, $runtime = array() ) {
		$runtime = wp_parse_args(
			$runtime,
			array(
				'paged' => 1,
				'tax'   => array(),
				's'     => '',
			)
		);

		$args = array(
			'post_type'           => count( $atts['post_type'] ) === 1 ? $atts['post_type'][0] : $atts['post_type'],
			'post_status'         => 'publish',
			'posts_per_page'      => $atts['posts_per_page'],
			'order'               => $atts['order'],
			'orderby'             => $atts['orderby'],
			'ignore_sticky_posts' => ( 'default' !== $atts['sticky'] ),
			'paged'               => max( 1, (int) $runtime['paged'] ),
		);

		if ( in_array( $atts['orderby'], array( 'meta_value', 'meta_value_num' ), true ) && $atts['meta_key'] ) {
			$args['meta_key'] = $atts['meta_key'];
		}

		// Offset: WP_Query disallows mixing 'offset' with paged natively, so we
		// emulate it via the offset filter on subsequent pages.
		if ( $atts['offset'] > 0 ) {
			$args['offset'] = $atts['offset'] + ( ( $args['paged'] - 1 ) * $atts['posts_per_page'] );
		}

		// Sticky "only".
		if ( 'only' === $atts['sticky'] ) {
			$stickies          = get_option( 'sticky_posts' );
			$args['post__in']  = ! empty( $stickies ) ? array_map( 'intval', $stickies ) : array( 0 );
			$args['orderby']   = 'post__in';
		}

		// Explicit include IDs.
		$include = self::ids_to_array( $atts['include_ids'] );
		if ( $include ) {
			$args['post__in'] = $include;
			if ( 'rand' !== $atts['orderby'] ) {
				$args['orderby'] = 'post__in';
			}
		}

		// Exclusions.
		$exclude = self::ids_to_array( $atts['exclude_ids'] );
		if ( 'yes' === $atts['exclude_current'] ) {
			$current = get_queried_object_id();
			if ( $current ) {
				$exclude[] = (int) $current;
			}
		}
		if ( $exclude ) {
			$args['post__not_in'] = array_values( array_unique( $exclude ) );
		}

		// Taxonomy include / exclude from element config.
		$tax_query = array();
		foreach ( self::parse_term_spec( $atts['include_terms'] ) as $tax => $terms ) {
			$tax_query[] = array(
				'taxonomy'         => $tax,
				'field'            => 'term_id',
				'terms'            => $terms,
				'operator'         => 'IN',
				'include_children' => true,
			);
		}
		foreach ( self::parse_term_spec( $atts['exclude_terms'] ) as $tax => $terms ) {
			$tax_query[] = array(
				'taxonomy' => $tax,
				'field'    => 'term_id',
				'terms'    => $terms,
				'operator' => 'NOT IN',
			);
		}

		// Runtime taxonomy filters (from the filter bar) — combine with AND.
		if ( ! empty( $runtime['tax'] ) && is_array( $runtime['tax'] ) ) {
			foreach ( $runtime['tax'] as $tax => $term_ids ) {
				$tax      = sanitize_key( $tax );
				$term_ids = array_filter( array_map( 'intval', (array) $term_ids ) );
				if ( $tax && taxonomy_exists( $tax ) && $term_ids ) {
					$tax_query[] = array(
						'taxonomy'         => $tax,
						'field'            => 'term_id',
						'terms'            => $term_ids,
						'operator'         => 'IN',
						'include_children' => true,
					);
				}
			}
		}

		if ( $tax_query ) {
			$tax_query['relation'] = 'AND';
			$args['tax_query']     = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		// Keyword search.
		if ( '' !== (string) $runtime['s'] ) {
			$args['s'] = sanitize_text_field( $runtime['s'] );
		}

		// Advanced meta_query passthrough (JSON).
		if ( $atts['meta_query'] ) {
			$decoded = json_decode( $atts['meta_query'], true );
			if ( is_array( $decoded ) ) {
				$args['meta_query'] = self::sanitize_meta_query( $decoded ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}
		}

		/**
		 * Filter the final WP_Query args before the query runs.
		 *
		 * @param array $args    Query args.
		 * @param array $atts    Element attributes.
		 * @param array $runtime Runtime filters.
		 */
		$args = apply_filters( 'dpg_query_args', $args, $atts, $runtime );

		return new WP_Query( $args );
	}

	/* ----------------------------------------------------------------- *
	 * Helpers
	 * ----------------------------------------------------------------- */

	/**
	 * Coerce a truthy string/bool to bool.
	 *
	 * @param mixed $v Value.
	 * @return bool
	 */
	public static function bool( $v ) {
		if ( is_bool( $v ) ) {
			return $v;
		}
		return in_array( strtolower( (string) $v ), array( '1', 'yes', 'true', 'on' ), true );
	}

	/**
	 * Clean a comma/space separated list of integers into a normalised string.
	 *
	 * @param string $list Raw list.
	 * @return string Comma list of ints.
	 */
	private static function clean_id_list( $list ) {
		$ids = self::ids_to_array( $list );
		return implode( ',', $ids );
	}

	/**
	 * Convert a list string to an array of positive ints.
	 *
	 * @param string $list Raw list.
	 * @return int[]
	 */
	private static function ids_to_array( $list ) {
		if ( is_array( $list ) ) {
			$parts = $list;
		} else {
			$parts = preg_split( '/[\s,]+/', (string) $list, -1, PREG_SPLIT_NO_EMPTY );
		}
		$ids = array();
		foreach ( (array) $parts as $p ) {
			$n = (int) $p;
			if ( $n > 0 ) {
				$ids[] = $n;
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Restrict post types to registered public types.
	 *
	 * @param mixed $types Raw value (comma string or array).
	 * @return string[] At least one valid type.
	 */
	public static function clean_post_types( $types ) {
		$public = get_post_types( array( 'public' => true ), 'names' );
		if ( is_array( $types ) ) {
			$parts = $types;
		} else {
			$parts = preg_split( '/[\s,]+/', (string) $types, -1, PREG_SPLIT_NO_EMPTY );
		}
		$clean = array();
		foreach ( (array) $parts as $t ) {
			$t = sanitize_key( $t );
			if ( $t && isset( $public[ $t ] ) ) {
				$clean[] = $t;
			}
		}
		if ( empty( $clean ) ) {
			$clean = array( 'post' );
		}
		return array_values( array_unique( $clean ) );
	}

	/**
	 * Restrict a taxonomy list to taxonomies registered on the given post types.
	 *
	 * @param mixed    $taxes      Raw value.
	 * @param string[] $post_types Clean post types.
	 * @return string Comma list of valid taxonomy slugs.
	 */
	public static function clean_taxonomies( $taxes, $post_types ) {
		$valid = array();
		foreach ( (array) $post_types as $pt ) {
			foreach ( get_object_taxonomies( $pt ) as $tax ) {
				$valid[ $tax ] = true;
			}
		}
		if ( is_array( $taxes ) ) {
			$parts = $taxes;
		} else {
			$parts = preg_split( '/[\s,]+/', (string) $taxes, -1, PREG_SPLIT_NO_EMPTY );
		}
		$clean = array();
		foreach ( (array) $parts as $t ) {
			$t = sanitize_key( $t );
			if ( $t && isset( $valid[ $t ] ) ) {
				$clean[] = $t;
			}
		}
		return implode( ',', array_values( array_unique( $clean ) ) );
	}

	/**
	 * Parse a term spec string "tax:termA|termB; tax2:termC" into
	 * array( tax => array( term_id, ... ) ). Terms may be slugs or IDs.
	 *
	 * @param string $spec Spec string.
	 * @return array
	 */
	private static function parse_term_spec( $spec ) {
		$out = array();
		if ( ! $spec ) {
			return $out;
		}
		$groups = preg_split( '/[;]+/', (string) $spec, -1, PREG_SPLIT_NO_EMPTY );
		foreach ( $groups as $group ) {
			$pair = explode( ':', $group, 2 );
			if ( count( $pair ) !== 2 ) {
				continue;
			}
			$tax = sanitize_key( trim( $pair[0] ) );
			if ( ! $tax || ! taxonomy_exists( $tax ) ) {
				continue;
			}
			$terms = preg_split( '/[|,]+/', $pair[1], -1, PREG_SPLIT_NO_EMPTY );
			$ids   = array();
			foreach ( $terms as $term ) {
				$term = trim( $term );
				if ( is_numeric( $term ) ) {
					$ids[] = (int) $term;
				} else {
					$obj = get_term_by( 'slug', sanitize_title( $term ), $tax );
					if ( $obj && ! is_wp_error( $obj ) ) {
						$ids[] = (int) $obj->term_id;
					}
				}
			}
			$ids = array_filter( array_map( 'intval', $ids ) );
			if ( $ids ) {
				$out[ $tax ] = array_values( array_unique( $ids ) );
			}
		}
		return $out;
	}

	/**
	 * Recursively sanitise a passthrough meta_query: keys/compares whitelisted.
	 *
	 * @param array $mq Decoded meta_query.
	 * @return array
	 */
	private static function sanitize_meta_query( $mq ) {
		$allowed_compare = array( '=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'EXISTS', 'NOT EXISTS' );
		$allowed_type    = array( 'NUMERIC', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'UNSIGNED', 'TIME' );
		$clean           = array();
		foreach ( $mq as $key => $clause ) {
			if ( 'relation' === $key ) {
				$clean['relation'] = ( 'OR' === strtoupper( (string) $clause ) ) ? 'OR' : 'AND';
				continue;
			}
			if ( ! is_array( $clause ) ) {
				continue;
			}
			// Nested clause.
			if ( isset( $clause['relation'] ) || ( ! isset( $clause['key'] ) && isset( $clause[0] ) && is_array( $clause[0] ) ) ) {
				$clean[] = self::sanitize_meta_query( $clause );
				continue;
			}
			$row = array();
			if ( isset( $clause['key'] ) ) {
				$row['key'] = sanitize_text_field( $clause['key'] );
			}
			if ( isset( $clause['value'] ) ) {
				$row['value'] = is_array( $clause['value'] ) ? array_map( 'sanitize_text_field', $clause['value'] ) : sanitize_text_field( $clause['value'] );
			}
			$row['compare'] = isset( $clause['compare'] ) && in_array( strtoupper( $clause['compare'] ), $allowed_compare, true ) ? strtoupper( $clause['compare'] ) : '=';
			if ( isset( $clause['type'] ) && in_array( strtoupper( $clause['type'] ), $allowed_type, true ) ) {
				$row['type'] = strtoupper( $clause['type'] );
			}
			if ( ! empty( $row ) ) {
				$clean[] = $row;
			}
		}
		return $clean;
	}
}
