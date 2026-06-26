/**
 * Dynamic Post Grid — Gutenberg block (editor).
 *
 * No build step: this uses the wp.* globals provided by WordPress core
 * (wp-blocks, wp-element, wp-block-editor, wp-components, wp-i18n,
 * wp-server-side-render). The block is dynamic — `save` returns null and the
 * front-end markup is produced by the PHP render_callback (DPG_Render::render),
 * so the block shares the shortcode/WPBakery render path.
 *
 * Version: 1.2.2
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.element ) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = ( wp.i18n && wp.i18n.__ ) ? wp.i18n.__ : function ( s ) { return s; };
	var InspectorControls = wp.blockEditor ? wp.blockEditor.InspectorControls : ( wp.editor && wp.editor.InspectorControls );
	var ServerSideRender = wp.serverSideRender || ( wp.components && wp.components.ServerSideRender );

	var C = wp.components;
	var PanelBody = C.PanelBody;
	var SelectControl = C.SelectControl;
	var TextControl = C.TextControl;
	var ToggleControl = C.ToggleControl;
	var RangeControl = C.RangeControl;
	var Placeholder = C.Placeholder;
	var Spinner = C.Spinner;

	var BLOCK = 'dpg/post-grid';

	/* Option sets mirror the shortcode / vc_map whitelists. */
	var OPT = {
		orderby: [
			{ label: __( 'Date', 'dynamic-post-grid' ), value: 'date' },
			{ label: __( 'Title', 'dynamic-post-grid' ), value: 'title' },
			{ label: __( 'Menu order', 'dynamic-post-grid' ), value: 'menu_order' },
			{ label: __( 'Random', 'dynamic-post-grid' ), value: 'rand' },
			{ label: __( 'Comment count', 'dynamic-post-grid' ), value: 'comment_count' },
			{ label: __( 'Modified', 'dynamic-post-grid' ), value: 'modified' },
			{ label: __( 'Meta value', 'dynamic-post-grid' ), value: 'meta_value' },
			{ label: __( 'Meta value (num)', 'dynamic-post-grid' ), value: 'meta_value_num' }
		],
		order: [
			{ label: __( 'Descending', 'dynamic-post-grid' ), value: 'DESC' },
			{ label: __( 'Ascending', 'dynamic-post-grid' ), value: 'ASC' }
		],
		sticky: [
			{ label: __( 'Default', 'dynamic-post-grid' ), value: 'default' },
			{ label: __( 'Ignore sticky', 'dynamic-post-grid' ), value: 'ignore' },
			{ label: __( 'Only sticky', 'dynamic-post-grid' ), value: 'only' }
		],
		style: [
			{ label: __( 'Classic (meta below)', 'dynamic-post-grid' ), value: 'classic' },
			{ label: __( 'Overlay (meta on image)', 'dynamic-post-grid' ), value: 'overlay' },
			{ label: __( 'Minimal', 'dynamic-post-grid' ), value: 'minimal' },
			{ label: __( 'Magazine (featured)', 'dynamic-post-grid' ), value: 'magazine' },
			{ label: __( 'Education / Featured Magazine', 'dynamic-post-grid' ), value: 'education' }
		],
		mode: [
			{ label: __( 'Grid', 'dynamic-post-grid' ), value: 'grid' },
			{ label: __( 'Carousel', 'dynamic-post-grid' ), value: 'carousel' }
		],
		hover: [
			{ label: __( 'None', 'dynamic-post-grid' ), value: 'none' },
			{ label: __( 'Zoom image', 'dynamic-post-grid' ), value: 'zoom' },
			{ label: __( 'Overlay fade', 'dynamic-post-grid' ), value: 'overlay' },
			{ label: __( 'Lift', 'dynamic-post-grid' ), value: 'lift' }
		],
		pagination: [
			{ label: __( 'None', 'dynamic-post-grid' ), value: 'none' },
			{ label: __( 'Numbered', 'dynamic-post-grid' ), value: 'numbered' },
			{ label: __( 'Load more button', 'dynamic-post-grid' ), value: 'loadmore' },
			{ label: __( 'Infinite scroll', 'dynamic-post-grid' ), value: 'infinite' }
		],
		termsScope: [
			{ label: __( 'Only used terms', 'dynamic-post-grid' ), value: 'used' },
			{ label: __( 'All terms', 'dynamic-post-grid' ), value: 'all' }
		],
		applyMode: [
			{ label: __( 'Live (on change)', 'dynamic-post-grid' ), value: 'live' },
			{ label: __( 'On submit', 'dynamic-post-grid' ), value: 'submit' }
		]
	};

	/* Small control factories bound to a block's attributes. */
	function makeControls( attributes, set ) {
		function bind( key ) {
			return function ( value ) {
				var o = {};
				o[ key ] = value;
				set( o );
			};
		}
		return {
			select: function ( label, key, options ) {
				return el( SelectControl, { label: label, value: attributes[ key ], options: options, onChange: bind( key ) } );
			},
			text: function ( label, key, help ) {
				return el( TextControl, { label: label, value: attributes[ key ], help: help, onChange: bind( key ) } );
			},
			number: function ( label, key, min, max ) {
				return el( RangeControl, { label: label, value: attributes[ key ], min: min, max: max, onChange: bind( key ), allowReset: true } );
			},
			toggle: function ( label, key ) {
				return el( ToggleControl, { label: label, checked: !! attributes[ key ], onChange: bind( key ) } );
			}
		};
	}

	function edit( props ) {
		var a = props.attributes;
		var ctl = makeControls( a, props.setAttributes );
		var useBlockProps = wp.blockEditor && wp.blockEditor.useBlockProps;
		var blockProps = useBlockProps ? useBlockProps( { className: 'dpg-block-editor-preview' } ) : { className: 'dpg-block-editor-preview' };

		var inspector = el(
			InspectorControls,
			{},
			el( PanelBody, { title: __( 'Source', 'dynamic-post-grid' ), initialOpen: true },
				ctl.text( __( 'Post type', 'dynamic-post-grid' ), 'post_type', __( 'A public post type slug (e.g. post, page, or a CPT).', 'dynamic-post-grid' ) ),
				ctl.number( __( 'Posts per page', 'dynamic-post-grid' ), 'posts_per_page', -1, 48 ),
				ctl.number( __( 'Offset', 'dynamic-post-grid' ), 'offset', 0, 50 ),
				ctl.select( __( 'Order by', 'dynamic-post-grid' ), 'orderby', OPT.orderby ),
				ctl.select( __( 'Order', 'dynamic-post-grid' ), 'order', OPT.order ),
				( a.orderby === 'meta_value' || a.orderby === 'meta_value_num' ) ? ctl.text( __( 'Meta key', 'dynamic-post-grid' ), 'meta_key' ) : null,
				ctl.text( __( 'Include terms', 'dynamic-post-grid' ), 'include_terms', __( 'taxonomy:term|term; taxonomy2:term', 'dynamic-post-grid' ) ),
				ctl.text( __( 'Exclude terms', 'dynamic-post-grid' ), 'exclude_terms' ),
				ctl.text( __( 'Include IDs', 'dynamic-post-grid' ), 'include_ids' ),
				ctl.text( __( 'Exclude IDs', 'dynamic-post-grid' ), 'exclude_ids' ),
				ctl.toggle( __( 'Exclude current post', 'dynamic-post-grid' ), 'exclude_current' ),
				ctl.select( __( 'Sticky posts', 'dynamic-post-grid' ), 'sticky', OPT.sticky )
			),
			el( PanelBody, { title: __( 'Layout', 'dynamic-post-grid' ), initialOpen: false },
				ctl.select( __( 'Card style / layout', 'dynamic-post-grid' ), 'style', OPT.style ),
				ctl.select( __( 'Mode', 'dynamic-post-grid' ), 'mode', OPT.mode ),
				ctl.number( __( 'Columns (desktop)', 'dynamic-post-grid' ), 'columns', 1, 5 ),
				ctl.number( __( 'Columns (tablet)', 'dynamic-post-grid' ), 'columns_tablet', 1, 4 ),
				ctl.number( __( 'Columns (mobile)', 'dynamic-post-grid' ), 'columns_mobile', 1, 3 ),
				ctl.number( __( 'Gap (px)', 'dynamic-post-grid' ), 'gap', 0, 80 ),
				ctl.number( __( 'Card corner radius (px)', 'dynamic-post-grid' ), 'card_radius', 0, 60 )
			),
			el( PanelBody, { title: __( 'Card Content', 'dynamic-post-grid' ), initialOpen: false },
				ctl.toggle( __( 'Featured image', 'dynamic-post-grid' ), 'show_image' ),
				a.show_image ? ctl.text( __( 'Image size', 'dynamic-post-grid' ), 'image_size' ) : null,
				ctl.toggle( __( 'Title', 'dynamic-post-grid' ), 'show_title' ),
				ctl.toggle( __( 'Excerpt', 'dynamic-post-grid' ), 'show_excerpt' ),
				a.show_excerpt ? ctl.number( __( 'Excerpt length (words)', 'dynamic-post-grid' ), 'excerpt_length', 0, 60 ) : null,
				ctl.toggle( __( 'Date', 'dynamic-post-grid' ), 'show_date' ),
				ctl.toggle( __( 'Author', 'dynamic-post-grid' ), 'show_author' ),
				a.show_author ? ctl.toggle( __( 'Author avatar', 'dynamic-post-grid' ), 'show_avatar' ) : null,
				ctl.toggle( __( 'Category / term badge', 'dynamic-post-grid' ), 'show_category' ),
				ctl.toggle( __( 'Read more link', 'dynamic-post-grid' ), 'show_readmore' ),
				a.show_readmore ? ctl.text( __( 'Read more text', 'dynamic-post-grid' ), 'readmore_text' ) : null,
				ctl.select( __( 'Hover effect', 'dynamic-post-grid' ), 'hover', OPT.hover )
			),
			el( PanelBody, { title: __( 'Pagination', 'dynamic-post-grid' ), initialOpen: false },
				ctl.select( __( 'Pagination', 'dynamic-post-grid' ), 'pagination', OPT.pagination ),
				( a.pagination === 'loadmore' || a.pagination === 'infinite' ) ? ctl.text( __( 'Load more text', 'dynamic-post-grid' ), 'loadmore_text' ) : null
			),
			el( PanelBody, { title: __( 'Filter Bar', 'dynamic-post-grid' ), initialOpen: false },
				ctl.toggle( __( 'Enable filter bar', 'dynamic-post-grid' ), 'filter_enable' ),
				a.filter_enable ? ctl.text( __( 'Filter taxonomies', 'dynamic-post-grid' ), 'filter_taxonomies', __( 'Comma list of taxonomy slugs. Empty = auto.', 'dynamic-post-grid' ) ) : null,
				a.filter_enable ? ctl.text( __( 'Custom labels', 'dynamic-post-grid' ), 'filter_labels', __( 'taxonomy:Label, taxonomy2:Label2', 'dynamic-post-grid' ) ) : null,
				a.filter_enable ? ctl.toggle( __( 'Keyword search', 'dynamic-post-grid' ), 'filter_search' ) : null,
				( a.filter_enable && a.filter_search ) ? ctl.text( __( 'Search label', 'dynamic-post-grid' ), 'filter_search_label' ) : null,
				a.filter_enable ? ctl.select( __( 'Term scope', 'dynamic-post-grid' ), 'filter_terms_scope', OPT.termsScope ) : null,
				a.filter_enable ? ctl.select( __( 'Apply mode', 'dynamic-post-grid' ), 'filter_apply', OPT.applyMode ) : null,
				a.filter_enable ? ctl.text( __( 'Bar background colour (hex)', 'dynamic-post-grid' ), 'filter_bg', __( 'e.g. #1f2430. Blank = default.', 'dynamic-post-grid' ) ) : null,
				a.filter_enable ? ctl.text( __( 'Bar text colour (hex)', 'dynamic-post-grid' ), 'filter_text' ) : null,
				a.filter_enable ? ctl.text( __( 'Field background colour (hex)', 'dynamic-post-grid' ), 'filter_field_bg' ) : null,
				a.filter_enable ? ctl.text( __( 'Field text colour (hex)', 'dynamic-post-grid' ), 'filter_field_text' ) : null
			)
		);

		var preview;
		if ( ServerSideRender ) {
			preview = el( ServerSideRender, {
				block: BLOCK,
				attributes: a,
				EmptyResponsePlaceholder: function () {
					return el( Placeholder, { label: __( 'Dynamic Post Grid', 'dynamic-post-grid' ) }, __( 'No posts match the current settings.', 'dynamic-post-grid' ) );
				},
				LoadingResponsePlaceholder: function () {
					return el( Placeholder, { label: __( 'Dynamic Post Grid', 'dynamic-post-grid' ) }, el( Spinner, {} ) );
				}
			} );
		} else {
			preview = el( Placeholder, { icon: 'grid-view', label: __( 'Dynamic Post Grid', 'dynamic-post-grid' ) }, __( 'Preview unavailable — the grid renders on the front end.', 'dynamic-post-grid' ) );
		}

		return el( Fragment, {}, inspector, el( 'div', blockProps, preview ) );
	}

	wp.blocks.registerBlockType( BLOCK, {
		edit: edit,
		save: function () { return null; } // Dynamic block — rendered in PHP.
	} );
} )( window.wp );
