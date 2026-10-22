<?php
/** CLI: isolated local WP; restores feature settings. Does not submit any forms. */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { exit( "Supply an isolated WordPress directory.\n" ); }
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { exit( "Local environment required.\n" ); }
function date_check( $ok, $label ) { if ( ! $ok ) { throw new RuntimeException( $label ); } echo "PASS: $label\n"; }
$original = get_option( 'xw_settings' );
try {
    date_check( xw_get_default_settings()['features']['elementor_date_input'] === 0, 'date module defaults off' );
    date_check( in_array( 'elementor_date_input', xw_get_feature_groups()['elementor']['features'], true ) && empty( xw_get_feature_definitions()['elementor_date_input']['settings'] ), 'one standard Elementor card without extra controls' );
    $settings = xw_get_settings(); $settings['features']['elementor_date_input'] = 0; update_option( 'xw_settings', $settings );
    xw_enqueue_elementor_date(); date_check( ! wp_script_is( 'xw-elementor-date', 'enqueued' ) && ! wp_style_is( 'xw-elementor-date', 'enqueued' ), 'disabled module adds no assets or field behavior' );
    $settings['features']['elementor_date_input'] = 1;
    date_check( xw_sanitize_settings( $settings )['features']['elementor_date_input'] === 1, 'date toggle uses existing settings sanitizer' );
    update_option( 'xw_settings', $settings ); xw_enqueue_elementor_date();
    date_check( wp_script_is( 'xw-elementor-date', 'enqueued' ) && wp_style_is( 'xw-elementor-date', 'enqueued' ), 'enabled module with Elementor loads its own script and styles independently of Global CSS' );
} finally { update_option( 'xw_settings', $original ); }
