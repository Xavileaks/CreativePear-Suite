<?php
/** Styles controlled by the Global CSS styles card. */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_global_style_files() {
    static $files;
    if ( isset( $files ) ) { return $files; }

    $directory = plugin_dir_path( XW_FUNCTIONS_FILE ) . 'assets/css/';
    // Styles owned by other features and old standalone color/scrollbar files.
    $excluded = array(
        'admin-settings.css', 'admin-save-bar.css', 'appointments.css', 'appointments-admin.css',
        'back-to-top.css', 'elementor-date.css', 'variation-swatches.css', 'variation-swatches-admin.css',
        'whatsapp-button.css', 'woocommerce-cart.css', 'woocommerce-checkout.css',
        'elementor extra.css',
    );
    $files = array();
    foreach ( glob( $directory . '*.css' ) ?: array() as $file ) {
        if ( in_array( basename( $file ), $excluded, true ) ) { continue; }
        $key = sanitize_key( str_replace( ' ', '-', basename( $file, '.css' ) ) );
        $files[ $key ] = basename( $file );
    }
    foreach ( glob( $directory . 'global/*.css' ) ?: array() as $rule ) {
        if ( 'scrollbar.css' === basename( $rule ) ) { continue; }
        $key = sanitize_key( str_replace( ' ', '-', basename( $rule, '.css' ) ) );
        $files[ 'global-' . $key ] = 'global/' . basename( $rule );
    }
    return $files;
}

function xw_global_style_labels() {
    return array(
        'global-paragraph-last-margin' => xw_t( 'Quitar margen del último párrafo', 'Remove last paragraph margin' ),
        'global-elementor-icon'        => xw_t( 'Ajustar altura de iconos', 'Adjust icon line height' ),
        'global-overflow'              => xw_t( 'Limitar desborde horizontal', 'Clip horizontal overflow' ),
        'global-link-decoration'       => xw_t( 'Quitar subrayado de enlaces', 'Remove link underlines' ),
        'global-button-focus'          => xw_t( 'Quitar contorno de botones', 'Remove button focus outline' ),
        'global-focus-visible'         => xw_t( 'Quitar contorno de foco', 'Remove visible focus outline' ),
        'global-form-focus'            => xw_t( 'Quitar sombra de foco del formulario', 'Remove form focus shadow' ),
        'global-tap-highlight'         => xw_t( 'Quitar resaltado táctil', 'Remove tap highlight' ),
        'global-list-spacing'          => xw_t( 'Margen de listas', 'List margin' ),
        'global-text-balance'          => xw_t( 'Equilibrio del texto', 'Text balance' ),
        'global-checkbox-color'        => xw_t( 'Color de casillas', 'Checkbox color' ),
        'global-radio-color'           => xw_t( 'Color de botones de opción', 'Radio button color' ),
        'global-selection-color'       => xw_t( 'Color de selección', 'Selection color' ),
        'custom'                       => xw_t( 'CSS personalizado', 'Custom CSS' ),
    );
}

function xw_global_optional_pixels( $value, $max ) {
    return is_numeric( $value ) ? max( 0, min( $max, (int) $value ) ) : '';
}

function xleon_suite_enqueue_styles() {
    $directory = 'assets/css/';
    $path = plugin_dir_path( XW_FUNCTIONS_FILE ) . $directory;
    $url = plugin_dir_url( XW_FUNCTIONS_FILE ) . $directory;
    $styles = xw_get_settings()['styles'];
    $primary = sanitize_hex_color( $styles['primary_color'] );
    $color_rules = array( 'global-checkbox-color', 'global-radio-color', 'global-selection-color' );

    foreach ( xw_global_style_files() as $key => $relative ) {
        if ( empty( $styles['rules'][ $key ] ) ) { continue; }
        if ( ! $primary && in_array( $key, $color_rules, true ) ) { continue; }

        $handle = 'xleon-' . $key;
        $encoded = implode( '/', array_map( 'rawurlencode', explode( '/', $relative ) ) );
        wp_enqueue_style( $handle, $url . $encoded, array(), filemtime( $path . $relative ) );
    }

    if ( $primary ) {
        wp_register_style( 'xleon-global-color', false );
        wp_enqueue_style( 'xleon-global-color' );
        wp_add_inline_style( 'xleon-global-color', ':root{--color-web:' . $primary . ';}' );
    }

    $track = sanitize_hex_color( $styles['scrollbar_track_color'] );
    $thumb = sanitize_hex_color( $styles['scrollbar_thumb_color'] );
    $width = xw_global_optional_pixels( $styles['scrollbar_width'], 40 );
    $radius = xw_global_optional_pixels( $styles['scrollbar_radius'], 50 );
    $corners = '' === $radius ? '' : 'border-radius:' . $radius . 'px;';
    $track_css = $corners . ( $track ? 'background:' . $track . ';' : '' );
    $thumb_css = $corners . ( $thumb ? 'background:' . $thumb . ';' : '' );
    $css = '' === $width ? '' : '::-webkit-scrollbar{width:' . $width . 'px;height:' . $width . 'px;}';
    if ( $track_css ) { $css .= '::-webkit-scrollbar-track{' . $track_css . '}'; }
    if ( $thumb_css ) { $css .= '::-webkit-scrollbar-thumb{' . $thumb_css . '}'; }
    if ( $css ) {
        wp_register_style( 'xleon-global-scrollbar', false );
        wp_enqueue_style( 'xleon-global-scrollbar' );
        wp_add_inline_style( 'xleon-global-scrollbar', $css );
    }
}
