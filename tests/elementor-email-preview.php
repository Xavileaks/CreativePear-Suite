<?php
/** Read-only loopback preview; never sends mail, saves settings or creates a login session. */
if ( ! in_array( $_SERVER['REMOTE_ADDR'] ?? '', array( '127.0.0.1', '::1' ), true ) || 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { http_response_code(403); exit; }
$qa_root = getenv( 'XW_EMAIL_QA_WP_ROOT' );
if ( ! $qa_root || ! is_file( $qa_root . '/wp-load.php' ) ) { exit( 'Configure isolated local WordPress.' ); }
$suite = 'creativepear' === ( $_GET['suite'] ?? '' ) ? 'creativepear-suite' : 'xleon-suite';
$GLOBALS['wp_filter']['option_active_plugins'][10][] = array( 'function' => static function() use ( $suite ) { return array( 'elementor/elementor.php', 'proelements/pro-elements.php', $suite . '/' . $suite . '.php' ); }, 'accepted_args' => 1 );
require $qa_root . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { http_response_code(403); exit; }
$lang = 'en' === ( $_GET['lang'] ?? '' ) ? 'en_US' : 'es_ES';
add_filter( 'locale', static function() use ( $lang ) { return $lang; } );
add_filter( 'determine_locale', static function() use ( $lang ) { return $lang; } );
add_filter( 'option_blogname', static function() use ( $lang ) { return 'en_US' === $lang ? 'Website' : 'Sitio web'; } );
add_filter( 'theme_mod_custom_logo', '__return_zero' );
add_filter( 'plugins_url', static function( $url, $path ) { return 'http://127.0.0.1:8097/' . ltrim( $path, '/' ); }, 10, 2 );
add_filter( 'home_url', static function() { return 'https://example.invalid/'; } );
$values = array( 'Nombre' => 'Ana García', 'Correo electrónico' => 'ana@example.invalid', 'Teléfono' => '+34 600 123 456', 'Mensaje' => "Hola, me gustaría recibir más información sobre sus servicios.\nGracias." );
$record = new class( $values ) {
    private $fields; public function __construct( $values ) { foreach ( $values as $label => $value ) { $this->fields[] = array( 'title' => $label, 'value' => $value, 'type' => strpos( $value, '@' ) !== false ? 'email' : 'text' ); } }
    public function get( $key ) {
        if ( 'fields' === $key ) { return $this->fields; }
        if ( 'form_settings' === $key ) { return array( 'form_metadata' => isset( $_GET['nometa'] ) ? array() : array( 'date', 'time', 'page_url', 'remote_ip', 'user_agent', 'credit' ), 'form_metadata_2' => array() ); }
        if ( 'meta' === $key ) { return array( 'date' => array( 'value' => '26/10/2026' ), 'time' => array( 'value' => '15:07' ), 'page_url' => array( 'value' => 'https://example.invalid/contacto/' ), 'remote_ip' => array( 'value' => '203.0.113.42' ), 'user_agent' => array( 'value' => 'Navegador de ejemplo' ), 'credit' => array( 'value' => 'PRO Elements' ) ); }
        return null;
    }
    public function get_form_settings( $key ) { return 'form_name' === $key ? 'Formulario de contacto' : null; }
};
$mode = $_GET['mode'] ?? '';
if ( in_array( $mode, array( 'admin', 'customer' ), true ) ) { echo xw_form_email_render( $record, $mode ); exit; }
if ( 'settings' === $mode ) {
    require_once ABSPATH . 'wp-admin/includes/admin.php';
    $admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) ); wp_set_current_user( (int) $admins[0] );
    ob_start(); xw_render_settings_page(); $markup = ob_get_clean();
    $dom = new DOMDocument(); @$dom->loadHTML( '<?xml encoding="UTF-8">' . $markup ); $xpath = new DOMXPath( $dom );
    $section = $xpath->query( '//section[header/h2[@id="xw-section-elementor"]]' )->item(0);
    ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ajustes de correo · QA local</title><link rel="stylesheet" href="../assets/css/admin-settings.css"><style>body{background:#f0f0f1;color:#1d2327;font:13px/1.5 system-ui;padding:24px}textarea{box-sizing:border-box;width:100%;min-height:110px;padding:10px;font:inherit}h2{font-size:24px}.xw-options-toggle{width:100%}.screen-reader-text{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(1px,1px,1px,1px);white-space:nowrap}.xw-options-toggle .dashicons{display:inline-block;width:8px;height:8px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(45deg)}</style></head><body><div class="xw-settings-wrap"><form data-xw-settings-form><?php if ( $section ) { echo $dom->saveHTML( $section ); } ?></form></div><script src="../build/12ui-form-emails/jquery.min.js"></script><script src="../assets/js/admin-settings.js"></script></body></html><?php exit;
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Correos de Elementor · vista previa</title><style>body{margin:0;background:#f5f7fc;font:16px/1.5 Arial;color:#18264a}main{max-width:1320px;margin:auto;padding:24px}h1{font-size:24px}p{font-size:14px}.emails{display:grid;grid-template-columns:1fr 1fr;gap:24px}@media(max-width:700px){main{padding:10px}.emails{grid-template-columns:1fr}}@media(max-width:480px){.xw-mail-pad{padding:22px 18px!important}.xw-mail-label,.xw-mail-value{display:block!important;width:100%!important}.xw-mail-label{padding-bottom:3px!important}.xw-mail-title{font-size:23px!important;line-height:30px!important}}</style></head><body><main><h1>Correos de Elementor</h1><p>Datos de ejemplo. Vista previa local sin envíos. Administrador y cliente.</p><div class="emails"><?php foreach ( array( 'admin', 'customer' ) as $kind ) { $html = xw_form_email_render( $record, $kind ); preg_match( '#<body[^>]*>(.*)</body>#s', $html, $body ); echo '<article aria-label="' . esc_attr( $kind ) . '">' . ( $body[1] ?? '' ) . '</article>'; } ?></div></main></body></html>
