<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles /go/{slug} -> redirect to target, with device targeting.
 */
class Trellink_Redirector {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register_rewrite' ) );
        add_filter( 'query_vars', array( $this, 'register_query_var' ) );
        // Priority 0: must run before WordPress's own redirect_canonical() (priority 10 on
        // this same hook), which otherwise steals /go/{slug} requests with its own
        // trailing-slash redirect before we ever see them.
        add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 0 );
    }

    private function base_slug() {
        $settings = get_option( 'trellink_settings', array() );
        $base = isset( $settings['base_slug'] ) ? $settings['base_slug'] : 'go';
        return sanitize_title( $base );
    }

    public function register_rewrite() {
        $base = $this->base_slug();
        add_rewrite_rule( '^' . $base . '/([^/]+)/?$', 'index.php?trellink_link_slug=$matches[1]', 'top' );
    }

    public function register_query_var( $vars ) {
        $vars[] = 'trellink_link_slug';
        return $vars;
    }

    public function maybe_redirect() {
        $slug = get_query_var( 'trellink_link_slug' );
        if ( empty( $slug ) ) {
            return;
        }

        $link = Trellink_CPT::get_by_slug( $slug );

        if ( ! $link ) {
            status_header( 404 );
            nocache_headers();
            wp_die(
                esc_html__( 'This link could not be found.', 'trellink' ),
                esc_html__( 'Link not found', 'trellink' ),
                array( 'response' => 404 )
            );
        }

        // Track the click before redirecting — never let tracking failure block the redirect.
        try {
            Trellink_Tracker::instance()->record_click( $link );
        } catch ( Throwable $e ) {
            trellink_log( '[Trellink] Click tracking failed for link ' . $link->id . ': ' . $e->getMessage() );
        }

        $target = $link->target_url;

        if ( ! empty( $link->mobile_target_url ) && wp_is_mobile() ) {
            $target = $link->mobile_target_url;
        }

        if ( 'broken' === $link->status ) {
            trellink_log( '[Trellink] Warning: redirecting through a link flagged broken by the checker: ' . $slug );
        }

        nocache_headers();
        wp_redirect( esc_url_raw( $target ), (int) $link->redirect_type );
        exit;
    }
}
