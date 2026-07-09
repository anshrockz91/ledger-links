<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trellink_Admin {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_post_trellink_create_link', array( $this, 'handle_create_link' ) );
        add_action( 'admin_post_trellink_delete_link', array( $this, 'handle_delete_link' ) );
        add_action( 'admin_post_trellink_export_csv', array( 'Trellink_CSV', 'export_all' ) );
        add_action( 'admin_post_trellink_import_csv', array( $this, 'handle_import_csv' ) );
        add_action( 'admin_post_trellink_run_check_now', array( $this, 'handle_run_check_now' ) );
        add_action( 'admin_post_trellink_activate_license', array( $this, 'handle_activate_license' ) );
        add_action( 'admin_post_trellink_save_settings', array( $this, 'handle_save_settings' ) );
    }

    public function enqueue_assets( $hook ) {
        if ( false === strpos( $hook, 'trellink' ) ) {
            return;
        }
        wp_enqueue_style( 'trellink-admin', TRELLINK_URL . 'assets/css/admin.css', array(), TRELLINK_VERSION );
    }

    public function register_menu() {
        add_menu_page(
            'Trellink', 'Trellink', 'manage_options', 'trellink',
            array( $this, 'render_links_page' ), 'dashicons-admin-links', 58
        );
        add_submenu_page( 'trellink', 'All Links', 'All Links', 'manage_options', 'trellink', array( $this, 'render_links_page' ) );
        add_submenu_page( 'trellink', 'Analytics', 'Analytics', 'manage_options', 'trellink-analytics', array( $this, 'render_analytics_page' ) );
        add_submenu_page( 'trellink', 'Import / Export', 'Import / Export', 'manage_options', 'trellink-import', array( $this, 'render_import_page' ) );
        add_submenu_page( 'trellink', 'Settings', 'Settings', 'manage_options', 'trellink-settings', array( $this, 'render_settings_page' ) );
    }

    // ---- Handlers ----

    public function handle_create_link() {
        check_admin_referer( 'trellink_create_link' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Not allowed.' );
        }

        $slug = sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) );
        $target = esc_url_raw( wp_unslash( $_POST['target_url'] ?? '' ) );

        if ( empty( $slug ) || empty( $target ) || ! filter_var( $target, FILTER_VALIDATE_URL ) ) {
            wp_safe_redirect( add_query_arg( 'trellink_error', 'invalid_input', wp_get_referer() ) );
            exit;
        }

        if ( Trellink_CPT::get_by_slug( $slug ) ) {
            wp_safe_redirect( add_query_arg( 'trellink_error', 'slug_taken', wp_get_referer() ) );
            exit;
        }

        // Device targeting stays free — it was a named missing-feature complaint in research, not a Pro gate.
        $mobile = ! empty( $_POST['mobile_target_url'] ) ? esc_url_raw( wp_unslash( $_POST['mobile_target_url'] ) ) : '';

        Trellink_CPT::insert( array(
            'slug'              => $slug,
            'target_url'        => $target,
            'mobile_target_url' => $mobile,
            'title'             => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
            'category'          => sanitize_text_field( wp_unslash( $_POST['category'] ?? '' ) ),
            'redirect_type'     => (int) ( $_POST['redirect_type'] ?? 301 ),
        ) );

        wp_safe_redirect( admin_url( 'admin.php?page=trellink&trellink_created=1' ) );
        exit;
    }

    public function handle_delete_link() {
        check_admin_referer( 'trellink_delete_link' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Not allowed.' );
        }
        Trellink_CPT::delete( absint( $_GET['id'] ?? 0 ) );
        wp_safe_redirect( admin_url( 'admin.php?page=trellink&trellink_deleted=1' ) );
        exit;
    }

    public function handle_import_csv() {
        check_admin_referer( 'trellink_import_csv' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Not allowed.' );
        }

        if ( empty( $_FILES['csv_file']['tmp_name'] ) || UPLOAD_ERR_OK !== $_FILES['csv_file']['error'] ) {
            wp_safe_redirect( add_query_arg( 'trellink_error', 'upload_failed', wp_get_referer() ) );
            exit;
        }

        $filename = sanitize_file_name( $_FILES['csv_file']['name'] );
        if ( 'csv' !== strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
            wp_safe_redirect( add_query_arg( 'trellink_error', 'not_csv', wp_get_referer() ) );
            exit;
        }

        $result = Trellink_CSV::import_from_file( $_FILES['csv_file']['tmp_name'] );
        set_transient( 'trellink_last_import_result', $result, 60 );

        wp_safe_redirect( admin_url( 'admin.php?page=trellink-import&trellink_imported=1' ) );
        exit;
    }

    public function handle_run_check_now() {
        check_admin_referer( 'trellink_run_check_now' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Not allowed.' );
        }
        $broken = Trellink_Link_Checker::instance()->check_all_links();
        set_transient( 'trellink_last_check_result', $broken, 60 );
        wp_safe_redirect( admin_url( 'admin.php?page=trellink&trellink_checked=1' ) );
        exit;
    }

    public function handle_activate_license() {
        check_admin_referer( 'trellink_activate_license' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Not allowed.' );
        }
        $result = Trellink_License::activate( wp_unslash( $_POST['license_key'] ?? '' ) );
        set_transient( 'trellink_license_result', $result, 60 );
        wp_safe_redirect( admin_url( 'admin.php?page=trellink-settings' ) );
        exit;
    }

    public function handle_save_settings() {
        check_admin_referer( 'trellink_save_settings' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Not allowed.' );
        }
        $settings = get_option( 'trellink_settings', array() );
        $settings['base_slug']            = sanitize_title( wp_unslash( $_POST['base_slug'] ?? 'go' ) );
        $settings['exclude_bots']         = ! empty( $_POST['exclude_bots'] );
        $settings['exclude_admin_clicks'] = ! empty( $_POST['exclude_admin_clicks'] );
        update_option( 'trellink_settings', $settings );
        flush_rewrite_rules();
        wp_safe_redirect( admin_url( 'admin.php?page=trellink-settings&trellink_saved=1' ) );
        exit;
    }

    // ---- Views ----

    public function render_links_page() {
        require TRELLINK_DIR . 'admin/views/links.php';
    }

    public function render_analytics_page() {
        require TRELLINK_DIR . 'admin/views/analytics.php';
    }

    public function render_import_page() {
        require TRELLINK_DIR . 'admin/views/import.php';
    }

    public function render_settings_page() {
        require TRELLINK_DIR . 'admin/views/settings.php';
    }
}
