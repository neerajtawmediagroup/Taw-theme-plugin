<?php
/**
 * TAW Helper Functions
 *
 * Helper functions for TAW Theme
 *
 * @package TAW_Theme
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper function to wrap WordPress translation function for linter compatibility
 *
 * @param string $text Text to translate
 * @param string $domain Text domain
 * @return string Translated text
 */
if ( ! function_exists( 'taw__' ) ) {
	function taw__( $text, $domain ) {
		return function_exists( '__' ) ? __( $text, $domain ) : $text;
	}
}

/**
 * Get all registered post types for use in module settings
 *
 * Only returns 'post' (Posts) and custom post types, excluding pages and templates.
 * This function filters out built-in WordPress post types that shouldn't be displayed
 * in the module settings dropdown.
 *
 * @since 1.0.0
 * @return array Array of post type slugs => labels, with 'post' appearing first
 */
if ( ! function_exists( 'taw_get_registered_post_types' ) ) {
	function taw_get_registered_post_types() {
		// Return empty array if called too early
		if ( ! function_exists( 'get_post_types' ) ) {
			return array();
		}

		try {
			// Get all public post types
			$post_types = get_post_types( array( 'public' => true ), 'objects' );

			// Return default if no post types found
			if ( empty( $post_types ) ) {
				return array( 'post' => taw__( 'Posts', 'taw-theme' ) );
			}

			$options = array();

			// Post types to exclude from the dropdown
			$excluded_types = array(
				'attachment',      // Media attachments
				'revision',        // Post revisions
				'nav_menu_item',   // Navigation menu items
				'page',            // Pages (excluded per requirements)
			);

			// Built-in WordPress core post types
			$built_in_types = array( 'post', 'page', 'attachment', 'revision', 'nav_menu_item' );

			// Loop through all post types and filter
			foreach ( $post_types as $post_type ) {
				// Skip if post_type object is invalid
				if ( ! is_object( $post_type ) || ! isset( $post_type->name ) || ! isset( $post_type->label ) ) {
					continue;
				}

				// Skip excluded post types
				if ( in_array( $post_type->name, $excluded_types, true ) ) {
					continue;
				}

				// Only include 'post' (Posts) and custom post types
				// Custom post types are those that are not built-in WordPress core types
				if ( 'post' === $post_type->name || ! in_array( $post_type->name, $built_in_types, true ) ) {
					$options[ $post_type->name ] = $post_type->label;
				}
			}

			// Return default if no valid options found
			if ( empty( $options ) ) {
				return array( 'post' => taw__( 'Posts', 'taw-theme' ) );
			}

			// Sort by label, but put 'post' first for better UX
			$post_option   = array();
			$other_options = array();

			foreach ( $options as $key => $label ) {
				if ( 'post' === $key ) {
					$post_option[ $key ] = $label;
				} else {
					$other_options[ $key ] = $label;
				}
			}

			// Sort custom post types alphabetically
			asort( $other_options );

			// Merge with 'post' first, then custom post types
			$options = array_merge( $post_option, $other_options );

			return $options;
		} catch ( Exception $e ) {
			// Return default on any error
			return array( 'post' => taw__( 'Posts', 'taw-theme' ) );
		}
	}
}

/**
 * Get post types options for module settings
 *
 * Helper function to safely get post types for use in module field options.
 * This is called during module registration, so it needs to be safe.
 *
 * @since 1.0.0
 * @return array Array of post type slugs => labels
 */
if ( ! function_exists( 'taw_get_post_types_for_modules' ) ) {
	function taw_get_post_types_for_modules() {
		static $cached_types = null;

		// Return cached value if available
		if ( null !== $cached_types ) {
			return $cached_types;
		}

		// Try to get post types
		if ( function_exists( 'taw_get_registered_post_types' ) ) {
			$types = taw_get_registered_post_types();
			if ( is_array( $types ) && ! empty( $types ) ) {
				$cached_types = $types;
				return $cached_types;
			}
		}

		// Return default
		$cached_types = array( 'post' => taw__( 'Posts', 'taw-theme' ) );
		return $cached_types;
	}
}

