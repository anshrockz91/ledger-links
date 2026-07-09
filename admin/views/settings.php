<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$settings = get_option( 'trellink_settings', array() );
$license_result = get_transient( 'trellink_license_result' );
$is_pro = Trellink_License::is_pro();
?>
<div class="wrap trellink-wrap">
    <h1>Settings</h1>

    <?php if ( ! empty( $_GET['trellink_saved'] ) ) : ?>
        <div class="notice notice-success"><p>Settings saved.</p></div>
    <?php endif; ?>

    <div class="trellink-card">
        <h2>Link behavior</h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'trellink_save_settings' ); ?>
            <input type="hidden" name="action" value="trellink_save_settings" />
            <table class="form-table">
                <tr>
                    <th><label for="base_slug">Link base</label></th>
                    <td><code><?php echo esc_html( home_url( '/' ) ); ?></code>
                        <input type="text" name="base_slug" id="base_slug" value="<?php echo esc_attr( $settings['base_slug'] ?? 'go' ); ?>" />
                        <code>/your-slug</code></td>
                </tr>
                <tr>
                    <th>Analytics honesty</th>
                    <td>
                        <label><input type="checkbox" name="exclude_bots" <?php checked( ! empty( $settings['exclude_bots'] ) ); ?> /> Exclude known bots from click counts (recommended, on by default)</label><br>
                        <label><input type="checkbox" name="exclude_admin_clicks" <?php checked( ! empty( $settings['exclude_admin_clicks'] ) ); ?> /> Exclude your own admin clicks (recommended, on by default)</label>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Save settings' ); ?>
        </form>
    </div>

    <div class="trellink-card">
        <h2>License</h2>
        <p>Status: <strong><?php echo $is_pro ? 'Pro active' : 'Free'; ?></strong> — Pro unlocks geo-redirects, the autolinker, advanced analytics, and multi-site licensing. Everything else in this plugin stays free permanently.</p>

        <?php if ( $license_result ) : ?>
            <div class="notice notice-<?php echo $license_result['success'] ? 'success' : 'error'; ?>">
                <p><?php echo esc_html( $license_result['message'] ); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'trellink_activate_license' ); ?>
            <input type="hidden" name="action" value="trellink_activate_license" />
            <input type="text" name="license_key" placeholder="License key" class="regular-text" value="<?php echo esc_attr( $settings['license_key'] ?? '' ); ?>" />
            <?php submit_button( 'Activate', 'secondary' ); ?>
        </form>
    </div>
</div>
