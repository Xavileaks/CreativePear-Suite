<?php
/** Switch between suite builds ONLY in an isolated local WordPress test install. */
if (PHP_SAPI !== 'cli' || empty($argv[1])) { exit("Supply local WordPress path and suite slug.\n"); }
require rtrim($argv[1], '/\\') . '/wp-load.php';
if ('local' !== wp_get_environment_type()) { exit("Local environment required.\n"); }
$plugins=array('creativepear-suite'=>'creativepear-suite/creativepear-suite.php','xleon-suite'=>'xleon-suite/xleon-suite.php');
$slug=$argv[2] ?? '';
if (!isset($plugins[$slug]) || !is_file(WP_PLUGIN_DIR.'/'.$plugins[$slug])) { throw new RuntimeException('Unknown/missing local suite'); }
$active=array_values(array_diff(get_option('active_plugins',array()),array_values($plugins)));
$active[]=$plugins[$slug]; update_option('active_plugins',$active);
echo "PASS: local test profile selected: $slug\n";
