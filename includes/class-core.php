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

		// Portability Engine (Import/Export)
		require_once plugin_dir_path( __FILE__ ) . 'class-portability.php';
		$portability = new \ZenAdmin_Portability();
		$portability->init();

		add_action( 'admin_init', array( $this, 'enforce_hard_blocks' ) ); // Hard Blocking Enforcement
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_head', array( $this, 'inject_styles' ), 999 );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 100 );
		add_action( 'wp_ajax_zenadmin_save_block', array( $this, 'ajax_save_block' ) );
		add_action( 'wp_ajax_zenadmin_delete_block', array( $this, 'ajax_delete_block' ) );
		add_action( 'wp_ajax_zenadmin_update_block_roles', array( $this, 'ajax_update_block_roles' ) );
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
		global $wp_roles;
		$roles_list = wp_list_pluck( $wp_roles->roles, 'name' );

		wp_localize_script(
			'zenadmin-engine',
			'zenadminConfig',
			array(
				'nonce'     => wp_create_nonce( 'zenadmin_nonce' ),
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'whitelist' => $this->get_whitelist(),
				'safeMode'  => $this->is_safe_mode(),
				'blocked'   => get_option( 'zenadmin_blacklist', array() ),
				'roles'     => $roles_list,
				'i18n'      => array(
					'confirmTitle' => __( 'Block Element', 'zenadmin' ),
					'confirmBtn'   => __( 'Block Forever', 'zenadmin' ),
					'cancelBtn'    => __( 'Cancel', 'zenadmin' ),
					'labelLabel'   => __( 'Label (for your reference)', 'zenadmin' ),
					'sessionOnly'  => __( 'Hide for this session only', 'zenadmin' ),
					'hiddenFor'    => __( 'Hide for roles:', 'zenadmin' ),
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

		// Role-Based Visibility Engine
		$user       = wp_get_current_user();
		$user_roles = (array) $user->roles;
		$selectors  = array();

		foreach ( $blacklist as $entry ) {
			// If no hidden_for defined (legacy), treat as global (hide for all)
			if ( ! isset( $entry['hidden_for'] ) || empty( $entry['hidden_for'] ) ) {
				$selectors[] = $entry['selector'];
				continue;
			}

			// Check if user has any role in the hidden_for list
			if ( array_intersect( $user_roles, $entry['hidden_for'] ) ) {
				$selectors[] = $entry['selector'];
			}
		}

		if ( empty( $selectors ) ) {
			return;
		}

		// Group selectors - aggressive CSS to prevent gaps
		$hide_css = 'display: none !important; visibility: hidden !important; height: 0 !important; min-height: 0 !important; max-height: 0 !important; margin: 0 !important; padding: 0 !important; overflow: hidden !important;';
		
		$css = implode( ', ', $selectors ) . ' { ' . $hide_css . ' }';

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
		
		// Role-Based Visibility Engine: Parse hidden_for array
		$hidden_for_raw = isset( $_POST['hidden_for'] ) ? sanitize_text_field( wp_unslash( $_POST['hidden_for'] ) ) : '[]';
		$hidden_for     = json_decode( $hidden_for_raw, true );
		if ( ! is_array( $hidden_for ) ) {
			$hidden_for = array();
		}
		// Sanitize role slugs
		$hidden_for = array_map( 'sanitize_key', $hidden_for );

		// Hard Block Fields
		$target_url = isset( $_POST['target_url'] ) ? sanitize_text_field( wp_unslash( $_POST['target_url'] ) ) : '';
		$hard_block = isset( $_POST['hard_block'] ) && '1' === $_POST['hard_block'];

		if ( empty( $selector ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid selector', 'zenadmin' ) ) );
		}

		// Conflict Check
		$blacklist = get_option( 'zenadmin_blacklist', array() );
		
		// Ensure $blacklist is always an array (fix for PHP 8+ count() TypeError)
		if ( ! is_array( $blacklist ) ) {
			$blacklist = array();
		}
		
		// Limit Check (Hardening Section 4: Limit increased to 200)
		if ( count( $blacklist ) >= 200 ) {
			wp_send_json_error( array( 'message' => __( 'Limit reached (200). Please delete some blocks.', 'zenadmin' ) ) );
		}

		// Store with Hash for uniqueness
		$hash = md5( $selector );
		
		if ( isset( $blacklist[ $hash ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Selector already blocked', 'zenadmin' ) ) );
		}

		$blacklist[ $hash ] = array(
			'selector'   => $selector,
			'label'      => $label ?: $selector,
			'hidden_for' => $hidden_for,
			'target_url' => $target_url,   // URL to hard block
			'hard_block' => $hard_block,   // Boolean flag
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
	 * AJAX: Update roles for an existing block.
	 */
	public function ajax_update_block_roles() {
		check_ajax_referer( 'zenadmin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'zenadmin' ) ) );
		}

		$hash = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		
		// Parse hidden_for array
		$hidden_for_raw = isset( $_POST['hidden_for'] ) ? sanitize_text_field( wp_unslash( $_POST['hidden_for'] ) ) : '[]';
		$hidden_for     = json_decode( $hidden_for_raw, true );
		if ( ! is_array( $hidden_for ) ) {
			$hidden_for = array();
		}
		$hidden_for = array_map( 'sanitize_key', $hidden_for );

		if ( empty( $hash ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid block ID', 'zenadmin' ) ) );
		}

		$blacklist = get_option( 'zenadmin_blacklist', array() );

		if ( ! isset( $blacklist[ $hash ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Block not found', 'zenadmin' ) ) );
		}

		$blacklist[ $hash ]['hidden_for'] = $hidden_for;
		update_option( 'zenadmin_blacklist', $blacklist );

		wp_send_json_success( array( 'message' => __( 'Roles updated', 'zenadmin' ) ) );
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

	/**
	 * Enforce Hard Blocking on restricted URLs.
	 */
	public function enforce_hard_blocks() {
		// 1. Safety & Exclusions
		if ( wp_doing_ajax() || defined( 'DOING_AUTOSAVE' ) || $this->is_safe_mode() ) {
			return;
		}

		if ( ! is_admin() ) {
			return;
		}

		$current_uri = $_SERVER['REQUEST_URI'];
		
		// Anti-Lockout Whitelist (Hardcoded)
		// Prevent blocking ZenAdmin settings or Dashboard index
		if ( strpos( $current_uri, 'page=zenadmin' ) !== false || 
			 preg_match( '/wp-admin\/index\.php$/', $current_uri ) ||
			 preg_match( '/wp-admin\/$/', $current_uri ) ) {
			return;
		}

		// 2. Get Rules
		$blacklist = get_option( 'zenadmin_blacklist', array() );
		if ( empty( $blacklist ) ) {
			return;
		}

		$user = wp_get_current_user();
		$user_roles = (array) $user->roles;

		// 3. Check for Hard Blocks
		foreach ( $blacklist as $entry ) {
			// Must be marked as Hard Block
			if ( empty( $entry['hard_block'] ) || empty( $entry['target_url'] ) ) {
				continue;
			}

			// Check Role Visibility (if defined)
			// Example: If blocked for 'editor', and user is 'editor', then Block.
			// Logic matches frontend: if user_roles intersects with hidden_for, it is hidden.
			if ( isset( $entry['hidden_for'] ) && is_array( $entry['hidden_for'] ) && ! empty( $entry['hidden_for'] ) ) {
				if ( ! array_intersect( $user_roles, $entry['hidden_for'] ) ) {
					// User does NOT have a blocked role -> Skip
					continue;
				}
			}

			// Match URL
			// Decode HTML entities just in case
			$blocked_url = html_entity_decode( $entry['target_url'] );

			// Basic loose matching: if current URI contains the blocked relative path
			// This covers: /wp-admin/options-general.php matches options-general.php
			if ( strpos( $current_uri, $blocked_url ) !== false ) {
				
				// Block Access
				wp_safe_redirect( admin_url( 'index.php?zenadmin_blocked=1' ) );
				exit;
			}
		}
	}
}
