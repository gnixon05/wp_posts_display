<?php
/**
 * Card style: Classic (meta below image).
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
	<?php if ( $card['has_image'] ) : ?>
		<a class="dpg-card-media" href="<?php echo esc_url( $card['permalink'] ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo $card['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP image markup. ?>
		</a>
	<?php endif; ?>

	<div class="dpg-card-body">
		<?php if ( $card['term'] ) : ?>
			<div class="dpg-card-terms">
				<?php if ( $card['term_url'] ) : ?>
					<a class="dpg-term-badge" href="<?php echo esc_url( $card['term_url'] ); ?>"><?php echo esc_html( $card['term']->name ); ?></a>
				<?php else : ?>
					<span class="dpg-term-badge"><?php echo esc_html( $card['term']->name ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( 'yes' === $atts['show_title'] && $card['title'] ) : ?>
			<h3 class="dpg-card-title">
				<a href="<?php echo esc_url( $card['permalink'] ); ?>"><?php echo esc_html( $card['title'] ); ?></a>
			</h3>
		<?php endif; ?>

		<?php if ( $card['date'] || $card['author'] ) : ?>
			<div class="dpg-card-meta">
				<?php if ( $card['avatar'] ) : ?>
					<span class="dpg-meta-avatar"><?php echo $card['avatar']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar markup. ?></span>
				<?php endif; ?>
				<?php if ( $card['author'] ) : ?>
					<span class="dpg-meta-author"><a href="<?php echo esc_url( $card['author_url'] ); ?>"><?php echo esc_html( $card['author'] ); ?></a></span>
				<?php endif; ?>
				<?php if ( $card['date'] ) : ?>
					<time class="dpg-meta-date" datetime="<?php echo esc_attr( $card['datetime'] ); ?>"><?php echo esc_html( $card['date'] ); ?></time>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $card['excerpt'] ) : ?>
			<div class="dpg-card-excerpt"><?php echo esc_html( $card['excerpt'] ); ?></div>
		<?php endif; ?>

		<?php if ( 'yes' === $atts['show_readmore'] ) : ?>
			<a class="dpg-card-readmore" href="<?php echo esc_url( $card['permalink'] ); ?>">
				<?php echo esc_html( DPG_Render::readmore_text( $atts ) ); ?>
				<span class="dpg-readmore-arrow" aria-hidden="true">&rarr;</span>
			</a>
		<?php endif; ?>
	</div>
</article>
