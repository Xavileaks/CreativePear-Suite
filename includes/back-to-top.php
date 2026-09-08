<?php
/**
 * Botón de regreso al inicio con indicador de progreso.
 *
 * Los iconos SVG incluidos en este archivo proceden de Lucide Icons.
 * Consulte assets/vendor/lucide/LICENSE para conocer sus licencias.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Devuelve los iconos que puede seleccionar el administrador.
 *
 * @return array<string,string>
 */
function xw_get_back_to_top_icon_options() {
    return array(
        'arrow-up'       => xw_t( 'Flecha', 'Arrow' ),
        'chevron-up'     => xw_t( 'Ángulo', 'Chevron' ),
        'chevrons-up'    => xw_t( 'Ángulo doble', 'Double chevron' ),
        'arrow-big-up'   => xw_t( 'Flecha ancha', 'Wide arrow' ),
        'circle-arrow-up' => xw_t( 'Flecha circular', 'Circle arrow' ),
        'move-up'        => xw_t( 'Flecha larga', 'Long arrow' ),
    );
}

/**
 * Renderiza únicamente SVG conocidos de Lucide.
 *
 * @param string $icon Identificador del icono.
 * @return string
 */
function xw_back_to_top_icon_svg( $icon ) {
    $paths = array(
        'arrow-up' => '<path d="m5 12 7-7 7 7"></path><path d="M12 19V5"></path>',
        'chevron-up' => '<path d="m18 15-6-6-6 6"></path>',
        'chevrons-up' => '<path d="m17 11-5-5-5 5"></path><path d="m17 18-5-5-5 5"></path>',
        'arrow-big-up' => '<path d="M9 19a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-6a1 1 0 0 1 1-1h3.293a.707.707 0 0 0 .5-1.207l-7.086-7.086a1 1 0 0 0-1.414 0l-7.086 7.086a.707.707 0 0 0 .5 1.207H8a1 1 0 0 1 1 1z"></path>',
        'circle-arrow-up' => '<circle cx="12" cy="12" r="10"></circle><path d="m16 12-4-4-4 4"></path><path d="M12 16V8"></path>',
        'move-up' => '<path d="M8 6 12 2l4 4"></path><path d="M12 2v20"></path>',
    );

    if ( ! isset( $paths[ $icon ] ) ) {
        $icon = 'arrow-up';
    }

    return '<svg class="xw-back-to-top__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . $paths[ $icon ]
        . '</svg>';
}

/**
 * Renderiza un trazo SVG circular con grosor uniforme para el progreso.
 *
 * @param int $button_size   Diámetro del botón en píxeles.
 * @param int $progress_size Grosor del indicador en píxeles.
 * @return string
 */
function xw_back_to_top_progress_svg( $button_size, $progress_size ) {
    $center = $button_size / 2;
    $radius = ( $button_size - $progress_size ) / 2;

    return sprintf(
        '<svg class="xw-back-to-top__progress" viewBox="0 0 %1$d %1$d" aria-hidden="true" focusable="false"><circle class="xw-back-to-top__progress-track" cx="%2$s" cy="%2$s" r="%3$s" pathLength="100"></circle><circle class="xw-back-to-top__progress-value" cx="%2$s" cy="%2$s" r="%3$s" pathLength="100"></circle></svg>',
        $button_size,
        esc_attr( number_format( $center, 2, '.', '' ) ),
        esc_attr( number_format( $radius, 2, '.', '' ) )
    );
}

add_action( 'wp_enqueue_scripts', 'xw_enqueue_back_to_top_assets', 30 );
/**
 * Carga los recursos exclusivamente en el sitio público.
 *
 * @return void
 */
function xw_enqueue_back_to_top_assets() {
    if ( is_admin() || ! xw_feature_enabled( 'back_to_top' ) ) {
        return;
    }

    $settings = xw_get_settings();
    $options  = $settings['back_to_top'];
    $css_path = plugin_dir_path( XW_FUNCTIONS_FILE ) . 'assets/css/back-to-top.css';
    $js_path  = plugin_dir_path( XW_FUNCTIONS_FILE ) . 'assets/js/back-to-top.js';

    wp_enqueue_style(
        'xw-back-to-top',
        plugin_dir_url( XW_FUNCTIONS_FILE ) . 'assets/css/back-to-top.css',
        array(),
        filemtime( $css_path )
    );
    wp_enqueue_script(
        'xw-back-to-top',
        plugin_dir_url( XW_FUNCTIONS_FILE ) . 'assets/js/back-to-top.js',
        array(),
        filemtime( $js_path ),
        true
    );
    wp_localize_script(
        'xw-back-to-top',
        'xwBackToTop',
        array(
            'offset'   => absint( $options['offset'] ),
            'duration' => absint( $options['duration'] ),
        )
    );
}

// Se imprime antes de los scripts del pie (prioridad 20 de WordPress).
add_action( 'wp_footer', 'xw_render_back_to_top_button', 10 );
/**
 * Imprime el control accesible al final del documento público.
 *
 * @return void
 */
function xw_render_back_to_top_button() {
    if ( is_admin() || ! xw_feature_enabled( 'back_to_top' ) ) {
        return;
    }

    $settings = xw_get_settings();
    $options  = $settings['back_to_top'];
    $icons    = xw_get_back_to_top_icon_options();
    $icon     = isset( $icons[ $options['icon'] ] ) ? $options['icon'] : 'arrow-up';
    $position = in_array( $options['position'], array( 'bottom-right', 'bottom-left' ), true ) ? $options['position'] : 'bottom-right';
    $shape    = in_array( $options['shape'], array( 'circle', 'rounded', 'square' ), true ) ? $options['shape'] : 'circle';
    $classes  = array(
        'xw-back-to-top',
        'xw-back-to-top--' . $position,
        'xw-back-to-top--' . $shape,
    );

    foreach ( array( 'mobile', 'tablet', 'desktop' ) as $device ) {
        if ( ! empty( $options[ 'hide_' . $device ] ) ) {
            $classes[] = 'xw-back-to-top--hide-' . $device;
        }
    }

    $background = sanitize_hex_color( $options['background_color'] ) ?: '#FFFFFF';
    $border     = sanitize_hex_color( $options['border_color'] ) ?: '#DCDCDE';
    $icon_color = sanitize_hex_color( $options['icon_color'] ) ?: '#1D2327';
    $progress   = sanitize_hex_color( $options['progress_color'] ) ?: '#3858E9';
    $button_size = max( 32, min( 100, absint( $options['button_size'] ) ) );
    $icon_size   = max( 12, min( $button_size - 8, absint( $options['icon_size'] ) ) );
    $progress_size = max( 1, min( 10, absint( $options['progress_size'] ) ) );
    $style      = sprintf(
        '--xw-btt-size:%dpx;--xw-btt-border-size:%dpx;--xw-btt-icon-size:%dpx;--xw-btt-progress-size:%dpx;--xw-btt-progress-value:0;--xw-btt-vertical-margin:%dpx;--xw-btt-horizontal-margin:%dpx;--xw-btt-background:%s;--xw-btt-border:%s;--xw-btt-icon:%s;--xw-btt-progress-color:%s;',
        $button_size,
        max( 0, min( 10, absint( $options['border_size'] ) ) ),
        $icon_size,
        $progress_size,
        max( 0, min( 200, absint( $options['vertical_margin'] ) ) ),
        max( 0, min( 200, absint( $options['horizontal_margin'] ) ) ),
        $background,
        $border,
        $icon_color,
        $progress
    );
    $label = xw_t( 'Volver arriba', 'Back to top' );
    ?>
    <button
        id="xw-back-to-top"
        class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
        type="button"
        style="<?php echo esc_attr( $style ); ?>"
        aria-label="<?php echo esc_attr( $label ); ?>"
        title="<?php echo esc_attr( $label ); ?>"
        aria-hidden="true"
        tabindex="-1"
    >
        <?php echo xw_back_to_top_progress_svg( $button_size, $progress_size ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fijo con valores numéricos saneados. ?>
        <?php echo xw_back_to_top_icon_svg( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fijo de la lista permitida. ?>
    </button>
    <?php
}
