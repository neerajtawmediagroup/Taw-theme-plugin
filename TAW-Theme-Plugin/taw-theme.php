<?php
/**
 * Plugin Name: TAW Theme Builder
 * Plugin URI: https://www.yourwebsite.com
 * Description: TAW Theme Builder – Modular theme builder with real estate property management and listing tools.
 * Version: 1.0.0
 * Author: TAW Media Group
 * Author URI: https://www.yourwebsite.com
 * Text Domain: taw-theme
 * Domain Path: /languages
 *
 * @package TAW_Theme
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define plugin constants
 */

// Main plugin directory constant expected by theme / other modules.
if ( ! defined( 'TAW_THEME_PLUGIN_DIR' ) ) {
	if ( function_exists( 'plugin_dir_path' ) ) {
		define( 'TAW_THEME_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	} else {
		$dir = dirname( __FILE__ );
		define( 'TAW_THEME_PLUGIN_DIR', ( substr( $dir, -1 ) !== '/' ) ? $dir . '/' : $dir );
	}
}

// For backward‑compatibility / internal use, we keep MODULES_*
// and simply map them to the same dir / URL.
if ( ! defined( 'TAW_MODULES_DIR' ) ) {
	define( 'TAW_MODULES_DIR', TAW_THEME_PLUGIN_DIR );
}

if ( ! defined( 'TAW_MODULES_URL' ) ) {
	if ( function_exists( 'plugins_url' ) ) {
		define( 'TAW_MODULES_URL', plugins_url( '/', __FILE__ ) );
	} else {
		// Fallback URL calculation.
		$wp_plugin_dir = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : dirname( dirname( __FILE__ ) );
		$plugin_folder = basename( dirname( __FILE__ ) );
		$plugins_url   = defined( 'WP_PLUGIN_URL' ) ? WP_PLUGIN_URL : '';
		define( 'TAW_MODULES_URL', $plugins_url . '/' . $plugin_folder . '/' );
	}
}

if ( ! defined( 'TAW_MODULES_VERSION' ) ) {
	define( 'TAW_MODULES_VERSION', '1.0.0' );
}

/**
 * Initialize TAW Theme
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'TAW_Theme' ) ) {

	/**
	 * Main TAW Theme Class
	 *
	 * @class TAW_Theme
	 */
	class TAW_Theme {

		/**
		 * Constructor function that initializes required actions and hooks.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->define_constant();

			// TAW Initialize.
			$init_file = TAW_MODULES_DIR . 'classes/class-taw-init.php';

			if ( file_exists( $init_file ) ) {
				require_once $init_file;

				// Instantiate the init class.
				if ( class_exists( 'TAW_Init' ) ) {
					try {
						new TAW_Init();
					} catch ( Exception $e ) {
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'TAW Init Error: ' . $e->getMessage() );
						}
					} catch ( Error $e ) {
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log(
								'TAW Init Fatal Error: ' . $e->getMessage() .
								' in ' . $e->getFile() .
								' on line ' . $e->getLine()
							);
						}
					}
				}
			}
		}

		/**
		 * Function which defines additional constants for TAW plugin.
		 *
		 * @since 1.0.0
		 */
		public function define_constant() {
			// Currently constants are defined above; kept for future use.
		}
	}
}

// Initialize the plugin.
if ( class_exists( 'TAW_Theme' ) ) {
	try {
		new TAW_Theme();
	} catch ( Exception $e ) {
		// Log error if WP_DEBUG is enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'TAW Theme Plugin Error: ' . $e->getMessage() );
		}
	} catch ( Error $e ) {
		// Catch fatal errors in PHP 7+.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				'TAW Theme Plugin Fatal Error: ' . $e->getMessage() .
				' in ' . $e->getFile() .
				' on line ' . $e->getLine()
			);
		}
	}
}

/**
 * AJAX handler for live saving property (admin).
 */
add_action( 'wp_ajax_taw_save_property_live', 'taw_save_property_live' );
function taw_save_property_live() {

	// Security.
	check_ajax_referer( 'taw_property_save', 'security' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Permission denied', 'taw-theme' ),
			)
		);
	}

	$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
	$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

	// If title empty, keep a placeholder.
	if ( '' === $title ) {
		$title = __( 'Untitled Property', 'taw-theme' );
	}

	$postarr = array(
		'post_type'    => 'taw_property',
		'post_title'   => $title,
		'post_content' => $content,
		'post_status'  => 'draft',
	);

	$post_id = wp_insert_post( $postarr, true );

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error(
			array(
				'message' => $post_id->get_error_message(),
			)
		);
	}

	wp_send_json_success(
		array(
			'message'   => __( 'Saved', 'taw-theme' ),
			'post_id'   => $post_id,
			'edit_link' => get_edit_post_link( $post_id ),
		)
	);
}

/**
 * Enqueue admin script for the Add Property page.
 */
add_action( 'admin_enqueue_scripts', 'taw_property_live_enqueue' );
function taw_property_live_enqueue( $hook ) {

	// Adjust condition if you want to limit to specific admin pages.
	$script_rel_path = 'assets/js/taw-property-live.js';
	$script_path     = plugin_dir_url( __FILE__ ) . $script_rel_path;

	wp_enqueue_script(
		'taw-property-live',
		$script_path,
		array( 'jquery' ),
		file_exists( plugin_dir_path( __FILE__ ) . $script_rel_path )
			? filemtime( plugin_dir_path( __FILE__ ) . $script_rel_path )
			: TAW_MODULES_VERSION,
		true
	);

	// Localize: pass variables to JS (ajax url + nonce).
	wp_localize_script(
		'taw-property-live',
		'TAWPropertyLive',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'taw_property_save' ),
		)
	);
}

add_action( 'init', function() {
    // Only unhook if the class & method exist
    if ( class_exists( 'AGS_divi_wc' ) ) {
        // Remove method AGS_divi_wc::onEditTaxonomyTerm from 'edit_term'
        remove_action( 'edit_term', array( 'AGS_divi_wc', 'onEditTaxonomyTerm' ), 10, 4 );
    }
});




add_action( 'after_setup_theme', function() {
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'post-thumbnails', array( 'taw_testimonial' ) );
} );