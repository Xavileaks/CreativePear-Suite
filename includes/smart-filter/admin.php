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
    xw_sf_asset( 'admin.js', true );
    wp_enqueue_style( 'xw-sf-admin.css' );
    wp_enqueue_script( 'xw-sf-admin.js' );
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
        <aside class="xw-sf-connection">
            <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
            <div>
                <h3><?php echo esc_html( xw_t( 'Conecta el grupo en Elementor', 'Connect the group in Elementor' ) ); ?></h3>
                <ol>
                    <li><?php echo esc_html( xw_t( 'Publica el grupo y añade el widget Smart Filter en la página de tu Loop Grid.', 'Publish the group and add the Smart Filter widget to your Loop Grid page.' ) ); ?></li>
                    <li><?php echo esc_html( xw_t( 'Selecciona este grupo en el widget y en Loop Grid → Consulta → Smart Filter. El Loop Grid debe usar una plantilla de Productos.', 'Select this group in the widget and in Loop Grid → Query → Smart Filter. The Loop Grid must use a Products template.' ) ); ?></li>
                    <li><?php echo esc_html( xw_t( 'Si usas paginación, elige números o anterior/siguiente y el tipo de carga Recargar página.', 'For pagination, use numbers or previous/next and the Page Reload load type.' ) ); ?></li>
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
        <span class="xw-sf-row-number" aria-hidden="true"></span>
        <label>
            <?php echo esc_html( xw_t( 'Tipo de filtro', 'Filter type' ) ); ?>
            <select name="<?php echo esc_attr( $name . '[source]' ); ?>" data-xw-sf-source>
                <option value=""><?php echo esc_html( xw_t( 'Seleccionar', 'Select' ) ); ?></option>
                <?php foreach ( $sources as $key => $label ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" data-modes="<?php echo esc_attr( implode( ' ', xw_sf_source_modes( $key ) ) ); ?>" <?php selected( $source, $key ); ?>><?php echo esc_html( $label ); ?></option>
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
    </li>
    <?php
}
