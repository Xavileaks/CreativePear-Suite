<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_appt_field_string( $value ) {
    if ( is_scalar( $value ) ) { return (string) $value; }
    if ( ! is_array( $value ) ) { return ''; }
    $parts = array(); array_walk_recursive( $value, static function( $part ) use ( &$parts ) { if ( is_scalar( $part ) && count( $parts ) < 100 ) { $parts[] = (string) $part; } } );
    return implode( ', ', $parts );
}
function xw_appt_record_revision( $row ) {
    return hash( 'sha256', wp_json_encode( array_intersect_key( $row, array_flip( array( 'id', 'fields', 'customer_name', 'email', 'phone', 'starts_at', 'ends_at', 'status', 'status_changed_at', 'rebooked_to' ) ) ) ) );
}
/** Normalize legacy snapshots without changing labels or guessing fields from a different form. */
function xw_appt_record_fields( $row ) {
    $fields = is_array( $row['fields'] ) ? $row['fields'] : json_decode( $row['fields'], true ); $out = array(); $mapped = array();
    foreach ( array_slice( is_array( $fields ) ? $fields : array(), 0, 50 ) as $index => $field ) {
        if ( ! is_array( $field ) || ! is_string( $field['label'] ?? null ) || ! is_scalar( $field['value'] ?? null ) ) { continue; }
        $field['value'] = (string) $field['value']; $field['id'] = $field['id'] ?? 'legacy_' . $index; $field['type'] = $field['type'] ?? 'text';
        if ( 'xw_appointment' === $field['type'] || ( str_starts_with( $field['id'], 'legacy_' ) && preg_match( '/^(?:\d{4}-\d{2}-\d{2}T\d{2}:\d{2})$/D', $field['value'] ) && preg_match( '/cita|appointment|fecha preferida/i', $field['label'] ) ) ) { $field['role'] = 'appointment'; }
        $role = $field['contact_role'] ?? '';
        if ( ! in_array( $role, array( 'customer_name', 'email', 'phone' ), true ) ) {
            $role = '';
            foreach ( array( 'customer_name', 'email', 'phone' ) as $candidate ) {
                if ( empty( $mapped[ $candidate ] ) && '' !== (string) $row[ $candidate ] && $field['value'] === (string) $row[ $candidate ] ) { $role = $candidate; break; }
            }
        }
        if ( $role && ! isset( $mapped[ $role ] ) ) { $field['contact_role'] = $role; $field['value'] = (string) $row[ $role ]; $mapped[ $role ] = true; }
        else { unset( $field['contact_role'] ); }
        $out[] = $field;
    }
    foreach ( array( 'customer_name' => xw_t( 'Nombre', 'Name' ), 'email' => xw_t( 'Correo electrónico', 'Email' ), 'phone' => xw_t( 'Teléfono', 'Phone' ) ) as $role => $label ) {
        if ( ! isset( $mapped[ $role ] ) ) { $out[] = array( 'id' => '__' . $role, 'label' => $label, 'value' => (string) $row[ $role ], 'type' => 'text', 'contact_role' => $role ); }
    }
    return $out;
}
function xw_appt_sync_date_field( $fields, $value ) {
    foreach ( $fields as &$field ) { if ( 'appointment' === ( $field['role'] ?? '' ) || 'xw_appointment' === ( $field['type'] ?? '' ) ) { $field['value'] = $value; } } unset( $field );
    return $fields;
}
function xw_appt_details_update( $id, $input ) {
    if ( ! xw_appt_can_manage() || ! xw_appt_ready() ) { return new WP_Error( 'forbidden', xw_t( 'No autorizado.', 'Not authorized.' ), 403 ); }
    global $wpdb; $owner = xw_appt_lock(); if ( ! $owner ) { return new WP_Error( 'busy', xw_t( 'La agenda está ocupada.', 'The calendar is busy.' ), 409 ); }
    try {
        $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . xw_appt_table() . " WHERE id=%d AND status<>'holding'", $id ), ARRAY_A );
        if ( ! $row ) { return new WP_Error( 'missing', xw_t( 'No se encontró la cita.', 'Appointment not found.' ), 404 ); }
        if ( ! is_string( $input['revision'] ?? null ) || ! hash_equals( xw_appt_record_revision( $row ), $input['revision'] ) ) { return new WP_Error( 'changed', xw_t( 'La cita cambió. Recarga antes de guardar.', 'The appointment changed. Reload before saving.' ), 409 ); }
        $fields = xw_appt_record_fields( $row ); $values = $input['fields'] ?? null;
        if ( ! is_array( $values ) || count( $values ) > 53 ) { return new WP_Error( 'fields', xw_t( 'Datos del formulario no válidos.', 'Invalid form data.' ), 400 ); }
        $expected = array();
        foreach ( $fields as $index => &$field ) {
            if ( 'appointment' === ( $field['role'] ?? '' ) ) { continue; }
            $expected[] = (string) $index;
            if ( ! array_key_exists( $index, $values ) || ! is_string( $values[ $index ] ) || mb_strlen( $values[ $index ] ) > 2000 ) { return new WP_Error( 'field', xw_t( 'Revisa los campos; cada valor admite hasta 2000 caracteres.', 'Check the fields; each value allows up to 2000 characters.' ), 400 ); }
            $field['value'] = sanitize_textarea_field( $values[ $index ] );
        } unset( $field );
        if ( array_diff( array_map( 'strval', array_keys( $values ) ), $expected ) ) { return new WP_Error( 'field', xw_t( 'Hay campos desconocidos. Recarga la página.', 'Unknown fields. Reload the page.' ), 400 ); }
        $name_index = is_scalar( $input['name_field'] ?? null ) && ctype_digit( (string) $input['name_field'] ) ? (int) $input['name_field'] : -1;
        if ( ! isset( $fields[ $name_index ] ) || 'appointment' === ( $fields[ $name_index ]['role'] ?? '' ) || in_array( $fields[ $name_index ]['contact_role'] ?? '', array( 'email', 'phone' ), true ) ) { return new WP_Error( 'name', xw_t( 'Elige el campo que contiene el nombre.', 'Choose the field containing the name.' ), 400 ); }
        foreach ( $fields as &$field ) { if ( 'customer_name' === ( $field['contact_role'] ?? '' ) ) { unset( $field['contact_role'] ); } } unset( $field );
        $fields[ $name_index ]['contact_role'] = 'customer_name';
        $data = array();
        foreach ( $fields as &$field ) {
            $role = $field['contact_role'] ?? '';
            if ( in_array( $role, array( 'customer_name', 'email', 'phone' ), true ) ) { $data[ $role ] = mb_substr( sanitize_text_field( $field['value'] ), 0, 'phone' === $role ? 100 : 200 ); $field['value'] = $data[ $role ]; }
        } unset( $field );
        $changed = $input['status_changed_at'] ?? '';
        if ( ! is_string( $changed ) ) { return new WP_Error( 'date', xw_t( 'Fecha del estado no válida.', 'Invalid status date.' ), 400 ); }
        if ( '' === $changed ) { $data['status_changed_at'] = null; }
        else {
            $date = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $changed, wp_timezone() );
            if ( ! $date || $date->format( 'Y-m-d\TH:i' ) !== $changed || $date->getTimestamp() > time() + 60 ) { return new WP_Error( 'date', xw_t( 'La fecha del estado debe ser válida y no puede ser futura.', 'The status date must be valid and cannot be in the future.' ), 400 ); }
            $data['status_changed_at'] = $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
        }
        $data['fields'] = wp_json_encode( $fields );
        $saved = $wpdb->update( xw_appt_table(), $data, array( 'id' => $id, 'fields' => $row['fields'], 'status' => $row['status'], 'starts_at' => $row['starts_at'], 'status_changed_at' => $row['status_changed_at'] ) );
        if ( false === $saved || ( 0 === $saved && array_diff_assoc( $data, array_intersect_key( $row, $data ) ) ) ) { return new WP_Error( 'changed', xw_t( 'La cita cambió en otra pestaña. Recarga antes de guardar.', 'The appointment changed in another tab. Reload before saving.' ), 409 ); }
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . xw_appt_table() . ' WHERE id=%d', $id ), ARRAY_A );
    } finally { xw_appt_unlock( $owner ); }
}

function xw_appt_rebook_cutoff( $day = '' ) {
    $date = '' === $day ? current_datetime()->modify( '-' . (int) xw_appt_settings()['rebook_days'] . ' days' )->setTime( 23, 59, 59 ) : xw_appt_day( $day );
    if ( ! $date || $date->format( 'Y-m-d' ) > current_datetime()->format( 'Y-m-d' ) ) { return false; }
    return $date->setTime( 23, 59, 59 )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
}
function xw_appt_can_rebook_row( $row, $cutoff = null ) {
    $cutoff = $cutoff ?? xw_appt_rebook_cutoff();
    return $cutoff && empty( $row['rebooked_to'] ) && ! empty( $row['status_changed_at'] ) && $row['status_changed_at'] <= $cutoff &&
        in_array( $row['status'], array( 'confirmed', 'completed', 'cancelled' ), true ) && ( 'cancelled' === $row['status'] || $row['ends_at'] <= gmdate( 'Y-m-d H:i:s' ) );
}
function xw_appt_rebook( $id, $value, $before = '' ) {
    if ( ! xw_appt_can_manage() || ! xw_appt_ready() ) { return new WP_Error( 'forbidden', xw_t( 'No autorizado.', 'Not authorized.' ), 403 ); }
    global $wpdb; $owner = xw_appt_lock(); if ( ! $owner ) { return new WP_Error( 'busy', xw_t( 'La agenda está ocupada.', 'The calendar is busy.' ), 409 ); }
    $transaction = false;
    try {
        if ( false === $wpdb->query( 'START TRANSACTION' ) ) { return new WP_Error( 'transaction', xw_t( 'No se pudo iniciar el guardado.', 'Could not start saving.' ), 500 ); } $transaction = true;
        $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . xw_appt_table() . ' WHERE id=%d', $id ), ARRAY_A );
        if ( ! $row || ! xw_appt_can_rebook_row( $row, xw_appt_rebook_cutoff( $before ) ) ) { return new WP_Error( 'state', xw_t( 'Esta cita no está lista para reagendar o ya se reagendó.', 'This appointment is not ready to rebook or has already been rebooked.' ), 409 ); }
        $slots = is_string( $value ) && preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $value ) ? xw_appt_available( substr( $value, 0, 10 ) ) : array();
        if ( ! isset( $slots[ $value ] ) ) { return new WP_Error( 'unavailable', xw_t( 'Elige una fecha y hora disponibles.', 'Choose an available date and time.' ), 409 ); }
        $copy = array_intersect_key( $row, array_flip( array( 'customer_name', 'email', 'phone', 'form_name', 'form_id' ) ) );
        $copy = array_merge( $copy, array( 'fields' => wp_json_encode( xw_appt_sync_date_field( xw_appt_record_fields( $row ), $value ) ), 'status' => 'pending', 'starts_at' => $slots[ $value ]['start'], 'ends_at' => $slots[ $value ]['end'], 'timezone' => wp_timezone_string(), 'created_at' => gmdate( 'Y-m-d H:i:s' ), 'status_changed_at' => gmdate( 'Y-m-d H:i:s' ) ) );
        if ( false === $wpdb->insert( xw_appt_table(), $copy ) ) { return new WP_Error( 'save', xw_t( 'No se pudo crear la nueva cita.', 'Could not create the new appointment.' ), 500 ); }
        $new_id = (int) $wpdb->insert_id;
        if ( 1 !== $wpdb->update( xw_appt_table(), array( 'rebooked_to' => $new_id ), array( 'id' => $id, 'rebooked_to' => null, 'status' => $row['status'], 'starts_at' => $row['starts_at'], 'fields' => $row['fields'], 'status_changed_at' => $row['status_changed_at'] ) ) ) { return new WP_Error( 'changed', xw_t( 'La cita cambió. Recarga antes de continuar.', 'The appointment changed. Reload before continuing.' ), 409 ); }
        if ( false === $wpdb->query( 'COMMIT' ) ) { return new WP_Error( 'save', xw_t( 'No se pudo confirmar el guardado.', 'Could not commit the changes.' ), 500 ); } $transaction = false;
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . xw_appt_table() . ' WHERE id=%d', $new_id ), ARRAY_A );
    } finally { if ( $transaction ) { $wpdb->query( 'ROLLBACK' ); } xw_appt_unlock( $owner ); }
}
