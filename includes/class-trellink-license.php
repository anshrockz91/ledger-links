<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pro-tier license gating via Lemon Squeezy's License API.
 * Free tier works fully with zero license key — this only unlocks
 * geo-redirects, autolinker, advanced analytics, and multi-site.
 */
class Trellink_License {

    private static $instance = null;
    const API_VALIDATE = 'https://api.lemonsqueezy.com/v1/licenses/validate';
    const API_ACTIVATE = 'https://api.lemonsqueezy.com/v1/licenses/activate';

    const CRON_HOOK = 'trellink_revalidate_license';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'cron_schedules', array( $this, 'add_weekly_schedule' ) );
        add_action( 'init', function () {
            if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
                wp_schedule_event( time(), 'trellink_weekly', self::CRON_HOOK );
            }
        } );
        add_action( self::CRON_HOOK, array( $this, 'revalidate' ) );
    }

    public function add_weekly_schedule( $schedules ) {
        $schedules['trellink_weekly'] = array( 'interval' => 7 * DAY_IN_SECONDS, 'display' => 'Once weekly (Trellink)' );
        return $schedules;
    }

    public static function is_pro() {
        $settings = get_option( 'trellink_settings', array() );
        return isset( $settings['license_status'] ) && 'active' === $settings['license_status'];
    }

    public static function activate( $license_key ) {
        $license_key = sanitize_text_field( $license_key );
        if ( empty( $license_key ) ) {
            return array( 'success' => false, 'message' => 'No license key provided.' );
        }

        $response = wp_remote_post( self::API_ACTIVATE, array(
            'timeout' => 15,
            'body'    => array(
                'license_key' => $license_key,
                'instance_name' => home_url(),
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            trellink_log( '[Trellink] License activation request failed: ' . $response->get_error_message() );
            return array( 'success' => false, 'message' => 'Could not reach the license server. Try again shortly.' );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['activated'] ) ) {
            $reason = $body['error'] ?? 'License key was not accepted.';
            return array( 'success' => false, 'message' => $reason );
        }

        $settings = get_option( 'trellink_settings', array() );
        $settings['license_key']    = $license_key;
        $settings['license_status'] = 'active';
        $settings['license_instance_id'] = $body['instance']['id'] ?? null;
        update_option( 'trellink_settings', $settings );

        return array( 'success' => true, 'message' => 'Pro license activated.' );
    }

    /**
     * Re-validate on a schedule (weekly) — cancelled subscriptions should
     * lose Pro access without the site owner needing to do anything.
     */
    public function revalidate() {
        $settings = get_option( 'trellink_settings', array() );
        if ( empty( $settings['license_key'] ) ) {
            return;
        }

        $response = wp_remote_post( self::API_VALIDATE, array(
            'timeout' => 15,
            'body'    => array( 'license_key' => $settings['license_key'] ),
        ) );

        if ( is_wp_error( $response ) ) {
            trellink_log( '[Trellink] License revalidation failed (network): ' . $response->get_error_message() );
            return; // fail open for one cycle rather than punishing users for a transient network blip
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $valid = ! empty( $body['valid'] );

        $settings['license_status'] = $valid ? 'active' : 'expired';
        update_option( 'trellink_settings', $settings );

        if ( ! $valid ) {
            trellink_log( '[Trellink] License no longer valid, Pro features disabled for ' . home_url() );
        }
    }
}
