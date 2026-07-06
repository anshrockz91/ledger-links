<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CSV import/export — paywalled on ThirstyAffiliates despite no real
 * technical reason to gate it. Free here.
 */
class Ledger_CSV {

    public static function export_all() {
        $links = Ledger_Links_CPT::get_all( array( 'limit' => 100000 ) );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=ledger-links-export-' . gmdate( 'Y-m-d' ) . '.csv' );

        // WP_Filesystem has no equivalent for streaming directly to php://output,
        // so a direct file handle is the only option for a CSV download response.
        $out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        fputcsv( $out, array( 'slug', 'target_url', 'mobile_target_url', 'title', 'category', 'redirect_type', 'status' ) );

        foreach ( $links as $link ) {
            fputcsv( $out, array(
                $link->slug,
                $link->target_url,
                $link->mobile_target_url,
                $link->title,
                $link->category,
                $link->redirect_type,
                $link->status,
            ) );
        }

        fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        exit;
    }

    /**
     * @param string $file_path Path to an uploaded CSV file (already validated as .csv upstream).
     * @return array{imported:int, skipped:int, errors:array}
     */
    public static function import_from_file( $file_path ) {
        $result = array( 'imported' => 0, 'skipped' => 0, 'errors' => array() );

        global $wp_filesystem;
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();

        $contents = $wp_filesystem->get_contents( $file_path );
        if ( false === $contents ) {
            $result['errors'][] = 'Could not open the uploaded file.';
            return $result;
        }

        $lines = preg_split( '/\r\n|\r|\n/', trim( $contents ) );
        $expected = array( 'slug', 'target_url', 'mobile_target_url', 'title', 'category', 'redirect_type' );

        // First line is the header row, skip it.
        for ( $row_num = 1; $row_num < count( $lines ); $row_num++ ) {
            if ( '' === trim( $lines[ $row_num ] ) ) {
                continue;
            }
            $row = str_getcsv( $lines[ $row_num ] );
            $data = array_combine( array_slice( $expected, 0, count( $row ) ), $row );

            if ( empty( $data['slug'] ) || empty( $data['target_url'] ) || ! filter_var( $data['target_url'], FILTER_VALIDATE_URL ) ) {
                $result['skipped']++;
                $result['errors'][] = "Row {$row_num}: missing/invalid slug or target_url, skipped.";
                continue;
            }

            if ( Ledger_Links_CPT::get_by_slug( sanitize_title( $data['slug'] ) ) ) {
                $result['skipped']++;
                $result['errors'][] = "Row {$row_num}: slug '{$data['slug']}' already exists, skipped.";
                continue;
            }

            $inserted = Ledger_Links_CPT::insert( $data );
            if ( false === $inserted ) {
                $result['skipped']++;
                $result['errors'][] = "Row {$row_num}: database insert failed.";
                continue;
            }

            $result['imported']++;
        }

        return $result;
    }
}
