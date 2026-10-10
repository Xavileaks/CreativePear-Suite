<?php
/** CLI only; isolated local WordPress. Restores all settings after endpoint tests. */
if (PHP_SAPI !== 'cli' || empty($argv[1])) { exit("Supply an isolated WordPress directory.\n"); }
define('DOING_AJAX', true);
require rtrim($argv[1], '/\\') . '/wp-load.php';
if ('local' !== wp_get_environment_type()) { exit("Local environment required.\n"); }
xw_register_settings();
$admins = get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
$admin = (int) $admins[0];
$original = get_option('xw_settings');
add_filter('wp_die_ajax_handler', static function() { return static function() { throw new RuntimeException('qa-json-end'); }; });
function qa_save_request($input, $nonce = null, $complete = true) {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = array('xw_settings'=>wp_slash($input), '_wpnonce'=>$nonce ?? wp_create_nonce('xw_settings_group-options'));
    if ($complete) { $_POST['xw_settings_complete'] = '1'; }
    $_REQUEST = $_POST;
    ob_start();
    try { xw_ajax_save_settings(); } catch (RuntimeException $e) {
        if ($e->getMessage() !== 'qa-json-end') { ob_end_clean(); throw $e; }
    }
    return json_decode(ob_get_clean(), true);
}
function qa_save_check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
    echo "PASS: $message\n";
}
try {
    wp_set_current_user($admin);
    $input = xw_get_settings();
    $input['login']['logo_width'] = 9999;
    $input['features']['woocommerce_cart_css'] = 0;
    $input['woocommerce']['extra_fee_rules'] = array();
    $input['admin_branding']['footer_html'] = '<a href="https://example.com">Pear\'s test</a><script>alert(1)</script>';
    $expected = xw_sanitize_settings($input);
    $result = qa_save_request($input);
    qa_save_check($result['success'] && get_option('xw_settings') === $expected, 'AJAX persists through the existing sanitizer');
    qa_save_check($result['data']['settings']['login']['logo_width'] === 600 && strpos($result['data']['settings']['admin_branding']['footer_html'], '<script') === false, 'ranges and allowed HTML are sanitized');
    qa_save_check($result['data']['settings']['woocommerce']['extra_fee_rules'] === array() && !$result['data']['settings']['features']['woocommerce_cart_css'], 'empty dynamic lists and switched-off features remain saved');
    qa_save_check(wp_verify_nonce($result['data']['nonce'], 'xw_settings_group-options'), 'successful response refreshes nonce');
    qa_save_check(qa_save_request($input)['success'], 'unchanged settings are a successful idempotent save');
    qa_save_check(!qa_save_request($input, 'invalid')['success'] && get_option('xw_settings') === $expected, 'invalid nonce cannot change settings');
    $expired = qa_save_request($input, 'expired');
    qa_save_check(wp_verify_nonce($expired['data']['nonce'], 'xw_settings_group-options') && qa_save_request($input, $expired['data']['nonce'])['success'], 'expired nonce is renewed for a manual retry without reloading');
    qa_save_check(!qa_save_request($input, null, false)['success'] && get_option('xw_settings') === $expected, 'truncated request cannot reset settings');
    qa_save_check(!qa_save_request('malformed')['success'] && get_option('xw_settings') === $expected, 'non-array settings are rejected');
    wp_set_current_user(0);
    qa_save_check(!qa_save_request($input)['success'] && get_option('xw_settings') === $expected, 'users without manage_options cannot save');
    wp_set_current_user($admin);
    $reject = static function($value, $old) { return $old; };
    add_filter('pre_update_option_xw_settings', $reject, 999, 2);
    $input['login']['logo_width'] = 123;
    qa_save_check(!qa_save_request($input)['success'] && get_option('xw_settings') === $expected, 'storage failure is not reported as saved');
    remove_filter('pre_update_option_xw_settings', $reject, 999);
} finally {
    if (isset($reject)) { remove_filter('pre_update_option_xw_settings', $reject, 999); }
    update_option('xw_settings', $original);
}
