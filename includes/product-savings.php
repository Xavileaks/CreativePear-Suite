<?php
/** Optional Elementor savings badge. Reads WooCommerce prices; never changes them. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_product_savings_enabled() {
    return xw_feature_enabled( 'woocommerce_product_savings' ) && class_exists( 'WooCommerce' ) && did_action( 'elementor/loaded' );
}

function xw_product_savings_current_product() {
    global $product, $wp_query;
    if ( ! empty( $wp_query->is_loop_product ) && $product instanceof WC_Product ) { return $product; }
    $id = get_the_ID();
    if ( $id && in_array( get_post_type( $id ), array( 'product', 'product_variation' ), true ) ) {
        $current = wc_get_product( $id );
        if ( $current ) { return $current; }
    }
    return $product instanceof WC_Product ? $product : wc_get_product();
}

function xw_product_savings_price_html( $html ) {
    return wp_kses( $html, array( 'span' => array( 'class' => true, 'dir' => true, 'translate' => true, 'aria-hidden' => true ), 'bdi' => array( 'dir' => true ) ) );
}

/** Amounts are per unit and follow the shop's tax display, currency and price filters. */
function xw_product_savings_values( $product ) {
    if ( ! $product instanceof WC_Product || $product->is_type( array( 'variable', 'grouped' ) ) || ! $product->is_on_sale() ) { return null; }
    $regular = $product->get_regular_price(); $current = $product->get_price();
    if ( '' === $regular || '' === $current || ! is_numeric( $regular ) || ! is_numeric( $current ) || (float) $regular <= 0 || (float) $current < 0 || (float) $current >= (float) $regular ) { return null; }
    $regular = (float) wc_get_price_to_display( $product, array( 'price' => (float) $regular ) );
    $current = (float) wc_get_price_to_display( $product, array( 'price' => (float) $current ) );
    if ( ! is_finite( $regular ) || ! is_finite( $current ) || $regular <= 0 || $current < 0 || $current >= $regular ) { return null; }
    $amount = $regular - $current;
    $percent = 100 * $amount / $regular;
    // Do not overstate a discount or label a paid product as 100% off after rounding.
    $whole = (int) floor( min( 100, $percent ) + 1e-9 );
    if ( $current > 0 && $whole >= 100 ) { $whole = 99; }
    $relative = $whole > 0 ? number_format_i18n( $whole, 0 ) . '%' : '< 1%';
    $absolute = xw_product_savings_price_html( wc_price( $amount ) );
    return array( 'relative' => $relative, 'absolute' => $absolute );
}

function xw_product_savings_variation( $data, $parent, $variation ) {
    if ( ! xw_product_savings_enabled() || ! is_array( $data ) ) { return $data; }
    $data['xw_product_savings'] = xw_product_savings_values( $variation );
    return $data;
}
add_filter( 'woocommerce_available_variation', 'xw_product_savings_variation', 20, 3 );

function xw_product_savings_register_assets() {
    if ( ! xw_product_savings_enabled() ) { return; }
    $base = plugin_dir_path( XW_FUNCTIONS_FILE ); $url = plugin_dir_url( XW_FUNCTIONS_FILE );
    wp_register_style( 'xw-product-savings', $url . 'assets/product-savings/product-savings.css', array(), filemtime( $base . 'assets/product-savings/product-savings.css' ) );
    wp_register_script( 'xw-product-savings', $url . 'assets/product-savings/product-savings.js', array( 'jquery' ), filemtime( $base . 'assets/product-savings/product-savings.js' ), true );
}
add_action( 'wp_enqueue_scripts', 'xw_product_savings_register_assets' );
add_action( 'elementor/frontend/after_register_styles', 'xw_product_savings_register_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'xw_product_savings_register_assets' );

add_action( 'elementor/widgets/register', static function( $manager ) {
    if ( ! xw_product_savings_enabled() ) { return; }
    require_once __DIR__ . '/product-savings/widget.php';
    $manager->register( new XW_Product_Savings_Widget() );
} );

/** Also used by the read-only local preview with the real widget settings. */
function xw_product_savings_markup( $product, $settings, $editing = false ) {
    $mode = 'absolute' === ( $settings['value_mode'] ?? '' ) ? 'absolute' : 'relative';
    $label = is_scalar( $settings['label'] ?? null ) ? (string) $settings['label'] : xw_t( 'Ahorra', 'Save' );
    $values = xw_product_savings_values( $product );
    $variable = $product instanceof WC_Product && $product->is_type( 'variable' );
    $id = $product instanceof WC_Product ? $product->get_id() : 0;
    $notice = ! $product ? xw_t( 'Coloca el widget en una plantilla de producto o un Loop de productos.', 'Place this widget in a product template or product Loop.' ) : ( $variable ? xw_t( 'Selecciona una variación para ver su ahorro.', 'Select a variation to see its savings.' ) : xw_t( 'Este producto no tiene una oferta activa.', 'This product has no active sale.' ) );
    ob_start(); ?>
    <div class="xw-product-savings" data-xw-product-savings data-product-id="<?php echo esc_attr( $id ); ?>" data-value-mode="<?php echo esc_attr( $mode ); ?>" data-variable="<?php echo $variable ? '1' : '0'; ?>" role="status" aria-live="polite" aria-atomic="true">
        <span class="xw-product-savings__badge"<?php echo $values ? '' : ' hidden'; ?>><?php if ( '' !== trim( $label ) ) : ?><span class="xw-product-savings__label"><?php echo esc_html( $label ); ?></span> <?php endif; ?><span class="xw-product-savings__value"><?php echo $values ? ( 'absolute' === $mode ? xw_product_savings_price_html( $values[ $mode ] ) : esc_html( $values[ $mode ] ) ) : ''; ?></span></span>
        <?php if ( $editing ) : ?><span class="xw-product-savings__notice"<?php echo $values ? ' hidden' : ''; ?>><?php echo esc_html( $notice ); ?></span><?php endif; ?>
    </div>
    <?php return trim( ob_get_clean() );
}
