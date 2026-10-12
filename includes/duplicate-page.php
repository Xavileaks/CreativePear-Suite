<?php
/** Optional duplication of saved WordPress content, using native APIs. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_duplicate_defaults() {
    return array( 'editor' => 'all', 'status' => 'draft', 'redirect' => 'list', 'suffix' => '' );
}

function xw_duplicate_sanitize( $input ) {
    $input = is_array( $input ) ? $input : array();
    $settings = xw_duplicate_defaults();
    $choices = array( 'editor' => array( 'all', 'classic', 'gutenberg' ), 'status' => array( 'draft', 'pending', 'private', 'publish' ), 'redirect' => array( 'list', 'edit' ) );
    foreach ( $choices as $key => $allowed ) {
        if ( isset( $input[ $key ] ) && in_array( $input[ $key ], $allowed, true ) ) { $settings[ $key ] = $input[ $key ]; }
    }
    $settings['suffix'] = isset( $input['suffix'] ) && is_scalar( $input['suffix'] ) ? mb_substr( sanitize_text_field( $input['suffix'] ), 0, 80 ) : '';
    return $settings;
}

function xw_duplicate_settings() {
    return xw_duplicate_sanitize( xw_get_settings()['duplicate_page'] );
}

function xw_duplicate_enabled() {
    // The original plugin keeps its own links and settings while active.
    return xw_feature_enabled( 'duplicate_page' ) && ! class_exists( 'duplicate_page', false );
}

function xw_duplicate_allowed( $post ) {
    if ( ! $post ) { return false; }
    $post = get_post( $post );
    if ( ! $post || in_array( $post->post_type, array( 'attachment', 'revision', 'nav_menu_item', 'product', 'product_variation', 'shop_order', 'shop_order_refund' ), true ) || in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) ) { return false; }
    $type = get_post_type_object( $post->post_type );
    return $type && $type->show_ui && ( $type->public || 'elementor_library' === $post->post_type ) && current_user_can( 'read_post', $post->ID ) && current_user_can( 'edit_post', $post->ID ) && current_user_can( $type->cap->create_posts );
}

function xw_duplicate_url( $post_id ) {
    return wp_nonce_url( add_query_arg( array( 'action' => 'xw_duplicate_page', 'post' => (int) $post_id ), admin_url( 'admin-post.php' ) ), 'xw_duplicate_page_' . (int) $post_id );
}

function xw_duplicate_safe_meta( $value, $depth = 0 ) {
    if ( $depth > 20 || is_object( $value ) || is_resource( $value ) ) { return false; }
    if ( is_array( $value ) ) {
        foreach ( $value as $item ) { if ( ! xw_duplicate_safe_meta( $item, $depth + 1 ) ) { return false; } }
    }
    return ! is_string( $value ) || ! preg_match( '/(?:^|[;{])(?:O|C):\d+:/', $value );
}

/** Returns the new ID or WP_Error; never updates the source. */
function xw_duplicate_post( $post_id ) {
    $source = get_post( $post_id );
    if ( ! $source || ! xw_duplicate_enabled() || ! xw_duplicate_allowed( $source ) ) {
        return new WP_Error( 'xw_duplicate_forbidden', xw_t( 'No tienes permiso para duplicar este contenido.', 'You cannot duplicate this content.' ) );
    }
    $settings = xw_duplicate_settings();
    $type = get_post_type_object( $source->post_type );
    $status = $settings['status'];
    if ( in_array( $status, array( 'publish', 'private' ), true ) && ! current_user_can( $type->cap->publish_posts ) ) { $status = 'draft'; }
    // Build privately first; only publish after all terms and metadata are copied.
    $new_id = wp_insert_post( wp_slash( array(
        'post_type' => $source->post_type, 'post_status' => 'draft', 'post_author' => get_current_user_id(),
        'post_title' => $source->post_title . ( '' !== $settings['suffix'] ? ' — ' . $settings['suffix'] : '' ),
        'post_content' => $source->post_content, 'post_excerpt' => $source->post_excerpt,
        'post_parent' => $source->post_parent, 'post_password' => $source->post_password,
        'comment_status' => $source->comment_status, 'ping_status' => $source->ping_status, 'menu_order' => $source->menu_order,
    ) ), true );
    if ( is_wp_error( $new_id ) || ! $new_id ) { return is_wp_error( $new_id ) ? $new_id : new WP_Error( 'xw_duplicate_failed', xw_t( 'No se pudo crear la copia.', 'The copy could not be created.' ) ); }
    foreach ( get_object_taxonomies( $source->post_type ) as $taxonomy ) {
        $terms = wp_get_object_terms( $source->ID, $taxonomy, array( 'fields' => 'ids' ) );
        $result = is_wp_error( $terms ) ? $terms : wp_set_object_terms( $new_id, array_map( 'intval', $terms ), $taxonomy );
        if ( is_wp_error( $result ) ) { wp_delete_post( $new_id, true ); return $result; }
    }
    $skip = array( '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date', '_elementor_css', '_elementor_page_assets', '_elementor_element_cache', '_elementor_conditions' );
    foreach ( get_post_meta( $source->ID ) as $key => $values ) {
        if ( in_array( $key, $skip, true ) ) { continue; }
        foreach ( get_post_meta( $source->ID, $key ) as $value ) {
            if ( ! xw_duplicate_safe_meta( $value ) ) { continue; }
            if ( ! add_post_meta( $new_id, $key, wp_slash( $value ) ) ) {
                wp_delete_post( $new_id, true );
                return new WP_Error( 'xw_duplicate_meta_failed', xw_t( 'No se pudieron copiar todos los campos. Inténtalo de nuevo.', 'Not all fields could be copied. Please try again.' ) );
            }
        }
    }
    if ( 'draft' !== $status ) {
        $result = wp_update_post( array( 'ID' => $new_id, 'post_status' => $status ), true );
        if ( is_wp_error( $result ) || ! $result ) { wp_delete_post( $new_id, true ); return is_wp_error( $result ) ? $result : new WP_Error( 'xw_duplicate_failed', 'Could not set copy status.' ); }
    }
    return $new_id;
}

function xw_duplicate_request( $post_id, $nonce ) {
    if ( ! is_scalar( $post_id ) || ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'xw_duplicate_page_' . absint( $post_id ) ) ) {
        return new WP_Error( 'xw_duplicate_nonce', xw_t( 'La verificación de seguridad falló. Recarga e inténtalo de nuevo.', 'Security verification failed. Reload and try again.' ) );
    }
    return xw_duplicate_post( absint( $post_id ) );
}

add_action( 'admin_post_xw_duplicate_page', static function () {
    $result = xw_duplicate_request( $_GET['post'] ?? 0, isset( $_GET['_wpnonce'] ) && is_string( $_GET['_wpnonce'] ) ? wp_unslash( $_GET['_wpnonce'] ) : '' );
    if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 403, 'back_link' => true ) ); }
    $settings = xw_duplicate_settings();
    $type = get_post_type( $result );
    $url = 'edit' === $settings['redirect'] ? get_edit_post_link( $result, 'raw' ) : add_query_arg( array( 'post_type' => $type, 'xw_duplicated' => 1 ), admin_url( 'edit.php' ) );
    wp_safe_redirect( $url ); exit;
} );

function xw_duplicate_row_action( $actions, $post ) {
    if ( xw_duplicate_enabled() && xw_duplicate_allowed( $post ) ) {
        $label = xw_t( 'Duplicar', 'Duplicate This' );
        $actions['xw_duplicate_page'] = '<a href="' . esc_url( xw_duplicate_url( $post->ID ) ) . '" aria-label="' . esc_attr( $label . ': ' . $post->post_title ) . '">' . esc_html( $label ) . '</a>';
    }
    return $actions;
}
add_filter( 'post_row_actions', 'xw_duplicate_row_action', 10, 2 );
add_filter( 'page_row_actions', 'xw_duplicate_row_action', 10, 2 );

add_action( 'post_submitbox_misc_actions', static function () {
    global $post;
    if ( ! xw_duplicate_enabled() || ! xw_duplicate_allowed( $post ) || 'gutenberg' === xw_duplicate_settings()['editor'] ) { return; }
    echo '<div class="misc-pub-section"><a href="' . esc_url( xw_duplicate_url( $post->ID ) ) . '">' . esc_html( xw_t( 'Duplicar contenido guardado', 'Duplicate saved content' ) ) . '</a></div>';
} );

add_action( 'enqueue_block_editor_assets', static function () {
    global $post;
    if ( ! xw_duplicate_enabled() || ! xw_duplicate_allowed( $post ) || 'classic' === xw_duplicate_settings()['editor'] ) { return; }
    wp_enqueue_script( 'xw-duplicate-page-editor', plugins_url( 'assets/js/duplicate-page-editor.js', XW_FUNCTIONS_FILE ), array( 'wp-element', 'wp-plugins', 'wp-editor', 'wp-edit-post' ), XW_FUNCTIONS_VERSION, true );
    wp_localize_script( 'xw-duplicate-page-editor', 'xwDuplicatePage', array( 'url' => xw_duplicate_url( $post->ID ), 'label' => xw_t( 'Duplicar contenido guardado', 'Duplicate saved content' ) ) );
} );

add_action( 'admin_bar_menu', static function ( $bar ) {
    if ( is_admin() || ! is_singular() || ! xw_duplicate_enabled() ) { return; }
    $post = get_queried_object();
    if ( ! xw_duplicate_allowed( $post ) ) { return; }
    $bar->add_node( array( 'id' => 'xw-duplicate-page', 'parent' => 'edit', 'title' => xw_t( 'Duplicar contenido guardado', 'Duplicate saved content' ), 'href' => xw_duplicate_url( $post->ID ) ) );
}, 90 );

add_action( 'admin_notices', static function () {
    if ( xw_duplicate_enabled() && isset( $_GET['xw_duplicated'] ) && '1' === $_GET['xw_duplicated'] ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( xw_t( 'Copia creada. El contenido original no ha cambiado.', 'Copy created. The original content has not changed.' ) ) . '</p></div>';
    }
} );

function xw_render_duplicate_settings( $settings ) {
    $fields = array(
        'editor' => array( xw_t( 'Editor', 'Editor' ), array( 'all' => xw_t( 'Todos los editores', 'All editors' ), 'classic' => xw_t( 'Editor clásico', 'Classic editor' ), 'gutenberg' => 'Gutenberg' ) ),
        'status' => array( xw_t( 'Estado de la copia', 'Copy status' ), array( 'draft' => xw_t( 'Borrador', 'Draft' ), 'pending' => xw_t( 'Pendiente', 'Pending review' ), 'private' => xw_t( 'Privado', 'Private' ), 'publish' => xw_t( 'Publicado', 'Published' ) ) ),
        'redirect' => array( xw_t( 'Después de duplicar', 'After duplicating' ), array( 'list' => xw_t( 'Volver al listado', 'Return to list' ), 'edit' => xw_t( 'Editar la copia', 'Edit the copy' ) ) ),
    );
    foreach ( $fields as $key => $field ) : ?>
        <div class="xw-field-row">
            <label for="xw-duplicate-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label>
            <select id="xw-duplicate-<?php echo esc_attr( $key ); ?>" name="xw_settings[duplicate_page][<?php echo esc_attr( $key ); ?>]">
                <?php foreach ( $field[1] as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings[ $key ], $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
            </select>
        </div>
    <?php endforeach; ?>
    <div class="xw-field-row">
        <label for="xw-duplicate-suffix"><?php echo esc_html( xw_t( 'Sufijo del título', 'Title suffix' ) ); ?></label>
        <input id="xw-duplicate-suffix" type="text" name="xw_settings[duplicate_page][suffix]" value="<?php echo esc_attr( $settings['suffix'] ); ?>" maxlength="80" placeholder="<?php echo esc_attr( xw_t( 'Copia', 'Copy' ) ); ?>">
    </div>
    <div class="xw-field-row xw-field-full">
        <p><?php echo esc_html( xw_t( 'Añade «Duplicar» a los listados de páginas, entradas y tipos de contenido compatibles. Copia la versión guardada, campos, taxonomías e imágenes vinculadas; no duplica archivos, comentarios ni condiciones globales de plantillas Elementor. Los productos conservan la duplicación nativa de WooCommerce.', 'Adds “Duplicate This” to pages, posts and supported custom content lists. Copies the saved version, fields, taxonomies and linked images; does not duplicate files, comments or Elementor template display conditions. Products keep WooCommerce’s native duplication.' ) ); ?></p>
        <p><?php echo esc_html( xw_t( 'Publicado/Privado requiere permiso de publicación; sin él, se crea un borrador. Si Duplicate Page está activo, desactívalo antes de usar este módulo; sus ajustes no se modifican.', 'Published/Private requires publishing permission; otherwise a draft is created. If Duplicate Page is active, deactivate it before using this module; its settings are not changed.' ) ); ?></p>
    </div>
    <?php
}
