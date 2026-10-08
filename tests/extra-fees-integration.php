<?php
/** CLI only: php tests/extra-fees-integration.php /isolated/wordpress */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { exit( "Supply a local WordPress directory.\n" ); }
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { exit( "Local environment required.\n" ); }
require_once ABSPATH . 'wp-admin/includes/template.php';
function cp_fee_assert( $condition, $description ) {
    if ( ! $condition ) { throw new RuntimeException( $description ); }
    echo "PASS: $description\n";
}
$original_settings = get_option( 'xw_settings' );
$original_user = get_current_user_id();
$original_post = $GLOBALS['post'] ?? null;
try {
    cp_fee_assert( 0 === xw_get_default_settings()['woocommerce']['extra_fee_exclude_virtual_only'], 'new switch defaults off' );
    $settings = xw_get_settings();
    $settings['features']['woocommerce_shipping'] = 1;
    $settings['woocommerce']['extra_fees_enabled'] = 1;
    $settings['woocommerce']['extra_fee_exclude_virtual_only'] = 1;
    $settings['woocommerce']['extra_fee_label'] = 'Handling Fee';
    $settings['woocommerce']['extra_fee_rules'] = array( array( 'min'=>0, 'max'=>100, 'amount'=>3 ) );
    cp_fee_assert( 1 === xw_sanitize_settings( $settings )['woocommerce']['extra_fee_exclude_virtual_only'], 'checked switch is sanitized' );
    $unchecked = $settings;
    unset( $unchecked['woocommerce']['extra_fee_exclude_virtual_only'] );
    cp_fee_assert( 0 === xw_sanitize_settings( $unchecked )['woocommerce']['extra_fee_exclude_virtual_only'], 'unchecked switch is sanitized' );
    update_option( 'xw_settings', $settings );
    wp_set_current_user( 1 );
    ob_start(); xw_render_settings_page(); $admin_html = ob_get_clean();
    cp_fee_assert( strpos( $admin_html, 'name="xw_settings[woocommerce][extra_fee_exclude_virtual_only]"' ) !== false, 'native switch renders in settings' );
    wp_set_current_user( 0 );

    // Unsaved products and a fresh, non-persistent cart: no shop data is changed.
    $digital = new WC_Product_Simple(); $digital->set_virtual( true ); $digital->set_downloadable( true );
    $virtual = new WC_Product_Simple(); $virtual->set_virtual( true );
    $physical = new WC_Product_Simple();
    $download_with_shipping = new WC_Product_Simple(); $download_with_shipping->set_downloadable( true );
    $variation = new WC_Product_Variation(); $variation->set_virtual( true );
    $cart = new WC_Cart(); $cart->set_subtotal( 50 );
    $run = static function ( $products, $expected, $description, $mode = 'fixed' ) use ( $cart, &$settings ) {
        $settings['woocommerce']['extra_fee_mode'] = $mode;
        update_option( 'xw_settings', $settings );
        $items = array();
        foreach ( $products as $i=>$product ) { $items['test'.$i] = array( 'data'=>$product, 'quantity'=>1, 'product_id'=>$i+101, 'variation_id'=>0 ); }
        $cart->set_cart_contents( $items );
        $cart->fees_api()->remove_all_fees();
        $cart->add_fee( 'Other charge', 2, false );
        xw_apply_subtotal_extra_fee( $cart );
        $fees = array();
        foreach ( $cart->get_fees() as $fee ) { $fees[$fee->name] = (float) $fee->amount; }
        cp_fee_assert( isset( $fees['Other charge'] ) && $fees['Other charge'] === 2.0, 'preserves unrelated fee: '.$description );
        cp_fee_assert( count( $fees ) === ( $expected ? 2 : 1 ), $description );
        if ( $expected ) { cp_fee_assert( isset( $fees['Handling Fee'] ) && $fees['Handling Fee'] === $expected, 'correct fee amount: '.$description ); }
    };
    $run( array( $digital ), 0, 'one digital product is excluded' );
    $run( array( $digital, $virtual ), 0, 'all virtual products are excluded' );
    $run( array( $variation ), 0, 'virtual variation is excluded' );
    $run( array( $digital, $physical ), 3.0, 'mixed cart receives fixed fee' );
    $run( array( $physical ), 3.0, 'physical-only cart receives fee' );
    $run( array( $download_with_shipping ), 3.0, 'physical downloadable is not excluded' );
    $run( array( $digital, $physical ), 1.5, 'mixed cart percentage remains correct', 'percentage' );
    $run( array( $digital ), 0, 'digital-only percentage is excluded', 'percentage' );
    $settings['woocommerce']['extra_fee_exclude_virtual_only'] = 0;
    $run( array( $digital ), 3.0, 'switch off preserves previous fee behavior' );
    $exclude = static function ( $allowed, $cart ) { return false; };
    add_filter( 'xw_should_apply_subtotal_extra_fee', $exclude, 10, 2 );
    $run( array( $physical ), 0, 'external snippet can suppress only suite fee' );
    remove_filter( 'xw_should_apply_subtotal_extra_fee', $exclude, 10 );
    $run( array( $physical ), 3.0, 'fee returns after external exclusion is removed' );
    $cart->set_cart_contents( array() );
    cp_fee_assert( ! xw_extra_fee_cart_is_virtual_only( $cart ), 'empty cart is not classified as digital-only' );
} finally {
    update_option( 'xw_settings', $original_settings );
    wp_set_current_user( $original_user );
    $GLOBALS['post'] = $original_post;
}
