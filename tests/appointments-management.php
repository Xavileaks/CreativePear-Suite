<?php
/** Isolated WordPress integration: synthetic accounts/records only; no mail is delivered. */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { exit( "Supply isolated WordPress directory.\n" ); }
$suite = 'creativepear' === ( $argv[2] ?? '' ) ? 'creativepear-suite' : 'xleon-suite';
$GLOBALS['wp_filter']['option_active_plugins'][10][] = array( 'function' => static function() use ( $suite ) { return array( 'elementor/elementor.php', $suite . '/' . $suite . '.php' ); }, 'accepted_args' => 1 );
define( 'DOING_AJAX', true ); define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { exit( 'Local environment required.' ); }
require_once ABSPATH . 'wp-admin/includes/user.php';
function appt_flow_check( $ok, $label ) { if ( ! $ok ) { throw new RuntimeException( $label ); } echo "PASS: $label\n"; }
add_filter( 'wp_die_ajax_handler', static function() { return static function() { throw new RuntimeException( 'appt-json-end' ); }; } );
function appt_flow_request( $callback, $post ) {
    $_SERVER['REQUEST_METHOD'] = 'POST'; $_POST = $post; $_GET = array(); $_REQUEST = $post;
    ob_start(); try { $callback(); } catch ( RuntimeException $e ) { if ( 'appt-json-end' !== $e->getMessage() ) { ob_end_clean(); throw $e; } }
    return json_decode( ob_get_clean(), true );
}
$original = array(); foreach ( array( 'xw_settings', 'xw_appointment_settings', 'xw_appointment_access_emails', 'timezone_string', 'WPLANG' ) as $key ) { $original[$key] = get_option( $key, null ); }
$test_locale='es_ES';add_filter('locale',static function($locale)use(&$test_locale){return $test_locale;});
$ids = array(); $users = array(); $mails = array(); $mail_fail = false;
add_filter( 'pre_wp_mail', static function( $return, $args ) use ( &$mails, &$mail_fail ) { $mails[] = $args; return ! $mail_fail; }, PHP_INT_MAX, 2 );
try {
    $settings = xw_get_settings(); $settings['features']['elementor_appointments'] = 1; update_option( 'xw_settings', $settings ); xw_appt_install(); update_option( 'timezone_string', 'Europe/Madrid' ); update_option( 'WPLANG', 'es_ES' ); unset($GLOBALS['locale']);
    $schedule = xw_appt_defaults(); $schedule['weekly'] = array_fill( 0, 7, '09:00-18:00' ); $schedule['notice'] = 0; $schedule['whatsapp_country_code'] = '58'; update_option( 'xw_appointment_settings', $schedule );
    foreach ( array( 'owner' => 'administrator', 'staffadmin' => 'administrator', 'staff' => 'subscriber' ) as $name => $role ) {
        $user = wp_insert_user( array( 'user_login' => 'appt-flow-' . $name . '-' . wp_generate_password( 6, false ), 'user_email' => 'appt-flow-' . $name . '-' . wp_generate_password( 6, false ) . '@example.invalid', 'user_pass' => wp_generate_password( 40 ), 'role' => $role ) );
        appt_flow_check( ! is_wp_error( $user ), 'synthetic ' . $name . ' account created' ); $users[$name] = $user;
    }
    $emails = array_map( static function( $id ) { return strtolower( get_userdata($id)->user_email ); }, array( $users['staffadmin'], $users['staff'] ) ); update_option( 'xw_appointment_access_emails', $emails );
    foreach ( array( 'staffadmin', 'staff' ) as $name ) {
        wp_set_current_user( $users[$name] );
        appt_flow_check( xw_appt_can_manage() && ! current_user_can('manage_options') && ! current_user_can('edit_posts') && ! current_user_can('edit_users') && ! current_user_can('install_plugins') && ! xw_appt_can_manage_access(), $name . ' can manage appointments only, regardless of role' );
        appt_flow_check( ! apply_filters('show_admin_bar', true) && is_wp_error( apply_filters('rest_authentication_errors', null) ) && ! apply_filters('wp_is_application_passwords_available_for_user', true, wp_get_current_user()), 'chrome, REST and application-password access denied' );
        appt_flow_check( xw_appt_admin_url() === apply_filters('login_redirect','/other','',wp_get_current_user()), 'login redirects to the agenda' );
        $reply = appt_flow_request('xw_appt_access_save', array('nonce'=>wp_create_nonce('xw_appointment_access'),'emails'=>'','revision'=>xw_appt_access_revision())); appt_flow_check( ! $reply['success'] && xw_appt_access_emails() === $emails, 'restricted account cannot remove its own restriction' );
    }
    appt_flow_check( get_userdata($users['staffadmin'])->roles === array('administrator'), 'persistent WordPress roles are unchanged' );
    wp_set_current_user( $users['owner'] ); appt_flow_check( current_user_can('manage_options') && xw_appt_can_manage_access(), 'unlisted administrator retains normal access' );
    appt_flow_check( xw_appt_restricted_route_allowed('admin.php','GET',array('page'=>'xw-appointments'),array()) && xw_appt_restricted_route_allowed('admin-ajax.php','POST',array(),array('action'=>'xw_appt_admin_save')), 'only agenda and its exact endpoints are allowed' );
    foreach ( array( array('users.php','GET',array(),array()), array('admin.php','POST',array('page'=>'xw-appointments'),array()), array('admin.php','GET',array('page'=>'xw-appointments','action'=>'delete'),array()), array('admin-ajax.php','POST',array(),array('action'=>'xw_appt_access_save')), array('admin-ajax.php','POST',array(),array('action'=>'woocommerce_save_account_details')) ) as $route ) { appt_flow_check( ! xw_appt_restricted_route_allowed(...$route), 'unrelated admin requests denied' ); }
    appt_flow_check( is_wp_error( xw_appt_parse_access_emails('broken email') ) && array('a@example.com') === xw_appt_parse_access_emails("A@example.com\na@example.com"), 'email list validates and normalizes without changing account data' );
    appt_flow_check( ! xw_appt_access_has_owner(array_merge($emails,array(strtolower(get_userdata($users['owner'])->user_email)),array_map('strtolower',get_users(array('capability'=>'manage_options','fields'=>'user_email'))))), 'last unrestricted administrator cannot be locked out' );
    $nonce = wp_create_nonce('xw_appointment_access'); $reply = appt_flow_request('xw_appt_access_save',array('nonce'=>$nonce,'emails'=>implode("\n",$emails),'revision'=>'stale')); appt_flow_check(!$reply['success'],'stale access-list overwrite rejected');
    $reply = appt_flow_request('xw_appt_access_save',array('nonce'=>$nonce,'emails'=>implode("\n",$emails),'revision'=>xw_appt_access_revision())); appt_flow_check($reply['success'],'unrestricted administrator can save access');
    appt_flow_check( ! xw_appt_phone_digits_valid('11358101745') && ! xw_appt_phone_digits_valid('581358101745') && xw_appt_phone_digits_valid('34612345678'), 'invalid national phone patterns hide contact; valid Spanish format accepted' );
    global $wpdb; $table=xw_appt_table(); $tomorrow=current_datetime()->modify('+1 day')->format('Y-m-d'); $slots=xw_appt_available($tomorrow); $value=array_key_first($slots);
    $data = array('starts_at'=>$slots[$value]['start'],'ends_at'=>$slots[$value]['end'],'timezone'=>wp_timezone_string(),'status'=>'pending','customer_name'=>'QA Example','email'=>'wrong email','phone'=>'(135)810-1745','form_name'=>'QA Form','form_id'=>'appt-flow','fields'=>wp_json_encode(array(array('id'=>'person','label'=>'Person','type'=>'text','value'=>'QA Example','contact_role'=>'customer_name'),array('id'=>'email','label'=>'Email','type'=>'email','value'=>'wrong email','contact_role'=>'email'),array('id'=>'phone','label'=>'Phone','type'=>'tel','value'=>'(135)810-1745','contact_role'=>'phone'),array('id'=>'service','label'=>'Any service','value'=>'Service A','type'=>'select'),array('id'=>'note','label'=>'Notes','value'=>'Line 1','type'=>'textarea'),array('id'=>'when','label'=>'Appointment','value'=>$value,'type'=>'xw_appointment','role'=>'appointment'))),'created_at'=>gmdate('Y-m-d H:i:s'),'status_changed_at'=>null);
    $wpdb->insert($table,$data);$id=(int)$wpdb->insert_id;$ids[]=$id;
    $row = static function($id) use($wpdb,$table){return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$id),ARRAY_A);};
    $card=xw_appt_booking_card($row($id),'nonce'); appt_flow_check(!str_contains($card,'wa.me')&&!str_contains($card,'xw-appt-email-open')&&str_contains($card,'Service A')&&!str_contains($card,'<details'),'all ordinary fields visible; malformed contact buttons absent');
    $fields=xw_appt_record_fields($row($id));$input=array('revision'=>xw_appt_record_revision($row($id)),'fields'=>array(),'name_field'=>0,'status_changed_at'=>'');foreach($fields as $index=>$field){if('appointment'!==($field['role']??''))$input['fields'][$index]=$field['value'];}
    $input['fields'][1]='appointment-flow@example.invalid';$input['fields'][2]='0412 555 1234';$input['fields'][3]='Service B';$input['fields'][4]="New note\nsecond line";
    wp_set_current_user($users['staff']);$edited=xw_appt_details_update($id,$input);appt_flow_check(!is_wp_error($edited)&&$edited['email']===$input['fields'][1]&&str_contains($edited['fields'],'Service B'),'restricted staff can edit all ordinary submitted fields');
    appt_flow_check(is_wp_error(xw_appt_details_update($id,$input)),'stale field editing rejected');
    $card=xw_appt_booking_card($edited,'nonce');appt_flow_check(str_contains($card,'wa.me/584125551234?text=')&&str_contains($card,'xw-appt-email-open'),'corrected valid contacts reveal their buttons immediately');
    $input['revision']=xw_appt_record_revision($edited);$input['fields'][5]='bypass';appt_flow_check(is_wp_error(xw_appt_details_update($id,$input)),'appointment date cannot bypass availability via generic field editing');unset($input['fields'][5]);
    $input['name_field']=3;$edited=xw_appt_details_update($id,$input);if(is_wp_error($edited))fwrite(STDERR,$edited->get_error_message()."\n");appt_flow_check(!is_wp_error($edited)&&$edited['customer_name']==='Service B'&&str_contains(xw_appt_message_text($edited),'Hola Service B'),'chosen name field personalizes messages');
    $preview=xw_appt_message_request($id,array());appt_flow_check(!is_wp_error($preview)&&!$mails&&str_contains($preview['html'],'<table role="presentation"')&&str_contains($preview['html'],'email-received.png'),'email preview uses HTML design without sending');
    appt_flow_check(is_wp_error(xw_appt_message_request($id,array('subject'=>$preview['subject'],'text'=>$preview['text'],'preview_token'=>'bad','request_id'=>'qa-request-123456789'),true)),'email send requires matching preview token');
    $send=array('subject'=>$preview['subject'],'text'=>$preview['text'],'preview_token'=>$preview['preview_token'],'request_id'=>'qa-request-123456789','recipient'=>'attacker@example.invalid');
    $sent=xw_appt_message_request($id,$send,true);$again=xw_appt_message_request($id,$send,true);appt_flow_check(!is_wp_error($sent)&&!is_wp_error($again)&&count($mails)===1&&$mails[0]['to']===$edited['email']&&str_contains($mails[0]['headers'][0],'text/html'),'explicit send goes only to stored recipient and retries are deduplicated');
    $nonce=wp_create_nonce('xw_appointment_admin');$reply=appt_flow_request('xw_appt_admin_save',array('operation'=>'status','booking'=>$id,'nonce'=>$nonce,'status'=>'confirmed'));$confirmed=$row($id);appt_flow_check($reply['success']&&$confirmed['status_changed_at']!==null&&count($mails)===1,'status change records timestamp without sending mail');
    $count=count($mails);$reply=appt_flow_request('xw_appt_admin_save',array('operation'=>'message_send','booking'=>$id,'nonce'=>'bad'));appt_flow_check(!$reply['success']&&count($mails)===$count,'invalid nonce cannot send');
    appt_flow_check(is_wp_error(xw_appt_message_request($id,$send,true)),'a changed appointment invalidates the earlier email preview');
    foreach(array('pending','confirmed','cancelled','rebook') as $kind){$test=$row($id);$test['status']=$kind==='rebook'?'cancelled':$kind;$text=xw_appt_message_text($test,$kind);appt_flow_check(!str_contains($text,'{saludo}')&&str_contains($text,'Service B'),'localized message tokens expand for '.$kind);}
    $test['customer_name']='';appt_flow_check(str_starts_with(xw_appt_message_text($test),'Hola,'),'missing name uses natural generic greeting');
    $test_locale='en_US';appt_flow_check(str_starts_with(xw_appt_message_text($test),'Hello,')&&'Log out'===xw_appt_site_t('Cerrar sesión','Log out'),'site language selects English defaults and logout');$test_locale='es_ES';
    $old=gmdate('Y-m-d H:i:s',time()-16*DAY_IN_SECONDS);$past=gmdate('Y-m-d H:i:s',time()-30*DAY_IN_SECONDS);
    $base=$row($id);$base['status_changed_at']=$old;$base['status']='confirmed';appt_flow_check(!xw_appt_can_rebook_row($base),'future confirmed bookings excluded even with old status date');$base['status']='pending';$base['ends_at']=$past;appt_flow_check(!xw_appt_can_rebook_row($base),'pending bookings excluded');$base['status']='cancelled';$base['status_changed_at']=null;appt_flow_check(!xw_appt_can_rebook_row($base),'legacy unknown status date is not invented');$base['status_changed_at']=$old;appt_flow_check(xw_appt_can_rebook_row($base),'cancelled bookings qualify from status-change date');$base['status_changed_at']=gmdate('Y-m-d H:i:s',time()-14*DAY_IN_SECONDS);appt_flow_check(!xw_appt_can_rebook_row($base)&&xw_appt_can_rebook_row($base,xw_appt_rebook_cutoff(current_datetime()->format('Y-m-d'))),'configured delay and manual cutoff work independently of original appointment date');
    $wpdb->update($table,array('status'=>'cancelled','status_changed_at'=>$old),array('id'=>$id));$oldrow=$row($id);$ready=xw_appt_booking_card($oldrow,'nonce','rebook');appt_flow_check(str_contains($ready,'data-operation="rebook"')&&str_contains($ready,'data-kind="rebook"'),'ready cards use rebook workflow and contact message');
    $available=xw_appt_available($tomorrow);$newvalue=array_key_first($available);$new=xw_appt_rebook($id,$newvalue);appt_flow_check(!is_wp_error($new)&&$new['status']==='pending'&&$new['email']===$oldrow['email'],'rebook creates a new pending appointment with copied data');$ids[]=(int)$new['id'];
    $historical=$row($id);appt_flow_check($historical['starts_at']===$oldrow['starts_at']&&$historical['status']==='cancelled'&&(int)$historical['rebooked_to']===(int)$new['id']&&$historical['status_changed_at']===$old,'original record and state date preserved with link to new booking');
    appt_flow_check(is_wp_error(xw_appt_rebook($id,$newvalue))&&!xw_appt_can_rebook_row($historical),'duplicate rebooking rejected');
    $copy=$data;$copy['status']='completed';$copy['starts_at']=$past;$copy['ends_at']=$past;$copy['status_changed_at']=$old;$wpdb->insert($table,$copy);$other=(int)$wpdb->insert_id;$ids[]=$other;appt_flow_check(is_wp_error(xw_appt_rebook($other,$newvalue))&&empty($row($other)['rebooked_to']),'busy slot cannot create a rebooking or corrupt history');
    $_GET=array('booking_view'=>'rebook');ob_start();xw_appt_admin_list($nonce);$list=ob_get_clean();appt_flow_check(str_contains($list,'data-booking="'.$other.'"')&&!str_contains($list,'data-booking="'.$id.'"')&&!str_contains($list,'data-booking="'.$new['id'].'"'),'ready list excludes rebooked and future pending records');
    wp_set_current_user(0);appt_flow_check(is_wp_error(xw_appt_details_update($id,$input))&&is_wp_error(xw_appt_message_request($id,array()))&&is_wp_error(xw_appt_rebook($other,$newvalue)),'anonymous callers cannot edit, preview messages or rebook');
} catch (Throwable $error) { fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n"); throw $error; } finally {
    wp_set_current_user(0); foreach($ids as $id){$wpdb->delete(xw_appt_table(),array('id'=>$id));}
    delete_option('xw_appointment_access_emails'); foreach($users as $id){wp_delete_user($id);}
    foreach($original as $key=>$value){if(null===$value)delete_option($key);else update_option($key,$value);}
}
