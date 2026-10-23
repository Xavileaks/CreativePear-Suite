<?php
/** Disposable loopback-only admin UI harness. No authentication/session is created. Never ship tests. */
if ( ! in_array( $_SERVER['REMOTE_ADDR'] ?? '', array( '127.0.0.1', '::1' ), true ) ) { http_response_code( 403 ); exit; }
if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { define( 'DOING_AJAX', true ); }
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() || ! get_option( 'xw_appt_qa_backup' ) ) { http_response_code( 403 ); exit; }
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
wp_set_current_user( (int) $admins[0] );
if ( 'en' !== ( $_GET['lang'] ?? '' ) ) { add_filter( 'determine_locale', static function() { return 'es_ES'; } ); }
if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && 'xw_appt_admin_save' === ( $_POST['action'] ?? '' ) ) { xw_appt_admin_save(); exit; }
xw_appt_admin_assets( 'toplevel_page_xw-appointments' );
wp_localize_script( 'xw-appointments-admin', 'xwAppointmentAdmin', array( 'url' => plugins_url( 'tests/appointments-admin-fixture.php', XW_FUNCTIONS_FILE ), 'saving' => 'Guardando…', 'saved' => 'Cambios guardados.', 'error' => 'No se pudo guardar.', 'dirty' => 'Hay cambios sin guardar.', 'cancel' => '¿Cancelar esta cita y liberar el horario?' ) );
wp_enqueue_style( 'common' ); wp_enqueue_style( 'forms' ); wp_enqueue_style( 'buttons' );
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Citas · panel local</title><?php wp_print_styles(); ?><style>body{font:13px/1.5 system-ui;background:#f0f0f1;margin:0;padding:24px;color:#1d2327}.wrap h1{font-size:28px}.screen-reader-text{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}@media(max-width:600px){body{padding:12px}}</style></head><body><p>QA local — panel con datos sintéticos. Sin sesiones ni correos.</p><?php xw_appt_admin_page(); wp_print_footer_scripts(); ?></body></html>
