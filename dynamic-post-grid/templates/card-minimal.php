<?php
/**
 * Card style: Minimal (no image emphasis — title + meta, tight).
 *
 * @var array $card Prepared card data.
 * @var array $atts Element attributes.
 *
 * @package DynamicPostGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article class="<?php echo esc_attr( $card['card_class'] ); ?>" role="listitem">
	<div class="dpg-card-body">
		<?php if ( $card['term'] ) : ?>
			<span class="dpg-term-badge dpg-term-badge--inline"><?php echo esc_html( $card['term']->name ); ?></span>
		<?php endif; ?>

		<?php if ( 'yes' === $atts['show_title'] && $card['title'] ) : ?>
			<h3 class="dpg-card-title">
				<a href="<?php echo esc_url( $card['permalink'] ); ?>"><?php echo esc_html( $card['title'] ); ?></a>
			</h3>
		<?php endif; ?>

		<?php if ( $card['date'] ) : ?>
			<time class="dpg-meta-date" datetime="<?php echo esc_attr( $card['datetime'] ); ?>"><?php echo esc_html( $card['date'] ); ?></time>
		<?php endif; ?>

		<?php if ( $card['excerpt'] ) : ?>
			<div class="dpg-card-excerpt"><?php echo esc_html( $card['excerpt'] ); ?></div>
		<?php endif; ?>

		<?php if ( 'yes' === $atts['show_readmore'] ) : ?>
			<a class="dpg-card-readmore" href="<?php echo esc_url( $card['permalink'] ); ?>"><?php echo esc_html( DPG_Render::readmore_text( $atts ) ); ?> <span class="dpg-readmore-arrow" aria-hidden="true">&rarr;</span></a>
		<?php endif; ?>
	</div>
</article>
