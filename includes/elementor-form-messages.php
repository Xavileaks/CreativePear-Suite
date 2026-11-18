<?php
/** Localize Elementor's standard form notices using the site's language. */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_form_message_language() {
    return substr( strtolower( get_locale() ), 0, 2 );
}

function xw_form_message_text( $spanish, $english ) {
    return 'es' === xw_form_message_language() ? $spanish : $english;
}

function xw_form_default_message_translations() {
    return array(
        'Your submission was successful.'                      => 'Tu mensaje se ha enviado correctamente.',
        'Your submission failed because of an error.'          => 'No se pudo enviar el formulario. Inténtalo de nuevo.',
        'This field is required.'                              => 'Este campo es obligatorio.',
        'Your submission failed because the form is invalid.'  => 'Revisa los campos del formulario antes de enviarlo.',
        'Your submission failed because of a server error.'    => 'No se pudo enviar el formulario por un error del servidor.',
        'Subscriber already exists.'                          => 'La suscripción ya existe.',
    );
}

add_filter( 'gettext_elementor-pro', static function( $translation, $original ) {
    $messages = xw_form_default_message_translations();
    if ( ! isset( $messages[ $original ] ) || ! xw_feature_enabled( 'elementor_messages' ) ) {
        return $translation;
    }
    $language = xw_form_message_language();
    if ( 'es' === $language ) { return $messages[ $original ]; }
    return 'en' === $language ? $original : $translation;
}, 10, 2 );

add_action( 'wp_enqueue_scripts', static function() {
    if ( ! xw_feature_enabled( 'elementor_messages' ) ) { return; }

    $directory = 'assets/floating-messages/';
    $path = plugin_dir_path( XW_FUNCTIONS_FILE ) . $directory;
    $url = plugin_dir_url( XW_FUNCTIONS_FILE ) . $directory;
    $handle = 'xw-floating-messages';
    wp_enqueue_style( $handle, $url . 'floating-messages.css', array(), filemtime( $path . 'floating-messages.css' ) );
    wp_enqueue_script( $handle, $url . 'floating-messages.js', array(), filemtime( $path . 'floating-messages.js' ), true );

    // Some existing forms have saved the default copy as a custom message.
    $messages = xw_form_default_message_translations();
    $language = xw_form_message_language();
    $localized = array( 'es' => $messages, 'en' => array_flip( $messages ) );
    wp_localize_script( $handle, 'xwFloatingMessages', array(
        'closeLabel' => xw_form_message_text( 'Cerrar aviso', 'Close notice' ),
        'defaultMessages' => $localized[ $language ] ?? array(),
    ) );
} );
