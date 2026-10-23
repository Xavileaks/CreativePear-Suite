<?php
/** Prepare / restore an isolated, disposable WP for actual Elementor integration QA. */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) || empty( $argv[2] ) ) { exit( "Usage: php tests/appointments-setup.php <local-wp> prepare|restore\n" ); }
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { exit( "Local environment required\n" ); }
if ( 'restore' === $argv[2] ) {
    $backup = get_option( 'xw_appt_qa_backup' );
    if ( ! $backup ) { exit( "No QA backup\n" ); }
    update_option( 'active_plugins', $backup['plugins'] ); update_option( 'xw_settings', $backup['settings'] ); update_option( 'timezone_string', $backup['timezone'] );
    if ( null === $backup['schedule'] ) { delete_option( 'xw_appointment_settings' ); } else { update_option( 'xw_appointment_settings', $backup['schedule'] ); }
    $post_id = (int) get_option( 'xw_appt_qa_post' ); if ( $post_id && 'xw-appointment-qa' === get_post_field( 'post_name', $post_id ) ) { wp_delete_post( $post_id, true ); }
    global $wpdb;
    if ( function_exists( 'xw_appt_ready' ) && xw_appt_ready() ) { $wpdb->delete( xw_appt_table(), array( 'form_id' => 'a170001' ) ); }
    delete_option( 'xw_appt_qa_post' ); delete_option( 'xw_appt_qa_backup' ); echo "Local QA state restored\n"; exit;
}
if ( ! get_option( 'xw_appt_qa_backup' ) ) { add_option( 'xw_appt_qa_backup', array( 'plugins' => get_option( 'active_plugins' ), 'settings' => get_option( 'xw_settings' ), 'schedule' => get_option( 'xw_appointment_settings', null ), 'timezone' => get_option( 'timezone_string' ) ), '', false ); }
update_option( 'active_plugins', array( 'elementor/elementor.php', 'proelements/pro-elements.php', 'xleon-suite/xleon-suite.php' ) );
$settings = xw_get_settings(); $settings['features']['elementor_appointments'] = 1; update_option( 'xw_settings', $settings );
update_option( 'timezone_string', 'Europe/Madrid' );
$schedule = xw_appt_defaults(); $schedule['weekly'] = array( '', '09:00-13:00, 15:00-18:00', '09:00-13:00', '09:00-13:00', '09:00-13:00', '09:00-13:00', '' ); $schedule['notice'] = 120;
$schedule['closed'] = array( current_datetime()->modify( 'next tuesday' )->format( 'Y-m-d' ) ); update_option( 'xw_appointment_settings', $schedule );
$post_id = (int) get_option( 'xw_appt_qa_post' );
if ( ! $post_id ) { $post_id = wp_insert_post( array( 'post_title' => 'Appointment QA — local only', 'post_name' => 'xw-appointment-qa', 'post_status' => 'publish', 'post_type' => 'page' ) ); update_option( 'xw_appt_qa_post', $post_id ); }
$fields = array(
    array( '_id' => 'aname', 'custom_id' => 'name', 'field_type' => 'text', 'field_label' => 'Nombre y apellido', 'placeholder' => 'Tu nombre completo', 'required' => 'true', 'width' => '50' ),
    array( '_id' => 'aemail', 'custom_id' => 'email', 'field_type' => 'email', 'field_label' => 'Correo electrónico', 'placeholder' => 'nombre@example.com', 'required' => 'true', 'width' => '50' ),
    array( '_id' => 'aphone', 'custom_id' => 'phone', 'field_type' => 'tel', 'field_label' => 'Teléfono', 'placeholder' => 'Tu número de contacto', 'width' => '50' ),
    array( '_id' => 'aappt', 'custom_id' => 'appointment', 'field_type' => 'xw_appointment', 'field_label' => 'Fecha de tu cita', 'required' => 'true', 'width' => '50' ),
);
$widget = array( 'id' => 'a170001', 'elType' => 'widget', 'widgetType' => 'form', 'settings' => array( 'form_name' => 'Appointment local QA', 'form_fields' => $fields, 'submit_actions' => array(), 'button_text' => 'Solicitar cita', 'show_labels' => 'true', 'form_id' => 'appointment-local-qa' ), 'elements' => array() );
update_post_meta( $post_id, '_elementor_edit_mode', 'builder' ); update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( array( $widget ) ) ) );
echo "Prepared local synthetic form post $post_id; no mail actions enabled\n";
