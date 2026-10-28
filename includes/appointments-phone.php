<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Offline format validation; does not claim that the number has a WhatsApp account. */
function xw_appt_phone_digits_valid( $digits ) {
    static $metadata = null;
    if ( ! is_string( $digits ) || ! preg_match( '/^[1-9]\d{7,14}$/D', $digits ) ) { return false; }
    if ( null === $metadata ) { $path = plugin_dir_path( XW_FUNCTIONS_FILE ) . 'assets/vendor/phone-validation/phone-patterns.json'; $metadata = is_readable( $path ) ? json_decode( file_get_contents( $path ), true ) : array(); }
    for ( $length = 1; $length <= 3; $length++ ) {
        $code = substr( $digits, 0, $length ); $national = substr( $digits, $length );
        foreach ( $metadata['calling_codes'][ $code ] ?? array() as $rule ) {
            if ( in_array( strlen( $national ), $rule[1], true ) && preg_match( '~^(?:' . str_replace( '~', '\\~', $rule[0] ) . ')$~D', $national ) ) { return true; }
        }
    }
    return false;
}
