<?php
/** Wishlist independiente y opcional, con tres widgets de Elementor. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_wishlist_enabled() {
    return xw_feature_enabled( 'elementor_wishlist' ) && class_exists( 'WooCommerce' ) && did_action( 'elementor/loaded' );
}

function xw_wishlist_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'xw_wishlists';
}

function xw_wishlist_install() {
    if ( ! xw_wishlist_enabled() || '1' === get_option( 'xw_wishlist_schema' ) ) { return; }
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = xw_wishlist_table_name();
    $collate = $wpdb->get_charset_collate();
    dbDelta( "CREATE TABLE $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        owner_key varchar(80) NOT NULL,
        items longtext NOT NULL,
        share_token varchar(64) DEFAULT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY owner_key (owner_key),
        UNIQUE KEY share_token (share_token)
    ) ENGINE=InnoDB $collate;" );
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table ) {
        update_option( 'xw_wishlist_schema', '1', false );
    }
}
add_action( 'admin_init', 'xw_wishlist_install' );

function xw_wishlist_guest_token( $create = false ) {
    $token = isset( $_COOKIE['xw_wishlist_v1'] ) ? (string) wp_unslash( $_COOKIE['xw_wishlist_v1'] ) : '';
    if ( preg_match( '/^[a-f0-9]{64}$/D', $token ) ) { return $token; }
    if ( ! $create || headers_sent() ) { return ''; }
    $token = hash( 'sha256', wp_generate_password( 64, false, false ) );
    setcookie( 'xw_wishlist_v1', $token, array(
        'expires' => time() + YEAR_IN_SECONDS,
        'path' => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
        'domain' => defined( 'COOKIE_DOMAIN' ) ? (string) COOKIE_DOMAIN : '',
        'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax',
    ) );
    $_COOKIE['xw_wishlist_v1'] = $token;
    return $token;
}

// Los visitantes no comparten el nonce de usuario 0 de WordPress.
add_filter( 'nonce_user_logged_out', static function ( $uid, $action ) {
    return 'xw_wishlist_write' === $action ? hash( 'sha256', xw_wishlist_guest_token( true ) ) : $uid;
}, 10, 2 );

function xw_wishlist_owner() {
    return is_user_logged_in() ? 'u:' . get_current_user_id() : 'g:' . hash( 'sha256', xw_wishlist_guest_token( true ) );
}

/** Normaliza registros, elimina duplicados y limita el tamaño de una lista. */
function xw_wishlist_normalize( $items ) {
    $result = array();
    foreach ( is_array( $items ) ? $items : array() as $item ) {
        if ( ! is_array( $item ) ) { continue; }
        $id = absint( $item['id'] ?? 0 );
        if ( ! $id || isset( $result[ $id ] ) ) { continue; }
        $result[ $id ] = array( 'id' => $id, 'added' => max( 1, absint( $item['added'] ?? time() ) ) );
        if ( count( $result ) >= 200 ) { break; }
    }
    return array_values( $result );
}

function xw_wishlist_get_row( $owner, $lock = false ) {
    global $wpdb;
    $table = xw_wishlist_table_name();
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE owner_key = %s" . ( $lock ? ' FOR UPDATE' : '' ), $owner ), ARRAY_A );
}

/** Transacción: dos clicks/pestañas no deben sobrescribir los cambios entre sí. */
function xw_wishlist_change( $owner, $callback ) {
    global $wpdb;
    $table = xw_wishlist_table_name();
    if ( false === $wpdb->query( 'START TRANSACTION' ) ) { return new WP_Error( 'storage', xw_t( 'No se pudo guardar la lista.', 'Could not save the list.' ) ); }
    $inserted = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO $table (owner_key, items, updated_at) VALUES (%s, %s, %s)", $owner, '[]', current_time( 'mysql', true ) ) );
    $row = xw_wishlist_get_row( $owner, true );
    if ( false === $inserted || ! $row ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'storage', xw_t( 'No se pudo guardar la lista.', 'Could not save the list.' ) ); }
    $items = $callback( xw_wishlist_normalize( json_decode( $row['items'], true ) ) );
    if ( is_wp_error( $items ) ) { $wpdb->query( 'ROLLBACK' ); return $items; }
    $items = xw_wishlist_normalize( $items );
    $updated = $wpdb->update( $table, array( 'items' => wp_json_encode( $items ), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $row['id'] ), array( '%s', '%s' ), array( '%d' ) );
    if ( false === $updated || false === $wpdb->query( 'COMMIT' ) ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'storage', xw_t( 'No se pudo guardar la lista.', 'Could not save the list.' ) ); }
    return $items;
}

/** Conserva los deseos de invitado al entrar en la cuenta, sin duplicarlos. */
function xw_wishlist_merge_guest() {
    $token = xw_wishlist_guest_token();
    if ( ! is_user_logged_in() || ! $token ) { return; }
    $owner = 'g:' . hash( 'sha256', $token );
    $guest = xw_wishlist_get_row( $owner );
    if ( ! $guest ) { return; }
    $items = xw_wishlist_normalize( json_decode( $guest['items'], true ) );
    $merged = xw_wishlist_change( xw_wishlist_owner(), static function ( $current ) use ( $items ) { return array_merge( $current, $items ); } );
    if ( ! is_wp_error( $merged ) ) {
        global $wpdb;
        $wpdb->delete( xw_wishlist_table_name(), array( 'owner_key' => $owner ), array( '%s' ) );
    }
}

function xw_wishlist_page_url() {
    $settings = xw_get_settings();
    $page = absint( $settings['wishlist']['page_id'] ?? 0 );
    return $page && 'publish' === get_post_status( $page ) ? get_permalink( $page ) : '';
}

/** Solo devuelve datos públicos de productos publicados y visibles. */
function xw_wishlist_product( $id, $added = 0 ) {
    $product = wc_get_product( $id );
    if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() ) { return null; }
    $image_id = $product->get_image_id();
    if ( ! $image_id && $product->get_parent_id() ) {
        $parent = wc_get_product( $product->get_parent_id() );
        $image_id = $parent ? $parent->get_image_id() : 0;
    }
    return array(
        'id' => $product->get_id(), 'name' => $product->get_name(),
        'url' => esc_url_raw( $product->get_permalink() ),
        'image' => esc_url_raw( $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src() ),
        'price' => wp_kses_post( $product->get_price_html() ),
        'date' => $added ? wp_date( get_option( 'date_format' ), $added ) : '',
        'in_stock' => $product->is_in_stock(),
        'stock' => wp_strip_all_tags( wc_get_stock_html( $product ) ),
        'simple' => $product->is_type( 'simple' ),
        'purchasable' => $product->is_purchasable() && $product->is_in_stock(),
    );
}

function xw_wishlist_payload( $items, $read_only = false ) {
    $products = array();
    foreach ( xw_wishlist_normalize( $items ) as $item ) {
        $product = xw_wishlist_product( $item['id'], $item['added'] );
        if ( $product ) { $products[] = $product; }
    }
    return array( 'items' => $products, 'count' => count( $products ), 'read_only' => $read_only );
}

function xw_wishlist_ajax() {
    nocache_headers();
    if ( ! xw_wishlist_enabled() ) { wp_send_json_error( array( 'message' => xw_t( 'Wishlist no está disponible.', 'Wishlist is unavailable.' ) ), 503 ); }
    xw_wishlist_install();
    if ( '1' !== get_option( 'xw_wishlist_schema' ) ) { wp_send_json_error( array( 'message' => xw_t( 'No se pudo preparar la lista.', 'Could not prepare the list.' ) ), 503 ); }
    $operation = sanitize_key( wp_unslash( $_POST['operation'] ?? 'state' ) );
    $share = sanitize_text_field( wp_unslash( $_POST['share'] ?? '' ) );
    if ( ! is_user_logged_in() && ! xw_wishlist_guest_token( true ) ) { wp_send_json_error( array( 'message' => xw_t( 'No se pudo iniciar la sesión de Wishlist.', 'Could not start the Wishlist session.' ) ), 503 ); }
    $owner = xw_wishlist_owner();
    if ( 'state' === $operation ) {
        if ( $share ) {
            if ( ! preg_match( '/^[a-f0-9]{64}$/D', $share ) ) { wp_send_json_error( array( 'message' => xw_t( 'Enlace no válido.', 'Invalid link.' ) ), 404 ); }
            global $wpdb;
            $table = xw_wishlist_table_name();
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE share_token = %s", $share ), ARRAY_A );
            if ( ! $row ) { wp_send_json_error( array( 'message' => xw_t( 'Esta lista no está disponible.', 'This list is unavailable.' ) ), 404 ); }
        } else {
            xw_wishlist_merge_guest();
            $row = xw_wishlist_get_row( $owner );
        }
        $payload = xw_wishlist_payload( $row ? json_decode( $row['items'], true ) : array(), (bool) $share );
        $payload['nonce'] = wp_create_nonce( 'xw_wishlist_write' );
        wp_send_json_success( $payload );
    }
    if ( ! check_ajax_referer( 'xw_wishlist_write', 'nonce', false ) ) { wp_send_json_error( array( 'message' => xw_t( 'La sesión expiró. Recarga la página e inténtalo de nuevo.', 'The session expired. Reload the page and try again.' ) ), 403 ); }
    if ( ! in_array( $operation, array( 'add', 'remove', 'share', 'cart' ), true ) ) { wp_send_json_error( array( 'message' => xw_t( 'Acción no válida.', 'Invalid action.' ) ), 400 ); }
    if ( 'add' === $operation || 'remove' === $operation ) {
        $id = absint( $_POST['product_id'] ?? 0 );
        if ( 'add' === $operation && ! xw_wishlist_product( $id ) ) { wp_send_json_error( array( 'message' => xw_t( 'Producto no disponible.', 'Product unavailable.' ) ), 404 ); }
        $items = xw_wishlist_change( $owner, static function ( $items ) use ( $id, $operation ) {
            if ( 'remove' === $operation ) { return array_filter( $items, static function ( $item ) use ( $id ) { return $id !== $item['id']; } ); }
            // Productos borrados/privados no deben bloquear para siempre el límite.
            $items = array_values( array_filter( $items, static function ( $item ) { return null !== xw_wishlist_product( $item['id'] ); } ) );
            $existing = array_column( $items, 'id' );
            if ( in_array( $id, $existing, true ) ) { return $items; }
            if ( count( $items ) >= 200 ) { return new WP_Error( 'limit', xw_t( 'La lista admite hasta 200 productos.', 'The list supports up to 200 products.' ) ); }
            $items[] = array( 'id' => $id, 'added' => time() );
            return $items;
        } );
        if ( is_wp_error( $items ) ) { wp_send_json_error( array( 'message' => $items->get_error_message() ), 400 ); }
        wp_send_json_success( xw_wishlist_payload( $items ) );
    }
    if ( 'share' === $operation ) {
        $page = xw_wishlist_page_url();
        if ( ! $page ) { wp_send_json_error( array( 'message' => xw_t( 'Selecciona la página de Wishlist en los ajustes de la suite.', 'Select the Wishlist page in the suite settings.' ) ), 400 ); }
        $items = xw_wishlist_change( $owner, static function ( $items ) { return $items; } );
        if ( is_wp_error( $items ) ) { wp_send_json_error( array( 'message' => $items->get_error_message() ), 500 ); }
        global $wpdb;
        $table = xw_wishlist_table_name();
        $token = hash( 'sha256', wp_generate_password( 64, false, false ) );
        // El primer enlace se conserva: clicks simultáneos no invalidan lo compartido.
        $wpdb->query( $wpdb->prepare( "UPDATE $table SET share_token = %s WHERE owner_key = %s AND share_token IS NULL", $token, $owner ) );
        $row = xw_wishlist_get_row( $owner );
        if ( empty( $row['share_token'] ) ) { wp_send_json_error( array( 'message' => xw_t( 'No se pudo crear el enlace.', 'Could not create the link.' ) ), 500 ); }
        wp_send_json_success( array( 'url' => add_query_arg( 'xw_wishlist', $row['share_token'], $page ) ) );
    }
    // Carrito: nunca inventar una variación ni omitir la validación de WooCommerce.
    $row = xw_wishlist_get_row( $owner );
    $allowed = array_column( xw_wishlist_normalize( $row ? json_decode( $row['items'], true ) : array() ), 'id' );
    $ids = array_slice( array_unique( array_map( 'absint', (array) ( $_POST['ids'] ?? array() ) ) ), 0, 200 );
    if ( ! WC()->cart ) { wc_load_cart(); }
    $added = 0;
    $skipped = 0;
    foreach ( $ids as $id ) {
        $product = in_array( $id, $allowed, true ) ? wc_get_product( $id ) : false;
        if ( ! $product || ! xw_wishlist_product( $id ) || ! $product->is_type( 'simple' ) || ! $product->is_purchasable() || ! $product->is_in_stock() ) { ++$skipped; continue; }
        try {
            if ( apply_filters( 'woocommerce_add_to_cart_validation', true, $id, 1 ) && WC()->cart->add_to_cart( $id, 1 ) ) { ++$added; } else { ++$skipped; }
        } catch ( Exception $e ) { ++$skipped; }
    }
    $message = sprintf( xw_t( '%d productos añadidos al carrito.', '%d products added to cart.' ), $added );
    if ( $skipped ) { $message .= ' ' . xw_t( 'Algunos productos necesitan opciones o no están disponibles; abre su ficha para elegirlos.', 'Some products require options or are unavailable; open their product page to choose them.' ); }
    wp_send_json_success( array( 'message' => $message, 'added' => $added, 'skipped' => $skipped ) );
}
add_action( 'wp_ajax_xw_wishlist', 'xw_wishlist_ajax' );
add_action( 'wp_ajax_nopriv_xw_wishlist', 'xw_wishlist_ajax' );

function xw_wishlist_register_assets() {
    if ( ! xw_wishlist_enabled() ) { return; }
    $url = plugin_dir_url( XW_FUNCTIONS_FILE );
    $path = plugin_dir_path( XW_FUNCTIONS_FILE );
    wp_register_style( 'xw-wishlist', $url . 'assets/wishlist/wishlist.css', array(), filemtime( $path . 'assets/wishlist/wishlist.css' ) );
    wp_register_script( 'xw-wishlist', $url . 'assets/wishlist/wishlist.js', array(), filemtime( $path . 'assets/wishlist/wishlist.js' ), true );
    wp_localize_script( 'xw-wishlist', 'xwWishlist', array(
        'ajax' => admin_url( 'admin-ajax.php' ),
        'error' => xw_t( 'No se pudo actualizar la lista. Inténtalo de nuevo.', 'Could not update the list. Try again.' ),
        'saved' => xw_t( 'Producto guardado.', 'Product saved.' ),
        'removed' => xw_t( 'Producto eliminado de la lista.', 'Product removed from the list.' ),
        'copied' => xw_t( 'Enlace copiado.', 'Link copied.' ),
        'select' => xw_t( 'Selecciona al menos un producto.', 'Select at least one product.' ),
        'removeLabel' => xw_t( 'Eliminar', 'Remove' ),
        'selectLabel' => xw_t( 'Seleccionar', 'Select' ),
        'popup' => xw_t( 'Permite ventanas emergentes para compartir la lista.', 'Allow popups to share the list.' ),
        'copyLabel' => xw_t( 'Enlace de Wishlist', 'Wishlist link' ),
        'shop' => wc_get_page_permalink( 'shop' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'xw_wishlist_register_assets', 5 );
add_action( 'elementor/preview/enqueue_scripts', 'xw_wishlist_register_assets', 5 );

/** Refresh generated CSS/markup once after the responsive widget revision. No templates or lists are changed. */
function xw_wishlist_refresh_elementor_styles() {
    $revision = '3';
    if ( ! xw_wishlist_enabled() || ! current_user_can( 'manage_options' ) || get_option( 'xw_wishlist_style_revision' ) === $revision || ! class_exists( '\Elementor\Plugin' ) ) { return; }
    $manager = \Elementor\Plugin::$instance->files_manager ?? null;
    if ( ! $manager || ! is_callable( array( $manager, 'clear_cache' ) ) ) { return; }
    $manager->clear_cache();
    update_option( 'xw_wishlist_style_revision', $revision, false );
}
add_action( 'admin_init', 'xw_wishlist_refresh_elementor_styles' );

add_action( 'elementor/elements/categories_registered', static function ( $manager ) {
    if ( xw_wishlist_enabled() ) { $manager->add_category( 'xw-suite', array( 'title' => 'Creative Pear Suite', 'icon' => 'fa fa-heart' ) ); }
} );
add_action( 'elementor/widgets/register', static function ( $manager ) {
    if ( ! xw_wishlist_enabled() ) { return; }
    require_once __DIR__ . '/wishlist/widgets.php';
    $manager->register( new XW_Wishlist_Table_Widget() );
    $manager->register( new XW_Wishlist_Counter_Widget() );
    $manager->register( new XW_Wishlist_Add_Widget() );
} );
