<?php
/**
 * Card style: Magazine (featured-first). The first item spans wide with an
 * overlaid title; remaining items render as compact classic cards.
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
	<a class="dpg-card-link" href="<?php echo esc_url( $card['permalink'] ); ?>">
		<div class="dpg-card-media">
			<?php
			if ( $card['has_image'] ) {
				echo $card['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP image markup.
			} else {
				echo '<span class="dpg-img dpg-img--placeholder" aria-hidden="true"></span>';
			}
			?>
			<span class="dpg-card-scrim" aria-hidden="true"></span>
		</div>
		<div class="dpg-card-overlay-body">
			<?php if ( $card['term'] ) : ?>
				<span class="dpg-term-badge"><?php echo esc_html( $card['term']->name ); ?></span>
			<?php endif; ?>

			<?php if ( 'yes' === $atts['show_title'] && $card['title'] ) : ?>
				<h3 class="dpg-card-title"><?php echo esc_html( $card['title'] ); ?></h3>
			<?php endif; ?>

			<?php if ( $card['date'] ) : ?>
				<time class="dpg-meta-date" datetime="<?php echo esc_attr( $card['datetime'] ); ?>"><?php echo esc_html( $card['date'] ); ?></time>
			<?php endif; ?>

			<?php if ( $card['is_featured'] && $card['excerpt'] ) : ?>
				<div class="dpg-card-excerpt"><?php echo esc_html( $card['excerpt'] ); ?></div>
			<?php endif; ?>
		</div>
	</a>
</article>
