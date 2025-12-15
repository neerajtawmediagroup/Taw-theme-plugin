<?php
/**
 * Testimonials Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'TAW_Testimonial_Module' ) ) :

class TAW_Testimonial_Module extends TAW_Module_Base {

	/**
	 * Module init.
	 * IMPORTANT: call parent::init() so base hooks still work.
	 */
	protected function init() {
		parent::init();

		// Register this module’s shortcodes.
		add_action( 'init', array( $this, 'register_shortcodes' ), 5 );

		// Frontend assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Register post type
	 * (called from TAW_Module_Base::init() on 'init' hook)
	 */
	public function register_post_types() {

		$labels = array(
			'name'               => _x( 'TAW Testimonials', 'post type general name', 'taw-theme' ),
			'singular_name'      => _x( 'TAW Testimonial', 'post type singular name', 'taw-theme' ),
			'add_new'            => __( 'Add New', 'taw-theme' ),
			'add_new_item'       => __( 'Add New Testimonial', 'taw-theme' ),
			'edit_item'          => __( 'Edit Testimonial', 'taw-theme' ),
			'new_item'           => __( 'New Testimonial', 'taw-theme' ),
			'all_items'          => __( 'All Testimonials', 'taw-theme' ),
			'view_item'          => __( 'View Testimonial', 'taw-theme' ),
			'search_items'       => __( 'Search Testimonials', 'taw-theme' ),
			'not_found'          => __( 'No testimonials found', 'taw-theme' ),
			'not_found_in_trash' => __( 'No testimonials found in Trash', 'taw-theme' ),
			'menu_name'          => __( 'TAW Testimonials', 'taw-theme' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'testimonial' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 10,
			'menu_icon'          => 'dashicons-testimonial',
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
		);

		register_post_type( 'taw_testimonial', $args );
	}

	/**
	 * Register shortcodes
	 */
	public function register_shortcodes() {
		add_shortcode( 'taw_testimonials', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets() {
		wp_enqueue_style(
			'taw-testimonials',
			$this->module_url . 'assets/css/testimonials.css',
			array(),
			$this->module_version
		);
	}

	/**
	 * Render shortcode output.
	 *
	 * @param array  $atts
	 * @param string $content
	 * @return string
	 */
	public function render_shortcode( $atts, $content = '' ) {

		$atts = shortcode_atts(
			array(
				'posts_per_page' => 5,
				'order'          => 'DESC',
				'view'           => 'grid',
			),
			$atts,
			'taw_testimonials'
		);

		$view = $this->validate_view( $atts['view'] );

		$query = new WP_Query( array(
			'post_type'      => 'taw_testimonial',
			'posts_per_page' => (int) $atts['posts_per_page'],
			'order'          => $atts['order'],
		) );

		$args = array(
			'query' => $query,
			'atts'  => $atts,
			'view'  => $view,
		);

		// Use the base-class template loader:
		$output = $this->load_template( 'loop-testimonials.php', $args, 'view' );

		wp_reset_postdata();

		// Fallback if template missing.
		if ( '' === $output ) {
			ob_start();
			if ( $query->have_posts() ) {
				echo '<div class="taw-testimonials">';
				while ( $query->have_posts() ) {
					$query->the_post();
					echo '<div class="taw-testimonial-item">';
					echo '<h3 class="taw-testimonial-title">' . esc_html( get_the_title() ) . '</h3>';
					echo '<div class="taw-testimonial-content">' . wp_kses_post( get_the_content() ) . '</div>';
					echo '</div>';
				}
				echo '</div>';
			} else {
				echo '<p>' . esc_html__( 'No testimonials found.', 'taw-theme' ) . '</p>';
			}
			$output = ob_get_clean();
		}

		return $output;
	}

	/**
	 * Optional: override available views for this module
	 */
	protected function get_available_views() {
		return array(
			'grid' => __( 'Grid', 'taw-theme' ),
		);
	}
}

endif;