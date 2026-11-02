<?php
/** Optional message policy, enforced before Elementor runs its submission actions. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_message_validation_error( $value ) {
    if ( ! is_string( $value ) ) { return 'characters'; }
    // Accept Spanish accents entered as composed characters or combining sequences.
    $compose = array( "a\u{0301}" => 'á', "e\u{0301}" => 'é', "i\u{0301}" => 'í', "o\u{0301}" => 'ó', "u\u{0301}" => 'ú', "A\u{0301}" => 'Á', "E\u{0301}" => 'É', "I\u{0301}" => 'Í', "O\u{0301}" => 'Ó', "U\u{0301}" => 'Ú', "n\u{0303}" => 'ñ', "N\u{0303}" => 'Ñ', "u\u{0308}" => 'ü', "U\u{0308}" => 'Ü' );
    $value = strtr( $value, $compose );
    $allowed = '~\A[A-Za-z0-9áéíóúüñÁÉÍÓÚÜÑ \t\r\n.,;:!?¿¡\x{0027}"’“”«»()\[\]\-–—+%&€$#@/\\\\=*_]*\z~u';
    if ( 1 !== preg_match( $allowed, $value ) ) { return 'characters'; }
    $candidate = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    for ( $i = 0; $i < 2; ++$i ) { $candidate = rawurldecode( $candidate ); }
    if ( 1 !== preg_match( $allowed, strtr( $candidate, $compose ) ) ) { return 'characters'; }
    $pattern = '~(?:\b(?:https?|hxxps?|ftp)\s*:|\bwww\s*\.|\b(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9-]{2,59})\b|\b(?:\d{1,3}\.){3}\d{1,3}\b|\[\s*(?:url|link)\b)~i';
    return preg_match( $pattern, $candidate ) ? 'link' : '';
}

function xw_message_validation_text( $code ) {
    return 'link' === $code
        ? xw_t( 'El mensaje no puede contener enlaces. Elimínalos para continuar.', 'The message cannot contain links. Remove them to continue.' )
        : xw_t( 'Usa solo letras del español o del inglés, números y signos de puntuación habituales.', 'Use only Spanish or English letters, numbers and common punctuation.' );
}

add_action( 'elementor_pro/forms/validation/textarea', static function( $field, $record, $handler ) {
    if ( ! xw_feature_enabled( 'elementor_message_mask' ) ) { return; }
    $code = xw_message_validation_error( $field['raw_value'] ?? $field['value'] ?? '' );
    if ( $code ) { $handler->add_error( $field['id'], xw_message_validation_text( $code ) ); }
}, 10, 3 );

add_action( 'wp_enqueue_scripts', static function() {
    if ( ! xw_feature_enabled( 'elementor_message_mask' ) ) { return; }
    $base = plugin_dir_path( XW_FUNCTIONS_FILE ); $url = plugin_dir_url( XW_FUNCTIONS_FILE );
    wp_enqueue_script( 'xw-message-validation', $url . 'assets/message-validation/message-validation.js', array(), filemtime( $base . 'assets/message-validation/message-validation.js' ), true );
    wp_enqueue_style( 'xw-message-validation', $url . 'assets/message-validation/message-validation.css', array(), filemtime( $base . 'assets/message-validation/message-validation.css' ) );
    wp_localize_script( 'xw-message-validation', 'xwMessageValidation', array( 'characters' => xw_message_validation_text( 'characters' ), 'link' => xw_message_validation_text( 'link' ) ) );
} );
