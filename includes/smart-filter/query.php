<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_sf_query_id( $id ) {
    return 'xw_smart_filter_' . $id;
}

function xw_sf_grid_group( $widget ) {
    if ( 'loop-grid' !== $widget->get_name() || 'product' !== $widget->get_settings( '_skin' ) ) { return 0; }
    $query_id = $widget->get_settings( 'product_query_query_id' );
    return is_string( $query_id ) && preg_match( '/^xw_smart_filter_([1-9][0-9]*)$/D', $query_id, $match ) ? absint( $match[1] ) : 0;
}

function xw_sf_choices( $source ) {
    switch ( $source ) {
        case 'stock':
            return array(
                'instock' => xw_t( 'En stock', 'In stock' ),
                'onbackorder' => xw_t( 'Disponible para reserva', 'Available on backorder' ),
                'outofstock' => xw_t( 'Agotado', 'Out of stock' ),
            );
        case 'sort':
            return array(
                'newest' => xw_t( 'Más recientes', 'Newest' ),
                'price_asc' => xw_t( 'Precio de menor a mayor', 'Price low to high' ),
                'price_desc' => xw_t( 'Precio de mayor a menor', 'Price high to low' ),
                'popular' => xw_t( 'Más vendidos', 'Best selling' ),
                'rating' => xw_t( 'Mejor valorados', 'Top rated' ),
                'title' => xw_t( 'Nombre de A a Z', 'Name A to Z' ),
            );
        case 'rating':
            return array( 4 => xw_t( '4 estrellas o más', '4 stars and above' ), 3 => xw_t( '3 estrellas o más', '3 stars and above' ), 2 => xw_t( '2 estrellas o más', '2 stars and above' ), 1 => xw_t( '1 estrella o más', '1 star and above' ) );
    }
    return array();
}

function xw_sf_amount( $value ) {
    if ( ! is_string( $value ) || ! preg_match( '/^\d{1,8}(?:[.,]\d{1,6})?$/D', $value ) ) { return ''; }
    return str_replace( ',', '.', $value );
}

function xw_sf_values( $id, $rows ) {
    $raw = $_GET['xwsf'][ $id ] ?? array();
    $raw = is_array( $raw ) ? wp_unslash( $raw ) : array();
    $values = array();
    foreach ( $rows as $source => $row ) {
        $input = $raw[ $source ] ?? '';
        if ( 'price' === $source ) {
            $values[ $source ] = array(
                'min' => xw_sf_amount( $input['min'] ?? '' ),
                'max' => xw_sf_amount( $input['max'] ?? '' ),
            );
        } elseif ( taxonomy_exists( $source ) && ! in_array( $source, array( 'search', 'sale', 'stock', 'sort', 'rating' ), true ) ) {
            $terms = array();
            $limit = 'checkbox' === $row['mode'] ? 100 : 1;
            foreach ( array_slice( (array) $input, 0, $limit ) as $term ) {
                if ( is_string( $term ) && preg_match( '/^[1-9][0-9]{0,17}$/D', $term ) ) {
                    $terms[] = (int) $term;
                }
            }
            $values[ $source ] = array_values( array_unique( $terms ) );
        } elseif ( is_string( $input ) ) {
            if ( 'search' === $source ) {
                $values[ $source ] = wp_html_excerpt( sanitize_text_field( $input ), 120 );
            } elseif ( 'sale' === $source && '1' === $input ) {
                $values[ $source ] = '1';
            } elseif ( array_key_exists( $input, xw_sf_choices( $source ) ) ) {
                $values[ $source ] = $input;
            }
        }
    }
    return $values;
}

function xw_sf_filter_query( $query, $values ) {
    $tax = array();
    $lookup = array();
    foreach ( $values as $source => $value ) {
        if ( '' === $value || array() === $value ) { continue; }
        switch ( $source ) {
            case 'search':
                $query->set( 's', trim( $query->get( 's' ) . ' ' . $value ) );
                break;
            case 'price':
                if ( '' !== $value['min'] || '' !== $value['max'] ) { $lookup['price'] = $value; }
                break;
            case 'sort':
                if ( 'newest' === $value ) {
                    $query->set( 'orderby', array( 'date' => 'DESC', 'ID' => 'DESC' ) );
                } elseif ( 'title' === $value ) {
                    $query->set( 'orderby', array( 'title' => 'ASC', 'ID' => 'ASC' ) );
                } else {
                    $lookup['sort'] = $value;
                }
                break;
            case 'stock':
            case 'rating':
            case 'sale':
                $lookup[ $source ] = $value;
                break;
            default:
                $tax[] = array( 'taxonomy' => $source, 'field' => 'term_id', 'terms' => $value, 'include_children' => is_taxonomy_hierarchical( $source ) );
                break;
        }
    }
    if ( $tax ) {
        $existing = $query->get( 'tax_query' );
        if ( is_array( $existing ) && $existing ) { array_unshift( $tax, $existing ); }
        $query->set( 'tax_query', array( 'relation' => 'AND' ) + $tax );
    }
    if ( $lookup ) {
        $query->set( 'suppress_filters', false );
        $query->set( '_xw_sf_lookup', $lookup );
    }
}

// Match WooCommerce catalog range semantics for variable products.
add_filter( 'posts_clauses', static function( $clauses, $query ) {
    $lookup = $query->get( '_xw_sf_lookup' );
    if ( ! is_array( $lookup ) || ! $lookup || ! xw_sf_enabled() ) { return $clauses; }
    global $wpdb;
    $clauses['join'] .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} AS xw_sf_product ON {$wpdb->posts}.ID = xw_sf_product.product_id ";
    if ( isset( $lookup['price'] ) ) {
        $min = xw_sf_amount( $lookup['price']['min'] ?? '' );
        $max = xw_sf_amount( $lookup['price']['max'] ?? '' );
        if ( '' !== $min && '' !== $max && (float) $min > (float) $max ) { list( $min, $max ) = array( $max, $min ); }
        if ( '' !== $min ) { $clauses['where'] .= $wpdb->prepare( ' AND xw_sf_product.max_price >= %f', $min ); }
        if ( '' !== $max ) { $clauses['where'] .= $wpdb->prepare( ' AND xw_sf_product.min_price <= %f', $max ); }
    }
    if ( isset( $lookup['stock'] ) ) { $clauses['where'] .= $wpdb->prepare( ' AND xw_sf_product.stock_status = %s', $lookup['stock'] ); }
    if ( isset( $lookup['rating'] ) ) { $clauses['where'] .= $wpdb->prepare( ' AND xw_sf_product.average_rating >= %d', $lookup['rating'] ); }
    if ( isset( $lookup['sale'] ) ) { $clauses['where'] .= ' AND xw_sf_product.onsale = 1'; }
    $sort = array(
        'price_asc'  => 'xw_sf_product.min_price ASC, xw_sf_product.product_id ASC',
        'price_desc' => 'xw_sf_product.max_price DESC, xw_sf_product.product_id DESC',
        'popular'    => 'xw_sf_product.total_sales DESC, xw_sf_product.product_id DESC',
        'rating'     => 'xw_sf_product.average_rating DESC, xw_sf_product.rating_count DESC, xw_sf_product.product_id DESC',
    );
    $clauses['orderby'] = $sort[ $lookup['sort'] ?? '' ] ?? $clauses['orderby'];
    return $clauses;
}, 100, 2 );

add_action( 'elementor/element/loop-grid/section_query/before_section_end', static function( $widget ) {
    if ( ! xw_sf_enabled() ) { return; }
    // The product skin reserves this query key internally but does not expose a control for it.
    if ( $widget->get_controls( 'product_query_query_id' ) ) { return; }
    $widget->add_control( 'product_query_query_id', array(
        'label' => 'Smart Filter',
        'type' => \Elementor\Controls_Manager::SELECT,
        'options' => xw_sf_group_options( true ),
        'condition' => array( '_skin' => 'product' ),
        'description' => xw_t( 'Selecciona el mismo grupo en el widget Smart Filter de esta página.', 'Select the same group in the Smart Filter widget on this page.' ),
    ) );
} );

add_action( 'elementor/frontend/widget/before_render', static function( $widget ) {
    if ( ! xw_sf_enabled() ) { return; }
    $id = xw_sf_grid_group( $widget );
    if ( ! $id || ! ( $rows = xw_sf_group( $id ) ) ) { return; }
    $widget->add_render_attribute( '_wrapper', 'data-xw-sf-grid', $id );
    $hook = 'elementor/query/' . xw_sf_query_id( $id );
    if ( ! has_action( $hook, 'xw_sf_elementor_query' ) ) { add_action( $hook, 'xw_sf_elementor_query', 20, 2 ); }
} );

function xw_sf_elementor_query( $query, $widget ) {
    $id = xw_sf_grid_group( $widget );
    if ( ! $id || ! in_array( 'product', (array) $query->get( 'post_type' ), true ) ) { return; }
    xw_sf_filter_query( $query, xw_sf_values( $id, xw_sf_group( $id ) ) );
}

add_filter( 'elementor/frontend/widget/should_render', static function( $render, $widget ) {
    return $render || ( xw_sf_enabled() && xw_sf_group( xw_sf_grid_group( $widget ) ) );
}, 1, 2 );

// Elementor's previous/next links do not copy custom filter parameters.
add_filter( 'elementor/posts/wp_link_page', static function( $url ) {
    $groups = $_GET['xwsf'] ?? array();
    if ( ! is_array( $groups ) || ! $groups || ! xw_sf_enabled() ) { return $url; }
    $state = array();
    foreach ( array_slice( array_keys( $groups ), 0, 20 ) as $id ) {
        if ( ! is_int( $id ) || $id < 1 ) { continue; }
        foreach ( xw_sf_values( $id, xw_sf_group( $id ) ) as $source => $value ) {
            if ( is_array( $value ) ) {
                $value = array_filter( $value, static function( $item ) { return '' !== $item; } );
            }
            if ( '' !== $value && array() !== $value ) { $state[ $id ][ $source ] = $value; }
        }
    }
    return $state ? add_query_arg( 'xwsf', urlencode_deep( $state ), $url ) : $url;
} );
