<?php
/** Optional relocation of the native WooCommerce Orders admin entry. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_orders_menu_enabled() {
    return class_exists( 'WooCommerce' ) && xw_feature_enabled( 'woocommerce_orders_menu' );
}

function xw_orders_menu_move() {
    global $menu, $submenu, $xw_orders_menu_target, $xw_orders_menu_source;
    $xw_orders_menu_target = '';
    $xw_orders_menu_source = '';
    if ( ! xw_orders_menu_enabled() || empty( $submenu['woocommerce'] ) ) { return; }

    $wc_position = null;
    foreach ( (array) $menu as $position => $item ) {
        if ( 'woocommerce' === $item[2] ) { $wc_position = (float) $position; break; }
    }
    if ( null === $wc_position ) { return; }

    foreach ( $submenu['woocommerce'] as $item ) {
        // Exact native routes only: do not relocate custom order types/extensions.
        if ( ! in_array( $item[2], array( 'wc-orders', 'edit.php?post_type=shop_order' ), true ) ) { continue; }
        if ( ! current_user_can( $item[1] ) ) { return; }
        $target = 'wc-orders' === $item[2] ? 'admin.php?page=wc-orders' : $item[2];
        foreach ( (array) $menu as $top ) {
            if ( in_array( $top[2], array( $target, $item[2] ), true ) ) { return; }
        }

        // Keep the native registration/callback and the count markup WooCommerce already produced.
        add_menu_page( isset( $item[3] ) ? $item[3] : wp_strip_all_tags( $item[0] ), $item[0], $item[1], $target, '', 'dashicons-clipboard', $wc_position + 0.01 );
        // Core resolves HPOS page authorization and load hooks through this submenu.
        // Remove it only when rendering the header, after those checks have run.
        $xw_orders_menu_source = $item[2];
        $xw_orders_menu_target = $target;
        return;
    }
}
add_action( 'admin_menu', 'xw_orders_menu_move', PHP_INT_MAX );

function xw_orders_menu_hide_submenu() {
    global $xw_orders_menu_target, $xw_orders_menu_source;
    if ( xw_orders_menu_enabled() && ! empty( $xw_orders_menu_target ) && ! empty( $xw_orders_menu_source ) ) {
        remove_submenu_page( 'woocommerce', $xw_orders_menu_source );
    }
}
add_action( 'admin_head', 'xw_orders_menu_hide_submenu', PHP_INT_MAX );

function xw_orders_menu_custom_order( $enabled ) {
    global $xw_orders_menu_target;
    return $enabled || ( xw_orders_menu_enabled() && ! empty( $xw_orders_menu_target ) );
}
add_filter( 'custom_menu_order', 'xw_orders_menu_custom_order', PHP_INT_MAX );

function xw_orders_menu_order( $order ) {
    global $xw_orders_menu_target;
    if ( ! xw_orders_menu_enabled() || empty( $xw_orders_menu_target ) || ! is_array( $order ) || ! in_array( 'woocommerce', $order, true ) || ! in_array( $xw_orders_menu_target, $order, true ) ) { return $order; }
    $order = array_values( array_diff( $order, array( $xw_orders_menu_target ) ) );
    array_splice( $order, array_search( 'woocommerce', $order, true ) + 1, 0, array( $xw_orders_menu_target ) );
    return $order;
}
add_filter( 'menu_order', 'xw_orders_menu_order', PHP_INT_MAX );

function xw_orders_menu_parent( $parent ) {
    global $xw_orders_menu_target;
    if ( ! xw_orders_menu_enabled() || empty( $xw_orders_menu_target ) ) { return $parent; }
    $screen = get_current_screen();
    if ( $screen && ( in_array( $screen->id, array( 'woocommerce_page_wc-orders', 'admin_page_wc-orders' ), true ) || ( 'shop_order' === $screen->post_type && in_array( $screen->base, array( 'edit', 'post' ), true ) ) ) ) {
        return $xw_orders_menu_target;
    }
    return $parent;
}
add_filter( 'parent_file', 'xw_orders_menu_parent', PHP_INT_MAX );
