<?php
/**
 * TAW Theme Views List Template
 * Full manage-view page for taw_prop_shortcode.
 */

// Current tab from URL (for left nav highlighting).
$current_tab = 'views';

// Base URLs.
$dashboard_url = admin_url( 'admin.php?page=taw-theme-builder' );
$views_url     = admin_url( 'admin.php?page=taw-views-list' );

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

// Query args.
$query_args = array(
	'post_type'      => 'taw_prop_shortcode',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'post_status'    => 'any',
	'orderby'        => 'date',
	'order'          => 'DESC',
);

if ( 'all' !== $status_filter ) {
	$query_args['post_status'] = $status_filter;
}

if ( ! empty( $search ) ) {
	$query_args['s'] = $search;
}

$views_query = new WP_Query( $query_args );
$counts      = wp_count_posts( 'taw_prop_shortcode' );
$total       = isset( $counts->publish, $counts->draft, $counts->trash )
	? ( (int) $counts->publish + (int) $counts->draft + (int) $counts->trash )
	: (int) $views_query->found_posts;

$all_url = remove_query_arg( array( 'post_status', 'paged' ), $views_url );

?>

<div class="taw-theme-dashboard-wrap">

	<!-- LEFT PANEL -->
	<aside class="taw-re-left-panel">
		<div class="taw-re-left-panel__inner">
			<ul class="taw-re-left-panel__menu">

				<li class="taw-re-left-panel__item">
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'dashboard', $dashboard_url ) ); ?>">
						<span class="dashicons dashicons-dashboard taw-re-left-panel__icon"></span>
						<span class="taw-re-left-panel__text"><?php esc_html_e( 'Dashboard', 'taw-theme' ); ?></span>
					</a>
				</li>

				<li class="taw-re-left-panel__item is-active">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=taw-properties-list' ) ); ?>">
						<span class="dashicons dashicons-admin-multisite taw-re-left-panel__icon"></span>
						<span class="taw-re-left-panel__text"><?php esc_html_e( 'Real Estate', 'taw-theme' ); ?></span>
					</a>
				</li>

				<li class="taw-re-left-panel__subitem">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=taw-properties-list' ) ); ?>">
						<?php esc_html_e( 'All Listing', 'taw-theme' ); ?>
					</a>
				</li>

				<li class="taw-re-left-panel__subitem">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=taw-property-add' ) ); ?>">
						<?php esc_html_e( 'Add New Property', 'taw-theme' ); ?>
					</a>
				</li>

				<li class="taw-re-left-panel__subitem is-active">
					<a href="<?php echo esc_url( $views_url ); ?>">
						<?php esc_html_e( 'Manage View', 'taw-theme' ); ?>
					</a>
				</li>

				<li class="taw-re-left-panel__subitem">
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'categories', $dashboard_url ) ); ?>">
						<?php esc_html_e( 'Categories', 'taw-theme' ); ?>
					</a>
				</li>

			</ul>
		</div>
	</aside>

	<!-- MAIN CONTENT -->
	<main class="taw-theme-dashboard-main">

		<div class="taw-re-list-header">
			<div>
				<h1 class="taw-re-page-title"><?php esc_html_e( 'Manage View', 'taw-theme' ); ?></h1>
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
						$url     = ( 'all' === $status_key ) ? $all_url : add_query_arg( 'post_status', $status_key, $all_url );
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
					<input type="hidden" name="page" value="taw-views-list">
					<?php if ( 'all' !== $status_filter ) : ?>
						<input type="hidden" name="post_status" value="<?php echo esc_attr( $status_filter ); ?>">
					<?php endif; ?>
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search views...', 'taw-theme' ); ?>" class="taw-re-search-input">
					<button type="submit" class="button"><?php esc_html_e( 'Search Views', 'taw-theme' ); ?></button>
				</form>
				<a class="button button-primary taw-re-add-new" href="<?php echo esc_url( admin_url( 'admin.php?page=taw-shortcode-add' ) ); ?>">
					<?php esc_html_e( 'Add New Shortcode', 'taw-theme' ); ?>
				</a>
			</div>
		</div>

		<div class="taw-re-toolbar">
			<div class="taw-re-toolbar-left">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="taw-views-bulk-form">
					<?php wp_nonce_field( 'taw_views_bulk_action', 'taw_views_bulk_nonce' ); ?>
					<input type="hidden" name="action" value="taw_views_bulk_action">
					
					<select name="bulk_action" class="taw-re-bulk-select">
						<option value=""><?php esc_html_e( 'Bulk Actions', 'taw-theme' ); ?></option>
						<?php if ( 'trash' === $status_filter ) : ?>
							<option value="restore"><?php esc_html_e( 'Restore', 'taw-theme' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete Permanently', 'taw-theme' ); ?></option>
						<?php else : ?>
							<option value="trash"><?php esc_html_e( 'Move to Trash', 'taw-theme' ); ?></option>
						<?php endif; ?>
					</select>
					<button type="submit" class="button"><?php esc_html_e( 'Apply', 'taw-theme' ); ?></button>
				</form>

				<span class="taw-re-items-count">
					<?php
					printf(
						/* translators: %d: number of items */
						esc_html__( '%d items', 'taw-theme' ),
						(int) $views_query->found_posts
					);
					?>
				</span>
			</div>
		</div>

		<?php if ( $views_query->have_posts() ) : ?>
			<table class="widefat fixed striped taw-re-list-table">
				<thead>
					<tr>
						<th style="width: 3%;" class="manage-column column-cb check-column">
							<input type="checkbox" id="taw-select-all">
						</th>
						<th><?php esc_html_e( 'Title', 'taw-theme' ); ?></th>
						<th style="width: 30%;"><?php esc_html_e( 'Shortcode', 'taw-theme' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'Status', 'taw-theme' ); ?></th>
						<th style="width: 20%;"><?php esc_html_e( 'Date', 'taw-theme' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					while ( $views_query->have_posts() ) :
						$views_query->the_post();
						$post_id     = get_the_ID();
						$post_status = get_post_status();
						
						// Custom edit URL - now uses post_id.
						$edit_url = admin_url( 'admin.php?page=taw-shortcode-add&post_id=' . $post_id );
						
						// Row actions.
						$row_actions = array();
						
						if ( 'trash' === $post_status ) {
							$restore_url = wp_nonce_url(
								admin_url( 'admin-post.php?action=taw_view_restore&view_id=' . $post_id ),
								'taw_view_restore_' . $post_id
							);
							$delete_url = wp_nonce_url(
								admin_url( 'admin-post.php?action=taw_view_delete&view_id=' . $post_id ),
								'taw_view_delete_' . $post_id
							);
							
							$row_actions[] = '<a href="' . esc_url( $restore_url ) . '">' . esc_html__( 'Restore', 'taw-theme' ) . '</a>';
							$row_actions[] = '<a href="' . esc_url( $delete_url ) . '" class="taw-delete-link" style="color:#a00;">' . esc_html__( 'Delete Permanently', 'taw-theme' ) . '</a>';
						} else {
							$trash_url = wp_nonce_url(
								admin_url( 'admin-post.php?action=taw_view_trash&view_id=' . $post_id ),
								'taw_view_trash_' . $post_id
							);
							
							$row_actions[] = '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'taw-theme' ) . '</a>';
							$row_actions[] = '<a href="' . esc_url( $trash_url ) . '" style="color:#a00;">' . esc_html__( 'Trash', 'taw-theme' ) . '</a>';
						}
						?>
						<tr>
							<th scope="row" class="check-column">
								<input type="checkbox" name="view_ids[]" value="<?php echo esc_attr( $post_id ); ?>" form="taw-views-bulk-form">
							</th>
							<td>
								<strong>
									<?php if ( 'trash' !== $post_status ) : ?>
										<a href="<?php echo esc_url( $edit_url ); ?>">
											<?php echo esc_html( get_the_title() ); ?>
										</a>
									<?php else : ?>
										<?php echo esc_html( get_the_title() ); ?>
									<?php endif; ?>
								</strong>
								<div class="row-actions">
									<?php echo implode( ' | ', $row_actions ); ?>
								</div>
							</td>
							<td>
								<?php
								// Dynamic shortcode slug based on this view ID.
								$shortcode_slug = 'taw_prop_shortcode_' . $post_id;
								?>
								<code class="taw-shortcode-copy"
									  style="cursor:pointer;"
									  title="<?php esc_attr_e( 'Click to copy', 'taw-theme' ); ?>">
									[<?php echo esc_html( $shortcode_slug ); ?>]
								</code>
								<span class="taw-copied-message"
									  style="display:none;color:green;margin-left:8px;">
									<?php esc_html_e( 'Copied!', 'taw-theme' ); ?>
								</span>
							</td>
							<td>
								<?php
								$status_labels = array(
									'publish' => '<span class="taw-status-badge taw-status-publish">' . esc_html__( 'Published', 'taw-theme' ) . '</span>',
									'draft'   => '<span class="taw-status-badge taw-status-draft">' . esc_html__( 'Draft', 'taw-theme' ) . '</span>',
									'trash'   => '<span class="taw-status-badge taw-status-trash">' . esc_html__( 'Trash', 'taw-theme' ) . '</span>',
								);
								echo isset( $status_labels[ $post_status ] ) ? $status_labels[ $post_status ] : esc_html( $post_status );
								?>
							</td>
							<td><?php echo esc_html( get_the_date() . ' ' . get_the_time() ); ?></td>
						</tr>
					<?php endwhile; wp_reset_postdata(); ?>
				</tbody>
			</table>

			<?php
			// Pagination.
			if ( $views_query->max_num_pages > 1 ) {
				$pagination_args = array(
					'base'      => add_query_arg( 'paged', '%#%', $views_url ),
					'format'    => '',
					'prev_text' => __( '&laquo; Previous', 'taw-theme' ),
					'next_text' => __( 'Next &raquo;', 'taw-theme' ),
					'total'     => $views_query->max_num_pages,
					'current'   => $paged,
				);

				if ( ! empty( $search ) ) {
					$pagination_args['add_args'] = array( 's' => $search );
				}
				
				if ( 'all' !== $status_filter ) {
					$pagination_args['add_args']['post_status'] = $status_filter;
				}

				echo '<div class="tablenav"><div class="tablenav-pages">';
				echo wp_kses_post( paginate_links( $pagination_args ) );
				echo '</div></div>';
			}
			?>

		<?php else : ?>
			<div class="notice notice-info">
				<p><?php esc_html_e( 'No views found.', 'taw-theme' ); ?></p>
			</div>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=taw-shortcode-add' ) ); ?>">
					<?php esc_html_e( 'Create First View', 'taw-theme' ); ?>
				</a>
			</p>
		<?php endif; ?>

	</main>

</div>

<!-- Copy to clipboard script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
	// Select all checkbox
	const selectAll = document.getElementById('taw-select-all');
	if (selectAll) {
		selectAll.addEventListener('change', function() {
			const checkboxes = document.querySelectorAll('input[name="view_ids[]"]');
			checkboxes.forEach(cb => cb.checked = this.checked);
		});
	}
	
	// Copy shortcode to clipboard
	document.querySelectorAll('.taw-shortcode-copy').forEach(function(el) {
		el.addEventListener('click', function() {
			const text = this.textContent.trim();
			navigator.clipboard.writeText(text).then(() => {
				const msg = this.nextElementSibling;
				if (msg) {
					msg.style.display = 'inline';
					setTimeout(() => { msg.style.display = 'none'; }, 2000);
				}
			});
		});
	});
});
</script>

<style>
.taw-status-badge {
	display: inline-block;
	padding: 2px 8px;
	border-radius: 3px;
	font-size: 12px;
}
.taw-status-publish { background: #d4edda; color: #155724; }
.taw-status-draft { background: #fff3cd; color: #856404; }
.taw-status-trash { background: #f8d7da; color: #721c24; }
.row-actions { visibility: hidden; padding-top: 4px; }
tr:hover .row-actions { visibility: visible; }
</style>