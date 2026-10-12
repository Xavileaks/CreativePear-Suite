<?php
/** CLI only: compact toolbar guards and responsive stylesheet regression checks. */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { exit( "Supply an isolated WordPress directory.\n" ); }
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { exit( "Local environment required.\n" ); }
function toolbar_check( $ok, $label ) { if ( ! $ok ) { throw new RuntimeException( $label ); } echo "PASS: $label\n"; }
$original = get_option( 'xw_settings' );
try {
    $admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
    $input = xw_get_settings();
    $input['features']['admin_bar_style'] = 0;
    update_option( 'xw_settings', $input ); wp_set_current_user( (int) $admins[0] ); show_admin_bar( true );
    xl_adminbar_font_12_and_align();
    toolbar_check( ! wp_style_is( 'xw-compact-admin-bar', 'enqueued' ), 'disabled option leaves toolbar untouched' );
    $input['features']['admin_bar_style'] = 1; update_option( 'xw_settings', $input );
    wp_set_current_user( 0 ); xl_adminbar_font_12_and_align();
    toolbar_check( ! wp_style_is( 'xw-compact-admin-bar', 'enqueued' ), 'guests do not load toolbar styles' );
    wp_set_current_user( (int) $admins[0] ); show_admin_bar( false ); xl_adminbar_font_12_and_align();
    toolbar_check( ! wp_style_is( 'xw-compact-admin-bar', 'enqueued' ), 'hidden toolbar stays hidden' );
    show_admin_bar( true ); xl_adminbar_font_12_and_align();
    toolbar_check( wp_style_is( 'xw-compact-admin-bar', 'enqueued' ), 'enabled visible toolbar loads responsive stylesheet' );
    $css = file_get_contents( dirname( __DIR__ ) . '/assets/admin-bar/compact.css' );
    toolbar_check( strpos( $css, 'min-width: 783px' ) !== false && strpos( $css, 'max-width: 782px' ) !== false, 'desktop/mobile rules follow the native WordPress breakpoint' );
    toolbar_check( strpos( $css, '#wpadminbar *' ) === false && strpos( $css, 'margin-top:32px' ) === false, 'no blanket top/font overrides or desktop offset on mobile menus' );
    toolbar_check( strpos( $css, 'height: 46px' ) !== false && strpos( $css, 'min-height: 44px' ) !== false, 'native toolbar height and touch-sized menu targets are preserved' );
} finally { update_option( 'xw_settings', $original ); }
