<?php
/**
 * Shared render layer for Dynamic Post Grid.
 *
 * One code path produces the markup for: the initial server render, the
 * "load more" append, and the filter-bar AJAX replace. Card templates live in
 * /templates and receive a prepared $card array + the element $atts.
 *
 * @package DynamicPostGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DPG_Render.
 */
class DPG_Render {

	/**
	 * Monotonic instance counter so multiple grids on one page get unique IDs.
	 *
	 * @var int
	 */
	private static $counter = 0;

	/**
	 * Render a complete element instance: optional filter bar + grid + pagination.
	 *
	 * @param array $atts Clean attributes.
	 * @return string HTML.
	 */
	public static function render( $atts ) {
		$atts = DPG_Query::sanitize( $atts );

		// Flag assets as needed for this request (conditional enqueue).
		DPG_Assets::mark_needed();

		$instance_id = 'dpg-' . ++self::$counter;

		// Apply incoming URL filters (progressive enhancement / shareable links).
		$runtime = self::runtime_from_request( $atts, $instance_id );

		$query = DPG_Query::build( $atts, $runtime );

		// Config travelling to the AJAX endpoint. Re-sanitised server-side on use.
		$config = self::ajax_config( $atts );

		$classes = array(
			'dpg',
			'dpg-instance',
			'dpg-style-' . $atts['style'],
			'dpg-mode-' . $atts['mode'],
			'dpg-hover-' . $atts['hover'],
		);

		ob_start();
		?>
		<div
			id="<?php echo esc_attr( $instance_id ); ?>"
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			style="<?php echo esc_attr( self::root_vars( $atts ) ); ?>"
			data-dpg-instance="<?php echo esc_attr( $instance_id ); ?>"
			data-dpg-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>"
			data-dpg-nonce="<?php echo esc_attr( wp_create_nonce( 'dpg_ajax' ) ); ?>"
			data-dpg-apply="<?php echo esc_attr( $atts['filter_apply'] ); ?>"
		>
			<?php
			if ( 'yes' === $atts['filter_enable'] ) {
				echo DPG_Filter::render_bar( $atts, $instance_id, $runtime ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
			<div class="dpg-grid-wrap" data-dpg-results>
				<?php echo self::render_grid_inner( $atts, $query, $runtime['paged'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<?php echo self::render_pagination( $atts, $query, $runtime['paged'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Render the grid container + its cards. Used by initial render and by the
	 * AJAX "replace" response (filter applied).
	 *
	 * @param array    $atts  Clean attributes.
	 * @param WP_Query $query Query.
	 * @param int      $paged Current page.
	 * @return string HTML.
	 */
	public static function render_grid_inner( $atts, $query, $paged = 1 ) {
		ob_start();

		if ( ! $query->have_posts() ) {
			echo self::render_empty(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return ob_get_clean();
		}

		$grid_class = 'dpg-grid';
		if ( 'carousel' === $atts['mode'] ) {
			$grid_class .= ' dpg-carousel';
		}
		?>
		<div class="<?php echo esc_attr( $grid_class ); ?>" data-dpg-grid role="list">
			<?php echo self::render_cards( $atts, $query, $paged ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render just the cards (no wrapper). Used by the "load more" append.
	 *
	 * @param array    $atts  Clean attributes.
	 * @param WP_Query $query Query.
	 * @param int      $paged Current page (for index continuity / featured item).
	 * @return string HTML.
	 */
	public static function render_cards( $atts, $query, $paged = 1 ) {
		ob_start();
		$index = ( max( 1, (int) $paged ) - 1 ) * (int) $atts['posts_per_page'];
		while ( $query->have_posts() ) {
			$query->the_post();
			echo self::render_card( $atts, get_post(), $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$index++;
		}
		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Render a single card by including the matching template.
	 *
	 * @param array   $atts  Clean attributes.
	 * @param WP_Post $post  Post object.
	 * @param int     $index Zero-based index within the full result set.
	 * @return string HTML.
	 */
	public static function render_card( $atts, $post, $index = 0 ) {
		$card     = self::card_data( $atts, $post, $index );
		$style    = in_array( $atts['style'], DPG_Query::allowed_styles(), true ) ? $atts['style'] : 'classic';
		$template = DPG_DIR . 'templates/card-' . $style . '.php';
		if ( ! file_exists( $template ) ) {
			$template = DPG_DIR . 'templates/card-classic.php';
		}
		return self::load_template( $template, $card, $atts );
	}

	/**
	 * Prepare the computed values a card template needs. Centralising this keeps
	 * every template thin and consistent.
	 *
	 * @param array   $atts  Clean attributes.
	 * @param WP_Post $post  Post.
	 * @param int     $index Index in result set.
	 * @return array
	 */
	public static function card_data( $atts, $post, $index = 0 ) {
		// Only the Magazine layout promotes the first item to a wide hero. The
		// Education preset uses an even grid of equal cards (matches the design).
		$is_featured = ( 'magazine' === $atts['style'] ) && 0 === (int) $index;

		// Featured items in magazine/education layouts read better with a larger crop.
		$image_size = $atts['image_size'];
		if ( $is_featured && in_array( $image_size, array( 'medium', 'thumbnail', 'medium_large' ), true ) ) {
			$image_size = 'large';
		}

		$card = array(
			'id'          => $post->ID,
			'index'       => (int) $index,
			'is_featured' => $is_featured,
			'permalink'   => get_permalink( $post ),
			'title'       => get_the_title( $post ),
			'image'       => '',
			'has_image'   => false,
			'excerpt'     => '',
			'date'        => '',
			'date_compact'=> '',
			'datetime'    => '',
			'author'      => '',
			'author_url'  => '',
			'avatar'      => '',
			'term'        => null,
			'term_url'    => '',
			'card_class'  => 'dpg-card dpg-card--' . $atts['style'],
		);

		if ( $is_featured ) {
			$card['card_class'] .= ' dpg-card--featured';
		}

		// Featured image (with fallback).
		if ( 'yes' === $atts['show_image'] ) {
			$img = self::featured_image( $atts, $post, $image_size );
			if ( $img ) {
				$card['image']     = $img;
				$card['has_image'] = true;
			}
		}

		// Excerpt.
		if ( 'yes' === $atts['show_excerpt'] && $atts['excerpt_length'] > 0 ) {
			$card['excerpt'] = self::excerpt( $post, $atts['excerpt_length'] );
		}

		// Date.
		if ( 'yes' === $atts['show_date'] ) {
			$card['date']         = get_the_date( '', $post );
			$card['date_compact'] = get_the_date( 'F Y', $post ); // e.g. "June 2026" (Education meta).
			$card['datetime']     = get_the_date( 'c', $post );
		}

		// Author + avatar.
		if ( 'yes' === $atts['show_author'] ) {
			$card['author']     = get_the_author_meta( 'display_name', $post->post_author );
			$card['author_url'] = get_author_posts_url( $post->post_author );
			if ( 'yes' === $atts['show_avatar'] ) {
				$card['avatar'] = get_avatar( $post->post_author, 40, '', $card['author'], array( 'class' => 'dpg-avatar' ) );
			}
		}

		// Primary term badge.
		if ( 'yes' === $atts['show_category'] ) {
			$term = self::primary_term( $post );
			if ( $term ) {
				$card['term']     = $term;
				$card['term_url'] = get_term_link( $term );
				if ( is_wp_error( $card['term_url'] ) ) {
					$card['term_url'] = '';
				}
			}
		}

		/**
		 * Filter the prepared card data before it reaches the template.
		 *
		 * @param array   $card Card data.
		 * @param WP_Post $post Post.
		 * @param array   $atts Attributes.
		 */
		return apply_filters( 'dpg_card_data', $card, $post, $atts );
	}

	/**
	 * Read-more text (filterable, escaped at template).
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function readmore_text( $atts ) {
		$text = $atts['readmore_text'] ? $atts['readmore_text'] : __( 'Read more', 'dynamic-post-grid' );
		return $text;
	}

	/* ----------------------------------------------------------------- *
	 * Pagination
	 * ----------------------------------------------------------------- */

	/**
	 * Render the pagination control matching the selected mode.
	 *
	 * @param array    $atts  Attributes.
	 * @param WP_Query $query Query.
	 * @param int      $paged Current page.
	 * @return string
	 */
	public static function render_pagination( $atts, $query, $paged ) {
		if ( 'none' === $atts['pagination'] || 'carousel' === $atts['mode'] ) {
			return '';
		}
		$max = (int) $query->max_num_pages;
		if ( $max < 2 ) {
			return '';
		}

		if ( 'numbered' === $atts['pagination'] ) {
			$links = paginate_links(
				array(
					'base'      => '%_%',
					'format'    => '?dpg_paged=%#%',
					'current'   => max( 1, $paged ),
					'total'     => $max,
					'type'      => 'array',
					'prev_text' => __( '&laquo; Prev', 'dynamic-post-grid' ),
					'next_text' => __( 'Next &raquo;', 'dynamic-post-grid' ),
				)
			);
			if ( ! $links ) {
				return '';
			}
			return '<nav class="dpg-pagination dpg-pagination--numbered" aria-label="' . esc_attr__( 'Posts navigation', 'dynamic-post-grid' ) . '"><ul><li>' . implode( '</li><li>', $links ) . '</li></ul></nav>';
		}

		// load more / infinite.
		$label   = $atts['loadmore_text'] ? $atts['loadmore_text'] : __( 'Load more', 'dynamic-post-grid' );
		$is_inf  = ( 'infinite' === $atts['pagination'] ) ? '1' : '0';
		$next    = $paged + 1;
		$visible = ( $next <= $max );

		ob_start();
		?>
		<div class="dpg-pagination dpg-pagination--loadmore" data-dpg-loadmore data-dpg-infinite="<?php echo esc_attr( $is_inf ); ?>" data-dpg-page="<?php echo esc_attr( $paged ); ?>" data-dpg-max="<?php echo esc_attr( $max ); ?>">
			<button type="button" class="dpg-loadmore-btn"<?php echo $visible ? '' : ' hidden'; ?>>
				<span class="dpg-loadmore-label"><?php echo esc_html( $label ); ?></span>
				<span class="dpg-spinner" aria-hidden="true"></span>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Markup shown when a query returns no posts.
	 *
	 * @return string
	 */
	public static function render_empty() {
		$msg = apply_filters( 'dpg_empty_message', __( 'No posts found.', 'dynamic-post-grid' ) );
		return '<div class="dpg-empty" role="status">' . esc_html( $msg ) . '</div>';
	}

	/* ----------------------------------------------------------------- *
	 * Internals
	 * ----------------------------------------------------------------- */

	/**
	 * Include a template in an isolated scope exposing $card and $atts.
	 *
	 * @param string $file Template path.
	 * @param array  $card Card data.
	 * @param array  $atts Attributes.
	 * @return string
	 */
	private static function load_template( $file, $card, $atts ) {
		ob_start();
		include $file;
		return ob_get_clean();
	}

	/**
	 * Build the featured image markup with size + graceful fallback.
	 *
	 * @param array   $atts Attributes.
	 * @param WP_Post $post Post.
	 * @param string  $size Image size.
	 * @return string Empty string when there is nothing to show.
	 */
	private static function featured_image( $atts, $post, $size ) {
		if ( has_post_thumbnail( $post ) ) {
			return get_the_post_thumbnail(
				$post,
				$size,
				array(
					'class'   => 'dpg-img',
					'loading' => 'lazy',
					'alt'     => the_title_attribute( array( 'echo' => false, 'post' => $post ) ),
				)
			);
		}

		// Fallback (attachment ID or raw URL).
		$fallback = $atts['fallback_image'];
		if ( $fallback ) {
			if ( is_numeric( $fallback ) ) {
				$html = wp_get_attachment_image( (int) $fallback, $size, false, array( 'class' => 'dpg-img dpg-img--fallback', 'loading' => 'lazy' ) );
				if ( $html ) {
					return $html;
				}
			} else {
				$url = esc_url( $fallback );
				if ( $url ) {
					return '<img class="dpg-img dpg-img--fallback" src="' . $url . '" alt="" loading="lazy" />';
				}
			}
		}
		return '';
	}

	/**
	 * Build a trimmed excerpt of N words.
	 *
	 * @param WP_Post $post   Post.
	 * @param int     $length Word count.
	 * @return string
	 */
	private static function excerpt( $post, $length ) {
		if ( has_excerpt( $post ) ) {
			$text = get_the_excerpt( $post );
		} else {
			$text = (string) $post->post_content;

			// Drop heading/script/style blocks first so an obvious header or
			// repeated title at the top of the content doesn't bleed into the
			// preview (e.g. "EVENT RECAP | October 2025 The Philanthropy…").
			$text = preg_replace( '#<h[1-6][^>]*>.*?</h[1-6]>#is', ' ', $text );
			$text = preg_replace( '#<(script|style)[^>]*>.*?</\1>#is', ' ', $text );

			// NOTE: strip_shortcodes() would delete the *content* of page-builder
			// shortcodes (e.g. WPBakery [vc_*]) entirely, leaving pages with no
			// preview text — so strip only the bracket tokens and keep inner text.
			$text = preg_replace( '/\[[^\]]*\]/', ' ', $text );
			$text = wp_strip_all_tags( $text );
			$text = preg_replace( '/\s+/', ' ', $text );
			$text = trim( $text );

			// If the content still begins with a "CATEGORY | Month YYYY" style
			// header line (common when builders render meta as plain text), drop it.
			$text = preg_replace(
				'/^[\p{L}\p{N}&,\'\-\/ ]{2,40}\s*[|·\x{2022}\-]\s*(?:January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{4}\s*/u',
				'',
				$text
			);

			// If it still leads with the exact post title, strip that too.
			$title = trim( wp_strip_all_tags( get_the_title( $post ) ) );
			if ( '' !== $title && 0 === stripos( $text, $title ) ) {
				$text = trim( substr( $text, strlen( $title ) ) );
			}
		}

		/**
		 * Filter the cleaned excerpt source before it is trimmed to length.
		 *
		 * @param string  $text Cleaned text.
		 * @param WP_Post $post Post.
		 */
		$text = apply_filters( 'dpg_excerpt_source', $text, $post );

		$text = wp_trim_words( $text, (int) $length, '&hellip;' );
		return $text;
	}

	/**
	 * Determine the primary term for the badge: Yoast primary if set, else the
	 * first term of the first taxonomy attached to the post type.
	 *
	 * @param WP_Post $post Post.
	 * @return WP_Term|null
	 */
	private static function primary_term( $post ) {
		$taxes = get_object_taxonomies( $post->post_type, 'names' );
		// Prefer 'category' when present.
		if ( in_array( 'category', $taxes, true ) ) {
			$taxes = array_merge( array( 'category' ), array_diff( $taxes, array( 'category' ) ) );
		}
		foreach ( $taxes as $tax ) {
			$tax_obj = get_taxonomy( $tax );
			if ( ! $tax_obj || ! $tax_obj->public ) {
				continue;
			}
			$terms = get_the_terms( $post, $tax );
			if ( $terms && ! is_wp_error( $terms ) ) {
				// Honour Yoast primary term if available.
				$primary_id = (int) get_post_meta( $post->ID, '_yoast_wpseo_primary_' . $tax, true );
				if ( $primary_id ) {
					foreach ( $terms as $t ) {
						if ( (int) $t->term_id === $primary_id ) {
							return $t;
						}
					}
				}
				return $terms[0];
			}
		}
		return null;
	}

	/**
	 * Emit the scoped CSS custom properties for this instance (no :root leakage).
	 *
	 * @param array $atts Attributes.
	 * @return string Inline style string.
	 */
	private static function root_vars( $atts ) {
		$vars = array(
			'--dpg-columns'        => (int) $atts['columns'],
			'--dpg-columns-tablet' => (int) $atts['columns_tablet'],
			'--dpg-columns-mobile' => (int) $atts['columns_mobile'],
			'--dpg-gap'            => (int) $atts['gap'] . 'px',
			'--dpg-card-radius'    => (int) $atts['card_radius'] . 'px',
		);

		// Optional filter-bar colour overrides (already hex-sanitised).
		if ( $atts['filter_bg'] ) {
			$vars['--dpg-bar-bg'] = $atts['filter_bg'];
		}
		if ( $atts['filter_text'] ) {
			$vars['--dpg-bar-text']  = $atts['filter_text'];
			$vars['--dpg-bar-muted'] = $atts['filter_text'];
		}
		if ( $atts['filter_field_bg'] ) {
			$vars['--dpg-pill-bg'] = $atts['filter_field_bg'];
		}
		if ( $atts['filter_field_text'] ) {
			$vars['--dpg-pill-text'] = $atts['filter_field_text'];
		}

		$out = '';
		foreach ( $vars as $k => $v ) {
			$out .= $k . ':' . $v . ';';
		}
		return $out;
	}

	/**
	 * The subset of attributes that travel to AJAX as the query "base".
	 *
	 * @param array $atts Attributes.
	 * @return array
	 */
	private static function ajax_config( $atts ) {
		// Everything is re-sanitised server-side; this is just the base shape.
		return $atts;
	}

	/**
	 * Read incoming filter values from the request (URL/GET) for the initial
	 * server render, namespaced per instance.
	 *
	 * @param array  $atts        Attributes.
	 * @param string $instance_id Instance id.
	 * @return array runtime array (paged, tax, s).
	 */
	public static function runtime_from_request( $atts, $instance_id = '' ) {
		$runtime = array(
			'paged' => 1,
			'tax'   => array(),
			's'     => '',
		);

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only public filters; GET fallback for no-JS.
		if ( isset( $_GET['dpg_paged'] ) ) {
			$runtime['paged'] = max( 1, (int) $_GET['dpg_paged'] );
		}
		if ( isset( $_GET['dpg_s'] ) && 'yes' === $atts['filter_search'] ) {
			$runtime['s'] = sanitize_text_field( wp_unslash( $_GET['dpg_s'] ) );
		}
		$taxes = $atts['filter_taxonomies'] ? explode( ',', $atts['filter_taxonomies'] ) : array();
		foreach ( $taxes as $tax ) {
			$key = 'dpg_' . $tax;
			if ( isset( $_GET[ $key ] ) && '' !== $_GET[ $key ] ) {
				$runtime['tax'][ $tax ] = array_filter( array_map( 'intval', (array) wp_unslash( $_GET[ $key ] ) ) );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return $runtime;
	}
}
