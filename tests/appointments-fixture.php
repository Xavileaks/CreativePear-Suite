<?php
if ( ! in_array( $_SERVER['REMOTE_ADDR'] ?? '', array( '127.0.0.1', '::1' ), true ) ) { http_response_code( 403 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { http_response_code( 403 ); exit; }
if ( 'es' === ( $_GET['lang'] ?? '' ) ) { add_filter( 'determine_locale', static function() { return 'es_ES'; } ); }
// This shared disposable WP has an older Woo-only MU fixture. Do not invoke it here.
foreach ( $GLOBALS['wp_filter']['wp_footer']->callbacks ?? array() as $priority => $callbacks ) {
    foreach ( $callbacks as $callback ) {
        if ( $callback['function'] instanceof Closure && 'wishlist-variation-qa.php' === basename( ( new ReflectionFunction( $callback['function'] ) )->getFileName() ) ) { remove_action( 'wp_footer', $callback['function'], $priority ); }
    }
}
$post_id = (int) get_option( 'xw_appt_qa_post' );
if ( ! $post_id || ! class_exists( '\ElementorPro\Modules\Forms\Widgets\Form' ) ) { exit( 'Prepare isolated WordPress with Pro Elements first.' ); }
global $post;
$post = get_post( $post_id ); setup_postdata( $post );
$data = json_decode( get_post_meta( $post_id, '_elementor_data', true ), true );
$widget = \Elementor\Plugin::$instance->elements_manager->create_element_instance( $data[0] );
$post = null; // display API rejects rendering the same current post (recursion guard).
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Citas · QA local</title><?php wp_head(); ?><style>
body{margin:0;background:#fff6fa;color:#26313e;font-family:system-ui;--e-global-color-primary:#ad3561}main{max-width:920px;margin:48px auto;padding:32px;background:white;border-radius:20px}h1{font-size:32px;margin-bottom:8px}.eyebrow{color:#ad3561;font-size:13px;letter-spacing:.12em}.elementor-form-fields-wrapper{display:flex;flex-wrap:wrap;gap:20px}.elementor-field-group{width:calc(50% - 10px);display:flex;flex-direction:column}.elementor-field-type-submit{width:100%}.elementor-field-label{margin-bottom:8px}.elementor-field{font:inherit;color:inherit;background:white;border:1px solid #c8ccd2;padding:12px;border-radius:6px;box-sizing:border-box;width:100%}.elementor-button{padding:14px 22px;background:#ad3561;color:white;border:0;border-radius:24px;cursor:pointer;font:inherit}.elementor-message{margin-top:20px}.elementor-message-success{color:#195632}#qa-warning{font-size:14px;color:#626b77;margin-bottom:30px}@media(max-width:600px){main{margin:16px;padding:24px}.elementor-field-group{width:100%}.elementor-field{font-size:16px}}
main .elementor-widget-form .elementor-field-group{display:flex;flex-direction:column;align-items:stretch}main .elementor-field{max-width:100%}main .elementor-field-label{display:block}
</style></head><body><main><p class="eyebrow">AGENDA TU CITA</p><h1>Solicita tu cita</h1><p id="qa-warning">Prueba local con Elementor Forms real. Sin correos ni servicios externos. Martes, fines de semana y fechas pasadas bloqueados según la configuración de prueba.</p><?php echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $post_id, true ); ?><p>Las solicitudes quedan pendientes de confirmación. Horario de Europe/Madrid.</p></main><?php \Elementor\Plugin::$instance->frontend->enqueue_scripts(); wp_footer(); ?><style>body main .elementor-field-group{display:flex!important;flex-flow:column nowrap!important;align-items:stretch!important}body main .elementor-field-label{display:block!important;flex:none!important;width:100%!important;font-size:16px!important}body main input.elementor-field{flex:none!important;display:block!important;width:100%!important;max-width:100%!important;min-height:44px!important;box-sizing:border-box!important;font-size:16px!important}body main .elementor-field-type-submit .elementor-button{font:inherit;background:#ad3561;color:white;padding:14px 22px;border:0;border-radius:24px}</style></body></html>
