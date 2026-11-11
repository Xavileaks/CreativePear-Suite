<?php
/** Reusable WooCommerce filters connected to Elementor product Loop Grids. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_sf_enabled() {
    return class_exists( 'WooCommerce' ) && xw_feature_enabled( 'woocommerce_smart_filter' );
}

add_action( 'init', static function() {
    if ( ! xw_sf_enabled() ) { return; }
    $capabilities = array_fill_keys(
        array( 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'create_posts' ),
        'manage_woocommerce'
    );
    $capabilities += array( 'edit_post' => 'edit_xw_sf_group', 'read_post' => 'read_xw_sf_group', 'delete_post' => 'delete_xw_sf_group' );
    register_post_type( 'xw_smart_filter', array(
        'labels' => array(
            'name' => 'Smart Filter',
            'singular_name' => xw_t( 'Grupo de filtros', 'Filter group' ),
            'add_new' => xw_t( 'Añadir grupo', 'Add group' ),
            'add_new_item' => xw_t( 'Nuevo grupo de filtros', 'New filter group' ),
            'edit_item' => xw_t( 'Editar grupo de filtros', 'Edit filter group' ),
            'search_items' => xw_t( 'Buscar grupos', 'Search groups' ),
            'not_found' => xw_t( 'Aún no hay grupos de filtros.', 'There are no filter groups yet.' ),
        ),
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-filter',
        'supports' => array( 'title' ),
        'capabilities' => $capabilities,
        'map_meta_cap' => true,
        'rewrite' => false,
        'query_var' => false,
    ) );
}, 20 );

function xw_sf_group( $id ) {
    if ( ! $id ) { return array(); }
    $post = get_post( $id );
    if ( ! $post || 'xw_smart_filter' !== $post->post_type || 'publish' !== $post->post_status || '' !== $post->post_password ) {
        return array();
    }
    return xw_sf_rows( get_post_meta( $post->ID, '_xw_sf_rows', true ) );
}

function xw_sf_sources() {
    $sources = array(
        'search' => xw_t( 'Búsqueda', 'Search' ),
        'price'  => xw_t( 'Precio', 'Price' ),
        'stock'  => xw_t( 'Disponibilidad', 'Availability' ),
        'sale'   => xw_t( 'En oferta', 'On sale' ),
        'rating' => xw_t( 'Valoración', 'Rating' ),
        'sort'   => xw_t( 'Ordenar por', 'Sort by' ),
    );
    foreach ( get_object_taxonomies( 'product', 'objects' ) as $taxonomy ) {
        if ( $taxonomy->public && 'product_visibility' !== $taxonomy->name ) {
            $sources[ $taxonomy->name ] = $taxonomy->labels->singular_name;
        }
    }
    // Global attributes can be filterable even when their archives are disabled.
    foreach ( wc_get_attribute_taxonomies() as $attribute ) {
        $name = wc_attribute_taxonomy_name( $attribute->attribute_name );
        if ( taxonomy_exists( $name ) ) {
            $sources[ $name ] = sprintf( xw_t( 'Atributo: %s', 'Attribute: %s' ), $attribute->attribute_label ?: $attribute->attribute_name );
        }
    }
    return $sources;
}

function xw_sf_modes() {
    return array(
        'checkbox' => xw_t( 'Casillas', 'Checkboxes' ),
        'select'   => xw_t( 'Desplegable', 'Dropdown' ),
        'radio'    => xw_t( 'Selección única', 'Radio buttons' ),
        'buttons'  => xw_t( 'Botones de texto', 'Text buttons' ),
        'swatches' => xw_t( 'Muestras de color', 'Color swatches' ),
        'text'     => xw_t( 'Campo de búsqueda', 'Search field' ),
        'range'    => xw_t( 'Mínimo y máximo', 'Minimum and maximum' ),
        'slider'   => xw_t( 'Barra de precio', 'Price slider' ),
        'toggle'   => xw_t( 'Casilla única', 'Single checkbox' ),
    );
}

function xw_sf_source_modes( $source ) {
    $specific = array(
        'search' => array( 'text' ),
        'price'  => array( 'range', 'slider' ),
        'sale'   => array( 'toggle' ),
        'sort'   => array( 'select', 'radio' ),
        'rating' => array( 'select', 'radio' ),
        'stock'  => array( 'select', 'radio' ),
    );
    return $specific[ $source ] ?? array( 'checkbox', 'select', 'radio', 'buttons', 'swatches' );
}

function xw_sf_rows( $input, $keep_missing = false ) {
    $rows = array();
    $sources = xw_sf_sources();
    $single = array();
    foreach ( array_slice( (array) $input, 0, 20, true ) as $index => $row ) {
        if ( ! is_array( $row ) || ! is_string( $row['source'] ?? null ) ) { continue; }
        $source = sanitize_key( $row['source'] );
        if ( '' === $source || isset( $single[ $source ] ) || ( ! isset( $sources[ $source ] ) && ! $keep_missing ) ) { continue; }
        if ( ! xw_sf_is_terms( $source ) && isset( $sources[ $source ] ) ) { $single[ $source ] = true; }
        $key = $row['key'] ?? $index;
        if ( ! is_string( $key ) || ( $key !== $source && ! preg_match( '/^f_[a-f0-9]{32}$/D', $key ) ) || isset( $rows[ $key ] ) ) {
            $key = isset( $rows[ $source ] ) ? 'f_' . str_replace( '-', '', wp_generate_uuid4() ) : $source;
        }
        $modes = xw_sf_source_modes( $source );
        $mode = in_array( $row['mode'] ?? '', $modes, true ) ? $row['mode'] : $modes[0];
        $terms = $row['terms'] ?? array();
        if ( is_string( $terms ) ) { $terms = json_decode( $terms, true ); }
        $terms = empty( $row['limit_terms'] ) ? array() : xw_sf_term_ids( $terms );
        $custom = $row['term_custom'] ?? array();
        if ( is_string( $custom ) ) { $custom = json_decode( $custom, true ); }
        $custom = is_array( $custom ) ? $custom : array();
        $term_custom = array();
        foreach ( $terms as $term_id ) {
            $option = $custom[ $term_id ] ?? null;
            if ( ! is_array( $option ) ) { continue; }
            $label = is_string( $option['label'] ?? null ) ? sanitize_text_field( $option['label'] ) : '';
            $color = is_string( $option['color'] ?? null ) ? sanitize_hex_color( $option['color'] ) : '';
            if ( '' !== $label || $color ) { $term_custom[ $term_id ] = array( 'label' => $label, 'color' => $color ?: '' ); }
        }
        $layout = $row['term_layout'] ?? '';
        $placement = $row['label_position'] ?? 'below';
        $rows[ $key ] = array(
            'key'    => $key,
            'source' => $source,
            'label'  => is_string( $row['label'] ?? null ) ? sanitize_text_field( $row['label'] ) : '',
            'mode'   => $mode,
            'terms'  => $terms,
            'limit_terms' => ! empty( $row['limit_terms'] ),
            'term_custom' => $term_custom,
            'term_layout' => in_array( $layout, array( 'vertical', 'horizontal' ), true ) ? $layout : '',
            'multiple' => 'auto' === ( $row['multiple'] ?? 'auto' ) ? 'checkbox' === $mode : ! empty( $row['multiple'] ),
            'label_position' => in_array( $placement, array( 'below', 'right', 'hidden' ), true ) ? $placement : 'below',
        );
    }
    return $rows;
}

function xw_sf_is_terms( $source ) {
    return ! in_array( $source, array( 'search', 'price', 'stock', 'sale', 'rating', 'sort' ), true ) && taxonomy_exists( $source );
}

function xw_sf_term_ids( $input, $limit = 100 ) {
    $ids = array();
    foreach ( array_slice( (array) $input, 0, $limit ) as $term ) {
        if ( ( is_string( $term ) || is_int( $term ) ) && preg_match( '/^[1-9][0-9]{0,17}$/D', (string) $term ) ) {
            $ids[] = (int) $term;
        }
    }
    return array_values( array_unique( $ids ) );
}

function xw_sf_group_options( $query_ids = false ) {
    $groups = get_posts( array(
        'post_type' => 'xw_smart_filter', 'post_status' => 'publish', 'has_password' => false,
        'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC',
    ) );
    $options = array( '' => xw_t( 'Seleccionar grupo', 'Select a group' ) );
    foreach ( $groups as $group ) {
        if ( ! xw_sf_group( $group->ID ) ) { continue; }
        $options[ $query_ids ? xw_sf_query_id( $group->ID ) : $group->ID ] = $group->post_title ?: sprintf( xw_t( 'Grupo #%d', 'Group #%d' ), $group->ID );
    }
    return $options;
}

function xw_sf_asset( $file, $script = false, $dependencies = array() ) {
    $path = 'assets/smart-filter/' . $file;
    $url = plugin_dir_url( XW_FUNCTIONS_FILE ) . $path;
    $version = filemtime( plugin_dir_path( XW_FUNCTIONS_FILE ) . $path );
    if ( $script ) {
        wp_register_script( 'xw-sf-' . $file, $url, $dependencies, $version, true );
    } else {
        wp_register_style( 'xw-sf-' . $file, $url, array(), $version );
    }
}

require_once __DIR__ . '/smart-filter/query.php';
require_once __DIR__ . '/smart-filter/frontend.php';
if ( is_admin() ) {
    require_once __DIR__ . '/smart-filter/admin.php';
}
