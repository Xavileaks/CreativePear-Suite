<?php
/** Read-only local CSS fixture. No submissions, remote assets or WordPress settings writes. */
$root = dirname( __DIR__ );
$css = file_get_contents( $root . '/assets/css/elementor extra.css' );
if ( PHP_SAPI === 'cli' ) {
    if ( ! preg_match( '/\\.elementor-widget-form \\.elementor-form \\.elementor-field:focus,\s*\\.elementor-widget-form \\.elementor-form \\.elementor-select-wrapper select:focus\\s*\\{([^}]+)\\}/', $css, $matches ) || trim( $matches[1] ) !== 'box-shadow: none !important;' ) {
        throw new RuntimeException( 'Form focus rule must remove only box-shadow.' );
    }
    $main = is_file( $root . '/creativepear-suite.php' ) ? 'creativepear-suite.php' : 'xleon-suite.php';
    if ( ! preg_match( "/if \\( xw_feature_enabled\\( 'assets_styles' \\) \\) \\{\\s*add_action\\( 'wp_enqueue_scripts', 'xleon_suite_enqueue_styles', 20 \\);/", file_get_contents( $root . '/' . $main ) ) ) {
        throw new RuntimeException( 'Global stylesheet must remain gated by the existing switch.' );
    }
    echo "PASS: Elementor Form focus removes only shadow, scoped to form fields and the existing Global CSS styles switch\n";
    exit;
}
if ( ! in_array( $_SERVER['REMOTE_ADDR'] ?? '', array( '127.0.0.1', '::1' ), true ) ) { http_response_code( 403 ); exit; }
$enabled = ! isset( $_GET['enabled'] ) || '0' !== $_GET['enabled'];
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Elementor Form · Focus QA</title>
<?php if ( $enabled ) : ?><style><?php echo $css; ?></style><?php endif; ?>
<style>
body{margin:0;background:#fdf6f9;color:#152238;font:16px/1.5 system-ui}
main{max-width:850px;margin:36px auto;padding:28px;background:#fff;border-radius:20px}
h1{font-size:25px;margin:0 0 12px}p{margin:0 0 20px}.fields{display:grid;grid-template-columns:1fr 1fr;gap:18px}
label{display:block}input,select,textarea{font:inherit;box-sizing:border-box;width:100%;padding:12px;background:#fff;color:inherit;border:0;border-bottom:1px solid #536070;border-radius:0}
/* Deliberately later than suite CSS: reproduce a theme/Elementor focus shadow. */
input:focus,select:focus,textarea:focus{box-shadow:inset 0 0 0 1px #ddd;border-bottom-color:#d92d75}
.elementor-field:focus-visible{outline:0;border-bottom:2px solid #9f2457}
.comparison{margin-top:26px;padding-top:18px;border-top:1px solid #ddd}
@media(max-width:600px){main{margin:16px;padding:20px}.fields{grid-template-columns:1fr}}
</style></head><body><main>
<h1>Elementor Form · foco sin box-shadow</h1>
<p>Global CSS styles: <?php echo $enabled ? 'activado' : 'desactivado'; ?>. Solo se elimina la sombra de foco; se conservan borde y outline configurados.</p>
<div class="elementor-widget-form"><form class="elementor-form" onsubmit="return false"><div class="fields">
<label>Nombre<input id="qa-name" class="elementor-field elementor-field-textual" type="text" autocomplete="off" placeholder="Tu nombre completo"></label>
<label>Correo<input id="qa-email" class="elementor-field elementor-field-textual" type="email" autocomplete="off" placeholder="correo@example.test"></label>
<label>Teléfono<input id="qa-phone" class="elementor-field elementor-field-textual" type="tel" autocomplete="off" placeholder="Tu número de contacto"></label>
<label>Motivo<div class="elementor-field elementor-select-wrapper"><select id="qa-select" class="elementor-field-textual elementor-size-md"><option>Selecciona una opción</option><option>Consulta</option></select></div></label>
<label>Fecha<input id="qa-date" class="elementor-field elementor-field-textual" type="date"></label>
<label>Mensaje<textarea id="qa-message" class="elementor-field elementor-field-textual" rows="2"></textarea></label>
</div></form></div>
<div class="comparison"><label>Campo ajeno a Elementor (sin cambios)<input id="qa-outside" type="text" autocomplete="off"></label><label>Select ajeno a Elementor (sin cambios)<select id="qa-outside-select"><option>Selecciona una opción</option><option>Otra</option></select></label></div>
</main></body></html>
