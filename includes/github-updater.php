<?php
/**
 * Actualizaciones de Creative Pear Suite mediante GitHub Releases.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'XW_GITHUB_REPOSITORY', 'Xavileaks/CreativePear-Suite' );
define( 'XW_GITHUB_RELEASE_ASSET', 'creativepear-suite.zip' );

/**
 * Obtiene la última publicación estable y la conserva durante quince minutos.
 *
 * @param bool $force_refresh Ignorar la caché guardada.
 * @return array|WP_Error
 */
function xw_github_get_latest_release( $force_refresh = false ) {
    $cache_key = 'xw_github_latest_release';

    if ( ! $force_refresh ) {
        $cached = get_site_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached;
        }
    }

    $response = wp_remote_get(
        'https://api.github.com/repos/' . XW_GITHUB_REPOSITORY . '/releases/latest',
        array(
            'headers' => array(
                'Accept'               => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent'           => 'CreativePear-Suite/' . XW_FUNCTIONS_VERSION,
            ),
            'timeout' => 10,
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code( $response );

    if ( 200 !== $status_code ) {
        return new WP_Error(
            'xw_github_release_request_failed',
            sprintf( xw_t( 'GitHub respondió con el código HTTP %d.', 'GitHub returned HTTP status code %d.' ), (int) $status_code )
        );
    }

    $release = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( ! is_array( $release ) || empty( $release['tag_name'] ) ) {
        return new WP_Error( 'xw_github_invalid_release', xw_t( 'GitHub devolvió una publicación no válida.', 'GitHub returned an invalid release.' ) );
    }

    $package_url = '';

    if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
        foreach ( $release['assets'] as $asset ) {
            if (
                isset( $asset['name'], $asset['browser_download_url'] ) &&
                XW_GITHUB_RELEASE_ASSET === $asset['name']
            ) {
                $package_url = esc_url_raw( $asset['browser_download_url'] );
                break;
            }
        }
    }

    $result = array(
        'version'      => ltrim( sanitize_text_field( $release['tag_name'] ), 'vV' ),
        'package'      => $package_url,
        'details_url'  => isset( $release['html_url'] ) ? esc_url_raw( $release['html_url'] ) : '',
        'published_at' => isset( $release['published_at'] ) ? sanitize_text_field( $release['published_at'] ) : '',
        'notes'        => isset( $release['body'] ) ? (string) $release['body'] : '',
    );

    set_site_transient( $cache_key, $result, 15 * MINUTE_IN_SECONDS );

    return $result;
}

/**
 * Informa a WordPress cuando existe una versión más reciente.
 *
 * @param object $transient Datos de actualización de plugins.
 * @return object
 */
function xw_github_check_for_update( $transient ) {
    if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
        return $transient;
    }

    // WordPress ya controla la frecuencia de sus comprobaciones. Consultar la
    // publicación directamente evita conservar como "última" una versión que
    // se publicó justo después de haber llenado nuestra caché interna.
    $release = xw_github_get_latest_release( true );

    if (
        is_wp_error( $release ) ||
        empty( $release['version'] ) ||
        empty( $release['package'] ) ||
        ! version_compare( XW_FUNCTIONS_VERSION, $release['version'], '<' )
    ) {
        return $transient;
    }

    $plugin_file = plugin_basename( XW_FUNCTIONS_FILE );
    $update      = new stdClass();
    $update->id  = 'github.com/' . XW_GITHUB_REPOSITORY;
    $update->slug = dirname( $plugin_file );
    $update->plugin = $plugin_file;
    $update->new_version = $release['version'];
    $update->url = $release['details_url'];
    $update->package = $release['package'];

    $transient->response[ $plugin_file ] = $update;

    return $transient;
}
add_filter( 'pre_set_site_transient_update_plugins', 'xw_github_check_for_update' );

/**
 * Responde al mecanismo oficial de Update URI para repositorios externos.
 *
 * WordPress usa el hostname de Update URI para crear este filtro dinámico. Al
 * devolver también la versión instalada como "sin actualización", mantiene
 * disponibles los controles de actualización automática del plugin.
 *
 * @param array|false $update      Información proporcionada por otro actualizador.
 * @param array       $plugin_data Cabeceras del plugin instalado.
 * @param string      $plugin_file Ruta relativa del archivo principal.
 * @param string[]    $locales     Idiomas instalados.
 * @return array|false
 */
function xw_github_update_uri_response( $update, $plugin_data, $plugin_file, $locales ) {
    unset( $locales );

    if ( plugin_basename( XW_FUNCTIONS_FILE ) !== $plugin_file ) {
        return $update;
    }

    $release = xw_github_get_latest_release( true );

    if ( is_wp_error( $release ) || empty( $release['version'] ) ) {
        return $update;
    }

    $update_uri = ! empty( $plugin_data['UpdateURI'] )
        ? esc_url_raw( $plugin_data['UpdateURI'] )
        : 'https://github.com/' . XW_GITHUB_REPOSITORY;

    return array(
        'id'           => $update_uri,
        'slug'         => dirname( $plugin_file ),
        'version'      => $release['version'],
        'url'          => $release['details_url'],
        'package'      => $release['package'],
        'requires_php' => '7.4',
        'autoupdate'   => false,
    );
}
add_filter( 'update_plugins_github.com', 'xw_github_update_uri_response', 10, 4 );

/**
 * Completa la ventana de detalles que WordPress muestra antes de actualizar.
 *
 * @param false|object|array $result Resultado previo.
 * @param string             $action Acción solicitada.
 * @param object             $args Argumentos de la consulta.
 * @return false|object|array
 */
function xw_github_plugin_information( $result, $action, $args ) {
    if (
        'plugin_information' !== $action ||
        empty( $args->slug ) ||
        dirname( plugin_basename( XW_FUNCTIONS_FILE ) ) !== $args->slug
    ) {
        return $result;
    }

    $release = xw_github_get_latest_release();

    if ( is_wp_error( $release ) || empty( $release['version'] ) ) {
        return $result;
    }

    $information = new stdClass();
    $information->name = 'Creative Pear Suite';
    $information->slug = dirname( plugin_basename( XW_FUNCTIONS_FILE ) );
    $information->version = $release['version'];
    $information->author = '<a href="https://creativepearagency.com">Creative Pear Agency</a>';
    $information->homepage = 'https://github.com/' . XW_GITHUB_REPOSITORY;
    $information->download_link = $release['package'];
    $information->last_updated = $release['published_at'];
    $information->sections = array(
        'description' => xw_t( 'Funciones y personalizaciones reutilizables para sitios WordPress.', 'Reusable features and customizations for WordPress websites.' ),
        'changelog'   => nl2br( esc_html( $release['notes'] ) ),
    );

    return $information;
}
add_filter( 'plugins_api', 'xw_github_plugin_information', 20, 3 );

/**
 * Elimina la caché después de actualizar este plugin.
 *
 * @param WP_Upgrader $upgrader Actualizador de WordPress.
 * @param array       $options Datos del proceso finalizado.
 * @return void
 */
function xw_github_clear_release_cache( $upgrader, $options ) {
    if (
        empty( $options['action'] ) ||
        'update' !== $options['action'] ||
        empty( $options['type'] ) ||
        'plugin' !== $options['type'] ||
        empty( $options['plugins'] ) ||
        ! in_array( plugin_basename( XW_FUNCTIONS_FILE ), (array) $options['plugins'], true )
    ) {
        return;
    }

    delete_site_transient( 'xw_github_latest_release' );
}
add_action( 'upgrader_process_complete', 'xw_github_clear_release_cache', 10, 2 );
