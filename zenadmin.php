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
define( 'ZENADMIN_VERSION', '1.0.0' );
define( 'ZENADMIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ZENADMIN_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'ZENADMIN_DEBUG' ) ) {
	define( 'ZENADMIN_DEBUG', false );
}

// Autoloader (Primitive but effective since we have a fixed structure)
spl_autoload_register(
	function ( $class ) {
		$prefix   = 'ZenAdmin\\';
		$base_dir = ZENADMIN_PATH . 'includes/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );
		// Replace namespace separators with hyphens, and underscores with hyphens
		$file = $base_dir . 'class-' . strtolower( str_replace( array( '\\', '_' ), '-', $relative_class ) ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

/**
 * Main Plugin Class
 *
 * @since 1.0.0
 */
final class ZenAdmin {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Get the instance of the class.
	 *
	 * @return object
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof ZenAdmin ) ) {
			self::$instance = new ZenAdmin();
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Setup plugin constants.
	 */
	private function setup_constants() {
		// Already defined globally for simplicity in templates
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		// Autoload handled by SPL
	}

	/**
	 * Setup hooks.
	 */
	private function hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Initialize the plugin.
	 */
	public function init() {
		// Load text domain
		load_plugin_textdomain( 'zenadmin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// Initialize Core Logic
		if ( class_exists( 'ZenAdmin\\Core' ) ) {
			new \ZenAdmin\Core();
		}

		// Initialize Settings
		if ( is_admin() && class_exists( 'ZenAdmin\\Settings' ) ) {
			new \ZenAdmin\Settings();
		}

		// Initialize White Label (Pro)
		if ( class_exists( 'ZenAdmin\\White_Label' ) ) {
			$white_label = new \ZenAdmin\White_Label();
			$white_label->init();
		}
	}

	/**
	 * Activation hook.
	 */
	public function activate() {
		// Set default options if not exists
		if ( false === get_option( 'zenadmin_blacklist' ) ) {
			add_option( 'zenadmin_blacklist', array() );
		}

		// Schema version
		if ( false === get_option( 'zenadmin_schema_version' ) ) {
			add_option( 'zenadmin_schema_version', '1.0.0' );
		}
	}

	/**
	 * Deactivation hook.
	 */
	public function deactivate() {
		// Flush rewrite rules if needed, or cleanup temporary data
	}
}

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
