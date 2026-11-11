<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'add_meta_boxes_xw_smart_filter', static function() {
    add_meta_box( 'xw-sf-builder', xw_t( 'Filtros del grupo', 'Group filters' ), 'xw_sf_render_builder', 'xw_smart_filter', 'normal', 'high' );
} );

add_filter( 'enter_title_here', static function( $title, $post ) {
    return 'xw_smart_filter' === $post->post_type ? xw_t( 'Nombre del grupo', 'Group name' ) : $title;
}, 10, 2 );

add_filter( 'post_updated_messages', static function( $messages ) {
    $updated = xw_t( 'Grupo de filtros actualizado.', 'Filter group updated.' );
    $messages['xw_smart_filter'] = array(
        1 => $updated,
        4 => $updated,
        6 => xw_t( 'Grupo de filtros publicado.', 'Filter group published.' ),
        7 => xw_t( 'Grupo de filtros guardado.', 'Filter group saved.' ),
        10 => xw_t( 'Borrador del grupo actualizado.', 'Filter group draft updated.' ),
    ) + $messages['post'];
    return $messages;
} );

add_action( 'admin_enqueue_scripts', static function() {
    $screen = get_current_screen();
    if ( ! $screen || 'post' !== $screen->base || 'xw_smart_filter' !== $screen->post_type || ! xw_sf_enabled() ) { return; }
    xw_sf_asset( 'admin.css' );
    xw_admin_save_bar_assets();
    xw_sf_asset( 'admin.js', true, array( 'jquery', 'selectWoo', 'post' ) );
    wp_localize_script( 'xw-sf-admin.js', 'xwSmartFilterAdmin', array(
        'ajax' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'xw_sf_terms' ),
        'placeholder' => xw_t( 'Buscar y seleccionar términos…', 'Search and select terms…' ),
        'revision' => xw_sf_group_revision( get_post() ),
        'save' => array(
            'idle' => xw_t( 'Sin cambios pendientes.', 'No unsaved changes.' ),
            'pending' => xw_t( 'Hay cambios pendientes de guardar.', 'You have unsaved changes.' ),
            'saving' => xw_t( 'Guardando…', 'Saving…' ),
            'saved' => xw_t( 'Cambios guardados.', 'Changes saved.' ),
            'error' => xw_t( 'No se pudo confirmar el guardado. Reinténtalo.', 'Saving could not be confirmed. Please try again.' ),
            'button' => xw_t( 'Guardar cambios', 'Save changes' ),
        ),
    ) );
    wp_enqueue_style( 'woocommerce_admin_styles' );
    wp_enqueue_style( 'xw-sf-admin.css' );
    wp_enqueue_script( 'xw-sf-admin.js' );
} );

function xw_sf_group_revision( $post ) {
    return hash( 'sha256', wp_json_encode( array(
        'auto-draft' === $post->post_status ? '' : $post->post_title, $post->post_status, $post->post_password,
        get_post_meta( $post->ID, '_xw_sf_rows', true ),
    ) ) );
}

add_action( 'dbx_post_sidebar', static function( $post ) {
    if ( 'xw_smart_filter' !== $post->post_type || ! xw_sf_enabled() ) { return; }
    ?>
    <div class="xw-save-bar" data-xw-sf-save-bar hidden>
        <p role="status" aria-live="polite" aria-atomic="true"><?php echo esc_html( xw_t( 'Sin cambios pendientes.', 'No unsaved changes.' ) ); ?></p>
        <button type="submit" class="button button-primary" disabled><?php echo esc_html( in_array( $post->post_status, array( 'auto-draft', 'draft', 'pending' ), true ) ? xw_t( 'Publicar grupo', 'Publish group' ) : xw_t( 'Guardar cambios', 'Save changes' ) ); ?></button>
    </div>
    <?php
} );

add_action( 'wp_ajax_xw_sf_save_group', static function() {
    check_ajax_referer( 'xw_sf_save', 'nonce' );
    $post = get_post( absint( $_POST['post_id'] ?? 0 ) );
    if ( ! xw_sf_enabled() || ! $post || 'xw_smart_filter' !== $post->post_type ||
        ! current_user_can( 'edit_post', $post->ID ) || ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( null, 403 );
    }
    $revision = $_POST['revision'] ?? '';
    if ( ! is_string( $revision ) || ! hash_equals( xw_sf_group_revision( $post ), wp_unslash( $revision ) ) || wp_check_post_lock( $post->ID ) ) {
        wp_send_json_error( array( 'message' => xw_t( 'Este grupo ha cambiado en otra sesión. Recarga la página antes de guardar.', 'This group has changed in another session. Reload the page before saving.' ) ), 409 );
    }
    if ( in_array( $post->post_status, array( 'trash', 'future' ), true ) ) {
        wp_send_json_error( null, 409 );
    }
    $input = $_POST['rows'] ?? null;
    $title = $_POST['title'] ?? null;
    $input = is_string( $input ) ? json_decode( wp_unslash( $input ), true ) : null;
    if ( ! is_array( $input ) || ! is_string( $title ) ) { wp_send_json_error( null, 400 ); }
    $rows = xw_sf_rows( $input, true );
    $title = sanitize_text_field( wp_unslash( $title ) );
    $status = in_array( $post->post_status, array( 'auto-draft', 'draft', 'pending' ), true ) ? 'publish' : $post->post_status;
    $saved = wp_update_post( wp_slash( array(
        'ID' => $post->ID, 'post_title' => $title, 'post_status' => $status,
        'meta_input' => array( '_xw_sf_rows' => $rows ),
    ) ), true );
    $post = get_post( $post->ID );
    if ( is_wp_error( $saved ) || ! $saved || $post->post_title !== $title ||
        $post->post_status !== $status || get_post_meta( $post->ID, '_xw_sf_rows', true ) !== $rows ) {
        wp_send_json_error( array( 'message' => xw_t( 'No se pudo confirmar el guardado. Reinténtalo.', 'Saving could not be confirmed. Please try again.' ) ), 500 );
    }
    ob_start();
    $sources = xw_sf_sources();
    foreach ( array_values( $rows ) as $index => $row ) { xw_sf_render_builder_row( $index, $row, $sources ); }
    wp_send_json_success( array(
        'html' => ob_get_clean(), 'title' => $post->post_title, 'status' => $post->post_status,
        'statusLabel' => get_post_status_object( $post->post_status )->label,
        'revision' => xw_sf_group_revision( $post ), 'nonce' => wp_create_nonce( 'xw_sf_save' ),
        'editUrl' => get_edit_post_link( $post->ID, 'raw' ),
    ) );
} );

add_action( 'wp_ajax_xw_sf_terms', static function() {
    check_ajax_referer( 'xw_sf_terms', 'nonce' );
    if ( ! xw_sf_enabled() || ! current_user_can( 'manage_woocommerce' ) ) { wp_send_json_error( null, 403 ); }
    $source = sanitize_key( is_string( $_GET['taxonomy'] ?? null ) ? $_GET['taxonomy'] : '' );
    if ( ! xw_sf_is_terms( $source ) || ! isset( xw_sf_sources()[ $source ] ) ) { wp_send_json_error( null, 400 ); }
    $search = is_string( $_GET['q'] ?? null ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
    $page = min( 200, max( 1, absint( $_GET['page'] ?? 1 ) ) );
    $terms = get_terms( array(
        'taxonomy' => $source, 'hide_empty' => false, 'search' => $search,
        'number' => 21, 'offset' => ( $page - 1 ) * 20, 'orderby' => 'name',
    ) );
    if ( is_wp_error( $terms ) ) { wp_send_json_error( null, 400 ); }
    wp_send_json( array(
        'results' => array_map( static function( $term ) {
            return array( 'id' => $term->term_id, 'text' => wp_specialchars_decode( $term->name, ENT_QUOTES ) );
        }, array_slice( $terms, 0, 20 ) ),
        'pagination' => array( 'more' => count( $terms ) > 20 ),
    ) );
} );

add_action( 'save_post_xw_smart_filter', static function( $id ) {
    $nonce = $_POST['xw_sf_nonce'] ?? '';
    if ( ! xw_sf_enabled() || ! is_string( $nonce ) || ! wp_verify_nonce( wp_unslash( $nonce ), 'xw_sf_save' ) ||
        wp_is_post_autosave( $id ) || wp_is_post_revision( $id ) || ! current_user_can( 'edit_post', $id ) ) {
        return;
    }
    $rows = $_POST['xw_sf_rows'] ?? array();
    if ( is_array( $rows ) ) {
        update_post_meta( $id, '_xw_sf_rows', wp_slash( xw_sf_rows( wp_unslash( $rows ), true ) ) );
    }
} );

function xw_sf_render_builder( $post ) {
    $rows = xw_sf_rows( get_post_meta( $post->ID, '_xw_sf_rows', true ), true );
    $sources = xw_sf_sources();
    wp_nonce_field( 'xw_sf_save', 'xw_sf_nonce' );
    ?>
    <div class="xw-sf-builder" data-xw-sf-builder>
        <p class="description"><?php echo esc_html( xw_t( 'Elige qué podrá filtrar el visitante, cómo se muestra cada control y en qué orden aparece.', 'Choose what visitors can filter, how each control appears, and its order.' ) ); ?></p>
        <ol class="xw-sf-rows" data-xw-sf-rows>
            <?php foreach ( array_values( $rows ?: array( array() ) ) as $index => $row ) { xw_sf_render_builder_row( $index, $row, $sources ); } ?>
        </ol>
        <button class="button" type="button" data-xw-sf-add>+ <?php echo esc_html( xw_t( 'Añadir filtro', 'Add filter' ) ); ?></button>
        <template data-xw-sf-template><?php xw_sf_render_builder_row( 'INDEX', array(), $sources ); ?></template>
        <template data-xw-sf-term-template>
            <li class="xw-sf-term-card">
                <div class="xw-sf-term-card-header">
                    <strong data-term-name></strong>
                    <div class="xw-sf-term-order">
                        <button type="button" class="button" data-term-move="-1" aria-label="<?php echo esc_attr( xw_t( 'Mover antes', 'Move earlier' ) ); ?>">↑</button>
                        <button type="button" class="button" data-term-move="1" aria-label="<?php echo esc_attr( xw_t( 'Mover después', 'Move later' ) ); ?>">↓</button>
                    </div>
                </div>
                <label>
                    <?php echo esc_html( xw_t( 'Etiqueta', 'Label' ) ); ?>
                    <input type="text" maxlength="100" data-term-label>
                </label>
                <label data-term-color-field>
                    <?php echo esc_html( xw_t( 'Color hexadecimal', 'Hex color' ) ); ?>
                    <input type="text" maxlength="7" placeholder="#FF0000" pattern="#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})" data-term-color>
                </label>
            </li>
        </template>
        <aside class="xw-sf-connection">
            <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
            <div>
                <h3><?php echo esc_html( xw_t( 'Conecta el grupo en Elementor', 'Connect the group in Elementor' ) ); ?></h3>
                <ol>
                    <li><?php echo esc_html( xw_t( 'Publica el grupo y añade el widget Smart Filter en la página de tu Loop Grid.', 'Publish the group and add the Smart Filter widget to your Loop Grid page.' ) ); ?></li>
                    <li><?php echo esc_html( xw_t( 'En el widget Smart Filter, elige este grupo y busca la plantilla que usa tu cuadrícula en Plantilla de Loop. Si la usan varias cuadrículas, elige cuál filtrar.', 'In the Smart Filter widget, choose this group and search for the template your grid uses in Loop template. If several grids use it, choose which grid to filter.' ) ); ?></li>
                    <li><?php echo esc_html( xw_t( 'Para paginar con AJAX, usa números o anterior/siguiente y Recargar página en el Loop Grid. Activa Paginar el Loop Grid con AJAX en Smart Filter.', 'For AJAX pagination, use numbers or previous/next and Page Reload in the Loop Grid. Enable Paginate the Loop Grid with AJAX in Smart Filter.' ) ); ?></li>
                </ol>
            </div>
        </aside>
    </div>
    <?php
}

function xw_sf_render_builder_row( $index, $row, $sources ) {
    $name = 'xw_sf_rows[' . $index . ']';
    $source = $row['source'] ?? '';
    if ( $source && ! isset( $sources[ $source ] ) ) {
        $sources[ $source ] = sprintf( xw_t( '%s (ya no disponible)', '%s (no longer available)' ), $source );
    }
    ?>
    <li class="xw-sf-row" data-xw-sf-row>
        <input type="hidden" name="<?php echo esc_attr( $name . '[key]' ); ?>" value="<?php echo esc_attr( $row['key'] ?? '' ); ?>">
        <span class="xw-sf-row-number" aria-hidden="true"></span>
        <label>
            <?php echo esc_html( xw_t( 'Tipo de filtro', 'Filter type' ) ); ?>
            <select name="<?php echo esc_attr( $name . '[source]' ); ?>" data-xw-sf-source>
                <option value=""><?php echo esc_html( xw_t( 'Seleccionar', 'Select' ) ); ?></option>
                <?php foreach ( $sources as $key => $label ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" data-modes="<?php echo esc_attr( implode( ' ', xw_sf_source_modes( $key ) ) ); ?>" <?php echo xw_sf_is_terms( $key ) ? 'data-taxonomy ' : ''; ?><?php selected( $source, $key ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <?php echo esc_html( xw_t( 'Etiqueta en la tienda', 'Storefront label' ) ); ?>
            <input type="text" maxlength="100" name="<?php echo esc_attr( $name . '[label]' ); ?>" value="<?php echo esc_attr( $row['label'] ?? '' ); ?>" placeholder="<?php echo esc_attr( xw_t( 'Automática', 'Automatic' ) ); ?>">
        </label>
        <label>
            <?php echo esc_html( xw_t( 'Modo de visualización', 'Display mode' ) ); ?>
            <select name="<?php echo esc_attr( $name . '[mode]' ); ?>" data-xw-sf-mode>
                <?php foreach ( xw_sf_modes() as $mode => $label ) : ?>
                    <option value="<?php echo esc_attr( $mode ); ?>" <?php selected( $row['mode'] ?? '', $mode ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="xw-sf-row-actions">
            <?php foreach ( array( 'up' => array( 'arrow-up-alt2', xw_t( 'Subir', 'Move up' ) ), 'down' => array( 'arrow-down-alt2', xw_t( 'Bajar', 'Move down' ) ), 'remove' => array( 'trash', xw_t( 'Quitar filtro', 'Remove filter' ) ) ) as $action => list( $icon, $label ) ) : ?>
                <button type="button" class="button" data-xw-sf-action="<?php echo esc_attr( $action ); ?>" title="<?php echo esc_attr( $label ); ?>" aria-label="<?php echo esc_attr( $label ); ?>">
                    <span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="xw-sf-term-settings" data-xw-sf-term-settings <?php echo xw_sf_is_terms( $source ) ? '' : 'hidden'; ?>>
            <label>
                <?php echo esc_html( xw_t( 'Opciones visibles', 'Visible options' ) ); ?>
                <select name="<?php echo esc_attr( $name . '[limit_terms]' ); ?>" data-xw-sf-limit>
                    <option value="0"><?php echo esc_html( xw_t( 'Todos los términos', 'All terms' ) ); ?></option>
                    <option value="1" <?php selected( ! empty( $row['limit_terms'] ) ); ?>><?php echo esc_html( xw_t( 'Solo los seleccionados', 'Selected terms only' ) ); ?></option>
                </select>
            </label>
            <label data-xw-sf-term-picker <?php echo empty( $row['limit_terms'] ) ? 'hidden' : ''; ?>>
                <?php echo esc_html( xw_t( 'Términos para mostrar', 'Terms to display' ) ); ?>
                <select multiple data-xw-sf-terms>
                    <?php if ( xw_sf_is_terms( $source ) && ! empty( $row['terms'] ) ) :
                        $terms = get_terms( array( 'taxonomy' => $source, 'include' => $row['terms'], 'orderby' => 'include', 'hide_empty' => false ) );
                        foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) : ?>
                            <option value="<?php echo esc_attr( $term->term_id ); ?>" selected><?php echo esc_html( $term->name ); ?></option>
                        <?php endforeach;
                    endif; ?>
                </select>
                <input type="hidden" name="<?php echo esc_attr( $name . '[terms]' ); ?>" value="<?php echo esc_attr( wp_json_encode( $row['terms'] ?? array() ) ); ?>" data-xw-sf-term-ids>
            </label>
            <div class="xw-sf-term-display">
                <label data-xw-sf-layout-field>
                    <?php echo esc_html( xw_t( 'Orientación', 'Orientation' ) ); ?>
                    <select name="<?php echo esc_attr( $name . '[term_layout]' ); ?>">
                        <option value=""><?php echo esc_html( xw_t( 'Según el diseño', 'By design' ) ); ?></option>
                        <option value="vertical" <?php selected( $row['term_layout'] ?? '', 'vertical' ); ?>><?php echo esc_html( xw_t( 'Vertical', 'Vertical' ) ); ?></option>
                        <option value="horizontal" <?php selected( $row['term_layout'] ?? '', 'horizontal' ); ?>><?php echo esc_html( xw_t( 'Horizontal', 'Horizontal' ) ); ?></option>
                    </select>
                </label>
                <label>
                    <?php echo esc_html( xw_t( 'Permitir selección múltiple', 'Allow multiple selection' ) ); ?>
                    <select name="<?php echo esc_attr( $name . '[multiple]' ); ?>">
                        <option value="auto" <?php selected( ! array_key_exists( 'multiple', $row ) ); ?>><?php echo esc_html( xw_t( 'Según el diseño', 'By design' ) ); ?></option>
                        <option value="1" <?php selected( $row['multiple'] ?? null, true ); ?>><?php echo esc_html( xw_t( 'Sí', 'Yes' ) ); ?></option>
                        <option value="0" <?php selected( array_key_exists( 'multiple', $row ) && ! $row['multiple'] ); ?>><?php echo esc_html( xw_t( 'No', 'No' ) ); ?></option>
                    </select>
                </label>
                <label data-xw-sf-swatch-field>
                    <?php echo esc_html( xw_t( 'Columnas de colores', 'Swatch columns' ) ); ?>
                    <input type="number" min="0" max="12" name="<?php echo esc_attr( $name . '[columns]' ); ?>" value="<?php echo esc_attr( $row['columns'] ?? 0 ); ?>">
                </label>
                <label data-xw-sf-swatch-field>
                    <?php echo esc_html( xw_t( 'Etiquetas de los colores', 'Swatch labels' ) ); ?>
                    <select name="<?php echo esc_attr( $name . '[label_position]' ); ?>">
                        <?php foreach ( array( 'below' => xw_t( 'Debajo', 'Below' ), 'right' => xw_t( 'Al lado', 'Beside' ), 'hidden' => xw_t( 'Ocultas', 'Hidden' ) ) as $position => $text ) : ?>
                            <option value="<?php echo esc_attr( $position ); ?>" <?php selected( $row['label_position'] ?? 'below', $position ); ?>><?php echo esc_html( $text ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="xw-sf-term-custom" data-xw-sf-term-custom hidden>
                <strong><?php echo esc_html( xw_t( 'Personalizar y ordenar términos', 'Customize and order terms' ) ); ?></strong>
                <ol data-xw-sf-term-cards></ol>
            </div>
            <input type="hidden" name="<?php echo esc_attr( $name . '[term_custom]' ); ?>" value="<?php echo esc_attr( wp_json_encode( (object) ( $row['term_custom'] ?? array() ) ) ); ?>" data-xw-sf-custom-values>
        </div>
    </li>
    <?php
}
