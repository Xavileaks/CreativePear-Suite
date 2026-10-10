<?php
/** Local, read-only CSS regression fixture. CLI validates rules; browser verifies layout. */
$root = dirname(__DIR__);
$main = is_file($root . '/creativepear-suite.php') ? 'creativepear-suite.php' : 'xleon-suite.php';
$cart = file_get_contents($root . '/assets/css/woocommerce-cart.css');
$checkout = file_get_contents($root . '/assets/css/woocommerce-checkout.css');
$source = file_get_contents($root . '/' . $main);
if (!preg_match('/<style id="xw-checkout-product-images">(.*?)<\/style>/s', $source, $match)) {
    throw new RuntimeException('Standalone checkout image styles missing');
}
$inline = $match[1];
if (PHP_SAPI === 'cli') {
    foreach (array($cart, $checkout, $inline) as $css) {
        if (!preg_match('/aspect-ratio:\s*1\s*\/\s*1\s*!important/', $css) || !preg_match('/object-fit:\s*contain\s*!important/', $css)) {
            throw new RuntimeException('Square contain rules missing');
        }
    }
    foreach (array('body.woocommerce-cart .woocommerce-cart-form .product-thumbnail img', '.woocommerce-mini-cart .woocommerce-mini-cart-item img', '.elementor-menu-cart__product-image img') as $selector) {
        if (strpos($cart, $selector) === false) { throw new RuntimeException('Missing selector: ' . $selector); }
    }
    if (!preg_match('/height:\s*auto\s*!important/', $cart)) { throw new RuntimeException('Theme height must not defeat the ratio'); }
    if (strpos($checkout, 'object-fit: cover') !== false || strpos($inline, 'object-fit: cover') !== false) { throw new RuntimeException('Checkout still crops images'); }
    echo "PASS: square contain rules cover cart, both mini carts and both checkout modes\n";
    exit;
}
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', array('127.0.0.1', '::1'), true)) { http_response_code(403); exit; }
function qa_image($shape) {
    $dimensions = array('Portrait' => array(200, 400), 'Landscape' => array(400, 200), 'Square' => array(200, 200));
    list($w, $h) = $dimensions[$shape];
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '"><rect x="3" y="3" width="' . ($w-6) . '" height="' . ($h-6) . '" fill="#dcebef" stroke="#375b70" stroke-width="6"/><path d="M0 0 L' . $w . ' ' . $h . ' M0 ' . $h . ' L' . $w . ' 0" stroke="#9dbac3" stroke-width="4"/><text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" font-size="24" fill="#234350">' . $shape . '</text></svg>';
    return '<img alt="' . $shape . ' test image" width="' . $w . '" height="' . $h . '" src="data:image/svg+xml;base64,' . base64_encode($svg) . '">';
}
$shapes = array('Portrait', 'Landscape', 'Square');
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>WooCommerce square image QA</title>
<style>
body { font: 16px/1.4 system-ui; margin: 24px; color: #234350; }
h1 { font-size: 22px; } h2 { font-size: 16px; margin: 12px 0; }
section { border: 1px solid #cbd9de; border-radius: 8px; padding: 12px; margin-bottom: 12px; }
.samples { display: flex; gap: 20px; flex-wrap: wrap; }
table { border-collapse: collapse; } td { padding: 0 10px 0 0; }
img { background: #fafafa; outline: 1px dashed #9dbac3; }
.woocommerce-cart-form .product-thumbnail img { width: 92px; height: 160px; object-fit: cover !important; }
.woocommerce-mini-cart { display:flex; gap:20px; padding:0; list-style:none; }
.woocommerce-mini-cart-item img { width:74px; height:120px; object-fit:cover !important; }
.elementor-menu-cart__product-image img { width:80px; height:140px; object-fit:cover !important; }
.gallery-control { width:120px; height:160px; object-fit:cover; }
iframe { width:100%; height:140px; border:0; }
</style><style><?= $cart ?></style><style><?= $inline ?></style><style><?= $checkout ?></style></head>
<body class="woocommerce-cart woocommerce-checkout">
<h1>WooCommerce · square images / contain</h1>
<p>Portrait, landscape and square sources. Existing widths stay unchanged.</p>
<section><h2>Cart page · 92 × 92</h2><form class="woocommerce-cart-form"><table><tr><?php foreach ($shapes as $shape): ?><td class="product-thumbnail"><?= qa_image($shape) ?></td><?php endforeach; ?></tr></table></form></section>
<section><h2>Floating cart · native 74 × 74 / Elementor 80 × 80</h2><div class="samples"><ul class="woocommerce-mini-cart"><?php foreach ($shapes as $shape): ?><li class="woocommerce-mini-cart-item"><?= qa_image($shape) ?></li><?php endforeach; ?></ul><div class="elementor-menu-cart__product-image"><?= qa_image('Portrait') ?></div></div></section>
<section><h2>Checkout with layout CSS · 64 desktop / 58 mobile</h2><table class="woocommerce-checkout-review-order-table"><tr><?php foreach ($shapes as $shape): ?><td><span class="xw-checkout-product-media"><?= str_replace('<img ', '<img class="xw-checkout-product-thumbnail" ', qa_image($shape)) ?></span></td><?php endforeach; ?></tr></table></section>
<?php
$standalone = '<!doctype html><html><head><style>body{font:16px system-ui;color:#234350}img{outline:1px dashed #9dbac3}' . $inline . '</style></head><body><p>Checkout images alone · 55 × 55</p><table class="woocommerce-checkout-review-order-table"><tr>';
foreach ($shapes as $shape) { $standalone .= '<td><span class="xw-checkout-product-media">' . str_replace('<img ', '<img class="xw-checkout-product-thumbnail" ', qa_image($shape)) . '</span></td>'; }
$standalone .= '</tr></table></body></html>';
?>
<iframe title="Standalone checkout images" srcdoc="<?= htmlspecialchars($standalone, ENT_QUOTES) ?>"></iframe>
<section><h2>Unrelated catalog image · unchanged 120 × 160</h2><?= str_replace('<img ', '<img class="gallery-control" ', qa_image('Portrait')) ?></section>
</body></html>

