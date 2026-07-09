<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$links = Trellink_CPT::get_all();
$tracker = Trellink_Tracker::instance();
?>
<div class="wrap trellink-wrap">
    <h1>Analytics</h1>
    <p>Numbers below exclude known bots and your own admin clicks by default — see Settings to change that. This is the honest count, not the inflated one.</p>

    <?php if ( empty( $links ) ) : ?>
        <p>No links yet.</p>
    <?php else : foreach ( $links as $link ) :
        $breakdown = $tracker->get_breakdown( $link->id, 30 );
        $devices = array( 'desktop' => 0, 'mobile' => 0 );
        $browsers = array();
        foreach ( $breakdown as $row ) {
            if ( isset( $devices[ $row->device ] ) ) { $devices[ $row->device ]++; }
            $browsers[ $row->browser ] = ( $browsers[ $row->browser ] ?? 0 ) + 1;
        }
        ?>
        <div class="trellink-card">
            <h2><?php echo esc_html( $link->title ?: $link->slug ); ?> <span class="description">/<?php echo esc_html( $link->slug ); ?></span></h2>
            <p><strong><?php echo esc_html( count( $breakdown ) ); ?></strong> clean clicks in the last 30 days.</p>
            <p>Device split — Desktop: <?php echo esc_html( $devices['desktop'] ); ?>, Mobile: <?php echo esc_html( $devices['mobile'] ); ?></p>
            <?php
            $browser_parts = array();
            foreach ( $browsers as $browser_name => $browser_count ) {
                $browser_parts[] = "{$browser_name}: {$browser_count}";
            }
            $browser_summary = $browser_parts ? implode( ', ', $browser_parts ) : 'no data yet';
            ?>
            <p>Browsers — <?php echo esc_html( $browser_summary ); ?></p>
        </div>
    <?php endforeach; endif; ?>
</div>
