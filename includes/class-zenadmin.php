<?php
/**
 * Main Plugin Class
 *
 * @package ZenAdmin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		register_activation_hook( ZENADMIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( ZENADMIN_FILE, array( $this, 'deactivate' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ZENADMIN_FILE ), array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Initialize the plugin.
	 */
	public function init() {
		// Load text domain
		load_plugin_textdomain( 'zenadmin', false, dirname( plugin_basename( ZENADMIN_FILE ) ) . '/languages' );

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

	/**
	 * Add specific links to the plugin action links.
	 *
	 * @param array $links Plugin action links.
	 * @return array Modified links.
	 */
	public function plugin_action_links( $links ) {
		$settings_link = '<a href="' . admin_url( 'options-general.php?page=zenadmin' ) . '">' . __( 'Settings', 'zenadmin' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}
