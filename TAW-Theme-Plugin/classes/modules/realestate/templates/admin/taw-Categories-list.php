<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Page: Categories Management
 * Taxonomy: taw_property_category
 */

/* ---------- HELPERS ---------- */
function taw_get_current_page() {
	return max( 1, isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1 );
}

/* -------------------------------------------------------
 * 1. HANDLE ADD CATEGORY
 * ---------------------------------------------------- */
if (
	isset( $_POST['taw_add_category_nonce'] )
	&& check_admin_referer( 'taw_add_category', 'taw_add_category_nonce' )
) {
	if ( ! empty( $_POST['taw_new_cat_name'] ) ) {
		$name        = sanitize_text_field( wp_unslash( $_POST['taw_new_cat_name'] ) );
		$description = ! empty( $_POST['taw_new_cat_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['taw_new_cat_desc'] ) ) : '';

		$term = wp_insert_term(
			$name,
			'taw_property_category',
			array(
				'description' => $description,
			)
		);

		if ( ! is_wp_error( $term ) ) {
			update_term_meta( $term['term_id'], 'taw_status', 'active' );
			echo '<div class="updated"><p>Category added successfully.</p></div>';
		} else {
			echo '<div class="error"><p>' . esc_html( $term->get_error_message() ) . '</p></div>';
		}
	}
}

/* -------------------------------------------------------
 * 2. HANDLE EDIT CATEGORY
 * ---------------------------------------------------- */
if (
	isset( $_POST['taw_edit_category_nonce'] )
	&& check_admin_referer( 'taw_edit_category', 'taw_edit_category_nonce' )
) {
	$term_id = isset( $_POST['taw_edit_term_id'] ) ? (int) $_POST['taw_edit_term_id'] : 0;
	$name    = isset( $_POST['taw_edit_cat_name'] ) ? sanitize_text_field( wp_unslash( $_POST['taw_edit_cat_name'] ) ) : '';
	$desc    = isset( $_POST['taw_edit_cat_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['taw_edit_cat_desc'] ) ) : '';

	if ( $term_id && $name ) {
		$updated = wp_update_term(
			$term_id,
			'taw_property_category',
			array(
				'name'        => $name,
				'description' => $desc,
			)
		);

		if ( ! is_wp_error( $updated ) ) {
			echo '<div class="updated"><p>Category updated successfully.</p></div>';
		} else {
			echo '<div class="error"><p>' . esc_html( $updated->get_error_message() ) . '</p></div>';
		}
	}
}

/* -------------------------------------------------------
 * 3. HANDLE BULK ACTIONS
 * ---------------------------------------------------- */
if (
	isset( $_POST['taw_bulk_action_nonce'] )
	&& check_admin_referer( 'taw_bulk_action', 'taw_bulk_action_nonce' )
	&& ! empty( $_POST['taw_bulk_action'] )
	&& ! empty( $_POST['taw_term_ids'] )
) {
	$bulk_action = sanitize_text_field( wp_unslash( $_POST['taw_bulk_action'] ) );
	$term_ids    = array_map( 'intval', (array) $_POST['taw_term_ids'] );

	foreach ( $term_ids as $term_id ) {
		if ( 'delete' === $bulk_action ) {
			wp_delete_term( $term_id, 'taw_property_category' );
		} elseif ( 'deactivate' === $bulk_action ) {
			update_term_meta( $term_id, 'taw_status', 'inactive' );
		} elseif ( 'activate' === $bulk_action ) {
			update_term_meta( $term_id, 'taw_status', 'active' );
		}
	}

	echo '<div class="updated"><p>Bulk action applied.</p></div>';
}

/* -------------------------------------------------------
 * 4. LOAD TERMS (filters: search + status + pagination)
 * ---------------------------------------------------- */

// Search (by name).
$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

// Status filter (active/inactive).
$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'active';
if ( ! in_array( $status, array( 'active', 'inactive' ), true ) ) {
	$status = 'active';
}

// Pagination.
$paged    = taw_get_current_page();
$per_page = 10;

// Base args for get_terms.
$args_all = array(
	'taxonomy'   => array( 'taw_property_category' ),
	'hide_empty' => false,
	'search'     => $search ? $search : '',
);

// Get all terms matching search (no status filter yet).
$all_terms = get_terms( $args_all );

// Counters.
$total_active   = 0;
$total_inactive = 0;
$all_filtered   = array();

if ( ! is_wp_error( $all_terms ) ) {
	// Global counts.
	foreach ( $all_terms as $term ) {
		$term_status = get_term_meta( $term->term_id, 'taw_status', true );
		if ( ! $term_status ) {
			$term_status = 'active';
		}

		if ( 'active' === $term_status ) {
			$total_active++;
		} else {
			$total_inactive++;
		}
	}

	// Filter by current tab status.
	foreach ( $all_terms as $term ) {
		$term_status = get_term_meta( $term->term_id, 'taw_status', true );
		if ( ! $term_status ) {
			$term_status = 'active';
		}

		if ( $term_status === $status ) {
			$all_filtered[] = $term;
		}
	}
}

// Pagination on filtered set.
$total        = is_array( $all_filtered ) ? count( $all_filtered ) : 0;
$total_pages  = $total > 0 ? (int) ceil( $total / $per_page ) : 1;
$offset       = ( $paged - 1 ) * $per_page;
$categories   = array_slice( $all_filtered, $offset, $per_page );

// Currently editing term?
$edit_id      = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
$editing_term = $edit_id ? get_term( $edit_id, 'taw_property_category' ) : null;

?>
<style>
.wrap {
	max-width: 100%;
}
.taw-flex { display:flex; gap:30px; align-items:flex-start; margin-top:20px; }
.taw-box {
	background:#ffffff;
	padding:25px;
	border-radius:4px;
	box-shadow:0 1px 1px rgba(0,0,0,0.04);
	border:1px solid #e2e4e7;
}
.taw-left { width:420px; }
.taw-right { flex:1; }
.taw-box h2 { margin-top:0; font-size:18px; margin-bottom:20px; }
.taw-box label { font-size:13px; font-weight:500; color:#555; }
.taw-box input,
.taw-box textarea,
.taw-box select {
	width:100%;
	padding:9px 10px;
	border-radius:3px;
	border:1px solid #dcdcdc;
	margin-top:5px;
	box-sizing:border-box;
	font-size:13px;
}
.taw-box textarea { resize:vertical; min-height:160px; }
.taw-btn, .taw-btn-search {
	background:#007cba;
	color:#fff;
	padding:8px 18px;
	border-radius:3px;
	text-decoration:none;
	border:none;
	cursor:pointer;
	font-size:13px;
}
.taw-btn:hover { background:#006ba1; }

/* TOP BAR */
.taw-topbar {
	display:flex;
	justify-content:space-between;
	align-items:flex-start;
	margin-bottom:10px;
}
.taw-tabs a {
	font-size:14px;
	font-weight:500;
	margin-right:10px;
	color:#007cba;
	text-decoration:none;
}
.taw-tabs a.active { color:#23282d; }
.taw-tabs span.count { color:#777; }

/* bulk + count */
.taw-bulk-row {
	display:flex;
	align-items:center;
	gap:8px;
	font-size:13px;
	margin-top:8px;
}
.taw-bulk-row select {
	padding:4px 6px;
	font-size:13px;
}
.taw-bulk-row .taw-items-count {
	margin-left:8px;
	color:#555;
}

/* search */
.taw-search-wrap {
	display:flex;
	gap:8px;
	align-items:center;
	justify-content:flex-end;
	margin-bottom:10px;
}
.taw-search-wrap input[type="search"] {
	padding:6px 8px;
	border:1px solid #ccd0d4;
	border-radius:3px;
	min-width:210px;
	font-size:13px;
}
.taw-search-wrap .taw-btn-search {
	background:#007cba;
	color:#fff;
	border:none;
	border-radius:3px;
	padding:6px 14px;
	cursor:pointer;
	font-size:13px;
	white-space:nowrap;
}
.taw-search-wrap .taw-btn-search:hover { background:#006ba1; }

/* TABLE */
.taw-table-wrap {
	background:#fff;
	border:1px solid #e2e4e7;
	border-radius:4px;
	box-shadow:0 1px 1px rgba(0,0,0,0.04);
}
.taw-table {
	width:100%;
	border-collapse:collapse;
}
.taw-table thead th {
	background:#f9f9f9;
	padding:10px 8px;
	text-align:left;
	border-bottom:1px solid #e1e1e1;
	font-weight:600;
	font-size:13px;
}
.taw-table tbody td {
	padding:9px 8px;
	border-bottom:1px solid #f1f1f1;
	font-size:13px;
}
.taw-table .col-cb { width:35px; }
.taw-table .col-name a { color:#0073aa; text-decoration:none; }
.taw-table .col-name a:hover { color:#00a0d2; text-decoration:underline; }
.taw-pill {
	display:inline-block;
	background:#f3f4f6;
	color:#555;
	padding:2px 8px;
	font-size:12px;
	border-radius:20px;
}

/* Bottom items + pagination */
.taw-bottom-bar {
	display:flex;
	justify-content:space-between;
	align-items:center;
	padding:8px 10px;
	font-size:12px;
}
.taw-pagination { display:flex; gap:2px; }
.taw-page-btn {
	border:1px solid #ddd;
	padding:3px 7px;
	border-radius:3px;
	background:#fff;
	cursor:pointer;
	font-size:12px;
	text-decoration:none;
	color:#0073aa;
}
.taw-page-btn:hover {
	background:#007cba;
	color:#fff;
}
.taw-page-btn.disabled,
.taw-page-btn.disabled:hover {
	background:#f5f5f5;
	color:#999;
	cursor:default;
}
.taw-page-btn.current {
	background:#007cba;
	color:#fff;
	font-weight:bold;
}
.taw-row-actions {
	font-size:11px;
	color:#555;
}
.taw-row-actions a { color:#0073aa; text-decoration:none; }
.taw-row-actions a:hover { text-decoration:underline; }
</style>

<div class="wrap">
	<h1>Categories</h1>

	<div class="taw-flex">

		<!-- LEFT: ADD / EDIT -->
		<div class="taw-box taw-left">
			<?php if ( $editing_term && ! is_wp_error( $editing_term ) ) : ?>
				<h2>Edit Category</h2>
				<form method="post">
					<?php wp_nonce_field( 'taw_edit_category', 'taw_edit_category_nonce' ); ?>
					<input type="hidden" name="taw_edit_term_id" value="<?php echo (int) $editing_term->term_id; ?>">

					<p>
						<label>Name<br>
							<input type="text" name="taw_edit_cat_name"
								   value="<?php echo esc_attr( $editing_term->name ); ?>" required>
						</label>
					</p>

					<p>
						<label>Description<br>
							<textarea name="taw_edit_cat_desc" rows="5"
									  placeholder="Enter short description"><?php echo esc_textarea( $editing_term->description ); ?></textarea>
						</label>
					</p>

					<p>
						<button type="submit" class="taw-btn">Update Category</button>
						<a href="<?php echo esc_url( remove_query_arg( 'edit' ) ); ?>" class="button" style="margin-left:10px;">Cancel</a>
					</p>
				</form>
			<?php else : ?>
				<h2>Add Categories</h2>
				<form method="post">
					<?php wp_nonce_field( 'taw_add_category', 'taw_add_category_nonce' ); ?>

					<p>
						<label>Name<br>
							<input type="text" name="taw_new_cat_name" placeholder="Select" required>
						</label>
					</p>

					<p>
						<label>Description<br>
							<textarea name="taw_new_cat_desc" placeholder="Enter short description" rows="5"></textarea>
						</label>
					</p>

					<p><button type="submit" class="taw-btn">Add Category</button></p>
				</form>
			<?php endif; ?>
		</div>

		<!-- RIGHT: LIST + FILTERS -->
		<div class="taw-right">

			<!-- SEARCH (separate GET form) -->
			<form method="get" class="taw-search-wrap">
				<?php
				// Preserve existing query args except search and paged.
				foreach ( $_GET as $k => $v ) {
					if ( in_array( $k, array( 's', 'paged' ), true ) ) {
						continue;
					}
					echo '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
				}
				?>
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search">
				<button type="submit" class="taw-btn-search">Search Properties</button>
			</form>

			<!-- Bulk + table share this POST form -->
			<form method="post">
				<?php wp_nonce_field( 'taw_bulk_action', 'taw_bulk_action_nonce' ); ?>

				<div class="taw-topbar">
					<div>
						<div class="taw-tabs">
							<?php
							$base_url = remove_query_arg( array( 'status', 'paged', 'edit' ) );
							?>
							<a href="<?php echo esc_url( add_query_arg( 'status', 'active', $base_url ) ); ?>"
							   class="<?php echo ( 'inactive' !== $status ) ? 'active' : ''; ?>">
								Active <span class="count">(<?php echo (int) $total_active; ?>)</span>
							</a> |
							<a href="<?php echo esc_url( add_query_arg( 'status', 'inactive', $base_url ) ); ?>"
							   class="<?php echo ( 'inactive' === $status ) ? 'active' : ''; ?>">
								In-active <span class="count">(<?php echo (int) $total_inactive; ?>)</span>
							</a>
						</div>

						<div class="taw-bulk-row">
							<select name="taw_bulk_action">
								<option value="">Bulk Actions</option>
								<option value="delete">Delete</option>
								<?php if ( 'active' === $status ) : ?>
									<option value="deactivate">Mark In-active</option>
								<?php else : ?>
									<option value="activate">Mark Active</option>
								<?php endif; ?>
							</select>
							<button type="submit" class="taw-btn" style="padding:4px 12px;font-size:13px;">Apply</button>
							<span class="taw-items-count">
								<?php echo (int) $total; ?> items
							</span>
						</div>
					</div>
				</div>

				<!-- TABLE -->
				<div class="taw-table-wrap">
					<table class="taw-table">
						<thead>
						<tr>
							<th class="col-cb"><input type="checkbox" id="taw-select-all"></th>
							<th class="col-name">Name</th>
							<th class="col-count">Count</th>
						</tr>
						</thead>
						<tbody>
						<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
							<?php foreach ( $categories as $cat ) : ?>
								<tr>
									<td class="col-cb">
										<input type="checkbox" name="taw_term_ids[]" value="<?php echo (int) $cat->term_id; ?>">
									</td>
									<td class="col-name">
										<?php
										$edit_link = add_query_arg(
											array( 'edit' => $cat->term_id ),
											remove_query_arg( 'paged' )
										);
										?>
										<a href="<?php echo esc_url( $edit_link ); ?>"><?php echo esc_html( $cat->name ); ?></a>
										<div class="taw-row-actions">
                                            <span class="edit">
                                                <a href="<?php echo esc_url( $edit_link ); ?>">Edit</a>
                                            </span>
										</div>
									</td>
									<td class="col-count">
										<span class="taw-pill"><?php echo (int) $cat->count; ?></span>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="3">No categories found.</td>
							</tr>
						<?php endif; ?>
						</tbody>
					</table>

					<!-- bottom items + pagination -->
					<div class="taw-bottom-bar">
						<span><?php echo (int) $total; ?> items</span>

						<div class="taw-pagination">
							<?php
							$base = remove_query_arg( 'paged' );

							// Prev.
							if ( $paged > 1 ) {
								$first_url = esc_url( add_query_arg( 'paged', 1, $base ) );
								$prev_url  = esc_url( add_query_arg( 'paged', $paged - 1, $base ) );
								echo '<a class="taw-page-btn" href="' . $first_url . '">&laquo;</a>';
								echo '<a class="taw-page-btn" href="' . $prev_url . '">&lsaquo;</a>';
							} else {
								echo '<span class="taw-page-btn disabled">&laquo;</span>';
								echo '<span class="taw-page-btn disabled">&lsaquo;</span>';
							}

							// Page numbers.
							for ( $i = 1; $i <= $total_pages; $i++ ) {
								$url   = esc_url( add_query_arg( 'paged', $i, $base ) );
								$class = 'taw-page-btn';
								if ( $i === $paged ) {
									$class .= ' current';
								}
								echo '<a class="' . esc_attr( $class ) . '" href="' . $url . '">' . (int) $i . '</a>';
							}

							// Next.
							if ( $paged < $total_pages ) {
								$next_url = esc_url( add_query_arg( 'paged', $paged + 1, $base ) );
								$last_url = esc_url( add_query_arg( 'paged', $total_pages, $base ) );
								echo '<a class="taw-page-btn" href="' . $next_url . '">&rsaquo;</a>';
								echo '<a class="taw-page-btn" href="' . $last_url . '">&raquo;</a>';
							} else {
								echo '<span class="taw-page-btn disabled">&rsaquo;</span>';
								echo '<span class="taw-page-btn disabled">&raquo;</span>';
							}
							?>
						</div>
					</div>
				</div>
			</form>

		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	const selectAll = document.getElementById('taw-select-all');
	if (!selectAll) return;

	selectAll.addEventListener('change', function () {
		document.querySelectorAll('input[name="taw_term_ids[]"]').forEach(function (cb) {
			cb.checked = selectAll.checked;
		});
	});
});
</script>