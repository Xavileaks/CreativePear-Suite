<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_filter( 'wp_privacy_personal_data_exporters', static function( $exporters ) {
    $exporters['xw-appointments'] = array( 'exporter_friendly_name' => xw_t( 'Citas', 'Appointments' ), 'callback' => 'xw_appt_privacy_export' ); return $exporters;
} );
add_filter( 'wp_privacy_personal_data_erasers', static function( $erasers ) {
    $erasers['xw-appointments'] = array( 'eraser_friendly_name' => xw_t( 'Citas', 'Appointments' ), 'callback' => 'xw_appt_privacy_erase' ); return $erasers;
} );
function xw_appt_privacy_export( $email, $page = 1 ) {
    $data = array();
    if ( ! xw_appt_ready() || ! is_email( $email ) ) { return array( 'data' => $data, 'done' => true ); }
    global $wpdb; $table = xw_appt_table();
    $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE email = %s ORDER BY id LIMIT 100 OFFSET %d", $email, ( max( 1, (int) $page ) - 1 ) * 100 ), ARRAY_A );
    foreach ( $rows ?: array() as $row ) {
        $fields = array();
        foreach ( array( 'customer_name', 'email', 'phone', 'starts_at', 'ends_at', 'timezone', 'status', 'form_name' ) as $key ) { $fields[] = array( 'name' => $key, 'value' => $row[ $key ] ); }
        foreach ( json_decode( $row['fields'], true ) ?: array() as $field ) { $fields[] = array( 'name' => $field['label'], 'value' => $field['value'] ); }
        $data[] = array( 'group_id' => 'xw-appointments', 'group_label' => xw_t( 'Citas', 'Appointments' ), 'item_id' => 'xw-appointment-' . $row['id'], 'data' => $fields );
    }
    return array( 'data' => $data, 'done' => count( $rows ?: array() ) < 100 );
}
function xw_appt_privacy_erase( $email, $page = 1 ) {
    $result = array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
    if ( ! xw_appt_ready() || ! is_email( $email ) ) { return $result; }
    global $wpdb; $table = xw_appt_table();
    // Keep anonymous time reservations: privacy erasure must not silently cancel appointments.
    $count = $wpdb->update( $table, array( 'customer_name' => '', 'email' => '', 'phone' => '', 'fields' => '[]', 'form_name' => '', 'form_id' => '' ), array( 'email' => $email ) );
    $result['items_removed'] = $count > 0;
    if ( false === $count ) { $result['items_retained'] = true; $result['messages'][] = xw_t( 'No se pudieron borrar los datos de citas. Inténtalo de nuevo.', 'Appointment data could not be erased. Please try again.' ); }
    return $result;
}
