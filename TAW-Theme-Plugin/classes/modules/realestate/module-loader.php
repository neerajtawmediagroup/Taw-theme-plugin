<?php
/**
 * Real Estate Module Loader
 *
 * This file registers the Real Estate module with the module manager
 *
 * @package TAW_Theme
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure required classes are loaded.
if ( ! class_exists( 'TAW_Module_Base' ) ) {
	// Try to load base class if it exists
	if ( defined( 'TAW_MODULES_DIR' ) && file_exists( TAW_MODULES_DIR . 'classes/class-taw-module-base.php' ) ) {
		require_once TAW_MODULES_DIR . 'classes/class-taw-module-base.php';
	}
	// If still doesn't exist, abort
	if ( ! class_exists( 'TAW_Module_Base' ) ) {
		return;
	}
}

if ( ! class_exists( 'TAW_Module_Manager' ) ) {
	return;
}

// Load the module class only if base class exists.
if ( ! class_exists( 'TAW_Module_RealEstate' ) && class_exists( 'TAW_Module_Base' ) ) {
	$module_file = __DIR__ . '/class-taw-module-realestate.php';
	if ( file_exists( $module_file ) ) {
		require_once $module_file;
	}
}

// Register the Real Estate module only if class was successfully loaded.
if ( class_exists( 'TAW_Module_RealEstate' ) ) {
	TAW_Module_Manager::register_module(
		'realestate',
		'TAW_Module_RealEstate',
		'Real Estate'
	);
}

