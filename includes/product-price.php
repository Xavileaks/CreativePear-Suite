<?php
/** Optional, presentation-only WooCommerce price widget. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_product_price_enabled() {
    return xw_feature_enabled( 'woocommerce_product_price' ) && class_exists( 'WooCommerce' ) && did_action( 'elementor/loaded' );
}

/** Both the selected sale price and an undiscounted price use the same current-price role. */
function xw_product_price_values( $product ) {
    if ( ! $product instanceof WC_Product ) { return null; }
    if ( $product->is_type( array( 'variable', 'grouped' ) ) ) {
        $html = wp_kses_post( $product->get_price_html() );
        return '' === $html ? null : array( 'current' => $html, 'original' => '', 'suffix' => '' );
    }
    $current = $product->get_price();
    if ( ! is_numeric( $current ) || ! is_finite( (float) $current ) || (float) $current < 0 ) { return null; }
    $regular = $product->get_regular_price();
    $sale = $product->is_on_sale() && is_numeric( $regular ) && (float) $regular > (float) $current;
    return array(
        'current' => xw_product_savings_price_html( wc_price( wc_get_price_to_display( $product, array( 'price' => (float) $current ) ) ) ),
        'original' => $sale ? xw_product_savings_price_html( wc_price( wc_get_price_to_display( $product, array( 'price' => (float) $regular ) ) ) ) : '',
        'suffix' => wp_kses_post( $product->get_price_suffix() ),
    );
}

function xw_product_price_variation( $data, $parent, $variation ) {
    if ( xw_product_price_enabled() && is_array( $data ) ) { $data['xw_product_price'] = xw_product_price_values( $variation ); }
    return $data;
}
add_filter( 'woocommerce_available_variation', 'xw_product_price_variation', 20, 3 );

function xw_product_price_register_assets() {
    if ( ! xw_product_price_enabled() ) { return; }
    $base = plugin_dir_path( XW_FUNCTIONS_FILE ); $url = plugin_dir_url( XW_FUNCTIONS_FILE );
    wp_register_style( 'xw-product-price', $url . 'assets/product-price/product-price.css', array(), filemtime( $base . 'assets/product-price/product-price.css' ) );
    wp_register_script( 'xw-product-price', $url . 'assets/product-price/product-price.js', array( 'jquery' ), filemtime( $base . 'assets/product-price/product-price.js' ), true );
}
add_action( 'wp_enqueue_scripts', 'xw_product_price_register_assets' );
add_action( 'elementor/frontend/after_register_styles', 'xw_product_price_register_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'xw_product_price_register_assets' );

add_action( 'elementor/widgets/register', static function( $manager ) {
    if ( ! xw_product_price_enabled() ) { return; }
    require_once __DIR__ . '/product-price/widget.php';
    $manager->register( new XW_Product_Price_Widget() );
} );

function xw_product_price_markup( $product, $editing = false ) {
    $values = xw_product_price_values( $product );
    $variable = $product instanceof WC_Product && $product->is_type( 'variable' );
    $id = $product instanceof WC_Product ? $product->get_id() : 0;
    ob_start(); ?>
    <div class="xw-product-price" data-xw-product-price data-product-id="<?php echo esc_attr( $id ); ?>" data-variable="<?php echo $variable ? '1' : '0'; ?>" role="status" aria-live="polite" aria-atomic="true">
        <del class="xw-product-price__original"<?php echo ! empty( $values['original'] ) ? '' : ' hidden'; ?>><?php echo $values ? $values['original'] : ''; ?></del>
        <span class="xw-product-price__current"><?php echo $values ? $values['current'] : ''; ?></span>
        <span class="xw-product-price__suffix"><?php echo $values ? $values['suffix'] : ''; ?></span>
        <?php if ( $editing && ! $values ) : ?><span class="xw-product-price__notice"><?php echo esc_html( xw_t( 'Selecciona un producto con precio en la vista previa de Elementor.', 'Select a priced product in the Elementor preview.' ) ); ?></span><?php endif; ?>
    </div>
    <?php return trim( ob_get_clean() );
}
