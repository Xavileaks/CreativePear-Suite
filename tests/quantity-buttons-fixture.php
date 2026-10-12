<?php
/** Browser-only, read-only fixture, excluded from the published plugin. */
require rtrim( $_SERVER['DOCUMENT_ROOT'], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { http_response_code( 403 ); exit; }
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Quantity controls — local QA</title><?php wp_head(); ?>
<style>body{margin:0;padding:24px;font-family:system-ui;color:#1d2327;background:#f0f0f1}main{max-width:800px;margin:auto}section{padding:20px;background:white;border:1px solid #dcdcde;border-radius:10px;margin:16px 0}h1{font-size:24px}h2{font-size:18px;margin:0 0 16px}label{display:block;margin:8px 0}input{font:inherit}button{font:inherit}#native-events{font-size:14px}</style></head>
<body><main><h1>Quantity Plus Minus Button</h1><p>Read-only local fixture: product limits, cart zero and decimal steps.</p>
<section><h2>Product · minimum 1, maximum 3</h2><?php woocommerce_quantity_input( array( 'input_id' => 'qa-product-qty', 'input_name' => 'product_quantity', 'input_value' => 1, 'min_value' => 1, 'max_value' => 3, 'step' => 1, 'product_name' => 'QA product' ), null ); ?></section>
<section><h2>Cart · minimum 0</h2><?php woocommerce_quantity_input( array( 'input_id' => 'qa-cart-qty', 'input_name' => 'cart_quantity', 'input_value' => 1, 'min_value' => 0, 'max_value' => '', 'step' => 1, 'product_name' => 'QA cart product' ), null ); ?></section>
<section><h2>Decimal · step 0.1</h2><?php woocommerce_quantity_input( array( 'input_id' => 'qa-decimal-qty', 'input_name' => 'decimal_quantity', 'input_value' => '0.2', 'min_value' => '0.1', 'max_value' => '1', 'step' => '0.1', 'product_name' => 'Decimal QA product' ), null ); ?></section>
<section><h2>Existing theme controls · no duplicates</h2><div class="quantity"><button type="button" class="minus" disabled>−</button><label class="screen-reader-text" for="qa-theme-qty">Theme quantity</label><input type="number" class="qty" id="qa-theme-qty" value="1" min="1"><button type="button" class="plus" disabled>+</button></div></section>
<p id="native-events" role="status">Quantity changes: 0</p>
</main><?php wp_footer(); ?><script>let count=0;document.addEventListener('change',e=>{if(e.target.matches('input.qty'))document.querySelector('#native-events').textContent='Quantity changes: '+(++count)});</script></body></html>
