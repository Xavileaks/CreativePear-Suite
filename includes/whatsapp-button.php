<?php
/**
 * Botón flotante configurable para abrir una conversación de WhatsApp.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Devuelve el número configurado usando únicamente dígitos internacionales.
 *
 * @return string
 */
function xw_get_whatsapp_number() {
    $settings = xw_get_settings();
    $number   = isset( $settings['whatsapp_button']['phone_number'] )
        ? preg_replace( '/\D+/', '', (string) $settings['whatsapp_button']['phone_number'] )
        : '';

    return substr( $number, 0, 15 );
}

add_action( 'wp_enqueue_scripts', 'xw_enqueue_whatsapp_button_assets', 31 );
function xw_enqueue_whatsapp_button_assets() {
    if ( is_admin() || ! xw_feature_enabled( 'whatsapp_button' ) || strlen( xw_get_whatsapp_number() ) < 6 ) {
        return;
    }

    $settings = xw_get_settings();
    $options  = $settings['whatsapp_button'];
    $css_path = plugin_dir_path( XW_FUNCTIONS_FILE ) . 'assets/css/whatsapp-button.css';
    $js_path  = plugin_dir_path( XW_FUNCTIONS_FILE ) . 'assets/js/whatsapp-button.js';

    wp_enqueue_style(
        'xw-whatsapp-button',
        plugin_dir_url( XW_FUNCTIONS_FILE ) . 'assets/css/whatsapp-button.css',
        array(),
        filemtime( $css_path )
    );
    wp_enqueue_script(
        'xw-whatsapp-button',
        plugin_dir_url( XW_FUNCTIONS_FILE ) . 'assets/js/whatsapp-button.js',
        array(),
        filemtime( $js_path ),
        true
    );
    wp_localize_script(
        'xw-whatsapp-button',
        'xwWhatsAppButton',
        array(
            'offset' => absint( $options['offset'] ),
        )
    );
}

add_action( 'wp_footer', 'xw_render_whatsapp_button', 11 );
function xw_render_whatsapp_button() {
    if ( is_admin() || ! xw_feature_enabled( 'whatsapp_button' ) ) {
        return;
    }

    $number = xw_get_whatsapp_number();

    if ( strlen( $number ) < 6 ) {
        return;
    }

    $settings = xw_get_settings();
    $options  = $settings['whatsapp_button'];
    $position = in_array( $options['position'], array( 'bottom-right', 'bottom-left' ), true ) ? $options['position'] : 'bottom-right';
    $shape    = in_array( $options['shape'], array( 'circle', 'rounded', 'square' ), true ) ? $options['shape'] : 'circle';
    $classes  = array(
        'xw-whatsapp-button',
        'xw-whatsapp-button--' . $position,
        'xw-whatsapp-button--' . $shape,
    );

    foreach ( array( 'mobile', 'tablet', 'desktop' ) as $device ) {
        if ( ! empty( $options[ 'hide_' . $device ] ) ) {
            $classes[] = 'xw-whatsapp-button--hide-' . $device;
        }
    }

    $button_size = max( 32, min( 100, absint( $options['button_size'] ) ) );
    $icon_size   = max( 12, min( $button_size - 8, absint( $options['icon_size'] ) ) );
    $background  = sanitize_hex_color( $options['background_color'] ) ?: '#25D366';
    $border      = sanitize_hex_color( $options['border_color'] ) ?: '#25D366';
    $icon_color  = sanitize_hex_color( $options['icon_color'] ) ?: '#FFFFFF';
    $style       = sprintf(
        '--xw-wa-size:%dpx;--xw-wa-border-size:%dpx;--xw-wa-icon-size:%dpx;--xw-wa-vertical-margin:%dpx;--xw-wa-horizontal-margin:%dpx;--xw-wa-duration:%dms;--xw-wa-background:%s;--xw-wa-border:%s;--xw-wa-icon:%s;',
        $button_size,
        max( 0, min( 10, absint( $options['border_size'] ) ) ),
        $icon_size,
        max( 0, min( 200, absint( $options['vertical_margin'] ) ) ),
        max( 0, min( 200, absint( $options['horizontal_margin'] ) ) ),
        max( 0, min( 3000, absint( $options['duration'] ) ) ),
        $background,
        $border,
        $icon_color
    );
    $label = xw_t( 'Abrir conversación en WhatsApp', 'Open WhatsApp conversation' );
    ?>
    <a
        id="xw-whatsapp-button"
        class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
        href="<?php echo esc_url( 'https://wa.me/' . $number ); ?>"
        target="_blank"
        rel="noopener noreferrer"
        style="<?php echo esc_attr( $style ); ?>"
        aria-label="<?php echo esc_attr( $label ); ?>"
        title="<?php echo esc_attr( $label ); ?>"
        aria-hidden="true"
        tabindex="-1"
    >
        <svg class="xw-whatsapp-button__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.5-.669-.51-.173-.009-.371-.011-.57-.011-.198 0-.52.074-.792.372-.273.297-1.04 1.016-1.04 2.479s1.065 2.875 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.262.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.981.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.002-5.45 4.436-9.884 9.892-9.884 2.641 0 5.122 1.029 6.988 2.896a9.825 9.825 0 0 1 2.893 7.003c-.002 5.45-4.436 9.878-9.889 9.878m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"></path>
        </svg>
    </a>
    <?php
}
