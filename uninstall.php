<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Only drop data if the site owner opted in via settings — default is to keep it, since
// losing every affiliate link on an accidental deactivate/reinstall would be a real problem.
$settings = get_option( 'trellink_settings', array() );

if ( ! empty( $settings['delete_data_on_uninstall'] ) ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}trellink" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}trellink_clicks" );
    delete_option( 'trellink_settings' );
}

wp_clear_scheduled_hook( 'trellink_check_all' );
wp_clear_scheduled_hook( 'trellink_revalidate_license' );
