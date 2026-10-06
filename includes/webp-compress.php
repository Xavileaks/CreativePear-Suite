<?php
/**
 * Conversión y entrega WebP para Creative Pear Suite.
 *
 * El flujo se inspira en QuickWebP 4.1.2 (GPL-2.0+), pero está implementado
 * específicamente para la suite, sin AVIF, licencias ni servicios externos.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Devuelve los ajustes normalizados del módulo.
 *
 * @return array
 */
function xw_webp_get_settings() {
    $settings = xw_get_settings();

    return isset( $settings['webp'] ) && is_array( $settings['webp'] )
        ? $settings['webp']
        : array();
}

/**
 * Traduce la opción visual a calidad WebP.
 *
 * @return int
 */
function xw_webp_get_quality() {
    $settings = xw_webp_get_settings();
    $quality  = isset( $settings['quality'] ) ? $settings['quality'] : 'high';
    $values   = array(
        'low'        => 50,
        'medium'     => 60,
        'high'       => 75,
        'extra_high' => 90,
    );

    return isset( $values[ $quality ] ) ? $values[ $quality ] : 75;
}

/**
 * Comprueba si otro optimizador podría procesar la misma subida.
 *
 * @return bool
 */
function xw_webp_has_quickwebp_conflict() {
    return defined( 'QUICKWEBP_VERSION' ) || class_exists( 'Quickwebp_Image_Optimizer', false );
}

/**
 * Comprueba si el editor de imágenes disponible puede escribir WebP.
 *
 * @return bool
 */
function xw_webp_is_supported() {
    return function_exists( 'wp_image_editor_supports' )
        && wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
}

/**
 * Estado efectivo del módulo en cada petición.
 *
 * @return bool
 */
function xw_webp_runtime_active() {
    return xw_feature_enabled( 'webp_compress' )
        && xw_webp_is_supported()
        && ! xw_webp_has_quickwebp_conflict();
}

/**
 * Limpia el nombre conservando su extensión.
 *
 * @param string $filename Nombre recibido.
 * @return string
 */
function xw_webp_clean_filename( $filename ) {
    $extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
    $basename  = pathinfo( $filename, PATHINFO_FILENAME );
    $basename  = sanitize_title( $basename );

    if ( '' === $basename ) {
        $basename = 'image';
    }

    return $extension ? $basename . '.' . $extension : $basename;
}

/**
 * Crea un archivo WebP con la calidad configurada.
 *
 * @param string $source      Archivo origen.
 * @param string $destination Archivo WebP de salida.
 * @param bool   $compare     Comparar pesos antes de aceptar, cuando proceda.
 * @return bool
 */
function xw_webp_create_file( $source, $destination, $compare = true ) {
    if ( ! is_readable( $source ) ) {
        return false;
    }

    $editor = wp_get_image_editor( $source );

    if ( is_wp_error( $editor ) ) {
        return false;
    }

    if ( method_exists( $editor, 'maybe_exif_rotate' ) ) {
        $editor->maybe_exif_rotate();
    }

    $editor->set_quality( xw_webp_get_quality() );
    $saved = $editor->save( $destination, 'image/webp' );

    if ( is_wp_error( $saved ) || empty( $saved['path'] ) || ! is_file( $saved['path'] ) ) {
        return false;
    }

    if ( $compare && filesize( $saved['path'] ) >= filesize( $source ) ) {
        wp_delete_file( $saved['path'] );
        return false;
    }

    return true;
}

/**
 * Procesa el original antes de que WordPress lo mueva a uploads.
 *
 * @param array $file Datos de la subida.
 * @return array
 */
function xw_webp_prepare_upload( $file ) {
    if ( ! xw_webp_runtime_active() || ! is_array( $file ) || empty( $file['tmp_name'] ) || ! empty( $file['error'] ) ) {
        return $file;
    }

    $settings = xw_webp_get_settings();

    if ( ! empty( $settings['cleanup_filename'] ) && ! empty( $file['name'] ) ) {
        $file['name'] = xw_webp_clean_filename( $file['name'] );
    }

    $mime = wp_get_image_mime( $file['tmp_name'] );

    if ( ! in_array( $mime, array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
        return $file;
    }

    if ( 'image/webp' === $mime && ! empty( $settings['ignore_webp'] ) ) {
        return $file;
    }

    // Al conservar originales, los WebP alternativos se crean después de generar los tamaños.
    if ( ! empty( $settings['save_original'] ) ) {
        return $file;
    }

    $destination = trailingslashit( dirname( $file['tmp_name'] ) )
        . wp_unique_filename( dirname( $file['tmp_name'] ), pathinfo( $file['name'], PATHINFO_FILENAME ) . '.webp' );

    if ( ! xw_webp_create_file( $file['tmp_name'], $destination, false ) ) {
        return $file;
    }

    // Conserva la ruta temporal original para superar la validación is_uploaded_file().
    if ( ! copy( $destination, $file['tmp_name'] ) ) {
        wp_delete_file( $destination );
        return $file;
    }

    wp_delete_file( $destination );
    $file['name']     = pathinfo( $file['name'], PATHINFO_FILENAME ) . '.webp';
    $file['type']     = 'image/webp';
    $file['size']     = filesize( $file['tmp_name'] );

    return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'xw_webp_prepare_upload', 20 );

/**
 * Lista el original y todos sus tamaños generados.
 *
 * @param int   $attachment_id ID del adjunto.
 * @param array $metadata      Metadatos de WordPress.
 * @return array
 */
function xw_webp_attachment_files( $attachment_id, $metadata ) {
    $attached = get_attached_file( $attachment_id );

    if ( ! $attached ) {
        return array();
    }

    $directory = dirname( $attached );
    $files     = array( $attached );

    if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
        foreach ( $metadata['sizes'] as $size ) {
            if ( ! empty( $size['file'] ) ) {
                $files[] = trailingslashit( $directory ) . wp_basename( $size['file'] );
            }
        }
    }

    if ( ! empty( $metadata['original_image'] ) ) {
        $files[] = trailingslashit( $directory ) . wp_basename( $metadata['original_image'] );
    }

    return array_values( array_unique( array_filter( $files, 'is_file' ) ) );
}

/**
 * Elimina los WebP alternativos registrados para un adjunto.
 *
 * @param int $attachment_id ID del adjunto.
 * @return void
 */
function xw_webp_delete_sidecars( $attachment_id ) {
    $uploads = wp_get_upload_dir();
    $stored  = get_post_meta( $attachment_id, '_xw_webp_sidecars', true );

    if ( ! is_array( $stored ) ) {
        return;
    }

    $base = wp_normalize_path( trailingslashit( $uploads['basedir'] ) );

    foreach ( $stored as $relative ) {
        $path = wp_normalize_path( $base . ltrim( (string) $relative, '/\\' ) );

        if ( 0 === strpos( $path, $base ) && is_file( $path ) ) {
            wp_delete_file( $path );
        }
    }

    delete_post_meta( $attachment_id, '_xw_webp_sidecars' );
}

/**
 * Crea archivos .webp paralelos cuando se conservan los originales.
 *
 * @param array  $metadata      Metadatos del adjunto.
 * @param int    $attachment_id ID del adjunto.
 * @param string $context       Contexto de generación.
 * @return array
 */
function xw_webp_generate_sidecars( $metadata, $attachment_id, $context = '' ) {
    if ( ! xw_webp_runtime_active() ) {
        return $metadata;
    }

    $settings = xw_webp_get_settings();

    if ( empty( $settings['save_original'] ) ) {
        return $metadata;
    }

    xw_webp_delete_sidecars( $attachment_id );
    $created = array();

    foreach ( xw_webp_attachment_files( $attachment_id, $metadata ) as $source ) {
        $mime = wp_get_image_mime( $source );

        if ( ! in_array( $mime, array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
            continue;
        }

        if ( 'image/webp' === $mime && ! empty( $settings['ignore_webp'] ) ) {
            continue;
        }

        $destination = $source . '.webp';

        if ( is_file( $destination ) ) {
            wp_delete_file( $destination );
        }

        if ( xw_webp_create_file( $source, $destination, false ) ) {
            $relative = _wp_relative_upload_path( $destination );

            if ( $relative ) {
                $created[] = $relative;
            }
        }
    }

    if ( $created ) {
        update_post_meta( $attachment_id, '_xw_webp_sidecars', array_values( array_unique( $created ) ) );
    }

    return $metadata;
}
add_filter( 'wp_generate_attachment_metadata', 'xw_webp_generate_sidecars', 20, 3 );
add_action( 'delete_attachment', 'xw_webp_delete_sidecars', 5 );

/**
 * Aplica el ancho máximo configurado al escalado grande de WordPress.
 *
 * @param int|false $threshold Umbral actual.
 * @param array     $imagesize Dimensiones.
 * @return int|false
 */
function xw_webp_big_image_threshold( $threshold, $imagesize ) {
    if ( ! xw_webp_runtime_active() ) {
        return $threshold;
    }

    $settings = xw_webp_get_settings();

    if ( empty( $settings['resize_enabled'] ) ) {
        return $threshold;
    }

    return ! empty( $imagesize[0] ) && $imagesize[0] > absint( $settings['max_width'] )
        ? absint( $settings['max_width'] )
        : $threshold;
}
add_filter( 'big_image_size_threshold', 'xw_webp_big_image_threshold', 20, 2 );

/**
 * Mantiene la calidad elegida al crear tamaños WebP.
 *
 * @param int    $quality   Calidad propuesta.
 * @param string $mime_type Tipo MIME de salida.
 * @return int
 */
function xw_webp_editor_quality( $quality, $mime_type ) {
    if ( xw_webp_runtime_active() && 'image/webp' === $mime_type ) {
        return xw_webp_get_quality();
    }

    return $quality;
}
add_filter( 'wp_editor_set_quality', 'xw_webp_editor_quality', 20, 2 );

/**
 * Indica si deben entregarse los WebP paralelos en el frontend.
 *
 * @return bool
 */
function xw_webp_frontend_delivery_active() {
    $settings = xw_webp_get_settings();

    if ( ! xw_webp_runtime_active() || empty( $settings['save_original'] ) ) {
        return false;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return false;
    }

    return ! is_admin() || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() );
}

/**
 * Cambia una URL local por su archivo paralelo si existe.
 *
 * @param string $url URL original.
 * @return string
 */
function xw_webp_sidecar_url( $url ) {
    if ( ! xw_webp_frontend_delivery_active() || ! is_string( $url ) || ! preg_match( '/\.(?:jpe?g|png)(?:\?.*)?$/i', $url ) ) {
        return $url;
    }

    $uploads  = wp_get_upload_dir();
    $base_url = untrailingslashit( $uploads['baseurl'] );
    $base_dir = wp_normalize_path( untrailingslashit( $uploads['basedir'] ) );
    $parts    = explode( '?', $url, 2 );
    $plain    = $parts[0];
    $query    = isset( $parts[1] ) ? '?' . $parts[1] : '';
    $relative = '';

    if ( 0 === stripos( set_url_scheme( $plain ), set_url_scheme( $base_url ) ) ) {
        $relative = substr( set_url_scheme( $plain ), strlen( set_url_scheme( $base_url ) ) );
    } else {
        $base_path = wp_parse_url( $base_url, PHP_URL_PATH );
        $url_path  = wp_parse_url( $plain, PHP_URL_PATH );

        if ( $base_path && $url_path && 0 === strpos( $url_path, $base_path ) ) {
            $relative = substr( $url_path, strlen( $base_path ) );
        }
    }

    if ( '' === $relative ) {
        return $url;
    }

    $path = wp_normalize_path( $base_dir . '/' . ltrim( rawurldecode( $relative ), '/' ) );

    if ( 0 !== strpos( $path, $base_dir . '/' ) || ! is_file( $path . '.webp' ) ) {
        return $url;
    }

    return $plain . '.webp' . $query;
}

/**
 * Sustituye la URL principal de un adjunto.
 *
 * @param string $url URL del adjunto.
 * @return string
 */
function xw_webp_filter_attachment_url( $url ) {
    return xw_webp_sidecar_url( $url );
}
add_filter( 'wp_get_attachment_url', 'xw_webp_filter_attachment_url', 20 );

/**
 * Sustituye la URL devuelta para un tamaño concreto.
 *
 * @param array|false $image Datos de imagen.
 * @return array|false
 */
function xw_webp_filter_image_src( $image ) {
    if ( is_array( $image ) && ! empty( $image[0] ) ) {
        $image[0] = xw_webp_sidecar_url( $image[0] );
    }

    return $image;
}
add_filter( 'wp_get_attachment_image_src', 'xw_webp_filter_image_src', 20 );

/**
 * Sustituye cada candidato de srcset.
 *
 * @param array $sources Candidatos.
 * @return array
 */
function xw_webp_filter_srcset( $sources ) {
    if ( ! is_array( $sources ) ) {
        return $sources;
    }

    foreach ( $sources as &$source ) {
        if ( ! empty( $source['url'] ) ) {
            $source['url'] = xw_webp_sidecar_url( $source['url'] );
        }
    }
    unset( $source );

    return $sources;
}
add_filter( 'wp_calculate_image_srcset', 'xw_webp_filter_srcset', 20 );

/**
 * Evita alterar la vista de edición de Elementor.
 *
 * @return bool
 */
function xw_webp_is_elementor_editor() {
    if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\\Elementor\\Plugin' ) ) {
        return false;
    }

    $plugin = \Elementor\Plugin::$instance;

    return ( isset( $plugin->editor ) && $plugin->editor->is_edit_mode() )
        || ( isset( $plugin->preview ) && $plugin->preview->is_preview_mode() );
}

/**
 * Sustituye URLs locales presentes directamente en el HTML final.
 *
 * @param string $html Documento generado.
 * @return string
 */
function xw_webp_replace_html_urls( $html ) {
    if ( ! is_string( $html ) || '' === $html ) {
        return $html;
    }

    return preg_replace_callback(
        '~(?:(?:https?:)?//[^\s\"\'<>(),]+|/[^\s\"\'<>(),]+)\.(?:jpe?g|png)(?:\?[^\s\"\'<>(),]*)?~i',
        static function ( $matches ) {
            return xw_webp_sidecar_url( $matches[0] );
        },
        $html
    );
}

/**
 * Inicia la sustitución final, incluida la imagen de fondo de constructores.
 *
 * @return void
 */
function xw_webp_start_delivery_buffer() {
    if ( ! xw_webp_frontend_delivery_active() || is_feed() || xw_webp_is_elementor_editor() ) {
        return;
    }

    ob_start( 'xw_webp_replace_html_urls' );
}
add_action( 'template_redirect', 'xw_webp_start_delivery_buffer', 0 );

/**
 * Renderiza el panel del módulo en Ajustes.
 *
 * @param array $settings Ajustes WebP.
 * @return void
 */
function xw_render_webp_compress_settings( $settings ) {
    $supported = xw_webp_is_supported();
    $conflict  = xw_webp_has_quickwebp_conflict();
    $qualities = array(
        'low' => array(
            'title'       => xw_t( 'Baja', 'Low' ),
            'description' => xw_t( 'Menor tamaño', 'Smallest size' ),
            'value'       => 50,
        ),
        'medium' => array(
            'title'       => xw_t( 'Media', 'Medium' ),
            'description' => xw_t( 'Buen equilibrio', 'Good balance' ),
            'value'       => 60,
        ),
        'high' => array(
            'title'       => xw_t( 'Alta', 'High' ),
            'description' => xw_t( 'Buena calidad y tamaño', 'Good quality and size' ),
            'value'       => 75,
            'recommended' => true,
        ),
        'extra_high' => array(
            'title'       => xw_t( 'Extra alta', 'Extra High' ),
            'description' => xw_t( 'Máxima calidad', 'Maximum quality' ),
            'value'       => 90,
        ),
    );
    ?>
    <div class="xw-webp-settings xw-field-full" data-xw-webp-settings>
        <div class="xw-webp-status <?php echo $supported && ! $conflict ? 'is-ready' : 'is-warning'; ?>">
            <span class="dashicons <?php echo $supported && ! $conflict ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" aria-hidden="true"></span>
            <div>
                <strong>
                    <?php
                    echo esc_html(
                        $conflict
                            ? xw_t( 'Hay otro optimizador WebP activo', 'Another WebP optimizer is active' )
                            : ( $supported
                                ? xw_t( 'El servidor admite WebP', 'The server supports WebP' )
                                : xw_t( 'El servidor no puede crear WebP', 'The server cannot create WebP' ) )
                    );
                    ?>
                </strong>
                <p>
                    <?php
                    echo esc_html(
                        $conflict
                            ? xw_t( 'Desactive QuickWebP para evitar procesar cada imagen dos veces. Mientras exista el conflicto, este módulo permanecerá en pausa.', 'Deactivate QuickWebP to avoid processing every image twice. This module remains paused while the conflict exists.' )
                            : ( $supported
                                ? xw_t( 'Las imágenes nuevas se procesan localmente con el editor de WordPress. No se usa AVIF ni se envían archivos a servicios externos.', 'New images are processed locally with the WordPress image editor. AVIF is not used and files are not sent to external services.' )
                                : xw_t( 'Active WebP en GD o Imagick desde el alojamiento antes de usar esta función.', 'Enable WebP in GD or Imagick with your hosting provider before using this feature.' ) )
                    );
                    ?>
                </p>
            </div>
        </div>

        <section class="xw-webp-section" aria-labelledby="xw-webp-quality-title">
            <div class="xw-webp-section-heading">
                <h3 id="xw-webp-quality-title"><?php echo esc_html( xw_t( 'Calidad de imagen', 'Image quality' ) ); ?></h3>
                <p><?php echo esc_html( xw_t( 'Elija el equilibrio entre peso y detalle visual.', 'Choose the balance between file size and visual detail.' ) ); ?></p>
            </div>
            <fieldset class="xw-webp-quality-grid">
                <legend class="screen-reader-text"><?php echo esc_html( xw_t( 'Calidad WebP', 'WebP quality' ) ); ?></legend>
                <?php foreach ( $qualities as $key => $quality ) : ?>
                    <label>
                        <input type="radio" name="xw_settings[webp][quality]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $settings['quality'], $key ); ?>>
                        <span>
                            <?php if ( ! empty( $quality['recommended'] ) ) : ?>
                                <em><?php echo esc_html( xw_t( 'Recomendado', 'Recommended' ) ); ?></em>
                            <?php endif; ?>
                            <strong><?php echo esc_html( $quality['title'] ); ?></strong>
                            <small><?php echo esc_html( $quality['description'] ); ?></small>
                            <b><?php echo esc_html( $quality['value'] ); ?>%</b>
                        </span>
                    </label>
                <?php endforeach; ?>
            </fieldset>
        </section>

        <section class="xw-webp-section" aria-labelledby="xw-webp-conversion-title">
            <div class="xw-webp-section-heading">
                <h3 id="xw-webp-conversion-title"><?php echo esc_html( xw_t( 'Conversión', 'Conversion' ) ); ?></h3>
                <p><?php echo esc_html( xw_t( 'Decida qué conservar al subir imágenes nuevas.', 'Choose what to preserve when uploading new images.' ) ); ?></p>
            </div>
            <div class="xw-webp-toggle-grid">
                <div class="xw-webp-toggle-card">
                    <div>
                        <strong><?php echo esc_html( xw_t( 'No recomprimir WebP', 'Do not recompress WebP' ) ); ?></strong>
                        <p><?php echo esc_html( xw_t( 'Omite imágenes que ya están en el formato WebP.', 'Skips images that are already in WebP format.' ) ); ?></p>
                    </div>
                    <label class="xw-switch">
                        <span class="screen-reader-text"><?php echo esc_html( xw_t( 'No recomprimir WebP', 'Do not recompress WebP' ) ); ?></span>
                        <input type="checkbox" name="xw_settings[webp][ignore_webp]" value="1" <?php checked( ! empty( $settings['ignore_webp'] ) ); ?>>
                        <span class="xw-switch-track" aria-hidden="true"><span></span></span>
                    </label>
                </div>
                <div class="xw-webp-toggle-card">
                    <div>
                        <strong><?php echo esc_html( xw_t( 'Conservar imágenes originales', 'Save original images' ) ); ?></strong>
                        <p><?php echo esc_html( xw_t( 'Guarda el archivo original y crea copias WebP paralelas para el frontend.', 'Keeps the original file and creates WebP sidecars for the frontend.' ) ); ?></p>
                    </div>
                    <label class="xw-switch">
                        <span class="screen-reader-text"><?php echo esc_html( xw_t( 'Conservar imágenes originales', 'Save original images' ) ); ?></span>
                        <input type="checkbox" name="xw_settings[webp][save_original]" value="1" <?php checked( ! empty( $settings['save_original'] ) ); ?> data-xw-webp-save-original>
                        <span class="xw-switch-track" aria-hidden="true"><span></span></span>
                    </label>
                </div>
            </div>
            <div
                class="xw-webp-guidance"
                data-xw-webp-guidance
                data-replace-text="<?php echo esc_attr( xw_t( 'El archivo subido se sustituye por WebP. La biblioteca y el frontend usarán directamente el archivo convertido.', 'The uploaded file is replaced with WebP. The media library and frontend will use the converted file directly.' ) ); ?>"
                data-preserve-text="<?php echo esc_attr( xw_t( 'El original se conserva. Se crea un WebP paralelo para cada tamaño y se entrega automáticamente en el frontend.', 'The original is preserved. A WebP sidecar is created for every size and delivered automatically on the frontend.' ) ); ?>"
            >
                <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
                <div>
                    <strong><?php echo esc_html( xw_t( 'Modo actual', 'Current mode' ) ); ?></strong>
                    <p data-xw-webp-guidance-text></p>
                    <small><?php echo esc_html( xw_t( 'Estos ajustes solo procesan imágenes nuevas; no modifican automáticamente la biblioteca existente.', 'These settings only process new images; they do not automatically modify the existing media library.' ) ); ?></small>
                </div>
            </div>
        </section>

        <section class="xw-webp-section" aria-labelledby="xw-webp-resize-title" data-xw-webp-resize>
            <div class="xw-webp-section-heading">
                <h3 id="xw-webp-resize-title"><?php echo esc_html( xw_t( 'Redimensionar', 'Resize' ) ); ?></h3>
                <p><?php echo esc_html( xw_t( 'Limita las imágenes muy grandes durante la subida.', 'Limits oversized images during upload.' ) ); ?></p>
            </div>
            <div class="xw-webp-inline-setting">
                <div>
                    <strong><?php echo esc_html( xw_t( 'Redimensionar imágenes', 'Resize images' ) ); ?></strong>
                    <p><?php echo esc_html( xw_t( 'WordPress conserva la proporción de la imagen.', 'WordPress preserves the image proportions.' ) ); ?></p>
                </div>
                <label class="xw-switch">
                    <span class="screen-reader-text"><?php echo esc_html( xw_t( 'Redimensionar imágenes', 'Resize images' ) ); ?></span>
                    <input type="checkbox" name="xw_settings[webp][resize_enabled]" value="1" <?php checked( ! empty( $settings['resize_enabled'] ) ); ?> data-xw-webp-resize-toggle>
                    <span class="xw-switch-track" aria-hidden="true"><span></span></span>
                </label>
            </div>
            <div class="xw-webp-max-width">
                <label for="xw-webp-max-width"><?php echo esc_html( xw_t( 'Ancho máximo', 'Maximum width' ) ); ?></label>
                <div class="xw-number-control">
                    <input id="xw-webp-max-width" type="number" name="xw_settings[webp][max_width]" value="<?php echo esc_attr( $settings['max_width'] ); ?>" min="320" max="10000" step="10" data-xw-webp-max-width>
                    <span>px</span>
                </div>
            </div>
        </section>

        <section class="xw-webp-section" aria-labelledby="xw-webp-filename-title" data-xw-webp-filename>
            <div class="xw-webp-section-heading">
                <h3 id="xw-webp-filename-title"><?php echo esc_html( xw_t( 'Nombres de archivo', 'File names' ) ); ?></h3>
                <p><?php echo esc_html( xw_t( 'Elimina espacios, apóstrofes y acentos para crear URLs limpias.', 'Removes spaces, apostrophes, and accents to create clean URLs.' ) ); ?></p>
            </div>
            <div class="xw-webp-inline-setting">
                <div>
                    <strong><?php echo esc_html( xw_t( 'Limpiar nombres al subir', 'Clean names on upload' ) ); ?></strong>
                    <p><?php echo esc_html( xw_t( 'Solo afecta a archivos nuevos; no renombra la biblioteca existente.', 'Only affects new files; it does not rename the existing library.' ) ); ?></p>
                </div>
                <label class="xw-switch">
                    <span class="screen-reader-text"><?php echo esc_html( xw_t( 'Limpiar nombres al subir', 'Clean names on upload' ) ); ?></span>
                    <input type="checkbox" name="xw_settings[webp][cleanup_filename]" value="1" <?php checked( ! empty( $settings['cleanup_filename'] ) ); ?> data-xw-webp-filename-toggle>
                    <span class="xw-switch-track" aria-hidden="true"><span></span></span>
                </label>
            </div>
            <div class="xw-webp-filename-preview" data-xw-webp-filename-preview>
                <label for="xw-webp-filename-example"><?php echo esc_html( xw_t( 'Pruebe un nombre', 'Try a file name' ) ); ?></label>
                <input id="xw-webp-filename-example" type="text" value="Men's Summer Shoes - Limited Edition 2026.jpg" maxlength="160" data-xw-webp-filename-input>
                <div class="xw-webp-filename-results">
                    <div>
                        <small><?php echo esc_html( xw_t( 'Nombre original', 'Original name' ) ); ?></small>
                        <output data-xw-webp-filename-original></output>
                    </div>
                    <div>
                        <small><?php echo esc_html( xw_t( 'Nombre limpio', 'Clean name' ) ); ?></small>
                        <output data-xw-webp-filename-clean></output>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <?php
}
