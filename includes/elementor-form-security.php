<?php
/** Server-side cooldown shared by the site's Elementor forms. */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_form_security_fields() {
    return array(
        'days'    => array( 'default' => 0, 'max' => 365, 'label' => xw_t( 'Días', 'Days' ) ),
        'hours'   => array( 'default' => 0, 'max' => 23, 'label' => xw_t( 'Horas', 'Hours' ) ),
        'minutes' => array( 'default' => 5, 'max' => 59, 'label' => xw_t( 'Minutos', 'Minutes' ) ),
    );
}

function xw_form_security_sanitize( $input ) {
    $settings = array();
    foreach ( xw_form_security_fields() as $key => $field ) {
        $value = $input[ $key ] ?? $field['default'];
        $settings[ $key ] = is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) )
            ? min( $field['max'], max( 0, (int) $value ) )
            : 0;
    }
    return $settings;
}

function xw_form_security_storage_ready( $table ) {
    global $wpdb;
    if ( get_option( 'xw_form_security_schema' ) === '1' ) {
        return true;
    }

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( "CREATE TABLE $table (
        ip_key char(64) NOT NULL,
        owner char(32) NOT NULL DEFAULT '',
        expires bigint(20) unsigned NOT NULL,
        PRIMARY KEY  (ip_key),
        KEY expires (expires)
    ) " . $wpdb->get_charset_collate() . ';' );

    if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) {
        return false;
    }
    update_option( 'xw_form_security_schema', '1', false );
    return true;
}

function xw_form_security_error( $handler, $message ) {
    // Elementor checks its field errors to stop processing and uses the message for the global notice.
    $handler->add_error( 'xw_form_security' );
    $handler->add_error_message( $message );
}

function xw_form_security_validate( $record, $handler ) {
    if ( ! $handler->is_success || ! empty( $handler->errors ) ) {
        return;
    }

    $suite = xw_get_settings();
    if ( empty( $suite['features']['elementor_form_security'] ) ) { return; }

    $settings = xw_form_security_sanitize( $suite['form_security'] );
    $wait = $settings['days'] * DAY_IN_SECONDS + $settings['hours'] * HOUR_IN_SECONDS + $settings['minutes'] * MINUTE_IN_SECONDS;
    if ( ! $wait ) { return; }

    // Trust the server's client address, never forwarding headers submitted by the visitor.
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $packed = is_string( $ip ) && filter_var( $ip, FILTER_VALIDATE_IP ) ? inet_pton( $ip ) : false;
    $unavailable = xw_t( 'No se pudo comprobar el límite de envíos. Inténtalo de nuevo en unos minutos.', 'Could not check the submission limit. Please try again in a few minutes.' );
    if ( false === $packed ) {
        xw_form_security_error( $handler, $unavailable );
        return;
    }
    if ( 0 === strpos( $packed, str_repeat( "\0", 10 ) . "\xff\xff" ) ) {
        $packed = substr( $packed, 12 );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'xw_form_security';
    if ( ! xw_form_security_storage_ready( $table ) ) {
        xw_form_security_error( $handler, $unavailable );
        return;
    }

    $key = hash_hmac( 'sha256', $packed, wp_salt() );
    $owner = wp_generate_password( 32, false, false );
    $now = time();
    $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE ip_key = %s AND expires <= %d", $key, $now ) );
    // A unique key prevents simultaneous requests from dispatching the same IP's form actions.
    $previous = $wpdb->suppress_errors( true );
    $inserted = $wpdb->insert( $table, array( 'ip_key' => $key, 'owner' => $owner, 'expires' => $now + 10 * MINUTE_IN_SECONDS ), array( '%s', '%s', '%d' ) );
    $wpdb->suppress_errors( $previous );

    if ( 1 !== $inserted ) {
        $existing = $wpdb->get_row( $wpdb->prepare( "SELECT owner, expires FROM $table WHERE ip_key = %s", $key ) );
        if ( $existing && '' === $existing->owner ) {
            $remaining = (int) $existing->expires - $now;
            $duration = $remaining < MINUTE_IN_SECONDS
                ? xw_t( 'menos de un minuto', 'less than a minute' )
                : human_time_diff( $now, (int) $existing->expires );
            $unavailable = sprintf( xw_t( 'Ya se ha enviado un formulario desde esta conexión. Puedes volver a enviarlo en %s.', 'A form has already been sent from this connection. You can submit again in %s.' ), $duration );
        } elseif ( $existing ) {
            $unavailable = xw_t( 'Hay un envío en curso desde esta conexión. Espera un momento y vuelve a intentarlo.', 'A submission is in progress from this connection. Please wait a moment and try again.' );
        }
        xw_form_security_error( $handler, $unavailable );
        return;
    }

    $hold = array( 'ip_key' => $key, 'owner' => $owner );
    $release = static function() use ( $wpdb, $table, $hold ) { $wpdb->delete( $table, $hold ); };
    add_action( 'shutdown', $release );
    add_action( 'elementor_pro/forms/new_record', static function( $submitted, $result ) use ( $record, $wpdb, $table, $hold, $wait, $release ) {
        if ( $record !== $submitted ) { return; }

        if ( $result->is_success && empty( $result->errors ) && empty( $result->messages['error'] ) && empty( $result->messages['admin_error'] ) ) {
            $saved = $wpdb->update( $table, array( 'owner' => '', 'expires' => time() + $wait ), $hold, array( '%s', '%d' ) );
            // Keep the short hold until expiry if storing the cooldown fails after actions have run.
            if ( false === $saved ) {
                remove_action( 'shutdown', $release );
                return;
            }
        } else {
            $release();
        }
        remove_action( 'shutdown', $release );
    }, PHP_INT_MAX, 2 );
}

add_action( 'elementor_pro/forms/validation', 'xw_form_security_validate', PHP_INT_MAX, 2 );

add_action( 'wp_scheduled_delete', static function() {
    if ( get_option( 'xw_form_security_schema' ) !== '1' ) { return; }

    global $wpdb;
    $table = $wpdb->prefix . 'xw_form_security';
    $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE expires <= %d", time() ) );
} );
