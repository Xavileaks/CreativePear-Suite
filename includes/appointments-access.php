<?php
/** Site-local, email-based appointment-only access. No persistent role/capability changes. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_appt_access_emails() {
    $emails = get_option( 'xw_appointment_access_emails', array() );
    return is_array( $emails ) ? array_values( array_unique( array_map( 'strtolower', array_filter( $emails, 'is_string' ) ) ) ) : array();
}
function xw_appt_restricted_user( $user = null ) {
    if ( ! xw_feature_enabled( 'elementor_appointments' ) ) { return false; }
    if ( is_numeric( $user ) ) { $user = get_userdata( (int) $user ); }
    if ( null === $user ) { $user = wp_get_current_user(); }
    return $user instanceof WP_User && $user->exists() && in_array( strtolower( $user->user_email ), xw_appt_access_emails(), true );
}
function xw_appt_can_manage() { return xw_feature_enabled( 'elementor_appointments' ) && current_user_can( 'xw_manage_appointments' ); }
function xw_appt_can_manage_access() { return ! xw_appt_restricted_user() && current_user_can( 'manage_options' ); }
function xw_appt_admin_url() { return admin_url( 'admin.php?page=xw-appointments' ); }
function xw_appt_access_revision() { return hash( 'sha256', wp_json_encode( xw_appt_access_emails() ) ); }

add_filter( 'map_meta_cap', static function( $caps, $cap, $user_id ) {
    $restricted = xw_appt_restricted_user( $user_id );
    if ( 'xw_manage_appointments' === $cap ) { return $restricted ? array( 'exist' ) : array( 'manage_options' ); }
    if ( $restricted ) { return in_array( $cap, array( 'read', 'exist' ), true ) ? array( 'exist' ) : array( 'do_not_allow' ); }
    return $caps;
}, PHP_INT_MAX, 3 );

/** Pure request policy, shared by the early gate and security regression tests. */
function xw_appt_restricted_route_allowed( $script, $method, $get, $post ) {
    if ( 'admin-ajax.php' === $script ) {
        $action = 'POST' === $method ? ( $post['action'] ?? '' ) : ( $get['action'] ?? '' );
        return ( 'POST' === $method && 'xw_appt_admin_save' === $action ) ||
            ( 'GET' === $method && in_array( $action, array( 'xw_appt_admin_availability', 'xw_appt_availability' ), true ) );
    }
    return 'GET' === $method && 'admin.php' === $script && 'xw-appointments' === ( $get['page'] ?? '' ) && empty( $get['action'] );
}
function xw_appt_access_denied() {
    if ( wp_doing_ajax() || wp_is_json_request() ) { wp_send_json_error( array( 'message' => xw_t( 'Esta cuenta solo puede gestionar Citas.', 'This account can only manage Appointments.' ) ), 403 ); }
    wp_die( esc_html( xw_t( 'Esta cuenta solo puede gestionar Citas.', 'This account can only manage Appointments.' ) ), '', array( 'response' => 403 ) );
}
function xw_appt_restrict_request() {
    if ( ! xw_appt_restricted_user() ) { return; }
    global $pagenow;
    // Authentication/logout remain native and nonce-protected. API authentication is denied separately.
    if ( 'wp-login.php' === $pagenow ) { return; }
    if ( is_admin() || wp_doing_ajax() ) {
        if ( ! is_network_admin() && ! is_user_admin() && xw_appt_restricted_route_allowed( $pagenow, $_SERVER['REQUEST_METHOD'] ?? 'GET', $_GET, $_POST ) ) { return; }
        if ( wp_doing_ajax() || ! in_array( $_SERVER['REQUEST_METHOD'] ?? 'GET', array( 'GET', 'HEAD' ), true ) ) { xw_appt_access_denied(); }
        wp_safe_redirect( xw_appt_admin_url(), 303 ); exit;
    }
    $path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
    if ( isset( $_GET['rest_route'] ) || ( is_string( $path ) && preg_match( '#/' . preg_quote( rest_get_url_prefix(), '#' ) . '(?:/|$)#', $path ) ) ) { xw_appt_access_denied(); }
    if ( ! in_array( $_SERVER['REQUEST_METHOD'] ?? 'GET', array( 'GET', 'HEAD' ), true ) || isset( $_GET['wc-ajax'] ) ) { xw_appt_access_denied(); }
    wp_safe_redirect( xw_appt_admin_url(), 303 ); exit;
}
// Before WooCommerce/account form handlers, plugin admin actions, and ordinary page rendering.
add_action( 'init', 'xw_appt_restrict_request', -10000 );
add_action( 'admin_init', 'xw_appt_restrict_request', -10000 );
add_filter( 'login_redirect', static function( $url, $requested, $user ) { return xw_appt_restricted_user( $user ) ? xw_appt_admin_url() : $url; }, PHP_INT_MAX, 3 );
add_filter( 'woocommerce_login_redirect', static function( $url, $user ) { return xw_appt_restricted_user( $user ) ? xw_appt_admin_url() : $url; }, PHP_INT_MAX, 2 );
add_filter( 'woocommerce_prevent_admin_access', static function( $prevent ) { return xw_appt_restricted_user() ? false : $prevent; }, PHP_INT_MAX );
add_filter( 'show_admin_bar', static function( $show ) { return xw_appt_restricted_user() ? false : $show; }, PHP_INT_MAX );
add_filter( 'admin_body_class', static function( $classes ) { return $classes . ( xw_appt_restricted_user() ? ' xw-appt-restricted' : '' ); } );
add_filter( 'rest_authentication_errors', static function( $result ) { return xw_appt_restricted_user() ? new WP_Error( 'xw_appointments_only', xw_t( 'Esta cuenta solo puede gestionar Citas.', 'This account can only manage Appointments.' ), array( 'status' => 403 ) ) : $result; }, -10000 );
add_filter( 'rest_authentication_errors', static function( $result ) { return xw_appt_restricted_user() ? new WP_Error( 'xw_appointments_only', xw_t( 'Esta cuenta solo puede gestionar Citas.', 'This account can only manage Appointments.' ), array( 'status' => 403 ) ) : $result; }, PHP_INT_MAX );
add_filter( 'authenticate', static function( $user ) { return defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST && xw_appt_restricted_user( $user ) ? new WP_Error( 'xw_appointments_only', 'Appointments-only account.' ) : $user; }, PHP_INT_MAX );
add_filter( 'wp_is_application_passwords_available_for_user', static function( $available, $user ) { return xw_appt_restricted_user( $user ) ? false : $available; }, PHP_INT_MAX, 2 );
add_action( 'admin_enqueue_scripts', static function() { if ( xw_appt_restricted_user() ) { wp_deregister_script( 'heartbeat' ); wp_dequeue_script( 'wp-auth-check' ); } }, PHP_INT_MAX );
add_action( 'admin_head', static function() { if ( xw_appt_restricted_user() ) { echo '<style>html.wp-toolbar{padding-top:0!important}</style>'; } } );

function xw_appt_parse_access_emails( $text ) {
    if ( ! is_string( $text ) || strlen( $text ) > 20000 ) { return new WP_Error( 'emails', xw_t( 'Lista de correos no válida.', 'Invalid email list.' ) ); }
    $out = array();
    foreach ( preg_split( '/[\r\n,;]+/', $text ) as $email ) {
        $email = strtolower( trim( $email ) ); if ( '' === $email ) { continue; }
        if ( ! is_email( $email ) ) { return new WP_Error( 'email', xw_t( 'Revisa este correo: ', 'Check this email: ' ) . sanitize_text_field( $email ) ); }
        $out[] = $email;
    }
    $out = array_values( array_unique( $out ) );
    if ( count( $out ) > 100 ) { return new WP_Error( 'emails', xw_t( 'Puedes indicar hasta 100 correos.', 'You can list up to 100 emails.' ) ); }
    return $out;
}
function xw_appt_access_has_owner( $emails ) {
    foreach ( get_users( array( 'capability' => 'manage_options', 'fields' => 'all' ) ) as $user ) {
        if ( ! in_array( strtolower( $user->user_email ), $emails, true ) && ( ! empty( $user->allcaps['manage_options'] ) || is_super_admin( $user->ID ) ) ) { return true; }
    }
    return false;
}
function xw_appt_access_save() {
    if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! xw_appt_can_manage_access() || ! xw_feature_enabled( 'elementor_appointments' ) || ! check_ajax_referer( 'xw_appointment_access', 'nonce', false ) ) { wp_send_json_error( array( 'message' => xw_t( 'No autorizado o sesión caducada.', 'Not authorized or session expired.' ) ), 403 ); }
    $emails = xw_appt_parse_access_emails( isset( $_POST['emails'] ) && is_string( $_POST['emails'] ) ? wp_unslash( $_POST['emails'] ) : null );
    if ( is_wp_error( $emails ) ) { wp_send_json_error( array( 'message' => $emails->get_error_message() ), 400 ); }
    if ( ! xw_appt_access_has_owner( $emails ) ) { wp_send_json_error( array( 'message' => xw_t( 'Mantén al menos un administrador fuera de esta lista para gestionar los accesos.', 'Keep at least one administrator outside this list to manage access.' ) ), 400 ); }
    $owner = xw_appt_lock(); if ( ! $owner ) { wp_send_json_error( array( 'message' => xw_t( 'Inténtalo de nuevo en unos segundos.', 'Try again in a few seconds.' ) ), 409 ); }
    try {
        if ( ! is_string( $_POST['revision'] ?? null ) || ! hash_equals( xw_appt_access_revision(), $_POST['revision'] ) ) { $result = new WP_Error( 'stale', xw_t( 'La lista cambió en otra pestaña. Recarga antes de guardar.', 'The list changed in another tab. Reload before saving.' ) ); }
        else { update_option( 'xw_appointment_access_emails', $emails, false ); $result = xw_appt_access_emails() === $emails; }
    } finally { xw_appt_unlock( $owner ); }
    if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), 409 ); }
    if ( ! $result ) { wp_send_json_error( array( 'message' => xw_t( 'No se pudo guardar la lista.', 'Could not save the list.' ) ), 500 ); }
    wp_send_json_success( array( 'revision' => xw_appt_access_revision(), 'redirect' => xw_appt_restricted_user() ? xw_appt_admin_url() : '' ) );
}
add_action( 'wp_ajax_xw_appt_access_save', 'xw_appt_access_save' );

function xw_appt_access_panel() {
    if ( ! xw_appt_can_manage_access() ) { return; } ?>
    <section class="xw-appt-panel xw-appt-access-panel"><h2><?php echo esc_html( xw_t( 'Usuarios con acceso solo a Citas', 'Appointments-only users' ) ); ?></h2>
        <p><?php echo esc_html( xw_t( 'Los correos de esta lista solo podrán gestionar la agenda, sin acceso al resto de WordPress, aunque su cuenta sea administradora.', 'Emails on this list can only manage the calendar, with no access to the rest of WordPress, even if their account is an administrator.' ) ); ?></p>
        <label for="xw-appt-access-emails"><?php echo esc_html( xw_t( 'Correos de las cuentas', 'Account emails' ) ); ?></label><textarea form="xw-appt-access" id="xw-appt-access-emails" name="emails" rows="5" aria-describedby="xw-appt-access-help"><?php echo esc_textarea( implode( "\n", xw_appt_access_emails() ) ); ?></textarea><p class="description" id="xw-appt-access-help"><?php echo esc_html( xw_t( 'Un correo por línea. Se aplica al correo registrado en la cuenta; no crea usuarios ni envía invitaciones. Mantén al menos un administrador fuera de la lista. Solo un administrador sin restricción puede editarla.', 'One email per line. Uses the account’s registered email; does not create users or send invitations. Keep at least one administrator outside the list. Only an unrestricted administrator can edit it.' ) ); ?></p><button form="xw-appt-access" class="button button-primary" type="submit" disabled><?php echo esc_html( xw_t( 'Guardar acceso', 'Save access' ) ); ?></button><p id="xw-appt-access-status" role="status" aria-live="polite"></p>
    </section><?php
}
