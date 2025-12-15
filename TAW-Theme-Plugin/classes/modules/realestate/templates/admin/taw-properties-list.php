<?php
/**
 * TAW Theme Properties List Template
 * Full listings page with pagination
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// TEMP DEBUG: confirm template is reached in the browser console.
echo '<script>console.log("TAW: properties list template reached");</script>';

// Current tab from URL.
$current_tab = 'real-estate';

// Base URL for this page.
$dashboard_url = admin_url( 'admin.php?page=taw-theme-builder' );
$listings_url  = admin_url( 'admin.php?page=taw-properties-list' );

// Pagination.
$paged    = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
$per_page = 20;

// Search.
$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

// Status filter.
$status_filter = isset( $_GET['post_status'] ) ? sanitize_key( $_GET['post_status'] ) : 'all';
if ( ! in_array( $status_filter, array( 'all', 'publish', 'draft', 'trash' ), true ) ) {
	$status_filter = 'all';
}

// Category filter.
$category_filter = isset( $_GET['property_cat'] ) ? absint( $_GET['property_cat'] ) : 0;

// Query arguments.
$query_args = array(
	'post_type'      => 'taw_property',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'post_status'    => 'any',
	'orderby'        => 'date',
	'order'          => 'DESC',
);

// Apply status filter.
if ( 'all' !== $status_filter ) {
	$query_args['post_status'] = $status_filter;
}

// Apply category filter.
if ( $category_filter ) {
	$query_args['tax_query'] = array(
		array(
			'taxonomy' => 'taw_property_category',
			'field'    => 'term_id',
			'terms'    => $category_filter,
		),
	);
}

// Apply search.
if ( ! empty( $search ) ) {
	$query_args['s'] = $search;
}

// Query posts.
$props   = new WP_Query( $query_args );
$counts  = wp_count_posts( 'taw_property' );
$total   = isset( $counts->publish, $counts->draft, $counts->trash )
	? (int) $counts->publish + (int) $counts->draft + (int) $counts->trash
	: (int) $props->found_posts;

// We still keep $all_url for reference, but we will rebuild links manually.
$all_url = remove_query_arg( array( 'post_status', 'paged' ), $listings_url );

// Message from bulk / save handlers.
$message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
?>

<div class="taw-theme-dashboard-wrap">

	<!-- LEFT PANEL -->
	<aside class="taw-re-left-panel">
		<div class="taw-re-left-panel__inner">
			<ul class="taw-re-left-panel__menu">

				<!-- Dashboard -->
				<li class="taw-re-left-panel__item">
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'dashboard', $dashboard_url ) ); ?>">
						<span class="dashicons dashicons-dashboard taw-re-left-panel__icon"></span>
						<span class="taw-re-left-panel__text">
							<?php esc_html_e( 'Dashboard', 'taw-theme' ); ?>
						</span>
					</a>
				</li>

				<!-- Real Estate (main row) -->
				<li class="taw-re-left-panel__item is-active">
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'real-estate', $dashboard_url ) ); ?>">
						<span class="dashicons dashicons-admin-multisite taw-re-left-panel__icon"></span>
						<span class="taw-re-left-panel__text">
							<?php esc_html_e( 'Real Estate', 'taw-theme' ); ?>
						</span>
					</a>
				</li>

				<!-- Real Estate sub‑items -->
				<li class="taw-re-left-panel__subitem is-active">
					<a href="<?php echo esc_url( $listings_url ); ?>">
						<?php esc_html_e( 'All Listing', 'taw-theme' ); ?>
					</a>
				</li>

				<!-- Add New Property (subitem) -->
				<li class="taw-re-left-panel__subitem">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=taw-property-add' ) ); ?>">
						<?php esc_html_e( 'Add New Property', 'taw-theme' ); ?>
					</a>
				</li>

				<!-- Manage View -->
				<li class="taw-re-left-panel__subitem <?php echo ( 'views' === $current_tab ) ? 'is-active' : ''; ?>">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=taw-views-list' ) ); ?>">
						<?php esc_html_e( 'Manage View', 'taw-theme' ); ?>
					</a>
				</li>

				<!-- Categories -->
				<li class="taw-re-left-panel__subitem">
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'categories', $dashboard_url ) ); ?>">
						<?php esc_html_e( 'Categories', 'taw-theme' ); ?>
					</a>
				</li>

			</ul>
		</div>
	</aside>

	<!-- MAIN DASHBOARD CONTENT (right side) -->
	<main class="taw-theme-dashboard-main">

		<?php if ( $message ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
		<?php endif; ?>

		<div class="taw-re-list-header">
			<div>
				<h1 class="taw-re-page-title"><?php esc_html_e( 'Real Estate', 'taw-theme' ); ?></h1>
				<div class="taw-re-status-filters">
					<?php
					$statuses = array(
						'all'     => array(
							'label' => __( 'All', 'taw-theme' ),
							'count' => $total,
						),
						'publish' => array(
							'label' => __( 'Published', 'taw-theme' ),
							'count' => isset( $counts->publish ) ? (int) $counts->publish : 0,
						),
						'draft'   => array(
							'label' => __( 'Draft', 'taw-theme' ),
							'count' => isset( $counts->draft ) ? (int) $counts->draft : 0,
						),
						'trash'   => array(
							'label' => __( 'Trash', 'taw-theme' ),
							'count' => isset( $counts->trash ) ? (int) $counts->trash : 0,
						),
					);

					$links = array();
					foreach ( $statuses as $status_key => $data ) {

						// Start from base URL.
						$url_args = array(
							'page' => 'taw-properties-list',
						);

						// Preserve search and category filter if set.
						if ( ! empty( $search ) ) {
							$url_args['s'] = $search;
						}
						if ( $category_filter ) {
							$url_args['property_cat'] = $category_filter;
						}

						// Add status filter, unless "all".
						if ( 'all' !== $status_key ) {
							$url_args['post_status'] = $status_key;
						}

						$url     = add_query_arg( $url_args, admin_url( 'admin.php' ) );
						$current = ( $status_filter === $status_key ) ? ' class="current"' : '';

						$links[] = sprintf(
							'<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
							esc_url( $url ),
							$current,
							esc_html( $data['label'] ),
							(int) $data['count']
						);
					}
					echo wp_kses_post( implode( ' | ', $links ) );
					?>
				</div>
			</div>

			<div class="taw-re-header-actions">
				<form method="get" action="" class="taw-re-search-form">
					<input type="hidden" name="page" value="taw-properties-list">
					<?php if ( 'all' !== $status_filter ) : ?>
						<input type="hidden" name="post_status" value="<?php echo esc_attr( $status_filter ); ?>">
					<?php endif; ?>
					<?php if ( $category_filter ) : ?>
						<input type="hidden" name="property_cat" value="<?php echo esc_attr( $category_filter ); ?>">
					<?php endif; ?>
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search properties...', 'taw-theme' ); ?>" class="taw-re-search-input">
					<button type="submit" class="button"><?php esc_html_e( 'Search Properties', 'taw-theme' ); ?></button>
				</form>
				<a class="button button-primary taw-re-add-new" href="<?php echo esc_url( admin_url( 'admin.php?page=taw-property-add' ) ); ?>">
					<?php esc_html_e( 'Add New', 'taw-theme' ); ?>
				</a>
			</div>
		</div>

		<div class="taw-re-toolbar">
			<div class="taw-re-toolbar-left">
				<select name="bulk_action_select" class="taw-re-bulk-select" id="taw-bulk-select">
					<option value=""><?php esc_html_e( 'Bulk Actions', 'taw-theme' ); ?></option>
					<option value="trash"><?php esc_html_e( 'Move to Trash', 'taw-theme' ); ?></option>
				</select>
				<button type="button" class="button" id="taw-bulk-apply"><?php esc_html_e( 'Apply', 'taw-theme' ); ?></button>

				<select name="taw_re_filter_date" class="taw-re-filter-select">
					<option value="0"><?php esc_html_e( 'All Dates', 'taw-theme' ); ?></option>
				</select>

				<?php
				$categories = get_terms(
					array(
						'taxonomy'   => 'taw_property_category',
						'hide_empty' => false,
					)
				);
				?>
				<form method="get" action="" style="display:inline-block;margin:0;">
					<input type="hidden" name="page" value="taw-properties-list">
					<?php if ( ! empty( $search ) ) : ?>
						<input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>">
					<?php endif; ?>
					<?php if ( 'all' !== $status_filter ) : ?>
						<input type="hidden" name="post_status" value="<?php echo esc_attr( $status_filter ); ?>">
					<?php endif; ?>
					<select name="property_cat" class="taw-re-filter-select" onchange="this.form.submit();">
						<option value="0"><?php esc_html_e( 'All Categories', 'taw-theme' ); ?></option>
						<?php foreach ( $categories as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $category_filter, $cat->term_id ); ?>>
								<?php echo esc_html( $cat->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</form>

				<span class="taw-re-items-count">
					<?php
					printf(
						/* translators: %d: number of items */
						esc_html__( '%d items', 'taw-theme' ),
						(int) $props->found_posts
					);
					?>
				</span>
			</div>
		</div>

		<?php if ( $props->have_posts() ) : ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="taw-bulk-form">
				<?php wp_nonce_field( 'taw_bulk_action', 'taw_bulk_nonce' ); ?>
				<input type="hidden" name="action" value="taw_properties_bulk_action">
				<input type="hidden" name="bulk_action" value="">
				<input type="hidden" name="redirect_url" value="<?php echo esc_url( add_query_arg( $_GET, admin_url( 'admin.php' ) ) ); ?>">

				<table class="widefat fixed striped taw-re-list-table">
					<thead>
						<tr>
							<th style="width: 3%;" class="manage-column column-cb check-column">
								<input type="checkbox" id="taw-check-all">
							</th>
							<th style="width: 5%;"><?php esc_html_e( 'ID', 'taw-theme' ); ?></th>
							<th style="width: 30%;"><?php esc_html_e( 'Title', 'taw-theme' ); ?></th>
							<th style="width: 20%;"><?php esc_html_e( 'Categories', 'taw-theme' ); ?></th>
							<th style="width: 15%;"><?php esc_html_e( 'Status', 'taw-theme' ); ?></th>
							<th style="width: 20%;"><?php esc_html_e( 'Date', 'taw-theme' ); ?></th>
							<th style="width: 10%;"><?php esc_html_e( 'Actions', 'taw-theme' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						while ( $props->have_posts() ) :
							$props->the_post();
							$post_id = get_the_ID();
							$terms   = get_the_terms( $post_id, 'taw_property_category' );
							$cats    = array();

							if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
								foreach ( $terms as $t ) {
									$cats[] = $t->name;
								}
							}

							$post_status  = get_post_status( $post_id );
							$status_label = ucfirst( $post_status );

							// URL to your add/edit template (expects ?property_id=).
							$edit_url = add_query_arg(
								array(
									'page'        => 'taw-property-add',
									'property_id' => $post_id,
								),
								admin_url( 'admin.php' )
							);

							// Front-end view URL
							$view_url = get_permalink( $post_id );
							?>
							<tr>
								<th scope="row" class="check-column">
									<input type="checkbox" name="post_ids[]" value="<?php echo esc_attr( $post_id ); ?>">
								</th>
								<td><?php echo esc_html( $post_id ); ?></td>
								<td>
									<strong>
										<a href="<?php echo esc_url( $edit_url ); ?>">
											<?php echo esc_html( get_the_title() ); ?>
										</a>
									</strong>
								</td>
								<td><?php echo ! empty( $cats ) ? esc_html( implode( ', ', $cats ) ) : '—'; ?></td>
								<td>
									<span class="status-<?php echo esc_attr( $post_status ); ?>">
										<?php echo esc_html( $status_label ); ?>
									</span>
								</td>
								<td><?php echo esc_html( get_the_date() . ' ' . get_the_time() ); ?></td>
								<td>
									<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small">
										<?php esc_html_e( 'Edit', 'taw-theme' ); ?>
									</a>
									<a href="<?php echo esc_url( $view_url ); ?>" class="button button-small" target="_blank">
										<?php esc_html_e( 'View', 'taw-theme' ); ?>
									</a>
								</td>
							</tr>
						<?php endwhile; wp_reset_postdata(); ?>
					</tbody>
				</table>
			</form>

			<!-- Pagination -->
			<?php
			if ( $props->max_num_pages > 1 ) {
				$pagination_args = array(
					'base'      => add_query_arg( 'paged', '%#%', $listings_url ),
					'format'    => '',
					'prev_text' => __( '&laquo; Previous', 'taw-theme' ),
					'next_text' => __( 'Next &raquo;', 'taw-theme' ),
					'total'     => $props->max_num_pages,
					'current'   => $paged,
				);

				$add_args = array();
				if ( ! empty( $search ) ) {
					$add_args['s'] = $search;
				}
				if ( 'all' !== $status_filter ) {
					$add_args['post_status'] = $status_filter;
				}
				if ( $category_filter ) {
					$add_args['property_cat'] = $category_filter;
				}
				if ( ! empty( $add_args ) ) {
					$pagination_args['add_args'] = $add_args;
				}

				echo '<div class="tablenav">';
				echo '<div class="tablenav-pages">';
				echo paginate_links( $pagination_args );
				echo '</div>';
				echo '</div>';
			}
			?>

			<p style="margin-top: 15px;">
				<strong><?php echo esc_html( sprintf( __( 'Total: %d properties', 'taw-theme' ), $props->found_posts ) ); ?></strong>
			</p>

		<?php else : ?>
			<div class="notice notice-info">
				<p><?php esc_html_e( 'No properties found.', 'taw-theme' ); ?></p>
			</div>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=taw-property-add' ) ); ?>">
					<?php esc_html_e( 'Add First Property', 'taw-theme' ); ?>
				</a>
			</p>
		<?php endif; ?>

		<!-- Bulk JS -->
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			const bulkSelect  = document.getElementById('taw-bulk-select');
			const bulkForm    = document.getElementById('taw-bulk-form');
			const bulkApply   = document.getElementById('taw-bulk-apply');
			const bulkAction  = bulkForm ? bulkForm.querySelector('input[name="bulk_action"]') : null;
			const checkAll    = document.getElementById('taw-check-all');

			if (!bulkForm || !bulkSelect || !bulkApply || !bulkAction) return;

			// Check / uncheck all.
			if (checkAll) {
				checkAll.addEventListener('change', function() {
					const checkboxes = bulkForm.querySelectorAll('input[name="post_ids[]"]');
					checkboxes.forEach(function(cb) {
						cb.checked = checkAll.checked;
					});
				});
			}

			bulkApply.addEventListener('click', function(e) {
				e.preventDefault();

				const action = bulkSelect.value;
				if (!action) {
					alert('<?php echo esc_js( __( 'Please select a bulk action.', 'taw-theme' ) ); ?>');
					return;
				}

				const selected = bulkForm.querySelectorAll('input[name="post_ids[]"]:checked');
				if (!selected.length) {
					alert('<?php echo esc_js( __( 'Please select at least one item.', 'taw-theme' ) ); ?>');
					return;
				}

				if (action === 'trash' && !confirm('<?php echo esc_js( __( 'Move selected items to the Trash?', 'taw-theme' ) ); ?>')) {
					return;
				}

				bulkAction.value = action;
				bulkForm.submit();
			});
		});
		</script>

	</main>

</div>