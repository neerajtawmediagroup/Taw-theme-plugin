<?php
/**
 * TAW Theme Add/Edit Property Template
 *
 * Custom add/edit property form inside plugin UI that saves into taw_property CPT.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if we're editing an existing property.
$property_id = isset( $_GET['property_id'] ) ? absint( $_GET['property_id'] ) : 0;
$is_edit     = ( $property_id > 0 );
$property    = null;

// If editing, get the property data.
if ( $is_edit ) {
	$property = get_post( $property_id );

	// Verify the property exists and is the correct post type.
	if ( ! $property || $property->post_type !== 'taw_property' ) {
		wp_die( esc_html__( 'Invalid property.', 'taw-theme' ) );
	}

	// Optional: Check user permissions.
	if ( ! current_user_can( 'edit_post', $property_id ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this property.', 'taw-theme' ) );
	}
}

// Get existing meta values (for edit mode).
$property_title     = $is_edit ? $property->post_title : '';
$property_content   = $is_edit ? $property->post_content : '';
$property_status    = $is_edit ? $property->post_status : 'publish';
$property_type      = $is_edit ? get_post_meta( $property_id, 'taw_property_type', true ) : '';
$property_price     = $is_edit ? get_post_meta( $property_id, 'taw_property_price', true ) : '';
$property_area      = $is_edit ? get_post_meta( $property_id, 'taw_property_area', true ) : '';
$property_area_unit = $is_edit ? get_post_meta( $property_id, 'taw_property_area_unit', true ) : 'sqft';
$property_bedrooms  = $is_edit ? get_post_meta( $property_id, 'taw_property_bedrooms', true ) : '0';
$property_bathrooms = $is_edit ? get_post_meta( $property_id, 'taw_property_bathrooms', true ) : '0';
$property_address   = $is_edit ? get_post_meta( $property_id, 'taw_property_address', true ) : '';
$gallery_ids        = $is_edit ? get_post_meta( $property_id, 'taw_property_gallery_ids', true ) : '';

// NEW: Featured image.
$featured_image_id  = $is_edit ? get_post_thumbnail_id( $property_id ) : 0;
$featured_image_url = $featured_image_id ? wp_get_attachment_image_url( $featured_image_id, 'medium' ) : '';

// Get assigned category term.
$assigned_terms    = $is_edit ? wp_get_post_terms( $property_id, 'taw_property_category', array( 'fields' => 'ids' ) ) : array();
$selected_category = ! empty( $assigned_terms ) && ! is_wp_error( $assigned_terms ) ? $assigned_terms[0] : '';

// Messages via redirect (optional).
$message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
$error   = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';

// Left nav needs base URLs similar to dashboard.
$dashboard_url = admin_url( 'admin.php?page=taw-theme-builder' );
$listings_url  = admin_url( 'admin.php?page=taw-properties-list' );

// Page title.
$page_title = $is_edit ? __( 'Edit Property', 'taw-theme' ) : __( 'Add New Property', 'taw-theme' );
?>

<div class="taw-theme-dashboard-wrap taw-add-property-page">

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

				<li class="taw-re-left-panel__subitem <?php echo ! $is_edit ? 'is-active' : ''; ?>">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=taw-property-add' ) ); ?>">
						<?php esc_html_e( 'Add New Property', 'taw-theme' ); ?>
					</a>
				</li>

				<li class="taw-re-left-panel__subitem">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=taw-views-list' ) ); ?>">
						<?php esc_html_e( 'Manage View', 'taw-theme' ); ?>
					</a>
				</li>

			</ul>
		</div>
	</aside>

	<!-- MAIN CONTENT -->
	<main class="taw-theme-dashboard-main">
		<h1><?php echo esc_html( $page_title ); ?></h1>

		<?php if ( $message ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
		<?php endif; ?>

		<?php if ( $error ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="taw-re-add-form">

			<?php wp_nonce_field( 'taw_property_save', 'taw_property_nonce' ); ?>
			<input type="hidden" name="action" value="taw_property_save">

			<?php if ( $is_edit ) : ?>
				<input type="hidden" name="property_id" value="<?php echo esc_attr( $property_id ); ?>">
			<?php endif; ?>

			<!-- Title -->
			<p>
				<input type="text" id="taw_property_title" name="post_title" class="regular-text" style="width:100%;max-width:none;" placeholder="<?php esc_attr_e( 'Add title', 'taw-theme' ); ?>" value="<?php echo esc_attr( $property_title ); ?>" required>
			</p>

			<!-- Property Description -->
			<div class="taw-re-panel">
				<div class="taw-re-main" style="padding:16px 16px 12px;">
					<h2 style="margin:0 0 8px;font-size:14px;"><?php esc_html_e( 'Property Description', 'taw-theme' ); ?></h2>
					<textarea id="taw_property_content" name="post_content" rows="6" class="large-text" placeholder="<?php esc_attr_e( 'Enter property description', 'taw-theme' ); ?>"><?php echo esc_textarea( $property_content ); ?></textarea>
				</div>
			</div>

			<!-- Basic & Location + Gallery -->
			<div class="taw-re-panel taw-re-settings">
				<div class="taw-re-main">
					<div class="taw-re-tabs-content">
						<!-- Basic & Location section -->
						<div class="taw-re-tab is-active" data-tab="basic">
							<h2 class="taw-add-section-title"><?php esc_html_e( 'Basic & Location', 'taw-theme' ); ?></h2>
							<table class="form-table taw-property-meta-table">
								<tr>
									<th scope="row">
										<label for="taw_property_type"><?php esc_html_e( 'Property Type', 'taw-theme' ); ?></label>
									</th>
									<td>
										<?php
										$property_categories = get_terms(
											array(
												'taxonomy'   => 'taw_property_category',
												'hide_empty' => false,
											)
										);
										?>
										<select class="regular-text" id="taw_property_type" name="taw_property_type">
											<option value=""><?php esc_html_e( '— Select Property Category —', 'taw-theme' ); ?></option>
											<?php
											if ( ! is_wp_error( $property_categories ) && ! empty( $property_categories ) ) {
												foreach ( $property_categories as $category ) {
													$selected = ( $selected_category == $category->term_id ) ? 'selected' : '';
													echo '<option value="' . esc_attr( $category->term_id ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $category->name ) . '</option>';
												}
											}
											?>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="taw_property_price"><?php esc_html_e( 'Property Price', 'taw-theme' ); ?></label>
									</th>
									<td>
										<input type="text" class="regular-text" id="taw_property_price" name="taw_property_price" placeholder="<?php esc_attr_e( 'e.g. 350000', 'taw-theme' ); ?>" value="<?php echo esc_attr( $property_price ); ?>">
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="taw_property_area"><?php esc_html_e( 'Size', 'taw-theme' ); ?></label>
									</th>
									<td>
										<div class="taw-field-inline">
											<input type="number" step="0.01" min="0" id="taw_property_area" name="taw_property_area" value="<?php echo esc_attr( $property_area ); ?>">
											<select name="taw_property_area_unit">
												<option value="sqft" <?php selected( $property_area_unit, 'sqft' ); ?>><?php esc_html_e( 'sqft', 'taw-theme' ); ?></option>
												<option value="sqm" <?php selected( $property_area_unit, 'sqm' ); ?>><?php esc_html_e( 'sqm', 'taw-theme' ); ?></option>
											</select>
										</div>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="taw_property_bedrooms"><?php esc_html_e( 'Bedrooms', 'taw-theme' ); ?></label>
									</th>
									<td>
										<div class="taw-number-input-wrapper">
											<button type="button" class="taw-number-btn taw-number-minus" data-target="taw_property_bedrooms" aria-label="<?php esc_attr_e( 'Decrease', 'taw-theme' ); ?>">
												<span class="dashicons dashicons-minus"></span>
											</button>
											<input type="number" min="0" step="1" id="taw_property_bedrooms" name="taw_property_bedrooms" value="<?php echo esc_attr( $property_bedrooms ); ?>" class="taw-number-input">
											<button type="button" class="taw-number-btn taw-number-plus" data-target="taw_property_bedrooms" aria-label="<?php esc_attr_e( 'Increase', 'taw-theme' ); ?>">
												<span class="dashicons dashicons-plus-alt2"></span>
											</button>
										</div>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="taw_property_bathrooms"><?php esc_html_e( 'Bathrooms', 'taw-theme' ); ?></label>
									</th>
									<td>
										<div class="taw-number-input-wrapper">
											<button type="button" class="taw-number-btn taw-number-minus" data-target="taw_property_bathrooms" aria-label="<?php esc_attr_e( 'Decrease', 'taw-theme' ); ?>">
												<span class="dashicons dashicons-minus"></span>
											</button>
											<input type="number" min="0" step="0.5" id="taw_property_bathrooms" name="taw_property_bathrooms" value="<?php echo esc_attr( $property_bathrooms ); ?>" class="taw-number-input">
											<button type="button" class="taw-number-btn taw-number-plus" data-target="taw_property_bathrooms" aria-label="<?php esc_attr_e( 'Increase', 'taw-theme' ); ?>">
												<span class="dashicons dashicons-plus-alt2"></span>
											</button>
										</div>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="taw_property_address"><?php esc_html_e( 'Address', 'taw-theme' ); ?></label>
									</th>
									<td>
										<textarea class="large-text" rows="3" id="taw_property_address" name="taw_property_address" placeholder="<?php esc_attr_e( 'Full property address', 'taw-theme' ); ?>"><?php echo esc_textarea( $property_address ); ?></textarea>
									</td>
								</tr>

								<!-- NEW: Featured Image -->
								<tr>
									<th scope="row">
										<label><?php esc_html_e( 'Featured Image', 'taw-theme' ); ?></label>
									</th>
									<td>
										<input type="hidden" id="taw_property_featured_image" name="taw_property_featured_image" value="<?php echo esc_attr( $featured_image_id ); ?>">

										<div class="taw-featured-image-wrap">
											<div class="taw-featured-image-preview" style="margin-bottom:8px;">
												<?php if ( $featured_image_url ) : ?>
													<img src="<?php echo esc_url( $featured_image_url ); ?>" alt="" style="max-width:150px;height:auto;display:block;">
												<?php else : ?>
													<span class="description"><?php esc_html_e( 'No image selected.', 'taw-theme' ); ?></span>
												<?php endif; ?>
											</div>

											<button type="button" class="button taw-featured-image-upload">
												<?php esc_html_e( 'Set Featured Image', 'taw-theme' ); ?>
											</button>
											<button type="button" class="button taw-featured-image-remove" style="margin-left:5px;<?php echo $featured_image_id ? '' : 'display:none;'; ?>">
												<?php esc_html_e( 'Remove', 'taw-theme' ); ?>
											</button>
										</div>
									</td>
								</tr>
							</table>
						</div>

						<!-- Gallery Images section -->
						<div class="taw-re-tab" data-tab="media">
							<h2 class="taw-add-section-title"><?php esc_html_e( 'Gallery Images', 'taw-theme' ); ?></h2>
							<table class="form-table taw-property-meta-table">
								<tr>
									<th scope="row">
										<label for="taw_property_gallery_ids"><?php esc_html_e( 'Gallery Images', 'taw-theme' ); ?></label>
									</th>
									<td>
										<input type="hidden" id="taw_property_gallery_ids" name="taw_property_gallery_ids" value="<?php echo esc_attr( $gallery_ids ); ?>">
										<button type="button" class="button taw-re-media-upload">
											<?php esc_html_e( 'Add Image', 'taw-theme' ); ?>
										</button>
										<div class="taw-re-media-preview">
											<?php
											// Display existing gallery images.
											if ( ! empty( $gallery_ids ) ) {
												$ids_array = explode( ',', $gallery_ids );
												foreach ( $ids_array as $img_id ) {
													$img_id = absint( $img_id );
													if ( $img_id ) {
														$thumb_url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
														if ( $thumb_url ) {
															echo '<div class="taw-re-media-item" data-id="' . esc_attr( $img_id ) . '">';
															echo '<img src="' . esc_url( $thumb_url ) . '" alt="" style="max-width:100px;height:auto;display:block;margin-bottom:4px;" />';
															echo '<button type="button" class="button-link taw-re-media-remove" style="color:#a00;">' . esc_html__( 'Remove', 'taw-theme' ) . '</button>';
															echo '</div>';
														}
													}
												}
											}
											?>
										</div>
									</td>
								</tr>
							</table>
						</div>

					</div>
				</div>
			</div>

			<p class="submit">
				<select id="taw_property_status" name="post_status">
					<option value="publish" <?php selected( $property_status, 'publish' ); ?>><?php esc_html_e( 'Publish', 'taw-theme' ); ?></option>
					<option value="draft" <?php selected( $property_status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'taw-theme' ); ?></option>
				</select>
				<button type="submit" class="button button-primary">
					<?php echo $is_edit ? esc_html__( 'Update Property', 'taw-theme' ) : esc_html__( 'Save Property', 'taw-theme' ); ?>
				</button>
				<a href="<?php echo esc_url( $listings_url ); ?>" class="button"><?php esc_html_e( 'Back to Listings', 'taw-theme' ); ?></a>
			</p>
		</form>

		<!-- INLINE JS TO MAKE +/- BUTTONS WORK -->
		<script>
		document.addEventListener('click', function(e) {
			const btn = e.target.closest('.taw-number-btn');
			if (!btn) return;
			e.preventDefault();

			const targetId = btn.getAttribute('data-target');
			const input = document.getElementById(targetId);
			if (!input) return;

			let val = parseFloat(input.value);
			const min = parseFloat(input.getAttribute('min'));
			const step = parseFloat(input.getAttribute('step')) || 1;

			if (isNaN(val)) val = 0;
			if (isNaN(min)) val = 0;

			if (btn.classList.contains('taw-number-plus')) {
				val += step;
			} else if (btn.classList.contains('taw-number-minus')) {
				val -= step;
			}

			if (val < min) val = min;

			val = Math.round(val * 100) / 100;

			input.value = val;
			input.dispatchEvent(new Event('change'));
		});
		</script>

		<!-- Gallery + Featured Image JS -->
		<script>
		(function($){
			$(document).ready(function(){

				// ===== Gallery =========
				var frame;
				var $galleryField = $('#taw_property_gallery_ids');
				var $previewWrap  = $('.taw-re-media-preview');

				$('.taw-re-media-upload').on('click', function(e){
					e.preventDefault();

					if (frame) {
						frame.open();
						return;
					}

					frame = wp.media({
						title: '<?php echo esc_js( __( 'Select or Upload Images', 'taw-theme' ) ); ?>',
						button: {
							text: '<?php echo esc_js( __( 'Use these images', 'taw-theme' ) ); ?>'
						},
						multiple: true
					});

					frame.on('select', function(){
						var selection = frame.state().get('selection');
						var ids       = $galleryField.val() ? $galleryField.val().split(',') : [];

						selection.each(function(attachment){
							attachment = attachment.toJSON();
							if (ids.indexOf(String(attachment.id)) === -1) {
								ids.push(attachment.id);
								var thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
								$previewWrap.append(
									'<div class="taw-re-media-item" data-id="'+attachment.id+'">' +
										'<img src="'+thumb+'" alt="" style="max-width:100px;height:auto;display:block;margin-bottom:4px;" />' +
										'<button type="button" class="button-link taw-re-media-remove" style="color:#a00;">' +
											'<?php echo esc_js( __( 'Remove', 'taw-theme' ) ); ?>' +
										'</button>' +
									'</div>'
								);
							}
						});

						$galleryField.val(ids.join(','));
					});

					frame.open();
				});

				$previewWrap.on('click', '.taw-re-media-remove', function(e){
					e.preventDefault();
					var $item = $(this).closest('.taw-re-media-item');
					var id    = $item.data('id').toString();

					var ids = $galleryField.val() ? $galleryField.val().split(',') : [];
					ids = ids.filter(function(val){ return val !== id; });
					$galleryField.val(ids.join(','));

					$item.remove();
				});


				// ===== Featured Image =========
				var featuredFrame;
				var $featuredField   = $('#taw_property_featured_image');
				var $featuredPreview = $('.taw-featured-image-preview');
				var $btnUpload       = $('.taw-featured-image-upload');
				var $btnRemove       = $('.taw-featured-image-remove');

				$btnUpload.on('click', function(e){
					e.preventDefault();

					if (featuredFrame) {
						featuredFrame.open();
						return;
					}

					featuredFrame = wp.media({
						title: '<?php echo esc_js( __( 'Select Featured Image', 'taw-theme' ) ); ?>',
						button: {
							text: '<?php echo esc_js( __( 'Use this image', 'taw-theme' ) ); ?>'
						},
						multiple: false
					});

					featuredFrame.on('select', function(){
						var attachment = featuredFrame.state().get('selection').first().toJSON();
						$featuredField.val(attachment.id);
						$featuredPreview.html(
							'<img src="' + attachment.url + '" style="max-width:150px;height:auto;display:block;" />'
						);
						$btnRemove.show();
					});

					featuredFrame.open();
				});

				$btnRemove.on('click', function(e){
					e.preventDefault();
					$featuredField.val('');
					$featuredPreview.html('<span class="description"><?php echo esc_js( __( 'No image selected.', 'taw-theme' ) ); ?></span>');
					$btnRemove.hide();
				});

			});
		})(jQuery);
		</script>
	</main>

</div>