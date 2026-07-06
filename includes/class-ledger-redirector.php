<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles /go/{slug} -> redirect to target, with device targeting.
 */
class Ledger_Redirector {

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
        $settings = get_option( 'ledger_links_settings', array() );
        $base = isset( $settings['base_slug'] ) ? $settings['base_slug'] : 'go';
        return sanitize_title( $base );
    }

    public function register_rewrite() {
        $base = $this->base_slug();
        add_rewrite_rule( '^' . $base . '/([^/]+)/?$', 'index.php?ledger_link_slug=$matches[1]', 'top' );
    }

    public function register_query_var( $vars ) {
        $vars[] = 'ledger_link_slug';
        return $vars;
    }

    public function maybe_redirect() {
        $slug = get_query_var( 'ledger_link_slug' );
        if ( empty( $slug ) ) {
            return;
        }

        $link = Ledger_Links_CPT::get_by_slug( $slug );

        if ( ! $link ) {
            status_header( 404 );
            nocache_headers();
            wp_die(
                esc_html__( 'This link could not be found.', 'ledger-links' ),
                esc_html__( 'Link not found', 'ledger-links' ),
                array( 'response' => 404 )
            );
        }

        // Track the click before redirecting — never let tracking failure block the redirect.
        try {
            Ledger_Tracker::instance()->record_click( $link );
        } catch ( Throwable $e ) {
            ledger_links_log( '[Ledger Links] Click tracking failed for link ' . $link->id . ': ' . $e->getMessage() );
        }

        $target = $link->target_url;

        if ( ! empty( $link->mobile_target_url ) && wp_is_mobile() ) {
            $target = $link->mobile_target_url;
        }

        if ( 'broken' === $link->status ) {
            ledger_links_log( '[Ledger Links] Warning: redirecting through a link flagged broken by the checker: ' . $slug );
        }

        nocache_headers();
        wp_redirect( esc_url_raw( $target ), (int) $link->redirect_type );
        exit;
    }
}
