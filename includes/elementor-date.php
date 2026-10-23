<?php
/** Numeric date editing, persistent format guide and calendar access for Elementor Date fields. */
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
        'day' => xw_t( 'El día debe estar entre 01 y 31.', 'The day must be between 01 and 31.' ),
        'month' => xw_t( 'El mes debe estar entre 01 y 12.', 'The month must be between 01 and 12.' ),
        'year' => xw_t( 'El año debe estar entre 1000 y 3999: empieza por 1, 2 o 3.', 'The year must be between 1000 and 3999: start with 1, 2 or 3.' ),
        'calendar' => xw_t( 'Calendario', 'Calendar' ),
        'unavailable' => xw_t( 'Esta fecha no está disponible.', 'This date is not available.' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'xw_enqueue_elementor_date', 30 );
