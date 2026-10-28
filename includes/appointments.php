<?php
/** One optional, site-wide appointment calendar. No public customer-data endpoint. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/appointments-access.php';
require_once __DIR__ . '/appointments-phone.php';

function xw_appt_table( $suffix = '' ) {
    global $wpdb;
    return $wpdb->prefix . 'xw_appointments' . $suffix;
}

function xw_appt_ready() { return '2' === get_option( 'xw_appointments_schema' ); }

function xw_appt_install() {
    if ( ! xw_feature_enabled( 'elementor_appointments' ) || xw_appt_ready() ) { return; }
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = xw_appt_table(); $locks = xw_appt_table( '_locks' ); $collate = $wpdb->get_charset_collate();
    dbDelta( "CREATE TABLE $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        starts_at datetime NOT NULL,
        ends_at datetime NOT NULL,
        timezone varchar(64) NOT NULL,
        status varchar(16) NOT NULL,
        hold_expires bigint(20) unsigned DEFAULT NULL,
        owner_token varchar(64) DEFAULT NULL,
        customer_name varchar(200) NOT NULL DEFAULT '',
        email varchar(200) NOT NULL DEFAULT '',
        phone varchar(100) NOT NULL DEFAULT '',
        form_name varchar(200) NOT NULL DEFAULT '',
        form_id varchar(100) NOT NULL DEFAULT '',
        fields longtext NOT NULL,
        created_at datetime NOT NULL,
        status_changed_at datetime DEFAULT NULL,
        rebooked_to bigint(20) unsigned DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY starts_at (starts_at),
        KEY email (email),
        KEY status (status)
    ) ENGINE=InnoDB $collate;" );
    dbDelta( "CREATE TABLE $locks (
        lock_key varchar(32) NOT NULL,
        owner varchar(64) NOT NULL,
        expires bigint(20) unsigned NOT NULL,
        PRIMARY KEY  (lock_key)
    ) ENGINE=InnoDB $collate;" );
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table &&
        $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $locks ) ) ) === $locks ) {
        $columns = $wpdb->get_col( "DESC $table", 0 );
        if ( in_array( 'status_changed_at', $columns, true ) && in_array( 'rebooked_to', $columns, true ) ) { update_option( 'xw_appointments_schema', '2', false ); }
    }
}
add_action( 'init', 'xw_appt_install', 20 );

function xw_appt_defaults() {
    return array( 'weekly' => array_fill( 0, 7, '' ), 'closed' => array(), 'duration' => 30, 'notice' => 120, 'horizon' => 90, 'colors' => array(), 'whatsapp_country_code' => '', 'messages' => array(), 'message_layouts' => array(), 'rebook_days' => 15 );
}
function xw_appt_settings() { return array_merge( xw_appt_defaults(), (array) get_option( 'xw_appointment_settings', array() ) ); }

function xw_appt_day( $value ) {
    if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $value ) ) { return false; }
    $date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );
    return $date && $date->format( 'Y-m-d' ) === $value ? $date : false;
}

/** Strict intervals; no overnight or overlapping ranges. Blank = closed. */
function xw_appt_intervals( $text ) {
    if ( ! is_string( $text ) || strlen( $text ) > 150 ) { return false; }
    if ( '' === trim( $text ) ) { return array(); }
    $ranges = array();
    foreach ( explode( ',', $text ) as $part ) {
        if ( ! preg_match( '/^\s*([01]\d|2[0-3]):([0-5]\d)\s*-\s*([01]\d|2[0-3]):([0-5]\d)\s*$/D', $part, $m ) ) { return false; }
        $start = (int) $m[1] * 60 + (int) $m[2]; $end = (int) $m[3] * 60 + (int) $m[4];
        if ( $start >= $end ) { return false; }
        $ranges[] = array( $start, $end );
    }
    usort( $ranges, static function( $a, $b ) { return $a[0] <=> $b[0]; } );
    foreach ( $ranges as $i => $range ) { if ( $i && $range[0] < $ranges[ $i - 1 ][1] ) { return false; } }
    return $ranges;
}

function xw_appt_sanitize_schedule( $raw ) {
    if ( ! is_array( $raw ) ) { return new WP_Error( 'schedule', xw_t( 'Configuración no válida.', 'Invalid configuration.' ) ); }
    foreach ( array( 'weekly', 'shifts', 'colors' ) as $key ) { if ( isset( $raw[ $key ] ) && ! is_array( $raw[ $key ] ) ) { return new WP_Error( 'schedule', xw_t( 'Configuración no válida.', 'Invalid configuration.' ) ); } }
    $out = xw_appt_defaults();
    foreach ( array( 'duration' => array( 5, 240 ), 'notice' => array( 0, 10080 ), 'horizon' => array( 1, 180 ) ) as $key => $limits ) {
        $value = $raw[ $key ] ?? null;
        if ( ! is_scalar( $value ) || ! preg_match( '/^\d+$/D', (string) $value ) || (int) $value < $limits[0] || (int) $value > $limits[1] ) {
            return new WP_Error( 'schedule', xw_t( 'Revisa la duración, la antelación y los días de reserva.', 'Check duration, advance notice and booking horizon.' ) );
        }
        $out[ $key ] = (int) $value;
    }
    for ( $day = 0; $day < 7; $day++ ) {
        $text = $raw['weekly'][ $day ] ?? '';
        if ( isset( $raw['shifts'] ) ) {
            if ( ! array_key_exists( $day, $raw['shifts'] ) ) { return new WP_Error( 'schedule', xw_t( 'Faltan días del horario. Recarga la página antes de guardar.', 'Some schedule days are missing. Reload the page before saving.' ) ); }
            $shifts = $raw['shifts'][ $day ];
            if ( ! is_array( $shifts ) || count( $shifts ) > 4 ) { return new WP_Error( 'schedule', xw_t( 'Puedes añadir hasta cuatro turnos por día.', 'You can add up to four shifts per day.' ) ); }
            $parts = array();
            foreach ( $shifts as $shift ) {
                if ( ! is_array( $shift ) || ! is_string( $shift['start'] ?? '' ) || ! is_string( $shift['end'] ?? '' ) ) { return new WP_Error( 'schedule', xw_t( 'Turno no válido.', 'Invalid shift.' ) ); }
                $start = $shift['start'] ?? ''; $end = $shift['end'] ?? '';
                if ( '' === $start && '' === $end ) { continue; }
                if ( ! preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/D', $start ) || ! preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/D', $end ) ) { return new WP_Error( 'schedule', xw_t( 'Elige el inicio y el final de cada turno, o deja ambos vacíos.', 'Choose the start and end of each shift, or leave both empty.' ) ); }
                // Preserve valid legacy endpoints; new dropdown choices use quarter-hours.
                $parts[] = $start . '-' . $end;
            }
            $text = implode( ', ', $parts );
        }
        $ranges = xw_appt_intervals( $text );
        if ( false === $ranges ) { return new WP_Error( 'schedule', xw_t( 'Revisa los turnos: el final debe ser posterior al inicio y no pueden solaparse.', 'Check shifts: the end must be after the start and shifts cannot overlap.' ) ); }
        if ( count( $ranges ) > 4 ) { return new WP_Error( 'schedule', xw_t( 'Puedes añadir hasta cuatro turnos por día.', 'You can add up to four shifts per day.' ) ); }
        $out['weekly'][ $day ] = implode( ', ', array_map( static function( $range ) {
            return sprintf( '%02d:%02d-%02d:%02d', intdiv( $range[0], 60 ), $range[0] % 60, intdiv( $range[1], 60 ), $range[1] % 60 );
        }, $ranges ) );
    }
    if ( ! is_string( $raw['closed'] ?? null ) || strlen( $raw['closed'] ) > 10000 ) { return new WP_Error( 'schedule', xw_t( 'Fechas bloqueadas no válidas o incompletas. Recarga antes de guardar.', 'Invalid or incomplete blocked dates. Reload before saving.' ) ); }
    foreach ( preg_split( '/[\s,]+/', trim( $raw['closed'] ?? '' ), -1, PREG_SPLIT_NO_EMPTY ) as $day ) {
        if ( ! xw_appt_day( $day ) ) { return new WP_Error( 'schedule', xw_t( 'Fechas bloqueadas: usa AAAA-MM-DD, una por línea.', 'Blocked dates: use YYYY-MM-DD, one per line.' ) ); }
        $out['closed'][] = $day;
    }
    $out['closed'] = array_values( array_unique( $out['closed'] ) );
    $country = $raw['whatsapp_country_code'] ?? ( xw_appt_settings()['whatsapp_country_code'] ?? '' );
    if ( ! is_string( $country ) || ( '' !== $country && ! preg_match( '/^[1-9]\d{0,2}$/D', $country ) ) ) { return new WP_Error( 'schedule', xw_t( 'Código de país de WhatsApp no válido. Usa de uno a tres dígitos sin +.', 'Invalid WhatsApp country code. Use one to three digits without +.' ) ); }
    $out['whatsapp_country_code'] = $country;
    $rebook_days = $raw['rebook_days'] ?? xw_appt_settings()['rebook_days'];
    if ( ! is_scalar( $rebook_days ) || ! preg_match( '/^\d+$/D', (string) $rebook_days ) || (int) $rebook_days < 1 || (int) $rebook_days > 3650 ) { return new WP_Error( 'days', xw_t( 'Indica de 1 a 3650 días para reagendar.', 'Enter 1 to 3650 rebooking days.' ) ); }
    $out['rebook_days'] = (int) $rebook_days;
    $messages = $raw['messages'] ?? xw_appt_settings()['messages'];
    if ( ! is_array( $messages ) ) { return new WP_Error( 'messages', xw_t( 'Mensajes no válidos.', 'Invalid messages.' ) ); }
    foreach ( array( 'pending', 'confirmed', 'cancelled', 'rebook' ) as $status ) {
        $message = $messages[ $status ] ?? '';
        if ( ! is_string( $message ) || mb_strlen( $message ) > 4000 ) { return new WP_Error( 'message', xw_t( 'Cada mensaje admite hasta 4000 caracteres.', 'Each message allows up to 4000 characters.' ) ); }
        $out['messages'][ $status ] = sanitize_textarea_field( $message );
    }
    $out['message_layouts'] = xw_appt_settings()['message_layouts'];
    foreach ( array( 'border', 'background', 'text', 'calendar_background' ) as $key ) {
        $color = $raw['colors'][ $key ] ?? ( xw_appt_settings()['colors'][ $key ] ?? '' );
        if ( ! is_string( $color ) || ( '' !== $color && ! preg_match( '/^#[a-fA-F0-9]{6}$/D', $color ) ) ) { return new WP_Error( 'schedule', xw_t( 'Usa colores hexadecimales de seis dígitos o deja el color automático.', 'Use six-digit hex colors or leave the color automatic.' ) ); }
        $out['colors'][ $key ] = $color;
    }
    return $out;
}

/** UTC storage; visitors always select in the site's published timezone. */
function xw_appt_candidates( $day, $settings = null, $now = null ) {
    $date = xw_appt_day( $day ); $settings = $settings ?? xw_appt_settings(); $now = $now ?? time();
    if ( ! $date || in_array( $day, $settings['closed'], true ) ) { return array(); }
    $today = ( new DateTimeImmutable( '@' . $now ) )->setTimezone( wp_timezone() )->setTime( 0, 0 );
    if ( $date < $today || $date > $today->modify( '+' . (int) $settings['horizon'] . ' days' ) ) { return array(); }
    $ranges = xw_appt_intervals( $settings['weekly'][ (int) $date->format( 'w' ) ] ?? '' );
    if ( false === $ranges ) { return array(); }
    $slots = array(); $duration = max( 5, (int) $settings['duration'] );
    foreach ( $ranges as $range ) {
        for ( $minute = $range[0]; $minute + $duration <= $range[1]; $minute += 15 ) {
            $value = $day . ' ' . sprintf( '%02d:%02d', intdiv( $minute, 60 ), $minute % 60 );
            $start = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $value, wp_timezone() );
            if ( ! $start || $start->format( 'Y-m-d H:i' ) !== $value || $start->getTimestamp() < $now + $settings['notice'] * 60 ) { continue; }
            $end = $start->getTimestamp() + $duration * 60;
            // A DST transition must not move an appointment outside its working interval.
            $local_end = ( new DateTimeImmutable( '@' . $end ) )->setTimezone( wp_timezone() );
            if ( $local_end->format( 'Y-m-d' ) !== $day || ( (int) $local_end->format( 'H' ) * 60 + (int) $local_end->format( 'i' ) ) > $range[1] ) { continue; }
            $slots[ str_replace( ' ', 'T', $value ) ] = array( 'value' => str_replace( ' ', 'T', $value ), 'label' => $start->format( 'H:i' ), 'start' => gmdate( 'Y-m-d H:i:s', $start->getTimestamp() ), 'end' => gmdate( 'Y-m-d H:i:s', $end ) );
        }
    }
    return $slots;
}

function xw_appt_busy( $start, $end, $exclude_id = 0 ) {
    global $wpdb;
    $table = xw_appt_table();
    $duration = max( 5, min( 240, (int) xw_appt_settings()['duration'] ) ) * MINUTE_IN_SECONDS;
    // A longer current duration can extend a reservation from before the query window.
    $lookback = gmdate( 'Y-m-d H:i:s', strtotime( $start . ' UTC' ) - $duration );
    $rows = $wpdb->get_results( $wpdb->prepare( "SELECT starts_at,ends_at,status FROM $table WHERE id <> %d AND status <> 'cancelled' AND (status <> 'holding' OR hold_expires > %d) AND starts_at < %s AND (ends_at > %s OR (status IN ('pending','confirmed','holding') AND starts_at > %s))", absint( $exclude_id ), time(), $end, $start, $lookback ), ARRAY_A );
    if ( $wpdb->last_error || ! is_array( $rows ) ) { return null; }
    $busy = array();
    foreach ( $rows as $row ) {
        if ( in_array( $row['status'], array( 'pending', 'confirmed', 'holding' ), true ) ) {
            $starts_at = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $row['starts_at'], new DateTimeZone( 'UTC' ) );
            if ( ! $starts_at ) { return null; }
            $row['ends_at'] = gmdate( 'Y-m-d H:i:s', $starts_at->getTimestamp() + $duration );
        }
        if ( $row['starts_at'] < $end && $row['ends_at'] > $start ) { $busy[] = array( 'starts_at' => $row['starts_at'], 'ends_at' => $row['ends_at'] ); }
    }
    return $busy;
}
function xw_appt_available( $day, $busy = null, $exclude_id = 0 ) {
    if ( ! xw_feature_enabled( 'elementor_appointments' ) || ! xw_appt_ready() ) { return array(); }
    $slots = xw_appt_candidates( $day );
    if ( ! $slots ) { return array(); }
    $first = reset( $slots ); $last = end( $slots );
    $busy = $busy ?? xw_appt_busy( $first['start'], $last['end'], $exclude_id );
    // Database failure must never make a slot appear free.
    if ( ! is_array( $busy ) ) { return array(); }
    return array_filter( $slots, static function( $slot ) use ( $busy ) {
        foreach ( $busy as $row ) { if ( $row['starts_at'] < $slot['end'] && $row['ends_at'] > $slot['start'] ) { return false; } }
        return true;
    } );
}

/** Read-only, bounded calendar data. No patient names, email, IDs or booking counts. */
function xw_appt_availability( $exclude_id = 0 ) {
    nocache_headers();
    if ( ! xw_feature_enabled( 'elementor_appointments' ) || ! xw_appt_ready() ) { wp_send_json_error( array( 'message' => xw_t( 'La agenda no está disponible.', 'The calendar is unavailable.' ) ), 503 ); }
    $month = isset( $_GET['month'] ) && is_string( $_GET['month'] ) ? wp_unslash( $_GET['month'] ) : '';
    $first = xw_appt_day( $month . '-01' );
    $today = current_datetime()->setTime( 0, 0 ); $settings = xw_appt_settings();
    if ( ! $first || $first->format( 'Y-m' ) < $today->format( 'Y-m' ) || $first > $today->modify( '+' . $settings['horizon'] . ' days' ) ) { wp_send_json_error( array( 'message' => xw_t( 'Mes no válido.', 'Invalid month.' ) ), 400 ); }
    $next = $first->modify( 'first day of next month' );
    $busy = xw_appt_busy( $first->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ), $next->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ), $exclude_id );
    if ( ! is_array( $busy ) ) { wp_send_json_error( array( 'message' => xw_t( 'No se pudo consultar la agenda.', 'Could not load the calendar.' ) ), 503 ); }
    $days = array();
    for ( $day = $first; $day < $next; $day = $day->modify( '+1 day' ) ) {
        $key = $day->format( 'Y-m-d' );
        $days[ $key ] = array_values( array_map( static function( $slot ) { return array( 'value' => $slot['value'], 'label' => $slot['label'] ); }, xw_appt_available( $key, $busy ) ) );
    }
    wp_send_json_success( array( 'days' => $days, 'timezone' => wp_timezone_string() ) );
}
add_action( 'wp_ajax_xw_appt_availability', 'xw_appt_availability' );
add_action( 'wp_ajax_nopriv_xw_appt_availability', 'xw_appt_availability' );

/** Cross-request mutex: unique primary key serializes overlap-check + insert. */
function xw_appt_lock() {
    global $wpdb;
    $table = xw_appt_table( '_locks' ); $owner = wp_generate_password( 48, false, false );
    $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE lock_key = 'agenda' AND expires < %d", time() ) );
    $previous = $wpdb->suppress_errors( true );
    $inserted = $wpdb->insert( $table, array( 'lock_key' => 'agenda', 'owner' => $owner, 'expires' => time() + 120 ) );
    $wpdb->suppress_errors( $previous );
    return $inserted ? $owner : false;
}
function xw_appt_unlock( $owner ) {
    global $wpdb;
    $wpdb->delete( xw_appt_table( '_locks' ), array( 'lock_key' => 'agenda', 'owner' => $owner ) );
}

/** Store only ordinary form fields, not passwords, files, hidden tokens or CAPTCHA. */
function xw_appt_customer( $record, $item ) {
    $out = array( 'customer_name' => '', 'email' => '', 'phone' => '', 'fields' => array() );
    $types = array( 'text', 'email', 'tel', 'textarea', 'select', 'radio', 'checkbox', 'date', 'time', 'number', 'url', 'xw_appointment' );
    foreach ( array_slice( (array) $record->get( 'fields' ), 0, 50, true ) as $id => $field ) {
        if ( ! in_array( $field['type'] ?? '', $types, true ) ) { continue; }
        $value = mb_substr( sanitize_textarea_field( xw_appt_field_string( $field['value'] ?? '' ) ), 0, 2000 );
        $entry = array( 'id' => mb_substr( sanitize_text_field( (string) $id ), 0, 100 ), 'type' => $field['type'], 'label' => mb_substr( sanitize_text_field( $field['title'] ?? $id ), 0, 150 ), 'value' => $value );
        if ( 'xw_appointment' === $field['type'] ) { $entry['role'] = 'appointment'; }
        if ( ! empty( $item['xw_appt_office'] ) && $id === $item['xw_appt_office'] ) { $entry['role'] = 'office'; }
        $out['fields'][] = $entry;
        foreach ( array( 'customer_name' => array( 'xw_appt_name', 'text', 200 ), 'email' => array( 'xw_appt_email', 'email', 200 ), 'phone' => array( 'xw_appt_phone', 'tel', 100 ) ) as $target => $rule ) {
            $mapped = $item[ $rule[0] ] ?? '';
            if ( ( $mapped && $id === $mapped ) || ( ! $mapped && ! $out[ $target ] && $field['type'] === $rule[1] ) ) { foreach ( $out['fields'] as &$saved_field ) { if ( ( $saved_field['contact_role'] ?? '' ) === $target ) { unset( $saved_field['contact_role'] ); } } unset( $saved_field ); $out[ $target ] = mb_substr( sanitize_text_field( $value ), 0, $rule[2] ); $out['fields'][ count( $out['fields'] ) - 1 ]['contact_role'] = $target; }
        }
    }
    $out['fields'] = wp_json_encode( $out['fields'] );
    return $out;
}

/** A hold is created only after ALL Elementor field validators have passed. */
function xw_appt_process( $record, $handler ) {
    $fields = array_filter( (array) $record->get( 'fields' ), static function( $field ) { return 'xw_appointment' === ( $field['type'] ?? '' ); } );
    if ( ! $fields ) { return; }
    $field = reset( $fields ); $id = $field['id'];
    if ( ! xw_feature_enabled( 'elementor_appointments' ) || ! xw_appt_ready() || count( $fields ) !== 1 ) {
        $handler->add_error( $id, xw_t( 'Activa la agenda y utiliza un solo campo Cita por formulario.', 'Enable the calendar and use one Appointment field per form.' ) ); return;
    }
    if ( ! empty( $handler->errors ) || ! $handler->is_success ) { return; }
    if ( empty( $field['value'] ) ) {
        $handler->add_error( $id, xw_t( 'Selecciona una fecha y una hora.', 'Select a date and time.' ) ); return;
    }
    $owner = xw_appt_lock();
    if ( ! $owner ) { $handler->add_error( $id, xw_t( 'La agenda está ocupada. Inténtalo de nuevo.', 'The calendar is busy. Please try again.' ) ); return; }
    global $wpdb;
    try {
        $value = is_string( $field['value'] ) ? $field['value'] : '';
        $slots = preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $value ) ? xw_appt_available( substr( $value, 0, 10 ) ) : array();
        if ( ! isset( $slots[ $value ] ) ) { $handler->add_error( $id, xw_t( 'Esa cita ya no está disponible. Selecciona otra fecha u hora.', 'That appointment is no longer available. Select another date or time.' ) ); return; }
        // Supplement, not replace, Elementor's configured CAPTCHA / honeypot.
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $rate_key = 'xw_appt_rate_' . substr( hash_hmac( 'sha256', (string) $ip, wp_salt() ), 0, 32 );
        $window = get_transient( $rate_key );
        if ( ! is_array( $window ) || ( $window['expires'] ?? 0 ) <= time() ) { $window = array( 'count' => 0, 'expires' => time() + 10 * MINUTE_IN_SECONDS ); }
        $attempts = (int) $window['count'];
        if ( $attempts >= 30 ) { $handler->add_error( $id, xw_t( 'Demasiados intentos. Espera unos minutos antes de volver a reservar.', 'Too many attempts. Wait a few minutes before booking again.' ) ); return; }
        $window['count'] = $attempts + 1;
        set_transient( $rate_key, $window, max( 1, $window['expires'] - time() ) );
        $item = array();
        foreach ( (array) $record->get_form_settings( 'form_fields' ) as $candidate ) { if ( ( $candidate['custom_id'] ?? '' ) === $id ) { $item = $candidate; break; } }
        $row = array_merge( xw_appt_customer( $record, $item ), array(
            'starts_at' => $slots[ $value ]['start'], 'ends_at' => $slots[ $value ]['end'], 'timezone' => wp_timezone_string(),
            'status' => 'holding', 'hold_expires' => time() + 600, 'owner_token' => $owner,
            'form_name' => mb_substr( sanitize_text_field( (string) $record->get_form_settings( 'form_name' ) ), 0, 200 ),
            'form_id' => mb_substr( sanitize_text_field( (string) $record->get_form_settings( 'id' ) ), 0, 100 ), 'created_at' => gmdate( 'Y-m-d H:i:s' ),
        ) );
        if ( ! $wpdb->insert( xw_appt_table(), $row ) ) { $handler->add_error( $id, xw_t( 'No se pudo guardar la cita. Inténtalo de nuevo.', 'Could not save the appointment. Please try again.' ) ); return; }
        $GLOBALS['xw_appt_holds'][ spl_object_id( $record ) ] = array( 'id' => (int) $wpdb->insert_id, 'owner' => $owner );
    } finally { xw_appt_unlock( $owner ); }
}
add_action( 'elementor_pro/forms/process', 'xw_appt_process', 999, 2 );

function xw_appt_complete( $record, $handler ) {
    $key = spl_object_id( $record ); $hold = $GLOBALS['xw_appt_holds'][ $key ] ?? null;
    if ( ! $hold ) { return; }
    global $wpdb;
    $where = array( 'id' => $hold['id'], 'owner_token' => $hold['owner'], 'status' => 'holding' );
    if ( $handler->is_success && empty( $handler->errors ) && empty( $handler->messages['error'] ) && empty( $handler->messages['admin_error'] ) ) {
        $saved = $wpdb->update( xw_appt_table(), array( 'status' => 'pending', 'status_changed_at' => gmdate( 'Y-m-d H:i:s' ), 'hold_expires' => null, 'owner_token' => null ), $where );
        if ( 1 !== $saved ) { $handler->add_error_message( xw_t( 'No se pudo confirmar el registro de la cita. Contacta con el sitio antes de repetir el envío.', 'Could not finalize the appointment record. Contact the site before submitting again.' ) ); return; }
        $handler->add_response_data( 'xw_appointment', array( 'saved' => true ) );
    } else { $wpdb->delete( xw_appt_table(), $where ); }
    unset( $GLOBALS['xw_appt_holds'][ $key ] );
}
add_action( 'elementor_pro/forms/new_record', 'xw_appt_complete', 999, 2 );
add_action( 'shutdown', static function() {
    global $wpdb;
    foreach ( $GLOBALS['xw_appt_holds'] ?? array() as $hold ) { $wpdb->delete( xw_appt_table(), array( 'id' => $hold['id'], 'owner_token' => $hold['owner'], 'status' => 'holding' ) ); }
} );

function xw_appt_register_field( $registrar ) {
    if ( ! xw_feature_enabled( 'elementor_appointments' ) || ! class_exists( '\ElementorPro\Modules\Forms\Fields\Field_Base' ) ) { return; }
    require_once __DIR__ . '/appointments-field.php';
    $registrar->register( new XW_Appointment_Field() );
}
add_action( 'elementor_pro/forms/fields/register', 'xw_appt_register_field' );

function xw_appt_assets() {
    if ( ! xw_feature_enabled( 'elementor_appointments' ) ) { return; }
    $base = plugin_dir_path( XW_FUNCTIONS_FILE ); $url = plugin_dir_url( XW_FUNCTIONS_FILE );
    // Elementor owns this exact Flatpickr library. No CDN or second calendar library.
    if ( ! wp_script_is( 'flatpickr', 'registered' ) && defined( 'ELEMENTOR_ASSETS_URL' ) ) { wp_register_script( 'flatpickr', ELEMENTOR_ASSETS_URL . 'lib/flatpickr/flatpickr.min.js', array(), '4.6.13', true ); }
    if ( ! wp_style_is( 'flatpickr', 'registered' ) && defined( 'ELEMENTOR_ASSETS_URL' ) ) { wp_register_style( 'flatpickr', ELEMENTOR_ASSETS_URL . 'lib/flatpickr/flatpickr.min.css', array(), '4.6.13' ); }
    wp_register_style( 'xw-appointments', $url . 'assets/css/appointments.css', array( 'flatpickr' ), filemtime( $base . 'assets/css/appointments.css' ) );
    wp_register_script( 'xw-appointments', $url . 'assets/js/appointments.js', array( 'flatpickr', 'jquery' ), filemtime( $base . 'assets/js/appointments.js' ), true );
    $settings = xw_appt_settings();
    wp_localize_script( 'xw-appointments', 'xwAppointments', array(
        'url' => admin_url( 'admin-ajax.php' ), 'today' => current_datetime()->format( 'Y-m-d' ), 'max' => current_datetime()->modify( '+' . $settings['horizon'] . ' days' )->format( 'Y-m-d' ),
        'locale' => xw_interface_language(), 'timezone' => wp_timezone_string(),
        'colors' => array_intersect_key( array_filter( (array) $settings['colors'], static function( $color ) { return is_string( $color ) && preg_match( '/^#[a-fA-F0-9]{6}$/D', $color ); } ), array_flip( array( 'border', 'background', 'text', 'calendar_background' ) ) ),
        'back' => xw_t( 'Volver al calendario', 'Back to calendar' ), 'close' => xw_t( 'Cerrar', 'Close' ),
        'zone' => xw_t( 'Zona horaria: %s. Cita sujeta a confirmación.', 'Timezone: %s. Appointment subject to confirmation.' ),
        'choose' => xw_t( 'Selecciona una hora', 'Select a time' ), 'loading' => xw_t( 'Consultando disponibilidad…', 'Checking availability…' ),
        'ready' => xw_t( 'Selecciona un día con círculo y después una hora. Días grises: no disponibles.', 'Select a circled day, then a time. Grey days are unavailable.' ),
        'empty' => xw_t( 'No hay citas disponibles para este día.', 'No appointments available for this day.' ),
        'error' => xw_t( 'No se pudo cargar la agenda. Pulsa Reintentar.', 'Could not load the calendar. Press Retry.' ),
        'required' => xw_t( 'Selecciona una fecha y una hora.', 'Select a date and time.' ),
        'retry' => xw_t( 'Reintentar', 'Retry' ), 'time' => xw_t( 'Hora de la cita', 'Appointment time' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'xw_appt_assets', 25 );
add_action( 'elementor/preview/enqueue_scripts', 'xw_appt_assets', 5 );
add_action( 'elementor/editor/after_enqueue_scripts', static function() {
    if ( ! xw_feature_enabled( 'elementor_appointments' ) ) { return; }
    wp_enqueue_script( 'xw-appointments-editor', plugin_dir_url( XW_FUNCTIONS_FILE ) . 'assets/js/appointments-editor.js', array( 'elementor-editor' ), filemtime( plugin_dir_path( XW_FUNCTIONS_FILE ) . 'assets/js/appointments-editor.js' ), true );
    wp_localize_script( 'xw-appointments-editor', 'xwAppointmentPreview', array( 'date' => xw_t( 'Selecciona fecha y hora', 'Select date and time' ), 'time' => xw_t( 'Hora de la cita', 'Appointment time' ), 'retry' => xw_t( 'Reintentar', 'Retry' ), 'help' => xw_t( 'Disponibilidad configurada en WordPress → Citas.', 'Availability configured in WordPress → Appointments.' ) ) );
} );
add_action( 'admin_init', static function() {
    if ( ! xw_appt_ready() || ! current_user_can( 'manage_options' ) ) { return; }
    global $wpdb; $table = xw_appt_table();
    $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE status = 'holding' AND hold_expires < %d", time() - DAY_IN_SECONDS ) );
} );

require_once __DIR__ . '/appointments-admin.php';
require_once __DIR__ . '/appointments-records.php';
require_once __DIR__ . '/appointments-message-builder.php';
require_once __DIR__ . '/appointments-messages.php';
require_once __DIR__ . '/appointments-cards.php';
require_once __DIR__ . '/appointments-privacy.php';
