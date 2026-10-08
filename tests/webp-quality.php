<?php
/** Pruebas de calidad y aislamiento de filtros sin una instalación WordPress. */
define( 'ABSPATH', __DIR__ . '/' );
$hooks = array();
$settings = array();
$fail_load = false;
$fail_save = false;
$chosen_editors = array();

function add_filter( $hook, $callback, $priority = 10, $accepted = 1 ) {
    global $hooks;
    $hooks[ $hook ][ $priority ][] = $callback;
}
function add_action( $hook, $callback, $priority = 10, $accepted = 1 ) {
    add_filter( $hook, $callback, $priority, $accepted );
}
function remove_filter( $hook, $callback, $priority = 10 ) {
    global $hooks;
    foreach ( $hooks[ $hook ][ $priority ] ?? array() as $key => $registered ) {
        if ( $registered === $callback ) {
            unset( $hooks[ $hook ][ $priority ][ $key ] );
        }
    }
}
function apply_filters( $hook, $value, ...$args ) {
    global $hooks;
    $priorities = $hooks[ $hook ] ?? array();
    ksort( $priorities );
    foreach ( $priorities as $callbacks ) {
        foreach ( $callbacks as $callback ) {
            $value = $callback( $value, ...$args );
        }
    }
    return $value;
}
function xw_get_settings() { global $settings; return $settings; }
function xw_feature_enabled( $feature ) { return true; }
function wp_image_editor_supports( $args ) { return true; }
if ( ! function_exists( 'imagewebp' ) ) {
    function imagewebp() {}
}
class WP_Error {}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
class WP_Image_Editor_GD {
    public static $supported = true;
    public static function supports_mime_type( $mime ) { return self::$supported && 'image/webp' === $mime; }
}
class Quality_Test_Editor {
    public $quality;
    public function maybe_exif_rotate() {}
    public function set_quality( $quality ) { $this->quality = $quality; return true; }
    public function save( $destination, $mime ) {
        global $fail_save;
        ensure( 'image/webp' === $mime, 'Solo debe guardar WebP.' );
        ensure( $this->quality === apply_filters( 'wp_editor_set_quality', 86, 'image/webp' ), 'Calidad al cambiar de MIME.' );
        ensure( $this->quality === apply_filters( 'jpeg_quality', 82 ), 'Calidad frente a un filtro JPEG anterior.' );
        return $fail_save ? new WP_Error() : array( 'path' => __FILE__ );
    }
}
function wp_get_image_editor( $source ) {
    global $fail_load, $chosen_editors;
    $chosen_editors = apply_filters( 'wp_image_editors', array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) );
    return $fail_load ? new WP_Error() : new Quality_Test_Editor();
}
function ensure( $condition, $message ) {
    if ( ! $condition ) { throw new RuntimeException( $message ); }
}
require dirname( __DIR__ ) . '/includes/webp-compress.php';

// Otro plugin puede imponer una calidad anterior; la conversión debe ganar
// únicamente mientras se guarda y restaurar ese filtro al terminar.
add_filter( 'jpeg_quality', static function () { return 82; }, 99 );
foreach ( array( 'low' => 50, 'medium' => 60, 'high' => 75, 'extra_high' => 90, 'invalid' => 75 ) as $level => $quality ) {
    $settings = array( 'webp' => array( 'quality' => $level ) );
    ensure( $quality === xw_webp_get_quality(), 'Mapa de calidad: ' . $level );
    ensure( xw_webp_create_file( __FILE__, 'unused.webp', false ), 'Conversión: ' . $level );
    ensure( 'WP_Image_Editor_GD' === $chosen_editors[0], 'Debe preferir GD.' );
    ensure( array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) === apply_filters( 'wp_image_editors', array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) ), 'No debe alterar otros editores.' );
    ensure( 82 === apply_filters( 'jpeg_quality', 1 ), 'Debe restaurar los filtros JPEG.' );
    ensure( 82 === apply_filters( 'wp_editor_set_quality', 82, 'image/jpeg' ), 'No debe alterar otros formatos.' );
}
WP_Image_Editor_GD::$supported = false;
ensure( array( 'WP_Image_Editor_Imagick' ) === xw_webp_prefer_gd( array( 'WP_Image_Editor_Imagick' ) ), 'Conservar fallback si GD no soporta WebP.' );
WP_Image_Editor_GD::$supported = true;
$fail_load = true;
ensure( ! xw_webp_create_file( __FILE__, 'unused.webp', false ), 'Error de carga.' );
$fail_load = false;
$fail_save = true;
ensure( ! xw_webp_create_file( __FILE__, 'unused.webp', false ), 'Error de guardado.' );
ensure( 82 === apply_filters( 'jpeg_quality', 1 ), 'Restaurar filtros tras error.' );
add_filter( 'wp_image_editors', 'xw_webp_prefer_gd', PHP_INT_MAX );
ensure( array( 'width' => 1264 ) === xw_webp_finish_upload( array( 'width' => 1264 ) ), 'Conservar metadatos.' );
ensure( array( 'WP_Image_Editor_Imagick' ) === apply_filters( 'wp_image_editors', array( 'WP_Image_Editor_Imagick' ) ), 'Retirar preferencia al finalizar.' );
echo "OK: 4 niveles, fallback, errores y aislamiento de filtros.\n";
