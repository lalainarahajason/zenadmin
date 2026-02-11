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
		// Menu rebranding logic will be handled here or in admin_menu hook

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
	}

	/**
	 * 1. Identity: Rebrand Plugin in plugins.php
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
			// Fallback search if directory name is different
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
				// Only show if user has network capability (usually Super Admin or Agency)
				// Or if defined constant allows it
				if ( ! current_user_can( 'manage_network' ) && ! ( defined( 'ZENADMIN_DEBUG' ) && ZENADMIN_DEBUG ) ) {
					unset( $all_plugins[ $plugin_key ] );
					return $all_plugins;
				}
			}

			// Rebrand metadata
			if ( ! empty( $options['agency_name'] ) ) {
				$all_plugins[ $plugin_key ]['Name']   = $options['agency_name'] . ' (System)';
				$all_plugins[ $plugin_key ]['Author'] = $options['agency_name'];
			}
			
			if ( ! empty( $options['agency_url'] ) ) {
				$all_plugins[ $plugin_key ]['PluginURI'] = $options['agency_url'];
				$all_plugins[ $plugin_key ]['AuthorURI'] = $options['agency_url'];
			}
			
			// Updates logic: We should probably keep update info internal or hide it
			// For now, let's just rebrand the display
			$all_plugins[ $plugin_key ]['Description'] = __( 'Essential system utilities for WordPress administration.', 'zenadmin' );
		}

		return $all_plugins;
	}

	/**
	 * 2. Login: Customize Login Page
	 */
	public function customize_login_page() {
		$options = get_option( 'zenadmin_white_label', array() );
		if ( empty( $options['enabled'] ) ) {
			return;
		}

		// Custom Logo
		if ( ! empty( $options['login_logo'] ) ) {
			$logo_url = esc_url( $options['login_logo'] );
			?>
			<style type="text/css">
				body.login div#login h1 a {
					background-image: url('<?php echo $logo_url; ?>');
					background-size: contain;
					background-repeat: no-repeat;
					background-position: center;
					width: 100%;
					height: 80px;
					margin-bottom: 20px;
				}
			</style>
			<?php
		}
		
		// Background Color/Image (Future)
	}

	public function custom_login_header_url( $url ) {
		$options = get_option( 'zenadmin_white_label', array() );
		return ! empty( $options['agency_url'] ) ? esc_url( $options['agency_url'] ) : $url;
	}

	public function custom_login_header_text( $text ) {
		$options = get_option( 'zenadmin_white_label', array() );
		return ! empty( $options['agency_name'] ) ? esc_attr( $options['agency_name'] ) : $text;
	}

	/**
	 * 4. Menu: Rename Menu Items
	 */
	public function rename_menu_items() {
		global $submenu;
		$options = get_option( 'zenadmin_white_label', array() );
		
		if ( empty( $options['enabled'] ) || empty( $options['agency_name'] ) ) {
			return;
		}

		// Rename Settings > ZenAdmin
		if ( isset( $submenu['options-general.php'] ) ) {
			foreach ( $submenu['options-general.php'] as $key => $item ) {
				if ( 'zenadmin' === $item[2] ) {
					$submenu['options-general.php'][ $key ][0] = $options['agency_name'];
					$submenu['options-general.php'][ $key ][3] = $options['agency_name']; // Page title
					break;
				}
			}
		}
	}

	/**
	 * 3. Admin: Footer Text
	 */
	public function customize_admin_footer( $text ) {
		$options = get_option( 'zenadmin_white_label', array() );
		if ( empty( $options['enabled'] ) ) {
			return $text;
		}

		return ! empty( $options['footer_text'] ) ? wp_kses_post( $options['footer_text'] ) : $text;
	}

	/**
	 * 3. Admin: Hide Version
	 */
	public function hide_wp_version( $version ) {
		$options = get_option( 'zenadmin_white_label', array() );
		if ( empty( $options['enabled'] ) || empty( $options['hide_version'] ) ) {
			return $version;
		}
		return '';
	}

	/**
	 * 3. Admin: Clean Admin Bar & Rename Node
	 */
	public function clean_admin_bar() {
		global $wp_admin_bar;
		$options = get_option( 'zenadmin_white_label', array() );
		
		if ( empty( $options['enabled'] ) ) {
			return;
		}

		// Rebrand ZenAdmin Node
		if ( ! empty( $options['agency_name'] ) ) {
			$node = $wp_admin_bar->get_node( 'zenadmin-parent' );
			if ( $node ) {
				$icon = '<span class="ab-icon dashicons dashicons-visibility"></span> ';
				$node->title = $icon . esc_html( $options['agency_name'] );
				$wp_admin_bar->add_node( $node );
			}
		}

		// Remove WordPress Logo and related items
		$wp_admin_bar->remove_menu( 'wp-logo' );
		$wp_admin_bar->remove_menu( 'about' );
		$wp_admin_bar->remove_menu( 'wporg' );
		$wp_admin_bar->remove_menu( 'documentation' );
		$wp_admin_bar->remove_menu( 'support-forums' );
		$wp_admin_bar->remove_menu( 'feedback' );
	}

	/**
	 * 3. Admin: Dashboard Widgets
	 */
	public function customize_dashboard() {
		$options = get_option( 'zenadmin_white_label', array() );
		if ( empty( $options['enabled'] ) ) {
			return;
		}

		// Example: Remove default widgets if desired (could be a setting later)
		// For now, let's remove 'Welcome' panel
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
	}
}
