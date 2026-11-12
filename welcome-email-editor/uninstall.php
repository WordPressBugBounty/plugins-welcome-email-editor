<?php
/**
 * Runs when the plugin is deleted.
 *
 * Only removes data on sites where "Remove on uninstall" is enabled.
 *
 * @package Welcome_Email_Editor
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || die( "Can't access directly" );

/**
 * Remove the plugin's data from the current site if the user asked for it.
 */
function weed_uninstall_site() {

	wp_clear_scheduled_hook( 'weed_delete_old_email_logs' );

	$settings = get_option( 'weed_settings' );

	if ( ! is_array( $settings ) || empty( $settings['remove_on_uninstall'] ) ) {
		return;
	}

	delete_option( 'weed_settings' );
	delete_option( 'weed_v5_compatibility' );

	// Email logs.
	$log_ids = get_posts(
		array(
			'post_type'   => 'weed_email_logs',
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
		)
	);

	foreach ( $log_ids as $log_id ) {
		wp_delete_post( $log_id, true );
	}

	// Capabilities for the email logs.
	$role = get_role( 'administrator' );

	if ( $role ) {
		foreach ( array( 'edit_log', 'read_log', 'delete_log', 'edit_logs', 'edit_others_logs', 'delete_logs', 'publish_logs', 'read_private_logs' ) as $cap ) {
			$role->remove_cap( $cap );
		}
	}

}

if ( is_multisite() && function_exists( 'get_sites' ) ) {
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $weed_site_id ) {
		switch_to_blog( $weed_site_id );
		weed_uninstall_site();
		restore_current_blog();
	}
} else {
	weed_uninstall_site();
}
