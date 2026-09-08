<?php
/**
 * Actualizaciones de Creative Pear Suite mediante GitHub Releases.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'XW_GITHUB_REPOSITORY', 'Xavileaks/CreativePear-Suite' );
define( 'XW_GITHUB_RELEASE_ASSET', 'creativepear-suite.zip' );
define( 'XW_GITHUB_UPDATE_INTERVAL', 10 * MINUTE_IN_SECONDS );
define( 'XW_GITHUB_UPDATE_CRON_HOOK', 'xw_github_ten_minute_update_check' );

/**
 * Obtiene la última publicación estable y la conserva durante diez minutos.
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

    set_site_transient( $cache_key, $result, XW_GITHUB_UPDATE_INTERVAL );

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

    $release = xw_github_get_latest_release();

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
 * Añade una frecuencia de diez minutos sin modificar el cron de otros plugins.
 *
 * @param array $schedules Frecuencias registradas.
 * @return array
 */
function xw_github_add_ten_minute_schedule( $schedules ) {
    $schedules['xw_ten_minutes'] = array(
        'interval' => XW_GITHUB_UPDATE_INTERVAL,
        'display'  => xw_t( 'Cada diez minutos', 'Every ten minutes' ),
    );

    return $schedules;
}
add_filter( 'cron_schedules', 'xw_github_add_ten_minute_schedule' );

/**
 * Programa la comprobación. También se ejecuta en init para instalaciones
 * existentes, ya que una actualización no vuelve a disparar la activación.
 *
 * @return void
 */
function xw_github_schedule_update_checks() {
    if ( ! wp_next_scheduled( XW_GITHUB_UPDATE_CRON_HOOK ) ) {
        wp_schedule_event(
            time() + XW_GITHUB_UPDATE_INTERVAL,
            'xw_ten_minutes',
            XW_GITHUB_UPDATE_CRON_HOOK
        );
    }
}
add_action( 'init', 'xw_github_schedule_update_checks' );
register_activation_hook( XW_FUNCTIONS_FILE, 'xw_github_schedule_update_checks' );

/**
 * Elimina únicamente el cron perteneciente a este plugin.
 *
 * @return void
 */
function xw_github_unschedule_update_checks() {
    wp_clear_scheduled_hook( XW_GITHUB_UPDATE_CRON_HOOK );
}
register_deactivation_hook( XW_FUNCTIONS_FILE, 'xw_github_unschedule_update_checks' );

/**
 * Actualiza el transient de WordPress sin consultar el resto de plugins.
 *
 * @return void
 */
function xw_github_refresh_update_transient() {
    $lock_key = 'xw_github_ten_minute_update_lock';

    if ( false !== get_site_transient( $lock_key ) ) {
        return;
    }

    set_site_transient( $lock_key, 1, XW_GITHUB_UPDATE_INTERVAL );

    $release = xw_github_get_latest_release();

    if ( is_wp_error( $release ) || empty( $release['version'] ) ) {
        return;
    }

    $transient = get_site_transient( 'update_plugins' );

    if ( ! is_object( $transient ) ) {
        $transient = new stdClass();
    }

    if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
        $transient->response = array();
    }

    if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
        $transient->no_update = array();
    }

    $plugin_file = plugin_basename( XW_FUNCTIONS_FILE );

    if (
        ! empty( $release['package'] ) &&
        version_compare( XW_FUNCTIONS_VERSION, $release['version'], '<' )
    ) {
        $update = new stdClass();
        $update->id = 'github.com/' . XW_GITHUB_REPOSITORY;
        $update->slug = dirname( $plugin_file );
        $update->plugin = $plugin_file;
        $update->new_version = $release['version'];
        $update->url = $release['details_url'];
        $update->package = $release['package'];

        $transient->response[ $plugin_file ] = $update;
        unset( $transient->no_update[ $plugin_file ] );
    } else {
        unset( $transient->response[ $plugin_file ] );
    }

    // Evita que nuestro propio filtro repita la consulta al guardar el dato.
    remove_filter( 'pre_set_site_transient_update_plugins', 'xw_github_check_for_update' );
    set_site_transient( 'update_plugins', $transient );
    add_filter( 'pre_set_site_transient_update_plugins', 'xw_github_check_for_update' );
}
add_action( XW_GITHUB_UPDATE_CRON_HOOK, 'xw_github_refresh_update_transient' );

/**
 * Permite que el aviso ya esté preparado al entrar en la administración.
 * El bloqueo interno impide más de una consulta cada diez minutos.
 *
 * @return void
 */
function xw_github_maybe_refresh_update_transient_in_admin() {
    if ( current_user_can( 'update_plugins' ) ) {
        xw_github_refresh_update_transient();
    }
}
add_action( 'admin_init', 'xw_github_maybe_refresh_update_transient_in_admin' );

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

    $release = xw_github_get_latest_release();

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
    delete_site_transient( 'xw_github_ten_minute_update_lock' );
}
add_action( 'upgrader_process_complete', 'xw_github_clear_release_cache', 10, 2 );
