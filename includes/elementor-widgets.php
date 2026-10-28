<?php
/** Shared category for optional suite widgets. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_suite_widgets_enabled() {
    return class_exists( 'WooCommerce' ) && did_action( 'elementor/loaded' ) && ( xw_feature_enabled( 'woocommerce_product_savings' ) || xw_feature_enabled( 'woocommerce_product_price' ) );
}

add_action( 'elementor/elements/categories_registered', static function( $manager ) {
    if ( ! xw_suite_widgets_enabled() || isset( $manager->get_categories()['xw-suite'] ) ) { return; }
    $metadata = get_file_data( XW_FUNCTIONS_FILE, array( 'name' => 'Plugin Name' ) );
    $manager->add_category( 'xw-suite', array( 'title' => $metadata['name'] ?: 'Suite', 'icon' => 'eicon-woocommerce' ) );
}, 20 );

add_action( 'elementor/editor/after_enqueue_scripts', static function() {
    if ( ! xw_suite_widgets_enabled() ) { return; }
    $file = 'assets/elementor-widgets/editor.js';
    wp_enqueue_script( 'xw-suite-widget-order', plugin_dir_url( XW_FUNCTIONS_FILE ) . $file, array( 'elementor-editor' ), filemtime( plugin_dir_path( XW_FUNCTIONS_FILE ) . $file ), true );
} );
