<?php
/**
 * Core Logic for ZenAdmin.
 *
 * @package ZenAdmin
 */

namespace ZenAdmin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Core
 */
class Core {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Kill Switch: Emergency Disable (defined in wp-config.php)
		if ( defined( 'ZENADMIN_DISABLE' ) && ZENADMIN_DISABLE ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_head', array( $this, 'inject_styles' ), 999 );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 100 );
		add_action( 'wp_ajax_zenadmin_save_block', array( $this, 'ajax_save_block' ) );
		add_action( 'wp_ajax_zenadmin_delete_block', array( $this, 'ajax_delete_block' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_assets() {
		// Only load on backend
		if ( ! is_admin() ) {
			return;
		}

		// CSS
		wp_enqueue_style( 'zenadmin-styles', ZENADMIN_URL . 'assets/zen-styles.css', array(), ZENADMIN_VERSION );

		// JS
		wp_enqueue_script( 'zenadmin-modal', ZENADMIN_URL . 'assets/zen-modal.js', array(), ZENADMIN_VERSION, true );
		wp_enqueue_script( 'zenadmin-engine', ZENADMIN_URL . 'assets/zen-engine.js', array( 'zenadmin-modal' ), ZENADMIN_VERSION, true );

		// Localize Script
		wp_localize_script(
			'zenadmin-engine',
			'zenadminConfig',
			array(
				'nonce'     => wp_create_nonce( 'zenadmin_nonce' ),
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'whitelist' => $this->get_whitelist(),
				'safeMode'  => $this->is_safe_mode(),
				'blocked'   => get_option( 'zenadmin_blacklist', array() ),
				'i18n'      => array(
					'confirmTitle' => __( 'Block Element', 'zenadmin' ),
					'confirmBtn'   => __( 'Block Forever', 'zenadmin' ),
					'cancelBtn'    => __( 'Cancel', 'zenadmin' ),
					'labelLabel'   => __( 'Label (for your reference)', 'zenadmin' ),
					'sessionOnly'  => __( 'Hide for this session only', 'zenadmin' ),
				),
			)
		);
	}

	/**
	 * Inject blocking styles into admin head.
	 */
	public function inject_styles() {
		// Safety check: Safe Mode
		if ( $this->is_safe_mode() ) {
			return;
		}

		$blacklist = get_option( 'zenadmin_blacklist', array() );

		if ( empty( $blacklist ) ) {
			return;
		}

		$selectors = array_map(
			function ( $item ) {
				return $item['selector'];
			},
			$blacklist
		);

		// Group selectors
		$css = implode( ', ', $selectors ) . ' { display: none !important; }';

		echo '<style id="zenadmin-blocks">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Add toggle to Admin Bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public function admin_bar_menu( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Parent Node
		$wp_admin_bar->add_node(
			array(
				'id'    => 'zenadmin-parent',
				'title' => '<span class="ab-icon dashicons dashicons-visibility"></span> ' . __( 'ZenAdmin', 'zenadmin' ),
				'href'  => admin_url( 'admin.php?page=zenadmin' ),
			)
		);

		// Child Node: Toggle
		$wp_admin_bar->add_node(
			array(
				'parent' => 'zenadmin-parent',
				'id'     => 'zenadmin-toggle',
				'title'  => __( 'Toggle Zen Mode', 'zenadmin' ),
				'href'   => '#',
				'meta'   => array(
					'class'   => 'zenadmin-toggle-btn',
					'onclick' => 'return false;', // Handled by JS
				),
			)
		);

		// Child Node: Settings
		$wp_admin_bar->add_node(
			array(
				'parent' => 'zenadmin-parent',
				'id'     => 'zenadmin-settings',
				'title'  => __( 'Settings', 'zenadmin' ),
				'href'   => admin_url( 'options-general.php?page=zenadmin' ),
			)
		);

		// Child Node: Safe Mode
		$is_safe_mode = isset( $_GET['zenadmin_safe_mode'] ) && '1' === $_GET['zenadmin_safe_mode'];
		if ( ! $is_safe_mode ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => 'zenadmin-parent',
					'id'     => 'zenadmin-safe-mode',
					'title'  => __( 'Activate Safe Mode', 'zenadmin' ),
					'href'   => add_query_arg( 'zenadmin_safe_mode', '1' ),
					'meta'   => array( 'class' => 'zenadmin-danger-item' ),
				)
			);
		} else {
			$wp_admin_bar->add_node(
				array(
					'parent' => 'zenadmin-parent',
					'id'     => 'zenadmin-exit-safe-mode',
					'title'  => __( 'Exit Safe Mode', 'zenadmin' ),
					'href'   => remove_query_arg( 'zenadmin_safe_mode' ),
				)
			);
		}

		// Child Node: Clear Session
		$wp_admin_bar->add_node(
			array(
				'parent' => 'zenadmin-parent',
				'id'     => 'zenadmin-clear-session',
				'title'  => __( 'Clear Session Blocks', 'zenadmin' ),
				'href'   => '#',
			)
		);

		// Child Node: Reset All (Danger)
		$reset_url = wp_nonce_url( admin_url( 'admin-post.php?action=zenadmin_reset_all' ), 'zenadmin_reset_all' );
		$wp_admin_bar->add_node(
			array(
				'parent' => 'zenadmin-parent',
				'id'     => 'zenadmin-reset-all',
				'title'  => __( 'Reset All Settings', 'zenadmin' ),
				'href'   => $reset_url,
				'meta'   => array( 'class' => 'zenadmin-reset-item' ),
			)
		);
	}

	/**
	 * AJAX: Save a blocked selector.
	 */
	public function ajax_save_block() {
		check_ajax_referer( 'zenadmin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'zenadmin' ) ) );
		}

		$selector = isset( $_POST['selector'] ) ? sanitize_text_field( wp_unslash( $_POST['selector'] ) ) : '';
		$label    = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';

		if ( empty( $selector ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid selector', 'zenadmin' ) ) );
		}

		// Conflict Check
		$blacklist = get_option( 'zenadmin_blacklist', array() );
		
		// Limit Check (Hardening Section 4: Limit increased to 200)
		if ( count( $blacklist ) >= 200 ) {
			wp_send_json_error( array( 'message' => __( 'Limit reached (200). Please delete some blocks.', 'zenadmin' ) ) );
		}

		$hash      = hash( 'sha256', $selector . 'zenadmin' ); // Simple hash for ID

		if ( isset( $blacklist[ $hash ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Selector already blocked', 'zenadmin' ) ) );
		}

		$blacklist[ $hash ] = array(
			'selector'   => $selector,
			'label'      => $label ?: $selector,
			'created_at' => current_time( 'mysql' ),
			'user_id'    => get_current_user_id(),
		);

		update_option( 'zenadmin_blacklist', $blacklist );

		wp_send_json_success(
			array(
				'id'       => $hash,
				'selector' => $selector,
			)
		);
	}

	/**
	 * AJAX: Delete a blocked selector.
	 */
	public function ajax_delete_block() {
		// Not implemented in spec fully but needed for settings page management
		check_ajax_referer( 'zenadmin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'zenadmin' ) ) );
		}

		$hash      = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		$blacklist = get_option( 'zenadmin_blacklist', array() );

		if ( isset( $blacklist[ $hash ] ) ) {
			unset( $blacklist[ $hash ] );
			update_option( 'zenadmin_blacklist', $blacklist );
			wp_send_json_success();
		}

		wp_send_json_error( array( 'message' => __( 'Block not found', 'zenadmin' ) ) );
	}

	/**
	 * Check if Safe Mode is active.
	 *
	 * @return bool
	 */
	private function is_safe_mode() {
		return isset( $_GET['zenadmin_safe_mode'] ) && '1' === $_GET['zenadmin_safe_mode'];
	}

	/**
	 * Get whitelisted selectors that cannot be blocked.
	 *
	 * @return array
	 */
	private function get_whitelist() {
		return array(
			'#wpadminbar',
			'#wp-admin-bar-my-account',
			'#adminmenu',
			'#adminmenumain',
			'.toplevel_page_zenadmin',
			'#wpfooter',
			'#wpbody-content > .wrap',
			'.wrap > h1',
			'#wpbody',
			'.zenadmin-modal',
		);
	}
}
