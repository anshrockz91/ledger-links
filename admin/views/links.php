<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = get_option( 'trellink_settings', array( 'base_slug' => 'go' ) );
$links = Trellink_CPT::get_all();
$tracker = Trellink_Tracker::instance();
?>
<div class="wrap trellink-wrap">
    <h1>Trellink</h1>

    <?php if ( ! empty( $_GET['trellink_checked'] ) ) :
        $broken = get_transient( 'trellink_last_check_result' );
        $count = is_array( $broken ) ? count( $broken ) : 0; ?>
        <div class="notice notice-<?php echo $count ? 'warning' : 'success'; ?>">
            <p><?php echo $count ? esc_html( "Check complete: {$count} broken link(s) found." ) : 'Check complete: all links are healthy.'; ?></p>
        </div>
    <?php endif; ?>

    <div class="trellink-two-col">
        <div class="trellink-card">
            <h2>Add a link</h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'trellink_create_link' ); ?>
                <input type="hidden" name="action" value="trellink_create_link" />
                <table class="form-table">
                    <tr>
                        <th><label for="slug">Slug</label></th>
                        <td><code><?php echo esc_html( home_url( '/' . $settings['base_slug'] . '/' ) ); ?></code>
                            <input type="text" name="slug" id="slug" required placeholder="my-affiliate-deal" /></td>
                    </tr>
                    <tr>
                        <th><label for="target_url">Target URL</label></th>
                        <td><input type="url" name="target_url" id="target_url" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th><label for="mobile_target_url">Mobile target (optional)</label></th>
                        <td><input type="url" name="mobile_target_url" id="mobile_target_url" class="regular-text" />
                            <p class="description">Send mobile visitors somewhere different — free, no add-on required.</p></td>
                    </tr>
                    <tr>
                        <th><label for="title">Title</label></th>
                        <td><input type="text" name="title" id="title" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="category">Category</label></th>
                        <td><input type="text" name="category" id="category" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="redirect_type">Redirect type</label></th>
                        <td>
                            <select name="redirect_type" id="redirect_type">
                                <option value="301">301 — Permanent</option>
                                <option value="302">302 — Temporary</option>
                                <option value="307">307 — Temporary, preserve method</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button( 'Create link' ); ?>
            </form>
        </div>

        <div class="trellink-card">
            <h2>Link health</h2>
            <p>The broken-link checker runs automatically twice a day. Run it manually any time.</p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'trellink_run_check_now' ); ?>
                <input type="hidden" name="action" value="trellink_run_check_now" />
                <?php submit_button( 'Run check now', 'secondary' ); ?>
            </form>
        </div>
    </div>

    <h2>All links (<?php echo count( $links ); ?>)</h2>
    <table class="widefat striped">
        <thead>
            <tr>
                <th>Slug</th><th>Target</th><th>Status</th><th>Clicks (30d, clean)</th><th>Raw clicks (30d)</th><th></th>
            </tr>
        </thead>
        <tbody>
        <?php if ( empty( $links ) ) : ?>
            <tr><td colspan="6">No links yet — create your first one above.</td></tr>
        <?php else : foreach ( $links as $link ) :
            $clean = $tracker->get_clean_click_count( $link->id );
            $raw = $tracker->get_raw_click_count( $link->id ); ?>
            <tr>
                <td><code><?php echo esc_html( '/' . $settings['base_slug'] . '/' . $link->slug ); ?></code></td>
                <td><?php echo esc_html( $link->target_url ); ?></td>
                <td>
                    <?php if ( 'broken' === $link->status ) : ?>
                        <span class="trellink-badge trellink-badge-broken">Broken<?php echo $link->last_check_result ? ' — ' . esc_html( $link->last_check_result ) : ''; ?></span>
                    <?php else : ?>
                        <span class="trellink-badge trellink-badge-ok">Active</span>
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html( $clean ); ?></td>
                <td><?php echo esc_html( $raw ); ?><?php if ( $raw > $clean ) : ?>
                    <span class="description"> (<?php echo esc_html( $raw - $clean ); ?> filtered as bot/self-click)</span>
                <?php endif; ?></td>
                <td>
                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=trellink_delete_link&id=' . $link->id ), 'trellink_delete_link' ) ); ?>"
                       onclick="return confirm('Delete this link?');">Delete</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
