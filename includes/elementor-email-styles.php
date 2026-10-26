<?php
/** Styles only existing, generic Elementor Email/Email2 HTML actions. Never sends mail. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_form_email_t( $es, $en ) { return 0 === strpos( strtolower( get_locale() ), 'es' ) ? $es : $en; }

/** Request-local context: no submitted information or rendered emails are persisted. */
function &xw_form_email_contexts() { static $contexts = array(); return $contexts; }

function xw_form_email_default_subject( $subject ) {
    $site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
    $defaults = array( '', 'New message from "' . $site . '"', 'Nuevo mensaje de "' . $site . '"', 'Nuevo mensaje desde "' . $site . '"' );
    if ( function_exists( '__' ) ) { $defaults[] = sprintf( __( 'New message from "%s"', 'elementor-pro' ), $site ); }
    return in_array( trim( (string) $subject ), $defaults, true );
}

function xw_form_email_value( $value ) {
    if ( is_scalar( $value ) ) { return (string) $value; }
    if ( ! is_array( $value ) ) { return ''; }
    return implode( ', ', array_filter( array_map( 'xw_form_email_value', $value ), static function( $part ) { return '' !== $part; } ) );
}

function xw_form_email_fields( $record ) {
    $rows = array();
    foreach ( (array) $record->get( 'fields' ) as $id => $field ) {
        $type = $field['type'] ?? '';
        if ( in_array( $type, array( 'password', 'recaptcha', 'recaptcha_v3', 'hcaptcha', 'turnstile', 'honeypot', 'captcha', 'html', 'step', 'submit' ), true ) || 'attach' === ( $field['attachment_type'] ?? '' ) ) { continue; }
        $value = trim( xw_form_email_value( $field['value'] ?? '' ) );
        if ( '' === $value ) { continue; }
        $rows[] = array( 'label' => (string) ( ! empty( $field['title'] ) ? $field['title'] : $id ), 'value' => $value, 'type' => $type );
    }
    return $rows;
}

function xw_form_email_metadata( $record, $kind ) {
    $settings = (array) $record->get( 'form_settings' ); $selected = $settings[ 'form_metadata' . ( 'customer' === $kind ? '_2' : '' ) ] ?? array();
    if ( ! is_array( $selected ) || ! $selected ) { return array(); }
    $meta = (array) $record->get( 'meta' );
    if ( method_exists( $record, 'get_form_meta' ) ) { $missing = array_diff( $selected, array_keys( $meta ) ); if ( $missing ) { $meta = array_merge( $meta, (array) $record->get_form_meta( $missing ) ); } }
    $labels = array(
        'date' => xw_form_email_t( 'Fecha', 'Date' ), 'time' => xw_form_email_t( 'Hora', 'Time' ),
        'page_url' => xw_form_email_t( 'Página de origen', 'Source page' ), 'page_title' => xw_form_email_t( 'Página', 'Page' ),
        'user_agent' => xw_form_email_t( 'Navegador', 'Browser' ), 'remote_ip' => xw_form_email_t( 'Dirección IP', 'IP address' ), 'credit' => xw_form_email_t( 'Generado por', 'Powered by' ),
    );
    $rows = array();
    foreach ( $selected as $id ) {
        if ( ! is_string( $id ) || ! isset( $meta[ $id ] ) ) { continue; }
        $value = trim( xw_form_email_value( $meta[ $id ]['value'] ?? '' ) ); if ( '' === $value ) { continue; }
        $rows[] = array( 'label' => $labels[ $id ] ?? ( $meta[ $id ]['title'] ?? $id ), 'value' => $value, 'type' => 'page_url' === $id ? 'url' : 'meta', 'id' => $id );
    }
    return $rows;
}

function xw_form_email_display_value( $row ) {
    $text = nl2br( esc_html( $row['value'] ), false ); $url = '';
    if ( 'email' === $row['type'] && is_email( $row['value'] ) ) { $url = 'mailto:' . $row['value']; }
    elseif ( 'tel' === $row['type'] && preg_match( '/^\+?[\d\s().-]+$/', $row['value'] ) ) { $url = 'tel:' . preg_replace( '/[^\d+]/', '', $row['value'] ); }
    elseif ( 'url' === $row['type'] && preg_match( '#^https?://#i', $row['value'] ) ) { $url = $row['value']; }
    if ( $url ) { return '<a href="' . esc_url( $url, array( 'http', 'https', 'mailto', 'tel' ) ) . '" style="color:#3156c8;text-decoration:underline;word-break:break-word">' . $text . '</a>'; }
    return $text;
}

/** Table layout and inline styles remain readable without images, web fonts or media queries. */
function xw_form_email_render( $record, $kind ) {
    $customer = 'customer' === $kind; $site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
    $site = $site ?: xw_form_email_t( 'Sitio web', 'Website' ); $site_url = home_url( '/' );
    $title = $customer ? xw_form_email_t( 'Hemos recibido tu mensaje', 'We have received your message' ) : xw_form_email_t( 'Nuevo mensaje recibido', 'New message received' );
    $caption = $customer ? xw_form_email_t( 'Confirmación de recepción', 'Message confirmation' ) : xw_form_email_t( 'Notificación de contacto', 'Contact notification' );
    $form_name = xw_form_email_value( $record->get_form_settings( 'form_name' ) );
    $metadata = xw_form_email_metadata( $record, $kind ); $rows = $customer ? array() : xw_form_email_fields( $record );
    $settings = xw_get_settings(); $custom = $settings['elementor_emails']['customer_message'] ?? '';
    $message = is_string( $custom ) && '' !== trim( $custom ) ? $custom : xw_form_email_t( 'Gracias por ponerte en contacto con nosotros. Hemos recibido tu mensaje correctamente.', 'Thank you for contacting us. We have received your message successfully.' );
    $logo = wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'medium' );
    $icon = plugin_dir_url( XW_FUNCTIONS_FILE ) . 'assets/images/' . ( $customer ? 'email-received.png' : 'email-message.png' );
    $lang = 0 === strpos( strtolower( get_locale() ), 'es' ) ? 'es' : 'en';
    ob_start();
    ?>
<!doctype html><html lang="<?php echo esc_attr( $lang ); ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc_html( $title ); ?></title><style>@media only screen and (max-width:480px){.xw-mail-pad{padding:22px 18px!important}.xw-mail-label,.xw-mail-value{display:block!important;width:100%!important}.xw-mail-label{padding-bottom:3px!important}.xw-mail-title{font-size:23px!important;line-height:30px!important}}</style></head>
<body style="margin:0;padding:0;background:#f5f7fc;color:#18264a;font-family:Arial,Helvetica,sans-serif" data-xw-form-email="<?php echo esc_attr( $kind ); ?>">
<div style="display:none;font-size:1px;color:#f5f7fc;line-height:1px;max-height:0;overflow:hidden"><?php echo esc_html( $title . ' · ' . $site ); ?></div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#f5f7fc"><tr><td align="center" style="padding:24px 12px">
<!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #e1e6f2;border-radius:12px"><tr><td class="xw-mail-pad" style="padding:32px">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;border-bottom:1px solid #e7ebf4"><tr><td style="padding:0 12px 24px 0;vertical-align:middle">
<?php if ( $logo ) : ?><img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $site ); ?>" width="120" style="display:block;width:120px;max-width:100%;height:auto;border:0;margin:0 0 8px"><?php endif; ?>
<a href="<?php echo esc_url( $site_url ); ?>" style="font-size:18px;font-weight:bold;color:#18264a;text-decoration:none"><?php echo esc_html( $site ); ?></a></td><td align="right" style="padding:0 0 24px;font-size:12px;line-height:18px;color:#53647e;vertical-align:middle"><?php echo esc_html( $caption ); ?></td></tr></table>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%"><tr><td width="72" style="padding:28px 16px 24px 0;vertical-align:top"><img src="<?php echo esc_url( $icon ); ?>" alt="<?php echo esc_attr( $customer ? xw_form_email_t( 'Recibido', 'Received' ) : xw_form_email_t( 'Mensaje', 'Message' ) ); ?>" width="56" height="56" style="display:block;width:56px;height:56px;border:0"></td><td style="padding:28px 0 24px;vertical-align:top"><h1 class="xw-mail-title" style="margin:0;font-size:25px;line-height:33px;font-weight:bold;color:#18264a"><?php echo esc_html( $title ); ?></h1>
<?php if ( ! $customer ) : ?><p style="margin:12px 0 0;font-size:15px;line-height:24px;color:#53647e"><?php echo esc_html( xw_form_email_t( 'Has recibido un nuevo mensaje a través del formulario de tu sitio web. Estos son los datos enviados:', 'You have received a new message through your website form. Here are the submitted details:' ) ); ?></p><?php endif; ?></td></tr></table>
<?php if ( $customer ) : ?>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#f7f8ff;border:1px solid #e8ebfb;border-radius:9px"><tr><td style="padding:24px;font-size:16px;line-height:26px;color:#253452;word-break:break-word;overflow-wrap:anywhere"><?php echo nl2br( esc_html( $message ), false ); ?></td></tr></table>
<?php if ( $form_name ) : ?><p style="margin:20px 0 0;font-size:13px;line-height:20px;color:#53647e"><?php echo esc_html( xw_form_email_t( 'Formulario', 'Form' ) . ': ' . $form_name ); ?></p><?php endif; ?>
<?php else : ?>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#f7f8ff;border:1px solid #e8ebfb;border-radius:9px"><tr><td style="padding:22px">
<?php if ( $form_name ) : ?><p style="margin:0 0 16px;font-size:13px;line-height:20px;font-weight:bold;color:#53647e"><?php echo esc_html( $form_name ); ?></p><?php endif; ?>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed">
<?php foreach ( $rows as $row ) : ?><tr><td class="xw-mail-label" width="34%" style="width:34%;padding:0 12px 14px 0;vertical-align:top;font-size:14px;line-height:23px;color:#53647e;word-break:break-word"><?php echo esc_html( $row['label'] ); ?></td><td class="xw-mail-value" width="66%" style="width:66%;padding:0 0 14px;vertical-align:top;font-size:15px;line-height:23px;color:#18264a;word-break:break-word;overflow-wrap:anywhere"><?php echo xw_form_email_display_value( $row ); // Escaped by the value renderer. ?></td></tr><?php endforeach; ?>
</table></td></tr></table>
<?php endif; ?>
<?php if ( $metadata ) : ?>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;margin-top:26px;border-top:1px solid #e7ebf4;table-layout:fixed"><tr><td colspan="2" style="padding:20px 0 14px;font-size:13px;font-weight:bold;color:#53647e"><?php echo esc_html( xw_form_email_t( 'Detalles del envío', 'Submission details' ) ); ?></td></tr>
<?php foreach ( $metadata as $row ) : ?><tr><td class="xw-mail-label" width="34%" style="width:34%;padding:0 12px 10px 0;vertical-align:top;font-size:12px;line-height:20px;color:#53647e;word-break:break-word"><?php echo esc_html( $row['label'] ); ?></td><td class="xw-mail-value" width="66%" style="width:66%;padding:0 0 10px;vertical-align:top;font-size:12px;line-height:20px;color:#53647e;word-break:break-word;overflow-wrap:anywhere"><?php echo xw_form_email_display_value( $row ); ?></td></tr><?php endforeach; ?>
</table>
<?php endif; ?>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;margin-top:26px;border-top:1px solid #e7ebf4"><tr><td style="padding-top:20px;font-size:12px;line-height:20px;color:#53647e"><a href="<?php echo esc_url( $site_url ); ?>" style="color:#3156c8;text-decoration:underline"><?php echo esc_html( $site ); ?></a><br><?php echo esc_html( $customer ? xw_form_email_t( 'Gracias por ponerte en contacto.', 'Thank you for getting in touch.' ) : xw_form_email_t( 'Mensaje enviado desde el formulario de tu sitio.', 'Message sent from your website form.' ) ); ?></td></tr></table>
</td></tr></table>
<!--[if mso]></td></tr></table><![endif]-->
</td></tr></table></body></html>
    <?php
    return trim( ob_get_clean() );
}

function xw_form_email_prepare( $record, $handler = null ) {
    if ( ! is_object( $record ) || ! method_exists( $record, 'set' ) ) { return $record; }
    $settings = $record->get( 'form_settings' );
    if ( ! is_array( $settings ) ) { return $record; }
    $contexts =& xw_form_email_contexts(); $key = spl_object_hash( $record );
    if ( isset( $contexts[ $key ] ) ) { return $record; }
    $actions = (array) ( $settings['submit_actions'] ?? array() ); $entries = array();
    foreach ( array( 'email' => 'admin', 'email2' => 'customer' ) as $action => $kind ) {
        $feature = 'admin' === $kind ? 'elementor_email_admin_style' : 'elementor_email_customer_style';
        $suffix = 'email2' === $action ? '_2' : ''; $content_key = 'email_content' . $suffix;
        $content = $settings[ $content_key ] ?? '';
        if ( ! xw_feature_enabled( $feature ) || ! in_array( $action, $actions, true ) || ! is_scalar( $content ) || ! in_array( trim( (string) $content ), array( '', '[all-fields]' ), true ) || ! empty( $settings['__dynamic__'][ $content_key ] ) || 'plain' === ( $settings[ 'email_content_type' . $suffix ] ?? 'html' ) ) { continue; }
        $original = array( $content_key => array( array_key_exists( $content_key, $settings ), $content ) );
        $marker = '<!--xw-form-email-' . wp_generate_uuid4() . '-->';
        $entries[ $action ] = array( 'marker' => $marker, 'html' => xw_form_email_render( $record, $kind ), 'original' => $original );
        // Only the marker passes through Elementor's shortcode parser; user values never do.
        $settings[ $content_key ] = $marker;
        $subject_key = 'email_subject' . $suffix;
        if ( empty( $settings['__dynamic__'][ $subject_key ] ) && xw_form_email_default_subject( $settings[ $subject_key ] ?? '' ) ) {
            $entries[ $action ]['original'][ $subject_key ] = array( array_key_exists( $subject_key, $settings ), $settings[ $subject_key ] ?? null );
            $settings[ $subject_key ] = ( 'admin' === $kind ? xw_form_email_t( 'Nuevo mensaje recibido', 'New message received' ) : xw_form_email_t( 'Hemos recibido tu mensaje', 'We have received your message' ) ) . ' — ' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
        }
    }
    if ( $entries ) { $contexts[ $key ] = array( 'record' => $record, 'entries' => $entries ); $record->set( 'form_settings', $settings ); }
    return $record;
}

function xw_form_email_restore_entry( &$context, $action ) {
    if ( ! isset( $context['entries'][ $action ] ) ) { return; }
    $settings = $context['record']->get( 'form_settings' );
    foreach ( $context['entries'][ $action ]['original'] as $key => $original ) { if ( $original[0] ) { $settings[ $key ] = $original[1]; } else { unset( $settings[ $key ] ); } }
    $context['record']->set( 'form_settings', $settings ); unset( $context['entries'][ $action ] );
}

function xw_form_email_message( $message ) {
    if ( ! is_string( $message ) ) { return $message; }
    $contexts =& xw_form_email_contexts();
    foreach ( $contexts as $key => &$context ) {
        foreach ( $context['entries'] as $action => $entry ) {
            if ( 0 !== strpos( $message, $entry['marker'] ) ) { continue; }
            $html = $entry['html']; xw_form_email_restore_entry( $context, $action );
            if ( ! $context['entries'] ) { unset( $contexts[ $key ] ); }
            return $html;
        }
    }
    return $message;
}

/** Also restore on an earlier Email-action failure, without masking its original error. */
function xw_form_email_after_action( $action, $exception = null ) {
    if ( ! $exception || ! is_object( $action ) || ! method_exists( $action, 'get_name' ) ) { return; }
    $contexts =& xw_form_email_contexts();
    // A successful action has already restored its own marker. Never consume another record's pending action.
    if ( 1 !== count( $contexts ) ) { return; }
    foreach ( $contexts as $key => &$context ) { xw_form_email_restore_entry( $context, $action->get_name() ); if ( ! $context['entries'] ) { unset( $contexts[ $key ] ); } }
}

function xw_form_email_restore_record( $record ) {
    $contexts =& xw_form_email_contexts(); $key = spl_object_hash( $record );
    if ( ! isset( $contexts[ $key ] ) ) { return; }
    foreach ( array_keys( $contexts[ $key ]['entries'] ) as $action ) { xw_form_email_restore_entry( $contexts[ $key ], $action ); }
    unset( $contexts[ $key ] );
}

function xw_form_email_selected_actions( $actions, $record ) {
    $contexts =& xw_form_email_contexts(); $key = spl_object_hash( $record );
    if ( ! isset( $contexts[ $key ] ) || ! is_array( $actions ) ) { return $actions; }
    foreach ( array_keys( $contexts[ $key ]['entries'] ) as $action ) { if ( ! in_array( $action, $actions, true ) ) { xw_form_email_restore_entry( $contexts[ $key ], $action ); } }
    if ( ! $contexts[ $key ]['entries'] ) { unset( $contexts[ $key ] ); }
    return $actions;
}

add_filter( 'elementor_pro/forms/record/actions_before', 'xw_form_email_prepare', 20, 2 );
add_filter( 'elementor_pro/forms/submit_actions', 'xw_form_email_selected_actions', PHP_INT_MAX, 2 );
add_filter( 'elementor_pro/forms/wp_mail_message', 'xw_form_email_message', 1 );
add_action( 'elementor_pro/forms/actions/after_run', 'xw_form_email_after_action', 20, 2 );
add_action( 'elementor_pro/forms/new_record', 'xw_form_email_restore_record', 1 );
