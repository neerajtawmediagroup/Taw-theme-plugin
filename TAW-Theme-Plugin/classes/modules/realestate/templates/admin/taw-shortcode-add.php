<?php
/**
 * TAW Theme - Create / Edit Shortcode Template (Safe Minimal Version)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Basic URLs.
$dashboard_url = admin_url( 'admin.php?page=taw-theme-builder' );
$listings_url  = admin_url( 'admin.php?page=taw-properties-list' );
$views_url     = admin_url( 'admin.php?page=taw-views-list' );

// Editing ID (GET only).
$editing_id   = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
$editing_post = $editing_id ? get_post( $editing_id ) : null;

$message = '';
$error   = '';

// ---------- HANDLE SUBMIT ----------
if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {

	if (
		isset( $_POST['taw_shortcode_create_nonce'] ) &&
		wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['taw_shortcode_create_nonce'] ) ),
			'taw_shortcode_create'
		)
	) {
		if ( ! current_user_can( 'manage_options' ) ) {
			$error = __( 'You do not have permission to create shortcodes.', 'taw-theme' );
		} else {
			$title      = isset( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : '';
			$layout     = isset( $_POST['taw_shortcode_layout'] ) ? sanitize_key( wp_unslash( $_POST['taw_shortcode_layout'] ) ) : 'list';
			$properties = isset( $_POST['taw_shortcode_properties'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['taw_shortcode_properties'] ) ) : array();
			$status     = isset( $_POST['post_status'] ) ? sanitize_key( wp_unslash( $_POST['post_status'] ) ) : 'publish';
			$post_id_in = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

			if ( '' === $title ) {
				$error = __( 'Please enter a title for the shortcode.', 'taw-theme' );
			} else {
				// Decide: create or update.
				if ( $post_id_in && ( $post_obj = get_post( $post_id_in ) ) && 'taw_prop_shortcode' === $post_obj->post_type ) {
					$post_id = wp_update_post(
						array(
							'ID'          => $post_id_in,
							'post_title'  => $title,
							'post_status' => in_array( $status, array( 'publish', 'draft' ), true ) ? $status : 'publish',
						),
						true
					);
				} else {
					$post_id = wp_insert_post(
						array(
							'post_type'   => 'taw_prop_shortcode',
							'post_title'  => $title,
							'post_status' => in_array( $status, array( 'publish', 'draft' ), true ) ? $status : 'publish',
						),
						true
					);
				}

				if ( is_wp_error( $post_id ) ) {
					$error = $post_id->get_error_message();
				} else {
					// Save meta.
					update_post_meta( $post_id, '_taw_shortcode_layout', $layout );
					update_post_meta( $post_id, '_taw_shortcode_properties', $properties );

					if ( isset( $_POST['taw_shortcode_name'] ) ) {
						update_post_meta(
							$post_id,
							'_taw_shortcode_name',
							sanitize_text_field( wp_unslash( $_POST['taw_shortcode_name'] ) )
						);
					}

					$message    = __( 'Shortcode saved successfully.', 'taw-theme' );
					$editing_id = $post_id;
					$editing_post = get_post( $post_id );
				}
			}
		}
	}
}

// Pre-fill values for form.
$shortcode_title  = $editing_post ? $editing_post->post_title : '';
$shortcode_status = $editing_post ? $editing_post->post_status : 'publish';
$shortcode_layout = $editing_post ? get_post_meta( $editing_id, '_taw_shortcode_layout', true ) : 'list';
$shortcode_name   = $editing_post ? get_post_meta( $editing_id, '_taw_shortcode_name', true ) : '';
$selected_props   = $editing_post ? (array) get_post_meta( $editing_id, '_taw_shortcode_properties', true ) : array();

// Fetch properties to list.
$properties = get_posts(
	array(
		'post_type'      => 'taw_property',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	)
);

?>

<div class="taw-theme-dashboard-wrap taw-add-shortcode-page">

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

				<li class="taw-re-left-panel__item">
					<a href="<?php echo esc_url( $listings_url ); ?>">
						<span class="dashicons dashicons-admin-multisite taw-re-left-panel__icon"></span>
						<span class="taw-re-left-panel__text"><?php esc_html_e( 'Real Estate', 'taw-theme' ); ?></span>
					</a>
				</li>

				<li class="taw-re-left-panel__subitem">
					<a href="<?php echo esc_url( $listings_url ); ?>">
						<?php esc_html_e( 'All Listing', 'taw-theme' ); ?>
					</a>
				</li>

				<li class="taw-re-left-panel__subitem">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=taw-property-add' ) ); ?>">
						<?php esc_html_e( 'Add New Property', 'taw-theme' ); ?>
					</a>
				</li>

				<li class="taw-re-left-panel__subitem">
					<a href="<?php echo esc_url( $views_url ); ?>">
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

	<!-- MAIN CONTENT -->
	<main class="taw-theme-dashboard-main">

		<?php if ( $message ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
		<?php endif; ?>

		<?php if ( $error ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
		<?php endif; ?>

		<h1>
			<?php
			echo $editing_id
				? esc_html__( 'Edit Shortcode', 'taw-theme' )
				: esc_html__( 'Create New Shortcode', 'taw-theme' );
			?>
		</h1>

		<form method="post" class="taw-shortcode-form">
			<?php wp_nonce_field( 'taw_shortcode_create', 'taw_shortcode_create_nonce' ); ?>

			<?php if ( $editing_id ) : ?>
				<input type="hidden" name="post_id" value="<?php echo esc_attr( $editing_id ); ?>">
			<?php endif; ?>

			<p>
				<input
					type="text"
					name="post_title"
					class="regular-text"
					style="width:100%;max-width:none;"
					placeholder="<?php esc_attr_e( 'Add title', 'taw-theme' ); ?>"
					value="<?php echo esc_attr( $shortcode_title ); ?>"
					required
				>
			</p>

			<div class="taw-shortcode-layout">
				<div class="taw-shortcode-main">
					<table class="widefat fixed striped">
						<thead>
							<tr>
								<th class="manage-column column-cb check-column">
									<input type="checkbox" id="taw_shortcode_select_all">
								</th>
								<th><?php esc_html_e( 'Title', 'taw-theme' ); ?></th>
								<th><?php esc_html_e( 'Categories', 'taw-theme' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( ! empty( $properties ) ) : ?>
								<?php foreach ( $properties as $property ) : ?>
									<?php
									$terms = get_the_terms( $property->ID, 'taw_property_category' );
									$cats  = array();
									if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
										foreach ( $terms as $t ) {
											$cats[] = $t->name;
										}
									}
									?>
									<tr>
										<th scope="row" class="check-column">
											<input
												type="checkbox"
												name="taw_shortcode_properties[]"
												value="<?php echo esc_attr( $property->ID ); ?>"
												<?php checked( in_array( $property->ID, $selected_props, true ) ); ?>
											>
										</th>
										<td><?php echo esc_html( get_the_title( $property ) ); ?></td>
										<td><?php echo ! empty( $cats ) ? esc_html( implode( ', ', $cats ) ) : '—'; ?></td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="3">
										<?php esc_html_e( 'No properties found. Please create properties first.', 'taw-theme' ); ?>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<div class="taw-shortcode-side">
					<div class="taw-re-panel">
						<div class="taw-re-main">
							<h2 class="taw-add-section-title"><?php esc_html_e( 'Configuration', 'taw-theme' ); ?></h2>

							<p>
								<label style="display:block;font-weight:600;margin-bottom:4px;">
									<?php esc_html_e( 'Shortcode Name', 'taw-theme' ); ?>
								</label>
								<input
									type="text"
									name="taw_shortcode_name"
									class="regular-text"
									placeholder="<?php esc_attr_e( 'e.g. Featured Properties', 'taw-theme' ); ?>"
									value="<?php echo esc_attr( $shortcode_name ); ?>"
								>
							</p>

							<p style="margin-top:15px;font-weight:600;">
								<?php esc_html_e( 'Please select the template layout', 'taw-theme' ); ?>
							</p>

							<?php
							$is_grid = ( 'grid' === $shortcode_layout );
							$is_list = ( 'list' === $shortcode_layout || ! $shortcode_layout );
							?>
							<input
								type="hidden"
								id="taw_shortcode_layout"
								name="taw_shortcode_layout"
								value="<?php echo esc_attr( $shortcode_layout ? $shortcode_layout : 'list' ); ?>"
							>

							<div class="taw-layout-toggle">
								<button
									type="button"
									class="taw-layout-card <?php echo $is_grid ? 'is-active' : ''; ?>"
									data-layout-value="grid"
								>
									<span class="taw-layout-card__icon dashicons dashicons-screenoptions"></span>
									<span class="taw-layout-card__label">
										<?php esc_html_e( 'Grid View', 'taw-theme' ); ?>
									</span>
								</button>

								<button
									type="button"
									class="taw-layout-card <?php echo $is_list ? 'is-active' : ''; ?>"
									data-layout-value="list"
								>
									<span class="taw-layout-card__icon dashicons dashicons-menu-alt"></span>
									<span class="taw-layout-card__label">
										<?php esc_html_e( 'List View', 'taw-theme' ); ?>
									</span>
								</button>
							</div>
						</div>
					</div>

					<div class="taw-re-panel">
						<div class="taw-re-main">
							<h2 class="taw-add-section-title"><?php esc_html_e( 'Publish', 'taw-theme' ); ?></h2>
							<p>
								<select name="post_status">
									<option
										value="publish"
										<?php selected( $shortcode_status, 'publish' ); ?>
									>
										<?php esc_html_e( 'Publish', 'taw-theme' ); ?>
									</option>
									<option
										value="draft"
										<?php selected( $shortcode_status, 'draft' ); ?>
									>
										<?php esc_html_e( 'Save as Draft', 'taw-theme' ); ?>
									</option>
								</select>
							</p>
							<p>
								<button type="submit" class="button button-primary">
									<?php
									echo $editing_id
										? esc_html__( 'Update', 'taw-theme' )
										: esc_html__( 'Publish', 'taw-theme' );
									?>
								</button>
								<a href="<?php echo esc_url( $views_url ); ?>" class="button">
									<?php esc_html_e( 'Back to Views', 'taw-theme' ); ?>
								</a>
							</p>
						</div>
					</div>
				</div><!-- .taw-shortcode-side -->
			</div><!-- .taw-shortcode-layout -->
		</form>

	</main>

</div>

<script>
	(function($){
		// Toggle layout cards.
		$(document).on('click', '.taw-layout-card', function(e){
			e.preventDefault();
			var $btn   = $(this);
			var layout = $btn.data('layout-value');
			$btn.addClass('is-active').siblings('.taw-layout-card').removeClass('is-active');
			$('#taw_shortcode_layout').val(layout);
		});

		// Select all properties.
		$('#taw_shortcode_select_all').on('change', function(){
			$('input[name="taw_shortcode_properties[]"]').prop('checked', $(this).prop('checked'));
		});
	})(jQuery);
</script>

<style>
	.taw-re-panel .taw-re-main {
		padding: 20px;
		background-color: #fff;
		border-radius: 8px;
		margin-bottom: 20px;
		box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
	}
	.taw-layout-toggle {
		display: flex;
		gap: 10px;
		margin-top: 15px;
	}
	.taw-layout-card {
		padding: 10px 20px;
		background-color: #fff;
		border: 1px solid #ddd;
		border-radius: 5px;
		display: flex;
		align-items: center;
		cursor: pointer;
		transition: background-color 0.3s;
	}
	.taw-layout-card.is-active {
		background-color: #0073aa;
		color: #fff;
	}
	.taw-layout-card__label {
		margin-left: 10px;
	}
	.taw-shortcode-layout {
		display: flex;
		gap: 20px;
		margin-top: 15px;
	}
	.taw-shortcode-main { flex: 2; }
	.taw-shortcode-side { flex: 1; max-width: 300px; }
	.widefat { width: 100%; margin-top: 15px; border-collapse: collapse; }
	.widefat th, .widefat td { padding: 8px 10px; border-bottom: 1px solid #eee; }
	.widefat .check-column { text-align: center; }
</style>