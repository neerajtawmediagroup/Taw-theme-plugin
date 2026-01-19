<?php
/**
 * TAW Real Estate Module
 *
 * Real Estate property management module
 *
 * @package TAW_Theme
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure base class is loaded before we try to extend it.
if ( ! class_exists( 'TAW_Module_Base' ) ) {
	if ( defined( 'TAW_MODULES_DIR' ) && file_exists( TAW_MODULES_DIR . 'classes/class-taw-module-base.php' ) ) {
		require_once TAW_MODULES_DIR . 'classes/class-taw-module-base.php';
	} else {
		trigger_error( 'TAW_Module_Base class file not found', E_USER_WARNING );
		return;
	}
}

if ( ! class_exists( 'TAW_Module_Base' ) ) {
	return;
}

/**
 * TAW_Module_RealEstate Class
 *
 * Real Estate module implementation
 */
class TAW_Module_RealEstate extends TAW_Module_Base {

	/**
	 * Initialize the module
	 */
	protected function init() {
		parent::init();

		if ( ! function_exists( 'add_action' ) ) {
			return;
		}


		add_action( 'parse_request', array( $this, 'ensure_post_types_registered' ), 1 );
		if ( is_admin() ) {
			add_action( 'load-edit.php', array( $this, 'ensure_post_types_registered' ), 1 );
			add_action( 'load-post.php', array( $this, 'ensure_post_types_registered' ), 1 );
			add_action( 'load-post-new.php', array( $this, 'ensure_post_types_registered' ), 1 );
		}

		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );

		add_action( 'add_meta_boxes', array( $this, 'register_shortcode_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_shortcode_meta' ) );
		add_action( 'init', array( $this, 'register_dynamic_shortcodes' ), 20 );
		
		add_action( 'init', array( $this, 'register_shortcode_column_filters' ), 25 );
		add_action( 'init', array( $this, 'register_shortcodes' ), 30 );

if ( is_admin() ) {
		add_action( 'all_admin_notices', array( $this, 'add_manage_view_top_button' ) );
	}
		
	}

	/**
 * Add "Manage View" button beside "Add New Property" on Properties list screen.
 */
public function add_manage_view_top_button() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen ) {
		return;
	}

	// Only on Properties list table.
	if ( 'edit' !== $screen->base || 'taw_property' !== $screen->post_type ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$manage_url = admin_url( 'edit.php?post_type=taw_prop_shortcode' );
	?>
	<script>
		(function(){
			document.addEventListener('DOMContentLoaded', function () {
				// Heading that already contains "Add New Property"
				var heading = document.querySelector('.wrap > h1.wp-heading-inline');
				if (!heading) {
					heading = document.querySelector('.wrap > h2.wp-heading-inline');
				}
				if (!heading) return;

				// Create the Manage View button
				var manageBtn = document.createElement('a');
				manageBtn.href = '<?php echo esc_url( $manage_url ); ?>';
				manageBtn.className = 'page-title-action';
				manageBtn.textContent = '<?php echo esc_js( __( 'Manage View', 'taw-theme' ) ); ?>';

				// Insert it *after* the h1, so it's outside the heading tag
				if (heading.parentNode) {
					heading.parentNode.insertBefore(manageBtn, heading.nextSibling);
				}
			});
		})();
	</script>
	<?php
}


	/**
	 * Ensure post types are registered before WordPress validates them
	 */
	public function ensure_post_types_registered() {
		$this->register_post_types();
		$this->register_shortcode_column_filters();
	}

	/**
	 * Register column filters for shortcode post type
	 */
	public function register_shortcode_column_filters() {
		if ( post_type_exists( 'taw_prop_shortcode' ) ) {
			add_filter( 'manage_taw_prop_shortcode_posts_columns', array( $this, 'shortcode_columns' ) );
			add_action( 'manage_taw_prop_shortcode_posts_custom_column', array( $this, 'shortcode_column_content' ), 10, 2 );
		}
	}

	/**
	 * Register post types
	 */
	public function register_post_types() {
		$labels = array(
			'name'               => __( 'Properties', 'taw-theme' ),
			'singular_name'      => __( 'Property', 'taw-theme' ),
			'add_new'            => __( 'Add New', 'taw-theme' ),
			'add_new_item'       => __( 'Add New Property', 'taw-theme' ),
			'edit_item'          => __( 'Edit Property', 'taw-theme' ),
			'new_item'           => __( 'New Property', 'taw-theme' ),
			'all_items'          => __( 'Listing', 'taw-theme' ),
			'view_item'          => __( 'View Property', 'taw-theme' ),
			'search_items'       => __( 'Search Properties', 'taw-theme' ),
			'not_found'          => __( 'No properties found', 'taw-theme' ),
			'not_found_in_trash' => __( 'No properties found in Trash', 'taw-theme' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'show_in_admin_bar'  => true,
			'has_archive'        => true,
			'rewrite'            => array( 'slug' => 'property' ),
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
			'menu_icon'          => 'dashicons-building',
		);

		register_post_type( 'taw_property', $args );

		$tax_labels = array(
			'name'              => __( 'Property Categories', 'taw-theme' ),
			'singular_name'     => __( 'Property Category', 'taw-theme' ),
			'search_items'      => __( 'Search Categories', 'taw-theme' ),
			'all_items'         => __( 'All Categories', 'taw-theme' ),
			'parent_item'       => __( 'Parent Category', 'taw-theme' ),
			'parent_item_colon' => __( 'Parent Category:', 'taw-theme' ),
			'edit_item'         => __( 'Edit Category', 'taw-theme' ),
			'update_item'       => __( 'Update Category', 'taw-theme' ),
			'add_new_item'      => __( 'Add New Category', 'taw-theme' ),
			'new_item_name'     => __( 'New Category Name', 'taw-theme' ),
			'menu_name'         => __( 'Property Categories', 'taw-theme' ),
		);

		$tax_args = array(
			'hierarchical'      => true,
			'labels'            => $tax_labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'property-category' ),
		);

		register_taxonomy( 'taw_property_category', array( 'taw_property' ), $tax_args );

		$this->register_shortcode_cpt();
	}

	/**
	 * Register custom post type for property shortcodes.
	 */
	private function register_shortcode_cpt() {
		$labels = array(
			'name'               => __( 'Property Shortcodes', 'taw-theme' ),
			'singular_name'      => __( 'Property Shortcode', 'taw-theme' ),
			'add_new'            => __( 'Create Shortcode', 'taw-theme' ),
			'add_new_item'       => __( 'Create New Shortcode', 'taw-theme' ),
			'edit_item'          => __( 'Edit Shortcode', 'taw-theme' ),
			'new_item'           => __( 'New Shortcode', 'taw-theme' ),
			'all_items'          => __( 'Manage View', 'taw-theme' ),
			'view_item'          => __( 'View Shortcode', 'taw-theme' ),
			'search_items'       => __( 'Search Shortcodes', 'taw-theme' ),
			'not_found'          => __( 'No shortcodes found', 'taw-theme' ),
			'not_found_in_trash' => __( 'No shortcodes found in Trash', 'taw-theme' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'show_in_admin_bar'  => false,
			'has_archive'        => false,
			'supports'           => array( 'title' ),
			'menu_icon'          => 'dashicons-shortcode',
		);

		register_post_type( 'taw_prop_shortcode', $args );
	}

	/**
	 * Register static shortcodes.
	 */
	public function register_shortcodes() {
		// Property listing shortcodes
		add_shortcode( 'taw_property_list', array( $this, 'render_shortcode' ) );
		add_shortcode( 'properties_shortcode', array( $this, 'render_properties_shortcode' ) );

		// Media shortcode
		add_shortcode( 'taw_property_gallery', array( $this, 'render_property_gallery_shortcode' ) );

		// Basic & Location field shortcodes
		add_shortcode( 'taw_property_title', array( $this, 'render_property_title_shortcode' ) );
		add_shortcode( 'taw_property_price', array( $this, 'render_property_price_shortcode' ) );
		add_shortcode( 'taw_property_type', array( $this, 'render_property_type_shortcode' ) );
		add_shortcode( 'taw_property_area', array( $this, 'render_property_area_shortcode' ) );
		add_shortcode( 'taw_property_bedrooms', array( $this, 'render_property_bedrooms_shortcode' ) );
		add_shortcode( 'taw_property_bathrooms', array( $this, 'render_property_bathrooms_shortcode' ) );
		add_shortcode( 'taw_property_address', array( $this, 'render_property_address_shortcode' ) );
		
		// Contact Information shortcodes
		add_shortcode( 'taw_property_contact_name', array( $this, 'render_property_contact_name_shortcode' ) );
		add_shortcode( 'taw_property_contact_email', array( $this, 'render_property_contact_email_shortcode' ) );
		add_shortcode( 'taw_property_contact_phone', array( $this, 'render_property_contact_phone_shortcode' ) );
		add_shortcode( 'taw_property_contact_website', array( $this, 'render_property_contact_website_shortcode' ) );
		
		// Generic field shortcode
		add_shortcode( 'taw_property_field', array( $this, 'render_property_field_shortcode' ) );
		
		// All details combined shortcode
		add_shortcode( 'taw_property_details', array( $this, 'render_property_details_shortcode' ) );
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ( 'taw_property' !== $screen->post_type && 'taw_prop_shortcode' !== $screen->post_type ) ) {
			return;
		}

		if ( function_exists( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

		wp_enqueue_style(
			'taw-realestate-admin',
			$this->module_url . 'assets/css/admin.css',
			array(),
			$this->module_version
		);

		wp_enqueue_script(
			'taw-realestate-admin',
			$this->module_url . 'assets/js/admin.js',
			array( 'jquery' ),
			$this->module_version,
			true
		);
	}

	/**
	 * Register admin menu
	 */
	public function register_admin_menu() {
		$this->register_post_types();

		global $submenu;
		if ( isset( $submenu['taw-theme-builder'] ) ) {
			remove_submenu_page( 'taw-theme-builder', 'edit.php?post_type=taw_property' );
			remove_submenu_page( 'taw-theme-builder', 'post-new.php?post_type=taw_property' );
			remove_submenu_page( 'taw-theme-builder', 'edit.php?post_type=taw_prop_shortcode' );
			remove_submenu_page( 'taw-theme-builder', 'post-new.php?post_type=taw_prop_shortcode' );
		}

		add_submenu_page(
			'taw-theme-builder',
			__( 'Real Estate - Listing', 'taw-theme' ),
			__( 'Real Estate - Listing', 'taw-theme' ),
			'manage_options',
			'edit.php?post_type=taw_property'
		);

		add_submenu_page(
			'taw-theme-builder',
			__( 'Real Estate - Add New', 'taw-theme' ),
			__( 'Real Estate - Add New', 'taw-theme' ),
			'manage_options',
			'post-new.php?post_type=taw_property'
		);

		add_submenu_page(
			'taw-theme-builder',
			__( 'Real Estate - Manage View', 'taw-theme' ),
			__( 'Real Estate - Manage View', 'taw-theme' ),
			'manage_options',
			'edit.php?post_type=taw_prop_shortcode'
		);
	}

	/**
	 * Register meta boxes.
	 */
	public function register_meta_boxes() {
		add_meta_box(
			'taw_property_details',
			__( 'Property Details', 'taw-theme' ),
			array( $this, 'render_details_meta_box' ),
			'taw_property',
			'normal',
			'high'
		);
	}

	/**
	 * Register meta boxes for shortcode post type.
	 */
	public function register_shortcode_meta_boxes() {
		add_meta_box(
			'taw_shortcode_properties',
			__( 'Select Properties', 'taw-theme' ),
			array( $this, 'render_properties_list_meta_box' ),
			'taw_prop_shortcode',
			'normal',
			'high'
		);

		add_meta_box(
			'taw_shortcode_config',
			__( 'Shortcode Configuration', 'taw-theme' ),
			array( $this, 'render_shortcode_config_meta_box' ),
			'taw_prop_shortcode',
			'side',
			'high'
		);
	}

	/**
	 * Get meta with default
	 */
	protected function get_meta( $post_id, $key, $default = '' ) {
		$value = get_post_meta( $post_id, '_taw_property_' . $key, true );
		return ( '' === $value || null === $value ) ? $default : $value;
	}

	/**
	 * Get available views
	 */
	protected function get_available_views() {
		return array(
			'grid' => __( 'Grid', 'taw-theme' ),
			'list' => __( 'List', 'taw-theme' ),
		);
	}

	/**
	 * Get property ID from various sources (helper for shortcodes)
	 *
	 * @param int $provided_id ID provided via shortcode attribute
	 * @return int Property ID or 0 if not found
	 */
	private function get_property_id_for_shortcode( $provided_id = 0 ) {
		$property_id = absint( $provided_id );
		
		if ( $property_id ) {
			return $property_id;
		}
		
		// Method 1: Standard get_the_ID()
		$property_id = get_the_ID();
		
		// Method 2: Try global $post
		if ( ! $property_id ) {
			global $post;
			if ( $post && isset( $post->ID ) ) {
				$property_id = $post->ID;
			}
		}
		
		// Method 3: Try queried object (works in page builders)
		if ( ! $property_id ) {
			$queried_object = get_queried_object();
			if ( $queried_object && isset( $queried_object->ID ) ) {
				$property_id = $queried_object->ID;
			}
		}
		
		// Method 4: Try URL parameter (for AJAX/builder previews)
		if ( ! $property_id && isset( $_GET['post'] ) ) {
			$property_id = absint( $_GET['post'] );
		}
		
		// Method 5: Try from wp_query
		if ( ! $property_id ) {
			global $wp_query;
			if ( isset( $wp_query->queried_object_id ) ) {
				$property_id = $wp_query->queried_object_id;
			}
		}
		
		return $property_id;
	}

	/**
	 * Validate property for shortcode
	 *
	 * @param int  $property_id Property ID
	 * @param bool $debug       Debug mode
	 * @param bool $strict      Check if post type is taw_property
	 * @return array ['valid' => bool, 'error' => string]
	 */
	private function validate_property_for_shortcode( $property_id, $debug = false, $strict = true ) {
		if ( ! $property_id ) {
			if ( $debug ) {
				return array(
					'valid' => false,
					'error' => '<p style="color: red; border: 1px solid red; padding: 10px;">[Shortcode] Debug: No property ID found. Please use id="YOUR_PROPERTY_ID"</p>'
				);
			}
			return array( 'valid' => false, 'error' => '' );
		}
		
		$post_obj = get_post( $property_id );
		if ( ! $post_obj ) {
			if ( $debug ) {
				return array(
					'valid' => false,
					'error' => '<p style="color: red;">[Shortcode] Debug: Property ID ' . $property_id . ' not found.</p>'
				);
			}
			return array( 'valid' => false, 'error' => '' );
		}
		
		if ( $strict && 'taw_property' !== get_post_type( $property_id ) ) {
			if ( $debug ) {
				return array(
					'valid' => false,
					'error' => '<p style="color: red;">[Shortcode] Debug: Post type is "' . get_post_type( $property_id ) . '", not "taw_property".</p>'
				);
			}
			return array( 'valid' => false, 'error' => '' );
		}
		
		return array( 'valid' => true, 'error' => '' );
	}

	/**
	 * Render main list shortcode: [taw_property_list]
	 */
	public function render_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'category'       => '',
				'posts_per_page' => 6,
				'view'           => 'grid',
				'columns'        => 3,
			),
			$atts,
			'taw_property_list'
		);

		$view = $this->validate_view( sanitize_text_field( $atts['view'] ) );

		if ( ! post_type_exists( 'taw_property' ) ) {
			return '<p>' . esc_html__( 'Property post type not available.', 'taw-theme' ) . '</p>';
		}

		$args = array(
			'post_type'      => 'taw_property',
			'posts_per_page' => (int) $atts['posts_per_page'],
			'post_status'    => 'publish',
		);

		if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'taw_property_category',
					'field'    => 'slug',
					'terms'    => sanitize_title( $atts['category'] ),
				),
			);
		}

		$query = new WP_Query( $args );

		$properties = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$properties[] = $this->prepare_property_data( get_the_ID() );
			}
			wp_reset_postdata();
		}

		return $this->render_view( $view, array(
			'properties' => $properties,
			'columns'    => absint( $atts['columns'] ),
			'query'      => $query,
			'has_posts'  => $query->have_posts(),
		) );
	}

	/**
	 * Prepare property data (shared across all views)
	 */
	private function prepare_property_data( $post_id ) {
		$type_value = $this->get_meta( $post_id, 'type', '' );

		$type = '';
		if ( ! empty( $type_value ) ) {
			if ( is_numeric( $type_value ) ) {
				$term = get_term( (int) $type_value, 'taw_property_category' );
				$type = ( ! is_wp_error( $term ) && $term ) ? $term->name : '';
			} else {
				$type = $type_value;
			}
		}

		$gallery_ids_meta = $this->get_meta( $post_id, 'gallery_ids', '' );
		$gallery_ids      = array();
		if ( ! empty( $gallery_ids_meta ) ) {
			$gallery_ids = array_filter(
				array_map( 'absint', explode( ',', $gallery_ids_meta ) )
			);
		}

		$gallery = array();
		if ( ! empty( $gallery_ids ) ) {
			foreach ( $gallery_ids as $attachment_id ) {
				$img_html = wp_get_attachment_image( $attachment_id, 'large' );
				if ( $img_html ) {
					$gallery[] = array(
						'id'  => $attachment_id,
						'img' => $img_html,
						'url' => wp_get_attachment_url( $attachment_id ),
					);
				}
			}
		}

		return array(
			'post_id'     => $post_id,
			'title'       => get_the_title( $post_id ),
			'permalink'   => get_permalink( $post_id ),
			'excerpt'     => get_the_excerpt( $post_id ),
			'thumbnail'   => has_post_thumbnail( $post_id ) ? get_the_post_thumbnail( $post_id, 'medium_large' ) : '',
			'type'        => $type,
			'area'        => $this->get_meta( $post_id, 'area', '' ),
			'area_unit'   => $this->get_meta( $post_id, 'area_unit', 'sqft' ),
			'address'     => $this->get_meta( $post_id, 'address', '' ),
			'price'       => $this->get_meta( $post_id, 'price', '' ),
			'bedrooms'    => $this->get_meta( $post_id, 'bedrooms', '' ),
			'bathrooms'   => $this->get_meta( $post_id, 'bathrooms', '' ),
			'gallery_ids' => $gallery_ids,
			'gallery'     => $gallery,
		);
	}

	/**
	 * Render view template
	 */
	private function render_view( $view, $args = array() ) {
		$args['module'] = $this;
		$output = $this->load_template( 'wrapper.php', $args, $view );
		return $output;
	}

	/**
	 * Render the Property Details meta box.
	 */
	public function render_details_meta_box( $post ) {
		if ( ! $post || 'taw_property' !== $post->post_type ) {
			return;
		}

		wp_nonce_field( 'taw_property_save', 'taw_property_nonce' );

		$property_type = $this->get_meta( $post->ID, 'type', '' );
		$area          = $this->get_meta( $post->ID, 'area', '' );
		$area_unit     = $this->get_meta( $post->ID, 'area_unit', 'sqft' );
		$address       = $this->get_meta( $post->ID, 'address', '' );
		$price         = $this->get_meta( $post->ID, 'price', '' );
		$bedrooms      = $this->get_meta( $post->ID, 'bedrooms', '' );
		$bathrooms     = $this->get_meta( $post->ID, 'bathrooms', '' );

		$template_path = $this->module_dir . 'templates/property-meta-box.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			$this->render_property_meta_box_html( $post, $property_type, $area, $area_unit, $address, $price, $bedrooms, $bathrooms );
		}
	}

	/**
	 * Render property meta box HTML inline
	 */
	private function render_property_meta_box_html( $post, $property_type, $area, $area_unit, $address, $price, $bedrooms, $bathrooms ) {
		?>
		<div class="taw-re-panel taw-re-settings">
			<div class="taw-re-sidebar">
				<ul class="taw-re-tabs-nav">
					<li class="is-active" data-tab="basic">
						<span class="taw-re-tab-icon dashicons dashicons-admin-generic"></span>
						<span class="taw-re-tab-label"><?php esc_html_e( 'Basic & Location', 'taw-theme' ); ?></span>
					</li>
					<li data-tab="media">
						<span class="taw-re-tab-icon dashicons dashicons-format-gallery"></span>
						<span class="taw-re-tab-label"><?php esc_html_e( 'Media', 'taw-theme' ); ?></span>
					</li>
					<li data-tab="agent">
						<span class="taw-re-tab-icon dashicons dashicons-businessperson"></span>
						<span class="taw-re-tab-label"><?php esc_html_e( 'Contact Information', 'taw-theme' ); ?></span>
					</li>
				</ul>
			</div>
			<div class="taw-re-main">
				<div class="taw-re-tabs-content">
					<div class="taw-re-tab is-active" data-tab="basic">
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
												$selected = selected( $property_type, $category->term_id, false );
												echo '<option value="' . esc_attr( $category->term_id ) . '" ' . $selected . '>' . esc_html( $category->name ) . '</option>';
											}
										}
										?>
									</select>
									<p class="description"><?php esc_html_e( 'Select a property category from the list.', 'taw-theme' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="taw_property_price"><?php esc_html_e( 'Property Price', 'taw-theme' ); ?></label>
								</th>
								<td>
									<input type="text" class="regular-text" id="taw_property_price" name="taw_property_price" value="<?php echo esc_attr( $price ); ?>" placeholder="<?php esc_attr_e( 'e.g. 350000', 'taw-theme' ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="taw_property_area"><?php esc_html_e( 'Size', 'taw-theme' ); ?></label>
								</th>
								<td>
									<div class="taw-field-inline">
										<input type="number" step="0.01" min="0" id="taw_property_area" name="taw_property_area" value="<?php echo esc_attr( $area ); ?>">
										<select name="taw_property_area_unit">
											<option value="sqft" <?php selected( $area_unit, 'sqft' ); ?>><?php esc_html_e( 'sqft', 'taw-theme' ); ?></option>
											<option value="sqm" <?php selected( $area_unit, 'sqm' ); ?>><?php esc_html_e( 'sqm', 'taw-theme' ); ?></option>
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
										<input type="number" min="0" step="1" id="taw_property_bedrooms" name="taw_property_bedrooms" value="<?php echo esc_attr( $bedrooms ); ?>" class="taw-number-input">
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
										<input type="number" min="0" step="0.5" id="taw_property_bathrooms" name="taw_property_bathrooms" value="<?php echo esc_attr( $bathrooms ); ?>" class="taw-number-input">
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
									<textarea class="large-text" rows="3" id="taw_property_address" name="taw_property_address" placeholder="<?php esc_attr_e( 'Full property address', 'taw-theme' ); ?>"><?php echo esc_textarea( $address ); ?></textarea>
								</td>
							</tr>
						</table>
					</div>
					<div class="taw-re-tab" data-tab="media">
						<table class="form-table taw-property-meta-table">
							<tr>
								<th scope="row">
									<label for="taw_property_gallery_ids"><?php esc_html_e( 'Gallery Images', 'taw-theme' ); ?></label>
								</th>
								<td>
									<?php $gallery_ids = $this->get_meta( $post->ID, 'gallery_ids', '' ); ?>
									<input type="hidden" id="taw_property_gallery_ids" name="taw_property_gallery_ids" value="<?php echo esc_attr( $gallery_ids ); ?>">
									<button type="button" class="button taw-re-media-upload">
										<?php esc_html_e( 'Select Images', 'taw-theme' ); ?>
									</button>
									<p class="description">
										<?php esc_html_e( 'Choose one or more images to display as a gallery for this property.', 'taw-theme' ); ?>
									</p>
									<div class="taw-re-media-preview">
										<?php
										if ( ! empty( $gallery_ids ) ) {
											$ids = array_filter( array_map( 'absint', explode( ',', $gallery_ids ) ) );
											foreach ( $ids as $attachment_id ) {
												$attachment = get_post( $attachment_id );
												$thumb      = wp_get_attachment_image( $attachment_id, 'thumbnail' );
												if ( $thumb ) {
													$title = $attachment ? $attachment->post_title : '';
													echo '<div class="taw-re-media-item" data-id="' . esc_attr( $attachment_id ) . '">';
													echo '  <div class="taw-re-media-thumb">' . $thumb . '</div>';
													echo '  <button type="button" class="taw-re-media-remove" aria-label="' . esc_attr__( 'Remove image', 'taw-theme' ) . '"><span class="dashicons dashicons-trash"></span></button>';
													if ( $title ) {
														echo '  <div class="taw-re-media-title">' . esc_html( $title ) . '</div>';
													}
													echo '</div>';
												}
											}
										}
										?>
									</div>
								</td>
							</tr>
						</table>
					</div>
					<div class="taw-re-tab" data-tab="agent">
						<table class="form-table taw-property-meta-table">
							<tr>
								<th scope="row">
									<label for="taw_property_contact_name"><?php esc_html_e( 'Contact Name', 'taw-theme' ); ?></label>
								</th>
								<td>
									<input type="text" class="regular-text" id="taw_property_contact_name" name="taw_property_contact_name" value="<?php echo esc_attr( $this->get_meta( $post->ID, 'contact_name', '' ) ); ?>">
									<p class="description"><?php esc_html_e( 'Person or company to contact about this property.', 'taw-theme' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="taw_property_contact_email"><?php esc_html_e( 'Contact Email', 'taw-theme' ); ?></label>
								</th>
								<td>
									<input type="email" class="regular-text" id="taw_property_contact_email" name="taw_property_contact_email" value="<?php echo esc_attr( $this->get_meta( $post->ID, 'contact_email', '' ) ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="taw_property_contact_phone"><?php esc_html_e( 'Contact Phone', 'taw-theme' ); ?></label>
								</th>
								<td>
									<input type="text" class="regular-text" id="taw_property_contact_phone" name="taw_property_contact_phone" value="<?php echo esc_attr( $this->get_meta( $post->ID, 'contact_phone', '' ) ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="taw_property_contact_website"><?php esc_html_e( 'Website URL', 'taw-theme' ); ?></label>
								</th>
								<td>
									<input type="url" class="regular-text" id="taw_property_contact_website" name="taw_property_contact_website" value="<?php echo esc_attr( $this->get_meta( $post->ID, 'contact_website', '' ) ); ?>" placeholder="https://">
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save property meta.
	 */
	public function save_meta( $post_id ) {
		if ( ! isset( $_POST['taw_property_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['taw_property_nonce'] ) ), 'taw_property_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['post_type'] ) && 'taw_property' === $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$fields = array(
				'type'            => 'taw_property_type',
				'area'            => 'taw_property_area',
				'area_unit'       => 'taw_property_area_unit',
				'address'         => 'taw_property_address',
				'price'           => 'taw_property_price',
				'bedrooms'        => 'taw_property_bedrooms',
				'bathrooms'       => 'taw_property_bathrooms',
				'contact_name'    => 'taw_property_contact_name',
				'contact_email'   => 'taw_property_contact_email',
				'contact_phone'   => 'taw_property_contact_phone',
				'contact_website' => 'taw_property_contact_website',
				'gallery_ids'     => 'taw_property_gallery_ids',
			);

			foreach ( $fields as $meta_key => $post_key ) {
				if ( isset( $_POST[ $post_key ] ) ) {
					$value = wp_unslash( $_POST[ $post_key ] );
					update_post_meta( $post_id, '_taw_property_' . $meta_key, sanitize_text_field( $value ) );
				}
			}
		}
	}

/**
 * Render the Properties List meta box (with Available / Selected columns).
 */
public function render_properties_list_meta_box( $post ) {
	if ( ! $post || 'taw_prop_shortcode' !== $post->post_type ) {
		return;
	}

	$selected_properties = get_post_meta( $post->ID, '_taw_shortcode_properties', true );
	if ( ! is_array( $selected_properties ) ) {
		$selected_properties = array();
	}

	if ( ! post_type_exists( 'taw_property' ) ) {
		echo '<p>' . esc_html__( 'Property post type not registered yet.', 'taw-theme' ) . '</p>';
		return;
	}

	$properties = get_posts(
		array(
			'post_type'      => 'taw_property',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	if ( empty( $properties ) ) {
		echo '<p>' . esc_html__( 'No properties found. Please create some properties first.', 'taw-theme' ) . '</p>';
		return;
	}
	?>

	<style>
		.taw-select-properties-grid {
			display: grid;
			grid-template-columns: 1.1fr 0.9fr;
			gap: 16px;
		}
		.taw-select-properties-grid-column {
			min-width: 0;
		}
		.taw-select-properties-list,
		.taw-selected-properties-list {
			max-height: 420px;
			overflow-y: auto;
			border: 1px solid #ddd;
			padding: 10px;
			background: #fff;
		}
		.taw-select-properties-list ul,
		.taw-selected-properties-list ul {
			margin: 0;
			padding-left: 0;
			list-style: none;
		}
		.taw-property-row {
			display: flex;
			align-items: flex-start;
			gap: 8px;
		}
		.taw-property-thumb {
			width: 50px;
			height: 50px;
			flex: 0 0 50px;
			overflow: hidden;
			border-radius: 3px;
			background: #f3f3f3;
		}
		.taw-property-thumb img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			display: block;
		}
		.taw-property-meta {
			font-size: 11px;
			color: #666;
			margin-top: 2px;
		}
	</style>

	<div class="taw-select-properties-grid" id="taw-properties-selector">

		<!-- LEFT: AVAILABLE PROPERTIES -->
		<div class="taw-select-properties-grid-column">
			<label style="font-weight:600;display:block;margin-bottom:4px;">
				<?php esc_html_e( 'Available Properties', 'taw-theme' ); ?>
			</label>
			<div class="taw-select-properties-list">
				<ul id="taw-available-list">
					<?php foreach ( $properties as $property ) : ?>
						<?php
						if ( in_array( $property->ID, $selected_properties, true ) ) {
							continue; // skip, already selected
						}
						$address   = get_post_meta( $property->ID, '_taw_property_address', true );

						// Get category name
						$type_meta = get_post_meta( $property->ID, '_taw_property_type', true );
						$category  = '';
						if ( $type_meta ) {
							if ( is_numeric( $type_meta ) ) {
								$term = get_term( (int) $type_meta, 'taw_property_category' );
								if ( $term && ! is_wp_error( $term ) ) {
									$category = $term->name;
								}
							} else {
								$category = $type_meta;
							}
						}

						$thumb_html = get_the_post_thumbnail( $property->ID, 'thumbnail' );
						?>
						<li class="taw-property-item"
						    data-id="<?php echo esc_attr( $property->ID ); ?>">
							<label style="cursor:pointer;">
								<div class="taw-property-row">
									<input
										type="checkbox"
										class="taw-property-toggle"
										data-id="<?php echo esc_attr( $property->ID ); ?>"
										data-title="<?php echo esc_attr( $property->post_title ); ?>"
										data-address="<?php echo esc_attr( $address ); ?>"
										data-category="<?php echo esc_attr( $category ); ?>"
										style="margin-top:16px;margin-right:8px;"
									>
									<div class="taw-property-thumb"
									     data-thumb-html="<?php echo esc_attr( $thumb_html ); ?>">
										<?php
										if ( $thumb_html ) {
											echo $thumb_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										}
										?>
									</div>
									<div>
										<strong><?php echo esc_html( $property->post_title ); ?></strong>
										<?php if ( $category || $address ) : ?>
											<div class="taw-property-meta">
												<?php if ( $category ) : ?>
													<span class="taw-property-cat-text"><?php echo esc_html( $category ); ?></span>
												<?php endif; ?>
												<?php if ( $category && $address ) : ?>
													<span> · </span>
												<?php endif; ?>
												<?php if ( $address ) : ?>
													<span class="taw-property-address-text"><?php echo esc_html( $address ); ?></span>
												<?php endif; ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
				<p id="taw-available-empty" style="display:none;">
					<?php esc_html_e( 'No more available properties.', 'taw-theme' ); ?>
				</p>
			</div>
		</div>

		<!-- RIGHT: SELECTED PROPERTIES -->
		<div class="taw-select-properties-grid-column">
			<label style="font-weight:600;display:block;margin-bottom:4px;">
				<?php esc_html_e( 'Selected Properties', 'taw-theme' ); ?>
				(<span id="taw_selected_count_side"><?php echo esc_html( count( $selected_properties ) ); ?></span>)
			</label>

			<div class="taw-selected-properties-list">
				<ul id="taw-selected-list">
					<?php foreach ( $properties as $property ) : ?>
						<?php
						if ( ! in_array( $property->ID, $selected_properties, true ) ) {
							continue;
						}
						$address   = get_post_meta( $property->ID, '_taw_property_address', true );
						$type_meta = get_post_meta( $property->ID, '_taw_property_type', true );
						$category  = '';
						if ( $type_meta ) {
							if ( is_numeric( $type_meta ) ) {
								$term = get_term( (int) $type_meta, 'taw_property_category' );
								if ( $term && ! is_wp_error( $term ) ) {
									$category = $term->name;
								}
							} else {
								$category = $type_meta;
							}
						}
						$thumb_html = get_the_post_thumbnail( $property->ID, 'thumbnail' );
						?>
						<li class="taw-property-item"
						    data-id="<?php echo esc_attr( $property->ID ); ?>">
							<label style="cursor:pointer;">
								<div class="taw-property-row">
									<input
										type="checkbox"
										class="taw-property-toggle"
										checked="checked"
										data-id="<?php echo esc_attr( $property->ID ); ?>"
										data-title="<?php echo esc_attr( $property->post_title ); ?>"
										data-address="<?php echo esc_attr( $address ); ?>"
										data-category="<?php echo esc_attr( $category ); ?>"
										style="margin-top:16px;margin-right:8px;"
									>
									<div class="taw-property-thumb"
									     data-thumb-html="<?php echo esc_attr( $thumb_html ); ?>">
										<?php
										if ( $thumb_html ) {
											echo $thumb_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										}
										?>
									</div>
									<div>
										<strong><?php echo esc_html( $property->post_title ); ?></strong>
										<?php if ( $category || $address ) : ?>
											<div class="taw-property-meta">
												<?php if ( $category ) : ?>
													<span class="taw-property-cat-text"><?php echo esc_html( $category ); ?></span>
												<?php endif; ?>
												<?php if ( $category && $address ) : ?>
													<span> · </span>
												<?php endif; ?>
												<?php if ( $address ) : ?>
													<span class="taw-property-address-text"><?php echo esc_html( $address ); ?></span>
												<?php endif; ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</label>

							<input type="hidden" name="taw_shortcode_properties[]"
							       value="<?php echo esc_attr( $property->ID ); ?>" class="taw-selected-hidden">
						</li>
					<?php endforeach; ?>
				</ul>
				<p id="taw-selected-empty" <?php if ( ! empty( $selected_properties ) ) echo 'style="display:none"'; ?>>
					<?php esc_html_e( 'No properties selected.', 'taw-theme' ); ?>
				</p>
			</div>
		</div>

	</div><!-- .taw-select-properties-grid -->

	<script>
		jQuery(function($){
			var $availableList = $('#taw-available-list');
			var $selectedList  = $('#taw-selected-list');
			var $selectedCount = $('#taw_selected_count_side');
			var $availableEmpty = $('#taw-available-empty');
			var $selectedEmpty  = $('#taw-selected-empty');

			function updateCountsAndEmpty() {
				var selectedCount = $selectedList.children('.taw-property-item').length;
				$selectedCount.text(selectedCount);

				if (selectedCount === 0) {
					$selectedEmpty.show();
				} else {
					$selectedEmpty.hide();
				}

				if ($availableList.children('.taw-property-item').length === 0) {
					$availableEmpty.show();
				} else {
					$availableEmpty.hide();
				}
			}

			function buildItemHtml(id, title, address, category, thumbHtml, isSelected) {
				var checked = isSelected ? ' checked="checked"' : '';
				var hidden  = isSelected ? '<input type="hidden" name="taw_shortcode_properties[]" value="'+id+'" class="taw-selected-hidden">' : '';

				var meta = '';
				if (category || address) {
					meta += '<div class="taw-property-meta">';
					if (category) {
						meta += '<span class="taw-property-cat-text">'+_.escape(category)+'</span>';
					}
					if (category && address) {
						meta += '<span> · </span>';
					}
					if (address) {
						meta += '<span class="taw-property-address-text">'+_.escape(address)+'</span>';
					}
					meta += '</div>';
				}

				var html  = '<li class="taw-property-item" data-id="'+id+'">';
				html     += '<label style="cursor:pointer;"><div class="taw-property-row">';
				html     += '<input type="checkbox" class="taw-property-toggle"'+checked
				         + ' data-id="'+id+'"'
				         + ' data-title="'+_.escape(title)+'"'
				         + ' data-address="'+_.escape(address)+'"'
				         + ' data-category="'+_.escape(category)+'"'
				         + ' style="margin-top:16px;margin-right:8px;">';
				html     += '<div class="taw-property-thumb" data-thumb-html="'+_.escape(thumbHtml)+'">';
				if (thumbHtml) {
					html += thumbHtml;
				}
				html     += '</div><div><strong>'+_.escape(title)+'</strong>'+meta+'</div>';
				html     += '</div></label>' + hidden + '</li>';

				return html;
			}

			// Move item between lists when checkbox is toggled
			$(document).on('change', '.taw-property-toggle', function () {
				var $checkbox = $(this);
				var id        = $checkbox.data('id');
				var title     = $checkbox.data('title') || '';
				var address   = $checkbox.data('address') || '';
				var category  = $checkbox.data('category') || '';
				var $thumbDiv = $checkbox.closest('.taw-property-row').find('.taw-property-thumb');
				var thumbHtml = $thumbDiv.data('thumb-html') || $thumbDiv.html() || '';

				var $item = $checkbox.closest('.taw-property-item');

				if ($checkbox.is(':checked')) {
					// Move to SELECTED
					$item.remove();
					$selectedList.append(
						buildItemHtml(id, title, address, category, thumbHtml, true)
					);
				} else {
					// Move back to AVAILABLE
					$item.remove();
					$availableList.append(
						buildItemHtml(id, title, address, category, thumbHtml, false)
					);
				}

				updateCountsAndEmpty();
			});

			updateCountsAndEmpty();
		});
	</script>

	<?php
}

	/**
 * Render the Shortcode Configuration meta box.
 */
public function render_shortcode_config_meta_box( $post ) {
	if ( ! $post || 'taw_prop_shortcode' !== $post->post_type ) {
		return;
	}

	wp_nonce_field( 'taw_shortcode_save', 'taw_shortcode_nonce' );

	$shortcode_name      = get_post_meta( $post->ID, '_taw_shortcode_name', true );
	$selected_properties = get_post_meta( $post->ID, '_taw_shortcode_properties', true );
	$layout              = get_post_meta( $post->ID, '_taw_shortcode_layout', true );
	$columns             = get_post_meta( $post->ID, '_taw_shortcode_columns', true );

	if ( ! is_array( $selected_properties ) ) {
		$selected_properties = array();
	}
	if ( empty( $layout ) ) {
		$layout = 'grid';
	}
	$columns = (int) $columns;
	if ( ! in_array( $columns, array( 1, 2, 3, 4 ), true ) ) {
		$columns = 3; // default
	}

	$shortcode_slug = 'taw_prop_shortcode_' . $post->ID;
	$ids_string     = ! empty( $selected_properties ) ? implode( ',', array_map( 'absint', $selected_properties ) ) : '';
	?>
	<div style="margin-bottom: 15px;">
		 <code style="background: #f0f0f1; padding: 8px 12px; display: block; border-radius: 4px; word-break: break-all; margin-bottom: 5px;">
        [<?php echo esc_html( $shortcode_slug ); ?>]
    </code>
	</div>

	<div style="margin-bottom: 15px;">
		<label for="taw_shortcode_layout" style="display: block; margin-bottom: 5px; font-weight: 600;">
			<?php esc_html_e( 'Layout', 'taw-theme' ); ?>
		</label>
		<select
			id="taw_shortcode_layout"
			name="taw_shortcode_layout"
			style="width: 100%;"
		>
			<option value="grid" <?php selected( $layout, 'grid' ); ?>><?php esc_html_e( 'Grid', 'taw-theme' ); ?></option>
			<option value="list" <?php selected( $layout, 'list' ); ?>><?php esc_html_e( 'List', 'taw-theme' ); ?></option>
		</select>
		<p class="description" style="margin-top: 5px; margin-bottom: 0;">
			<?php esc_html_e( 'Select the layout for displaying properties.', 'taw-theme' ); ?>
		</p>
	</div>

	<div style="margin-bottom: 15px;">
		<label for="taw_shortcode_columns" style="display: block; margin-bottom: 5px; font-weight: 600;">
			<?php esc_html_e( 'Grid Columns', 'taw-theme' ); ?>
		</label>
		<select
			id="taw_shortcode_columns"
			name="taw_shortcode_columns"
			style="width: 100%;"
		>
			<option value="1" <?php selected( $columns, 1 ); ?>>1</option>
			<option value="2" <?php selected( $columns, 2 ); ?>>2</option>
			<option value="3" <?php selected( $columns, 3 ); ?>>3</option>
			<option value="4" <?php selected( $columns, 4 ); ?>>4</option>
		</select>
		<p class="description" style="margin-top: 5px; margin-bottom: 0;">
			<?php esc_html_e( 'Number of items per row when using the Grid layout.', 'taw-theme' ); ?>
		</p>
	</div>

	<!-- <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
		<p style="margin: 0;">
			<strong><?php esc_html_e( 'Selected Properties:', 'taw-theme' ); ?></strong>
			<span id="taw_selected_count"><?php echo esc_html( count( $selected_properties ) ); ?></span>
		</p>
	</div> -->
	<?php
}

	/**
 * Save shortcode meta.
 */
public function save_shortcode_meta( $post_id ) {
	if ( ! isset( $_POST['taw_shortcode_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['taw_shortcode_nonce'] ) ), 'taw_shortcode_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( isset( $_POST['post_type'] ) && 'taw_prop_shortcode' === $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['taw_shortcode_name'] ) ) {
			update_post_meta( $post_id, '_taw_shortcode_name', sanitize_text_field( wp_unslash( $_POST['taw_shortcode_name'] ) ) );
		}

		if ( isset( $_POST['taw_shortcode_properties'] ) && is_array( $_POST['taw_shortcode_properties'] ) ) {
			$properties = array_map( 'absint', wp_unslash( $_POST['taw_shortcode_properties'] ) );
			update_post_meta( $post_id, '_taw_shortcode_properties', $properties );
		} else {
			update_post_meta( $post_id, '_taw_shortcode_properties', array() );
		}

		if ( isset( $_POST['taw_shortcode_layout'] ) ) {
			$layout = sanitize_text_field( wp_unslash( $_POST['taw_shortcode_layout'] ) );
			if ( in_array( $layout, array( 'grid', 'list' ), true ) ) {
				update_post_meta( $post_id, '_taw_shortcode_layout', $layout );
			}
		}

		if ( isset( $_POST['taw_shortcode_columns'] ) ) {
			$cols = (int) $_POST['taw_shortcode_columns'];
			if ( in_array( $cols, array( 1, 2, 3, 4 ), true ) ) {
				update_post_meta( $post_id, '_taw_shortcode_columns', $cols );
			}
		}
	}
}

	/**
	 * Register dynamic shortcodes for all published shortcode posts.
	 */
	public function register_dynamic_shortcodes() {
		if ( ! post_type_exists( 'taw_prop_shortcode' ) ) {
			return;
		}

		try {
			$shortcodes = get_posts(
				array(
					'post_type'        => 'taw_prop_shortcode',
					'posts_per_page'   => -1,
					'post_status'      => 'publish',
					'no_found_rows'    => true,
					'suppress_filters' => false,
				)
			);

			if ( ! empty( $shortcodes ) && is_array( $shortcodes ) ) {
				foreach ( $shortcodes as $shortcode_post ) {
					if ( ! isset( $shortcode_post->ID ) ) {
						continue;
					}
					$shortcode_slug   = 'taw_prop_shortcode_' . $shortcode_post->ID;
					$post_id          = $shortcode_post->ID;
					$module_instance  = $this;

					add_shortcode(
						$shortcode_slug,
						function( $atts ) use ( $post_id, $module_instance ) {
							if ( method_exists( $module_instance, 'render_custom_shortcode' ) ) {
								return $module_instance->render_custom_shortcode( $atts, $post_id );
							}
							return '';
						}
					);
				}
			}
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'TAW Real Estate: Error registering dynamic shortcodes - ' . $e->getMessage() );
			}
		} catch ( Error $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'TAW Real Estate: Fatal error registering dynamic shortcodes - ' . $e->getMessage() );
			}
		}
	}

	/**
 * Render custom shortcode output.
 */
public function render_custom_shortcode( $atts, $shortcode_post_id = null ) {
	if ( empty( $shortcode_post_id ) ) {
		return '<p>' . esc_html__( 'Shortcode not found.', 'taw-theme' ) . '</p>';
	}

	if ( ! post_type_exists( 'taw_property' ) ) {
		return '<p>' . esc_html__( 'Property post type not available.', 'taw-theme' ) . '</p>';
	}

	$selected_properties = get_post_meta( $shortcode_post_id, '_taw_shortcode_properties', true );
	if ( ! is_array( $selected_properties ) || empty( $selected_properties ) ) {
		return '<p>' . esc_html__( 'No properties selected for this shortcode.', 'taw-theme' ) . '</p>';
	}

	$layout  = get_post_meta( $shortcode_post_id, '_taw_shortcode_layout', true );
	$columns = (int) get_post_meta( $shortcode_post_id, '_taw_shortcode_columns', true );
	if ( empty( $layout ) ) {
		$layout = 'grid';
	}
	if ( ! in_array( $columns, array( 1, 2, 3, 4 ), true ) ) {
		$columns = 3;
	}

	$atts = shortcode_atts(
		array(
			'view'    => $layout,
			'layout'  => $layout,
			'columns' => $columns,
		),
		$atts
	);

	$layout = isset( $atts['layout'] ) ? sanitize_text_field( $atts['layout'] ) : $layout;
	if ( empty( $layout ) && isset( $atts['view'] ) ) {
		$layout = sanitize_text_field( $atts['view'] );
	}

	$view    = $this->validate_view( $layout );
	$columns = (int) $atts['columns'];

	$args = array(
		'post_type'      => 'taw_property',
		'post__in'       => $selected_properties,
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'post__in',
	);

	$query = new WP_Query( $args );

	$properties = array();
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$properties[] = $this->prepare_property_data( get_the_ID() );
		}
		wp_reset_postdata();
	}

	$template_path = $this->get_template_path( 'index.php', $view );
	if ( $template_path && file_exists( $template_path ) ) {
		ob_start();
		$module    = $this;
		$columns   = max( 1, $columns );
		$has_posts = $query->have_posts();
		include $template_path;
		return ob_get_clean();
	}

	return $this->render_view( $view, array(
		'properties' => $properties,
		'columns'    => max( 1, $columns ),
		'query'      => $query,
		'has_posts'  => $query->have_posts(),
	) );
}

	/**
 * Render properties shortcode with ids and layout attributes
 * Shortcode: [properties_shortcode ids="1,2,3" layout="grid" columns="3"]
 */
public function render_properties_shortcode( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'ids'     => '',
			'layout'  => 'grid',
			'columns' => 3,
		),
		$atts,
		'properties_shortcode'
	);

	$ids = isset( $atts['ids'] ) ? sanitize_text_field( $atts['ids'] ) : '';
	if ( empty( $ids ) ) {
		return '<p>' . esc_html__( 'No property IDs provided.', 'taw-theme' ) . '</p>';
	}

	$property_ids = array_map( 'absint', explode( ',', $ids ) );
	$property_ids = array_filter( $property_ids );

	if ( empty( $property_ids ) ) {
		return '<p>' . esc_html__( 'Invalid property IDs.', 'taw-theme' ) . '</p>';
	}

	if ( ! post_type_exists( 'taw_property' ) ) {
		return '<p>' . esc_html__( 'Property post type not available.', 'taw-theme' ) . '</p>';
	}

	$layout  = isset( $atts['layout'] ) ? sanitize_text_field( $atts['layout'] ) : 'grid';
	$view    = $this->validate_view( $layout );
	$columns = (int) $atts['columns'];
	if ( ! in_array( $columns, array( 1, 2, 3, 4 ), true ) ) {
		$columns = 3;
	}

	$args = array(
		'post_type'      => 'taw_property',
		'post__in'       => $property_ids,
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'post__in',
	);

	$query = new WP_Query( $args );

	$properties = array();
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$properties[] = $this->prepare_property_data( get_the_ID() );
		}
		wp_reset_postdata();
	}

	$template_path = $this->get_template_path( 'index.php', $view );
	if ( $template_path && file_exists( $template_path ) ) {
		ob_start();
		$module    = $this;
		$columns   = max( 1, $columns );
		$has_posts = $query->have_posts();
		include $template_path;
		return ob_get_clean();
	}

	return $this->render_view( $view, array(
		'properties' => $properties,
		'columns'    => max( 1, $columns ),
		'query'      => $query,
		'has_posts'  => $query->have_posts(),
	) );
}

	/**
	 * Gallery shortcode for a single property
	 * Usage: [taw_property_gallery id="123" size="large"]
	 */
	public function render_property_gallery_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'size'  => 'large',
				'debug' => false,
			),
			$atts,
			'taw_property_gallery'
		);

		$property_id = $this->get_property_id_for_shortcode( $atts['id'] );
		$validation = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );
		
		if ( ! $validation['valid'] ) {
			return $validation['error'];
		}

		$gallery_ids = get_post_meta( $property_id, '_taw_property_gallery_ids', true );
		
		if ( empty( $gallery_ids ) ) {
			if ( $atts['debug'] ) {
				return '<p style="color: orange;">[taw_property_gallery] Debug: No gallery images found for property ID ' . $property_id . '</p>';
			}
			return '';
		}

		$ids = array_filter( array_map( 'absint', explode( ',', $gallery_ids ) ) );
		
		if ( empty( $ids ) ) {
			if ( $atts['debug'] ) {
				return '<p style="color: orange;">[taw_property_gallery] Debug: Gallery IDs empty after parsing.</p>';
			}
			return '';
		}

		ob_start();
		?>
		<div class="taw-property-gallery-shortcode" data-property-id="<?php echo esc_attr( $property_id ); ?>">
			<?php foreach ( $ids as $attachment_id ) : 
				$img = wp_get_attachment_image( $attachment_id, esc_attr( $atts['size'] ) );
				if ( $img ) :
			?>
				<div class="taw-property-gallery-shortcode__item">
					<?php echo $img; ?>
				</div>
			<?php 
				endif;
			endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Property Title Shortcode
	 * Usage: [taw_property_title id="123" tag="h1" link="yes"]
	 */
	public function render_property_title_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'tag'    => 'h1',
				'class'  => 'taw-property-title',
				'link'   => false,
				'debug'  => false,
			),
			$atts,
			'taw_property_title'
		);

		$property_id = $this->get_property_id_for_shortcode( $atts['id'] );
		$validation = $this->validate_property_for_shortcode( $property_id, $atts['debug'], false );
		
		if ( ! $validation['valid'] ) {
			return $validation['error'];
		}

		$title = get_the_title( $property_id );
		if ( empty( $title ) ) {
			return $atts['debug'] ? '<p style="color: orange;">[taw_property_title] No title found.</p>' : '';
		}

		$allowed_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' );
		$tag = in_array( strtolower( $atts['tag'] ), $allowed_tags, true ) ? strtolower( $atts['tag'] ) : 'h1';
		
		$output = '<' . $tag . ' class="' . esc_attr( $atts['class'] ) . '">';
		
		if ( filter_var( $atts['link'], FILTER_VALIDATE_BOOLEAN ) ) {
			$output .= '<a href="' . esc_url( get_permalink( $property_id ) ) . '">' . esc_html( $title ) . '</a>';
		} else {
			$output .= esc_html( $title );
		}
		
		$output .= '</' . $tag . '>';

		return $output;
	}

	/**
	 * Property Price Shortcode
	 * Usage: [taw_property_price id="123" currency="$" format="yes"]
	 */
	public function render_property_price_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'       => 0,
				'currency' => '$',
				'format'   => true,
				'before'   => '',
				'after'    => '',
				'class'    => 'taw-property-price',
				'debug'    => false,
			),
			$atts,
			'taw_property_price'
		);

		$property_id = $this->get_property_id_for_shortcode( $atts['id'] );
		$validation = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );
		
		if ( ! $validation['valid'] ) {
			return $validation['error'];
		}

		$price = $this->get_meta( $property_id, 'price', '' );
		
		if ( empty( $price ) ) {
			return $atts['debug'] ? '<p style="color: orange;">[taw_property_price] No price found for ID ' . $property_id . '</p>' : '';
		}

		$formatted_price = $price;
		if ( filter_var( $atts['format'], FILTER_VALIDATE_BOOLEAN ) && is_numeric( $price ) ) {
			$formatted_price = number_format( floatval( $price ) );
		}

		ob_start();
		?>
		<span class="<?php echo esc_attr( $atts['class'] ); ?>" data-property-id="<?php echo esc_attr( $property_id ); ?>">
			<?php echo esc_html( $atts['before'] ); ?>
			<span class="taw-property-price__currency"><?php echo esc_html( $atts['currency'] ); ?></span>
			<span class="taw-property-price__value"><?php echo esc_html( $formatted_price ); ?></span>
			<?php echo esc_html( $atts['after'] ); ?>
		</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Property Type Shortcode
	 * Usage: [taw_property_type id="123" link="yes"]
	 */
	public function render_property_type_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'link'   => false,
				'class'  => 'taw-property-type',
				'before' => '',
				'after'  => '',
				'debug'  => false,
			),
			$atts,
			'taw_property_type'
		);

		$property_id = $this->get_property_id_for_shortcode( $atts['id'] );
		$validation = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );
		
		if ( ! $validation['valid'] ) {
			return $validation['error'];
		}

		$type_value = $this->get_meta( $property_id, 'type', '' );
		
		if ( empty( $type_value ) ) {
			return $atts['debug'] ? '<p style="color: orange;">[taw_property_type] No type found for ID ' . $property_id . '</p>' : '';
		}

		$type_name = '';
		$type_link = '';
		
		if ( is_numeric( $type_value ) ) {
			$term = get_term( (int) $type_value, 'taw_property_category' );
			if ( ! is_wp_error( $term ) && $term ) {
				$type_name = $term->name;
				$type_link = get_term_link( $term );
			}
		} else {
			$type_name = $type_value;
		}

		if ( empty( $type_name ) ) {
			return '';
		}

		ob_start();
		?>
		<span class="<?php echo esc_attr( $atts['class'] ); ?>" data-property-id="<?php echo esc_attr( $property_id ); ?>">
			<?php echo esc_html( $atts['before'] ); ?>
			<?php if ( filter_var( $atts['link'], FILTER_VALIDATE_BOOLEAN ) && ! empty( $type_link ) && ! is_wp_error( $type_link ) ) : ?>
				<a href="<?php echo esc_url( $type_link ); ?>"><?php echo esc_html( $type_name ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $type_name ); ?>
			<?php endif; ?>
			<?php echo esc_html( $atts['after'] ); ?>
		</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Property Area Shortcode
	 * Usage: [taw_property_area id="123" show_unit="yes"]
	 */
	public function render_property_area_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'        => 0,
				'show_unit' => true,
				'class'     => 'taw-property-area',
				'before'    => '',
				'after'     => '',
				'debug'     => false,
			),
			$atts,
			'taw_property_area'
		);

		$property_id = $this->get_property_id_for_shortcode( $atts['id'] );
		$validation = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );
		
		if ( ! $validation['valid'] ) {
			return $validation['error'];
		}

		$area = $this->get_meta( $property_id, 'area', '' );
		$area_unit = $this->get_meta( $property_id, 'area_unit', 'sqft' );
		
		if ( empty( $area ) ) {
			return $atts['debug'] ? '<p style="color: orange;">[taw_property_area] No area found for ID ' . $property_id . '</p>' : '';
		}

		ob_start();
		?>
		<span class="<?php echo esc_attr( $atts['class'] ); ?>" data-property-id="<?php echo esc_attr( $property_id ); ?>">
			<?php echo esc_html( $atts['before'] ); ?>
			<span class="taw-property-area__value"><?php echo esc_html( $area ); ?></span>
			<?php if ( filter_var( $atts['show_unit'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
				<span class="taw-property-area__unit"><?php echo esc_html( $area_unit ); ?></span>
			<?php endif; ?>
			<?php echo esc_html( $atts['after'] ); ?>
		</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Property Bedrooms Shortcode
	 * Usage: [taw_property_bedrooms id="123" icon="yes" label="Bedrooms"]
	 */
	public function render_property_bedrooms_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'icon'   => false,
				'label'  => '',
				'class'  => 'taw-property-bedrooms',
				'before' => '',
				'after'  => '',
				'debug'  => false,
			),
			$atts,
			'taw_property_bedrooms'
		);

		$property_id = $this->get_property_id_for_shortcode( $atts['id'] );
		$validation = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );
		
		if ( ! $validation['valid'] ) {
			return $validation['error'];
		}

		$bedrooms = $this->get_meta( $property_id, 'bedrooms', '' );
		
		if ( '' === $bedrooms ) {
			return $atts['debug'] ? '<p style="color: orange;">[taw_property_bedrooms] No bedrooms data for ID ' . $property_id . '</p>' : '';
		}

		ob_start();
		?>
		<span class="<?php echo esc_attr( $atts['class'] ); ?>" data-property-id="<?php echo esc_attr( $property_id ); ?>">
			<?php echo esc_html( $atts['before'] ); ?>
			<?php if ( filter_var( $atts['icon'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
				<span class="taw-property-bedrooms__icon dashicons dashicons-bed"></span>
			<?php endif; ?>
			<span class="taw-property-bedrooms__value"><?php echo esc_html( $bedrooms ); ?></span>
			<?php if ( ! empty( $atts['label'] ) ) : ?>
				<span class="taw-property-bedrooms__label"><?php echo esc_html( $atts['label'] ); ?></span>
			<?php endif; ?>
			<?php echo esc_html( $atts['after'] ); ?>
		</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Property Bathrooms Shortcode
	 * Usage: [taw_property_bathrooms id="123" icon="yes" label="Bathrooms"]
	 */
	public function render_property_bathrooms_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'icon'   => false,
				'label'  => '',
				'class'  => 'taw-property-bathrooms',
				'before' => '',
				'after'  => '',
				'debug'  => false,
			),
			$atts,
			'taw_property_bathrooms'
		);

		$property_id = $this->get_property_id_for_shortcode( $atts['id'] );
		$validation = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );
		
		if ( ! $validation['valid'] ) {
			return $validation['error'];
		}

		$bathrooms = $this->get_meta( $property_id, 'bathrooms', '' );
		
		if ( '' === $bathrooms ) {
			return $atts['debug'] ? '<p style="color: orange;">[taw_property_bathrooms] No bathrooms data for ID ' . $property_id . '</p>' : '';
		}

		ob_start();
		?>
		<span class="<?php echo esc_attr( $atts['class'] ); ?>" data-property-id="<?php echo esc_attr( $property_id ); ?>">
			<?php echo esc_html( $atts['before'] ); ?>
			<?php if ( filter_var( $atts['icon'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
				<span class="taw-property-bathrooms__icon dashicons dashicons-bathroom"></span>
			<?php endif; ?>
			<span class="taw-property-bathrooms__value"><?php echo esc_html( $bathrooms ); ?></span>
			<?php if ( ! empty( $atts['label'] ) ) : ?>
				<span class="taw-property-bathrooms__label"><?php echo esc_html( $atts['label'] ); ?></span>
			<?php endif; ?>
			<?php echo esc_html( $atts['after'] ); ?>
		</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Property Address Shortcode
	 * Usage: [taw_property_address id="123" icon="yes" map_link="yes"]
	 */
	public function render_property_address_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'       => 0,
				'icon'     => false,
				'map_link' => false,
				'class'    => 'taw-property-address',
				'before'   => '',
				'after'    => '',
				'debug'    => false,
			),
			$atts,
			'taw_property_address'
		);

		$property_id = $this->get_property_id_for_shortcode( $atts['id'] );
		$validation = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );
		
		if ( ! $validation['valid'] ) {
			return $validation['error'];
		}

		$address = $this->get_meta( $property_id, 'address', '' );
		
		if ( empty( $address ) ) {
			return $atts['debug'] ? '<p style="color: orange;">[taw_property_address] No address found for ID ' . $property_id . '</p>' : '';
		}

		$map_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode( $address );

		ob_start();
		?>
		<span class="<?php echo esc_attr( $atts['class'] ); ?>" data-property-id="<?php echo esc_attr( $property_id ); ?>">
			<?php echo esc_html( $atts['before'] ); ?>
			<?php if ( filter_var( $atts['icon'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
				<span class="taw-property-address__icon dashicons dashicons-location"></span>
			<?php endif; ?>
			<?php if ( filter_var( $atts['map_link'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
				<a href="<?php echo esc_url( $map_url ); ?>" target="_blank" rel="noopener noreferrer" class="taw-property-address__link">
					<?php echo esc_html( $address ); ?>
				</a>
			<?php else : ?>
				<span class="taw-property-address__text"><?php echo esc_html( $address ); ?></span>
			<?php endif; ?>
			<?php echo esc_html( $atts['after'] ); ?>
		</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Property Contact Name Shortcode
	 * Usage: [taw_property_contact_name id="123"]
	 */
	public function render_property_contact_name_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'class' => 'taw-property-contact-name',
				'debug' => false,
			),
			$atts,
			'taw_property_contact_name'
		);

		$property_id = $this->get_property_id_for_shortcode( $atts['id'] );
		$validation = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );
		
		if ( ! $validation['valid'] ) {
			return $validation['error'];
		}

		$contact_name = $this->get_meta( $property_id, 'contact_name', '' );
		
		if ( empty( $contact_name ) ) {
			return $atts['debug'] ? '<p style="color: orange;">[taw_property_contact_name] No contact name found.</p>' : '';
		}

		return '<span class="' . esc_attr( $atts['class'] ) . '">' . esc_html( $contact_name ) . '</span>';
	}

	/**
	 * Property Contact Email Shortcode
	 * Usage: [taw_property_contact_email id="123" link="yes"]
	 */
	public function render_property_contact_email_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'link'  => true,
				'class' => 'taw-property-contact-email',
				'debug' => false,
			),
			$atts,
			'taw_property_contact_email'
		);

		$property_id = $this->get_property_id_for_shortcode( $atts['id'] );
		$validation = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );
		
		if ( ! $validation['valid'] ) {
			return $validation['error'];
		}

		$email = $this->get_meta( $property_id, 'contact_email', '' );
		
		if ( empty( $email ) ) {
			return $atts['debug'] ? '<p style="color: orange;">[taw_property_contact_email] No email found.</p>' : '';
		}

		if ( filter_var( $atts['link'], FILTER_VALIDATE_BOOLEAN ) ) {
			return '<a href="mailto:' . esc_attr( $email ) . '" class="' . esc_attr( $atts['class'] ) . '">' . esc_html( $email ) . '</a>';
		}

		return '<span class="' . esc_attr( $atts['class'] ) . '">' . esc_html( $email ) . '</span>';
	}

	/**
	 * Property Contact Phone Shortcode
	 * Usage: [taw_property_contact_phone id="123" link="yes"]
	 */
	public function render_property_contact_phone_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'link'  => true,
				'class' => 'taw-property-contact-phone',
				'debug' => false,
			),
			$atts,
			'taw_property_contact_phone'
		);

		$property_id = $this->get_property_id_for_shortcode( $atts['id'] );
		$validation = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );
		
		if ( ! $validation['valid'] ) {
			return $validation['error'];
		}

		$phone = $this->get_meta( $property_id, 'contact_phone', '' );
		
		if ( empty( $phone ) ) {
			return $atts['debug'] ? '<p style="color: orange;">[taw_property_contact_phone] No phone found.</p>' : '';
		}

		if ( filter_var( $atts['link'], FILTER_VALIDATE_BOOLEAN ) ) {
			$phone_clean = preg_replace( '/[^0-9+]/', '', $phone );
			return '<a href="tel:' . esc_attr( $phone_clean ) . '" class="' . esc_attr( $atts['class'] ) . '">' . esc_html( $phone ) . '</a>';
		}

		return '<span class="' . esc_attr( $atts['class'] ) . '">' . esc_html( $phone ) . '</span>';
	}

	/**
	 * Property Contact Website Shortcode
	 * Usage: [taw_property_contact_website id="123" text="Visit Website"]
	 */
	public function render_property_contact_website_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'text'   => '',
				'target' => '_blank',
				'class'  => 'taw-property-contact-website',
				'debug'  => false,
			),
			$atts,
			'taw_property_contact_website'
		);

		$property_id = $this->get_property_id_for_shortcode( $atts['id'] );
		$validation = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );
		
		if ( ! $validation['valid'] ) {
			return $validation['error'];
		}

		$website = $this->get_meta( $property_id, 'contact_website', '' );
		
		if ( empty( $website ) ) {
			return $atts['debug'] ? '<p style="color: orange;">[taw_property_contact_website] No website found.</p>' : '';
		}

		$link_text = ! empty( $atts['text'] ) ? $atts['text'] : $website;

		return '<a href="' . esc_url( $website ) . '" target="' . esc_attr( $atts['target'] ) . '" rel="noopener noreferrer" class="' . esc_attr( $atts['class'] ) . '">' . esc_html( $link_text ) . '</a>';
	}

	/**
	 * Generic Property Field Shortcode
	 * Usage: [taw_property_field id="123" field="price"]
	 */
	public function render_property_field_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'field'  => '',
				'before' => '',
				'after'  => '',
				'class'  => 'taw-property-field',
				'debug'  => false,
			),
			$atts,
			'taw_property_field'
		);

		if ( empty( $atts['field'] ) ) {
			return $atts['debug'] ? '<p style="color: red;">[taw_property_field] No field specified.</p>' : '';
		}

		$property_id = $this->get_property_id_for_shortcode( $atts['id'] );
		$validation = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );
		
		if ( ! $validation['valid'] ) {
			return $validation['error'];
		}

		$allowed_fields = array(
			'price'           => 'price',
			'type'            => 'type',
			'area'            => 'area',
			'area_unit'       => 'area_unit',
			'bedrooms'        => 'bedrooms',
			'bathrooms'       => 'bathrooms',
			'address'         => 'address',
			'contact_name'    => 'contact_name',
			'contact_email'   => 'contact_email',
			'contact_phone'   => 'contact_phone',
			'contact_website' => 'contact_website',
		);

		$field_key = sanitize_text_field( $atts['field'] );
		
		if ( ! isset( $allowed_fields[ $field_key ] ) ) {
			return $atts['debug'] ? '<p style="color: red;">[taw_property_field] Invalid field: ' . esc_html( $field_key ) . '</p>' : '';
		}

		$value = $this->get_meta( $property_id, $allowed_fields[ $field_key ], '' );
		
		if ( 'type' === $field_key && ! empty( $value ) && is_numeric( $value ) ) {
			$term = get_term( (int) $value, 'taw_property_category' );
			if ( ! is_wp_error( $term ) && $term ) {
				$value = $term->name;
			}
		}

		if ( empty( $value ) && '' !== $value ) {
			return $atts['debug'] ? '<p style="color: orange;">[taw_property_field] No value for field: ' . esc_html( $field_key ) . '</p>' : '';
		}

		return '<span class="' . esc_attr( $atts['class'] ) . ' taw-property-field--' . esc_attr( $field_key ) . '">' . 
			   esc_html( $atts['before'] ) . esc_html( $value ) . esc_html( $atts['after'] ) . 
			   '</span>';
	}

	/**
	 * Property Details Shortcode - Shows all basic details
	 * Usage: [taw_property_details id="123" layout="horizontal"]
	 */
	public function render_property_details_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'       => 0,
				'layout'   => 'horizontal',
				'show'     => 'all',
				'icons'    => true,
				'labels'   => true,
				'class'    => 'taw-property-details',
				'currency' => '$',
				'debug'    => false,
			),
			$atts,
			'taw_property_details'
		);

		$property_id = $this->get_property_id_for_shortcode( $atts['id'] );
		$validation = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );
		
		if ( ! $validation['valid'] ) {
			return $validation['error'];
		}

		$data = array(
			'price'     => $this->get_meta( $property_id, 'price', '' ),
			'type'      => $this->get_meta( $property_id, 'type', '' ),
			'area'      => $this->get_meta( $property_id, 'area', '' ),
			'area_unit' => $this->get_meta( $property_id, 'area_unit', 'sqft' ),
			'bedrooms'  => $this->get_meta( $property_id, 'bedrooms', '' ),
			'bathrooms' => $this->get_meta( $property_id, 'bathrooms', '' ),
			'address'   => $this->get_meta( $property_id, 'address', '' ),
		);

		if ( ! empty( $data['type'] ) && is_numeric( $data['type'] ) ) {
			$term = get_term( (int) $data['type'], 'taw_property_category' );
			if ( ! is_wp_error( $term ) && $term ) {
				$data['type'] = $term->name;
			}
		}

		$show_fields = array( 'price', 'type', 'bedrooms', 'bathrooms', 'area', 'address' );
		if ( 'all' !== $atts['show'] ) {
			$show_fields = array_map( 'trim', explode( ',', $atts['show'] ) );
		}

		$show_icons = filter_var( $atts['icons'], FILTER_VALIDATE_BOOLEAN );
		$show_labels = filter_var( $atts['labels'], FILTER_VALIDATE_BOOLEAN );

		$fields_config = array(
			'price' => array(
				'icon'  => 'dashicons-money-alt',
				'label' => __( 'Price', 'taw-theme' ),
				'value' => ! empty( $data['price'] ) ? $atts['currency'] . number_format( floatval( $data['price'] ) ) : '',
			),
			'type' => array(
				'icon'  => 'dashicons-category',
				'label' => __( 'Type', 'taw-theme' ),
				'value' => $data['type'],
			),
			'bedrooms' => array(
				'icon'  => 'dashicons-bed',
				'label' => __( 'Bedrooms', 'taw-theme' ),
				'value' => $data['bedrooms'],
			),
			'bathrooms' => array(
				'icon'  => 'dashicons-bathroom',
				'label' => __( 'Bathrooms', 'taw-theme' ),
				'value' => $data['bathrooms'],
			),
			'area' => array(
				'icon'  => 'dashicons-editor-expand',
				'label' => __( 'Area', 'taw-theme' ),
				'value' => ! empty( $data['area'] ) ? $data['area'] . ' ' . $data['area_unit'] : '',
			),
			'address' => array(
				'icon'  => 'dashicons-location',
				'label' => __( 'Address', 'taw-theme' ),
				'value' => $data['address'],
			),
		);

		ob_start();
		?>
		<div class="<?php echo esc_attr( $atts['class'] ); ?> taw-property-details--<?php echo esc_attr( $atts['layout'] ); ?>" data-property-id="<?php echo esc_attr( $property_id ); ?>">
			<?php if ( 'table' === $atts['layout'] ) : ?>
				<table class="taw-property-details__table">
					<?php foreach ( $show_fields as $field_key ) : 
						if ( ! isset( $fields_config[ $field_key ] ) || empty( $fields_config[ $field_key ]['value'] ) ) continue;
						$field = $fields_config[ $field_key ];
					?>
						<tr class="taw-property-details__row taw-property-details__row--<?php echo esc_attr( $field_key ); ?>">
							<th class="taw-property-details__label">
								<?php if ( $show_icons ) : ?>
									<span class="dashicons <?php echo esc_attr( $field['icon'] ); ?>"></span>
								<?php endif; ?>
								<?php echo esc_html( $field['label'] ); ?>
							</th>
							<td class="taw-property-details__value"><?php echo esc_html( $field['value'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php else : ?>
				<ul class="taw-property-details__list">
					<?php foreach ( $show_fields as $field_key ) : 
						if ( ! isset( $fields_config[ $field_key ] ) || empty( $fields_config[ $field_key ]['value'] ) ) continue;
						$field = $fields_config[ $field_key ];
					?>
						<li class="taw-property-details__item taw-property-details__item--<?php echo esc_attr( $field_key ); ?>">
							<?php if ( $show_icons ) : ?>
								<span class="taw-property-details__icon dashicons <?php echo esc_attr( $field['icon'] ); ?>"></span>
							<?php endif; ?>
							<?php if ( $show_labels ) : ?>
								<span class="taw-property-details__label"><?php echo esc_html( $field['label'] ); ?>:</span>
							<?php endif; ?>
							<span class="taw-property-details__value"><?php echo esc_html( $field['value'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Add custom columns to shortcode listing.
	 */
	public function shortcode_columns( $columns ) {
		if ( ! isset( $_GET['post_type'] ) || 'taw_prop_shortcode' !== $_GET['post_type'] ) {
			return $columns;
		}
		
		$new_columns           = array();
		$new_columns['cb']     = isset( $columns['cb'] ) ? $columns['cb'] : '';
		$new_columns['title']  = isset( $columns['title'] ) ? $columns['title'] : '';
		$new_columns['shortcode']  = __( 'Shortcode', 'taw-theme' );
		$new_columns['properties'] = __( 'Properties', 'taw-theme' );
		$new_columns['date']   = isset( $columns['date'] ) ? $columns['date'] : '';
		return $new_columns;
	}

	/**
	 * Display custom column content.
	 */
	public function shortcode_column_content( $column, $post_id ) {
		if ( 'shortcode' === $column ) {
			$shortcode_slug = 'taw_prop_shortcode_' . $post_id;
			echo '<code style="background: #f0f0f1; padding: 4px 8px; border-radius: 3px;">[' . esc_html( $shortcode_slug ) . ']</code>';
		}
		if ( 'properties' === $column ) {
			$selected_properties = get_post_meta( $post_id, '_taw_shortcode_properties', true );
			if ( is_array( $selected_properties ) && ! empty( $selected_properties ) ) {
				echo esc_html( count( $selected_properties ) ) . ' ' . esc_html__( 'properties', 'taw-theme' );
			} else {
				echo '<span style="color: #d63638;">' . esc_html__( 'No properties selected', 'taw-theme' ) . '</span>';
			}
		}
	}
}

/*[taw_property_title]	[taw_property_title id="123" tag="h2" link="yes"]
[taw_property_price]	[taw_property_price id="123" currency="€" format="yes"]
[taw_property_type]	[taw_property_type id="123" link="yes"]
[taw_property_area]	[taw_property_area id="123" show_unit="yes"]
[taw_property_bedrooms]	[taw_property_bedrooms id="123" icon="yes" label="Beds"]
[taw_property_bathrooms]	[taw_property_bathrooms id="123" icon="yes" label="Baths"]
[taw_property_address]	[taw_property_address id="123" icon="yes" map_link="yes"]
[taw_property_contact_name]	[taw_property_contact_name id="123"]
[taw_property_contact_email]	[taw_property_contact_email id="123" link="yes"]
[taw_property_contact_phone]	[taw_property_contact_phone id="123" link="yes"]
[taw_property_contact_website]	[taw_property_contact_website id="123" text="Visit"]
[taw_property_field]	[taw_property_field id="123" field="price"]
[taw_property_details]	[taw_property_details id="123" layout="table" icons="yes"]
[taw_property_gallery]	[taw_property_gallery id="123" size="large"]
[taw_property_list]	[taw_property_list category="sale" posts_per_page="6"]
[properties_shortcode]	[properties_shortcode ids="1,2,3" layout="grid"]*/
