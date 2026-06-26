<?php
/**
 * Filter bar template.
 *
 * Available in scope (from DPG_Filter::render_bar):
 * @var array  $atts         Element attributes.
 * @var string $instance_id  Instance DOM id.
 * @var array  $dropdowns    Each: taxonomy, label, name, terms[], selected[].
 * @var bool   $show_search  Whether to render the keyword field.
 * @var string $search_label Label for the keyword field.
 * @var string $active_s     Active keyword value.
 * @var string $apply_mode   'live' | 'submit'.
 *
 * @package DynamicPostGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form class="dpg-filter-bar" data-dpg-filter method="get" action="" role="search" aria-label="<?php esc_attr_e( 'Filter posts', 'dynamic-post-grid' ); ?>">
	<div class="dpg-filter-inner">

		<div class="dpg-filter-top">
			<button type="button" class="dpg-filter-reset" data-dpg-reset><?php esc_html_e( 'Clear', 'dynamic-post-grid' ); ?></button>
		</div>

		<div class="dpg-filter-row">

			<?php foreach ( $dropdowns as $dd ) : ?>
				<div class="dpg-filter-group dpg-filter-group--tax">
					<label class="dpg-filter-label" for="<?php echo esc_attr( $instance_id . '-' . $dd['name'] ); ?>">
						<?php echo esc_html( $dd['label'] ); ?>
					</label>
					<div class="dpg-select-wrap">
						<select
							class="dpg-select"
							id="<?php echo esc_attr( $instance_id . '-' . $dd['name'] ); ?>"
							name="<?php echo esc_attr( $dd['name'] ); ?>"
							data-dpg-taxonomy="<?php echo esc_attr( $dd['taxonomy'] ); ?>"
						>
							<option value=""><?php esc_html_e( 'All', 'dynamic-post-grid' ); ?></option>
							<?php foreach ( $dd['terms'] as $term ) : ?>
								<option
									value="<?php echo esc_attr( $term->term_id ); ?>"
									<?php selected( in_array( (int) $term->term_id, $dd['selected'], true ) ); ?>
								>
									<?php echo esc_html( $term->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			<?php endforeach; ?>

			<?php if ( $show_search ) : ?>
				<div class="dpg-filter-group dpg-filter-group--search">
					<label class="dpg-filter-label" for="<?php echo esc_attr( $instance_id . '-s' ); ?>">
						<?php echo esc_html( $search_label ); ?>
					</label>
					<div class="dpg-search-wrap">
						<input
							type="search"
							class="dpg-search-input"
							id="<?php echo esc_attr( $instance_id . '-s' ); ?>"
							name="dpg_s"
							value="<?php echo esc_attr( $active_s ); ?>"
							placeholder="<?php esc_attr_e( 'Keyword&hellip;', 'dynamic-post-grid' ); ?>"
							data-dpg-search
							autocomplete="off"
						/>
						<button type="submit" class="dpg-search-btn" aria-label="<?php esc_attr_e( 'Search', 'dynamic-post-grid' ); ?>">
							<svg class="dpg-icon-search" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
								<circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"></circle>
								<line x1="16.5" y1="16.5" x2="21" y2="21" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line>
							</svg>
						</button>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( 'submit' === $apply_mode ) : ?>
				<div class="dpg-filter-group dpg-filter-group--apply">
					<button type="submit" class="dpg-filter-apply"><?php esc_html_e( 'Apply', 'dynamic-post-grid' ); ?></button>
				</div>
			<?php endif; ?>

		</div>
	</div>
</form>
<?php
// Note for the integrator (Part 4 interpretation), emitted only with WP_DEBUG.
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	echo "\n<!-- dpg: filter dropdowns are admin-assigned taxonomies (not hardcoded); the keyword field runs an s= search. -->\n";
}
