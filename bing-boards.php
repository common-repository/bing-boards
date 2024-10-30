<?php
/*
Plugin Name: Bing Boards
Description: Create and submit Bing Boards directly from your WordPress site.
Version: 1.0.2
Author: Modern Tribe, Inc.
Author URI: http://m.tri.be/1y
Text Domain: bing-boards
Domain Path: /languages/
License: GPLv2 or later
*/

defined( 'ABSPATH' ) OR exit;

spl_autoload_register( 'bing_boards_autoloader' );
add_action( 'plugins_loaded', 'bing_boards_init' );

/**
 *  Load the translations file and init the plugin
 */
function bing_boards_init() {
	load_plugin_textdomain( 'bing-boards', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	bing_boards();
}

/**
 * Helper function that returns the singleton instance of the Bing_Boards class.
 */
function bing_boards( Bing_Boards_Container $container = null ) {
	return Bing_Boards::instance( $container );
}

/**
 * Callback for the spl_autoload
 * @param $class string
 */
function bing_boards_autoloader( $class ) {

	// Bail if it's not one of our classes.
	if ( ! strncmp( $class, "Bing", strlen( $class ) ) )
		return;

	// Include the PHP file with the class or interface definition
	$class              = str_replace( '_', '-', $class );
	$class              = strtolower( $class );
	$path_for_class     = untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/classes/' . $class . '.class.php';
	$path_for_interface = untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/interfaces/' . $class . '.interface.php';

	if ( file_exists( $path_for_class ) )
		include_once $path_for_class;
	elseif ( file_exists( $path_for_interface ) )
		include_once $path_for_interface;
}
