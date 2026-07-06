<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Broken-link checker — paywalled on PrettyLinks and ThirstyAffiliates.
 * Free here on purpose: it's the strongest wedge found in research.
 */
class Ledger_Link_Checker {

    private static $instance = null;
    const CRON_HOOK = 'ledger_links_check_all';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'maybe_schedule' ) );
        add_action( self::CRON_HOOK, array( $this, 'check_all_links' ) );
    }

    public function maybe_schedule() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), 'twicedaily', self::CRON_HOOK );
        }
    }

    public function check_all_links() {
        $links = Ledger_Links_CPT::get_all( array( 'limit' => 500 ) );
        $broken_found = array();

        foreach ( $links as $link ) {
            $result = $this->check_one( $link->target_url );
            $status = $result['ok'] ? 'active' : 'broken';
            Ledger_Links_CPT::update_status( $link->id, $status, $result['reason'] );

            if ( ! $result['ok'] ) {
                $broken_found[] = array( 'slug' => $link->slug, 'target' => $link->target_url, 'reason' => $result['reason'] );
            }
        }

        if ( ! empty( $broken_found ) ) {
            ledger_links_log( '[Ledger Links] Broken link check found ' . count( $broken_found ) . ' broken link(s): ' . wp_json_encode( $broken_found ) );
            do_action( 'ledger_links_broken_links_found', $broken_found );
        }

        return $broken_found;
    }

    public function check_one( $url ) {
        $response = wp_remote_head( $url, array(
            'timeout'     => 8,
            'redirection' => 5,
            'user-agent'  => 'Ledger-Links-Checker/1.0 (+https://ledgerlinks.com)',
        ) );

        if ( is_wp_error( $response ) ) {
            // Some servers reject HEAD; retry once with GET before declaring it broken.
            $response = wp_remote_get( $url, array( 'timeout' => 8, 'redirection' => 5 ) );
        }

        if ( is_wp_error( $response ) ) {
            return array( 'ok' => false, 'reason' => 'unreachable: ' . $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code >= 200 && $code < 400 ) {
            return array( 'ok' => true, 'reason' => 'HTTP ' . $code );
        }

        return array( 'ok' => false, 'reason' => 'HTTP ' . $code );
    }
}
