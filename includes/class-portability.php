<?php
/**
 * ZenAdmin Portability Engine
 *
 * Handles JSON import/export of blocked elements.
 *
 * @package ZenAdmin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZenAdmin_Portability {

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'admin_post_zenadmin_export', array( $this, 'handle_export' ) );
		add_action( 'admin_post_zenadmin_import', array( $this, 'handle_import' ) );
	}

	/**
	 * Handle the import request.
	 */
	public function handle_import() {
		// 1. Verify Nonce & Permissions
		check_admin_referer( 'zenadmin_import_nonce', 'zenadmin_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'zenadmin' ) );
		}

		// 2. Verify File
		if ( empty( $_FILES['zenadmin_import_file'] ) || empty( $_FILES['zenadmin_import_file']['tmp_name'] ) ) {
			add_settings_error( 'zenadmin_messages', 'zenadmin_import_error', __( 'Please select a file to import.', 'zenadmin' ), 'error' );
			wp_safe_redirect( add_query_arg( 'settings-updated', 'true', admin_url( 'options-general.php?page=zenadmin&tab=tools' ) ) );
			exit;
		}

		// 3. Read & Decode JSON
		$json_content = file_get_contents( $_FILES['zenadmin_import_file']['tmp_name'] );
		$data         = json_decode( $json_content, true );

		if ( ! is_array( $data ) || empty( $data['rules'] ) ) {
			add_settings_error( 'zenadmin_messages', 'zenadmin_import_error', __( 'Invalid JSON file or no rules found.', 'zenadmin' ), 'error' );
			wp_safe_redirect( add_query_arg( 'settings-updated', 'true', admin_url( 'options-general.php?page=zenadmin&tab=tools' ) ) );
			exit;
		}

		// 4. Validate Rules
		$valid_rules = $this->validate_import( $data['rules'] );

		if ( empty( $valid_rules ) ) {
			add_settings_error( 'zenadmin_messages', 'zenadmin_import_warning', __( 'No valid rules could be imported.', 'zenadmin' ), 'warning' );
			wp_safe_redirect( add_query_arg( 'settings-updated', 'true', admin_url( 'options-general.php?page=zenadmin&tab=tools' ) ) );
			exit;
		}

		// 5. Merge or Overwrite
		$overwrite = isset( $_POST['zenadmin_overwrite'] ) && '1' === $_POST['zenadmin_overwrite'];
		$current   = $overwrite ? array() : get_option( 'zenadmin_blacklist', array() );

		// Merge logic: new rules overwrite existing rules with same hash (selector)
		$merged = array_merge( $current, $valid_rules );

		// 6. Save & Redirect
		update_option( 'zenadmin_blacklist', $merged );

		$count = count( $valid_rules );
		/* translators: %d: number of rules imported */
		$message = sprintf( __( 'Successfully imported %d rules.', 'zenadmin' ), $count );
		add_settings_error( 'zenadmin_messages', 'zenadmin_import_success', $message, 'success' );

		wp_safe_redirect( add_query_arg( 'settings-updated', 'true', admin_url( 'options-general.php?page=zenadmin&tab=blocks' ) ) );
		exit;
	}

	/**
	 * Handle the export request.
	 */
	public function handle_export() {
		// 1. Verify Nonce & Permissions
		check_admin_referer( 'zenadmin_export_nonce', 'zenadmin_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'zenadmin' ) );
		}

		// 2. Retrieve Data
		$blacklist = get_option( 'zenadmin_blacklist', array() );

		// 3. Format Data
		$export_data = array(
			'version'   => ZENADMIN_VERSION,
			'timestamp' => current_time( 'mysql' ),
			'source'    => home_url(),
			'rules'     => $blacklist,
		);

		// 4. Send Download Headers
		$filename = 'zenadmin-config-' . date( 'Y-m-d-H-i-s' ) . '.json';

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		echo wp_json_encode( $export_data, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Validate imported rules.
	 *
	 * @param array $rules Raw rules from JSON.
	 * @return array Validated rules.
	 */
	public function validate_import( $rules ) {
		$valid_rules = array();

		if ( ! is_array( $rules ) ) {
			return $valid_rules;
		}

		foreach ( $rules as $hash => $item ) {
			// Basic schema check
			if ( ! isset( $item['selector'], $item['label'] ) ) {
				continue;
			}

			// Sanitize
			$label    = sanitize_text_field( $item['label'] );
			$selector = sanitize_text_field( $item['selector'] ); // We need stricter CSS validation here ideally

			// Recalculate hash to ensure integrity
			$new_hash = md5( $selector );

			$valid_rules[ $new_hash ] = array(
				'selector'   => $selector,
				'label'      => $label,
				'created_at' => isset( $item['created_at'] ) ? sanitize_text_field( $item['created_at'] ) : current_time( 'mysql' ),
				'hidden_for' => isset( $item['hidden_for'] ) && is_array( $item['hidden_for'] ) ? array_map( 'sanitize_text_field', $item['hidden_for'] ) : array(), // Default: no roles (or all, depending on logic)
			);
		}

		return $valid_rules;
	}
}
