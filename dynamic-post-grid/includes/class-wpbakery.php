<?php
/**
 * WPBakery Page Builder (js_composer) integration via vc_map().
 *
 * Registration is gated on function_exists('vc_map') so the plugin is inert on
 * sites without WPBakery. The element maps 1:1 to the [dynamic_post_grid]
 * shortcode, mirroring the full attribute set from DPG_Query::defaults().
 *
 * @package DynamicPostGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DPG_WPBakery.
 */
class DPG_WPBakery {

	/**
	 * Hook registration onto vc_before_init so vc_map() is available.
	 */
	public static function init() {
		add_action( 'vc_before_init', array( __CLASS__, 'map' ) );
	}

	/**
	 * Build dynamic dropdown values for registered public post types.
	 *
	 * @return array label => value.
	 */
	private static function post_type_options() {
		$out   = array();
		$types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $types as $type ) {
			$out[ $type->labels->singular_name . ' (' . $type->name . ')' ] = $type->name;
		}
		return $out ? $out : array( 'Post' => 'post' );
	}

	/**
	 * Build dropdown values for public taxonomies (used for filter examples).
	 *
	 * @return array label => value.
	 */
	private static function taxonomy_options() {
		$out   = array();
		$taxes = get_taxonomies( array( 'public' => true ), 'objects' );
		foreach ( $taxes as $tax ) {
			if ( 'post_format' === $tax->name ) {
				continue;
			}
			$out[ $tax->labels->singular_name . ' (' . $tax->name . ')' ] = $tax->name;
		}
		return $out;
	}

	/**
	 * Register the element.
	 */
	public static function map() {
		if ( ! function_exists( 'vc_map' ) ) {
			return;
		}

		$group_source     = __( 'Source', 'dynamic-post-grid' );
		$group_layout     = __( 'Layout', 'dynamic-post-grid' );
		$group_content    = __( 'Card Content', 'dynamic-post-grid' );
		$group_pagination = __( 'Pagination', 'dynamic-post-grid' );
		$group_filter     = __( 'Filter Bar', 'dynamic-post-grid' );

		$params = array(

			/* ---- Source ---- */
			array(
				'type'        => 'dropdown',
				'heading'     => __( 'Post type', 'dynamic-post-grid' ),
				'param_name'  => 'post_type',
				'value'       => self::post_type_options(),
				'std'         => 'post',
				'group'       => $group_source,
				'admin_label' => true,
			),
			array(
				'type'        => 'textfield',
				'heading'     => __( 'Posts per page', 'dynamic-post-grid' ),
				'param_name'  => 'posts_per_page',
				'value'       => '9',
				'description' => __( 'Use -1 for all.', 'dynamic-post-grid' ),
				'group'       => $group_source,
			),
			array(
				'type'       => 'textfield',
				'heading'    => __( 'Offset', 'dynamic-post-grid' ),
				'param_name' => 'offset',
				'value'      => '0',
				'group'      => $group_source,
			),
			array(
				'type'       => 'dropdown',
				'heading'    => __( 'Order by', 'dynamic-post-grid' ),
				'param_name' => 'orderby',
				'value'      => array(
					__( 'Date', 'dynamic-post-grid' )         => 'date',
					__( 'Title', 'dynamic-post-grid' )        => 'title',
					__( 'Menu order', 'dynamic-post-grid' )   => 'menu_order',
					__( 'Random', 'dynamic-post-grid' )       => 'rand',
					__( 'Comment count', 'dynamic-post-grid' )=> 'comment_count',
					__( 'Modified', 'dynamic-post-grid' )     => 'modified',
					__( 'Meta value', 'dynamic-post-grid' )   => 'meta_value',
					__( 'Meta value (num)', 'dynamic-post-grid' ) => 'meta_value_num',
				),
				'std'        => 'date',
				'group'      => $group_source,
			),
			array(
				'type'       => 'dropdown',
				'heading'    => __( 'Order', 'dynamic-post-grid' ),
				'param_name' => 'order',
				'value'      => array(
					__( 'Descending', 'dynamic-post-grid' ) => 'DESC',
					__( 'Ascending', 'dynamic-post-grid' )  => 'ASC',
				),
				'std'        => 'DESC',
				'group'      => $group_source,
			),
			array(
				'type'        => 'textfield',
				'heading'     => __( 'Meta key', 'dynamic-post-grid' ),
				'param_name'  => 'meta_key',
				'value'       => '',
				'description' => __( 'Used when ordering by meta value.', 'dynamic-post-grid' ),
				'dependency'  => array( 'element' => 'orderby', 'value' => array( 'meta_value', 'meta_value_num' ) ),
				'group'       => $group_source,
			),
			array(
				'type'        => 'textfield',
				'heading'     => __( 'Include terms', 'dynamic-post-grid' ),
				'param_name'  => 'include_terms',
				'value'       => '',
				'description' => __( 'Format: taxonomy:term-slug|term-slug; taxonomy2:term. Slugs or IDs.', 'dynamic-post-grid' ),
				'group'       => $group_source,
			),
			array(
				'type'        => 'textfield',
				'heading'     => __( 'Exclude terms', 'dynamic-post-grid' ),
				'param_name'  => 'exclude_terms',
				'value'       => '',
				'description' => __( 'Same format as include terms.', 'dynamic-post-grid' ),
				'group'       => $group_source,
			),
			array(
				'type'        => 'textfield',
				'heading'     => __( 'Include IDs', 'dynamic-post-grid' ),
				'param_name'  => 'include_ids',
				'value'       => '',
				'description' => __( 'Comma-separated post IDs.', 'dynamic-post-grid' ),
				'group'       => $group_source,
			),
			array(
				'type'        => 'textfield',
				'heading'     => __( 'Exclude IDs', 'dynamic-post-grid' ),
				'param_name'  => 'exclude_ids',
				'value'       => '',
				'group'       => $group_source,
			),
			array(
				'type'       => 'checkbox',
				'heading'    => __( 'Exclude current post', 'dynamic-post-grid' ),
				'param_name' => 'exclude_current',
				'value'      => array( __( 'Yes', 'dynamic-post-grid' ) => 'yes' ),
				'group'      => $group_source,
			),
			array(
				'type'       => 'dropdown',
				'heading'    => __( 'Sticky posts', 'dynamic-post-grid' ),
				'param_name' => 'sticky',
				'value'      => array(
					__( 'Default', 'dynamic-post-grid' )       => 'default',
					__( 'Ignore sticky', 'dynamic-post-grid' ) => 'ignore',
					__( 'Only sticky', 'dynamic-post-grid' )   => 'only',
				),
				'std'        => 'default',
				'group'      => $group_source,
			),
			array(
				'type'        => 'textarea_raw_html',
				'heading'     => __( 'Meta query (JSON)', 'dynamic-post-grid' ),
				'param_name'  => 'meta_query',
				'value'       => '',
				'description' => __( 'Advanced: a JSON meta_query passthrough.', 'dynamic-post-grid' ),
				'group'       => $group_source,
			),

			/* ---- Layout ---- */
			array(
				'type'        => 'dropdown',
				'heading'     => __( 'Card style / layout', 'dynamic-post-grid' ),
				'param_name'  => 'style',
				'value'       => array(
					__( 'Classic (meta below)', 'dynamic-post-grid' )         => 'classic',
					__( 'Overlay (meta on image)', 'dynamic-post-grid' )      => 'overlay',
					__( 'Minimal', 'dynamic-post-grid' )                      => 'minimal',
					__( 'Magazine (featured)', 'dynamic-post-grid' )          => 'magazine',
					__( 'Education / Featured Magazine', 'dynamic-post-grid' )=> 'education',
				),
				'std'         => 'classic',
				'group'       => $group_layout,
				'admin_label' => true,
			),
			array(
				'type'       => 'dropdown',
				'heading'    => __( 'Mode', 'dynamic-post-grid' ),
				'param_name' => 'mode',
				'value'      => array(
					__( 'Grid', 'dynamic-post-grid' )     => 'grid',
					__( 'Carousel', 'dynamic-post-grid' ) => 'carousel',
				),
				'std'        => 'grid',
				'group'      => $group_layout,
			),
			array(
				'type'       => 'dropdown',
				'heading'    => __( 'Columns (desktop)', 'dynamic-post-grid' ),
				'param_name' => 'columns',
				'value'      => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5' ),
				'std'        => '3',
				'group'      => $group_layout,
			),
			array(
				'type'       => 'dropdown',
				'heading'    => __( 'Columns (tablet)', 'dynamic-post-grid' ),
				'param_name' => 'columns_tablet',
				'value'      => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
				'std'        => '2',
				'group'      => $group_layout,
			),
			array(
				'type'       => 'dropdown',
				'heading'    => __( 'Columns (mobile)', 'dynamic-post-grid' ),
				'param_name' => 'columns_mobile',
				'value'      => array( '1' => '1', '2' => '2', '3' => '3' ),
				'std'        => '1',
				'group'      => $group_layout,
			),
			array(
				'type'       => 'textfield',
				'heading'    => __( 'Gap (px)', 'dynamic-post-grid' ),
				'param_name' => 'gap',
				'value'      => '30',
				'group'      => $group_layout,
			),
			array(
				'type'        => 'textfield',
				'heading'     => __( 'Card corner radius (px)', 'dynamic-post-grid' ),
				'param_name'  => 'card_radius',
				'value'       => '10',
				'description' => __( '0 for square corners.', 'dynamic-post-grid' ),
				'group'       => $group_layout,
			),

			/* ---- Card content ---- */
			array(
				'type'       => 'checkbox',
				'heading'    => __( 'Featured image', 'dynamic-post-grid' ),
				'param_name' => 'show_image',
				'value'      => array( __( 'Show', 'dynamic-post-grid' ) => 'yes' ),
				'std'        => 'yes',
				'group'      => $group_content,
			),
			array(
				'type'        => 'textfield',
				'heading'     => __( 'Image size', 'dynamic-post-grid' ),
				'param_name'  => 'image_size',
				'value'       => 'large',
				'description' => __( 'Any registered image size name.', 'dynamic-post-grid' ),
				'group'       => $group_content,
			),
			array(
				'type'        => 'attach_image',
				'heading'     => __( 'Fallback image', 'dynamic-post-grid' ),
				'param_name'  => 'fallback_image',
				'value'       => '',
				'description' => __( 'Shown when a post has no featured image.', 'dynamic-post-grid' ),
				'group'       => $group_content,
			),
			array(
				'type'       => 'checkbox',
				'heading'    => __( 'Title', 'dynamic-post-grid' ),
				'param_name' => 'show_title',
				'value'      => array( __( 'Show', 'dynamic-post-grid' ) => 'yes' ),
				'std'        => 'yes',
				'group'      => $group_content,
			),
			array(
				'type'       => 'checkbox',
				'heading'    => __( 'Excerpt', 'dynamic-post-grid' ),
				'param_name' => 'show_excerpt',
				'value'      => array( __( 'Show', 'dynamic-post-grid' ) => 'yes' ),
				'std'        => 'yes',
				'group'      => $group_content,
			),
			array(
				'type'       => 'textfield',
				'heading'    => __( 'Excerpt length (words)', 'dynamic-post-grid' ),
				'param_name' => 'excerpt_length',
				'value'      => '22',
				'group'      => $group_content,
			),
			array(
				'type'       => 'checkbox',
				'heading'    => __( 'Date', 'dynamic-post-grid' ),
				'param_name' => 'show_date',
				'value'      => array( __( 'Show', 'dynamic-post-grid' ) => 'yes' ),
				'std'        => 'yes',
				'group'      => $group_content,
			),
			array(
				'type'       => 'checkbox',
				'heading'    => __( 'Author', 'dynamic-post-grid' ),
				'param_name' => 'show_author',
				'value'      => array( __( 'Show', 'dynamic-post-grid' ) => 'yes' ),
				'group'      => $group_content,
			),
			array(
				'type'       => 'checkbox',
				'heading'    => __( 'Author avatar', 'dynamic-post-grid' ),
				'param_name' => 'show_avatar',
				'value'      => array( __( 'Show', 'dynamic-post-grid' ) => 'yes' ),
				'dependency' => array( 'element' => 'show_author', 'not_empty' => true ),
				'group'      => $group_content,
			),
			array(
				'type'       => 'checkbox',
				'heading'    => __( 'Category / term badge', 'dynamic-post-grid' ),
				'param_name' => 'show_category',
				'value'      => array( __( 'Show', 'dynamic-post-grid' ) => 'yes' ),
				'std'        => 'yes',
				'group'      => $group_content,
			),
			array(
				'type'       => 'checkbox',
				'heading'    => __( 'Read more link', 'dynamic-post-grid' ),
				'param_name' => 'show_readmore',
				'value'      => array( __( 'Show', 'dynamic-post-grid' ) => 'yes' ),
				'group'      => $group_content,
			),
			array(
				'type'       => 'textfield',
				'heading'    => __( 'Read more text', 'dynamic-post-grid' ),
				'param_name' => 'readmore_text',
				'value'      => '',
				'dependency' => array( 'element' => 'show_readmore', 'not_empty' => true ),
				'group'      => $group_content,
			),
			array(
				'type'       => 'dropdown',
				'heading'    => __( 'Hover effect', 'dynamic-post-grid' ),
				'param_name' => 'hover',
				'value'      => array(
					__( 'None', 'dynamic-post-grid' )        => 'none',
					__( 'Zoom image', 'dynamic-post-grid' )  => 'zoom',
					__( 'Overlay fade', 'dynamic-post-grid' )=> 'overlay',
					__( 'Lift', 'dynamic-post-grid' )        => 'lift',
				),
				'std'        => 'zoom',
				'group'      => $group_content,
			),

			/* ---- Pagination ---- */
			array(
				'type'       => 'dropdown',
				'heading'    => __( 'Pagination', 'dynamic-post-grid' ),
				'param_name' => 'pagination',
				'value'      => array(
					__( 'None', 'dynamic-post-grid' )            => 'none',
					__( 'Numbered', 'dynamic-post-grid' )        => 'numbered',
					__( 'Load more button', 'dynamic-post-grid' )=> 'loadmore',
					__( 'Infinite scroll', 'dynamic-post-grid' ) => 'infinite',
				),
				'std'        => 'none',
				'group'      => $group_pagination,
			),
			array(
				'type'       => 'textfield',
				'heading'    => __( 'Load more text', 'dynamic-post-grid' ),
				'param_name' => 'loadmore_text',
				'value'      => '',
				'dependency' => array( 'element' => 'pagination', 'value' => array( 'loadmore', 'infinite' ) ),
				'group'      => $group_pagination,
			),

			/* ---- Filter bar ---- */
			array(
				'type'       => 'checkbox',
				'heading'    => __( 'Enable filter bar', 'dynamic-post-grid' ),
				'param_name' => 'filter_enable',
				'value'      => array( __( 'Yes', 'dynamic-post-grid' ) => 'yes' ),
				'group'      => $group_filter,
			),
			array(
				'type'        => 'checkbox',
				'heading'     => __( 'Filter taxonomies', 'dynamic-post-grid' ),
				'param_name'  => 'filter_taxonomies',
				'value'       => self::taxonomy_options(),
				'description' => __( 'Each selected taxonomy renders as a dropdown. Leave empty to auto-use the post type taxonomies.', 'dynamic-post-grid' ),
				'dependency'  => array( 'element' => 'filter_enable', 'not_empty' => true ),
				'group'       => $group_filter,
			),
			array(
				'type'        => 'textfield',
				'heading'     => __( 'Custom dropdown labels', 'dynamic-post-grid' ),
				'param_name'  => 'filter_labels',
				'value'       => '',
				'description' => __( 'Format: taxonomy:Label, taxonomy2:Label2.', 'dynamic-post-grid' ),
				'dependency'  => array( 'element' => 'filter_enable', 'not_empty' => true ),
				'group'       => $group_filter,
			),
			array(
				'type'       => 'checkbox',
				'heading'    => __( 'Keyword search', 'dynamic-post-grid' ),
				'param_name' => 'filter_search',
				'value'      => array( __( 'Show', 'dynamic-post-grid' ) => 'yes' ),
				'std'        => 'yes',
				'dependency' => array( 'element' => 'filter_enable', 'not_empty' => true ),
				'group'      => $group_filter,
			),
			array(
				'type'       => 'textfield',
				'heading'    => __( 'Search label', 'dynamic-post-grid' ),
				'param_name' => 'filter_search_label',
				'value'      => '',
				'dependency' => array( 'element' => 'filter_search', 'not_empty' => true ),
				'group'      => $group_filter,
			),
			array(
				'type'       => 'dropdown',
				'heading'    => __( 'Term scope', 'dynamic-post-grid' ),
				'param_name' => 'filter_terms_scope',
				'value'      => array(
					__( 'Only used terms', 'dynamic-post-grid' ) => 'used',
					__( 'All terms', 'dynamic-post-grid' )       => 'all',
				),
				'std'        => 'used',
				'dependency' => array( 'element' => 'filter_enable', 'not_empty' => true ),
				'group'      => $group_filter,
			),
			array(
				'type'       => 'dropdown',
				'heading'    => __( 'Apply mode', 'dynamic-post-grid' ),
				'param_name' => 'filter_apply',
				'value'      => array(
					__( 'Live (on change)', 'dynamic-post-grid' ) => 'live',
					__( 'On submit', 'dynamic-post-grid' )        => 'submit',
				),
				'std'        => 'live',
				'dependency' => array( 'element' => 'filter_enable', 'not_empty' => true ),
				'group'      => $group_filter,
			),
			array(
				'type'        => 'colorpicker',
				'heading'     => __( 'Bar background colour', 'dynamic-post-grid' ),
				'param_name'  => 'filter_bg',
				'value'       => '',
				'description' => __( 'Leave blank for the default dark bar.', 'dynamic-post-grid' ),
				'dependency'  => array( 'element' => 'filter_enable', 'not_empty' => true ),
				'group'       => $group_filter,
			),
			array(
				'type'       => 'colorpicker',
				'heading'    => __( 'Bar label/text colour', 'dynamic-post-grid' ),
				'param_name' => 'filter_text',
				'value'      => '',
				'dependency' => array( 'element' => 'filter_enable', 'not_empty' => true ),
				'group'      => $group_filter,
			),
			array(
				'type'        => 'colorpicker',
				'heading'     => __( 'Field background colour', 'dynamic-post-grid' ),
				'param_name'  => 'filter_field_bg',
				'value'       => '',
				'description' => __( 'Dropdowns and the search box.', 'dynamic-post-grid' ),
				'dependency'  => array( 'element' => 'filter_enable', 'not_empty' => true ),
				'group'       => $group_filter,
			),
			array(
				'type'       => 'colorpicker',
				'heading'    => __( 'Field text colour', 'dynamic-post-grid' ),
				'param_name' => 'filter_field_text',
				'value'      => '',
				'dependency' => array( 'element' => 'filter_enable', 'not_empty' => true ),
				'group'      => $group_filter,
			),
		);

		vc_map(
			array(
				'name'        => __( 'Dynamic Post Grid', 'dynamic-post-grid' ),
				'base'        => DPG_Shortcode::TAG,
				'category'    => __( 'Content', 'dynamic-post-grid' ),
				'description' => __( 'Configurable post grid with filter bar.', 'dynamic-post-grid' ),
				'icon'        => 'icon-wpb-post-grid',
				'params'      => $params,
			)
		);
	}
}
