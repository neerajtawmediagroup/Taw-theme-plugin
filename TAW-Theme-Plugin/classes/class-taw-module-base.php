<?php
/**
 * TAW Module Base Class
 *
 * Base class that all modules should extend
 *
 * @package TAW_Theme
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TAW_Module_Base Class
 *
 * Base class for all TAW Theme modules
 */
abstract class TAW_Module_Base {

	/**
	 * Module ID (slug)
	 *
	 * @var string
	 */
	protected $module_id;

	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $module_name;

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $module_version = '1.0.0';

	/**
	 * Module directory path
	 *
	 * @var string
	 */
	protected $module_dir;

	/**
	 * Module URL
	 *
	 * @var string
	 */
	protected $module_url;

	/**
	 * Constructor
	 *
	 * @param string $module_id Module ID.
	 * @param string $module_name Module name.
	 */
	public function __construct( $module_id, $module_name ) {
		$this->module_id   = $module_id;
		$this->module_name = $module_name;
		$this->module_dir  = TAW_MODULES_DIR . 'classes/modules/' . $module_id . '/';
		$this->module_url  = TAW_MODULES_URL . 'classes/modules/' . $module_id . '/';

		$this->init();
	}

	/**
	 * Initialize the module
	 * Override this method in child classes
	 */
	protected function init() {
		// Only register hooks if WordPress functions are available
		if ( ! function_exists( 'add_action' ) ) {
			return;
		}

		// Register post types on init hook with priority 0 (earliest possible)
		// This ensures they're registered before WordPress validates them
		add_action( 'init', array( $this, 'register_post_types' ), 0 );
		add_action( 'init', array( $this, 'register_shortcodes' ), 5 );
		
		// Also register immediately if init has already fired
		if ( did_action( 'init' ) ) {
			$this->register_post_types();
		}

		// Register other hooks
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Get module ID
	 *
	 * @return string
	 */
	public function get_module_id() {
		return $this->module_id;
	}

	/**
	 * Get module name
	 *
	 * @return string
	 */
	public function get_module_name() {
		return $this->module_name;
	}

	/**
	 * Register post types
	 * Override in child classes
	 */
	public function register_post_types() {
		// Override in child classes.
	}

	/**
	 * Register shortcodes
	 * Override in child classes
	 */
	public function register_shortcodes() {
		// Override in child classes.
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Override in child classes.
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets() {
		// Override in child classes.
	}

	/**
	 * Register admin menu items
	 * Override in child classes
	 */
	public function register_admin_menu() {
		// Override in child classes.
	}

	/**
	 * Get meta value with default
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key without prefix.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	protected function get_meta( $post_id, $key, $default = '' ) {
		$value = get_post_meta( $post_id, '_taw_' . $this->module_id . '_' . $key, true );
		return ( '' === $value || null === $value ) ? $default : $value;
	}

	/**
	 * Update meta value
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key without prefix.
	 * @param mixed  $value Value to save.
	 */
	protected function update_meta( $post_id, $key, $value ) {
		update_post_meta( $post_id, '_taw_' . $this->module_id . '_' . $key, $value );
	}

	/**
	 * Render shortcode output
	 * Override in child classes
	 *
	 * @param array  $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string
	 */
	abstract public function render_shortcode( $atts, $content = '' );

	/**
	 * Get template path with view support
	 *
	 * @param string $template_name Template filename.
	 * @param string $view View name (optional).
	 * @param string $subdir Subdirectory (optional, e.g., 'partials', 'shared').
	 * @return string|false Template path or false if not found.
	 */
	protected function get_template_path( $template_name, $view = '', $subdir = '' ) {
		// Check theme override first (only if WordPress is fully loaded)
		if ( function_exists( 'get_template_directory' ) ) {
			$theme_path = get_template_directory() . '/taw-theme/' . $this->module_id;
			if ( $subdir ) {
				$theme_path .= '/' . $subdir . '/' . $template_name;
			} elseif ( $view ) {
				$theme_path .= '/views/' . $view . '/' . $template_name;
			} else {
				$theme_path .= '/templates/' . $template_name;
			}
			if ( file_exists( $theme_path ) ) {
				return $theme_path;
			}
		}

		// Check plugin template
		$plugin_path = $this->module_dir . 'templates';
		if ( $subdir ) {
			$plugin_path .= '/' . $subdir . '/' . $template_name;
		} elseif ( $view ) {
			$plugin_path .= '/views/' . $view . '/' . $template_name;
		} else {
			$plugin_path .= '/' . $template_name;
		}
		if ( file_exists( $plugin_path ) ) {
			return $plugin_path;
		}

		return false;
	}

	/**
	 * Load template file
	 *
	 * @param string $template_name Template filename.
	 * @param array  $args Variables to pass to template.
	 * @param string $view View name (optional).
	 * @param string $subdir Subdirectory (optional, e.g., 'partials', 'shared').
	 * @return string Rendered template output.
	 */
	protected function load_template( $template_name, $args = array(), $view = '', $subdir = '' ) {
		$template_path = $this->get_template_path( $template_name, $view, $subdir );
		if ( ! $template_path ) {
			// Try shared template as fallback
			if ( ! $subdir && ! $view ) {
				$shared_path = $this->get_template_path( $template_name, '', 'shared' );
				if ( $shared_path ) {
					$template_path = $shared_path;
				}
			}
			if ( ! $template_path ) {
				return '';
			}
		}

		// Extract variables for template
		extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract

		ob_start();
		include $template_path;
		return ob_get_clean();
	}

	/**
	 * Get available views for this module
	 * Override in child classes to define available views
	 *
	 * @return array Array of view slugs => labels
	 */
	protected function get_available_views() {
		return array(
			'grid' => __( 'Grid', 'taw-theme' ),
			'list' => __( 'List', 'taw-theme' ),
		);
	}

	/**
	 * Validate view name
	 *
	 * @param string $view View name.
	 * @return string Validated view name or default.
	 */
	protected function validate_view( $view ) {
		$available_views = array_keys( $this->get_available_views() );
		if ( in_array( $view, $available_views, true ) ) {
			return $view;
		}
		// Return first available view as default
		return ! empty( $available_views ) ? $available_views[0] : 'grid';
	}
}

