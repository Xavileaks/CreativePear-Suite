<?php
/** Cart rejection/storage recovery checks. Isolated local WordPress only. */
if (PHP_SAPI !== 'cli' || empty($argv[1])) { exit("CLI: supply an isolated WordPress directory.\n"); }
define('DOING_AJAX', true);
require rtrim($argv[1], '/\\') . '/wp-load.php';
if ('local' !== wp_get_environment_type()) { exit("Refusing a non-local installation.\n"); }
$ids = get_option('wl_qa_product_ids');
if (!$ids) { throw new RuntimeException('Run wishlist-integration.php first.'); }
$admins = get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
wp_set_current_user((int) $admins[0]);
if (!WC()->cart) { wc_load_cart(); }
$owner = xw_wishlist_owner();
$original = xw_wishlist_get_row($owner);
$original_items = $original ? json_decode($original['items'], true) : array();
add_filter('wp_die_ajax_handler', static function() { return static function() { throw new RuntimeException('qa-json-end'); }; });
function wl_cart_ajax($id) {
    $_POST = array('operation'=>'cart','ids'=>array($id),'nonce'=>wp_create_nonce('xw_wishlist_write'));
    $_REQUEST = $_POST;
    ob_start();
    try { xw_wishlist_ajax(); } catch (RuntimeException $error) {
        if ($error->getMessage() !== 'qa-json-end') { ob_end_clean(); throw $error; }
    }
    $json = json_decode(ob_get_clean(), true);
    if (!$json || !$json['success']) { throw new RuntimeException('Expected successful JSON response'); }
    return $json['data'];
}
function wl_cart_check($condition, $name) {
    if (!$condition) { throw new RuntimeException($name); }
    echo "PASS: $name\n";
}
try {
    WC()->cart->empty_cart();
    xw_wishlist_change($owner, static function() use ($ids) { return array(array('id'=>$ids[0],'added'=>100)); });
    $reject = static function() { return false; };
    add_filter('woocommerce_add_to_cart_validation', $reject, 999);
    $result = wl_cart_ajax($ids[0]);
    remove_filter('woocommerce_add_to_cart_validation', $reject, 999);
    wl_cart_check($result['added'] === 0 && $result['count'] === 1 && WC()->cart->get_cart_contents_count() === 0, 'WooCommerce validation rejection preserves wishlist');
    $exception = static function() { throw new Exception('QA rejection'); };
    add_filter('woocommerce_add_to_cart_validation', $exception, 999);
    $result = wl_cart_ajax($ids[0]);
    remove_filter('woocommerce_add_to_cart_validation', $exception, 999);
    wl_cart_check($result['added'] === 0 && $result['count'] === 1, 'WooCommerce exceptions preserve wishlist');
    $table = xw_wishlist_table_name();
    $fail_save = static function($query) use ($table) { return strpos($query, "UPDATE `$table`") === 0 ? '' : $query; };
    add_filter('query', $fail_save, 999);
    $result = wl_cart_ajax($ids[0]);
    remove_filter('query', $fail_save, 999);
    wl_cart_check($result['added'] === 1 && $result['cleanup_failed'] && $result['count'] === 1 && WC()->cart->get_cart_contents_count() === 1, 'Storage failure preserves list, confirms cart and reports recovery without retrying');
    wc_clear_notices();
} finally {
    if (isset($reject)) { remove_filter('woocommerce_add_to_cart_validation', $reject, 999); }
    if (isset($exception)) { remove_filter('woocommerce_add_to_cart_validation', $exception, 999); }
    if (isset($fail_save)) { remove_filter('query', $fail_save, 999); }
    xw_wishlist_change($owner, static function() use ($original_items) { return $original_items; });
    WC()->cart->empty_cart();
}
