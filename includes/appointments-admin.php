<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_menu', static function() {
    if ( ! xw_feature_enabled( 'elementor_appointments' ) ) { return; }
    add_menu_page( xw_t( 'Citas', 'Appointments' ), xw_t( 'Citas', 'Appointments' ), 'manage_options', 'xw-appointments', 'xw_appt_admin_page', 'dashicons-calendar-alt', 25.1 );
} );
function xw_appt_admin_assets( $hook ) {
    if ( 'toplevel_page_xw-appointments' !== $hook || ! current_user_can( 'manage_options' ) ) { return; }
    $base = plugin_dir_path( XW_FUNCTIONS_FILE ); $url = plugin_dir_url( XW_FUNCTIONS_FILE );
    xw_appt_assets();
    $calendar = wp_script_is( 'flatpickr', 'registered' );
    if ( $calendar ) { wp_enqueue_script( 'flatpickr' ); wp_enqueue_style( 'flatpickr' ); }
    wp_enqueue_style( 'xw-appointments' ); wp_enqueue_script( 'xw-appointments' );
    wp_enqueue_style( 'xw-appointments-admin', $url . 'assets/css/appointments-admin.css', array(), filemtime( $base . 'assets/css/appointments-admin.css' ) );
    wp_enqueue_script( 'xw-appointments-admin', $url . 'assets/js/appointments-admin.js', $calendar ? array( 'flatpickr' ) : array(), filemtime( $base . 'assets/js/appointments-admin.js' ), true );
    wp_localize_script( 'xw-appointments-admin', 'xwAppointmentAdmin', array( 'url' => admin_url( 'admin-ajax.php' ), 'saving' => xw_t( 'Guardando…', 'Saving…' ), 'saved' => xw_t( 'Cambios guardados.', 'Changes saved.' ), 'error' => xw_t( 'No se pudo guardar. Revisa la conexión e inténtalo de nuevo.', 'Could not save. Check your connection and try again.' ), 'dirty' => xw_t( 'Hay cambios sin guardar.', 'You have unsaved changes.' ), 'cancel' => xw_t( '¿Cancelar esta cita y liberar su horario?', 'Cancel this appointment and release its time slot?' ) ) );
    wp_add_inline_script( 'xw-appointments-admin', 'Object.assign(xwAppointmentAdmin,' . wp_json_encode( array(
        'today' => current_datetime()->format( 'Y-m-d' ), 'locale' => xw_interface_language(),
        'remove' => xw_t( 'Eliminar turno', 'Remove shift' ), 'shift' => xw_t( 'Turno', 'Shift' ),
        'start' => xw_t( 'Inicio', 'Start' ), 'end' => xw_t( 'Fin', 'End' ),
        'daysOff' => xw_t( 'Días bloqueados', 'Blocked days' ), 'none' => xw_t( 'Ningún día bloqueado.', 'No blocked days.' ),
        'calendarMissing' => xw_t( 'Activa Elementor para mostrar el calendario. Tus fechas guardadas se conservan.', 'Activate Elementor to display the calendar. Your saved dates are preserved.' ),
        'statuses' => xw_appt_statuses(), 'rescheduled' => xw_t( 'Fecha actualizada. No se ha enviado ningún aviso.', 'Date updated. No notification was sent.' ),
    ) ) . ');', 'before' );
}
add_action( 'admin_enqueue_scripts', 'xw_appt_admin_assets' );

function xw_appt_statuses() {
    return array( 'pending' => xw_t( 'Pendiente', 'Pending' ), 'confirmed' => xw_t( 'Confirmada', 'Confirmed' ), 'completed' => xw_t( 'Completada', 'Completed' ), 'cancelled' => xw_t( 'Cancelada', 'Cancelled' ) );
}

function xw_appt_admin_save() {
    if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! current_user_can( 'manage_options' ) ) { wp_send_json_error( array( 'message' => xw_t( 'No autorizado.', 'Not authorized.' ) ), 403 ); }
    if ( ! check_ajax_referer( 'xw_appointment_admin', 'nonce', false ) ) { wp_send_json_error( array( 'message' => xw_t( 'La sesión ha caducado. Recarga esta página.', 'Your session expired. Reload this page.' ) ), 403 ); }
    if ( ! xw_feature_enabled( 'elementor_appointments' ) || ! xw_appt_ready() ) { wp_send_json_error( array( 'message' => xw_t( 'Activa la utilidad Citas.', 'Enable the Appointments utility.' ) ), 400 ); }
    $op = isset( $_POST['operation'] ) && is_string( $_POST['operation'] ) ? $_POST['operation'] : '';
    if ( 'schedule' === $op ) {
        $input = isset( $_POST['schedule'] ) && is_array( $_POST['schedule'] ) ? wp_unslash( $_POST['schedule'] ) : null;
        $settings = xw_appt_sanitize_schedule( $input );
        if ( is_wp_error( $settings ) ) { wp_send_json_error( array( 'message' => $settings->get_error_message() ), 400 ); }
        $owner = xw_appt_lock();
        if ( ! $owner ) { wp_send_json_error( array( 'message' => xw_t( 'La agenda está ocupada. Inténtalo de nuevo.', 'The calendar is busy. Please try again.' ) ), 409 ); }
        try { update_option( 'xw_appointment_settings', $settings, false ); $saved = get_option( 'xw_appointment_settings' ) === $settings; }
        finally { xw_appt_unlock( $owner ); }
        if ( ! $saved ) { wp_send_json_error( array( 'message' => xw_t( 'No se pudo guardar la configuración.', 'Could not save the settings.' ) ), 500 ); }
        wp_send_json_success();
    }
    $id = isset( $_POST['booking'] ) && is_scalar( $_POST['booking'] ) ? absint( $_POST['booking'] ) : 0;
    if ( 'reschedule' === $op ) {
        $value = isset( $_POST['appointment'] ) && is_string( $_POST['appointment'] ) ? wp_unslash( $_POST['appointment'] ) : '';
        $result = xw_appt_reschedule( $id, $value );
        if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), (int) $result->get_error_data() ?: 400 ); }
        wp_send_json_success( $result );
    }
    if ( 'status' !== $op ) { wp_send_json_error( array( 'message' => xw_t( 'Acción no válida.', 'Invalid action.' ) ), 400 ); }
    $status = isset( $_POST['status'] ) && is_string( $_POST['status'] ) ? $_POST['status'] : '';
    if ( ! isset( xw_appt_statuses()[ $status ] ) ) { wp_send_json_error( array( 'message' => xw_t( 'Estado no válido.', 'Invalid status.' ) ), 400 ); }
    global $wpdb;
    $table = xw_appt_table();
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d AND status <> 'holding'", $id ), ARRAY_A );
    if ( ! $row ) { wp_send_json_error( array( 'message' => xw_t( 'No se encontró la cita.', 'Appointment not found.' ) ), 404 ); }
    if ( 'cancelled' === $row['status'] && 'cancelled' !== $status ) { wp_send_json_error( array( 'message' => xw_t( 'Una cita cancelada no se reactiva: realiza una nueva reserva para comprobar disponibilidad.', 'A cancelled appointment cannot be reactivated: create a new booking to check availability.' ) ), 409 ); }
    if ( 'completed' === $status && $row['ends_at'] > gmdate( 'Y-m-d H:i:s' ) ) { wp_send_json_error( array( 'message' => xw_t( 'La cita todavía no ha terminado.', 'The appointment has not ended yet.' ) ), 400 ); }
    // Compare-and-swap prevents a stale admin tab from overwriting a cancellation.
    $saved = $wpdb->update( $table, array( 'status' => $status ), array( 'id' => $id, 'status' => $row['status'] ) );
    if ( false === $saved || ( 0 === $saved && $status !== $row['status'] ) ) { wp_send_json_error( array( 'message' => xw_t( 'La cita cambió en otra pestaña. Recarga antes de continuar.', 'This appointment changed in another tab. Reload before continuing.' ) ), 409 ); }
    wp_send_json_success( array( 'status' => $status ) );
}
add_action( 'wp_ajax_xw_appt_admin_save', 'xw_appt_admin_save' );

/** Only this authenticated, nonce-protected route can ignore a booking's own occupancy. */
function xw_appt_admin_availability() {
    if ( 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'xw_appointment_admin', 'nonce', false ) ) { wp_send_json_error( array( 'message' => xw_t( 'No autorizado o sesión caducada.', 'Not authorized or session expired.' ) ), 403 ); }
    $id = isset( $_GET['booking'] ) && is_scalar( $_GET['booking'] ) ? absint( $_GET['booking'] ) : 0;
    if ( ! xw_feature_enabled( 'elementor_appointments' ) || ! xw_appt_ready() ) { wp_send_json_error( array( 'message' => xw_t( 'Activa la utilidad Citas.', 'Enable the Appointments utility.' ) ), 400 ); }
    global $wpdb;
    $status = $wpdb->get_var( $wpdb->prepare( 'SELECT status FROM ' . xw_appt_table() . ' WHERE id = %d', $id ) );
    if ( ! in_array( $status, array( 'pending', 'confirmed' ), true ) ) { wp_send_json_error( array( 'message' => xw_t( 'Solo puedes cambiar fechas de citas pendientes o confirmadas.', 'Only pending or confirmed appointments can be rescheduled.' ) ), 409 ); }
    xw_appt_availability( $id );
}
add_action( 'wp_ajax_xw_appt_admin_availability', 'xw_appt_admin_availability' );

function xw_appt_reschedule( $id, $value ) {
    if ( ! current_user_can( 'manage_options' ) || ! xw_feature_enabled( 'elementor_appointments' ) || ! xw_appt_ready() ) { return new WP_Error( 'forbidden', xw_t( 'No autorizado.', 'Not authorized.' ), 403 ); }
    $owner = xw_appt_lock();
    if ( ! $owner ) { return new WP_Error( 'busy', xw_t( 'La agenda está ocupada. Inténtalo de nuevo.', 'The calendar is busy. Please try again.' ), 409 ); }
    global $wpdb;
    try {
        $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . xw_appt_table() . ' WHERE id = %d', $id ), ARRAY_A );
        if ( ! $row || ! in_array( $row['status'], array( 'pending', 'confirmed' ), true ) ) { return new WP_Error( 'state', xw_t( 'Solo puedes cambiar fechas de citas pendientes o confirmadas.', 'Only pending or confirmed appointments can be rescheduled.' ), 409 ); }
        $slots = is_string( $value ) && preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $value ) ? xw_appt_available( substr( $value, 0, 10 ), null, $id ) : array();
        if ( ! isset( $slots[ $value ] ) ) { return new WP_Error( 'unavailable', xw_t( 'Esa fecha u hora no está disponible. Elige otra.', 'That date or time is unavailable. Choose another.' ), 409 ); }
        $saved = $wpdb->update( xw_appt_table(), array( 'starts_at' => $slots[ $value ]['start'], 'ends_at' => $slots[ $value ]['end'], 'timezone' => wp_timezone_string() ), array( 'id' => $id, 'status' => $row['status'], 'starts_at' => $row['starts_at'], 'ends_at' => $row['ends_at'] ) );
        if ( false === $saved || ( 0 === $saved && ( $slots[ $value ]['start'] !== $row['starts_at'] || $slots[ $value ]['end'] !== $row['ends_at'] || wp_timezone_string() !== $row['timezone'] ) ) ) { return new WP_Error( 'changed', xw_t( 'La cita cambió en otra pestaña. Recarga antes de continuar.', 'This appointment changed in another tab. Reload before continuing.' ), 409 ); }
        $date = new DateTimeImmutable( $slots[ $value ]['start'], new DateTimeZone( 'UTC' ) ); $date = $date->setTimezone( wp_timezone() );
        return array( 'date' => $date->format( 'd/m/Y H:i' ), 'start' => $slots[ $value ]['start'], 'timezone' => wp_timezone_string(), 'status' => $row['status'] );
    } finally { xw_appt_unlock( $owner ); }
}

/** Quarter-hour choices; preserve an existing valid non-quarter-hour endpoint. */
function xw_appt_shift_select( $day, $index, $edge, $selected = '' ) {
    $options = array();
    for ( $minute = 0; $minute < 1440; $minute += 15 ) { $options[] = sprintf( '%02d:%02d', intdiv( $minute, 60 ), $minute % 60 ); }
    if ( $selected && ! in_array( $selected, $options, true ) ) { $options[] = $selected; sort( $options ); }
    echo '<select data-edge="' . esc_attr( $edge ) . '" name="schedule[shifts][' . esc_attr( $day ) . '][' . esc_attr( $index ) . '][' . esc_attr( $edge ) . ']" aria-label="' . esc_attr( xw_t( 'Turno', 'Shift' ) . ' ' . ( (int) $index + 1 ) . ': ' . ( 'start' === $edge ? xw_t( 'Inicio', 'Start' ) : xw_t( 'Fin', 'End' ) ) ) . '"><option value="">—</option>';
    foreach ( $options as $option ) { echo '<option value="' . esc_attr( $option ) . '" ' . selected( $selected, $option, false ) . '>' . esc_html( $option ) . '</option>'; }
    echo '</select>';
}
function xw_appt_shift_row( $day, $index, $start = '', $end = '' ) {
    echo '<div class="xw-appt-shift">';
    xw_appt_shift_select( $day, $index, 'start', $start );
    echo '<span aria-hidden="true">–</span>';
    xw_appt_shift_select( $day, $index, 'end', $end );
    echo '<button type="button" class="button xw-appt-remove-shift" aria-label="' . esc_attr( xw_t( 'Eliminar turno', 'Remove shift' ) ) . '">×</button></div>';
}

function xw_appt_admin_page() {
    if ( ! current_user_can( 'manage_options' ) || ! xw_feature_enabled( 'elementor_appointments' ) ) { wp_die( esc_html( xw_t( 'No autorizado.', 'Not authorized.' ) ) ); }
    if ( ! xw_appt_ready() ) { echo '<div class="notice notice-error"><p>' . esc_html( xw_t( 'No se pudieron crear las tablas de la agenda. Revisa la base de datos.', 'Could not create the calendar tables. Check your database.' ) ) . '</p></div>'; return; }
    $settings = xw_appt_settings(); $nonce = wp_create_nonce( 'xw_appointment_admin' );
    $week = array( xw_t( 'Domingo', 'Sunday' ), xw_t( 'Lunes', 'Monday' ), xw_t( 'Martes', 'Tuesday' ), xw_t( 'Miércoles', 'Wednesday' ), xw_t( 'Jueves', 'Thursday' ), xw_t( 'Viernes', 'Friday' ), xw_t( 'Sábado', 'Saturday' ) );
    ?>
    <div class="wrap xw-appointments-admin">
        <h1><?php echo esc_html( xw_t( 'Citas', 'Appointments' ) ); ?></h1>
        <p class="xw-appt-intro"><?php echo esc_html( xw_t( 'Una agenda para este sitio. Define la disponibilidad y gestiona las solicitudes de tus formularios.', 'One calendar for this site. Set availability and manage requests from your forms.' ) ); ?></p>
        <?php xw_appt_admin_list( $nonce ); ?>
        <p class="description"><?php echo esc_html( xw_t( 'Las reservas quedan pendientes hasta que las confirmes. No se envían avisos adicionales al cambiar estados. Datos privados: acceso de administrador, exportación y borrado mediante Herramientas → Datos personales de WordPress. Configura consentimiento y antispam en tu formulario; no recopiles datos clínicos innecesarios.', 'Bookings remain pending until you confirm them. Status changes do not send additional notifications. Private data: administrator access, export and erasure through WordPress Tools → Personal Data. Configure consent and antispam in your form; do not collect unnecessary clinical data.' ) ); ?></p>
        <div class="xw-appt-guide"><strong><?php echo esc_html( xw_t( 'Conecta un formulario', 'Connect a form' ) ); ?></strong><p><?php echo esc_html( xw_t( 'En el widget Formulario de Elementor, añade un campo de tipo Cita. Solo los formularios con ese campo crean reservas. Nombre, correo y teléfono se detectan automáticamente; puedes indicar sus IDs dentro del campo Cita. Usa un solo campo Cita por formulario.', 'In the Elementor Form widget, add an Appointment field. Only forms with this field create bookings. Name, email and phone are detected automatically; you can map their IDs in the Appointment field. Use one Appointment field per form.' ) ); ?></p><small><?php echo esc_html( xw_t( 'Requiere el widget Formulario de Elementor Pro o una implementación compatible. Las acciones de correo y antispam se configuran en el formulario.', 'Requires the Elementor Pro Form widget or a compatible implementation. Email and antispam actions are configured in the form.' ) ); ?></small></div>
        <form id="xw-appt-schedule" data-nonce="<?php echo esc_attr( $nonce ); ?>">
            <div class="xw-appt-settings-grid">
                <section class="xw-appt-panel"><h2><?php echo esc_html( xw_t( 'Días y horarios de trabajo', 'Working days and hours' ) ); ?></h2><p><?php echo esc_html( xw_t( 'Elige las horas cada 15 minutos. Hasta cuatro turnos por día; un día vacío permanece cerrado.', 'Choose times every 15 minutes. Up to four shifts per day; an empty day stays closed.' ) ); ?></p>
                <?php foreach ( array( 1, 2, 3, 4, 5, 6, 0 ) as $day ) : ?>
                    <fieldset class="xw-appt-day" data-day="<?php echo (int) $day; ?>"><legend><?php echo esc_html( $week[ $day ] ); ?></legend><div class="xw-appt-shifts"><?php
                        $ranges = xw_appt_intervals( $settings['weekly'][ $day ] ?? '' );
                        if ( ! $ranges ) { xw_appt_shift_row( $day, 0 ); }
                        else { foreach ( $ranges as $index => $range ) { xw_appt_shift_row( $day, $index, sprintf( '%02d:%02d', intdiv( $range[0], 60 ), $range[0] % 60 ), sprintf( '%02d:%02d', intdiv( $range[1], 60 ), $range[1] % 60 ) ); } }
                    ?></div><button type="button" class="button xw-appt-add-shift"><?php echo esc_html( xw_t( '+ Añadir otro turno', '+ Add another shift' ) ); ?></button></fieldset>
                <?php endforeach; ?>
                <template id="xw-appt-shift-template"><?php xw_appt_shift_row( 0, 0 ); ?></template>
                <p class="description"><?php echo esc_html( sprintf( xw_t( 'Todos los horarios usan la zona horaria de WordPress: %s.', 'All times use the WordPress timezone: %s.' ), wp_timezone_string() ) ); ?> <a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>"><?php echo esc_html( xw_t( 'Revisar zona horaria', 'Review timezone' ) ); ?></a></p></section>
                <section class="xw-appt-panel"><h2><?php echo esc_html( xw_t( 'Reglas y fechas bloqueadas', 'Rules and blocked dates' ) ); ?></h2>
                    <div class="xw-appt-rules">
                    <?php foreach ( array( 'duration' => array( xw_t( 'Duración de cada cita', 'Appointment duration' ), 5, 240, xw_t( 'minutos', 'minutes' ) ), 'notice' => array( xw_t( 'Antelación mínima', 'Minimum advance notice' ), 0, 10080, xw_t( 'minutos', 'minutes' ) ), 'horizon' => array( xw_t( 'Permitir reservar hasta', 'Allow booking up to' ), 1, 180, xw_t( 'días en adelante', 'days ahead' ) ) ) as $key => $control ) : ?>
                    <div class="xw-appt-rule"><label for="xw-appt-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $control[0] ); ?></label><div><input id="xw-appt-<?php echo esc_attr( $key ); ?>" name="schedule[<?php echo esc_attr( $key ); ?>]" type="number" min="<?php echo (int) $control[1]; ?>" max="<?php echo (int) $control[2]; ?>" step="1" required value="<?php echo (int) $settings[ $key ]; ?>"> <span><?php echo esc_html( $control[3] ); ?></span></div></div>
                    <?php endforeach; ?>
                    </div>
                    <p class="description"><?php echo esc_html( xw_t( 'La duración vigente también se aplica a la disponibilidad de citas pendientes y confirmadas. No cambia su hora de inicio: revisa posibles solapamientos al ampliarla.', 'The current duration also applies to availability for pending and confirmed appointments. Their start time does not change: review possible overlaps when increasing it.' ) ); ?></p>
                    <h3><?php echo esc_html( xw_t( 'Días en los que no trabajarás', 'Days off' ) ); ?></h3>
                    <p class="description"><?php echo esc_html( xw_t( 'Marca en rojo los días libres. Pulsa de nuevo para desmarcarlos. Los días pasados están desactivados.', 'Mark days off in red. Press again to unmark them. Past days are disabled.' ) ); ?></p>
                    <input type="hidden" id="xw-appt-closed" name="schedule[closed]" value="<?php echo esc_attr( implode( ',', $settings['closed'] ) ); ?>">
                    <div id="xw-appt-closed-calendar"><input type="text" class="xw-appt-calendar-anchor" aria-label="<?php echo esc_attr( xw_t( 'Calendario de días libres', 'Days off calendar' ) ); ?>" tabindex="-1"></div><p id="xw-appt-closed-summary" aria-live="polite"></p>
                    <p class="description"><?php echo esc_html( xw_t( 'Estas fechas prevalecen sobre el horario semanal. Las citas existentes no se cancelan: revísalas en la lista.', 'These dates override the weekly schedule. Existing appointments are not cancelled: review them in the list.' ) ); ?></p>
                <div class="xw-appt-palette"><h2><?php echo esc_html( xw_t( 'Colores del calendario de citas', 'Appointment calendar colors' ) ); ?></h2><p><?php echo esc_html( xw_t( 'Se aplican al calendario del formulario. Vacío: usa el estilo automático.', 'Applied to the form calendar. Empty: uses the automatic style.' ) ); ?></p><div class="xw-appt-colors">
                    <?php foreach ( array( 'border' => xw_t( 'Círculo de fecha disponible', 'Available date circle' ), 'background' => xw_t( 'Background de fecha disponible', 'Available date background' ), 'text' => xw_t( 'Color del número de la fecha', 'Date number color' ), 'calendar_background' => xw_t( 'Background del calendario', 'Calendar background' ) ) as $key => $label ) : $color = $settings['colors'][ $key ] ?? ''; ?>
                    <div class="xw-appt-color"><label for="xw-appt-color-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label><div><input type="color" data-color-picker="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $color ?: '#3852eb' ); ?>" aria-label="<?php echo esc_attr( $label ); ?>"><input type="text" id="xw-appt-color-<?php echo esc_attr( $key ); ?>" data-color="<?php echo esc_attr( $key ); ?>" name="schedule[colors][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $color ); ?>" placeholder="<?php echo esc_attr( xw_t( 'Automático', 'Automatic' ) ); ?>" pattern="#[a-fA-F0-9]{6}" maxlength="7"><button type="button" class="button xw-appt-color-reset" aria-label="<?php echo esc_attr( xw_t( 'Restablecer', 'Reset' ) . ': ' . $label ); ?>">↺</button></div></div>
                    <?php endforeach; ?></div><div class="xw-appt-color-preview"><span>15</span><small><?php echo esc_html( xw_t( 'Fecha disponible · Vista previa', 'Available date · Preview' ) ); ?></small></div></div>
                    <div class="xw-appt-contact-settings"><h3><?php echo esc_html( xw_t( 'Contacto por WhatsApp', 'WhatsApp contact' ) ); ?></h3><label for="xw-appt-country"><?php echo esc_html( xw_t( 'Código de país para teléfonos locales', 'Country code for local phone numbers' ) ); ?></label><input id="xw-appt-country" name="schedule[whatsapp_country_code]" type="text" inputmode="numeric" pattern="[1-9][0-9]{0,2}" maxlength="3" value="<?php echo esc_attr( $settings['whatsapp_country_code'] ); ?>" placeholder="58" aria-describedby="xw-appt-country-help"><p class="description" id="xw-appt-country-help"><?php echo esc_html( xw_t( 'Solo dígitos, sin +: por ejemplo, 58 para Venezuela o 1 para EE. UU. Vacío: WhatsApp solo estará disponible para números que ya incluyan + o 00 y su código de país. No se envían mensajes automáticamente.', 'Digits only, without +: for example, 58 for Venezuela or 1 for the USA. Empty: WhatsApp is only available for numbers already including + or 00 and their country code. Messages are not sent automatically.' ) ); ?></p></div>
                </section>
            </div>
            <div class="xw-appt-save"><p role="status" aria-live="polite" id="xw-appt-save-status"><?php echo esc_html( xw_t( 'Sin cambios pendientes.', 'No unsaved changes.' ) ); ?></p><button type="submit" class="button button-primary" disabled><?php echo esc_html( xw_t( 'Guardar cambios', 'Save changes' ) ); ?></button></div>
        </form>
    </div>
    <?php
}

/** Explicit field mapping takes precedence; legacy submissions are detected by label. */
function xw_appt_office( $fields ) {
    $fields = is_string( $fields ) ? json_decode( $fields, true ) : $fields;
    if ( ! is_array( $fields ) ) { return ''; }
    foreach ( $fields as $field ) { if ( is_array( $field ) && 'office' === ( $field['role'] ?? '' ) && is_string( $field['value'] ?? null ) ) { return $field['value']; } }
    foreach ( $fields as $field ) {
        if ( ! is_array( $field ) || ! is_string( $field['label'] ?? null ) || ! is_string( $field['value'] ?? null ) ) { continue; }
        if ( preg_match( '/\b(consultorio|consultorios|office|clinic|clinica|location|ubicacion|sede)\b/u', strtolower( remove_accents( $field['label'] ) ) ) ) { return $field['value']; }
    }
    return '';
}

/** No country guessing and no automatic messaging. Keep the original stored phone untouched. */
function xw_appt_whatsapp_url( $phone, $country = '' ) {
    if ( ! is_string( $phone ) || ! preg_match( '/^\s*(?:\+|00)?[\d\s().-]+\s*$/D', $phone ) ) { return ''; }
    $phone = trim( $phone ); $international = str_starts_with( $phone, '+' ) || str_starts_with( $phone, '00' );
    $digits = preg_replace( '/\D/', '', $phone );
    if ( str_starts_with( $phone, '00' ) ) { $digits = substr( $digits, 2 ); }
    if ( ! $international ) {
        if ( ! is_string( $country ) || ! preg_match( '/^[1-9]\d{0,2}$/D', $country ) ) { return ''; }
        // Italy retains its geographic leading zero internationally; common trunk zeros elsewhere do not.
        $digits = $country . ( '39' === $country ? $digits : ltrim( $digits, '0' ) );
    }
    return preg_match( '/^[1-9]\d{7,14}$/D', $digits ) ? 'https://wa.me/' . $digits : '';
}

function xw_appt_admin_list( $nonce ) {
    global $wpdb;
    $table = xw_appt_table(); $statuses = xw_appt_statuses();
    $filter = isset( $_GET['booking_status'] ) && is_string( $_GET['booking_status'] ) ? sanitize_key( $_GET['booking_status'] ) : '';
    $search = isset( $_GET['booking_search'] ) && is_string( $_GET['booking_search'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_GET['booking_search'] ) ), 0, 100 ) : '';
    $page = isset( $_GET['booking_page'] ) && is_scalar( $_GET['booking_page'] ) ? max( 1, min( 100000, absint( $_GET['booking_page'] ) ) ) : 1;
    $where = "status <> 'holding'"; $args = array();
    if ( isset( $statuses[ $filter ] ) ) { $where .= ' AND status = %s'; $args[] = $filter; }
    if ( $search ) { $where .= ' AND (customer_name LIKE %s OR email LIKE %s OR phone LIKE %s)'; $like = '%' . $wpdb->esc_like( $search ) . '%'; $args = array_merge( $args, array( $like, $like, $like ) ); }
    $count_sql = "SELECT COUNT(*) FROM $table WHERE $where";
    $count = (int) $wpdb->get_var( $args ? $wpdb->prepare( $count_sql, $args ) : $count_sql );
    $args[] = 25; $args[] = ( $page - 1 ) * 25;
    $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE $where ORDER BY CASE status WHEN 'pending' THEN 0 WHEN 'confirmed' THEN 1 WHEN 'cancelled' THEN 2 ELSE 3 END, starts_at ASC, id DESC LIMIT %d OFFSET %d", $args ), ARRAY_A );
    ?>
    <h2 id="xw-appt-bookings"><?php echo esc_html( xw_t( 'Personas que han reservado', 'People who booked' ) ); ?> <span class="xw-appt-count"><?php echo (int) $count; ?></span></h2>
    <form method="get" class="xw-appt-filters"><input type="hidden" name="page" value="xw-appointments"><label for="xw-appt-filter" class="screen-reader-text"><?php echo esc_html( xw_t( 'Estado', 'Status' ) ); ?></label><select name="booking_status" id="xw-appt-filter"><option value=""><?php echo esc_html( xw_t( 'Todos los estados', 'All statuses' ) ); ?></option><?php foreach ( $statuses as $key => $label ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( $filter, $key, false ) . '>' . esc_html( $label ) . '</option>'; } ?></select><label for="xw-appt-search" class="screen-reader-text"><?php echo esc_html( xw_t( 'Buscar por nombre, correo o teléfono', 'Search name, email or phone' ) ); ?></label><input id="xw-appt-search" name="booking_search" type="search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php echo esc_attr( xw_t( 'Nombre, correo o teléfono', 'Name, email or phone' ) ); ?>"><button class="button"><?php echo esc_html( xw_t( 'Filtrar', 'Filter' ) ); ?></button></form>
    <div class="xw-appt-bookings" aria-labelledby="xw-appt-bookings">
    <?php if ( ! $rows ) { echo '<p class="xw-appt-empty">' . esc_html( xw_t( 'Todavía no hay citas para estos filtros. Añade el campo Cita a un formulario para recibir reservas aquí.', 'No appointments match these filters yet. Add an Appointment field to a form to receive bookings here.' ) ) . '</p>'; } ?>
    <?php foreach ( $rows ?: array() as $row ) :
        $date = new DateTimeImmutable( $row['starts_at'], new DateTimeZone( 'UTC' ) ); $date = $date->setTimezone( new DateTimeZone( $row['timezone'] ) );
        $office = xw_appt_office( $row['fields'] ); $country = xw_appt_settings()['whatsapp_country_code']; $whatsapp = xw_appt_whatsapp_url( $row['phone'], $country );
        $email = is_email( $row['email'] ) ? $row['email'] : ''; $can_move = in_array( $row['status'], array( 'pending', 'confirmed' ), true );
        $whatsapp_help = ! $row['phone'] ? xw_t( 'Esta reserva no tiene teléfono.', 'This booking has no phone number.' ) : xw_t( 'Revisa el teléfono y configura el código de país para números locales en los ajustes de esta página.', 'Check the phone number and configure the country code for local numbers in this page’s settings.' );
        $card_id = 'xw-booking-' . (int) $row['id'];
    ?>
        <article class="xw-appt-booking" data-status="<?php echo esc_attr( $row['status'] ); ?>" data-start="<?php echo esc_attr( $row['starts_at'] ); ?>" aria-labelledby="<?php echo esc_attr( $card_id ); ?>">
            <header class="xw-appt-booking-header"><h3 id="<?php echo esc_attr( $card_id ); ?>"><?php echo esc_html( $row['customer_name'] ?: xw_t( 'Sin nombre', 'No name' ) ); ?></h3><span class="xw-appt-badge"><?php echo esc_html( $statuses[ $row['status'] ] ?? $row['status'] ); ?></span></header>
            <dl class="xw-appt-booking-meta"><div><dt><?php echo esc_html( xw_t( 'Consultorio', 'Office' ) ); ?></dt><dd><?php echo esc_html( $office ?: xw_t( 'No indicado', 'Not provided' ) ); ?></dd></div><div><dt><?php echo esc_html( xw_t( 'Fecha y hora', 'Date and time' ) ); ?></dt><dd class="xw-appt-booking-date"><?php echo esc_html( $date->format( 'd/m/Y H:i' ) ); ?></dd></div></dl>
            <p class="xw-appt-booking-zone"><span><?php echo esc_html( $row['timezone'] ); ?></span> · #<?php echo (int) $row['id']; ?></p>
            <div class="xw-appt-booking-actions">
                <?php if ( $whatsapp ) : ?><a class="button button-primary" href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener noreferrer">WhatsApp<span class="screen-reader-text"><?php echo esc_html( xw_t( ' (abre otra pestaña)', ' (opens a new tab)' ) ); ?></span></a><?php else : ?><button type="button" class="button" disabled title="<?php echo esc_attr( $whatsapp_help ); ?>">WhatsApp</button><?php endif; ?>
                <?php if ( $email ) : ?><a class="button" href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( xw_t( 'Correo', 'Email' ) ); ?></a><?php else : ?><button type="button" class="button" disabled title="<?php echo esc_attr( xw_t( 'Esta reserva no tiene un correo válido.', 'This booking has no valid email.' ) ); ?>"><?php echo esc_html( xw_t( 'Correo', 'Email' ) ); ?></button><?php endif; ?>
                <button type="button" class="button xw-appt-reschedule-toggle" aria-expanded="false" aria-controls="xw-reschedule-<?php echo (int) $row['id']; ?>" <?php disabled( ! $can_move ); ?>><?php echo esc_html( xw_t( 'Cambiar fecha', 'Change date' ) ); ?></button>
            </div>
            <?php if ( ! $whatsapp && $row['phone'] ) : ?><p class="description xw-appt-contact-hint"><?php echo esc_html( $whatsapp_help ); ?></p><?php endif; ?>
            <form id="xw-reschedule-<?php echo (int) $row['id']; ?>" class="xw-appt-reschedule" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-booking="<?php echo (int) $row['id']; ?>" hidden>
                <label for="xw-new-date-<?php echo (int) $row['id']; ?>"><?php echo esc_html( xw_t( 'Nueva fecha y hora', 'New date and time' ) ); ?></label>
                <div class="xw-appointment" data-xw-appointment data-xw-appointment-action="xw_appt_admin_availability" data-booking="<?php echo (int) $row['id']; ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>"><div class="xw-appointment-date"><input id="xw-new-date-<?php echo (int) $row['id']; ?>" type="text" readonly autocomplete="off" inputmode="none" class="xw-appointment-date-input" placeholder="<?php echo esc_attr( xw_t( 'Selecciona fecha y hora', 'Select date and time' ) ); ?>"><button type="button" class="xw-appointment-open" aria-label="<?php echo esc_attr( xw_t( 'Abrir calendario de citas', 'Open appointment calendar' ) ); ?>"><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span></button></div><input type="hidden" class="xw-appointment-value" name="appointment" value=""></div>
                <p class="description"><?php echo esc_html( xw_t( 'Se comprobará la disponibilidad al guardar. No se envían avisos automáticos.', 'Availability is checked when saving. No automatic notifications are sent.' ) ); ?></p><button type="submit" class="button button-primary" disabled><?php echo esc_html( xw_t( 'Guardar fecha', 'Save date' ) ); ?></button><p role="status" aria-live="polite"></p>
            </form>
            <form class="xw-appt-booking-status" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-booking="<?php echo (int) $row['id']; ?>"><label class="screen-reader-text" for="xw-status-<?php echo (int) $row['id']; ?>"><?php echo esc_html( xw_t( 'Estado de la cita', 'Appointment status' ) ); ?></label><select id="xw-status-<?php echo (int) $row['id']; ?>" name="status" <?php disabled( 'cancelled' === $row['status'] ); ?>><?php foreach ( $statuses as $key => $label ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( $row['status'], $key, false ) . '>' . esc_html( $label ) . '</option>'; } ?></select><button type="submit" class="button button-primary" disabled><?php echo esc_html( xw_t( 'Guardar', 'Save' ) ); ?></button><p role="status" aria-live="polite"></p></form>
            <details class="xw-appt-booking-details"><summary><?php echo esc_html( xw_t( 'Ver datos del formulario', 'View form data' ) ); ?></summary><p><?php echo esc_html( $row['form_name'] ?: $row['form_id'] ); ?></p><dl><?php foreach ( json_decode( $row['fields'], true ) ?: array() as $field ) { if ( is_array( $field ) && is_string( $field['label'] ?? null ) && is_string( $field['value'] ?? null ) ) { echo '<dt>' . esc_html( $field['label'] ) . '</dt><dd>' . nl2br( esc_html( $field['value'] ) ) . '</dd>'; } } ?></dl><p><?php echo esc_html( $row['email'] ); ?><br><?php echo esc_html( $row['phone'] ); ?></p></details>
        </article>
    <?php endforeach; ?>
    </div>
    <?php if ( $count > 25 ) { echo '<nav class="tablenav-pages" aria-label="' . esc_attr( xw_t( 'Páginas de citas', 'Appointment pages' ) ) . '">' . wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'booking_page', '%#%' ), 'format' => '', 'current' => $page, 'total' => (int) ceil( $count / 25 ) ) ) ) . '</nav>'; }
}
