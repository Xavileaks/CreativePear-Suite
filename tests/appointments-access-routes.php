<?php
/** Exercise the real early route gate on isolated local WP; no authentication/session is created. */
if(PHP_SAPI!=='cli'||empty($argv[1]))exit;
$mode=$argv[2]??'frontend';if($mode==='admin')define('WP_ADMIN',true);if($mode==='ajax'){define('WP_ADMIN',true);define('DOING_AJAX',true);}
$GLOBALS['wp_filter']['option_active_plugins'][10][]=array('function'=>static function(){return array('elementor/elementor.php','xleon-suite/xleon-suite.php');},'accepted_args'=>1);
$GLOBALS['wp_filter']['pre_option_xw_settings'][10][]=array('function'=>static function(){return array('features'=>array('elementor_appointments'=>1));},'accepted_args'=>1);
require rtrim($argv[1],'/\\').'/wp-load.php';if(wp_get_environment_type()!=='local')exit;
require_once ABSPATH.'wp-admin/includes/user.php';$original=get_option('xw_appointment_access_emails',null);$user=0;
function appt_route_expect($expected,$script,$method,$get=array(),$post=array(),$uri='/'){
 global $pagenow;$pagenow=$script;$_SERVER['REQUEST_METHOD']=$method;$_GET=$get;$_POST=$post;$_SERVER['REQUEST_URI']=$uri;$result='allowed';
 ob_start();try{xw_appt_restrict_request();}catch(RuntimeException $e){$result=$e->getMessage();}ob_end_clean();
 if($result!==$expected)throw new RuntimeException("Expected $expected for $script $method, got $result");echo "PASS: $script $method $uri => $expected\n";
}
add_filter('wp_redirect',static function($url){throw new RuntimeException($url===xw_appt_admin_url()?'redirect-agenda':'wrong-redirect');});
foreach(array('wp_die_handler','wp_die_ajax_handler','wp_die_json_handler') as $hook)add_filter($hook,static function(){return static function(){throw new RuntimeException('denied');};});
try{
 $user=wp_insert_user(array('user_login'=>'appt-route-'.wp_generate_password(8,false),'user_email'=>'appt-route-'.wp_generate_password(8,false).'@example.invalid','user_pass'=>wp_generate_password(40),'role'=>'administrator'));if(is_wp_error($user))throw new RuntimeException('test account failed');
 update_option('xw_appointment_access_emails',array(strtolower(get_userdata($user)->user_email)));wp_set_current_user($user);
 if($mode==='admin'){
  appt_route_expect('allowed','admin.php','GET',array('page'=>'xw-appointments'));
  appt_route_expect('redirect-agenda','users.php','GET');appt_route_expect('redirect-agenda','options-general.php','GET');appt_route_expect('denied','profile.php','POST');appt_route_expect('denied','admin.php','POST',array('page'=>'xw-appointments'));
 }elseif($mode==='ajax'){
  appt_route_expect('allowed','admin-ajax.php','POST',array(),array('action'=>'xw_appt_admin_save'));appt_route_expect('allowed','admin-ajax.php','GET',array('action'=>'xw_appt_admin_availability'));appt_route_expect('denied','admin-ajax.php','POST',array(),array('action'=>'xw_appt_access_save'));appt_route_expect('denied','admin-ajax.php','POST',array(),array('action'=>'edit_user'));
 }else{
  appt_route_expect('redirect-agenda','index.php','GET',array(),array(),'/my-account/');appt_route_expect('denied','index.php','POST',array(),array('action'=>'save_account_details'),'/my-account/');appt_route_expect('denied','index.php','GET',array(),array(),'/wp-json/wp/v2/users');appt_route_expect('denied','index.php','GET',array('rest_route'=>'/wp/v2/users'));appt_route_expect('denied','index.php','GET',array('wc-ajax'=>'checkout'));appt_route_expect('allowed','wp-login.php','GET',array('action'=>'logout'));
 }
}finally{wp_set_current_user(0);delete_option('xw_appointment_access_emails');if($user&&!is_wp_error($user))wp_delete_user($user);if(null!==$original)update_option('xw_appointment_access_emails',$original);}
