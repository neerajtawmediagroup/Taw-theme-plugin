<?php
/**
 * TAW Theme Admin
 *
 * Handles all admin-related functionality including menu registration and dashboard
 *
 * @class TAW_Admin
 * @package TAW_Theme
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TAW_Admin Class
 *
 * Handles admin menu registration and dashboard rendering
 */
class TAW_Admin {

	/**
	 * Constructor
	 *
	 * Initializes admin functionality
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Only proceed if WordPress is loaded
		if ( ! defined( 'ABSPATH' ) ) {
			return;
		}

		if ( function_exists( 'add_action' ) ) {
			// Register admin menu.
			add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 5 );

			// Hide submenus so only the top-level TAW Theme link is visible.
			add_action( 'admin_menu', array( $this, 'cleanup_submenus' ), 99 );

			// Enqueue admin assets.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		}
	}

	/**
	 * Register main TAW Theme Builder admin menu
	 *
	 * @since 1.0.0
	 */
	public function register_admin_menu() {
		add_menu_page(
			__( 'TAW Theme', 'taw-theme' ),          // Page title
			__( 'TAW Theme', 'taw-theme' ),          // Menu text in sidebar
			'read',                                  // Capability (any logged-in user)
			'taw-theme-builder',                     // slug
			array( $this, 'render_dashboard_page' ), // callback
			'dashicons-admin-generic',
			3
		);

		// Add submenu for full listings page
		add_submenu_page(
			'taw-theme-builder',
			__( 'All Listings', 'taw-theme' ),
			__( 'All Listings', 'taw-theme' ),
			'read',
			'taw-properties-list',
			array( $this, 'render_properties_list_page' )
		);

		// Add submenu for Manage View (shortcode views) page.
		add_submenu_page(
			'taw-theme-builder',
			__( 'Manage View', 'taw-theme' ),
			__( 'Manage View', 'taw-theme' ),
			'read',
			'taw-views-list',
			array( $this, 'render_views_list_page' )
		);

		// Add submenu for Create New Shortcode page.
		add_submenu_page(
			'taw-theme-builder',
			__( 'Add New Shortcode', 'taw-theme' ),
			__( 'Add New Shortcode', 'taw-theme' ),
			'read',
			'taw-shortcode-add',
			array( $this, 'render_shortcode_add_page' )
		);

		// Add submenu for add property page - custom TAW screen.
		add_submenu_page(
			'taw-theme-builder',
			__( 'Add New Property', 'taw-theme' ),
			__( 'Add New Property', 'taw-theme' ),
			'read',
			'taw-property-add',
			array( $this, 'render_add_property_page' )
		);
	}

	/**
	 * Render the dashboard page
	 *
	 * @since 1.0.0
	 */
	public function render_dashboard_page() {
		// Check user capabilities
		if ( ! current_user_can( 'read' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'taw-theme' ) );
		}

		// Template path
		$template = TAW_MODULES_DIR . 'templates/taw-theme-dashboard.php';

		// Output wrapper
		echo '<div class="wrap">';

		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<h1>' . esc_html__( 'TAW Theme', 'taw-theme' ) . '</h1>';
			echo '<p>' . esc_html__( 'Dashboard template not found.', 'taw-theme' ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Render the full properties list page
	 *
	 * @since 1.0.0
	 */
	public function render_properties_list_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'read' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'taw-theme' ) );
		}

		$template = TAW_MODULES_DIR . 'classes/modules/realestate/templates/admin/taw-properties-list.php';

		echo '<div class="wrap">';

		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<h1>' . esc_html__( 'All Properties', 'taw-theme' ) . '</h1>';
			echo '<p>' . esc_html__( 'Listings template not found.', 'taw-theme' ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Render the views (shortcodes) list page.
	 *
	 * @since 1.0.0
	 */
	public function render_views_list_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'read' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'taw-theme' ) );
		}

		$template = TAW_MODULES_DIR . 'classes/modules/realestate/templates/admin/taw-views-list.php';

		echo '<div class="wrap">';

		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<h1>' . esc_html__( 'Manage View', 'taw-theme' ) . '</h1>';
			echo '<p>' . esc_html__( 'Views template not found.', 'taw-theme' ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Render the add property page (custom TAW UI).
	 *
	 * @since 1.0.0
	 */
	public function render_add_property_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'read' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'taw-theme' ) );
		}

		$template = TAW_MODULES_DIR . 'classes/modules/realestate/templates/admin/taw-property-add.php';

		echo '<div class="wrap">';

		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<h1>' . esc_html__( 'Add Property', 'taw-theme' ) . '</h1>';
			echo '<p>' . esc_html__( 'Add Property template not found.', 'taw-theme' ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Enqueue admin CSS for TAW Theme dashboard page
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our plugin pages.
		if (
			false === strpos( $hook, 'taw-theme-builder' ) &&
			false === strpos( $hook, 'taw-properties-list' ) &&
			false === strpos( $hook, 'taw-property-add' ) &&
			false === strpos( $hook, 'taw-views-list' ) &&
			false === strpos( $hook, 'taw-shortcode-add' )
		) {
			return;
		}

		// URL to plugin root
		$plugin_url = TAW_MODULES_URL;

		wp_enqueue_style(
			'taw-theme-admin',
			$plugin_url . 'assets/css/taw-admin.css',
			array(),
			TAW_MODULES_VERSION
		);
	}

	/**
	 * Render the custom "Create New Shortcode" page.
	 *
	 * @since 1.0.0
	 */
	public function render_shortcode_add_page() {
		if ( ! current_user_can( 'read' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'taw-theme' ) );
		}

		$template = TAW_MODULES_DIR . 'classes/modules/realestate/templates/admin/taw-shortcode-add.php';

		echo '<div class="wrap">';

		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<h1>' . esc_html__( 'Create New Shortcode', 'taw-theme' ) . '</h1>';
			echo '<p>' . esc_html__( 'Shortcode template not found.', 'taw-theme' ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Cleanup duplicate/auto-added submenus.
	 *
	 * IMPORTANT:
	 * We keep our custom pages (taw-properties-list, taw-property-add, etc.)
	 * registered in the submenu so WordPress' permission system still
	 * recognizes them as valid pages. Removing them here would cause
	 * "Sorry, you are not allowed to access this page." even for admins.
	 */
	public function cleanup_submenus() {
		// Only remove the automatic self-link submenu to avoid duplicates.
		remove_submenu_page( 'taw-theme-builder', 'taw-theme-builder' );
		// Leave other custom submenus intact for correct access checks.
	}
}



