<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function xw_appt_admin_list( $nonce ) {
    if ( ! xw_appt_can_manage() ) { return; }
    global $wpdb;
    $table = xw_appt_table(); $statuses = xw_appt_statuses();
    $view = isset( $_GET['booking_view'] ) && is_string( $_GET['booking_view'] ) && in_array( $_GET['booking_view'], array( 'upcoming', 'rebook', 'history' ), true ) ? $_GET['booking_view'] : 'upcoming';
    $before = isset( $_GET['changed_before'] ) && is_string( $_GET['changed_before'] ) && xw_appt_rebook_cutoff( $_GET['changed_before'] ) ? $_GET['changed_before'] : '';
    $cutoff = xw_appt_rebook_cutoff( $before );
    $filter = isset( $_GET['booking_status'] ) && is_string( $_GET['booking_status'] ) ? sanitize_key( $_GET['booking_status'] ) : '';
    $search = isset( $_GET['booking_search'] ) && is_string( $_GET['booking_search'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_GET['booking_search'] ) ), 0, 100 ) : '';
    $page = isset( $_GET['booking_page'] ) && is_scalar( $_GET['booking_page'] ) ? max( 1, min( 100000, absint( $_GET['booking_page'] ) ) ) : 1;
    $where = "status <> 'holding'"; $args = array(); $now = gmdate( 'Y-m-d H:i:s' );
    if ( 'upcoming' === $view ) { $where .= " AND status IN ('pending','confirmed') AND ends_at >= %s"; $args[] = $now; }
    elseif ( 'history' === $view ) { $where .= " AND (ends_at < %s OR status IN ('completed','cancelled'))"; $args[] = $now; }
    else { $where .= " AND status IN ('confirmed','completed','cancelled') AND rebooked_to IS NULL AND status_changed_at IS NOT NULL AND status_changed_at <= %s AND (status='cancelled' OR ends_at <= %s)"; $args[] = $cutoff; $args[] = $now; }
    if ( isset( $statuses[ $filter ] ) ) { $where .= ' AND status = %s'; $args[] = $filter; }
    if ( $search ) { $where .= ' AND (customer_name LIKE %s OR email LIKE %s OR phone LIKE %s)'; $like = '%' . $wpdb->esc_like( $search ) . '%'; $args = array_merge( $args, array( $like, $like, $like ) ); }
    if ( isset( $_GET['booking_id'] ) && is_scalar( $_GET['booking_id'] ) ) { $where .= ' AND id=%d'; $args[] = absint( $_GET['booking_id'] ); }
    $count_sql = "SELECT COUNT(*) FROM $table WHERE $where";
    $count = (int) $wpdb->get_var( $args ? $wpdb->prepare( $count_sql, $args ) : $count_sql );
    $args[] = 25; $args[] = ( $page - 1 ) * 25;
    $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE $where ORDER BY CASE status WHEN 'pending' THEN 0 WHEN 'confirmed' THEN 1 WHEN 'cancelled' THEN 2 ELSE 3 END, starts_at ASC, id DESC LIMIT %d OFFSET %d", $args ), ARRAY_A );
    ?>
    <nav class="xw-appt-views" aria-label="<?php echo esc_attr( xw_t( 'Apartados de citas', 'Appointment views' ) ); ?>"><?php foreach ( array( 'upcoming' => xw_t( 'Próximas citas', 'Upcoming appointments' ), 'rebook' => xw_t( 'Listas para reagendar', 'Ready to rebook' ), 'history' => xw_t( 'Historial', 'History' ) ) as $key => $label ) { echo '<a class="button' . ( $key === $view ? ' button-primary' : '' ) . '" href="' . esc_url( add_query_arg( 'booking_view', $key, xw_appt_admin_url() ) ) . '" ' . ( $key === $view ? 'aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a>'; } ?></nav>
    <h2 id="xw-appt-bookings"><?php echo esc_html( 'rebook' === $view ? xw_t( 'Listas para reagendar', 'Ready to rebook' ) : ( 'history' === $view ? xw_t( 'Historial de citas', 'Appointment history' ) : xw_t( 'Personas que han reservado', 'People who booked' ) ) ); ?> <span class="xw-appt-count"><?php echo $count; ?></span></h2>
    <?php if ( 'rebook' === $view ) : ?><p class="description"><?php echo esc_html( sprintf( xw_t( 'Se cuentan %d días desde el cambio de estado. Las confirmadas futuras y las pendientes quedan fuera. Las antiguas sin fecha de estado se pueden completar en Editar datos.', '%d days are counted from the status change. Future confirmed and pending appointments are excluded. Use Edit details to enter a missing status date on older bookings.' ), xw_appt_settings()['rebook_days'] ) ); ?></p><?php endif; ?>
    <form method="get" class="xw-appt-filters"><input type="hidden" name="page" value="xw-appointments"><input type="hidden" name="booking_view" value="<?php echo esc_attr( $view ); ?>"><label for="xw-appt-filter" class="screen-reader-text"><?php echo esc_html( xw_t( 'Estado', 'Status' ) ); ?></label><select name="booking_status" id="xw-appt-filter"><option value=""><?php echo esc_html( xw_t( 'Todos los estados', 'All statuses' ) ); ?></option><?php foreach ( $statuses as $key => $label ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( $filter, $key, false ) . '>' . esc_html( $label ) . '</option>'; } ?></select><label for="xw-appt-search" class="screen-reader-text"><?php echo esc_html( xw_t( 'Buscar por nombre, correo o teléfono', 'Search name, email or phone' ) ); ?></label><input id="xw-appt-search" name="booking_search" type="search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php echo esc_attr( xw_t( 'Nombre, correo o teléfono', 'Name, email or phone' ) ); ?>"><?php if ( 'rebook' === $view ) : ?><label class="xw-appt-date-filter" for="xw-appt-changed-before"><?php echo esc_html( xw_t( 'Cambios de estado hasta', 'Status changes through' ) ); ?><input type="date" id="xw-appt-changed-before" name="changed_before" max="<?php echo esc_attr( current_datetime()->format( 'Y-m-d' ) ); ?>" value="<?php echo esc_attr( $before ?: current_datetime()->modify( '-' . (int) xw_appt_settings()['rebook_days'] . ' days' )->format( 'Y-m-d' ) ); ?>"></label><?php endif; ?><button class="button"><?php echo esc_html( xw_t( 'Filtrar', 'Filter' ) ); ?></button></form>
    <div class="xw-appt-bookings" aria-labelledby="xw-appt-bookings">
    <?php if ( ! $rows ) { echo '<p class="xw-appt-empty">' . esc_html( xw_t( 'No hay citas para estos filtros.', 'No appointments match these filters.' ) ) . '</p>'; } ?>
    <?php foreach ( $rows ?: array() as $row ) { echo xw_appt_booking_card( $row, $nonce, $view, $before ); } ?>
    </div>
    <?php if ( $count > 25 ) { echo '<nav class="tablenav-pages" aria-label="' . esc_attr( xw_t( 'Páginas de citas', 'Appointment pages' ) ) . '">' . wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'booking_page', '%#%' ), 'format' => '', 'current' => $page, 'total' => (int) ceil( $count / 25 ) ) ) ) . '</nav>'; }
}
