<?php
/** CLI integration tests; isolated local WordPress, restores options and deletes only its own synthetic bookings. */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { exit( "Supply isolated WordPress directory.\n" ); }
define( 'DOING_AJAX', true );
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { exit( "Local environment required.\n" ); }
function appt_check( $ok, $label ) { if ( ! $ok ) { throw new RuntimeException( $label ); } echo "PASS: $label\n"; }
class ApptRecord {
    public $fields; public $settings;
    public function __construct( $value ) { $this->fields = array( 'name' => array( 'id' => 'name', 'type' => 'text', 'title' => 'Name', 'value' => 'Appointment QA' ), 'email' => array( 'id' => 'email', 'type' => 'email', 'title' => 'Email', 'value' => 'appointment-qa@example.invalid' ), 'password' => array( 'id' => 'password', 'type' => 'password', 'title' => 'Secret', 'value' => 'DO-NOT-STORE' ), 'date' => array( 'id' => 'date', 'type' => 'xw_appointment', 'value' => $value, 'title' => 'Cita' ) ); $this->settings = array( 'id' => 'appointment-qa', 'form_name' => 'Appointment QA', 'form_fields' => array( array( 'custom_id' => 'date' ) ) ); }
    public function get( $key ) { return $this->$key; }
    public function get_form_settings( $key ) { return $this->settings[ $key ] ?? null; }
}
class ApptHandler {
    public $errors = array(); public $messages = array( 'error' => array(), 'admin_error' => array() ); public $is_success = true; public $data = array();
    public function add_error( $id, $message ) { $this->errors[ $id ] = $message; $this->is_success = false; }
    public function add_error_message( $message ) { $this->messages['error'][] = $message; $this->is_success = false; }
    public function add_response_data( $key, $value ) { $this->data[ $key ] = $value; }
}
add_filter( 'wp_die_ajax_handler', static function() { return static function() { throw new RuntimeException( 'appt-json-end' ); }; } );
function appt_request( $callback, $post = array(), $get = array() ) {
    $_SERVER['REQUEST_METHOD'] = 'POST'; $_POST = $post; $_GET = $get; $_REQUEST = array_merge( $get, $post );
    ob_start(); try { $callback(); } catch ( RuntimeException $e ) { if ( 'appt-json-end' !== $e->getMessage() ) { ob_end_clean(); throw $e; } }
    return json_decode( ob_get_clean(), true );
}
$original = get_option( 'xw_settings' ); $schedule_original = get_option( 'xw_appointment_settings', null ); $timezone_original = get_option( 'timezone_string' ); $ids = array();
try {
    appt_check( 0 === xw_get_default_settings()['features']['elementor_appointments'], 'utility defaults off' );
    $settings = xw_get_settings(); $settings['features']['elementor_appointments'] = 1; update_option( 'xw_settings', $settings ); xw_appt_install();
    appt_check( xw_appt_ready(), 'database and unique mutex table installed' );
    update_option( 'timezone_string', 'Europe/Madrid' );
    $schedule = xw_appt_defaults(); $schedule['weekly'] = array_fill( 0, 7, '09:00-12:00, 15:00-17:00' ); $schedule['notice'] = 0;
    update_option( 'xw_appointment_settings', $schedule );
    $tomorrow = current_datetime()->modify( '+1 day' )->format( 'Y-m-d' );
    $day = xw_appt_candidates( $tomorrow );
    appt_check( 10 === count( $day ), '30-minute appointments honor split shifts and breaks' );
    appt_check( ! xw_appt_candidates( current_datetime()->modify( '-1 day' )->format( 'Y-m-d' ) ) && ! xw_appt_candidates( current_datetime()->modify( '+181 days' )->format( 'Y-m-d' ) ), 'past dates and horizon are rejected server-side' );
    $closed = $schedule; $closed['closed'] = array( $tomorrow ); appt_check( ! xw_appt_candidates( $tomorrow, $closed ), 'exception day disabled' );
    $closed['closed'] = array(); $closed['weekly'] = array_fill( 0, 7, '' ); appt_check( ! xw_appt_candidates( $tomorrow, $closed ), 'unconfigured and non-working days are closed' );
    appt_check( false === xw_appt_day( '2026-02-31' ) && false === xw_appt_intervals( '12:00-09:00' ) && false === xw_appt_intervals( '09:00-12:00, 11:00-13:00' ), 'strict invalid date, backwards and overlapping hours rejection' );
    $dst = $schedule; $dst['weekly'] = array_fill( 0, 7, '01:00-04:00' ); $dst['horizon'] = 180;
    $spring = xw_appt_candidates( '2027-03-28', $dst, strtotime( '2027-03-27 00:00:00 Europe/Madrid' ) );
    appt_check( ! isset( $spring['2027-03-28T02:00'] ) && ! isset( $spring['2027-03-28T02:30'] ) && isset( $spring['2027-03-28T03:00'] ), 'DST nonexistent local times never become bookable' );
    $notice = $schedule; $notice['notice'] = 120; $notice_slots = xw_appt_candidates( $tomorrow, $notice, strtotime( $tomorrow . ' 08:00:00 Europe/Madrid' ) );
    appt_check( ! isset( $notice_slots[ $tomorrow . 'T09:00' ] ) && isset( $notice_slots[ $tomorrow . 'T10:00' ] ), 'minimum notice enforced independently of browser time' );
    $raw = $schedule; $raw['closed'] = "2026-12-25\n2027-01-01"; appt_check( ! is_wp_error( xw_appt_sanitize_schedule( $raw ) ), 'valid settings accepted' ); $raw['closed'] = '2026-02-31'; appt_check( is_wp_error( xw_appt_sanitize_schedule( $raw ) ), 'invalid exceptions cannot silently erase existing configuration' );
    $owner = xw_appt_lock(); appt_check( $owner && ! xw_appt_lock(), 'two simultaneous booking writers cannot acquire the same mutex' ); xw_appt_unlock( 'not-owner' ); appt_check( ! xw_appt_lock(), 'another request cannot release the lock' ); xw_appt_unlock( $owner );
    $value = array_key_first( xw_appt_available( $tomorrow ) ); $record = new ApptRecord( $value ); $handler = new ApptHandler();
    xw_appt_process( $record, $handler ); appt_check( $handler->is_success && isset( $GLOBALS['xw_appt_holds'][ spl_object_id( $record ) ] ), 'valid form reserves a temporary hold before mail / submit actions' ); $ids[] = $GLOBALS['xw_appt_holds'][ spl_object_id( $record ) ]['id'];
    $second = new ApptRecord( $value ); $second_handler = new ApptHandler(); xw_appt_process( $second, $second_handler ); appt_check( ! $second_handler->is_success, 'same slot cannot be booked twice' );
    xw_appt_complete( $record, $handler );
    global $wpdb; $table = xw_appt_table(); $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $ids[0] ), ARRAY_A );
    appt_check( 'pending' === $row['status'] && null === $row['hold_expires'] && ! str_contains( $row['fields'], 'DO-NOT-STORE' ), 'successful form becomes pending, sensitive field types excluded' );
    appt_check( $row['email'] === 'appointment-qa@example.invalid' && strtotime( $row['starts_at'] . ' UTC' ) === strtotime( $value . ':00 Europe/Madrid' ), 'contact auto-mapping and UTC storage are correct' );
    $overlap = $schedule; $overlap['duration'] = 45; update_option( 'xw_appointment_settings', $overlap ); appt_check( ! isset( xw_appt_available( $tomorrow )[ $value ] ), 'existing appointments still prevent overlaps after duration changes' ); update_option( 'xw_appointment_settings', $schedule );
    $fail_value = array_key_first( xw_appt_available( $tomorrow ) ); $failed = new ApptRecord( $fail_value ); $failed_handler = new ApptHandler(); xw_appt_process( $failed, $failed_handler ); $ids[] = $GLOBALS['xw_appt_holds'][ spl_object_id( $failed ) ]['id']; $failed_handler->add_error_message( 'Synthetic delivery failure' ); xw_appt_complete( $failed, $failed_handler ); appt_check( isset( xw_appt_available( $tomorrow )[ $fail_value ] ), 'failed submit actions release their temporary booking' );
    $bad = new ApptRecord( $tomorrow . 'T13:00' ); $bad_handler = new ApptHandler(); xw_appt_process( $bad, $bad_handler ); appt_check( ! $bad_handler->is_success, 'manipulated lunch-break time rejected' );
    $invalid = new ApptRecord( $fail_value ); $invalid_handler = new ApptHandler(); $invalid_handler->add_error( 'email', 'invalid' ); xw_appt_process( $invalid, $invalid_handler ); appt_check( ! isset( $GLOBALS['xw_appt_holds'][ spl_object_id( $invalid ) ] ), 'invalid other fields never create a booking' );
    $ordinary = new ApptRecord( '' ); unset( $ordinary->fields['date'] ); $ordinary_handler = new ApptHandler(); xw_appt_process( $ordinary, $ordinary_handler ); appt_check( $ordinary_handler->is_success && ! isset( $GLOBALS['xw_appt_holds'][ spl_object_id( $ordinary ) ] ), 'ordinary Elementor forms are not connected automatically' );
    $mapped = new ApptRecord( $value ); $mapped->fields['preferred'] = array( 'type' => 'text', 'title' => 'Preferred', 'value' => 'Mapped patient' );
    appt_check( xw_appt_customer( $mapped, array( 'xw_appt_name' => 'preferred' ) )['customer_name'] === 'Mapped patient', 'custom form field IDs override automatic contact detection' );
    $month = substr( $tomorrow, 0, 7 ); $response = appt_request( 'xw_appt_availability', array(), array( 'month' => $month ) ); appt_check( $response['success'] && ! str_contains( wp_json_encode( $response ), 'appointment-qa@' ) && ! str_contains( wp_json_encode( $response ), 'customer_name' ), 'public availability exposes only days, times and timezone' );
    $admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) ); wp_set_current_user( (int) $admins[0] );
    $response = appt_request( 'xw_appt_admin_save', array( 'operation' => 'schedule', 'nonce' => 'invalid', 'schedule' => $schedule ) ); appt_check( ! $response['success'], 'admin writes reject invalid nonce' );
    $nonce = wp_create_nonce( 'xw_appointment_admin' );
    $response = appt_request( 'xw_appt_admin_save', array( 'operation' => 'status', 'booking' => $ids[0], 'status' => 'cancelled', 'nonce' => $nonce ) ); appt_check( $response['success'] && isset( xw_appt_available( $tomorrow )[ $value ] ), 'admin cancellation releases the occupied slot' );
    $response = appt_request( 'xw_appt_admin_save', array( 'operation' => 'status', 'booking' => $ids[0], 'status' => 'confirmed', 'nonce' => $nonce ) ); appt_check( ! $response['success'], 'cancelled booking cannot bypass availability by reactivation' );
    wp_set_current_user( 0 ); $response = appt_request( 'xw_appt_admin_save', array( 'operation' => 'status', 'booking' => $ids[0], 'status' => 'pending', 'nonce' => $nonce ) ); appt_check( ! $response['success'], 'unauthenticated users cannot read or manage bookings' );
    $export = xw_appt_privacy_export( 'appointment-qa@example.invalid' ); appt_check( count( $export['data'] ) > 0, 'WordPress personal data exporter includes appointments' );
    $erasure = xw_appt_privacy_erase( 'appointment-qa@example.invalid' ); appt_check( $erasure['items_removed'] && ! xw_appt_privacy_export( 'appointment-qa@example.invalid' )['data'], 'WordPress erasure anonymizes customer data' );
    $settings['features']['elementor_appointments'] = 0; update_option( 'xw_settings', $settings ); $response = appt_request( 'xw_appt_availability', array(), array( 'month' => $month ) ); appt_check( ! $response['success'] && ! xw_appt_available( $tomorrow ), 'disabled utility fails closed, without altering ordinary forms' );
} finally {
    foreach ( $ids as $id ) { $wpdb->delete( xw_appt_table(), array( 'id' => $id ) ); }
    update_option( 'xw_settings', $original ); update_option( 'timezone_string', $timezone_original );
    if ( null === $schedule_original ) { delete_option( 'xw_appointment_settings' ); } else { update_option( 'xw_appointment_settings', $schedule_original ); }
}
