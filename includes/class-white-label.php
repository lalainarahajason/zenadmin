<?php
/**
 * ZenAdmin White Label Module
 *
 * @package ZenAdmin
 */

namespace ZenAdmin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class White_Label {

	/**
	 * Initialize the class.
	 */
	public function init() {
		// Safety Switch: if ZENADMIN_WHITE_LABEL is strictly false, disable everything.
		if ( defined( 'ZENADMIN_WHITE_LABEL' ) && false === ZENADMIN_WHITE_LABEL ) {
			return;
		}

		// 1. Identity & Rebranding
		add_filter( 'all_plugins', array( $this, 'rebrand_plugin_meta' ) );
		add_action( 'admin_menu', array( $this, 'rebrand_menu_icon' ), 99 );

		// 2. Login Customizer
		add_action( 'login_enqueue_scripts', array( $this, 'customize_login_page' ) );
		add_filter( 'login_headerurl', array( $this, 'custom_login_header_url' ) );
		add_filter( 'login_headertext', array( $this, 'custom_login_header_text' ) );

		// 3. Admin & Dashboard
		add_action( 'admin_menu', array( $this, 'rename_menu_items' ), 99 );
		add_filter( 'admin_footer_text', array( $this, 'customize_admin_footer' ) );
		add_filter( 'update_footer', array( $this, 'hide_wp_version' ), 11 );
		add_action( 'wp_before_admin_bar_render', array( $this, 'clean_admin_bar' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'customize_dashboard' ), 99 );
		add_action( 'admin_init', array( $this, 'hide_updates' ) );

		// 4. Global Hard Blocking
		add_action( 'admin_init', array( $this, 'enforce_global_hard_blocks' ) );
		add_action( 'admin_menu', array( $this, 'hide_hard_blocked_menus' ), 999 );

		// 5. Admin Color Scheme
		add_action( 'admin_head', array( $this, 'inject_admin_colors' ) );
	}

	/**
	 * Rebrand Plugin Metadata (Stealth Mode & Renaming).
	 *
	 * @param array $all_plugins List of installed plugins.
	 * @return array Modified list.
	 */
	public function rebrand_plugin_meta( $all_plugins ) {
		$options = get_option( 'zenadmin_white_label', array() );

		if ( empty( $options['enabled'] ) ) {
			return $all_plugins;
		}

		// Find our plugin
		$plugin_key = 'zenadmin/zenadmin.php'; // Adjust if folder name differs
		if ( ! isset( $all_plugins[ $plugin_key ] ) ) {
			// Fallback search
			foreach ( $all_plugins as $key => $data ) {
				if ( 'ZenAdmin' === $data['Name'] ) {
					$plugin_key = $key;
					break;
				}
			}
		}

		if ( isset( $all_plugins[ $plugin_key ] ) ) {
			// Stealth Mode
			if ( ! empty( $options['stealth_mode'] ) ) {
				if ( ! current_user_can( 'manage_network' ) && ! ( defined( 'ZENADMIN_DEBUG' ) && ZENADMIN_DEBUG ) ) {
					unset( $all_plugins[ $plugin_key ] );
					return $all_plugins;
				}
			}

			// Rebrand Name
			// Prioritize wl_plugin_name, fallback to agency_name for backward compat
			$new_name = ! empty( $options['wl_plugin_name'] ) ? $options['wl_plugin_name'] : ( ! empty( $options['agency_name'] ) ? $options['agency_name'] . ' (System)' : '' );
			
			if ( $new_name ) {
				$all_plugins[ $plugin_key ]['Name']   = $new_name;
				$all_plugins[ $plugin_key ]['Title']  = $new_name; // Some contexts use Title
				$all_plugins[ $plugin_key ]['Author'] = ! empty( $options['agency_name'] ) ? $options['agency_name'] : 'System';
			}
			
			if ( ! empty( $options['agency_url'] ) ) {
				$all_plugins[ $plugin_key ]['PluginURI'] = $options['agency_url'];
				$all_plugins[ $plugin_key ]['AuthorURI'] = $options['agency_url'];
			}
			
			// Rebrand Description
			if ( ! empty( $options['wl_plugin_desc'] ) ) {
				$all_plugins[ $plugin_key ]['Description'] = esc_html( $options['wl_plugin_desc'] );
			} elseif ( ! empty( $options['agency_name'] ) ) {
				$all_plugins[ $plugin_key ]['Description'] = __( 'Essential system utilities.', 'zenadmin' );
			}
		}

		return $all_plugins;
	}

	/**
	 * Rebrand Menu Icon.
	 */
	public function rebrand_menu_icon() {
		$options = get_option( 'zenadmin_white_label', array() );
		if ( empty( $options['enabled'] ) || empty( $options['wl_menu_icon'] ) ) {
			return;
		}

		global $menu;
		foreach ( $menu as $key => $item ) {
			if ( 'zenadmin' === $item[2] ) {
				$menu[ $key ][6] = $options['wl_menu_icon']; // Index 6 is icon_url/class
				break;
			}
		}
	}

	/**
	 * Rename Menu Items.
	 */
	public function rename_menu_items() {
		global $submenu;
		$options = get_option( 'zenadmin_white_label', array() );
		
		if ( empty( $options['enabled'] ) ) {
			return;
		}

		// Rename main menu item
		global $menu;
		// Prioritize wl_plugin_name, fallback to agency_name
		$new_name = ! empty( $options['wl_plugin_name'] ) ? $options['wl_plugin_name'] : ( ! empty( $options['agency_name'] ) ? $options['agency_name'] : '' );

		if ( $new_name ) {
			foreach ( $menu as $key => $item ) {
				if ( 'zenadmin' === $item[2] ) {
					$menu[ $key ][0] = $new_name;
					break;
				}
			}
		}

		// Rename Settings > ZenAdmin
		if ( isset( $submenu['options-general.php'] ) ) {
			foreach ( $submenu['options-general.php'] as $key => $item ) {
				if ( 'zenadmin' === $item[2] ) {
					$submenu['options-general.php'][ $key ][0] = $new_name;
					$submenu['options-general.php'][ $key ][3] = $new_name; // Page title
					break;
				}
			}
		}
	}

	/**
	 * Customize Login Page (Logo, Colors).
	 */
	public function customize_login_page() {
		$options = get_option( 'zenadmin_white_label', array() );
		if ( empty( $options['enabled'] ) ) {
			return;
		}

		$css = '';

		// Custom Logo
		// Check new field wl_login_logo_url first, then fallback to login_logo (which is the image URL)
		// Wait, settings field naming: 'login_logo' is the IMAGE URL. 'wl_login_logo_url' is the LINK URL.
		if ( ! empty( $options['login_logo'] ) ) {
			$logo_url = esc_url( $options['login_logo'] );
			$css .= "
				body.login div#login h1 a {
					background-image: url('{$logo_url}');
					background-size: contain;
					background-repeat: no-repeat;
					background-position: center;
					width: 100%;
					height: 80px; /* Default WP is 84px */
					margin-bottom: 20px;
				}
			";
		}

		// Background Color
		if ( ! empty( $options['wl_login_bg_color'] ) ) {
			$bg_color = sanitize_hex_color( $options['wl_login_bg_color'] );
			if ( $bg_color ) {
				$css .= "body.login { background-color: {$bg_color}; }";
			}
		}

		// Button Color
		if ( ! empty( $options['wl_login_btn_color'] ) ) {
			$btn_color = sanitize_hex_color( $options['wl_login_btn_color'] );
			if ( $btn_color ) {
				$css .= "
					body.login .button-primary {
						background-color: {$btn_color} !important;
						border-color: {$btn_color} !important;
						box-shadow: none !important;
						text-shadow: none !important;
					}
					body.login .button-primary:hover,
					body.login .button-primary:focus {
						background-color: {$btn_color} !important;
						border-color: {$btn_color} !important;
						filter: brightness(0.9);
					}
				";
			}
		}

		if ( $css ) {
			echo '<style type="text/css">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Custom Login Header URL.
	 */
	public function custom_login_header_url( $url ) {
		$options = get_option( 'zenadmin_white_label', array() );
		if ( empty( $options['enabled'] ) ) {
			return $url;
		}
		// Prefer specific logo link, fallback to agency URL
		if ( ! empty( $options['wl_login_logo_url'] ) ) {
			return esc_url( $options['wl_login_logo_url'] );
		}
		if ( ! empty( $options['agency_url'] ) ) {
			return esc_url( $options['agency_url'] );
		}
		return $url;
	}

	/**
	 * Custom Login Header Text.
	 */
	public function custom_login_header_text( $text ) {
		$options = get_option( 'zenadmin_white_label', array() );
		if ( empty( $options['enabled'] ) ) {
			return $text;
		}
		if ( ! empty( $options['agency_name'] ) ) {
			return esc_html( $options['agency_name'] );
		}
		return $text;
	}

	/**
	 * Customize Admin Footer Text.
	 */
	public function customize_admin_footer( $text ) {
		$options = get_option( 'zenadmin_white_label', array() );
		if ( empty( $options['enabled'] ) ) {
			return $text;
		}
		if ( ! empty( $options['footer_text'] ) ) {
			return wp_kses_post( $options['footer_text'] );
		}
		return $text;
	}

	/**
	 * Hide WP Version.
	 */
	public function hide_wp_version( $version ) {
		$options = get_option( 'zenadmin_white_label', array() );
		if ( empty( $options['enabled'] ) || empty( $options['hide_version'] ) ) {
			return $version;
		}
		return '';
	}

	/**
	 * Clean Admin Bar (WP Logo & ZenAdmin Node).
	 */
	public function clean_admin_bar() {
		global $wp_admin_bar;
		$options = get_option( 'zenadmin_white_label', array() );
		
		if ( empty( $options['enabled'] ) ) {
			return;
		}

		// Rebrand ZenAdmin Node
		// Name priority: wl_plugin_name > agency_name
		$name = ! empty( $options['wl_plugin_name'] ) ? $options['wl_plugin_name'] : ( ! empty( $options['agency_name'] ) ? $options['agency_name'] : '' );
		
		if ( $name ) {
			$node = $wp_admin_bar->get_node( 'zenadmin-parent' );
			if ( $node ) {
				// Check if we have a custom icon (if it's a dashicon class)
				$icon = '<span class="ab-icon dashicons dashicons-visibility"></span> ';
				if ( ! empty( $options['wl_menu_icon'] ) && strpos( $options['wl_menu_icon'], 'dashicons-' ) === 0 ) {
					$icon = '<span class="ab-icon dashicons ' . esc_attr( $options['wl_menu_icon'] ) . '"></span> ';
				}
				
				$node->title = $icon . esc_html( $name );
				$wp_admin_bar->add_node( $node );
			}
		}

		// Hide WP Logo
		if ( ! empty( $options['wl_hide_wp_logo'] ) ) {
			$wp_admin_bar->remove_menu( 'wp-logo' );
			$wp_admin_bar->remove_menu( 'about' );
			$wp_admin_bar->remove_menu( 'wporg' );
			$wp_admin_bar->remove_menu( 'documentation' );
			$wp_admin_bar->remove_menu( 'support-forums' );
			$wp_admin_bar->remove_menu( 'feedback' );
		}
	}

	/**
	 * Hide Updates (Core, Plugins, Themes).
	 */
	public function hide_updates() {
		$options = get_option( 'zenadmin_white_label', array() );
		if ( empty( $options['enabled'] ) || empty( $options['wl_hide_updates'] ) ) {
			return;
		}

		// Only hide for non-super-admins if desired, or everyone?
		// Spec implies "clean up the interface", usually for clients.
		// But let's apply globally if enabled, or maybe exclude Super Admin?
		// For now, consistent with specs: hide if enabled.
		if ( ! current_user_can( 'update_core' ) ) {
			// If user can't update anyway, WP handles it.
			return;
		}

		// Remove update notifications
		remove_action( 'admin_notices', 'update_nag', 3 );
		add_filter( 'pre_site_transient_update_core', '__return_null' );
		add_filter( 'pre_site_transient_update_plugins', '__return_null' );
		add_filter( 'pre_site_transient_update_themes', '__return_null' );
	}

	/**
	 * Customize Dashboard Widgets.
	 */
	public function customize_dashboard() {
		$options = get_option( 'zenadmin_white_label', array() );
		if ( empty( $options['enabled'] ) ) {
			return;
		}

		// 1. Reset Dashboard (Remove All)
		if ( ! empty( $options['wl_dashboard_reset'] ) ) {
			global $wp_meta_boxes;
			// Clear everything
			$wp_meta_boxes['dashboard'] = array();
		}

		// 2. Add Welcome Widget
		if ( ! empty( $options['wl_welcome_title'] ) || ! empty( $options['wl_welcome_content'] ) ) {
			wp_add_dashboard_widget(
				'zenadmin_welcome_widget',
				! empty( $options['wl_welcome_title'] ) ? esc_html( $options['wl_welcome_title'] ) : 'Welcome',
				array( $this, 'render_welcome_widget' )
			);
		}
		
		// Always remove default welcome panel if we are customizing dashboard
		if ( ! empty( $options['wl_dashboard_reset'] ) ) {
			remove_action( 'welcome_panel', 'wp_welcome_panel' );
		}
	}

	/**
	 * Render Welcome Widget Content.
	 */
	public function render_welcome_widget() {
		$options = get_option( 'zenadmin_white_label', array() );
		if ( ! empty( $options['wl_welcome_content'] ) ) {
			echo wp_kses_post( wpautop( $options['wl_welcome_content'] ) );
		}
	}

	/**
	 * Enforce Global Hard Blocks.
	 */
	public function enforce_global_hard_blocks() {
		// 1. Safety Checks
		// - DOING_AJAX: Exclude to prevent breaking dynamic functionality
		if ( wp_doing_ajax() ) {
			return;
		}
		// - Admin Immunity: Never block Super Admins or fully capable admins
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		// - Kill Switch (already handled by init check, but good to be double safe)
		if ( defined( 'ZENADMIN_WHITE_LABEL' ) && false === ZENADMIN_WHITE_LABEL ) {
			return;
		}

		$options = get_option( 'zenadmin_white_label', array() );
		if ( empty( $options['enabled'] ) ) {
			return;
		}

		// 2. Check Roles
		$applied_roles = isset( $options['wl_applied_roles'] ) ? (array) $options['wl_applied_roles'] : array();
		if ( empty( $applied_roles ) ) {
			return; // No roles targeted
		}

		$user = wp_get_current_user();
		$user_roles = (array) $user->roles;
		
		// Check intersection: if user has NONE of the applied roles, skip.
		if ( ! array_intersect( $user_roles, $applied_roles ) ) {
			return;
		}

		// 3. Check Pages
		$pages_raw = isset( $options['wl_hard_block_pages'] ) ? $options['wl_hard_block_pages'] : '';
		if ( empty( $pages_raw ) ) {
			return;
		}

		$blocked_pages = array_map( 'trim', explode( ',', $pages_raw ) );
		$current_uri   = $_SERVER['REQUEST_URI']; // e.g. /wp-admin/tools.php?page=x

		foreach ( $blocked_pages as $page ) {
			if ( empty( $page ) ) continue;

			// Simple strpos check for the page slug/filename in the URI
			// This covers 'tools.php' in '/wp-admin/tools.php'
			// And 'options-general.php' in '/wp-admin/options-general.php'
			if ( false !== strpos( $current_uri, $page ) ) {
				
				// BLOCK DETECTED
				$redirect_to = ! empty( $options['wl_redirect_dest'] ) ? $options['wl_redirect_dest'] : admin_url();
				
				wp_safe_redirect( $redirect_to );
				exit;
			}
		}
	}



	/**
	 * Hide Hard Blocked Menu Items.
	 */
	public function hide_hard_blocked_menus() {
		// 1. Safety Checks
		// - Admin Immunity: Never hide for Super Admins or fully capable admins
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = get_option( 'zenadmin_white_label', array() );
		if ( empty( $options['enabled'] ) ) {
			return;
		}

		// 2. Check Roles
		$applied_roles = isset( $options['wl_applied_roles'] ) ? (array) $options['wl_applied_roles'] : array();
		if ( empty( $applied_roles ) ) {
			return; // No roles targeted
		}

		$user = wp_get_current_user();
		$user_roles = (array) $user->roles;
		
		// Check intersection: if user has NONE of the applied roles, skip.
		if ( ! array_intersect( $user_roles, $applied_roles ) ) {
			return;
		}

		// 3. Hide Menus
		$pages_raw = isset( $options['wl_hard_block_pages'] ) ? $options['wl_hard_block_pages'] : '';
		if ( empty( $pages_raw ) ) {
			return;
		}

		$blocked_pages = array_map( 'trim', explode( ',', $pages_raw ) );

		foreach ( $blocked_pages as $page ) {
			if ( empty( $page ) ) continue;
			
			// Remove top-level menu
			remove_menu_page( $page );
			
			// Try to remove from common parents as submenu
			remove_submenu_page( 'index.php', $page );
			remove_submenu_page( 'tools.php', $page );
			remove_submenu_page( 'options-general.php', $page );
			remove_submenu_page( 'themes.php', $page );
			remove_submenu_page( 'plugins.php', $page );
			remove_submenu_page( 'users.php', $page );
			
			// Also attempt to remove using the page slug as parent
			remove_submenu_page( $page, $page );
		}
	}

	/**
	 * Inject Admin Colors (Pro).
	 */
	public function inject_admin_colors() {
		$options = get_option( 'zenadmin_white_label', array() );
		if ( empty( $options['enabled'] ) ) {
			return;
		}

		$primary    = ! empty( $options['wl_admin_primary'] ) ? $options['wl_admin_primary'] : '';
		$sidebar_bg = ! empty( $options['wl_admin_sidebar_bg'] ) ? $options['wl_admin_sidebar_bg'] : '';
		$accent     = ! empty( $options['wl_admin_accent'] ) ? $options['wl_admin_accent'] : '';
		$admin_bar  = ! empty( $options['wl_admin_bar_bg'] ) ? $options['wl_admin_bar_bg'] : '';

		if ( ! $primary && ! $sidebar_bg && ! $accent && ! $admin_bar ) {
			return;
		}

		$css = '';

		// 1. Sidebar Background & Text Contrast
		// Default to dark sidebar (white text) if no custom bg is set
		$text_color = '#fff';
		
		if ( $sidebar_bg ) {
			$css .= "
				#adminmenuback, #adminmenuwrap, #adminmenu, #adminmenu .wp-submenu {
					background-color: {$sidebar_bg} !important;
				}
			";

			// Smart Contrast for Sidebar Text
			$luminance = $this->get_luminance( $sidebar_bg );
			$text_color = ( $luminance > 0.5 ) ? '#1d2327' : '#fff';
		}

		// Apply Sidebar Text Color (globally or just when custom BG is set? 
		// If custom BG is set, we MUST apply it. 
		// If not, we can still force it if we want to ensure consistency with the active item logic below, 
		// but standard WP is fine. Let's apply it if $sidebar_bg is set OR if we need to force white for active items).
		
		if ( $sidebar_bg ) {
			$css .= "
				#adminmenu a {
					color: {$text_color} !important;
				}
				#adminmenu .wp-submenu a {
					color: {$text_color} !important;
				}
				#adminmenu div.wp-menu-image:before {
					color: {$text_color} !important;
				}
			";
		}
		
		// 1.5 Admin Bar Background
		if ( $admin_bar ) {
			$css .= "
				#wpadminbar {
					background-color: {$admin_bar} !important;
				}
			";
			
			// Smart Contrast for Admin Bar
			$ab_luminance = $this->get_luminance( $admin_bar );
			$ab_text_color = ( $ab_luminance > 0.5 ) ? '#1d2327' : '#fff';
			$ab_icon_color = ( $ab_luminance > 0.5 ) ? '#1d2327' : '#a7aaad'; // Standard grey for icons on dark, dark on light
			
			$css .= "
				#wpadminbar .ab-item, 
				#wpadminbar a.ab-item, 
				#wpadminbar > #wp-toolbar > #wp-admin-bar-root-default .ab-icon, 
				#wpadminbar .ab-icon, 
				#wpadminbar .ab-label {
					color: {$ab_text_color} !important;
				}
				
				/* Fix icons color specifically if needed */
				#wpadminbar .ab-icon:before {
					color: {$ab_icon_color} !important;
				}
			";
		}

		// 2. Primary Color (Buttons & Links)
		if ( $primary ) {
			$css .= "
				a {
					color: {$primary};
				}
				.wp-core-ui .button-primary {
					background-color: {$primary} !important;
					border-color: {$primary} !important;
				}
				.wp-core-ui .button-primary:hover, .wp-core-ui .button-primary:focus {
					background-color: {$primary} !important;
					border-color: {$primary} !important;
					filter: brightness(0.9);
				}
				input[type=checkbox]:checked:before {
					color: {$primary} !important;
				}
				input[type=radio]:checked:before {
					background-color: {$primary} !important;
				}
				.wp-core-ui .wp-ui-primary {
					color: #fff;
					background-color: {$primary};
				}
				
				/* Plugin List Borders (Active Row) */
				.plugins .active th.check-column, 
				.plugin-update-tr.active td {
					border-left-color: {$primary} !important;
				}
			";
		}

		// 3. Accent Color (Active Menu Items)
		if ( $accent ) {
			$css .= "
				#adminmenu li.current a.menu-top,
				#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu,
				#adminmenu li.current div.wp-menu-image:before,
				#adminmenu li.wp-has-current-submenu div.wp-menu-image:before,
				#adminmenu a:hover,
				#adminmenu li.menu-top:hover,
				#adminmenu li.opensub > a.menu-top,
				#adminmenu li > a.menu-top:focus {
					background-color: {$accent} !important;
					color: #fff !important;
				}
				
				/* Submenu active state */
				#adminmenu .wp-submenu li.current a,
				#adminmenu .wp-submenu li.current a:hover,
				#adminmenu .wp-submenu li.current a:focus,
				#adminmenu a:hover,
				#adminmenu .wp-submenu a:focus,
				#adminmenu .wp-submenu a:hover {
					color: {$text_color} !important; /* Use high-contrast text color instead of accent */
					font-weight: 600;
				}
			";
		}

		echo '<style type="text/css" id="zenadmin-admin-colors">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Calculate relative luminance of a hex color.
	 *
	 * @param string $hex Hex color code.
	 * @return float Luminance (0.0 to 1.0).
	 */
	private function get_luminance( $hex ) {
		$hex = str_replace( '#', '', $hex );
		
		// Map simple names to hex if needed, but wp-color-picker usually returns hex
		if ( 3 === strlen( $hex ) ) {
			$hex = str_repeat( substr( $hex, 0, 1 ), 2 ) . str_repeat( substr( $hex, 1, 1 ), 2 ) . str_repeat( substr( $hex, 2, 1 ), 2 );
		}
		
		$r = hexdec( substr( $hex, 0, 2 ) ) / 255;
		$g = hexdec( substr( $hex, 2, 2 ) ) / 255;
		$b = hexdec( substr( $hex, 4, 2 ) ) / 255;
		
		$r = ( $r <= 0.03928 ) ? $r / 12.92 : pow( ( $r + 0.055 ) / 1.055, 2.4 );
		$g = ( $g <= 0.03928 ) ? $g / 12.92 : pow( ( $g + 0.055 ) / 1.055, 2.4 );
		$b = ( $b <= 0.03928 ) ? $b / 12.92 : pow( ( $b + 0.055 ) / 1.055, 2.4 );
		
		return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
	}
}
