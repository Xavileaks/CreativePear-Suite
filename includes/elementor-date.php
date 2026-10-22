<?php
/** Numeric editing in front of Elementor's existing native date control. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_enqueue_elementor_date() {
    if ( ! xw_feature_enabled( 'elementor_date_input' ) || ! did_action( 'elementor/loaded' ) ) { return; }
    $base = plugin_dir_path( XW_FUNCTIONS_FILE );
    $url = plugin_dir_url( XW_FUNCTIONS_FILE );
    wp_enqueue_style( 'xw-elementor-date', $url . 'assets/css/elementor-date.css', array(), filemtime( $base . 'assets/css/elementor-date.css' ) );
    wp_enqueue_script( 'xw-elementor-date', $url . 'assets/js/elementor-date.js', array(), filemtime( $base . 'assets/js/elementor-date.js' ), true );
    wp_localize_script( 'xw-elementor-date', 'xwDateSettings', array(
        'placeholder' => xw_t( 'dd/mm/aaaa', 'dd/mm/yyyy' ),
        'invalid' => xw_t( 'Escribe una fecha válida en formato dd/mm/aaaa.', 'Enter a valid date in dd/mm/yyyy format.' ),
        'calendar' => xw_t( 'Calendario', 'Calendar' ),
        'unavailable' => xw_t( 'Esta fecha no está disponible.', 'This date is not available.' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'xw_enqueue_elementor_date', 30 );
