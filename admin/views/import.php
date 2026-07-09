<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$import_result = get_transient( 'trellink_last_import_result' );
?>
<div class="wrap trellink-wrap">
    <h1>Import / Export</h1>
    <p>Both directions are free — no add-on, no tier limit.</p>

    <?php if ( $import_result ) : ?>
        <div class="notice notice-info">
            <p>Imported <?php echo (int) $import_result['imported']; ?>, skipped <?php echo (int) $import_result['skipped']; ?>.</p>
            <?php if ( ! empty( $import_result['errors'] ) ) : ?>
                <ul><?php foreach ( array_slice( $import_result['errors'], 0, 20 ) as $err ) : ?>
                    <li><?php echo esc_html( $err ); ?></li>
                <?php endforeach; ?></ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="trellink-two-col">
        <div class="trellink-card">
            <h2>Export</h2>
            <p>Download every link as a CSV.</p>
            <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin-post.php?action=trellink_export_csv' ) ); ?>">Export CSV</a>
        </div>

        <div class="trellink-card">
            <h2>Import</h2>
            <p>Columns: <code>slug,target_url,mobile_target_url,title,category,redirect_type</code></p>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'trellink_import_csv' ); ?>
                <input type="hidden" name="action" value="trellink_import_csv" />
                <input type="file" name="csv_file" accept=".csv" required />
                <?php submit_button( 'Import CSV' ); ?>
            </form>
        </div>
    </div>
</div>
