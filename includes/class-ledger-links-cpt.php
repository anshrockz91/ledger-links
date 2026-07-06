<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Data access layer for links. Not an actual WP CPT — a dedicated table,
 * since links are high-volume and don't need post revisions/meta overhead.
 */
class Ledger_Links_CPT {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'ledger_links';
    }

    public static function get_by_slug( $slug ) {
        global $wpdb;
        $table = self::table();
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s LIMIT 1", $slug )
        );
        if ( null === $row && '' !== $wpdb->last_error ) {
            ledger_links_log( '[Ledger Links] DB error in get_by_slug: ' . $wpdb->last_error );
        }
        return $row;
    }

    public static function get_all( $args = array() ) {
        global $wpdb;
        $table = self::table();
        $defaults = array( 'orderby' => 'created_at', 'order' => 'DESC', 'limit' => 200 );
        $args = wp_parse_args( $args, $defaults );

        $orderby = in_array( $args['orderby'], array( 'created_at', 'title', 'status' ), true ) ? $args['orderby'] : 'created_at';
        $order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
        $limit   = absint( $args['limit'] );

        // Table name and $orderby/$order can't be bound via prepare() placeholders (they're
        // identifiers, not values) — $orderby/$order are whitelisted above and $limit is cast
        // with absint(), so interpolation here is safe despite not going through prepare().
        $rows = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table} ORDER BY {$orderby} {$order} LIMIT %d", $limit ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );
        if ( null === $rows && '' !== $wpdb->last_error ) {
            ledger_links_log( '[Ledger Links] DB error in get_all: ' . $wpdb->last_error );
            return array();
        }
        return $rows;
    }

    public static function insert( $data ) {
        global $wpdb;
        $table = self::table();
        $now = current_time( 'mysql' );

        $ok = $wpdb->insert( $table, array(
            'slug'              => sanitize_title( $data['slug'] ),
            'target_url'        => esc_url_raw( $data['target_url'] ),
            'mobile_target_url' => ! empty( $data['mobile_target_url'] ) ? esc_url_raw( $data['mobile_target_url'] ) : null,
            'title'             => sanitize_text_field( $data['title'] ?? '' ),
            'category'          => sanitize_text_field( $data['category'] ?? '' ),
            'redirect_type'     => in_array( (int) ( $data['redirect_type'] ?? 301 ), array( 301, 302, 307 ), true ) ? (int) $data['redirect_type'] : 301,
            'status'            => 'active',
            'created_at'        => $now,
            'updated_at'        => $now,
        ) );

        if ( false === $ok ) {
            ledger_links_log( '[Ledger Links] Failed to insert link "' . $data['slug'] . '": ' . $wpdb->last_error );
            return false;
        }
        return $wpdb->insert_id;
    }

    public static function update_status( $link_id, $status, $check_result = null ) {
        global $wpdb;
        $table = self::table();
        $ok = $wpdb->update(
            $table,
            array(
                'status'            => $status,
                'last_checked_at'   => current_time( 'mysql' ),
                'last_check_result' => $check_result,
                'updated_at'        => current_time( 'mysql' ),
            ),
            array( 'id' => $link_id )
        );
        if ( false === $ok ) {
            ledger_links_log( '[Ledger Links] Failed to update status for link ' . $link_id . ': ' . $wpdb->last_error );
        }
        return $ok;
    }

    public static function delete( $link_id ) {
        global $wpdb;
        $ok = $wpdb->delete( self::table(), array( 'id' => $link_id ) );
        if ( false === $ok ) {
            ledger_links_log( '[Ledger Links] Failed to delete link ' . $link_id . ': ' . $wpdb->last_error );
        }
        return $ok;
    }
}
