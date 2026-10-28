<?php
/** Loopback UI harness: no session is created and every email is intercepted. */
if(!in_array($_SERVER['REMOTE_ADDR']??'',array('127.0.0.1','::1'),true)){http_response_code(403);exit;}
if('POST'===($_SERVER['REQUEST_METHOD']??'')||isset($_GET['action']))define('DOING_AJAX',true);
$GLOBALS['wp_filter']['option_active_plugins'][10][]=array('function'=>static function(){return array('elementor/elementor.php','xleon-suite/xleon-suite.php');},'accepted_args'=>1);
foreach(array('home','siteurl') as $key)$GLOBALS['wp_filter']['pre_option_'.$key][10][]=array('function'=>static function(){return 'http://127.0.0.1:8097';},'accepted_args'=>1);
require dirname(__DIR__,4).'/wp-load.php';$state=get_option('xw_management_qa');if('local'!==wp_get_environment_type()||!$state){http_response_code(403);exit;}
add_filter('pre_wp_mail',static function(){return true;},PHP_INT_MAX);
$role='staff'===($_GET['role']??'')?'staff':'owner';
$owners=get_users(array('role'=>'administrator','number'=>10,'fields'=>'ID'));$owner=0;foreach($owners as $candidate){if(!xw_appt_restricted_user($candidate)){$owner=$candidate;break;}}
wp_set_current_user($role==='staff'?$state['staff']:$owner);add_filter('locale',static function(){return 'es_ES';});add_filter('determine_locale',static function(){return 'es_ES';});
$base='http://127.0.0.1:8097/wp-content/plugins/xleon-suite/tests/appointments-management-fixture.php';$endpoint=add_query_arg('role',$role,$base);
add_filter('admin_url',static function($url,$path)use($base,$role){return str_starts_with($path,'admin.php?')?$base.'?role='.$role.'&'.substr($path,10):$url;},10,2);
if('POST'===($_SERVER['REQUEST_METHOD']??'')&&($_POST['action']??'')==='xw_appt_admin_save'){xw_appt_admin_save();exit;}
if('POST'===($_SERVER['REQUEST_METHOD']??'')&&($_POST['action']??'')==='xw_appt_access_save'){xw_appt_access_save();exit;}
if(($_GET['action']??'')==='xw_appt_admin_availability'){xw_appt_admin_availability();exit;}
if(($_GET['action']??'')==='xw_appt_availability'){xw_appt_availability();exit;}
xw_appt_admin_assets('toplevel_page_xw-appointments');wp_add_inline_script('xw-appointments-admin','xwAppointmentAdmin.url='.wp_json_encode($endpoint).';','before');wp_add_inline_script('xw-appointments','xwAppointments.url='.wp_json_encode($endpoint).';','before');
wp_enqueue_style('common');wp_enqueue_style('forms');wp_enqueue_style('buttons');
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Citas · revisión local</title><?php wp_print_styles();?><style>body{font:13px/1.5 system-ui;background:#f0f0f1;margin:0;padding:24px;color:#1d2327}.wrap h1{font-size:28px}.screen-reader-text{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}@media(max-width:600px){body{padding:12px}}</style></head><body class="wp-core-ui <?php echo $role==='staff'?'xw-appt-restricted':'';?>"><p>Prueba local con datos ficticios. Los correos están interceptados.</p><?php xw_appt_admin_page();wp_print_footer_scripts();?></body></html>
