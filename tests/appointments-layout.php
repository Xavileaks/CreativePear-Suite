<?php
/** Read-only local render test. Option filters apply only to this CLI request. */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { exit( "Usage: php tests/appointments-layout.php <local-wp> [xleon|creativepear] [output.html]\n" ); }
$suite = 'creativepear' === ( $argv[2] ?? '' ) ? 'creativepear-suite' : 'xleon-suite';
$GLOBALS['wp_filter']['option_active_plugins'][10][] = array( 'function' => static function() use ( $suite ) { return array( 'elementor/elementor.php', $suite . '/' . $suite . '.php' ); }, 'accepted_args' => 1 );
$GLOBALS['wp_filter']['pre_option_xw_settings'][10][] = array( 'function' => static function() { return array( 'features' => array( 'elementor_appointments' => 1 ) ); }, 'accepted_args' => 1 );
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { exit( "Local environment required\n" ); }
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
wp_set_current_user( (int) $admins[0] );
add_filter( 'determine_locale', static function() { return 'es_ES'; } );
ob_start(); xw_appt_admin_page(); $markup = ob_get_clean();
function appt_layout_check( $ok, $label ) { if ( ! $ok ) { throw new RuntimeException( $label ); } echo "PASS: $label\n"; }
$intro = strpos( $markup, 'class="xw-appt-intro"' ); $bookings = strpos( $markup, 'id="xw-appt-bookings"' );
$guide = strpos( $markup, 'class="xw-appt-guide"' ); $schedule = strpos( $markup, 'id="xw-appt-schedule"' );
appt_layout_check( false !== $intro && $intro < $bookings && $bookings < $guide && $guide < $schedule, 'bookings follow the introduction and precede the guide and settings' );
appt_layout_check( 1 === substr_count( $markup, 'id="xw-appt-bookings"' ), 'the booking list is rendered exactly once' );
appt_layout_check( strpos( $markup, 'class="xw-appt-filters"' ) < $guide && strpos( $markup, 'class="xw-appt-bookings"' ) < $guide && ! str_contains( $markup, '<table' ), 'filters and booking cards precede the guide, without a table' );
$dom = new DOMDocument(); libxml_use_internal_errors( true ); $dom->loadHTML( '<?xml encoding="UTF-8">' . $markup ); libxml_clear_errors();
$xpath = new DOMXPath( $dom );
appt_layout_check( 3 === $xpath->query( '//div[@class="xw-appt-rules"]/div[@class="xw-appt-rule"]' )->length, 'the three booking rules share one responsive grid' );
appt_layout_check( 2 === $xpath->query( '//div[@class="xw-appt-settings-grid"]/section' )->length, 'the two desktop settings panels remain intact' );
appt_layout_check( 0 === $xpath->query( '//form[@id="xw-appt-schedule"]//table | //form[@id="xw-appt-schedule"]//form' )->length, 'booking forms are not nested inside the schedule form' );
if ( ! empty( $argv[3] ) ) {
    xw_appt_admin_assets( 'toplevel_page_xw-appointments' );
    wp_enqueue_style( 'common' ); wp_enqueue_style( 'forms' ); wp_enqueue_style( 'buttons' );
    ob_start();
    ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Citas · revisión de distribución</title><?php wp_print_styles(); ?><style>body{font:13px/1.5 system-ui;background:#f0f0f1;margin:0;padding:24px;color:#1d2327}.screen-reader-text{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}@media(max-width:600px){body{padding:12px}}</style></head><body class="wp-core-ui"><?php echo $markup; wp_print_footer_scripts(); ?></body></html><?php
    file_put_contents( $argv[3], ob_get_clean() );
    echo "Rendered read-only local fixture\n";
}
