<?php
/** Local-only regression checks; HTTP is mocked and settings/cron/cache are restored. */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { exit( "Supply an isolated WordPress directory.\n" ); }
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() || is_multisite() ) { exit( "Local single-site environment required.\n" ); }
function defaults_check( $ok, $label ) { if ( ! $ok ) { throw new RuntimeException( $label ); } echo "PASS: $label\n"; }
$original_settings = get_option( 'xw_settings' ); $original_cron = get_option( 'cron' );
$cache_options = array();
foreach ( array( 'xw_github_latest_release', 'xw_github_ten_minute_update_lock', 'xw_github_five_minute_update_lock', 'update_plugins' ) as $key ) {
    foreach ( array( '_site_transient_', '_site_transient_timeout_' ) as $prefix ) { $cache_options[ $prefix . $key ] = get_option( $prefix . $key ); }
}
$requests = 0;
$http = function ( $pre, $args, $url ) use ( &$requests ) {
    if ( 'https://api.github.com/repos/' . XW_GITHUB_REPOSITORY . '/releases/latest' !== $url ) { return $pre; }
    ++$requests;
    return array( 'response' => array( 'code' => 200 ), 'headers' => array(), 'body' => wp_json_encode( array( 'tag_name' => 'v99.0.0', 'assets' => array( array( 'name' => XW_GITHUB_RELEASE_ASSET, 'browser_download_url' => 'https://github.com/' . XW_GITHUB_REPOSITORY . '/releases/download/v99.0.0/' . XW_GITHUB_RELEASE_ASSET ) ) ) ) );
};
$legacy_schedule = function ( $schedules ) { $schedules['xw_ten_minutes'] = array( 'interval' => 600, 'display' => 'Legacy QA' ); return $schedules; };
add_filter( 'pre_http_request', $http, 10, 3 );
try {
    defaults_check( xw_get_default_settings()['styles']['scrollbar_width'] === 5, 'new scrollbar default is 5px' );
    defaults_check( preg_match( '/::-webkit-scrollbar\\s*\\{\\s*width:\\s*5px;/', file_get_contents( dirname( __DIR__ ) . '/assets/css/elementor extra.css' ) ) === 1, 'base CSS fallback also uses 5px' );
    delete_option( 'xw_settings' );
    defaults_check( xw_get_settings()['styles']['scrollbar_width'] === 5 && xw_sanitize_settings( array() )['styles']['scrollbar_width'] === 5, 'fresh settings and missing submitted width use 5px' );
    foreach ( array( 0, 10, 17 ) as $width ) {
        $settings = xw_get_default_settings(); $settings['styles']['scrollbar_width'] = $width; update_option( 'xw_settings', $settings );
        defaults_check( xw_get_settings()['styles']['scrollbar_width'] === $width && xw_sanitize_settings( $settings )['styles']['scrollbar_width'] === $width, "saved width $width remains unchanged" );
    }
    defaults_check( XW_GITHUB_UPDATE_INTERVAL === 300 && wp_get_schedules()['xw_five_minutes']['interval'] === 300, 'five-minute cron interval registered' );
    xw_github_unschedule_update_checks();
    add_filter( 'cron_schedules', $legacy_schedule );
    wp_schedule_event( time() + 600, 'xw_ten_minutes', 'xw_github_ten_minute_update_check' );
    wp_schedule_event( time() + 3600, 'hourly', 'xw_defaults_qa_unrelated' );
    $unrelated = wp_next_scheduled( 'xw_defaults_qa_unrelated' );
    set_site_transient( 'xw_github_latest_release', array( 'version' => 'old' ), 600 );
    set_site_transient( 'xw_github_ten_minute_update_lock', 1, 600 );
    xw_github_schedule_update_checks();
    $event = wp_get_scheduled_event( XW_GITHUB_UPDATE_CRON_HOOK );
    defaults_check( false === wp_next_scheduled( 'xw_github_ten_minute_update_check' ) && $event && $event->interval === 300 && $event->schedule === 'xw_five_minutes' && $event->timestamp <= time() + 300, 'existing ten-minute cron migrates to a five-minute event' );
    defaults_check( false === get_site_transient( 'xw_github_latest_release' ) && false === get_site_transient( 'xw_github_ten_minute_update_lock' ), 'migration invalidates the old ten-minute cache and lock' );
    xw_github_schedule_update_checks();
    defaults_check( wp_next_scheduled( XW_GITHUB_UPDATE_CRON_HOOK ) === $event->timestamp && wp_next_scheduled( 'xw_defaults_qa_unrelated' ) === $unrelated, 'repeated init preserves next check and unrelated cron events' );
    wp_clear_scheduled_hook( XW_GITHUB_UPDATE_CRON_HOOK );
    wp_schedule_event( time() + 3600, 'hourly', XW_GITHUB_UPDATE_CRON_HOOK );
    xw_github_schedule_update_checks();
    defaults_check( wp_get_scheduled_event( XW_GITHUB_UPDATE_CRON_HOOK )->interval === 300, 'mismatched stored interval is repaired' );
    xw_github_get_latest_release(); xw_github_get_latest_release();
    defaults_check( $requests === 1 && abs( get_option( '_site_transient_timeout_xw_github_latest_release' ) - time() - 300 ) <= 2, 'release cache lasts five minutes and avoids duplicate HTTP requests' );
    delete_site_transient( 'xw_github_five_minute_update_lock' );
    xw_github_refresh_update_transient(); xw_github_refresh_update_transient();
    defaults_check( $requests === 1 && abs( get_option( '_site_transient_timeout_xw_github_five_minute_update_lock' ) - time() - 300 ) <= 2, 'admin refresh lock also lasts five minutes' );
    wp_schedule_event( time() + 600, 'xw_ten_minutes', 'xw_github_ten_minute_update_check' );
    xw_github_unschedule_update_checks();
    defaults_check( false === wp_next_scheduled( XW_GITHUB_UPDATE_CRON_HOOK ) && false === wp_next_scheduled( 'xw_github_ten_minute_update_check' ) && wp_next_scheduled( 'xw_defaults_qa_unrelated' ) === $unrelated, 'deactivation clears both suite schedules, not unrelated events' );
} finally {
    remove_filter( 'pre_http_request', $http, 10 ); remove_filter( 'cron_schedules', $legacy_schedule );
    if ( false === $original_settings ) { delete_option( 'xw_settings' ); } else { update_option( 'xw_settings', $original_settings ); }
    update_option( 'cron', $original_cron );
    foreach ( $cache_options as $key => $value ) { if ( false === $value ) { delete_option( $key ); } else { update_option( $key, $value ); } }
}
