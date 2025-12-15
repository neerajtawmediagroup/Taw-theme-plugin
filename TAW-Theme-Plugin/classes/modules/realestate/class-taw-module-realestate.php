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

		// Admin assets for property and shortcode screens.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );

		add_action( 'add_meta_boxes', array( $this, 'register_shortcode_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_shortcode_meta' ) );

		add_action( 'init', array( $this, 'register_dynamic_shortcodes' ), 20 );
		add_action( 'init', array( $this, 'register_shortcode_column_filters' ), 25 );
		add_action( 'init', array( $this, 'register_shortcodes' ), 30 );

		// Ensure default categories exist.
		add_action( 'init', array( $this, 'maybe_create_default_property_categories' ), 40 );

		// Hook admin menu (if needed).
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 20 );
	}

// 	public function register_dynamic_shortcodes() {
//     if ( ! post_type_exists( 'taw_prop_shortcode' ) ) {
//         return;
//     }

//     $shortcodes = get_posts(
//         array(
//             'post_type'        => 'taw_prop_shortcode',
//             'posts_per_page'   => -1,
//             'post_status'      => 'publish',
//             'no_found_rows'    => true,
//             'suppress_filters' => false,
//         )
//     );

//     if ( ! empty( $shortcodes ) && is_array( $shortcodes ) ) {
//         foreach ( $shortcodes as $shortcode_post ) {
//             if ( ! isset( $shortcode_post->ID ) ) {
//                 continue;
//             }

//             $shortcode_slug  = 'taw_prop_shortcode_' . $shortcode_post->ID;
//             $post_id         = $shortcode_post->ID;
//             $module_instance = $this;

//             add_shortcode(
//                 $shortcode_slug,
//                 function( $atts ) use ( $post_id, $module_instance ) {
//                     if ( method_exists( $module_instance, 'render_custom_shortcode' ) ) {
//                         return $module_instance->render_custom_shortcode( $atts, $post_id );
//                     }
//                     return '';
//                 }
//             );
//         }
//     }
// }

	/**
	 * Ensure post types are registered before WordPress validates them.
	 */
	public function ensure_post_types_registered() {
		$this->register_post_types();
		// NOTE: register_shortcode_column_filters() is now only hooked on init.
	}

	/**
	 * Register column filters for shortcode post type.
	 */
	public function register_shortcode_column_filters() {
		if ( post_type_exists( 'taw_prop_shortcode' ) ) {
			add_filter( 'manage_taw_prop_shortcode_posts_columns', array( $this, 'shortcode_columns' ) );
			add_action( 'manage_taw_prop_shortcode_posts_custom_column', array( $this, 'shortcode_column_content' ), 10, 2 );
		}
	}

	/**
	 * Register post types.
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
	 * Create default Property Categories once.
	 */
	public function maybe_create_default_property_categories() {
		// Only run in admin for users that can manage options.
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Only run once.
		if ( get_option( 'taw_property_default_terms_created' ) ) {
			return;
		}

		$defaults = array(
			array(
				'name'        => 'Apartment',
				'slug'        => 'apartment',
				'description' => 'Apartment properties',
			),
			array(
				'name'        => 'Villa',
				'slug'        => 'villa',
				'description' => 'Villa properties',
			),
			array(
				'name'        => 'Commercial',
				'slug'        => 'commercial',
				'description' => 'Commercial properties',
			),
		);

		foreach ( $defaults as $term ) {
			if ( ! term_exists( $term['slug'], 'taw_property_category' ) ) {
				wp_insert_term(
					$term['name'],
					'taw_property_category',
					array(
						'slug'        => $term['slug'],
						'description' => $term['description'],
					)
				);
			}
		}

		update_option( 'taw_property_default_terms_created', 1 );
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
	 * Enqueue admin assets.
	 */
	public function enqueue_admin_assets( $hook ) {
		$allowed_hooks = array(
			'post.php',
			'post-new.php',
			'taw-theme-builder_page_taw-property-add',
		);

		if ( ! in_array( $hook, $allowed_hooks, true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $hook !== 'taw-theme-builder_page_taw-property-add' ) {
			if ( ! $screen || ( 'taw_property' !== $screen->post_type && 'taw_prop_shortcode' !== $screen->post_type ) ) {
				return;
			}
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
	 * Register admin menu.
	 */
	public function register_admin_menu() {
		// Make sure CPTs exist so the submenu URLs work.
		$this->register_post_types();

		global $submenu;

		if ( isset( $submenu['taw-theme-builder'] ) ) {
			remove_submenu_page( 'taw-theme-builder', 'edit.php?post_type=taw_property' );
			remove_submenu_page( 'taw-theme-builder', 'post-new.php?post_type=taw_property' );
			remove_submenu_page( 'taw-theme-builder', 'edit.php?post_type=taw_prop_shortcode' );
			remove_submenu_page( 'taw-theme-builder', 'post-new.php?post_type=taw_prop_shortcode' );
		}

		// Expose Real Estate items in main TAW menu.
	// 		add_submenu_page(
	//     'taw-theme-builder',
	//     __( 'Manage View', 'taw-theme' ),
	//     '  ' . __( 'Manage View', 'taw-theme' ),
	//     'manage_options',
	//     'taw-views-list',
	//     array( $this, 'render_views_page' )
	// );
		// add_submenu_page(
		// 	'taw-theme-builder',
		// 	__( 'All Listings', 'taw-theme' ),
		// 	'  ' . __( 'All Listings', 'taw-theme' ),
		// 	'manage_options',
		// 	'edit.php?post_type=taw_property'
		// );

		// *** THIS is the important change ***
		// add_submenu_page(
		// 	'taw-theme-builder',
		// 	__( 'Categories', 'taw-theme' ),
		// 	'  ' . __( 'Categories', 'taw-theme' ),
		// 	'manage_options',
		// 	'taw-property-categories',                         // custom slug
		// 	array( $this, 'render_property_categories_page' ) // callback defined above
		// );

		// add_submenu_page(
		// 	'taw-theme-builder',
		// 	__( 'Manage View', 'taw-theme' ),
		// 	'  ' . __( 'Manage View', 'taw-theme' ),
		// 	'manage_options',
		// 	'edit.php?post_type=taw_prop_shortcode'
		// );
	}

/**
	 * Render the "Manage View" page (taw-views-list).
	 */
	// public function render_views_page() {
	// 	// Path to your custom admin template file for listing/managing views.
	// 	// Adjust this path to match your folder structure.
	// 	$template = $this->module_dir . 'templates/admin/taw-views-list.php';

	// 	if ( file_exists( $template ) ) {
	// 		include $template;
	// 	} else {
	// 		// Simple fallback to avoid fatal errors if the template is missing.
	// 		echo '<div class="wrap">';
	// 		echo '<h1>' . esc_html__( 'Manage View', 'taw-theme' ) . '</h1>';
	// 		echo '<p>' . esc_html__( 'Views template file not found.', 'taw-theme' ) . '</p>';
	// 		echo '</div>';
	// 	}
	// }

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
	 * Get meta with default.
	 */
	protected function get_meta( $post_id, $key, $default = '' ) {
		$value = get_post_meta( $post_id, '_taw_property_' . $key, true );
		return ( '' === $value || null === $value ) ? $default : $value;
	}

	/**
	 * Get available views.
	 */
	protected function get_available_views() {
		return array(
			'grid' => __( 'Grid', 'taw-theme' ),
			'list' => __( 'List', 'taw-theme' ),
		);
	}

	/**
	 * Validate view.
	 */
	protected function validate_view( $view ) {
		$views = $this->get_available_views();
		return array_key_exists( $view, $views ) ? $view : 'grid';
	}

	/**
	 * Get property ID from various sources (helper for shortcodes).
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
	 * Validate property for shortcode.
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
					'error' => '<p style="color: red; border: 1px solid red; padding: 10px;">[Shortcode] Debug: No property ID found. Please use id="YOUR_PROPERTY_ID"</p>',
				);
			}
			return array( 'valid' => false, 'error' => '' );
		}

		$post_obj = get_post( $property_id );
		if ( ! $post_obj ) {
			if ( $debug ) {
				return array(
					'valid' => false,
					'error' => '<p style="color: red;">[Shortcode] Debug: Property ID ' . $property_id . ' not found.</p>',
				);
			}
			return array( 'valid' => false, 'error' => '' );
		}

		if ( $strict && 'taw_property' !== get_post_type( $property_id ) ) {
			if ( $debug ) {
				return array(
					'valid' => false,
					'error' => '<p style="color: red;">[Shortcode] Debug: Post type is "' . get_post_type( $property_id ) . '", not "taw_property".</p>',
				);
			}
			return array( 'valid' => false, 'error' => '' );
		}

		return array( 'valid' => true, 'error' => '' );
	}

	/**
	 * Render main list shortcode: [taw_property_list].
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

		return $this->render_view(
			$view,
			array(
				'properties' => $properties,
				'columns'    => absint( $atts['columns'] ),
				'query'      => $query,
				'has_posts'  => $query->have_posts(),
			)
		);
	}

	/**
	 * Prepare property data (shared across all views).
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
	 * Render view template.
	 */
	private function render_view( $view, $args = array() ) {
		$args['module'] = $this;
		$output         = $this->load_template( 'wrapper.php', $args, $view );
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
	 * Render property meta box HTML inline.
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

				</div><!-- .taw-re-tabs-content -->
			</div><!-- .taw-re-main -->
		</div><!-- .taw-re-panel -->
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
					// gallery_ids is a comma-separated string.
					if ( 'gallery_ids' === $meta_key ) {
						$value = sanitize_text_field( $value );
					} else {
						$value = sanitize_text_field( $value );
					}
					update_post_meta( $post_id, '_taw_property_' . $meta_key, $value );
				}
			}

			// Optionally synchronize taxonomy type if 'type' is a numeric term_id.
			if ( isset( $_POST['taw_property_type'] ) && intval( $_POST['taw_property_type'] ) ) {
				wp_set_object_terms(
					$post_id,
					intval( $_POST['taw_property_type'] ),
					'taw_property_category',
					false
				);
			}
		}
	}

	/**
	 * Render the Properties List meta box (left side).
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
		<div style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
			<p style="margin-top: 0;">
				<label>
					<input type="checkbox" id="taw_select_all_properties" style="margin-right: 5px;">
					<strong><?php esc_html_e( 'Select All', 'taw-theme' ); ?></strong>
				</label>
			</p>
			<ul style="list-style: none; margin: 0; padding: 0;">
				<?php
				foreach ( $properties as $property ) {
					$checked           = in_array( $property->ID, $selected_properties, true ) ? 'checked' : '';
					$property_meta     = get_post_meta( $property->ID, '_taw_property_address', true );
					$property_address  = ! empty( $property_meta ) ? ' - ' . esc_html( $property_meta ) : '';
					?>
					<li style="padding: 8px 0; border-bottom: 1px solid #eee;">
						<label style="display: flex; align-items: center; cursor: pointer;">
							<input
								type="checkbox"
								name="taw_shortcode_properties[]"
								value="<?php echo esc_attr( $property->ID ); ?>"
								<?php echo esc_attr( $checked ); ?>
								style="margin-right: 10px;"
							>
							<span>
								<strong><?php echo esc_html( $property->post_title ); ?></strong>
								<?php if ( ! empty( $property_address ) ) : ?>
									<br><small style="color: #666;"><?php echo esc_html( $property_meta ); ?></small>
								<?php endif; ?>
							</span>
						</label>
					</li>
					<?php
				}
				?>
			</ul>
		</div>
		<p class="description">
			<?php
			printf(
				esc_html__( 'Selected: %d property(ies)', 'taw-theme' ),
				count( $selected_properties )
			);
			?>
			<span id="taw_selected_count" style="display:none;"></span>
		</p>
		<script>
		jQuery(document).ready(function($) {
			function updateSelectedCount() {
				var checked = $('input[name="taw_shortcode_properties[]"]:checked').length;
				$('#taw_selected_count').text(checked);
			}

			$('#taw_select_all_properties').on('change', function() {
				$('input[name="taw_shortcode_properties[]"]').prop('checked', $(this).prop('checked'));
				updateSelectedCount();
			});

			$('input[name="taw_shortcode_properties[]"]').on('change', function() {
				var total = $('input[name="taw_shortcode_properties[]"]').length;
				var checked = $('input[name="taw_shortcode_properties[]"]:checked').length;
				$('#taw_select_all_properties').prop('checked', total === checked);
				updateSelectedCount();
			});

			var total = $('input[name="taw_shortcode_properties[]"]').length;
			var checked = $('input[name="taw_shortcode_properties[]"]:checked').length;
			$('#taw_select_all_properties').prop('checked', total === checked && total > 0);
			updateSelectedCount();
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

		if ( ! is_array( $selected_properties ) ) {
			$selected_properties = array();
		}
		if ( empty( $layout ) ) {
			$layout = 'grid';
		}

		$shortcode_slug = 'taw_prop_shortcode_' . $post->ID;
		$ids_string     = ! empty( $selected_properties ) ? implode( ',', array_map( 'absint', $selected_properties ) ) : '';
		?>
		<div style="margin-bottom: 15px;">
			<label for="taw_shortcode_name" style="display: block; margin-bottom: 5px; font-weight: 600;">
				<?php esc_html_e( 'Shortcode Name', 'taw-theme' ); ?>
			</label>
			<input
				type="text"
				class="regular-text"
				id="taw_shortcode_name"
				name="taw_shortcode_name"
				value="<?php echo esc_attr( $shortcode_name ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. Featured Properties', 'taw-theme' ); ?>"
				style="width: 100%;"
			>
			<p class="description" style="margin-top: 5px; margin-bottom: 0;">
				<?php esc_html_e( 'Enter a name for this shortcode (for your reference only).', 'taw-theme' ); ?>
			</p>
		</div>

		<!-- Layout selection cards -->
		<div style="margin-bottom: 15px;">
			<label style="display: block; margin-bottom: 8px; font-weight: 600;">
				<?php esc_html_e( 'Please select the template layout', 'taw-theme' ); ?>
			</label>

			<div class="taw-layout-toggle">
				<!-- Hidden real field that will be saved -->
				<input type="hidden" id="taw_shortcode_layout" name="taw_shortcode_layout" value="<?php echo esc_attr( $layout ); ?>">

				<!-- Grid card -->
				<button type="button"
					class="taw-layout-card <?php echo ( 'grid' === $layout ) ? 'is-active' : ''; ?>"
					data-layout-value="grid">
					<span class="taw-layout-card__icon dashicons dashicons-screenoptions"></span>
					<span class="taw-layout-card__label"><?php esc_html_e( 'Grid View', 'taw-theme' ); ?></span>
				</button>

				<!-- List card -->
				<button type="button"
					class="taw-layout-card <?php echo ( 'list' === $layout ) ? 'is-active' : ''; ?>"
					data-layout-value="list">
					<span class="taw-layout-card__icon dashicons dashicons-menu-alt"></span>
					<span class="taw-layout-card__label"><?php esc_html_e( 'List View', 'taw-theme' ); ?></span>
				</button>
			</div>

			<p class="description" style="margin-top: 6px; margin-bottom: 0;">
				<?php esc_html_e( 'Select the layout for displaying properties.', 'taw-theme' ); ?>
			</p>

			<script>
			(function($){
				$(document).on('click', '.taw-layout-card', function(e){
					e.preventDefault();
					var $btn   = $(this);
					var layout = $btn.data('layout-value');

					// Toggle active state
					$btn
						.addClass('is-active')
						.siblings('.taw-layout-card').removeClass('is-active');

					// Set hidden field
					$('#taw_shortcode_layout').val(layout);
				});
			})(jQuery);
			</script>
		</div>

		<?php if ( 'publish' === $post->post_status ) : ?>
		<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
			<label style="display: block; margin-bottom: 5px; font-weight: 600;">
				<?php esc_html_e( 'Generated Shortcode', 'taw-theme' ); ?>
			</label>
			<code style="background: #f0f0f1; padding: 8px 12px; display: block; border-radius: 4px; word-break: break-all; margin-bottom: 5px;">
				[properties_shortcode ids="<?php echo esc_attr( $ids_string ); ?>" layout="<?php echo esc_attr( $layout ); ?>"]
			</code>
			<p class="description" style="margin: 0;">
				<?php esc_html_e( 'Copy and paste this shortcode into any page or post.', 'taw-theme' ); ?>
			</p>
		</div>
		<?php endif; ?>

		<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
			<p style="margin: 0;">
				<strong><?php esc_html_e( 'Selected Properties:', 'taw-theme' ); ?></strong>
				<span id="taw_selected_count"><?php echo esc_html( count( $selected_properties ) ); ?></span>
			</p>
		</div>
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

					$shortcode_slug  = 'taw_prop_shortcode_' . $shortcode_post->ID;
					$post_id         = $shortcode_post->ID;
					$module_instance = $this;

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

		$layout = get_post_meta( $shortcode_post_id, '_taw_shortcode_layout', true );
		if ( empty( $layout ) ) {
			$layout = 'grid';
		}

		$atts = shortcode_atts(
			array(
				'view'    => $layout,
				'layout'  => $layout,
				'columns' => 3,
			),
			$atts
		);

		$layout = isset( $atts['layout'] ) ? sanitize_text_field( $atts['layout'] ) : $layout;
		if ( empty( $layout ) && isset( $atts['view'] ) ) {
			$layout = sanitize_text_field( $atts['view'] );
		}

		$view = $this->validate_view( $layout );

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
			$columns   = absint( $atts['columns'] );
			$has_posts = $query->have_posts();
			include $template_path;
			return ob_get_clean();
		}

		return $this->render_view(
			$view,
			array(
				'properties' => $properties,
				'columns'    => absint( $atts['columns'] ),
				'query'      => $query,
				'has_posts'  => $query->have_posts(),
			)
		);
	}

	/**
	 * Render properties shortcode with ids and layout attributes.
	 * Shortcode: [properties_shortcode ids="1,2,3" layout="grid"].
	 */
public function render_properties_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'ids'    => '',
				'layout' => 'grid',
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

		$layout = isset( $atts['layout'] ) ? sanitize_text_field( $atts['layout'] ) : 'grid';
		$view   = $this->validate_view( $layout );

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
			$columns   = 3;
			$has_posts = $query->have_posts();
			include $template_path;
			return ob_get_clean();
		}

		return $this->render_view(
			$view,
			array(
				'properties' => $properties,
				'columns'    => 3,
				'query'      => $query,
				'has_posts'  => $query->have_posts(),
			)
		);
	}

	/**
	 * Gallery shortcode for a single property.
	 * Usage: [taw_property_gallery id="123" size="large"].
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
		$validation  = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );

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

		// Ensure string then explode.
		if ( is_array( $gallery_ids ) ) {
			$gallery_ids = implode( ',', array_map( 'absint', $gallery_ids ) );
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
	 * Property Title Shortcode.
	 * Usage: [taw_property_title id="123" tag="h1" link="yes"].
	 */
	public function render_property_title_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'tag'   => 'h1',
				'class' => 'taw-property-title',
				'link'  => false,
				'debug' => false,
			),
			$atts,
			'taw_property_title'
		);

		$property_id = $this->get_property_id_for_shortcode( $atts['id'] );
		// strict = false: allow other post types if used outside property loop.
		$validation = $this->validate_property_for_shortcode( $property_id, $atts['debug'], false );

		if ( ! $validation['valid'] ) {
			return $validation['error'];
		}

		$title = get_the_title( $property_id );
		if ( empty( $title ) ) {
			return $atts['debug'] ? '<p style="color: orange;">[taw_property_title] No title found.</p>' : '';
		}

		$allowed_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' );
		$tag          = in_array( strtolower( $atts['tag'] ), $allowed_tags, true ) ? strtolower( $atts['tag'] ) : 'h1';

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
	 * Property Price Shortcode.
	 * Usage: [taw_property_price id="123" currency="$" format="yes"].
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
		$validation  = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );

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
	 * Property Type Shortcode.
	 * Usage: [taw_property_type id="123" link="yes"].
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
		$validation  = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );

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
	 * Property Area Shortcode.
	 * Usage: [taw_property_area id="123" show_unit="yes"].
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
		$validation  = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );

		if ( ! $validation['valid'] ) {
			return $validation['error'];
		}

		$area      = $this->get_meta( $property_id, 'area', '' );
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
	 * Property Bedrooms Shortcode.
	 * Usage: [taw_property_bedrooms id="123" icon="yes" label="Bedrooms"].
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
		$validation  = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );

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
	 * Property Bathrooms Shortcode.
	 * Usage: [taw_property_bathrooms id="123" icon="yes" label="Bathrooms"].
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
		$validation  = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );

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
	 * Property Address Shortcode.
	 * Usage: [taw_property_address id="123" icon="yes" map_link="yes"].
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
		$validation  = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );

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
	 * Property Contact Name Shortcode.
	 * Usage: [taw_property_contact_name id="123"].
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
		$validation  = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );

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
	 * Property Contact Email Shortcode.
	 * Usage: [taw_property_contact_email id="123" link="yes"].
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
		$validation  = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );

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
	 * Property Contact Phone Shortcode.
	 * Usage: [taw_property_contact_phone id="123" link="yes"].
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
		$validation  = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );

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
	 * Property Contact Website Shortcode.
	 * Usage: [taw_property_contact_website id="123" text="Visit Website"].
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
		$validation  = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );

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
	 * Generic Property Field Shortcode.
	 * Usage: [taw_property_field id="123" field="price"].
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
		$validation  = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );

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

		if ( '' === $value ) {
			return $atts['debug'] ? '<p style="color: orange;">[taw_property_field] No value for field: ' . esc_html( $field_key ) . '</p>' : '';
		}

		return '<span class="' . esc_attr( $atts['class'] ) . ' taw-property-field--' . esc_attr( $field_key ) . '">' .
				esc_html( $atts['before'] ) . esc_html( $value ) . esc_html( $atts['after'] ) .
			   '</span>';
	}

	/**
	 * Property Details Shortcode - Shows all basic details.
	 * Usage: [taw_property_details id="123" layout="horizontal"].
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
		$validation  = $this->validate_property_for_shortcode( $property_id, $atts['debug'] );

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

		$show_icons  = filter_var( $atts['icons'], FILTER_VALIDATE_BOOLEAN );
		$show_labels = filter_var( $atts['labels'], FILTER_VALIDATE_BOOLEAN );

		$fields_config = array(
			'price'     => array(
				'icon'  => 'dashicons-money-alt',
				'label' => __( 'Price', 'taw-theme' ),
				'value' => ! empty( $data['price'] ) ? $atts['currency'] . number_format( floatval( $data['price'] ) ) : '',
			),
			'type'      => array(
				'icon'  => 'dashicons-category',
				'label' => __( 'Type', 'taw-theme' ),
				'value' => $data['type'],
			),
			'bedrooms'  => array(
				'icon'  => 'dashicons-bed',
				'label' => __( 'Bedrooms', 'taw-theme' ),
				'value' => $data['bedrooms'],
			),
			'bathrooms' => array(
				'icon'  => 'dashicons-bathroom',
				'label' => __( 'Bathrooms', 'taw-theme' ),
				'value' => $data['bathrooms'],
			),
			'area'      => array(
				'icon'  => 'dashicons-editor-expand',
				'label' => __( 'Area', 'taw-theme' ),
				'value' => ! empty( $data['area'] ) ? $data['area'] . ' ' . $data['area_unit'] : '',
			),
			'address'   => array(
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
						if ( ! isset( $fields_config[ $field_key ] ) || empty( $fields_config[ $field_key ]['value'] ) ) {
							continue;
						}
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
						if ( ! isset( $fields_config[ $field_key ] ) || empty( $fields_config[ $field_key ]['value'] ) ) {
							continue;
						}
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
		$new_columns               = array();
		$new_columns['cb']         = $columns['cb'] ?? '';
		$new_columns['title']      = $columns['title'] ?? '';
		$new_columns['shortcode']  = __( 'Shortcode', 'taw-theme' );
		$new_columns['properties'] = __( 'Properties', 'taw-theme' );
		$new_columns['date']       = $columns['date'] ?? '';
		return $new_columns;
	}

	/**
	 * Display custom column content.
	 */
	public function shortcode_column_content( $column, $post_id ) {
		if ( 'shortcode' === $column ) {
			$shortcode_slug = 'taw_prop_shortcode_' . $post_id;
			echo '<code id="shortcode-' . esc_attr( $post_id ) . '" style="background: #f0f0f1; padding: 4px 8px; border-radius: 3px;">[' . esc_html( $shortcode_slug ) . ']</code> ';
			echo '<button class="button button-small copy-shortcode" data-target="shortcode-' . esc_attr( $post_id ) . '">' . esc_html__( 'Copy', 'taw-theme' ) . '</button>';
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


	/**
	 * Custom Property Categories screen.
	 */
	public function render_property_categories_page() {
		// Adjust constant/path if your base constant is different.
		$template = $this->module_dir . 'templates/admin/taw-Categories-list.php';

		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<div class="error"><p>Property categories template not found.</p></div>';
		}
	}
}

/**
 * TAW_Property_Manager
 *
 * Handles:
 * - admin copy-to-clipboard script (for shortcode list)
 * - custom add/edit "taw_property" handler for admin.php?page=taw-property-add
 */
class TAW_Property_Manager {

	public function __construct() {
		// Enqueue scripts for copy button on shortcode list screen.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Handle add/edit from custom property form.
		add_action( 'admin_post_taw_property_save', array( $this, 'handle_property_save' ) );
		// If you need front‑end submissions, also:
		// add_action( 'admin_post_nopriv_taw_property_save', array( $this, 'handle_property_save' ) );

		// Handle bulk actions from custom list table.
    // add_action( 'admin_post_taw_properties_bulk_action', array( $this, 'handle_bulk_action' ) );
	}

	/**
	 * Enqueue JS for copy-to-clipboard on shortcode list screen.
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on shortcode CPT list: edit.php?post_type=taw_prop_shortcode
		if ( 'edit.php' !== $hook ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'taw_prop_shortcode' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'taw-property-live',
			plugin_dir_url( __FILE__ ) . 'taw-property-live.js',
			array( 'jquery' ),
			'1.0',
			true
		);
	}

	/**
	 * Handle Add/Edit "taw_property" from custom admin form.
	 * Form posts to admin-post.php?action=taw_property_save
	 */
	public function handle_property_save() {

		// 1. Nonce check.
		if (
			empty( $_POST['taw_property_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['taw_property_nonce'] ) ),
				'taw_property_save'
			)
		) {
			wp_die( esc_html__( 'Security check failed.', 'taw-theme' ) );
		}

		// 2. Capability check.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'taw-theme' ) );
		}

		// 3. Gather & sanitize.
		$property_id = isset( $_POST['property_id'] ) ? absint( $_POST['property_id'] ) : 0;

		$post_title   = isset( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : '';
		$post_content = isset( $_POST['post_content'] ) ? wp_kses_post( wp_unslash( $_POST['post_content'] ) ) : '';
		$post_status  = isset( $_POST['post_status'] ) ? sanitize_key( wp_unslash( $_POST['post_status'] ) ) : 'publish';

		$property_type      = isset( $_POST['taw_property_type'] ) ? absint( $_POST['taw_property_type'] ) : 0;
		$property_price     = isset( $_POST['taw_property_price'] ) ? sanitize_text_field( wp_unslash( $_POST['taw_property_price'] ) ) : '';
		$property_area      = isset( $_POST['taw_property_area'] ) ? sanitize_text_field( wp_unslash( $_POST['taw_property_area'] ) ) : '';
		$property_area_unit = isset( $_POST['taw_property_area_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['taw_property_area_unit'] ) ) : 'sqft';
		$property_bedrooms  = isset( $_POST['taw_property_bedrooms'] ) ? sanitize_text_field( wp_unslash( $_POST['taw_property_bedrooms'] ) ) : '0';
		$property_bathrooms = isset( $_POST['taw_property_bathrooms'] ) ? sanitize_text_field( wp_unslash( $_POST['taw_property_bathrooms'] ) ) : '0';
		$property_address   = isset( $_POST['taw_property_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['taw_property_address'] ) ) : '';
		$gallery_ids        = isset( $_POST['taw_property_gallery_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['taw_property_gallery_ids'] ) ) : '';

		// NEW: Featured image ID.
		$featured_image_id  = isset( $_POST['taw_property_featured_image'] ) ? absint( $_POST['taw_property_featured_image'] ) : 0;

		// 4. Basic validation.
		if ( '' === $post_title ) {
			$redirect_url = add_query_arg(
				array(
					'page'   => 'taw-property-add',
					'error'  => rawurlencode( __( 'Title is required.', 'taw-theme' ) ),
				),
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// 5. Build post array.
		$post_data = array(
			'post_title'   => $post_title,
			'post_content' => $post_content,
			'post_status'  => in_array( $post_status, array( 'publish', 'draft' ), true ) ? $post_status : 'publish',
			'post_type'    => 'taw_property',
		);

		// 6. Insert or update.
		if ( $property_id > 0 ) {
			// Update existing property.
			$post_data['ID'] = $property_id;
			$property_id     = wp_update_post( $post_data, true );
		} else {
			// Create new property.
			$property_id = wp_insert_post( $post_data, true );
		}

		// 7. Handle error.
		if ( is_wp_error( $property_id ) ) {
			$redirect_url = add_query_arg(
				array(
					'page'  => 'taw-property-add',
					'error' => rawurlencode( $property_id->get_error_message() ),
				),
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// 8. Save meta (use same meta keys that the module uses everywhere else).
		update_post_meta( $property_id, '_taw_property_type',            $property_type );
		update_post_meta( $property_id, '_taw_property_price',           $property_price );
		update_post_meta( $property_id, '_taw_property_area',            $property_area );
		update_post_meta( $property_id, '_taw_property_area_unit',       $property_area_unit );
		update_post_meta( $property_id, '_taw_property_bedrooms',        $property_bedrooms );
		update_post_meta( $property_id, '_taw_property_bathrooms',       $property_bathrooms );
		update_post_meta( $property_id, '_taw_property_address',         $property_address );
		update_post_meta( $property_id, '_taw_property_gallery_ids',     $gallery_ids );

		// 8b. Featured image (post thumbnail).
		if ( $featured_image_id ) {
			set_post_thumbnail( $property_id, $featured_image_id );
		} else {
			delete_post_thumbnail( $property_id );
		}

		// 9. Set taxonomy term if provided.
		if ( $property_type ) {
			wp_set_object_terms(
				$property_id,
				array( $property_type ),
				'taw_property_category',
				false
			);
		}

		// 10. Redirect back to custom add/edit screen in EDIT mode.
		$redirect_url = add_query_arg(
			array(
				'page'        => 'taw-property-add',
				'property_id' => $property_id,
				'message'     => rawurlencode( __( 'Property saved successfully.', 'taw-theme' ) ),
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}
}

/**
 * Handle bulk actions for taw_property (All Listing page).
 * Form: admin-post.php?action=taw_properties_bulk_action
 */
add_action( 'admin_post_taw_properties_bulk_action', 'taw_handle_properties_bulk_action' );

function taw_handle_properties_bulk_action() {

    // 1. Nonce check – must match the form in the list template.
    if (
        empty( $_POST['taw_bulk_nonce'] ) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['taw_bulk_nonce'] ) ),
            'taw_bulk_action'
        )
    ) {
        wp_die( esc_html__( 'Security check failed.', 'taw-theme' ) );
    }

    // 2. Permission check.
    if ( ! current_user_can( 'delete_posts' ) ) {
        wp_die( esc_html__( 'You are not allowed to perform this action.', 'taw-theme' ) );
    }

    // 3. Read action + selected IDs.
    $bulk_action = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';
    $post_ids    = isset( $_POST['post_ids'] ) && is_array( $_POST['post_ids'] )
        ? array_map( 'absint', $_POST['post_ids'] )
        : array();

    // 4. Redirect URL (keeps filters/search/paged from your list page).
    $redirect = ! empty( $_POST['redirect_url'] )
        ? esc_url_raw( wp_unslash( $_POST['redirect_url'] ) )
        : admin_url( 'admin.php?page=taw-properties-list' );

    if ( empty( $bulk_action ) || empty( $post_ids ) ) {
        wp_safe_redirect( $redirect );
        exit;
    }

    // 5. Apply the bulk action.
    foreach ( $post_ids as $post_id ) {
        // Only operate on taw_property posts.
        if ( 'taw_property' !== get_post_type( $post_id ) ) {
            continue;
        }

        switch ( $bulk_action ) {
            case 'trash':
                wp_trash_post( $post_id );
                break;

            case 'restore':
                wp_untrash_post( $post_id );
                break;

            case 'delete':
                wp_delete_post( $post_id, true ); // permanent delete
                break;
        }
    }

    // 6. Optional success message.
    $redirect = add_query_arg(
        'message',
        rawurlencode( __( 'Bulk action completed.', 'taw-theme' ) ),
        $redirect
    );

    wp_safe_redirect( $redirect );
    exit;
}


// Initialize manager.
new TAW_Property_Manager();

/**
 * Ensure media uploader is available on custom property add/edit page.
 */
add_action(
	'admin_enqueue_scripts',
	function( $hook ) {
		if ( isset( $_GET['page'] ) && 'taw-property-add' === $_GET['page'] ) {
			wp_enqueue_media();
		}
	}
);


// Handle bulk actions on Manage View page (taw_prop_shortcode posts).
add_action( 'admin_post_taw_views_bulk_action', 'taw_handle_views_bulk_action' );
function taw_handle_views_bulk_action() {

    // 1. Nonce check.
    if (
        empty( $_POST['taw_views_bulk_nonce'] ) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['taw_views_bulk_nonce'] ) ),
            'taw_views_bulk_action'
        )
    ) {
        wp_die( esc_html__( 'Security check failed.', 'taw-theme' ) );
    }

    // 2. Capability check – adjust if needed.
    if ( ! current_user_can( 'delete_posts' ) ) {
        wp_die( esc_html__( 'You are not allowed to perform this action.', 'taw-theme' ) );
    }

    $bulk_action = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';
    $view_ids    = isset( $_POST['view_ids'] ) && is_array( $_POST['view_ids'] )
        ? array_map( 'absint', $_POST['view_ids'] )
        : array();

    if ( empty( $bulk_action ) || empty( $view_ids ) ) {
        // Nothing selected or no action – return to list.
        wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=taw-views-list' ) );
        exit;
    }

    foreach ( $view_ids as $view_id ) {
        // Ensure correct post type if you want.
        if ( 'taw_prop_shortcode' !== get_post_type( $view_id ) ) {
            continue;
        }

        switch ( $bulk_action ) {
            case 'trash':
                wp_trash_post( $view_id );
                break;

            case 'restore':
                wp_untrash_post( $view_id );
                break;

            case 'delete':
                wp_delete_post( $view_id, true ); // permanent delete
                break;
        }
    }

    // Redirect back to where we came from, keeps post_status filter & search.
    $redirect = wp_get_referer();
    if ( ! $redirect ) {
        $redirect = admin_url( 'admin.php?page=taw-views-list' );
    }

    wp_safe_redirect( $redirect );
    exit;
}

// Move a single view to trash.
add_action( 'admin_post_taw_view_trash', 'taw_view_trash_handler' );
function taw_view_trash_handler() {
    if ( empty( $_GET['view_id'] ) || empty( $_GET['_wpnonce'] ) ) {
        wp_die( esc_html__( 'Missing parameters.', 'taw-theme' ) );
    }

    $view_id = absint( $_GET['view_id'] );

    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'taw_view_trash_' . $view_id ) ) {
        wp_die( esc_html__( 'Security check failed.', 'taw-theme' ) );
    }

    if ( ! current_user_can( 'delete_post', $view_id ) ) {
        wp_die( esc_html__( 'You are not allowed to perform this action.', 'taw-theme' ) );
    }

    // Optional: ensure correct post type
    if ( 'taw_prop_shortcode' === get_post_type( $view_id ) ) {
        wp_trash_post( $view_id );
    }

    wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=taw-views-list' ) );
    exit;
}

// Restore a trashed view.
add_action( 'admin_post_taw_view_restore', 'taw_view_restore_handler' );
function taw_view_restore_handler() {
    if ( empty( $_GET['view_id'] ) || empty( $_GET['_wpnonce'] ) ) {
        wp_die( esc_html__( 'Missing parameters.', 'taw-theme' ) );
    }

    $view_id = absint( $_GET['view_id'] );

    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'taw_view_restore_' . $view_id ) ) {
        wp_die( esc_html__( 'Security check failed.', 'taw-theme' ) );
    }

    if ( ! current_user_can( 'delete_post', $view_id ) ) {
        wp_die( esc_html__( 'You are not allowed to perform this action.', 'taw-theme' ) );
    }

    if ( 'taw_prop_shortcode' === get_post_type( $view_id ) ) {
        wp_untrash_post( $view_id );
    }

    wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=taw-views-list' ) );
    exit;
}

// Delete a view permanently.
add_action( 'admin_post_taw_view_delete', 'taw_view_delete_handler' );
function taw_view_delete_handler() {
    if ( empty( $_GET['view_id'] ) || empty( $_GET['_wpnonce'] ) ) {
        wp_die( esc_html__( 'Missing parameters.', 'taw-theme' ) );
    }

    $view_id = absint( $_GET['view_id'] );

    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'taw_view_delete_' . $view_id ) ) {
        wp_die( esc_html__( 'Security check failed.', 'taw-theme' ) );
    }

    if ( ! current_user_can( 'delete_post', $view_id ) ) {
        wp_die( esc_html__( 'You are not allowed to perform this action.', 'taw-theme' ) );
    }

    if ( 'taw_prop_shortcode' === get_post_type( $view_id ) ) {
        wp_delete_post( $view_id, true );
    }

    wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=taw-views-list' ) );
    exit;
}



/*
Shortcodes (usage examples):
[taw_property_title]                  [taw_property_title id="123" tag="h2" link="yes"]
[taw_property_price]                  [taw_property_price id="123" currency="€" format="yes"]
[taw_property_type]                   [taw_property_type id="123" link="yes"]
[taw_property_area]                   [taw_property_area id="123" show_unit="yes"]
[taw_property_bedrooms]               [taw_property_bedrooms id="123" icon="yes" label="Beds"]
[taw_property_bathrooms]              [taw_property_bathrooms id="123" icon="yes" label="Baths"]
[taw_property_address]                [taw_property_address id="123" icon="yes" map_link="yes"]
[taw_property_contact_name]           [taw_property_contact_name id="123"]
[taw_property_contact_email]          [taw_property_contact_email id="123" link="yes"]
[taw_property_contact_phone]          [taw_property_contact_phone id="123" link="yes"]
[taw_property_contact_website]        [taw_property_contact_website id="123" text="Visit"]
[taw_property_field]                  [taw_property_field id="123" field="price"]
[taw_property_details]                [taw_property_details id="123" layout="table" icons="yes"]
[taw_property_gallery]                [taw_property_gallery id="123" size="large"]
[taw_property_list]                   [taw_property_list category="sale" posts_per_page="6"]
[properties_shortcode]                [properties_shortcode ids="1,2,3" layout="grid"]
*/