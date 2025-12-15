<?php
/**
 * Loader for Testimonials module
 *
 * @package TAW_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the module class file.
require_once __DIR__ . '/class-taw-testimonial-module.php';

// Register this module with the TAW module manager.
if ( class_exists( 'TAW_Module_Manager' ) && class_exists( 'TAW_Testimonial_Module' ) ) {

	// Module ID must match folder name if your base class uses it for paths.
	TAW_Module_Manager::register_module(
		'Testimonials',                  // module_id (matches folder name)
		'TAW_Testimonial_Module',        // class name
		__( 'TAW Testimonials', 'taw-theme' ) // display name
	);
}