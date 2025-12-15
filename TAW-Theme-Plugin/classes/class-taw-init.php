<?php
/**
 * TAW Theme Initialization
 *
 * This class initializes TAW Theme plugin
 *
 * @class TAW_Init
 * @package TAW_Theme
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TAW_Init Class
 *
 * Handles initialization and loading of standalone TAW features
 */
class TAW_Init {

	/**
	 * Constructor
	 *
	 * Initializes the plugin
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Only proceed if WordPress is loaded
		if ( ! defined( 'ABSPATH' ) ) {
			return;
		}

		// Load all the required files first.
		$this->includes();

		// Initialize admin class
		if ( class_exists( 'TAW_Admin' ) ) {
			new TAW_Admin();
		}
	}

	/**
	 * Function that includes necessary files
	 *
	 * @since 1.0.0
	 */
	public function includes() {
		// Helper functions.
		$helper_file = TAW_MODULES_DIR . 'classes/class-taw-helper.php';
		if ( file_exists( $helper_file ) ) {
			require_once $helper_file;
		}

		// Admin class.
		$admin_file = TAW_MODULES_DIR . 'classes/class-taw-admin.php';
		if ( file_exists( $admin_file ) ) {
			require_once $admin_file;
		}

		// Module system.
		$base_file = TAW_MODULES_DIR . 'classes/class-taw-module-base.php';
		if ( file_exists( $base_file ) ) {
			require_once $base_file;
		}

		$manager_file = TAW_MODULES_DIR . 'classes/class-taw-module-manager.php';
		if ( file_exists( $manager_file ) ) {
			require_once $manager_file;
		}

		// Initialize module manager only if class exists.
		if ( class_exists( 'TAW_Module_Manager' ) ) {
			TAW_Module_Manager::get_instance();

			// Load modules on plugins_loaded hook to ensure WordPress is ready
			// This prevents issues during plugin activation
			if ( function_exists( 'add_action' ) ) {
				add_action( 'plugins_loaded', array( $this, 'load_modules' ), 5 );
			}
		}
	}

	/**
	 * Load all registered modules
	 *
	 * @since 1.0.0
	 */
	public function load_modules() {
		// Ensure base class is loaded before loading any modules
		if ( ! class_exists( 'TAW_Module_Base' ) ) {
			return;
		}

		// Load Real Estate module.
		$realestate_module = TAW_MODULES_DIR . 'classes/modules/realestate/module-loader.php';
		if ( file_exists( $realestate_module ) ) {
			try {
				require_once $realestate_module;
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'TAW Real Estate Module Error: ' . $e->getMessage() );
				}
			} catch ( Error $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'TAW Real Estate Module Fatal Error: ' . $e->getMessage() );
				}
			}
		}

		// Add more modules here as they are created.
		// Example:
		// $another_module = TAW_MODULES_DIR . 'classes/modules/another-module/module-loader.php';
		// if ( file_exists( $another_module ) ) {
		//     require_once $another_module;
		// }

// Testimonials module.
	$testimonials_module = TAW_MODULES_DIR . 'classes/modules/Testimonials/testimonial-module-loder.php';
	if ( file_exists( $testimonials_module ) ) {
		require_once $testimonials_module;
	}
	}


}

