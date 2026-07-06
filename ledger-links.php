<?php
/**
 * Plugin Name: Ledger Links
 * Plugin URI: https://ledgerlinks.com
 * Description: Affiliate link cloaking with a broken-link checker, honest click analytics, and CSV import/export in the free tier — no upsell nags.
 * Version: 1.0.0
 * Author: Ledger
 * License: GPL v2 or later
 * Text Domain: ledger-links
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LEDGER_LINKS_VERSION', '1.0.0' );
define( 'LEDGER_LINKS_FILE', __FILE__ );
define( 'LEDGER_LINKS_DIR', plugin_dir_path( __FILE__ ) );
define( 'LEDGER_LINKS_URL', plugin_dir_url( __FILE__ ) );

require_once LEDGER_LINKS_DIR . 'includes/class-ledger-activator.php';
require_once LEDGER_LINKS_DIR . 'includes/class-ledger-links-cpt.php';
require_once LEDGER_LINKS_DIR . 'includes/class-ledger-redirector.php';
require_once LEDGER_LINKS_DIR . 'includes/class-ledger-tracker.php';
require_once LEDGER_LINKS_DIR . 'includes/class-ledger-link-checker.php';
require_once LEDGER_LINKS_DIR . 'includes/class-ledger-csv.php';
require_once LEDGER_LINKS_DIR . 'includes/class-ledger-license.php';

if ( is_admin() ) {
    require_once LEDGER_LINKS_DIR . 'admin/class-ledger-admin.php';
}

register_activation_hook( __FILE__, array( 'Ledger_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, function () {
    wp_clear_scheduled_hook( 'ledger_links_check_all' );
    wp_clear_scheduled_hook( 'ledger_links_revalidate_license' );
    flush_rewrite_rules();
} );

/**
 * Log a plugin-internal error, but only when WP_DEBUG logging is on — avoids
 * shipping debug-only calls that run unconditionally in production.
 */
function ledger_links_log( $message ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
        error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    }
}

/**
 * Boot the plugin.
 */
function ledger_links_init() {
    Ledger_Links_CPT::instance();
    Ledger_Redirector::instance();
    Ledger_Tracker::instance();
    Ledger_Link_Checker::instance();
    Ledger_License::instance();

    if ( is_admin() ) {
        Ledger_Admin::instance();
    }
}
add_action( 'plugins_loaded', 'ledger_links_init' );
