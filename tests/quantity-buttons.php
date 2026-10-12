<?php
/** CLI-only quantity module checks against an isolated local WordPress. */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { exit( "Supply the local WordPress directory.\n" ); }
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { exit( "Local environment required.\n" ); }
function quantity_check( $ok, $label ) {
    if ( ! $ok ) { throw new RuntimeException( $label ); }
    echo "PASS: $label\n";
}
$original = get_option( 'xw_settings' );
try {
    $defaults = xw_get_default_settings();
    quantity_check( ! $defaults['features']['woocommerce_quantity_buttons'], 'new module is off by default' );
    quantity_check( in_array( 'woocommerce_quantity_buttons', xw_get_feature_groups()['woocommerce']['features'], true ), 'module belongs to WooCommerce' );
    $dirty = array( 'button_background' => '</style><script>x</script>', 'hover_color' => '#7a1c0b', 'border_width' => -10, 'input_height' => 4, 'input_width' => 999, 'button_width' => array( 1 ), 'border_radius' => '4px;evil' );
    $clean = xw_quantity_sanitize( $dirty );
    quantity_check( '' === $clean['button_background'] && '#7a1c0b' === $clean['hover_color'], 'colors accept only hex values, no HTML/CSS injection' );
    quantity_check( 0 === $clean['border_width'] && 44 === $clean['input_height'] && 200 === $clean['input_width'] && 44 === $clean['button_width'] && 4 === $clean['border_radius'], 'dimensions clamp safely, including malformed nested input' );
    $extra = xw_quantity_sanitize( array( 'gap' => -4, 'input_border_radius' => 999, 'button_shape' => array( 'circle' ) ) );
    quantity_check( $extra['gap'] === 0 && $extra['input_border_radius'] === 100 && $extra['button_shape'] === 'normal', 'gap, input radius and shape reject malformed/out-of-range values' );
    $legacy = xw_get_settings(); $legacy['quantity_buttons']['border_radius'] = 100; unset( $legacy['quantity_buttons']['button_shape'] ); update_option( 'xw_settings', $legacy );
    quantity_check( xw_get_settings()['quantity_buttons']['button_shape'] === 'circle', 'old full-round radius migrates to a true circle' );
    $legacy['quantity_buttons']['button_shape'] = 'normal'; update_option( 'xw_settings', $legacy );
    quantity_check( xw_get_settings()['quantity_buttons']['button_shape'] === 'normal', 'explicit Normal shape is preserved even with full rounding' );
    $input = xw_get_settings();
    $input['quantity_buttons'] = $dirty;
    quantity_check( xw_sanitize_settings( $input )['quantity_buttons'] === $clean, 'existing settings sanitizer includes quantity controls' );
    $input['features']['woocommerce_quantity_buttons'] = 0;
    update_option( 'xw_settings', $input );
    xw_quantity_assets();
    quantity_check( ! wp_script_is( 'xw-quantity-buttons', 'enqueued' ) && ! wp_style_is( 'xw-quantity-buttons', 'enqueued' ), 'disabled module loads no assets' );
    $input['features']['woocommerce_quantity_buttons'] = 1;
    update_option( 'xw_settings', $input );
    xw_quantity_assets();
    quantity_check( wp_script_is( 'xw-quantity-buttons', 'enqueued' ) && wp_style_is( 'xw-quantity-buttons', 'enqueued' ), 'enabled module loads scoped frontend assets' );
    $inline = implode( '', wp_styles()->get_data( 'xw-quantity-buttons', 'after' ) );
    quantity_check( strpos( $inline, '</style>' ) === false && strpos( $inline, '--xw-qty-input-height:44px' ) !== false, 'frontend CSS uses sanitized tokens' );
    $input['quantity_buttons']['input_width'] = 100; $input['quantity_buttons']['button_width'] = 50;
    update_option( 'xw_settings', $input ); xw_quantity_assets();
    $inline = implode( '', wp_styles()->get_data( 'xw-quantity-buttons', 'after' ) );
    quantity_check( strpos( $inline, '--xw-qty-input-width:100px' ) !== false && strpos( $inline, '--xw-qty-button-width:50px' ) !== false, 'input and button widths are emitted as separate settings' );
    ob_start(); xw_render_quantity_settings( $defaults['quantity_buttons'] ); $html = ob_get_clean();
    quantity_check( 17 === substr_count( $html, 'name="xw_settings[quantity_buttons][' ), 'all colors, dimensions, shape, input radius and gap are editable' );
    $input['quantity_buttons'] = xw_quantity_sanitize( array( 'button_shape' => 'circle', 'button_width' => 50, 'input_width' => 50, 'input_height' => 44, 'input_border_radius' => 8, 'gap' => 8 ) );
    update_option( 'xw_settings', $input ); xw_quantity_assets();
    $inline = implode( '', wp_styles()->get_data( 'xw-quantity-buttons', 'after' ) );
    quantity_check( strpos( $inline, '--xw-qty-button-height:50px' ) !== false && strpos( $inline, '--xw-qty-button-radius:50%' ) !== false && strpos( $inline, '--xw-qty-input-border-radius:8px' ) !== false && strpos( $inline, '--xw-qty-gap:8px' ) !== false, 'circle height equals diameter; radius and gap have independent CSS tokens' );
    define( 'WQPMB_VERSION', 'qa' );
    quantity_check( ! xw_quantity_enabled(), 'original plugin conflict avoids duplicate controls' );
} finally { update_option( 'xw_settings', $original ); }
