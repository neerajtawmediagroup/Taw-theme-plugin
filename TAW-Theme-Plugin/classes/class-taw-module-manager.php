<?php
/**
 * TAW Module Manager
 *
 * Manages registration and initialization of all modules
 *
 * @package TAW_Theme
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TAW_Module_Manager Class
 *
 * Handles module registration and management
 */
class TAW_Module_Manager {

	/**
	 * Registered modules
	 *
	 * @var array
	 */
	private static $modules = array();

	/**
	 * Instance
	 *
	 * @var TAW_Module_Manager
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return TAW_Module_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Only add actions if WordPress is ready
		if ( function_exists( 'add_action' ) ) {
			// Load modules on plugins_loaded so they're ready before init fires
			// Use priority 25 to ensure module loaders have run first (they run at priority 5)
			add_action( 'plugins_loaded', array( $this, 'load_modules' ), 25 );
			// Also try on init as fallback with early priority
			add_action( 'init', array( $this, 'load_modules' ), 10 );
			add_action( 'admin_menu', array( $this, 'register_module_menus' ), 15 );
		}
	}

	/**
	 * Register a module
	 *
	 * @param string $module_id Module ID.
	 * @param string $module_class Module class name.
	 * @param string $module_name Module display name.
	 */
	public static function register_module( $module_id, $module_class, $module_name ) {
		if ( ! isset( self::$modules[ $module_id ] ) ) {
			self::$modules[ $module_id ] = array(
				'class' => $module_class,
				'name'  => $module_name,
			);
		}
	}

	/**
	 * Get registered modules
	 *
	 * @return array
	 */
	public static function get_modules() {
		return self::$modules;
	}

	/**
	 * Get a specific module instance
	 *
	 * @param string $module_id Module ID.
	 * @return TAW_Module_Base|null
	 */
	public static function get_module( $module_id ) {
		if ( isset( self::$modules[ $module_id ] ) && isset( self::$modules[ $module_id ]['instance'] ) ) {
			return self::$modules[ $module_id ]['instance'];
		}
		return null;
	}

	/**
	 * Load all registered modules
	 */
	public function load_modules() {
		// Prevent double-loading
		static $loaded = false;
		if ( $loaded ) {
			return;
		}

		// Ensure WordPress core functions are available
		if ( ! function_exists( 'register_post_type' ) ) {
			return;
		}

		foreach ( self::$modules as $module_id => $module_data ) {
			// Skip if already instantiated
			if ( isset( $module_data['instance'] ) ) {
				continue;
			}

			if ( isset( $module_data['class'] ) && class_exists( $module_data['class'] ) ) {
				try {
					// Check if base class exists before instantiating
					if ( ! class_exists( 'TAW_Module_Base' ) ) {
						continue;
					}

					$module_instance = new $module_data['class']( $module_id, $module_data['name'] );
					if ( $module_instance instanceof TAW_Module_Base ) {
						self::$modules[ $module_id ]['instance'] = $module_instance;
					}
				} catch ( Exception $e ) {
					// Silently fail if module can't be instantiated
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'TAW Module Error (' . $module_id . '): ' . $e->getMessage() );
					}
					continue;
				} catch ( Error $e ) {
					// Catch fatal errors in PHP 7+
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'TAW Module Fatal Error (' . $module_id . '): ' . $e->getMessage() );
					}
					continue;
				}
			}
		}

		$loaded = true;
	}

	/**
	 * Register admin menus for all modules
	 */
	public function register_module_menus() {
		// Ensure modules are loaded before registering menus
		$this->load_modules();
		
		foreach ( self::$modules as $module_id => $module_data ) {
			if ( isset( $module_data['instance'] ) && method_exists( $module_data['instance'], 'register_admin_menu' ) ) {
				$module_data['instance']->register_admin_menu();
			}
		}
	}
}

