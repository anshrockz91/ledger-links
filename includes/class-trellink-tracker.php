<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Click analytics — bot-filtering and self-click exclusion ON by default.
 * This directly answers the #1 competitor complaint found in research:
 * PrettyLinks inflates click counts unless a user manually enables filtering.
 * Here there is nothing to manually enable — it's the default, honest count.
 */
class Trellink_Tracker {

    private static $instance = null;

    private static $bot_patterns = array(
        'bot', 'spider', 'crawl', 'slurp', 'curl', 'wget', 'python-requests',
        'facebookexternalhit', 'facebot', 'preview', 'headless', 'phantomjs',
        'bingpreview', 'pingdom', 'uptimerobot', 'ahrefs', 'semrush', 'mj12bot',
    );

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function record_click( $link ) {
        global $wpdb;
        $table = $wpdb->prefix . 'trellink_clicks';

        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        $is_bot = $this->looks_like_bot( $ua );
        $is_self = is_user_logged_in() && current_user_can( 'manage_options' );

        $ip = $this->get_client_ip();
        $ip_hash = $ip ? hash( 'sha256', $ip . self::get_ip_hash_secret() ) : null;

        $ok = $wpdb->insert( $table, array(
            'link_id'       => (int) $link->id,
            'clicked_at'    => current_time( 'mysql' ),
            'referrer'      => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : null,
            'device'        => wp_is_mobile() ? 'mobile' : 'desktop',
            'browser'       => $this->guess_browser( $ua ),
            'is_bot'        => $is_bot ? 1 : 0,
            'is_self_click' => $is_self ? 1 : 0,
            'ip_hash'       => $ip_hash,
        ) );

        if ( false === $ok ) {
            trellink_log( '[Trellink] Failed to record click for link ' . $link->id . ': ' . $wpdb->last_error );
        }

        return $ok;
    }

    /**
     * "Clean" clicks = not a known bot, not the site admin clicking their own link.
     * This is the number shown by default in the dashboard.
     */
    public function get_clean_click_count( $link_id, $days = 30 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'trellink_clicks';
        $settings = get_option( 'trellink_settings', array() );

        $where = array( 'link_id = %d' );
        $params = array( $link_id );

        $where[] = 'clicked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)';
        $params[] = absint( $days );

        if ( ! empty( $settings['exclude_bots'] ) ) {
            $where[] = 'is_bot = 0';
        }
        if ( ! empty( $settings['exclude_admin_clicks'] ) ) {
            $where[] = 'is_self_click = 0';
        }

        // Table name interpolated (identifiers can't be prepare() placeholders); every value
        // in the WHERE clause goes through the single prepare() call below.
        $where_clause = implode( ' AND ', $where ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}", $params ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );
    }

    public function get_raw_click_count( $link_id, $days = 30 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'trellink_clicks';
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE link_id = %d AND clicked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                $link_id,
                absint( $days )
            )
        );
    }

    public function get_breakdown( $link_id, $days = 30 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'trellink_clicks';
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT device, browser, referrer FROM {$table}
             WHERE link_id = %d AND clicked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             AND is_bot = 0 AND is_self_click = 0",
            $link_id, absint( $days )
        ) );
        return $rows ? $rows : array();
    }

    private function looks_like_bot( $ua ) {
        if ( empty( $ua ) ) {
            return true; // no user-agent at all is itself a strong bot signal
        }
        $ua_lower = strtolower( $ua );
        foreach ( self::$bot_patterns as $pattern ) {
            if ( false !== strpos( $ua_lower, $pattern ) ) {
                return true;
            }
        }
        return false;
    }

    private function guess_browser( $ua ) {
        if ( empty( $ua ) ) {
            return 'unknown';
        }
        $ua_lower = strtolower( $ua );
        if ( false !== strpos( $ua_lower, 'edg/' ) ) return 'Edge';
        if ( false !== strpos( $ua_lower, 'chrome/' ) ) return 'Chrome';
        if ( false !== strpos( $ua_lower, 'safari/' ) && false === strpos( $ua_lower, 'chrome' ) ) return 'Safari';
        if ( false !== strpos( $ua_lower, 'firefox/' ) ) return 'Firefox';
        return 'other';
    }

    /**
     * A secret dedicated to hashing IPs for click dedup — generated and stored
     * by this plugin, never borrowed from WordPress's authentication salts
     * (which are reserved for signing session cookies, not application data).
     */
    private static function get_ip_hash_secret() {
        $secret = get_option( 'trellink_ip_hash_secret' );
        if ( empty( $secret ) ) {
            $secret = wp_generate_password( 64, true, true );
            update_option( 'trellink_ip_hash_secret', $secret );
        }
        return $secret;
    }

    private function get_client_ip() {
        foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) )[0];
                return trim( $ip );
            }
        }
        return null;
    }
}
