<?php
/** Request-only isolated WP bootstrap. Never saves options or creates products. */
$qa_root = PHP_SAPI === 'cli' ? ( $argv[1] ?? '' ) : getenv( 'XW_SAVINGS_QA_WP_ROOT' );
if ( PHP_SAPI !== 'cli' && ( ! in_array( $_SERVER['REMOTE_ADDR'] ?? '', array( '127.0.0.1', '::1' ), true ) || 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) { http_response_code( 403 ); exit; }
if ( ! $qa_root || ! is_file( $qa_root . '/wp-load.php' ) ) { exit( 'Supply the isolated local WordPress path.' ); }
$qa_suite = ( PHP_SAPI === 'cli' ? ( $argv[2] ?? '' ) : ( $_GET['suite'] ?? '' ) ) === 'creativepear' ? 'creativepear-suite' : 'xleon-suite';
$qa_mode = PHP_SAPI === 'cli' ? ( $argv[3] ?? 'on' ) : 'on';
$qa_woo_plugin = is_file( $qa_root . '/wp-content/plugins/xw-qa-woocommerce/woocommerce.php' ) ? 'xw-qa-woocommerce/woocommerce.php' : 'woocommerce/woocommerce.php';
$GLOBALS['wp_filter']['option_active_plugins'][10][] = array( 'function' => static function() use ( $qa_suite, $qa_mode, $qa_woo_plugin ) {
    $plugins = array( $qa_suite . '/' . $qa_suite . '.php' );
    if ( 'no-woocommerce' !== $qa_mode ) { array_unshift( $plugins, $qa_woo_plugin ); }
    if ( 'no-elementor' !== $qa_mode ) { array_unshift( $plugins, 'elementor/elementor.php' ); }
    return $plugins;
}, 'accepted_args' => 1 );
$GLOBALS['wp_filter']['pre_option_xw_settings'][10][] = array( 'function' => static function() use ( $qa_mode ) { return array( 'features' => array( 'woocommerce_product_savings' => 'off' !== $qa_mode ? 1 : 0 ) ); }, 'accepted_args' => 1 );
require rtrim( $qa_root, '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { exit( 'Local environment required.' ); }
add_filter( 'pre_wp_mail', '__return_true', 100 );

function xw_savings_qa_product( $regular, $current, $sale = null, $variation = false ) {
    $product = $variation ? new WC_Product_Variation() : new WC_Product_Simple();
    $product->set_name( 'Producto de ejemplo' ); $product->set_status( 'publish' ); $product->set_tax_status( 'none' );
    $product->set_regular_price( $regular ); $product->set_sale_price( null === $sale ? $current : $sale ); $product->set_price( $current );
    return $product;
}
