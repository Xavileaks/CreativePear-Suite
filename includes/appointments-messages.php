<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function xw_appt_site_t( $es, $en ) { return str_starts_with( strtolower( get_locale() ), 'es' ) ? $es : $en; }
function xw_appt_message_defaults() {
    return array(
        'pending' => xw_appt_site_t( "{saludo}\n\nHemos recibido tu solicitud de cita para el {fecha} a las {hora}. Está pendiente de confirmación.\n\n{sitio}", "{saludo}\n\nWe have received your appointment request for {fecha} at {hora}. It is awaiting confirmation.\n\n{sitio}" ),
        'confirmed' => xw_appt_site_t( "{saludo}\n\nTu cita del {fecha} a las {hora} está confirmada.\n\n{sitio}", "{saludo}\n\nYour appointment on {fecha} at {hora} is confirmed.\n\n{sitio}" ),
        'cancelled' => xw_appt_site_t( "{saludo}\n\nTu cita del {fecha} a las {hora} ha sido cancelada. Si quieres elegir otra fecha, ponte en contacto con nosotros.\n\n{sitio}", "{saludo}\n\nYour appointment on {fecha} at {hora} has been cancelled. Please contact us if you would like to choose another date.\n\n{sitio}" ),
        'rebook' => xw_appt_site_t( "{saludo}\n\n¿Te gustaría agendar una nueva cita? Escríbenos para elegir una fecha y hora disponibles.\n\n{sitio}", "{saludo}\n\nWould you like to book another appointment? Contact us to choose an available date and time.\n\n{sitio}" ),
    );
}
function xw_appt_message_text( $row, $kind = '', $template = null ) {
    $row = xw_appt_contact_row( $row ); $kind = $kind ?: $row['status']; $defaults = xw_appt_message_defaults();
    if ( null === $template && ( $parts = xw_appt_message_parts( $row, $kind ) ) ) {
        $chunks = array( $parts['greeting'], $parts['body'] );
        $details = array(); foreach ( $parts['details'] as $detail ) { $details[] = $detail['label'] . ': ' . $detail['value']; }
        if ( $details ) { $chunks[] = implode( "\n", $details ); }
        if ( $parts['show_site'] ) { $chunks[] = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ); }
        return implode( "\n\n", array_filter( $chunks, static function( $chunk ) { return '' !== trim( $chunk ); } ) );
    }
    if ( null === $template ) {
        $template = xw_appt_settings()['messages'][ $kind ] ?? '';
        if ( '' === trim( $template ) ) { $template = $defaults[ $kind ] ?? xw_appt_site_t( "{saludo}\n\nTe escribimos sobre tu cita del {fecha} a las {hora}.\n\n{sitio}", "{saludo}\n\nWe are contacting you about your appointment on {fecha} at {hora}.\n\n{sitio}" ); }
    }
    $date = ( new DateTimeImmutable( $row['starts_at'], new DateTimeZone( 'UTC' ) ) )->setTimezone( new DateTimeZone( $row['timezone'] ) );
    $name = trim( $row['customer_name'] );
    $states = array( 'pending' => xw_appt_site_t( 'Pendiente', 'Pending' ), 'confirmed' => xw_appt_site_t( 'Confirmada', 'Confirmed' ), 'completed' => xw_appt_site_t( 'Completada', 'Completed' ), 'cancelled' => xw_appt_site_t( 'Cancelada', 'Cancelled' ) );
    $replacements = array( '{saludo}' => xw_appt_site_t( 'Hola', 'Hello' ) . ( $name ? ' ' . $name : '' ) . ',', '{nombre}' => $name, '{fecha}' => $date->format( 'd/m/Y' ), '{hora}' => $date->format( 'H:i' ), '{consultorio}' => xw_appt_office( $row['fields'] ), '{sitio}' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), '{estado}' => $states[ $row['status'] ] ?? '' );
    foreach ( xw_appt_record_fields( $row ) as $field ) { $replacements[ '{campo:' . $field['id'] . '}' ] = $field['value']; }
    return strtr( $template, $replacements );
}
function xw_appt_message_subject( $kind ) {
    $subjects = array( 'pending' => xw_appt_site_t( 'Solicitud de cita recibida', 'Appointment request received' ), 'confirmed' => xw_appt_site_t( 'Cita confirmada', 'Appointment confirmed' ), 'cancelled' => xw_appt_site_t( 'Cita cancelada', 'Appointment cancelled' ), 'rebook' => xw_appt_site_t( 'Agenda tu próxima cita', 'Book your next appointment' ) );
    return ( $subjects[ $kind ] ?? xw_appt_site_t( 'Información de tu cita', 'Your appointment information' ) ) . ( ( $layout = xw_appt_message_layout( $kind ) ) && empty( $layout['show_site'] ) ? '' : ' · ' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
}
function xw_appt_message_html( $row, $subject, $text, $kind ) {
    $site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ); $url = home_url( '/' );
    $date = ( new DateTimeImmutable( $row['starts_at'], new DateTimeZone( 'UTC' ) ) )->setTimezone( new DateTimeZone( $row['timezone'] ) );
    $logo = wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'medium' );
    $icon = add_query_arg( 'ver', XW_FUNCTIONS_VERSION, plugin_dir_url( XW_FUNCTIONS_FILE ) . 'assets/images/email-received.png' );
    $office = xw_appt_office( $row['fields'] ); $parts = xw_appt_message_parts( $row, $kind ); $show_site = ! $parts || $parts['show_site'];
    ob_start(); ?>
<!doctype html><html lang="<?php echo esc_attr( str_starts_with( strtolower( get_locale() ), 'es' ) ? 'es' : 'en' ); ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc_html( $subject ); ?></title></head><body style="margin:0;padding:0;background:#f5f7fc;font-family:Arial,Helvetica,sans-serif;color:#18264a"><table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f5f7fc;width:100%"><tr><td align="center" style="padding:24px 12px"><!--[if mso]><table width="600" role="presentation"><tr><td><![endif]--><table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;max-width:600px;background:white;border:1px solid #e1e6f2;border-radius:12px"><tr><td style="padding:28px">
<?php if ( $show_site ) : ?><?php if ( $logo ) : ?><img src="<?php echo esc_url( $logo ); ?>" width="120" alt="<?php echo esc_attr( $site ); ?>" style="display:block;max-width:100%;height:auto;border:0;margin-bottom:12px"><?php endif; ?><a href="<?php echo esc_url( $url ); ?>" style="font-size:18px;font-weight:bold;color:#18264a;text-decoration:none"><?php echo esc_html( $site ); ?></a><?php endif; ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;margin-top:24px;border-top:1px solid #e7ebf4"><tr><td width="64" style="padding:24px 12px 20px 0;vertical-align:top"><img src="<?php echo esc_url( $icon ); ?>" width="48" height="48" alt="" style="display:block;border:0"></td><td style="padding:24px 0 20px"><h1 style="font-size:24px;line-height:32px;margin:0;color:#18264a"><?php echo esc_html( xw_appt_message_subject( $kind ) === $subject ? explode( ' · ', $subject )[0] : $subject ); ?></h1></td></tr></table>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;background:#f7f8ff;border:1px solid #e8ebfb;border-radius:8px"><tr><td style="padding:22px;font-size:16px;line-height:26px;word-break:break-word"><?php echo nl2br( esc_html( $text ), false ); ?></td></tr></table>
<?php if ( $parts ) : ?>
<?php if ( $parts['details'] ) : ?><h2 style="font-size:15px;color:#253452;margin:24px 0 12px"><?php echo esc_html( xw_appt_site_t( 'Detalles', 'Details' ) ); ?></h2><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;font-size:14px;line-height:22px;color:#253452"><?php foreach ( $parts['details'] as $detail ) : ?><tr><td style="padding:8px 12px 8px 0;vertical-align:top;border-bottom:1px solid #e7ebf4;font-weight:bold;width:36%"><?php echo esc_html( $detail['label'] ); ?></td><td style="padding:8px 0;vertical-align:top;border-bottom:1px solid #e7ebf4;word-break:break-word"><?php echo nl2br( esc_html( $detail['value'] ), false ); ?></td></tr><?php endforeach; ?></table><?php endif; ?>
<?php else : ?>
<p style="font-size:13px;color:#53647e;margin:24px 0 8px;font-weight:bold"><?php echo esc_html( 'rebook' === $kind ? xw_appt_site_t( 'Cita anterior', 'Previous appointment' ) : xw_appt_site_t( 'Datos de la cita', 'Appointment details' ) ); ?></p><p style="font-size:14px;line-height:23px;color:#253452;margin:0"><?php echo esc_html( $date->format( 'd/m/Y · H:i' ) . ' · ' . $row['timezone'] ); ?><?php if ( $office ) { echo '<br>' . esc_html( $office ); } ?></p>
<?php endif; ?>
<?php if ( $show_site ) : ?><p style="margin:24px 0 0;padding-top:18px;border-top:1px solid #e7ebf4;font-size:12px;line-height:20px;color:#53647e"><a href="<?php echo esc_url( $url ); ?>" style="color:#3156c8"><?php echo esc_html( $site ); ?></a></p><?php endif; ?></td></tr></table><!--[if mso]></td></tr></table><![endif]--></td></tr></table></body></html>
    <?php return trim( ob_get_clean() );
}
function xw_appt_message_token( $row, $subject, $text, $kind ) {
    return hash_hmac( 'sha256', wp_json_encode( array( xw_appt_record_revision( $row ), xw_appt_contact_row( $row )['email'], $subject, $text, $kind, xw_appt_message_layout( $kind ) ) ), wp_salt( 'auth' ) );
}
function xw_appt_message_request( $id, $input, $send = false ) {
    if ( ! xw_appt_can_manage() || ! xw_appt_ready() ) { return new WP_Error( 'forbidden', xw_t( 'No autorizado.', 'Not authorized.' ), 403 ); }
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . xw_appt_table() . " WHERE id=%d AND status<>'holding'", $id ), ARRAY_A );
    $contact = $row ? xw_appt_contact_row( $row ) : null;
    if ( ! $contact || ! is_email( $contact['email'] ) ) { return new WP_Error( 'email', xw_t( 'Guarda un correo válido en esta cita antes de continuar.', 'Save a valid email on this appointment before continuing.' ), 400 ); }
    $kind = 'rebook' === ( $input['kind'] ?? '' ) ? 'rebook' : $row['status'];
    $subject = $input['subject'] ?? xw_appt_message_subject( $kind ); $text = $input['text'] ?? xw_appt_message_email_text( $row, $kind );
    if ( ! is_string( $subject ) || ! is_string( $text ) || '' === trim( $subject ) || '' === trim( $text ) || mb_strlen( $subject ) > 250 || mb_strlen( $text ) > 20000 ) { return new WP_Error( 'message', xw_t( 'Revisa el asunto y el mensaje.', 'Check the subject and message.' ), 400 ); }
    $subject = sanitize_text_field( $subject ); $text = sanitize_textarea_field( $text );
    if ( '' === trim( $subject ) || '' === trim( $text ) ) { return new WP_Error( 'message', xw_t( 'Escribe un asunto y un mensaje.', 'Enter a subject and message.' ), 400 ); }
    $html = xw_appt_message_html( $row, $subject, $text, $kind ); $token = xw_appt_message_token( $row, $subject, $text, $kind );
    if ( ! $send ) { return array( 'subject' => $subject, 'text' => $text, 'html' => $html, 'recipient' => $contact['email'], 'preview_token' => $token, 'kind' => $kind ); }
    if ( ! is_string( $input['preview_token'] ?? null ) || ! hash_equals( $token, $input['preview_token'] ) ) { return new WP_Error( 'preview', xw_t( 'La cita o el texto cambiaron. Actualiza la vista previa antes de enviar.', 'The appointment or text changed. Refresh the preview before sending.' ), 409 ); }
    $request = $input['request_id'] ?? '';
    if ( ! is_string( $request ) || ! preg_match( '/^[a-zA-Z0-9-]{16,80}$/D', $request ) ) { return new WP_Error( 'request', xw_t( 'Recarga el editor de correo.', 'Reload the email editor.' ), 400 ); }
    $key = 'xw_appt_mail_' . substr( hash_hmac( 'sha256', get_current_user_id() . ':' . $id . ':' . $request, wp_salt( 'auth' ) ), 0, 40 );
    $lock = xw_appt_lock(); if ( ! $lock ) { return new WP_Error( 'busy', xw_t( 'Inténtalo de nuevo en unos segundos.', 'Try again in a few seconds.' ), 409 ); }
    try {
        $previous = get_transient( $key );
        if ( is_array( $previous ) ) { return hash_equals( $previous['token'], $token ) ? array( 'sent' => true, 'duplicate' => true ) : new WP_Error( 'request', xw_t( 'Abre un nuevo correo para enviar otro mensaje.', 'Open a new email to send another message.' ), 409 ); }
        $fresh = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . xw_appt_table() . ' WHERE id=%d', $id ), ARRAY_A );
        if ( ! $fresh || ! hash_equals( $token, xw_appt_message_token( $fresh, $subject, $text, $kind ) ) ) { return new WP_Error( 'changed', xw_t( 'La cita cambió. Actualiza la vista previa.', 'The appointment changed. Refresh the preview.' ), 409 ); }
        if ( ! wp_mail( $contact['email'], $subject, $html, array( 'Content-Type: text/html; charset=UTF-8' ) ) ) { return new WP_Error( 'mail', xw_t( 'WordPress no pudo enviar el correo. Revisa la configuración de envío y vuelve a intentarlo.', 'WordPress could not send the email. Check mail configuration and try again.' ), 502 ); }
        set_transient( $key, array( 'token' => $token ), HOUR_IN_SECONDS );
        return array( 'sent' => true );
    } finally { xw_appt_unlock( $lock ); }
}
function xw_appt_message_settings() { xw_appt_builder_ui(); }
function xw_appt_message_dialog() { ?>
    <dialog id="xw-appt-mail-dialog" aria-labelledby="xw-appt-mail-title"><div class="xw-appt-mail-heading"><h2 id="xw-appt-mail-title"><?php echo esc_html( xw_t( 'Correo de la cita', 'Appointment email' ) ); ?></h2><button type="button" class="button xw-appt-mail-close" aria-label="<?php echo esc_attr( xw_t( 'Cerrar', 'Close' ) ); ?>">×</button></div><form id="xw-appt-mail-form"><div class="xw-appt-mail-grid"><div><p class="xw-appt-mail-recipient"></p><label for="xw-appt-mail-subject"><?php echo esc_html( xw_t( 'Asunto', 'Subject' ) ); ?></label><input id="xw-appt-mail-subject" name="subject" type="text" maxlength="250" required><label for="xw-appt-mail-text"><?php echo esc_html( xw_t( 'Mensaje', 'Message' ) ); ?></label><textarea id="xw-appt-mail-text" name="text" rows="10" maxlength="20000" required></textarea><p class="description"><?php echo esc_html( xw_t( 'El mensaje se envía con el diseño HTML de la vista previa. Modificar el texto no envía nada.', 'The message is sent with the HTML design shown in the preview. Editing the text does not send anything.' ) ); ?></p><div class="xw-appt-mail-actions"><button type="button" class="button xw-appt-mail-preview"><?php echo esc_html( xw_t( 'Actualizar vista previa', 'Refresh preview' ) ); ?></button><button type="submit" class="button button-primary" disabled><?php echo esc_html( xw_t( 'Enviar correo', 'Send email' ) ); ?></button></div><p role="status" aria-live="polite"></p></div><iframe title="<?php echo esc_attr( xw_t( 'Vista previa del correo HTML', 'HTML email preview' ) ); ?>" sandbox=""></iframe></div></form></dialog>
<?php }
