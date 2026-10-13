<?php
/** CLI only: native Orders menu relocation in an isolated WordPress installation. */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { exit( "Supply an isolated WordPress directory.\n" ); }
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { exit( "Local environment required.\n" ); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
require_once ABSPATH . 'wp-admin/includes/screen.php';
function orders_check( $ok, $label ) { if ( ! $ok ) { throw new RuntimeException( $label ); } echo "PASS: $label\n"; }
$original = get_option( 'xw_settings' );
$admin = (int) get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) )[0];
$count_title = 'Orders <span class="menu-counter count-6"><span class="processing-count">6</span></span>';
function orders_seed_menu( $route, $title ) {
    global $menu, $submenu;
    $menu = array( '55.5' => array( 'WooCommerce', 'edit_others_shop_orders', 'woocommerce' ), 56 => array( 'Products', 'edit_products', 'edit.php?post_type=product' ) );
    $submenu = array( 'woocommerce' => array( array( $title, 'edit_shop_orders', $route, 'Orders' ), array( 'Coupons', 'edit_shop_coupons', 'edit.php?post_type=shop_coupon' ), array( 'Custom orders', 'edit_shop_orders', 'wc-orders--custom' ) ) );
}
try {
    wp_set_current_user( $admin );
    $settings = xw_get_settings();
    $settings['features']['woocommerce_orders_menu'] = 0;
    update_option( 'xw_settings', $settings );
    orders_seed_menu( 'wc-orders', $count_title ); $before = array( $menu, $submenu );
    xw_orders_menu_move();
    orders_check( array( $menu, $submenu ) === $before && xw_get_default_settings()['features']['woocommerce_orders_menu'] === 0, 'off by default and disabled module leaves native menu untouched' );
    $settings['features']['woocommerce_orders_menu'] = 1;
    update_option( 'xw_settings', $settings );
    orders_check( xw_sanitize_settings( $settings )['features']['woocommerce_orders_menu'] === 1, 'feature flag uses the existing settings sanitizer' );
    wp_set_current_user( 0 ); xw_orders_menu_move();
    orders_check( array( $menu, $submenu ) === $before, 'no menu entry or permissions granted to guests' );
    wp_set_current_user( $admin );
    foreach ( array( 'wc-orders' => 'admin.php?page=wc-orders', 'edit.php?post_type=shop_order' => 'edit.php?post_type=shop_order' ) as $route => $target ) {
        orders_seed_menu( $route, $count_title );
        if ( 'wc-orders' === $route ) {
            $admin_page_hooks['woocommerce'] = 'woocommerce';
            $_registered_pages['woocommerce_page_wc-orders'] = true;
            $pagenow = 'admin.php'; $plugin_page = 'wc-orders'; $parent_file = '';
            add_action( 'woocommerce_page_wc-orders', '__return_true' );
        }
        xw_orders_menu_move();
        orders_check( count( $submenu['woocommerce'] ) === 3, 'native submenu survives page authorization before rendering' );
        if ( 'wc-orders' === $route ) {
            orders_check( get_plugin_page_hook( 'wc-orders', 'admin.php' ) === 'woocommerce_page_wc-orders' && user_can_access_admin_page(), 'HPOS uses its original authorized WooCommerce load/output hook' );
            unset( $plugin_page );
        }
        xw_orders_menu_hide_submenu();
        $entry = array_values( array_filter( $menu, function ( $item ) use ( $target ) { return $target === $item[2]; } ) );
        orders_check( count( $entry ) === 1 && $entry[0][6] === 'dashicons-cart', 'Orders uses the native shopping cart icon for both storage modes' );
        orders_check( count( $entry ) === 1 && $entry[0][0] === $count_title && $entry[0][1] === 'edit_shop_orders' && count( $submenu['woocommerce'] ) === 2, "$route moves without losing count or native capability; unrelated submenus remain" );
        $order = xw_orders_menu_order( array( 'woocommerce', 'edit.php?post_type=product', $target, 'users.php' ) );
        orders_check( $order === array( 'woocommerce', $target, 'edit.php?post_type=product', 'users.php' ) && xw_orders_menu_order( $order ) === $order, 'placed directly below WooCommerce after native ordering, without duplicates' );
        set_current_screen( 'wc-orders' === $route ? 'woocommerce_page_wc-orders' : 'edit-shop_order' );
        orders_check( xw_orders_menu_parent( 'woocommerce' ) === $target, 'native order list selects standalone menu' );
        set_current_screen( 'wc-orders' === $route ? 'woocommerce_page_wc-orders' : 'shop_order' );
        orders_check( xw_orders_menu_parent( 'woocommerce' ) === $target, 'native order editor selects standalone menu' );
        set_current_screen( 'product' );
        orders_check( xw_orders_menu_parent( 'edit.php?post_type=product' ) === 'edit.php?post_type=product', 'products and other screens keep their own highlight' );
        $before_move = array( $menu, $submenu ); xw_orders_menu_move();
        orders_check( array( $menu, $submenu ) === $before_move, 'repeated registration does not duplicate or remove other entries' );
    }
    orders_seed_menu( 'wc-orders', $count_title ); $menu[57] = array( 'Existing orders', 'edit_shop_orders', 'admin.php?page=wc-orders' );
    $before = array( $menu, $submenu ); xw_orders_menu_move(); orders_check( array( $menu, $submenu ) === $before, 'existing third-party standalone Orders entry is respected' );
    orders_seed_menu( 'wc-orders', $count_title ); unset( $menu['55.5'] ); $before = array( $menu, $submenu );
    xw_orders_menu_move(); orders_check( array( $menu, $submenu ) === $before, 'missing WooCommerce parent leaves menus untouched' );
    $spanish = function () { return 'es_ES'; }; add_filter( 'pre_determine_locale', $spanish );
    orders_check( strpos( xw_get_feature_definitions()['woocommerce_orders_menu']['title'], 'Pedidos' ) !== false && xw_t( 'Pedidos', 'Orders' ) === 'Pedidos', 'new option uses Spanish for Spanish locale' ); remove_filter( 'pre_determine_locale', $spanish );
} finally { wp_set_current_user( $admin ); update_option( 'xw_settings', $original ); }
