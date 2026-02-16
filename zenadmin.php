<?php
/**
 * Plugin Name: ZenAdmin
 * Description: Clean up your WordPress admin interface by hiding annoying elements with a click. Enhanced Edition.
 * Version: 1.0.0
 * Author: Rahajason
 * Text Domain: zenadmin
 * Domain Path: /languages
 * License: GPLv2 or later
 * CLI Command: zenadmin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Global Constants
define( 'ZENADMIN_VERSION', '1.1.1' );
define( 'ZENADMIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ZENADMIN_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'ZENADMIN_DEBUG' ) ) {
	define( 'ZENADMIN_DEBUG', false );
}

// Define main file for hooks
if ( ! defined( 'ZENADMIN_FILE' ) ) {
	define( 'ZENADMIN_FILE', __FILE__ );
}

// Autoloader (Primitive but effective since we have a fixed structure)
spl_autoload_register(
	function ( $class_name ) {
		$prefix   = 'ZenAdmin\\';
		$base_dir = ZENADMIN_PATH . 'includes/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class_name, $len );
		// Replace namespace separators with hyphens, and underscores with hyphens
		$file = $base_dir . 'class-' . strtolower( str_replace( array( '\\', '_' ), '-', $relative_class ) ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

// Require Main Class
require_once ZENADMIN_PATH . 'includes/class-zenadmin.php';

/**
 * Returns the main instance of ZenAdmin.
 *
 * @return ZenAdmin
 */
function zenadmin() {
	return ZenAdmin::get_instance();
}

// Kickoff
zenadmin();
