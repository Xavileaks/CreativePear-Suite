<?php
/** Isolated local WP integration: pre_wp_mail captures every send; no delivery or option writes. */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { exit( "Supply isolated WordPress directory.\n" ); }
$suite = 'creativepear' === ( $argv[2] ?? '' ) ? 'creativepear-suite' : 'xleon-suite';
$GLOBALS['wp_filter']['option_active_plugins'][10][] = array( 'function' => static function() use ( $suite ) { return array( 'elementor/elementor.php', 'proelements/pro-elements.php', $suite . '/' . $suite . '.php' ); }, 'accepted_args' => 1 );
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { exit( "Local environment required\n" ); }
function mail_check( $condition, $label ) { if ( ! $condition ) { throw new RuntimeException( $label ); } echo 'PASS: ' . $label . "\n"; }
$mail_settings = xw_get_default_settings(); $mail_language = 'es_ES'; $captured = array(); $attacks = 0;
add_filter( 'pre_option_xw_settings', static function() use ( &$mail_settings ) { return $mail_settings; } );
add_filter( 'locale', static function() use ( &$mail_language ) { return $mail_language; } );
add_filter( 'pre_wp_mail', static function( $return, $args ) use ( &$captured ) { $captured[] = $args; return true; }, 100, 2 );
add_shortcode( 'xw_email_attack', static function() use ( &$attacks ) { ++$attacks; return 'EXECUTED'; } );
function mail_record( $overrides = array() ) {
    $settings = array( 'id' => 'email-style-qa', 'form_name' => 'Contacto QA', 'submit_actions' => array( 'email', 'email2' ), 'form_metadata' => array( 'date', 'time', 'page_url' ), 'form_metadata_2' => array(), 'form_fields' => array() );
    foreach ( array( '' => 'owner@example.invalid', '_2' => '[field id="email"]' ) as $suffix => $recipient ) {
        foreach ( array( 'email_to' => $recipient, 'email_content' => '[all-fields]', 'email_subject' => '', 'email_from_name' => 'Sitio QA', 'email_from' => 'site@example.invalid', 'email_reply_to' => '', 'email_to_cc' => '', 'email_to_bcc' => '', 'email_content_type' => 'html' ) as $name => $value ) { $settings[ $name . $suffix ] = $value; }
    }
    $values = array( 'name' => 'Ana & García', 'email' => 'visitor@example.invalid', 'phone' => '+34 612 345 678', 'message' => "Hola\nNecesito información [xw_email_attack]", 'zero' => '0', 'hidden' => 'campaign-qa', 'captcha' => '', 'password' => 'SECRET-DO-NOT-EMAIL' );
    $types = array( 'email' => 'email', 'phone' => 'tel', 'message' => 'textarea', 'hidden' => 'hidden', 'captcha' => 'recaptcha', 'password' => 'password' );
    foreach ( $values as $id => $value ) { $settings['form_fields'][] = array( 'custom_id' => $id, 'field_type' => $types[ $id ] ?? 'text', 'field_label' => $id, 'attachment_type' => '' ); }
    $settings = array_replace( $settings, $overrides );
    $record = new \ElementorPro\Modules\Forms\Classes\Form_Record( $values, array( 'widgetType' => 'form', 'settings' => $settings ) );
    $record->set( 'meta', array( 'date' => array( 'title' => 'Date', 'value' => '26 octubre 2026' ), 'time' => array( 'title' => 'Time', 'value' => '15:07' ), 'page_url' => array( 'title' => 'Page URL', 'value' => 'https://example.invalid/contacto/' ), 'remote_ip' => array( 'title' => 'Remote IP', 'value' => '203.0.113.42' ), 'user_agent' => array( 'title' => 'User Agent', 'value' => 'QA-PRIVATE-BROWSER' ), 'credit' => array( 'title' => 'Powered by', 'value' => 'PRO Elements' ) ) );
    return $record;
}
function mail_run( $record, $actions = array( 'email', 'email2' ) ) {
    $handler = new class { public function add_error_message( $message ) {} };
    apply_filters( 'elementor_pro/forms/record/actions_before', $record, $handler );
    foreach ( $actions as $name ) {
        $action = 'email' === $name ? new \ElementorPro\Modules\Forms\Actions\Email() : new \ElementorPro\Modules\Forms\Actions\Email2();
        try { $action->run( $record, $handler ); } finally { do_action( 'elementor_pro/forms/actions/after_run', $action, null ); }
    }
    xw_form_email_restore_record( $record );
}
mail_check( 0 === $mail_settings['features']['elementor_email_admin_style'] && 0 === $mail_settings['features']['elementor_email_customer_style'], 'both new cards default off' );
mail_check( array_slice( xw_get_feature_groups()['elementor']['features'], 0, 2 ) === array( 'elementor_email_admin_style', 'elementor_email_customer_style' ), 'owner and customer cards are first in Elementor' );
$record = mail_record(); $before = $record->get( 'form_settings' ); mail_run( $record );
mail_check( count( $captured ) === 2 && false === strpos( $captured[0]['message'], 'data-xw-form-email' ) && $record->get( 'form_settings' ) === $before, 'disabled cards preserve native Email and Email2 behavior' );
$captured = array(); $mail_settings['features']['elementor_email_admin_style'] = $mail_settings['features']['elementor_email_customer_style'] = 1;
$record = mail_record(); $before = $record->get( 'form_settings' ); mail_run( $record );
mail_check( count( $captured ) === 2, 'exactly existing configured sends, no duplicate confirmation' );
mail_check( $captured[0]['to'] === 'owner@example.invalid' && $captured[1]['to'] === 'visitor@example.invalid', 'owner and shortcode Email2 recipients preserved' );
mail_check( strpos( $captured[0]['message'], 'data-xw-form-email="admin"' ) !== false && strpos( $captured[1]['message'], 'data-xw-form-email="customer"' ) !== false, 'separate HTML layouts use the real Elementor Email actions' );
mail_check( strpos( $captured[0]['subject'], 'Nuevo mensaje recibido' ) !== false && strpos( $captured[1]['subject'], 'Hemos recibido tu mensaje' ) !== false, 'generic subjects use WordPress Spanish' );
mail_check( strpos( $captured[0]['message'], '203.0.113.42' ) === false && strpos( $captured[0]['message'], 'QA-PRIVATE-BROWSER' ) === false && strpos( $captured[0]['message'], 'PRO Elements' ) === false, 'disabled metadata omitted even when present in record' );
mail_check( strpos( $captured[0]['message'], '26 octubre 2026' ) !== false && strpos( $captured[0]['message'], 'https://example.invalid/contacto/' ) !== false, 'selected metadata included in organized footer' );
mail_check( strpos( $captured[0]['message'], 'SECRET-DO-NOT-EMAIL' ) === false && strpos( $captured[0]['message'], 'campaign-qa' ) !== false && strpos( $captured[0]['message'], '>0<' ) !== false, 'security fields omitted, ordinary hidden fields and zero retained' );
mail_check( strpos( $captured[0]['message'], 'Ana &amp; García' ) !== false && $attacks === 0 && strpos( $captured[1]['message'], 'visitor@example.invalid' ) === false, 'values escaped, submitted shortcodes never executed, receipt does not repeat field data' );
mail_check( $record->get( 'form_settings' ) === $before && ! xw_form_email_contexts(), 'record and settings restored before subsequent actions' );
mail_check( strpos( $captured[0]['headers'], 'site@example.invalid' ) !== false && strpos( $captured[0]['headers'], 'text/html' ) !== false, 'native sender and HTML headers preserved' );
$captured = array(); mail_run( mail_record( array( 'submit_actions' => array( 'email' ) ) ), array( 'email' ) );
mail_check( count( $captured ) === 1, 'customer card never creates an unconfigured Email2 send' );
$captured = array(); $record = mail_record( array( 'email_content' => 'PERSONALIZADO [all-fields]', 'email_content_2' => 'CUSTOM RECEIPT', 'email_subject_2' => 'My subject' ) ); mail_run( $record );
mail_check( strpos( $captured[0]['message'], 'PERSONALIZADO' ) !== false && strpos( $captured[0]['message'], 'data-xw-form-email' ) === false && $captured[1]['message'] === 'CUSTOM RECEIPT' && $captured[1]['subject'] === 'My subject', 'custom Elementor messages and subjects untouched' );
$captured = array(); mail_run( mail_record( array( 'email_content_type' => 'plain', 'email_subject_2' => 'Custom subject retained' ) ) );
mail_check( strpos( $captured[0]['message'], '<html' ) === false && $captured[1]['subject'] === 'Custom subject retained', 'plain-text opt-out and custom subject respected independently' );
$captured = array(); $mail_settings['elementor_emails']['customer_message'] = "Mensaje propio\nGracias <script>unsafe</script>";
mail_run( mail_record( array( 'form_metadata' => array(), 'form_metadata_2' => array( 'remote_ip', 'credit' ) ) ) );
mail_check( strpos( $captured[1]['message'], 'Mensaje propio' ) !== false && strpos( $captured[1]['message'], '<script>' ) === false && strpos( $captured[1]['message'], '&lt;script&gt;' ) !== false, 'custom receipt text is escaped and multiline' );
mail_check( strpos( $captured[0]['message'], 'Detalles del envío' ) === false && strpos( $captured[1]['message'], '203.0.113.42' ) !== false && strpos( $captured[1]['message'], 'PRO Elements' ) !== false, 'metadata choices independent for Email and Email2; empty section removed' );
$captured = array(); $mail_settings['elementor_emails']['customer_message'] = ''; $mail_language = 'en_US'; mail_run( mail_record() );
mail_check( strpos( $captured[0]['message'], 'New message received' ) !== false && strpos( $captured[1]['message'], 'Thank you for contacting us' ) !== false && strpos( $captured[0]['message'], 'Source page' ) !== false, 'generic content and metadata labels use WordPress English' );
$record = mail_record(); $before = $record->get( 'form_settings' ); xw_form_email_prepare( $record ); xw_form_email_after_action( new \ElementorPro\Modules\Forms\Actions\Email() ); xw_form_email_restore_record( $record );
mail_check( $record->get( 'form_settings' ) === $before && ! xw_form_email_contexts(), 'unprocessed or failed actions leave no marker in record settings' );
mail_check( xw_form_email_message( 'OTHER MAIL BODY' ) === 'OTHER MAIL BODY', 'unrelated messages unchanged' );
$first_record = mail_record(); $second_record = mail_record();
xw_form_email_prepare( $first_record ); xw_form_email_prepare( $second_record );
xw_form_email_message( $first_record->get_form_settings( 'email_content' ) );
xw_form_email_after_action( new \ElementorPro\Modules\Forms\Actions\Email(), null );
mail_check( strpos( $second_record->get_form_settings( 'email_content' ), '<!--xw-form-email-' ) === 0, 'successful action never consumes another record context' );
xw_form_email_restore_record( $first_record ); xw_form_email_restore_record( $second_record );
$conditional_record = mail_record(); xw_form_email_prepare( $conditional_record );
$unchanged_actions = apply_filters( 'elementor_pro/forms/submit_actions', array( 'email' ), $conditional_record );
mail_check( $unchanged_actions === array( 'email' ) && $conditional_record->get_form_settings( 'email_content_2' ) === '[all-fields]', 'conditionally removed actions restored without adding or removing sends' );
xw_form_email_restore_record( $conditional_record );
$sanitized = xw_sanitize_settings( array( 'features' => $mail_settings['features'], 'elementor_emails' => array( 'customer_message' => '<b>Hello</b>' ) ) );
mail_check( $sanitized['elementor_emails']['customer_message'] === 'Hello' && $sanitized['features']['elementor_email_customer_style'] === 1, 'customer text saved through existing sanitizer' );
$captured = array(); $mail_settings['features']['elementor_email_admin_style'] = 0; mail_run( mail_record() );
mail_check( strpos( $captured[0]['message'], 'data-xw-form-email' ) === false && strpos( $captured[1]['message'], 'data-xw-form-email="customer"' ) !== false, 'customer switch works independently of admin switch' );
$mail_settings['features']['elementor_email_admin_style'] = 1; $mail_settings['features']['elementor_email_customer_style'] = 0; $captured = array(); mail_run( mail_record() );
mail_check( strpos( $captured[0]['message'], 'data-xw-form-email="admin"' ) !== false && strpos( $captured[1]['message'], 'data-xw-form-email' ) === false, 'admin switch works independently of customer switch' );
$mail_settings['features']['elementor_email_customer_style'] = 1;
$attachment = tempnam( sys_get_temp_dir(), 'xw-email-qa-' ); file_put_contents( $attachment, 'Synthetic QA attachment.' );
try {
    $record = mail_record(); $settings = $record->get( 'form_settings' );
    $settings['form_fields'][] = array( 'custom_id' => 'document', 'field_type' => 'upload', 'field_label' => 'Document', 'attachment_type' => 'both' ); $record->set( 'form_settings', $settings );
    $record->set( 'files', array( 'document' => array( 'path' => array( $attachment ), 'url' => array( 'https://example.invalid/document.txt' ) ) ) );
    $fields = $record->get( 'fields' ); $fields['document'] = array( 'type' => 'upload', 'title' => 'Document', 'value' => 'https://example.invalid/document.txt', 'attachment_type' => 'both' ); $record->set( 'fields', $fields );
    $captured = array(); mail_run( $record );
    mail_check( $captured[0]['attachments'] === array( $attachment ) && $captured[1]['attachments'] === array( $attachment ), 'existing Elementor attachment handling preserved for both actions' );
} finally { if ( is_file( $attachment ) ) { unlink( $attachment ); } }
$fail_mail = static function() { return false; }; add_filter( 'pre_wp_mail', $fail_mail, 999 );
try {
    $record = mail_record( array( 'submit_actions' => array( 'email' ) ) ); $before = $record->get( 'form_settings' );
    try { mail_run( $record, array( 'email' ) ); } catch ( Exception $error ) {}
    mail_check( $record->get( 'form_settings' ) === $before && ! xw_form_email_contexts(), 'native mail failure leaves no modified settings or marker' );
} finally { remove_filter( 'pre_wp_mail', $fail_mail, 999 ); }
echo "All emails intercepted locally. No delivery attempted.\n";
