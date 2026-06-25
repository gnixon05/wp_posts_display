<?php
/**
 * Card style: Education / Featured Magazine preset.
 *
 * Replicates the texascensus.org/education grid pattern: a large featured hero
 * card leading the grid, followed by a responsive multi-column run of article
 * cards (image on top, category badge, title, date, excerpt, read-more arrow).
 *
 * NOTE: The live page could not be fetched from this environment (network
 * policy denied texascensus.org), so this preset is built to the standard
 * featured-magazine composition and is fully driven by the scoped --dpg-edu-*
 * variables in the stylesheet — tune those to pixel-match once the live DOM
 * can be inspected. The markup is intentionally portable (no theme CSS deps).
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
	<a class="dpg-edu-link" href="<?php echo esc_url( $card['permalink'] ); ?>">
		<div class="dpg-edu-media">
			<?php
			if ( $card['has_image'] ) {
				echo $card['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP image markup.
			} else {
				echo '<span class="dpg-img dpg-img--placeholder" aria-hidden="true"></span>';
			}
			?>
			<?php if ( $card['term'] ) : ?>
				<span class="dpg-edu-badge"><?php echo esc_html( $card['term']->name ); ?></span>
			<?php endif; ?>
		</div>

		<div class="dpg-edu-body">
			<?php if ( 'yes' === $atts['show_title'] && $card['title'] ) : ?>
				<h3 class="dpg-edu-title"><?php echo esc_html( $card['title'] ); ?></h3>
			<?php endif; ?>

			<?php if ( $card['date'] || $card['author'] ) : ?>
				<div class="dpg-edu-meta">
					<?php if ( $card['author'] ) : ?>
						<span class="dpg-edu-author"><?php echo esc_html( $card['author'] ); ?></span>
						<?php if ( $card['date'] ) : ?><span class="dpg-edu-sep" aria-hidden="true">&middot;</span><?php endif; ?>
					<?php endif; ?>
					<?php if ( $card['date'] ) : ?>
						<time class="dpg-edu-date" datetime="<?php echo esc_attr( $card['datetime'] ); ?>"><?php echo esc_html( $card['date'] ); ?></time>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $card['excerpt'] ) : ?>
				<p class="dpg-edu-excerpt"><?php echo esc_html( $card['excerpt'] ); ?></p>
			<?php endif; ?>

			<span class="dpg-edu-more">
				<?php echo esc_html( DPG_Render::readmore_text( $atts ) ); ?>
				<span class="dpg-readmore-arrow" aria-hidden="true">&rarr;</span>
			</span>
		</div>
	</a>
</article>
