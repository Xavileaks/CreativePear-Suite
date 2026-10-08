<?php
if (PHP_SAPI !== 'cli' || empty($argv[1])) exit("Supply an isolated WordPress path.\n");
require rtrim($argv[1], '/\\') . '/wp-load.php';
if (wp_get_environment_type() !== 'local') exit("Local environment required.\n");
$settings = xw_get_settings();
$enabled = ($argv[2] ?? 'check') === 'on';
$settings['features']['elementor_wishlist'] = $enabled ? 1 : 0;
update_option('xw_settings', $settings);
$types = Elementor\Plugin::$instance->widgets_manager->get_widget_types();
$count = count(array_filter(array_keys($types), static function($name) { return strpos($name, 'xw-wishlist-') === 0; }));
if ($count !== ($enabled ? 3 : 0)) throw new RuntimeException('Unexpected widget registration count '. $count);
xw_wishlist_register_assets();
if (wp_script_is('xw-wishlist', 'registered') !== $enabled) throw new RuntimeException('Unexpected asset registration');
echo 'PASS: feature '. ($enabled ? 'on registers three widgets' : 'off registers no wishlist widgets/assets') . "\n";
