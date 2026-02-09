<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package ZenAdmin
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options
delete_option( 'zenadmin_blacklist' );
delete_option( 'zenadmin_schema_version' );

// Clean up any other transients or temporary data if added later
