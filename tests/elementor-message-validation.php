<?php
/** Local integration: exercise the real Elementor record validation, without sends or saved data. */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { exit( "Supply isolated WordPress directory.\n" ); }
$suite = 'creativepear' === ( $argv[2] ?? '' ) ? 'creativepear-suite' : 'xleon-suite';
$GLOBALS['wp_filter']['option_active_plugins'][10][] = array( 'function' => static function() use ( $suite ) { return array( 'elementor/elementor.php', 'proelements/pro-elements.php', $suite . '/' . $suite . '.php' ); }, 'accepted_args' => 1 );
$GLOBALS['wp_filter']['pre_http_request'][10][] = array( 'function' => static function() { return new WP_Error( 'local_qa', 'No external requests in QA.' ); }, 'accepted_args' => 1 );
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { exit( 'Local environment required.' ); }
$enabled = true; $sent = 0;
add_filter( 'pre_option_xw_settings', static function() use ( &$enabled ) { return array( 'features' => array( 'elementor_message_mask' => $enabled ? 1 : 0 ) ); } );
add_filter( 'pre_wp_mail', static function() use ( &$sent ) { ++$sent; return true; } );
function message_check( $condition, $label ) { if ( ! $condition ) { throw new RuntimeException( $label ); } echo 'PASS: ' . $label . "\n"; }
function message_record( $value ) {
    return new ElementorPro\Modules\Forms\Classes\Form_Record( array( 'message' => $value, 'another' => 'Москва', 'mail' => 'qa@example.invalid' ), array( 'widgetType' => 'form', 'settings' => array( 'form_name' => 'Message QA', 'form_metadata' => array(), 'submit_actions' => array(), 'form_fields' => array(
        array( 'custom_id' => 'message', 'field_type' => 'textarea', 'field_label' => 'Mensaje' ),
        array( 'custom_id' => 'another', 'field_type' => 'text', 'field_label' => 'Otro campo' ),
        array( 'custom_id' => 'mail', 'field_type' => 'email', 'field_label' => 'Email' ),
    ) ) ) );
}
$cases = array(
    array( 'Hola, ¿cómo estás? ¡Información para mañana! Pingüino, ÁÉÍÓÚÜÑ.', '' ),
    array( "I'm interested in your services. Budget: 50% of $100. Thank you!", '' ),
    array( "Li\u{0301}nea 1\nMan\u{0303}ana y pingu\u{0308}ino.", '' ),
    array( '', '' ), array( '0', '' ), array( "Hola\tNecesito 2 presupuestos", '' ),
    array( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ abcdefghijklmnopqrstuvwxyz áéíóúüñ ÁÉÍÓÚÜÑ 0123456789', '' ),
    array( 'Москва', 'characters' ), array( 'Hola Привет', 'characters' ), array( 'مرحبا', 'characters' ), array( '你好', 'characters' ), array( 'Špecial', 'characters' ), array( 'ℌola', 'characters' ), array( "Hola\u{200B}", 'characters' ),
    array( 'Ελληνικά', 'characters' ), array( 'עברית', 'characters' ), array( 'हिन्दी', 'characters' ), array( 'こんにちは', 'characters' ), array( '안녕하세요', 'characters' ), array( 'ქართული', 'characters' ), array( 'ภาษาไทย', 'characters' ), array( 'ç à ä ö ß ł ı İ', 'characters' ), array( 'ＡＢＣ', 'characters' ), array( '٠١٢٣', 'characters' ),
    array( '&#1055;&#1088;&#1080;&#1074;&#1077;&#1090;', 'characters' ), array( '%D0%9F%D1%80%D0%B8%D0%B2%D0%B5%D1%82', 'characters' ), array( '&scaron;', 'characters' ),
    array( 'https://example.com/path', 'link' ), array( 'HTTP : //example.com', 'link' ), array( 'www.example.com', 'link' ), array( 'Visita example.com ahora', 'link' ), array( 'qa@example.com', 'link' ), array( 'https%3A%2F%2Fexample%2Ecom', 'link' ), array( 'https%253A%252F%252Fexample%252Ecom', 'link' ), array( 'https&colon;&sol;&sol;example&period;com', 'link' ), array( '&#104;ttp&#58;&#47;&#47;example&#46;com', 'link' ), array( '[url=example.com]Visita[/url]', 'link' ), array( '192.168.1.1', 'link' ), array( 'hxxps://example.com', 'link' ),
    array( '<a href="https://example.com">Hola</a>', 'characters' ), array( array( 'Hola', 'example.com' ), 'characters' ),
);
foreach ( $cases as $index => $case ) {
    list( $value, $code ) = $case;
    message_check( xw_message_validation_error( $value ) === $code, 'policy example ' . $index );
    $record = message_record( $value );
    $handler = new ElementorPro\Modules\Forms\Classes\Ajax_Handler();
    $valid = $record->validate( $handler );
    message_check( $valid === ( '' === $code ) && ( ! $code || isset( $handler->errors['message'] ) ), 'real Elementor validation without browser JS: ' . $index );
    message_check( ! isset( $handler->errors['another'] ) && ! isset( $handler->errors['mail'] ), 'other field types unaffected: ' . $index );
}
$enabled = false;
$handler = new ElementorPro\Modules\Forms\Classes\Ajax_Handler();
message_check( message_record( 'Привет https://example.com' )->validate( $handler ), 'feature off leaves message validation to Elementor' );
message_check( 0 === $sent, 'no emails sent or actions executed' );
echo "No options, forms or products saved.\n";
