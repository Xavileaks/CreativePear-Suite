<?php
require __DIR__ . '/product-savings-bootstrap.php';
add_filter( 'locale', static function() { return 'es_ES'; } );
add_filter( 'determine_locale', static function() { return 'es_ES'; } );
add_filter( 'pre_option_woocommerce_currency', static function() { return 'EUR'; } );
add_filter( 'pre_option_woocommerce_currency_pos', static function() { return 'right'; } );
add_filter( 'pre_option_woocommerce_price_decimal_sep', static function() { return ','; } );
add_filter( 'pre_option_woocommerce_price_thousand_sep', static function() { return '.'; } );
add_filter( 'woocommerce_price_trim_zeros', '__return_true' );
Elementor\Plugin::$instance->widgets_manager->get_widget_types();
$mode = $_GET['mode'] ?? '';
if ( 'settings' === $mode ) {
    require_once ABSPATH . 'wp-admin/includes/admin.php';
    $users = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) ); wp_set_current_user( (int) $users[0] );
    ob_start(); xw_render_settings_page(); $html = ob_get_clean();
    $dom = new DOMDocument(); @$dom->loadHTML( '<?xml encoding="UTF-8">' . $html ); $xpath = new DOMXPath( $dom );
    $section = $xpath->query( '//section[header/h2[@id="xw-section-woocommerce"]]' )->item( 0 );
    ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ajustes · Valor relativo y absoluto</title><link rel="stylesheet" href="../assets/css/admin-settings.css"><style>body{background:#f0f0f1;font:13px/1.5 system-ui;padding:24px}.screen-reader-text{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(1px,1px,1px,1px)}.xw-options-toggle .dashicons{display:inline-block;width:8px;height:8px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(45deg)}</style></head><body><div class="xw-settings-wrap"><form data-xw-settings-form><?php if ( $section ) { echo $dom->saveHTML( $section ); } ?></form></div><script src="../build/12ui-form-emails/jquery.min.js"></script><script src="../assets/js/admin-settings.js"></script></body></html><?php exit;
}
$parent = new WC_Product_Variable(); $parent->set_id( 900 );
$choices = array();
foreach ( array( 901 => array( 100, 80 ), 902 => array( 100, 100 ), 903 => array( 200, 150 ) ) as $id => $prices ) {
    $variation = xw_savings_qa_product( $prices[0], $prices[1], null, true ); $variation->set_parent_id( 900 ); $variation->set_id( $id );
    $choices[] = apply_filters( 'woocommerce_available_variation', array( 'variation_id' => $id ), $parent, $variation );
}
function savings_preview_widget( $product, $mode, $label = 'Ahorra' ) {
    $GLOBALS['product'] = $product;
    $widget = new XW_Product_Savings_Widget( array( 'id' => 'qa' . wp_rand( 1000, 9999 ), 'elType' => 'widget', 'widgetType' => 'xw-product-savings', 'settings' => array( 'value_mode' => $mode, 'label' => $label ) ), array() );
    $widget->render_content();
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Valor relativo y absoluto · prueba local</title><link rel="stylesheet" href="../assets/product-savings/product-savings.css"><style>
body{margin:0;color:#303747;background:#f7f8fa;font:16px/1.5 system-ui}main{max-width:940px;margin:auto;padding:32px 24px}h1{font-size:26px;margin:0 0 8px}h2{font-size:20px;margin:4px 0 10px}.intro{color:#687184;margin:0 0 28px}.examples{display:grid;grid-template-columns:1fr 1fr;gap:20px}.sample,.variable{background:white;border:1px solid #e1e4e8;border-radius:8px;padding:24px}.sample>p{color:#687184;font-size:14px;margin:0 0 8px}.price{font-size:23px;font-weight:700;margin:0 0 8px}.price del{color:#8e94a1;font-size:18px;font-weight:400;margin-right:6px}.variable{margin-top:24px}.variations_form select{font:inherit;max-width:100%;padding:9px;margin-bottom:16px}.variant-values{display:flex;gap:12px;align-items:center;flex-wrap:wrap;min-height:26px}.no-sale{color:#687184;font-size:14px}.custom .xw-product-savings__badge{background:#edf7f0;color:#215d36;border:1px solid #b6d9c0;border-radius:18px;padding:6px 12px}.isolated{font-size:13px;margin-top:20px;color:#687184}.xw-product-savings .xw-product-savings__badge{font-size:12px}@media(max-width:650px){main{padding:20px 16px}.examples{grid-template-columns:1fr}.sample,.variable{padding:20px}}
</style></head><body><main><h1>Valor relativo y absoluto</h1><p class="intro">Widget real de Elementor con productos de ejemplo. Sin modificar precios, productos ni ajustes.</p><div class="examples"><section class="sample" id="relative-example"><p>Valor relativo · porcentaje</p><h2>Producto con oferta</h2><div class="price"><del>45,00€</del>39,99€</div><?php savings_preview_widget( xw_savings_qa_product( 45, '39.99' ), 'relative' ); ?></section><section class="sample" id="absolute-example"><p>Valor absoluto · importe</p><h2>Producto con oferta</h2><div class="price"><del>450,00€</del>390,00€</div><?php savings_preview_widget( xw_savings_qa_product( 450, 390 ), 'absolute' ); ?></section></div>
<section class="variable product"><h2>Producto variable</h2><form class="variations_form" data-product_id="900" data-product_variations="<?php echo esc_attr( wp_json_encode( isset( $_GET['ajax'] ) ? false : $choices ) ); ?>" onsubmit="return false"><label for="qa-variation">Elige una variación</label><br><select id="qa-variation"><option value="">Selecciona una opción</option><option value="901">Oferta de 100€ a 80€</option><option value="902">Sin oferta · 100€</option><option value="903">Oferta de 200€ a 150€</option></select><input class="variation_id" type="hidden" value=""></form><div class="variant-values" id="variant-values"><?php savings_preview_widget( $parent, 'relative' ); savings_preview_widget( $parent, 'absolute' ); ?></div><p class="no-sale">Sin oferta o sin variación elegida, las etiquetas se ocultan.</p><div class="e-loop-item isolated" id="isolated-loop">Instancia del mismo producto en otro Loop (debe permanecer vacía): <?php savings_preview_widget( $parent, 'relative' ); ?></div></section>
<section class="variable custom"><h2>Ejemplo de estilo personalizado</h2><?php savings_preview_widget( xw_savings_qa_product( 100, 80 ), 'relative', 'Tu ahorro' ); ?><p class="no-sale">Texto, color, fondo, borde, redondeado y tipografía se configuran en cada widget.</p></section></main><script src="../build/12ui-form-emails/jquery.min.js"></script><script src="../assets/product-savings/product-savings.js"></script><script>
const savingsChoices=<?php echo wp_json_encode( $choices ); ?>;jQuery('#qa-variation').on('change',function(){const form=jQuery(this).closest('form');form.find('.variation_id').val(this.value);const item=savingsChoices.find(v=>String(v.variation_id)===this.value);form.trigger(item?'found_variation':'reset_data',item?[item]:[])});
</script></body></html>
