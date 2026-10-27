<?php
if ( PHP_SAPI !== 'cli' ) { exit; }
require __DIR__ . '/product-savings-bootstrap.php';
function savings_check( $condition, $label ) { if ( ! $condition ) { throw new RuntimeException( $label ); } echo 'PASS: ' . $label . "\n"; }
savings_check( 0 === xw_get_default_settings()['features']['woocommerce_product_savings'], 'feature defaults off' );
savings_check( 'woocommerce_product_savings' === xw_get_feature_groups()['woocommerce']['features'][0], 'new card is in WooCommerce and follows the standard grid' );
$has_dependencies = class_exists( 'WooCommerce' ) && did_action( 'elementor/loaded' );
if ( 'on' !== $qa_mode || ! $has_dependencies ) {
    savings_check( ! xw_product_savings_enabled(), 'disabled or missing dependencies are safe' );
    xw_product_savings_register_assets();
    savings_check( ! wp_style_is( 'xw-product-savings', 'registered' ) && ! wp_script_is( 'xw-product-savings', 'registered' ), 'no assets when disabled or a dependency is missing' );
    if ( did_action( 'elementor/loaded' ) ) { savings_check( ! isset( Elementor\Plugin::$instance->widgets_manager->get_widget_types()['xw-product-savings'] ), 'no widget when disabled or WooCommerce is absent' ); }
    $data = array( 'variation_id' => 5 );
    savings_check( xw_product_savings_variation( $data, null, null ) === $data, 'variation response untouched when inactive' );
    exit;
}
$widgets = Elementor\Plugin::$instance->widgets_manager->get_widget_types();
savings_check( isset( $widgets['xw-product-savings'] ), 'real Elementor registers the native widget without Pro' );
$controls = array();
foreach ( array( 'value_mode', 'label', 'text_color', 'background_color', 'text_typography_font_size', 'badge_border_border', 'border_radius', 'padding', 'alignment' ) as $control ) { $controls[ $control ] = $widgets['xw-product-savings']->get_controls( $control ); savings_check( is_array( $controls[ $control ] ), 'native Elementor control: ' . $control ); }
foreach ( array( 'text_color', 'background_color', 'border_radius' ) as $control ) { savings_check( str_contains( implode( ' ', array_keys( $controls[ $control ]['selectors'] ) ), '{{WRAPPER}}' ), 'per-instance style: ' . $control ); }
xw_product_savings_register_assets();
savings_check( wp_script_is( 'xw-product-savings', 'registered' ) && wp_style_is( 'xw-product-savings', 'registered' ) && ! wp_script_is( 'xw-product-savings', 'enqueued' ), 'assets registered on demand, not enqueued globally' );
$p = xw_savings_qa_product( 45, '39.99' );
$values = xw_product_savings_values( $p );
savings_check( '11%' === $values['relative'], '45 to 39.99 gives 11 percent without overstating savings' );
$p = xw_savings_qa_product( 450, 390 );
$values = xw_product_savings_values( $p );
savings_check( html_entity_decode( $values['absolute'] ) === html_entity_decode( wc_price( 60 ) ) && '13%' === $values['relative'], 'absolute savings use the shop currency formatter and per-unit prices' );
$price_injection = static function( $html ) { return $html . '<script>alert(1)</script><img src=x onerror=alert(1)>'; };
add_filter( 'wc_price', $price_injection );
$sanitized = xw_product_savings_values( $p )['absolute'];
remove_filter( 'wc_price', $price_injection );
savings_check( ! str_contains( $sanitized, '<script' ) && ! str_contains( $sanitized, 'onerror' ), 'formatted price HTML sanitized before reaching variation JavaScript' );
savings_check( '< 1%' === xw_product_savings_values( xw_savings_qa_product( 1000, '999.99' ) )['relative'], 'small positive discounts do not become zero' );
savings_check( '100%' === xw_product_savings_values( xw_savings_qa_product( 20, 0 ) )['relative'], 'free sale is valid without division by zero' );
foreach ( array( array( '', '' ), array( 0, 0 ), array( 100, 100 ), array( 100, 120 ), array( 100, -1 ), array( 100, 100, 80 ) ) as $prices ) { savings_check( null === xw_product_savings_values( xw_savings_qa_product( ...$prices ) ), 'non-sale or invalid price is hidden: ' . implode( '/', $prices ) ); }
$future = xw_savings_qa_product( 100, 80 ); $future->set_date_on_sale_from( time() + DAY_IN_SECONDS );
$expired = xw_savings_qa_product( 100, 80 ); $expired->set_date_on_sale_to( time() - DAY_IN_SECONDS );
savings_check( null === xw_product_savings_values( $future ) && null === xw_product_savings_values( $expired ), 'future and expired sales hidden' );
$parent = new WC_Product_Variable(); $parent->set_id( 900 );
savings_check( false === xw_product_savings_variation( false, $parent, null ), 'another plugin can suppress a variation without being overridden' );
savings_check( null === xw_product_savings_values( $parent ), 'variable parent never invents a discount from unrelated min/max prices' );
$v = xw_savings_qa_product( 100, 80, null, true ); $v->set_parent_id( 900 ); $v->set_id( 901 );
$data = xw_product_savings_variation( array( 'variation_id' => 901, 'display_price' => 80, 'custom_field' => 'preserve' ), $parent, $v );
savings_check( '20%' === $data['xw_product_savings']['relative'] && 80 === $data['display_price'] && 'preserve' === $data['custom_field'], 'variation payload is server-calculated and preserves native fields' );
$v->set_price( 100 );
savings_check( null === xw_product_savings_variation( array(), $parent, $v )['xw_product_savings'], 'non-discounted selected variation clears the badge' );
$markup = xw_product_savings_markup( xw_savings_qa_product( 100, 80 ), array( 'label' => '<img src=x onerror=alert(1)>', 'value_mode' => 'absolute' ) );
savings_check( ! str_contains( $markup, '<img' ) && str_contains( $markup, '&lt;img' ), 'custom text escaped' );
savings_check( ! str_contains( xw_product_savings_markup( $parent, array(), false ), '__notice' ) && str_contains( xw_product_savings_markup( $parent, array(), true ), '__notice' ), 'help is editor-only, no placeholder on the storefront' );
$GLOBALS['product'] = xw_savings_qa_product( 100, 75 );
savings_check( xw_product_savings_current_product() === $GLOBALS['product'], 'current product resolved without changing global context' );
$widget = new XW_Product_Savings_Widget( array( 'id' => 'savingsqa', 'elType' => 'widget', 'widgetType' => 'xw-product-savings', 'settings' => array( 'label' => 'Ahorra', 'value_mode' => 'relative' ) ), array() );
ob_start(); $widget->render_content(); $rendered = ob_get_clean();
savings_check( str_contains( $rendered, '25%' ) && str_contains( $rendered, 'Ahorra' ), 'real Elementor widget renders actual product savings' );
add_filter( 'pre_option_woocommerce_tax_display_shop', static function() { return 'incl'; } );
$tax_filter = static function( $price ) { return $price * 1.21; };
add_filter( 'woocommerce_get_price_including_tax', $tax_filter );
$tax_values = xw_product_savings_values( xw_savings_qa_product( 100, 80 ) );
savings_check( html_entity_decode( $tax_values['absolute'] ) === html_entity_decode( wc_price( 24.2 ) ) && '20%' === $tax_values['relative'], 'shop tax display filters honored for both price values' );
remove_filter( 'woocommerce_get_price_including_tax', $tax_filter );
echo "No products saved, no settings changed, no form submissions or mail deliveries.\n";
