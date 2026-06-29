<?php
/**
 * Uninstall handler for Image Gallery - Block (Free).
 *
 * Cleans up plugin data when the plugin is deleted from the admin.
 * Only runs if the user has opted in via the dashboard "Delete data on uninstall" setting.
 *
 * @package IGB
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$igb_is_delete_data = get_option( 'igb_delete_data_on_uninstall', false );

if ( ! $igb_is_delete_data ) {
	return;
}

// Delete plugin options.
$igb_options_to_delete = array(
	'igb_delete_data_on_uninstall',
);

foreach ( $igb_options_to_delete as $igb_option ) {
	delete_option( $igb_option );
}
