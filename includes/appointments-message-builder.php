<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function xw_appt_field_ref( $form, $id ) { return 'f:' . rtrim( strtr( base64_encode( wp_json_encode( array( (string) $form, (string) $id ) ) ), '+/', '-_' ), '=' ); }
function xw_appt_decode_ref( $ref ) {
    if ( ! is_string( $ref ) || strlen( $ref ) > 600 || ! str_starts_with( $ref, 'f:' ) ) { return false; }
    $value = json_decode( base64_decode( strtr( substr( $ref, 2 ), '-_', '+/' ), true ) ?: '', true );
    return is_array( $value ) && count( $value ) === 2 && is_string( $value[0] ?? null ) && is_string( $value[1] ?? null ) && strlen( $value[0] ) <= 100 && strlen( $value[1] ) <= 100 ? $value : false;
}
/** Labels only: no submitted values leave this server-side catalogue. */
function xw_appt_builder_catalog() {
    global $wpdb; $groups = array();
    $types = array( 'text', 'email', 'tel', 'textarea', 'select', 'radio', 'checkbox', 'date', 'time', 'number', 'url', 'xw_appointment' );
    $add = static function( $form, $name, $fields ) use ( &$groups, $types ) {
        foreach ( $fields as $field ) {
            if ( ! in_array( $field['type'] ?? 'text', $types, true ) || ! is_string( $field['id'] ?? null ) ) { continue; }
            if ( 'text' === ( $field['type'] ?? 'text' ) && in_array( $field['id'], array( '__customer_name', '__email', '__phone' ), true ) && ( $field['contact_role'] ?? '' ) === substr( $field['id'], 2 ) ) { continue; }
            $ref = xw_appt_field_ref( $form, $field['id'] );
            $groups[ $form ]['label'] = sanitize_text_field( $name ?: xw_t( 'Formulario', 'Form' ) );
            $groups[ $form ]['fields'][ $ref ] = sanitize_text_field( $field['label'] ?? $field['id'] );
        }
    };
    if ( xw_appt_ready() ) {
        $table = xw_appt_table();
        foreach ( $wpdb->get_results( "SELECT a.form_id,a.form_name,a.fields FROM $table a INNER JOIN (SELECT MAX(id) AS last_id FROM $table WHERE status<>'holding' GROUP BY form_id ORDER BY last_id DESC LIMIT 100) recent ON recent.last_id=a.id", ARRAY_A ) ?: array() as $row ) {
            $fields = json_decode( $row['fields'], true ); if ( is_array( $fields ) ) { $add( $row['form_id'], $row['form_name'], $fields ); }
        }
    }
    $walk = static function( $nodes, $depth = 0 ) use ( &$walk, $add, $types ) {
        if ( $depth > 20 || ! is_array( $nodes ) ) { return; }
        foreach ( $nodes as $node ) {
            if ( ! is_array( $node ) ) { continue; }
            if ( 'form' === ( $node['widgetType'] ?? '' ) && is_array( $node['settings']['form_fields'] ?? null ) ) {
                $fields = array(); $connected = false;
                foreach ( $node['settings']['form_fields'] as $field ) {
                    if ( ! in_array( $field['field_type'] ?? '', $types, true ) ) { continue; }
                    $connected = $connected || 'xw_appointment' === $field['field_type'];
                    $fields[] = array( 'id' => $field['custom_id'] ?? $field['_id'] ?? '', 'label' => $field['field_label'] ?? $field['custom_id'] ?? '', 'type' => $field['field_type'] );
                }
                if ( $connected ) { $add( $node['id'] ?? '', $node['settings']['form_name'] ?? '', $fields ); }
            }
            $walk( $node['elements'] ?? array(), $depth + 1 );
        }
    };
    $definitions = $wpdb->get_col( "SELECT m.meta_value FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE m.meta_key='_elementor_data' AND p.post_status NOT IN ('trash','auto-draft','inherit') AND m.meta_value LIKE '%xw_appointment%' ORDER BY m.meta_id DESC LIMIT 300" );
    foreach ( $definitions as $json ) { if ( strlen( $json ) <= 5000000 ) { $walk( json_decode( $json, true ) ); } }
    return $groups;
}
function xw_appt_builder_defaults( $kind ) {
    $bodies = array(
        'pending' => xw_appt_site_t( 'Hemos recibido tu solicitud de cita. Está pendiente de confirmación.', 'We have received your appointment request. It is awaiting confirmation.' ),
        'confirmed' => xw_appt_site_t( 'Tu cita está confirmada.', 'Your appointment is confirmed.' ),
        'cancelled' => xw_appt_site_t( 'Tu cita ha sido cancelada. Si quieres elegir otra fecha, ponte en contacto con nosotros.', 'Your appointment has been cancelled. Please contact us if you would like to choose another date.' ),
        'rebook' => xw_appt_site_t( '¿Te gustaría agendar una nueva cita? Escríbenos para elegir una fecha y hora disponibles.', 'Would you like to book another appointment? Contact us to choose an available date and time.' ),
    );
    return array( 'greeting' => xw_appt_site_t( 'Hola,', 'Hello,' ), 'greeting_field' => '@name', 'body' => $bodies[ $kind ] ?? '', 'details' => array( '@date', '@time' ), 'show_site' => 1 );
}
function xw_appt_message_layout( $kind ) {
    $settings = xw_appt_settings();
    if ( isset( $settings['message_layouts'][ $kind ] ) && is_array( $settings['message_layouts'][ $kind ] ) ) { return $settings['message_layouts'][ $kind ]; }
    return empty( $settings['messages'][ $kind ] ) && in_array( $kind, array( 'pending', 'confirmed', 'cancelled', 'rebook' ), true ) ? xw_appt_builder_defaults( $kind ) : null;
}
function xw_appt_resolve_detail( $row, $ref ) {
    $date = ( new DateTimeImmutable( $row['starts_at'], new DateTimeZone( 'UTC' ) ) )->setTimezone( new DateTimeZone( $row['timezone'] ) );
    $fixed = array( '@name' => array( 'label' => xw_appt_site_t( 'Nombre', 'Name' ), 'value' => xw_appt_contact_row( $row )['customer_name'] ), '@date' => array( 'label' => xw_appt_site_t( 'Fecha', 'Date' ), 'value' => $date->format( 'd/m/Y' ) ), '@time' => array( 'label' => xw_appt_site_t( 'Hora', 'Time' ), 'value' => $date->format( 'H:i' ) ) );
    if ( isset( $fixed[ $ref ] ) ) { return $fixed[ $ref ]; }
    $pair = xw_appt_decode_ref( $ref ); if ( ! $pair || $pair[0] !== (string) $row['form_id'] ) { return null; }
    foreach ( xw_appt_record_fields( $row ) as $field ) {
        if ( $field['id'] !== $pair[1] ) { continue; }
        return array( 'label' => $field['label'], 'value' => 'appointment' === ( $field['role'] ?? '' ) ? $date->format( 'd/m/Y H:i' ) : mb_substr( $field['value'], 0, 1000 ) );
    }
    return null;
}
function xw_appt_message_parts( $row, $kind ) {
    $layout = xw_appt_message_layout( $kind ); if ( ! $layout ) { return null; }
    $field = xw_appt_resolve_detail( $row, $layout['greeting_field'] ?? '' );
    $greeting = trim( (string) ( $layout['greeting'] ?? '' ) . ( ! empty( $field['value'] ) ? ' ' . $field['value'] : '' ) );
    $details = array();
    foreach ( $layout['details'] ?? array() as $ref ) { $detail = xw_appt_resolve_detail( $row, $ref ); if ( $detail && '' !== trim( $detail['value'] ) ) { $details[] = $detail; } }
    return array( 'greeting' => $greeting, 'body' => $layout['body'] ?? '', 'details' => $details, 'show_site' => ! empty( $layout['show_site'] ) );
}
function xw_appt_message_email_text( $row, $kind ) {
    $parts = xw_appt_message_parts( $row, $kind );
    return $parts ? implode( "\n\n", array_filter( array( $parts['greeting'], $parts['body'] ), static function( $part ) { return '' !== trim( $part ); } ) ) : xw_appt_message_text( $row, $kind );
}
function xw_appt_sanitize_layouts( $input ) {
    if ( ! is_array( $input ) ) { return new WP_Error( 'message', xw_t( 'Revisa los cuatro mensajes.', 'Check all four messages.' ) ); }
    $catalog = xw_appt_builder_catalog(); $allowed = array( '' => true, '@name' => true, '@date' => true, '@time' => true );
    foreach ( $catalog as $group ) { foreach ( $group['fields'] as $ref => $label ) { $allowed[ $ref ] = true; } }
    $previous = xw_appt_settings()['message_layouts']; $out = array();
    foreach ( array( 'pending', 'confirmed', 'cancelled', 'rebook' ) as $kind ) {
        $layout = $input[ $kind ] ?? null;
        if ( ! is_array( $layout ) || ! is_string( $layout['greeting'] ?? null ) || mb_strlen( $layout['greeting'] ) > 200 || ! is_string( $layout['body'] ?? null ) || '' === trim( $layout['body'] ) || mb_strlen( $layout['body'] ) > 4000 || ! is_string( $layout['greeting_field'] ?? null ) || ! is_array( $layout['details'] ?? null ) || count( $layout['details'] ) > 11 || ! in_array( $layout['show_site'] ?? null, array( '0', '1' ), true ) ) { return new WP_Error( 'message', xw_t( 'Revisa el saludo, el mensaje y los detalles (hasta 10) de cada estado.', 'Check each greeting, message and details (up to 10).' ) ); }
        $old_refs = array_merge( array( $previous[ $kind ]['greeting_field'] ?? '' ), $previous[ $kind ]['details'] ?? array() );
        foreach ( array_merge( array( $layout['greeting_field'] ), $layout['details'] ) as $ref ) { if ( ! is_string( $ref ) || ( ! isset( $allowed[ $ref ] ) && ! in_array( $ref, $old_refs, true ) ) ) { return new WP_Error( 'field', xw_t( 'Elige un campo disponible en el desplegable.', 'Choose an available field from the dropdown.' ) ); } }
        $out[ $kind ] = array( 'greeting' => sanitize_text_field( $layout['greeting'] ), 'greeting_field' => $layout['greeting_field'], 'body' => sanitize_textarea_field( $layout['body'] ), 'details' => array_values( array_unique( array_filter( $layout['details'], static function( $ref ) { return '' !== $ref; } ) ) ), 'show_site' => (int) $layout['show_site'] );
        if ( count( $out[ $kind ]['details'] ) > 10 ) { return new WP_Error( 'details', xw_t( 'Puedes añadir hasta 10 detalles.', 'You can add up to 10 details.' ) ); }
        if ( '' === trim( $out[ $kind ]['body'] ) ) { return new WP_Error( 'message', xw_t( 'Escribe el mensaje central.', 'Enter the main message.' ) ); }
    }
    return $out;
}
function xw_appt_builder_options( $catalog, $selected = '' ) {
    $found = '' === $selected; echo '<option value="">' . esc_html( xw_t( 'Sin campo', 'No field' ) ) . '</option><optgroup label="' . esc_attr( xw_t( 'Datos de la cita', 'Appointment data' ) ) . '">';
    foreach ( array( '@name' => xw_t( 'Nombre de la persona', 'Person’s name' ), '@date' => xw_t( 'Fecha de la cita', 'Appointment date' ), '@time' => xw_t( 'Hora de la cita', 'Appointment time' ) ) as $ref => $label ) { $found = $found || $selected === $ref; echo '<option value="' . esc_attr( $ref ) . '" ' . selected( $selected, $ref, false ) . '>' . esc_html( $label ) . '</option>'; } echo '</optgroup>';
    foreach ( $catalog as $group ) { echo '<optgroup label="' . esc_attr( $group['label'] ) . '">'; foreach ( $group['fields'] as $ref => $label ) { $found = $found || $selected === $ref; echo '<option value="' . esc_attr( $ref ) . '" ' . selected( $selected, $ref, false ) . '>' . esc_html( $label ) . '</option>'; } echo '</optgroup>'; }
    if ( ! $found ) { echo '<option value="' . esc_attr( $selected ) . '" selected>' . esc_html( xw_t( 'Campo anterior (ya no disponible)', 'Previous field (no longer available)' ) ) . '</option>'; }
}
function xw_appt_builder_ui() {
    $settings = xw_appt_settings(); $catalog = xw_appt_builder_catalog(); ?>
    <form id="xw-appt-messages" class="xw-appt-messages" data-nonce="<?php echo esc_attr( wp_create_nonce( 'xw_appointment_admin' ) ); ?>" aria-labelledby="xw-appt-messages-title"><h2 id="xw-appt-messages-title"><?php echo esc_html( xw_t( 'Mensajes de WhatsApp y correo', 'WhatsApp and email messages' ) ); ?></h2><p class="description"><?php echo esc_html( xw_t( 'Escribe el saludo y el mensaje. Elige los datos que quieres añadir: si una cita no contiene un campo, se omite. Cambiar el estado no envía mensajes automáticamente.', 'Write the greeting and message. Choose the data to include: fields missing from an appointment are omitted. Status changes do not automatically send messages.' ) ); ?></p><div class="xw-appt-messages-grid">
    <?php foreach ( array( 'pending' => xw_t( 'Pendiente', 'Pending' ), 'confirmed' => xw_t( 'Confirmada', 'Confirmed' ), 'cancelled' => xw_t( 'Cancelada', 'Cancelled' ), 'rebook' => xw_t( 'Reagendar', 'Rebook' ) ) as $kind => $label ) :
        $layout = xw_appt_message_layout( $kind ); $legacy = null === $layout; $layout = $layout ?? xw_appt_builder_defaults( $kind ); $prefix = 'layouts[' . $kind . ']'; ?>
        <section class="xw-appt-panel xw-appt-message-panel xw-appt-builder" data-kind="<?php echo esc_attr( $kind ); ?>"><h3><?php echo esc_html( $label ); ?></h3>
        <?php if ( $legacy ) : ?><p class="description"><?php echo esc_html( xw_t( 'El mensaje anterior sigue activo hasta que guardes esta nueva configuración.', 'Your previous message remains active until you save this new configuration.' ) ); ?></p><?php endif; ?>
        <div class="xw-appt-builder-greeting"><div><label for="xw-greeting-<?php echo esc_attr( $kind ); ?>"><?php echo esc_html( xw_t( 'Saludo', 'Greeting' ) ); ?></label><input id="xw-greeting-<?php echo esc_attr( $kind ); ?>" type="text" name="<?php echo esc_attr( $prefix ); ?>[greeting]" maxlength="200" value="<?php echo esc_attr( $layout['greeting'] ); ?>"></div><div><label for="xw-greeting-field-<?php echo esc_attr( $kind ); ?>"><?php echo esc_html( xw_t( 'Añadir un campo al saludo', 'Add a field to the greeting' ) ); ?></label><select id="xw-greeting-field-<?php echo esc_attr( $kind ); ?>" name="<?php echo esc_attr( $prefix ); ?>[greeting_field]"><?php xw_appt_builder_options( $catalog, $layout['greeting_field'] ); ?></select></div></div>
        <label for="xw-body-<?php echo esc_attr( $kind ); ?>"><?php echo esc_html( xw_t( 'Mensaje central', 'Main message' ) ); ?></label><textarea id="xw-body-<?php echo esc_attr( $kind ); ?>" name="<?php echo esc_attr( $prefix ); ?>[body]" rows="4" maxlength="4000" required><?php echo esc_textarea( $layout['body'] ); ?></textarea>
        <h4><?php echo esc_html( xw_t( 'Detalles', 'Details' ) ); ?></h4><input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[details][]" value=""><div class="xw-appt-builder-details"><?php foreach ( $layout['details'] as $index => $ref ) { xw_appt_builder_detail_row( $kind, $index, $ref, $catalog ); } ?></div><button type="button" class="button xw-appt-detail-add"><?php echo esc_html( xw_t( '+ Añadir detalle', '+ Add detail' ) ); ?></button>
        <template class="xw-appt-detail-template"><?php xw_appt_builder_detail_row( $kind, 'new', '', $catalog ); ?></template>
        <input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[show_site]" value="0"><label class="xw-appt-site-toggle"><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[show_site]" value="1" <?php checked( ! empty( $layout['show_site'] ) ); ?>><?php echo esc_html( xw_t( 'Mostrar el nombre del sitio', 'Show the site name' ) ); ?></label>
        </section>
    <?php endforeach; ?></div><div class="xw-appt-save"><p role="status" aria-live="polite"></p><button type="submit" class="button button-primary" disabled><?php echo esc_html( xw_t( 'Guardar mensajes', 'Save messages' ) ); ?></button></div></form>
    <?php
}
function xw_appt_builder_detail_row( $kind, $index, $ref, $catalog ) {
    $id = 'xw-detail-' . $kind . '-' . $index; ?><div class="xw-appt-detail-row"><label class="screen-reader-text" data-detail-label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( xw_t( 'Campo del detalle', 'Detail field' ) ); ?></label><select id="<?php echo esc_attr( $id ); ?>" name="layouts[<?php echo esc_attr( $kind ); ?>][details][]"><?php xw_appt_builder_options( $catalog, $ref ); ?></select><button type="button" class="button xw-appt-detail-plus" aria-label="<?php echo esc_attr( xw_t( 'Añadir otro detalle', 'Add another detail' ) ); ?>">+</button><button type="button" class="button xw-appt-detail-remove" aria-label="<?php echo esc_attr( xw_t( 'Eliminar detalle', 'Remove detail' ) ); ?>">×</button></div><?php
}
