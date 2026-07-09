<?php
/**
 * Plugin Name: Trellink
 * Plugin URI: https://github.com/anshrockz91/trellink
 * Description: Affiliate link cloaking with a broken-link checker, click analytics, and CSV import/export.
 * Version: 1.0.0
 * Author: ledgerlinks
 * License: GPL v2 or later
 * Text Domain: trellink
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TRELLINK_VERSION', '1.0.0' );
define( 'TRELLINK_FILE', __FILE__ );
define( 'TRELLINK_DIR', plugin_dir_path( __FILE__ ) );
define( 'TRELLINK_URL', plugin_dir_url( __FILE__ ) );

require_once TRELLINK_DIR . 'includes/class-trellink-activator.php';
require_once TRELLINK_DIR . 'includes/class-trellink-cpt.php';
require_once TRELLINK_DIR . 'includes/class-trellink-redirector.php';
require_once TRELLINK_DIR . 'includes/class-trellink-tracker.php';
require_once TRELLINK_DIR . 'includes/class-trellink-link-checker.php';
require_once TRELLINK_DIR . 'includes/class-trellink-csv.php';
require_once TRELLINK_DIR . 'includes/class-trellink-license.php';

if ( is_admin() ) {
    require_once TRELLINK_DIR . 'admin/class-trellink-admin.php';
}

register_activation_hook( __FILE__, array( 'Trellink_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, function () {
    wp_clear_scheduled_hook( 'trellink_check_all' );
    wp_clear_scheduled_hook( 'trellink_revalidate_license' );
    flush_rewrite_rules();
} );

/**
 * Log a plugin-internal error, but only when WP_DEBUG logging is on — avoids
 * shipping debug-only calls that run unconditionally in production.
 */
function trellink_log( $message ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
        error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    }
}

/**
 * Boot the plugin.
 */
function trellink_init() {
    Trellink_CPT::instance();
    Trellink_Redirector::instance();
    Trellink_Tracker::instance();
    Trellink_Link_Checker::instance();
    Trellink_License::instance();

    if ( is_admin() ) {
        Trellink_Admin::instance();
    }
}
add_action( 'plugins_loaded', 'trellink_init' );
