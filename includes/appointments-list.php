<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function xw_appt_list_state( $input ) {
    $view = is_string( $input['booking_view'] ?? null ) && in_array( $input['booking_view'], array( 'upcoming', 'pending', 'rebook', 'history' ), true ) ? $input['booking_view'] : 'upcoming';
    return array( 'view' => $view, 'days' => xw_appt_rebook_days( $input['rebook_after'] ?? null ), 'search' => is_string( $input['booking_search'] ?? null ) ? mb_substr( sanitize_text_field( $input['booking_search'] ), 0, 100 ) : '', 'page' => is_scalar( $input['booking_page'] ?? null ) ? max( 1, min( 100000, absint( $input['booking_page'] ) ) ) : 1, 'id' => is_scalar( $input['booking_id'] ?? null ) ? absint( $input['booking_id'] ) : 0 );
}
function xw_appt_list_data( $state ) {
    xw_appt_repair_contacts();
    global $wpdb; $table = xw_appt_table(); $args = array(); $now = gmdate( 'Y-m-d H:i:s' );
    if ( 'upcoming' === $state['view'] ) { $where = "status='confirmed' AND ends_at >= %s"; $args[] = $now; }
    elseif ( 'pending' === $state['view'] ) { $where = "status='pending'"; }
    elseif ( 'history' === $state['view'] ) { $where = "(status IN ('completed','cancelled') OR (status='confirmed' AND ends_at < %s))"; $args[] = $now; }
    else { $where = "status IN ('completed','cancelled') AND rebooked_to IS NULL AND status_changed_at IS NOT NULL AND status_changed_at <= %s AND (status='cancelled' OR ends_at <= %s)"; $args[] = xw_appt_rebook_cutoff( '', $state['days'] ); $args[] = $now; }
    if ( '' !== $state['search'] ) {
        $like = '%' . $wpdb->esc_like( $state['search'] ) . '%'; $where .= ' AND (customer_name LIKE %s OR email LIKE %s OR phone LIKE %s'; $args = array_merge( $args, array( $like, $like, $like ) );
        $digits = preg_replace( '/\D/', '', $state['search'] );
        if ( strlen( $digits ) >= 2 && preg_match( '/^[+\d\s().-]+$/D', $state['search'] ) ) { $where .= " OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'(',''),')',''),'.',''),'+','') LIKE %s"; $args[] = '%' . $digits . '%'; }
        $where .= ')';
    }
    if ( $state['id'] ) { $where .= ' AND id=%d'; $args[] = $state['id']; }
    $sql = "SELECT COUNT(*) FROM $table WHERE $where"; $count = (int) $wpdb->get_var( $args ? $wpdb->prepare( $sql, $args ) : $sql );
    $state['page'] = min( $state['page'], max( 1, (int) ceil( $count / 25 ) ) );
    $args[] = 25; $args[] = ( $state['page'] - 1 ) * 25;
    $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE $where ORDER BY starts_at ASC, id DESC LIMIT %d OFFSET %d", $args ), ARRAY_A );
    return array( 'state' => $state, 'count' => $count, 'rows' => $rows ?: array() );
}
function xw_appt_list_results( $data, $nonce ) {
    $state = $data['state']; ob_start(); ?>
    <div class="xw-appt-bookings" aria-labelledby="xw-appt-bookings">
        <?php if ( ! $data['rows'] ) { echo '<p class="xw-appt-empty">' . esc_html( xw_t( 'No hay citas que coincidan en este apartado.', 'No matching appointments in this view.' ) ) . '</p>'; } ?>
        <?php foreach ( $data['rows'] as $row ) { echo xw_appt_booking_card( $row, $nonce, $state['view'], $state['days'] ); } ?>
    </div>
    <?php if ( $data['count'] > 25 ) {
        $base = add_query_arg( array( 'booking_view' => $state['view'], 'booking_search' => $state['search'], 'rebook_after' => $state['days'], 'booking_page' => '%#%' ), xw_appt_admin_url() );
        echo '<nav class="xw-appt-pagination tablenav-pages" aria-label="' . esc_attr( xw_t( 'Páginas de citas', 'Appointment pages' ) ) . '">' . wp_kses_post( paginate_links( array( 'base' => $base, 'format' => '', 'current' => $state['page'], 'total' => (int) ceil( $data['count'] / 25 ) ) ) ) . '</nav>';
    }
    return ob_get_clean();
}
function xw_appt_admin_list( $nonce ) {
    if ( ! xw_appt_can_manage() ) { return; }
    $state = xw_appt_list_state( wp_unslash( $_GET ) ); if ( false === $state['days'] ) { $state['days'] = xw_appt_rebook_days(); }
    $data = xw_appt_list_data( $state ); $state = $data['state'];
    $views = array( 'upcoming' => xw_t( 'Próximas citas', 'Upcoming appointments' ), 'pending' => xw_t( 'Pendientes', 'Pending' ), 'rebook' => xw_t( 'Listas para reagendar', 'Ready to rebook' ), 'history' => xw_t( 'Historial', 'History' ) ); ?>
    <nav class="xw-appt-views" aria-label="<?php echo esc_attr( xw_t( 'Apartados de citas', 'Appointment views' ) ); ?>"><?php foreach ( $views as $key => $label ) { echo '<a class="button' . ( $key === $state['view'] ? ' button-primary' : '' ) . '" href="' . esc_url( add_query_arg( array( 'booking_view' => $key, 'rebook_after' => $state['days'] ), xw_appt_admin_url() ) ) . '" ' . ( $key === $state['view'] ? 'aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a>'; } ?></nav>
    <h2 id="xw-appt-bookings"><?php echo esc_html( $views[ $state['view'] ] ); ?> <span class="xw-appt-count" aria-live="polite"><?php echo $data['count']; ?></span></h2>
    <?php if ( 'rebook' === $state['view'] ) : ?><p class="description"><?php echo esc_html( xw_t( 'Completadas y canceladas cuyo último cambio de estado fue hace al menos los días indicados. Las antiguas sin esa fecha se pueden completar en Editar datos.', 'Completed and cancelled appointments whose last status change was at least the specified number of days ago. For older records without that date, use Edit details.' ) ); ?></p><?php endif; ?>
    <form method="get" class="xw-appt-filters" id="xw-appt-live-search" data-view="<?php echo esc_attr( $state['view'] ); ?>" data-days="<?php echo (int) $state['days']; ?>" data-page="<?php echo (int) $state['page']; ?>" data-booking-id="<?php echo (int) $state['id']; ?>">
        <input type="hidden" name="page" value="xw-appointments"><input type="hidden" name="booking_view" value="<?php echo esc_attr( $state['view'] ); ?>">
        <label for="xw-appt-search" class="screen-reader-text"><?php echo esc_html( xw_t( 'Buscar por nombre, correo o teléfono', 'Search name, email or phone' ) ); ?></label><input id="xw-appt-search" name="booking_search" type="search" value="<?php echo esc_attr( $state['search'] ); ?>" placeholder="<?php echo esc_attr( xw_t( 'Nombre, correo o teléfono', 'Name, email or phone' ) ); ?>" autocomplete="off" aria-controls="xw-appt-results">
        <?php if ( 'rebook' === $state['view'] ) : ?><label class="xw-appt-days-filter" for="xw-appt-rebook-after"><?php echo esc_html( xw_t( 'Días desde el cambio de estado', 'Days since status change' ) ); ?><input type="number" id="xw-appt-rebook-after" name="rebook_after" min="1" max="3650" step="1" required value="<?php echo (int) $state['days']; ?>" aria-controls="xw-appt-results"></label><?php endif; ?>
        <noscript><button class="button"><?php echo esc_html( xw_t( 'Buscar', 'Search' ) ); ?></button></noscript>
    </form>
    <p id="xw-appt-list-status" role="status" aria-live="polite"></p><div id="xw-appt-results"><?php echo xw_appt_list_results( $data, $nonce ); ?></div>
    <?php
}
