<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trellink_Activator {

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $links_table  = $wpdb->prefix . 'trellink';
        $clicks_table = $wpdb->prefix . 'trellink_clicks';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql_links = "CREATE TABLE {$links_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            slug VARCHAR(190) NOT NULL,
            target_url TEXT NOT NULL,
            mobile_target_url TEXT NULL,
            title VARCHAR(255) NULL,
            category VARCHAR(100) NULL,
            redirect_type SMALLINT NOT NULL DEFAULT 301,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            last_checked_at DATETIME NULL,
            last_check_result VARCHAR(20) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) {$charset_collate};";

        $sql_clicks = "CREATE TABLE {$clicks_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            link_id BIGINT UNSIGNED NOT NULL,
            clicked_at DATETIME NOT NULL,
            referrer VARCHAR(255) NULL,
            device VARCHAR(20) NULL,
            browser VARCHAR(50) NULL,
            is_bot TINYINT(1) NOT NULL DEFAULT 0,
            is_self_click TINYINT(1) NOT NULL DEFAULT 0,
            ip_hash VARCHAR(64) NULL,
            PRIMARY KEY  (id),
            KEY link_id (link_id),
            KEY clicked_at (clicked_at)
        ) {$charset_collate};";

        dbDelta( $sql_links );
        dbDelta( $sql_clicks );

        if ( false === get_option( 'trellink_settings' ) ) {
            add_option( 'trellink_settings', array(
                'base_slug'            => 'go',
                'exclude_bots'         => true,
                'exclude_admin_clicks' => true,
                'license_key'          => '',
                'license_status'       => 'free',
            ) );
        }

        flush_rewrite_rules();
    }
}
