<?php
if ( PHP_SAPI !== 'cli' ) { exit; }
require __DIR__ . '/product-savings-bootstrap.php';
function price_check( $condition, $label ) { if ( ! $condition ) { throw new RuntimeException( $label ); } echo 'PASS: ' . $label . "\n"; }
price_check( 0 === xw_get_default_settings()['features']['woocommerce_product_price'], 'price widget defaults off' );
if ( 'on' !== $qa_mode ) {
    price_check( ! xw_product_price_enabled(), 'disabled / missing dependency gate' );
    xw_product_price_register_assets();
    price_check( ! wp_style_is( 'xw-product-price', 'registered' ) && ! wp_script_is( 'xw-product-price', 'registered' ), 'inactive feature loads no assets' );
    if ( did_action( 'elementor/loaded' ) ) { price_check( ! Elementor\Plugin::$instance->widgets_manager->get_widget_types( 'xw-product-price' ), 'inactive widget not registered' ); }
    price_check( array( 'keep' => 1 ) === xw_product_price_variation( array( 'keep' => 1 ), null, null ), 'inactive variation payload unchanged' );
    exit;
}
$widget = Elementor\Plugin::$instance->widgets_manager->get_widget_types( 'xw-product-price' );
price_check( $widget instanceof XW_Product_Price_Widget, 'native widget registered with Elementor Free' );
price_check( ! xw_product_savings_enabled(), 'price widget works with savings feature switched off' );
price_check( isset( Elementor\Plugin::$instance->elements_manager->get_categories()['xw-suite'] ), 'shared suite category exists with only price enabled' );
$controls = array();
foreach ( array( 'vertical_alignment', 'alignment', 'gap', 'current_color', 'current_typography_font_size', 'current_stroke_text_stroke', 'current_shadow_text_shadow', 'current_blend_mode', 'original_color', 'original_typography_font_size', 'original_stroke_text_stroke', 'original_shadow_text_shadow', 'original_blend_mode' ) as $key ) {
    $controls[ $key ] = $widget->get_controls( $key );
    price_check( is_array( $controls[ $key ] ), 'native control: ' . $key );
}
price_check( str_contains( key( $controls['current_color']['selectors'] ), '__current' ) && str_contains( key( $controls['original_color']['selectors'] ), '__original' ), 'price styles have independent per-widget selectors' );
Elementor\Core\Frontend\Performance::set_use_style_controls( true );
$styled = new XW_Product_Price_Widget( array( 'id' => 'styledqa', 'elType' => 'widget', 'widgetType' => 'xw-product-price', 'settings' => array( 'current_color' => '#17683d', 'original_color' => '#9a5d36', 'vertical_alignment' => 'flex-end', 'alignment' => 'center', 'current_typography_typography' => 'custom', 'current_typography_font_size' => array( 'size' => 42, 'unit' => 'px' ), 'current_blend_mode' => 'multiply', 'current_stroke_text_stroke_type' => 'yes', 'current_stroke_text_stroke' => array( 'size' => 1, 'unit' => 'px' ), 'current_stroke_stroke_color' => '#123456', 'original_shadow_text_shadow_type' => 'yes', 'original_shadow_text_shadow' => array( 'horizontal' => 1, 'vertical' => 2, 'blur' => 3, 'color' => '#333333' ) ) ), array() );
$css_file = new Elementor\Core\Files\CSS\Post( 0 );
$css_file->add_controls_stack_style_rules( $styled, $css_file->get_style_controls( $styled ), $styled->get_settings(), array( '{{WRAPPER}}' ), array( '.qa-widget' ) );
$css = (string) $css_file->get_stylesheet();
Elementor\Core\Frontend\Performance::set_use_style_controls( false );
foreach ( array( 'color:#17683d', 'color:#9a5d36', 'font-size:42px', 'align-items:flex-end', 'justify-content:center', 'mix-blend-mode:multiply', '-webkit-text-stroke-width:1px', 'text-shadow:' ) as $rule ) { price_check( str_contains( $css, $rule ), 'real Elementor CSS generation: ' . $rule ); }
xw_product_price_register_assets();
price_check( wp_style_is( 'xw-product-price', 'registered' ) && ! wp_style_is( 'xw-product-price', 'enqueued' ), 'assets registered on demand' );
$p = xw_savings_qa_product( 350, 189 );
$sale = xw_product_price_values( $p );
price_check( $sale['current'] === xw_product_savings_price_html( wc_price( 189 ) ) && $sale['original'] === xw_product_savings_price_html( wc_price( 350 ) ), 'active sale renders actual current and regular amounts' );
$regular = xw_product_price_values( xw_savings_qa_product( 350, 350, '' ) );
price_check( '' === $regular['original'] && $sale['original'] === $regular['current'], 'without sale regular amount uses current role and no original amount' );
$html = xw_product_price_markup( xw_savings_qa_product( 350, 350, '' ) );
price_check( str_contains( $html, 'class="xw-product-price__original" hidden' ) && str_contains( $html, 'class="xw-product-price__current"' ), 'undiscounted markup preserves the current style and hides strikethrough' );
price_check( '' !== xw_product_price_values( xw_savings_qa_product( 350, 0 ) )['current'], 'zero-price sale is visible' );
$future = xw_savings_qa_product( 350, 350, 189 ); $future->set_date_on_sale_from( time() + DAY_IN_SECONDS );
$expired = xw_savings_qa_product( 350, 350, 189 ); $expired->set_date_on_sale_to( time() - DAY_IN_SECONDS );
price_check( '' === xw_product_price_values( $future )['original'] && '' === xw_product_price_values( $expired )['original'], 'future and expired sales do not show a discount' );
price_check( null === xw_product_price_values( null ) && null === xw_product_price_values( xw_savings_qa_product( '', '', '' ) ), 'missing product or unset price safe' );
$parent = new WC_Product_Variable(); $parent->set_id( 900 ); $parent->set_price( 100 );
$v = xw_savings_qa_product( 100, 80, null, true ); $v->set_id( 901 ); $v->set_parent_id( 900 );
$data = xw_product_price_variation( array( 'variation_id' => 901, 'keep' => 'yes' ), $parent, $v );
price_check( $data['keep'] === 'yes' && '' !== $data['xw_product_price']['original'], 'selected sale variation payload preserves other fields' );
$v->set_price( 100 );
price_check( '' === xw_product_price_variation( array(), $parent, $v )['xw_product_price']['original'], 'switching to regular variation removes old sale price' );
price_check( false === xw_product_price_variation( false, $parent, $v ), 'suppressed WooCommerce variation preserved' );
$unsafe = static function( $html ) { return $html . '<script>alert(1)</script><img src=x onerror=alert(1)>'; };
add_filter( 'wc_price', $unsafe );
$clean = wp_json_encode( xw_product_price_values( $p ) );
remove_filter( 'wc_price', $unsafe );
price_check( ! str_contains( $clean, '<script' ) && ! str_contains( $clean, 'onerror' ), 'variation price HTML sanitized' );
add_filter( 'pre_option_woocommerce_tax_display_shop', static function() { return 'incl'; } );
$tax = static function( $price ) { return $price * 1.21; }; add_filter( 'woocommerce_get_price_including_tax', $tax );
$taxed = xw_product_price_values( $p );
price_check( $taxed['current'] === xw_product_savings_price_html( wc_price( 228.69 ) ) && $taxed['original'] === xw_product_savings_price_html( wc_price( 423.5 ) ), 'both roles follow the shop tax display and currency formatter' );
remove_filter( 'woocommerce_get_price_including_tax', $tax );
$GLOBALS['product'] = $p;
$render = new XW_Product_Price_Widget( array( 'id' => 'priceqa', 'elType' => 'widget', 'widgetType' => 'xw-product-price', 'settings' => array() ), array() );
ob_start(); $render->render_content(); $rendered = ob_get_clean();
price_check( str_contains( $rendered, 'xw-product-price__current' ) && str_contains( $rendered, '189' ), 'real Elementor render uses the product context' );
echo "No saved products, settings, submissions or mail deliveries.\n";
