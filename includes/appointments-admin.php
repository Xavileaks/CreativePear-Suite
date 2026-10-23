<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_menu', static function() {
    if ( ! xw_feature_enabled( 'elementor_appointments' ) ) { return; }
    add_menu_page( xw_t( 'Citas', 'Appointments' ), xw_t( 'Citas', 'Appointments' ), 'manage_options', 'xw-appointments', 'xw_appt_admin_page', 'dashicons-calendar-alt', 25.1 );
} );
function xw_appt_admin_assets( $hook ) {
    if ( 'toplevel_page_xw-appointments' !== $hook || ! current_user_can( 'manage_options' ) ) { return; }
    $base = plugin_dir_path( XW_FUNCTIONS_FILE ); $url = plugin_dir_url( XW_FUNCTIONS_FILE );
    wp_enqueue_style( 'xw-appointments-admin', $url . 'assets/css/appointments-admin.css', array(), filemtime( $base . 'assets/css/appointments-admin.css' ) );
    wp_enqueue_script( 'xw-appointments-admin', $url . 'assets/js/appointments-admin.js', array(), filemtime( $base . 'assets/js/appointments-admin.js' ), true );
    wp_localize_script( 'xw-appointments-admin', 'xwAppointmentAdmin', array( 'url' => admin_url( 'admin-ajax.php' ), 'saving' => xw_t( 'Guardando…', 'Saving…' ), 'saved' => xw_t( 'Cambios guardados.', 'Changes saved.' ), 'error' => xw_t( 'No se pudo guardar. Revisa la conexión e inténtalo de nuevo.', 'Could not save. Check your connection and try again.' ), 'dirty' => xw_t( 'Hay cambios sin guardar.', 'You have unsaved changes.' ), 'cancel' => xw_t( '¿Cancelar esta cita y liberar su horario?', 'Cancel this appointment and release its time slot?' ) ) );
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
    if ( 'status' !== $op ) { wp_send_json_error( array( 'message' => xw_t( 'Acción no válida.', 'Invalid action.' ) ), 400 ); }
    $id = isset( $_POST['booking'] ) && is_scalar( $_POST['booking'] ) ? absint( $_POST['booking'] ) : 0;
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

function xw_appt_admin_page() {
    if ( ! current_user_can( 'manage_options' ) || ! xw_feature_enabled( 'elementor_appointments' ) ) { wp_die( esc_html( xw_t( 'No autorizado.', 'Not authorized.' ) ) ); }
    if ( ! xw_appt_ready() ) { echo '<div class="notice notice-error"><p>' . esc_html( xw_t( 'No se pudieron crear las tablas de la agenda. Revisa la base de datos.', 'Could not create the calendar tables. Check your database.' ) ) . '</p></div>'; return; }
    $settings = xw_appt_settings(); $nonce = wp_create_nonce( 'xw_appointment_admin' );
    $week = array( xw_t( 'Domingo', 'Sunday' ), xw_t( 'Lunes', 'Monday' ), xw_t( 'Martes', 'Tuesday' ), xw_t( 'Miércoles', 'Wednesday' ), xw_t( 'Jueves', 'Thursday' ), xw_t( 'Viernes', 'Friday' ), xw_t( 'Sábado', 'Saturday' ) );
    ?>
    <div class="wrap xw-appointments-admin">
        <h1><?php echo esc_html( xw_t( 'Citas', 'Appointments' ) ); ?></h1>
        <p class="xw-appt-intro"><?php echo esc_html( xw_t( 'Una agenda para este sitio. Define la disponibilidad y gestiona las solicitudes de tus formularios.', 'One calendar for this site. Set availability and manage requests from your forms.' ) ); ?></p>
        <div class="xw-appt-guide"><strong><?php echo esc_html( xw_t( 'Conecta un formulario', 'Connect a form' ) ); ?></strong><p><?php echo esc_html( xw_t( 'En el widget Formulario de Elementor, añade un campo de tipo Cita. Solo los formularios con ese campo crean reservas. Nombre, correo y teléfono se detectan automáticamente; puedes indicar sus IDs dentro del campo Cita. Usa un solo campo Cita por formulario.', 'In the Elementor Form widget, add an Appointment field. Only forms with this field create bookings. Name, email and phone are detected automatically; you can map their IDs in the Appointment field. Use one Appointment field per form.' ) ); ?></p><small><?php echo esc_html( xw_t( 'Requiere el widget Formulario de Elementor Pro o una implementación compatible. Las acciones de correo y antispam se configuran en el formulario.', 'Requires the Elementor Pro Form widget or a compatible implementation. Email and antispam actions are configured in the form.' ) ); ?></small></div>
        <form id="xw-appt-schedule" data-nonce="<?php echo esc_attr( $nonce ); ?>">
            <div class="xw-appt-settings-grid">
                <section class="xw-appt-panel"><h2><?php echo esc_html( xw_t( 'Días y horarios de trabajo', 'Working days and hours' ) ); ?></h2><p><?php echo esc_html( xw_t( 'Deja vacío un día para cerrarlo. Separa varios turnos con una coma.', 'Leave a day empty to close it. Separate multiple shifts with a comma.' ) ); ?></p>
                <?php foreach ( array( 1, 2, 3, 4, 5, 6, 0 ) as $day ) : ?>
                    <div class="xw-appt-hours"><label for="xw-appt-day-<?php echo (int) $day; ?>"><?php echo esc_html( $week[ $day ] ); ?></label><input id="xw-appt-day-<?php echo (int) $day; ?>" name="schedule[weekly][<?php echo (int) $day; ?>]" type="text" value="<?php echo esc_attr( $settings['weekly'][ $day ] ?? '' ); ?>" placeholder="09:00-13:00, 15:00-18:00" maxlength="150" autocomplete="off"></div>
                <?php endforeach; ?>
                <p class="description"><?php echo esc_html( sprintf( xw_t( 'Todos los horarios usan la zona horaria de WordPress: %s.', 'All times use the WordPress timezone: %s.' ), wp_timezone_string() ) ); ?> <a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>"><?php echo esc_html( xw_t( 'Revisar zona horaria', 'Review timezone' ) ); ?></a></p></section>
                <section class="xw-appt-panel"><h2><?php echo esc_html( xw_t( 'Reglas y fechas bloqueadas', 'Rules and blocked dates' ) ); ?></h2>
                    <?php foreach ( array( 'duration' => array( xw_t( 'Duración de cada cita', 'Appointment duration' ), 5, 240, xw_t( 'minutos', 'minutes' ) ), 'notice' => array( xw_t( 'Antelación mínima', 'Minimum advance notice' ), 0, 10080, xw_t( 'minutos', 'minutes' ) ), 'horizon' => array( xw_t( 'Permitir reservar hasta', 'Allow booking up to' ), 1, 180, xw_t( 'días en adelante', 'days ahead' ) ) ) as $key => $control ) : ?>
                    <div class="xw-appt-rule"><label for="xw-appt-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $control[0] ); ?></label><div><input id="xw-appt-<?php echo esc_attr( $key ); ?>" name="schedule[<?php echo esc_attr( $key ); ?>]" type="number" min="<?php echo (int) $control[1]; ?>" max="<?php echo (int) $control[2]; ?>" step="1" required value="<?php echo (int) $settings[ $key ]; ?>"> <span><?php echo esc_html( $control[3] ); ?></span></div></div>
                    <?php endforeach; ?>
                    <label for="xw-appt-closed"><strong><?php echo esc_html( xw_t( 'Días en los que no trabajarás', 'Days off' ) ); ?></strong></label><textarea id="xw-appt-closed" name="schedule[closed]" rows="5" maxlength="10000" placeholder="2026-12-25&#10;2027-01-01"><?php echo esc_textarea( implode( "\n", $settings['closed'] ) ); ?></textarea><p class="description"><?php echo esc_html( xw_t( 'Una fecha por línea, AAAA-MM-DD. Las citas existentes no se cancelan al cambiar horarios o días: revísalas en la lista.', 'One date per line, YYYY-MM-DD. Changing hours or days does not cancel existing appointments: review them in the list.' ) ); ?></p>
                </section>
            </div>
            <div class="xw-appt-save"><p role="status" aria-live="polite" id="xw-appt-save-status"><?php echo esc_html( xw_t( 'Sin cambios pendientes.', 'No unsaved changes.' ) ); ?></p><button type="submit" class="button button-primary" disabled><?php echo esc_html( xw_t( 'Guardar cambios', 'Save changes' ) ); ?></button></div>
        </form>
        <?php xw_appt_admin_list( $nonce ); ?>
        <p class="description"><?php echo esc_html( xw_t( 'Las reservas quedan pendientes hasta que las confirmes. No se envían avisos adicionales al cambiar estados. Datos privados: acceso de administrador, exportación y borrado mediante Herramientas → Datos personales de WordPress. Configura consentimiento y antispam en tu formulario; no recopiles datos clínicos innecesarios.', 'Bookings remain pending until you confirm them. Status changes do not send additional notifications. Private data: administrator access, export and erasure through WordPress Tools → Personal Data. Configure consent and antispam in your form; do not collect unnecessary clinical data.' ) ); ?></p>
    </div>
    <?php
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
    $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE $where ORDER BY starts_at DESC, id DESC LIMIT %d OFFSET %d", $args ), ARRAY_A );
    ?>
    <h2 id="xw-appt-bookings"><?php echo esc_html( xw_t( 'Personas que han reservado', 'People who booked' ) ); ?> <span class="xw-appt-count"><?php echo (int) $count; ?></span></h2>
    <form method="get" class="xw-appt-filters"><input type="hidden" name="page" value="xw-appointments"><label for="xw-appt-filter" class="screen-reader-text"><?php echo esc_html( xw_t( 'Estado', 'Status' ) ); ?></label><select name="booking_status" id="xw-appt-filter"><option value=""><?php echo esc_html( xw_t( 'Todos los estados', 'All statuses' ) ); ?></option><?php foreach ( $statuses as $key => $label ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( $filter, $key, false ) . '>' . esc_html( $label ) . '</option>'; } ?></select><label for="xw-appt-search" class="screen-reader-text"><?php echo esc_html( xw_t( 'Buscar por nombre, correo o teléfono', 'Search name, email or phone' ) ); ?></label><input id="xw-appt-search" name="booking_search" type="search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php echo esc_attr( xw_t( 'Nombre, correo o teléfono', 'Name, email or phone' ) ); ?>"><button class="button"><?php echo esc_html( xw_t( 'Filtrar', 'Filter' ) ); ?></button></form>
    <div class="xw-appt-table-wrap"><table class="widefat striped xw-appt-table"><thead><tr><?php foreach ( array( xw_t( 'Fecha y hora', 'Date and time' ), xw_t( 'Persona', 'Person' ), xw_t( 'Formulario y datos', 'Form and details' ), xw_t( 'Estado', 'Status' ) ) as $heading ) { echo '<th scope="col">' . esc_html( $heading ) . '</th>'; } ?></tr></thead><tbody>
    <?php if ( ! $rows ) { echo '<tr><td colspan="4">' . esc_html( xw_t( 'Todavía no hay citas para estos filtros. Añade el campo Cita a un formulario para recibir reservas aquí.', 'No appointments match these filters yet. Add an Appointment field to a form to receive bookings here.' ) ) . '</td></tr>'; } ?>
    <?php foreach ( $rows ?: array() as $row ) : $date = new DateTimeImmutable( $row['starts_at'], new DateTimeZone( 'UTC' ) ); $date = $date->setTimezone( new DateTimeZone( $row['timezone'] ) ); ?>
        <tr><td><strong><?php echo esc_html( $date->format( 'd/m/Y H:i' ) ); ?></strong><br><small><?php echo esc_html( $row['timezone'] ); ?></small><br><small>#<?php echo (int) $row['id']; ?></small></td><td><strong><?php echo esc_html( $row['customer_name'] ?: '—' ); ?></strong><br><?php echo esc_html( $row['email'] ); ?><br><?php echo esc_html( $row['phone'] ); ?></td><td><?php echo esc_html( $row['form_name'] ?: $row['form_id'] ); ?><details><summary><?php echo esc_html( xw_t( 'Ver datos del formulario', 'View form data' ) ); ?></summary><dl><?php foreach ( json_decode( $row['fields'], true ) ?: array() as $field ) { echo '<dt>' . esc_html( $field['label'] ) . '</dt><dd>' . nl2br( esc_html( $field['value'] ) ) . '</dd>'; } ?></dl></details></td><td><form class="xw-appt-booking-status" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-booking="<?php echo (int) $row['id']; ?>"><label class="screen-reader-text" for="xw-status-<?php echo (int) $row['id']; ?>"><?php echo esc_html( xw_t( 'Estado de la cita', 'Appointment status' ) ); ?></label><select id="xw-status-<?php echo (int) $row['id']; ?>" name="status" <?php disabled( 'cancelled' === $row['status'] ); ?>><?php foreach ( $statuses as $key => $label ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( $row['status'], $key, false ) . '>' . esc_html( $label ) . '</option>'; } ?></select><button class="button" disabled><?php echo esc_html( xw_t( 'Guardar', 'Save' ) ); ?></button><p role="status" aria-live="polite"></p></form></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php if ( $count > 25 ) { echo '<nav class="tablenav-pages" aria-label="' . esc_attr( xw_t( 'Páginas de citas', 'Appointment pages' ) ) . '">' . wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'booking_page', '%#%' ), 'format' => '', 'current' => $page, 'total' => (int) ceil( $count / 25 ) ) ) ) . '</nav>'; }
}
