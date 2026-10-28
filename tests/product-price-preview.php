<?php
require __DIR__ . '/product-savings-bootstrap.php';
Elementor\Plugin::$instance->widgets_manager->get_widget_types();
function price_preview( $product, $settings = array() ) {
    $GLOBALS['product'] = $product;
    Elementor\Core\Frontend\Performance::set_use_style_controls( true );
    $widget = new XW_Product_Price_Widget( array( 'id' => 'price' . wp_rand( 1000, 9999 ), 'elType' => 'widget', 'widgetType' => 'xw-product-price', 'settings' => $settings ), array() );
    $css = new Elementor\Core\Files\CSS\Post( 0 );
    $css->add_controls_stack_style_rules( $widget, $css->get_style_controls( $widget ), $widget->get_settings(), array( '{{WRAPPER}}' ), array( '#price-' . $widget->get_id() ) );
    echo '<style>' . $css->get_stylesheet() . '</style><div id="price-' . esc_attr( $widget->get_id() ) . '">';
    Elementor\Core\Frontend\Performance::set_use_style_controls( false );
    $widget->render_content();
    echo '</div>';
}
$parent = new WC_Product_Variable(); $parent->set_id( 900 ); $parent->set_price( 189 );
$range = static function( $html, $product ) { return 900 === $product->get_id() ? wc_format_price_range( 189, 249 ) : $html; };
add_filter( 'woocommerce_get_price_html', $range, 10, 2 );
$choices = array();
foreach ( array( 901 => array( 350, 189 ), 902 => array( 249, 249 ) ) as $id => $prices ) {
    $v = xw_savings_qa_product( $prices[0], $prices[1], null, true ); $v->set_parent_id( 900 ); $v->set_id( $id );
    $choices[] = xw_product_price_variation( array( 'variation_id' => $id ), $parent, $v );
}
$categories = Elementor\Plugin::$instance->elements_manager->get_categories();
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Precio en oferta · prueba local</title><link rel="stylesheet" href="../assets/product-price/product-price.css"><style>
body{margin:0;color:#303747;background:#f7f8fa;font:16px/1.5 Arial,sans-serif}main{max-width:900px;padding:28px 24px;margin:auto}h1{font-size:26px;margin:0 0 8px}h2{font-size:17px;margin:0 0 18px}.intro{color:#687184;margin:0 0 24px}.examples{display:grid;grid-template-columns:1fr 1fr;gap:20px}.sample{min-width:0;background:#fff;border:1px solid #e1e4e8;border-radius:8px;padding:24px}.sample label{display:block;margin-top:20px;font-size:14px}select{font:inherit;padding:8px;width:100%;max-width:100%;margin-top:6px}.custom .xw-product-price__current{color:#17683d;font-size:36px;text-shadow:0 1px 1px #ddd}.custom .xw-product-price__original{color:#9a5d36;font-size:18px}.bottom .xw-product-price{align-items:flex-end}.top .xw-product-price{align-items:flex-start}.center .xw-product-price{justify-content:center}.right .xw-product-price{justify-content:flex-end}.xw-product-price__current small{font-size:14px}.category{margin-top:24px;font-size:14px}button{font:inherit;padding:8px 14px;margin-top:10px}.screen-reader-text{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(1px,1px,1px,1px)}@media(max-width:650px){main{padding:20px 16px}.examples{grid-template-columns:1fr}.sample{padding:20px}}
</style></head><body><main><h1>Precio en oferta</h1><p class="intro">Widget real con WooCommerce y Elementor. Productos de ejemplo sin guardar.</p><div class="examples">
<section class="sample" id="sale"><h2>Con oferta · alineación al medio</h2><?php price_preview( xw_savings_qa_product( 350, 189 ) ); ?></section>
<section class="sample" id="regular"><h2>Sin oferta · mismo estilo principal</h2><?php price_preview( xw_savings_qa_product( 350, 350, '' ) ); ?></section>
<section class="sample" id="custom"><h2>Estilos independientes · abajo y derecha</h2><?php price_preview( xw_savings_qa_product( 350, 189 ), array( 'current_color' => '#17683d', 'current_typography_typography' => 'custom', 'current_typography_font_size' => array( 'size' => 36, 'unit' => 'px' ), 'original_color' => '#9a5d36', 'original_typography_typography' => 'custom', 'original_typography_font_size' => array( 'size' => 18, 'unit' => 'px' ), 'vertical_alignment' => 'flex-end', 'alignment' => 'flex-end' ) ); ?></section>
<section class="sample" id="top"><h2>Arriba y centrado</h2><?php price_preview( xw_savings_qa_product( 350, 189 ), array( 'vertical_alignment' => 'flex-start', 'alignment' => 'center' ) ); ?></section>
<section class="sample" id="variable"><h2>Producto variable</h2><?php price_preview( $parent ); ?><form class="variations_form" data-product_id="900" data-product_variations="<?php echo esc_attr( wp_json_encode( $choices ) ); ?>"><label for="variant">Variación</label><select id="variant"><option value="">Elige una opción</option><option value="901">Con oferta · 189</option><option value="902">Sin oferta · 249</option></select><input type="hidden" class="variation_id" value=""></form></section>
<section class="sample"><h2>Categorías del panel</h2><ol id="categories"></ol><button id="rebuild" type="button">Cambiar documento</button></section>
</div></main><script src="../build/12ui-form-emails/jquery.min.js"></script><script src="<?php echo esc_url( 'http://127.0.0.1:8893/wp-underscore.js' ); ?>"></script><script src="http://127.0.0.1:8893/wp-backbone.js"></script><script>
window.elementor={hooks:{filters:{},addFilter:function(n,fn){this.filters[n]=fn;}}};
</script><script src="../assets/elementor-widgets/editor.js"></script><script src="../assets/product-price/product-price.js"></script><script>
(function($){var categories=<?php echo wp_json_encode( $categories ); ?>;function renderCategories(){var collection=new Backbone.Collection(Object.keys(categories).map(function(key){return {name:key,title:categories[key].title};}));var views={categories:{options:{collection:collection}}};elementor.hooks.filters['panel/elements/regionViews'](views);$('#categories').empty();collection.each(function(model){$('#categories').append($('<li>').text(model.get('name')+' · '+model.get('title')));});}renderCategories();$('#rebuild').on('click',renderCategories);$('#variant').on('change',function(){var form=this.form;$(form).find('.variation_id').val(this.value);var data=$(form).data('product_variations').find(function(v){return String(v.variation_id)===$('#variant').val();});$(form).trigger(data?'found_variation':'reset_data',data?[data]:[]);});})(jQuery);
</script></body></html>
