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

			<?php
			foreach ( $dropdowns as $dd ) :
				$dpg_selected = array_map( 'intval', (array) $dd['selected'] );
				$dpg_count    = count( $dpg_selected );

				// Summary text shown on the closed control.
				if ( 0 === $dpg_count ) {
					$dpg_summary = __( 'All', 'dynamic-post-grid' );
				} elseif ( 1 === $dpg_count ) {
					$dpg_summary = __( 'All', 'dynamic-post-grid' );
					foreach ( $dd['terms'] as $dpg_t ) {
						if ( (int) $dpg_t->term_id === $dpg_selected[0] ) {
							$dpg_summary = $dpg_t->name;
							break;
						}
					}
				} else {
					/* translators: %d: number of selected filter options. */
					$dpg_summary = sprintf( __( '%d selected', 'dynamic-post-grid' ), $dpg_count );
				}
				$dpg_field_id = $instance_id . '-' . $dd['name'];
				?>
				<div class="dpg-filter-group dpg-filter-group--tax">
					<label class="dpg-filter-label" id="<?php echo esc_attr( $dpg_field_id . '-lbl' ); ?>">
						<?php echo esc_html( $dd['label'] ); ?>
					</label>
					<details class="dpg-ms" data-dpg-ms data-dpg-taxonomy="<?php echo esc_attr( $dd['taxonomy'] ); ?>">
						<summary class="dpg-ms-toggle" role="button" aria-haspopup="listbox" aria-labelledby="<?php echo esc_attr( $dpg_field_id . '-lbl' ); ?>">
							<span class="dpg-ms-value" data-dpg-ms-value><?php echo esc_html( $dpg_summary ); ?></span>
							<span class="dpg-ms-caret" aria-hidden="true"></span>
						</summary>
						<?php $dpg_input_type = ! empty( $multiselect ) ? 'checkbox' : 'radio'; ?>
						<div class="dpg-ms-panel" role="group" aria-labelledby="<?php echo esc_attr( $dpg_field_id . '-lbl' ); ?>">
							<?php if ( empty( $multiselect ) ) : ?>
								<label class="dpg-ms-option">
									<input
										type="radio"
										name="<?php echo esc_attr( $dd['name'] ); ?>[]"
										value=""
										data-dpg-taxonomy="<?php echo esc_attr( $dd['taxonomy'] ); ?>"
										<?php checked( empty( $dpg_selected ) ); ?>
									/>
									<span class="dpg-ms-option-label"><?php esc_html_e( 'All', 'dynamic-post-grid' ); ?></span>
								</label>
							<?php endif; ?>
							<?php foreach ( $dd['terms'] as $term ) : ?>
								<label class="dpg-ms-option">
									<input
										type="<?php echo esc_attr( $dpg_input_type ); ?>"
										name="<?php echo esc_attr( $dd['name'] ); ?>[]"
										value="<?php echo esc_attr( $term->term_id ); ?>"
										data-dpg-taxonomy="<?php echo esc_attr( $dd['taxonomy'] ); ?>"
										<?php checked( in_array( (int) $term->term_id, $dpg_selected, true ) ); ?>
									/>
									<span class="dpg-ms-option-label"><?php echo esc_html( $term->name ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</details>
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
