<?php
/**
 * Card style: Education / Featured Magazine preset.
 *
 * Reproduces the texascensus.org/education grid: an even run of equal cards,
 * each centred — featured image on top, a "CATEGORY | Month Year" meta line,
 * a centred title, a preview excerpt, and a navy pill "Learn more" button.
 *
 * Driven by the scoped --dpg-edu-* / --dpg-card-* variables so it is portable
 * and restylable without editing markup.
 *
 * @var array $card Prepared card data.
 * @var array $atts Element attributes.
 *
 * @package DynamicPostGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dpg_btn_label = $atts['readmore_text'] ? $atts['readmore_text'] : __( 'Learn more', 'dynamic-post-grid' );
$dpg_has_cat   = ( 'yes' === $atts['show_category'] && $card['term'] );
$dpg_has_date  = ( 'yes' === $atts['show_date'] && $card['date_compact'] );
?>
<article class="<?php echo esc_attr( $card['card_class'] ); ?>" role="listitem">
	<a class="dpg-edu-link" href="<?php echo esc_url( $card['permalink'] ); ?>">
		<?php if ( $card['has_image'] ) : ?>
			<div class="dpg-edu-media">
				<?php echo $card['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP image markup. ?>
			</div>
		<?php endif; ?>

		<div class="dpg-edu-body">
			<?php if ( $dpg_has_cat || $dpg_has_date ) : ?>
				<div class="dpg-edu-meta">
					<?php if ( $dpg_has_cat ) : ?>
						<span class="dpg-edu-cat"><?php echo esc_html( $card['term']->name ); ?></span>
					<?php endif; ?>
					<?php if ( $dpg_has_cat && $dpg_has_date ) : ?>
						<span class="dpg-edu-sep" aria-hidden="true">|</span>
					<?php endif; ?>
					<?php if ( $dpg_has_date ) : ?>
						<time class="dpg-edu-date" datetime="<?php echo esc_attr( $card['datetime'] ); ?>"><?php echo esc_html( $card['date_compact'] ); ?></time>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( 'yes' === $atts['show_title'] && $card['title'] ) : ?>
				<h3 class="dpg-edu-title"><?php echo esc_html( $card['title'] ); ?></h3>
			<?php endif; ?>

			<?php if ( $card['excerpt'] ) : ?>
				<p class="dpg-edu-excerpt"><?php echo esc_html( $card['excerpt'] ); ?></p>
			<?php endif; ?>

			<span class="dpg-edu-btn"><?php echo esc_html( $dpg_btn_label ); ?></span>
		</div>
	</a>
</article>
