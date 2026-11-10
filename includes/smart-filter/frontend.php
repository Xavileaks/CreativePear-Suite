<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'elementor/widgets/register', static function( $manager ) {
    if ( ! xw_sf_enabled() ) { return; }
    require_once __DIR__ . '/widget.php';
    $manager->register( new XW_Smart_Filter_Widget() );
} );

add_action( 'elementor/controls/register', static function( $manager ) {
    if ( ! xw_sf_enabled() ) { return; }
    require_once __DIR__ . '/loop-control.php';
    $manager->register( new XW_Smart_Filter_Loop_Control() );
} );

add_action( 'elementor/editor/after_enqueue_scripts', static function() {
    if ( ! xw_sf_enabled() ) { return; }
    xw_sf_asset( 'editor.js', true, array( 'elementor-editor' ) );
    wp_enqueue_script( 'xw-sf-editor.js' );
    wp_localize_script( 'xw-sf-editor.js', 'xwSmartFilterEditor', array(
        'automatic' => xw_t( 'Automático por grupo', 'Automatic by group' ),
        'saved' => xw_t( 'Conexión guardada', 'Saved connection' ),
        'missing' => xw_t( 'Loop Grid guardado no disponible', 'Saved Loop Grid unavailable' ),
    ) );
} );

function xw_sf_register_assets() {
    if ( ! xw_sf_enabled() ) { return; }
    xw_sf_asset( 'filter.css' );
    xw_sf_asset( 'filter.js', true );
}
add_action( 'wp_enqueue_scripts', 'xw_sf_register_assets' );
add_action( 'elementor/frontend/after_register_styles', 'xw_sf_register_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'xw_sf_register_assets' );

add_action( 'init', static function() {
    if ( xw_sf_enabled() && ( isset( $_GET['xwsf_refresh'] ) || isset( $_GET['xwsf'] ) ) && ! defined( 'DONOTCACHEPAGE' ) ) {
        define( 'DONOTCACHEPAGE', true );
    }
} );
add_action( 'template_redirect', static function() {
    if ( xw_sf_enabled() && isset( $_GET['xwsf_refresh'] ) ) { nocache_headers(); }
}, 0 );

function xw_sf_price_bounds() {
    static $bounds = null;
    if ( null !== $bounds ) { return $bounds; }
    global $wpdb;
    $visibility = wc_get_product_visibility_term_ids();
    $excluded = array( absint( $visibility['exclude-from-catalog'] ?? 0 ) );
    if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
        $excluded[] = absint( $visibility['outofstock'] ?? 0 );
    }
    $exclude_sql = implode( ',', $excluded );
    $row = $wpdb->get_row(
        "SELECT MIN(prices.min_price) AS minimum, MAX(prices.max_price) AS maximum
         FROM {$wpdb->wc_product_meta_lookup} prices
         INNER JOIN {$wpdb->posts} products ON products.ID = prices.product_id
         WHERE products.post_type = 'product' AND products.post_status = 'publish' AND products.post_password = ''
         AND NOT EXISTS (SELECT 1 FROM {$wpdb->term_relationships} visibility
                         WHERE visibility.object_id = products.ID AND visibility.term_taxonomy_id IN ($exclude_sql))",
        ARRAY_A
    );
    $bounds = array();
    if ( isset( $row['minimum'], $row['maximum'] ) ) {
        foreach ( array( 'min' => 'minimum', 'max' => 'maximum' ) as $key => $column ) {
            $bounds[ $key ] = wc_format_decimal( $row[ $column ], 6, true );
        }
    }
    return $bounds;
}

function xw_sf_form( $id, $rows, $settings ) {
    $sources = xw_sf_sources();
    $values = xw_sf_values( $id, $rows );
    $first_page = get_pagenum_link( 1, false );
    $base = explode( '?', $first_page, 2 );
    if ( is_singular() && ! is_front_page() && (int) get_query_var( 'page' ) > 1 ) {
        $base[0] = explode( '?', get_permalink( get_queried_object_id() ), 2 )[0];
    }
    parse_str( $base[1] ?? '', $query );
    unset( $query['page'], $query['paged'], $query['xwsf_refresh'] );
    if ( isset( $query['xwsf'] ) && ! is_array( $query['xwsf'] ) ) { unset( $query['xwsf'] ); }
    unset( $query['xwsf'][ $id ] );
    if ( isset( $query['xwsf_grid'] ) && ! is_array( $query['xwsf_grid'] ) ) { unset( $query['xwsf_grid'] ); }
    $target = xw_sf_target( $settings['grid_target'] ?? '' );
    if ( '' === ( $settings['grid_target'] ?? '' ) ) {
        $target = xw_sf_grid_id( ltrim( trim( (string) ( $settings['grid_id'] ?? '' ) ), '#' ) );
    }
    unset( $query['xwsf_grid'][ $id ] );
    if ( $target ) { $query['xwsf_grid'][ $id ] = $target; }
    foreach ( array_keys( $query ) as $key ) {
        if ( 0 === strpos( (string) $key, 'e-page-' ) ) { unset( $query[ $key ] ); }
    }
    $reset = $base[0] . ( $query ? '?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 ) : '' );
    $label = $settings['filter_heading'] ?: xw_t( 'Filtrar productos', 'Filter products' );
    ?>
    <form method="get" action="<?php echo esc_url( $base[0] ); ?>" class="xw-sf" aria-label="<?php echo esc_attr( $label ); ?>"
        data-xw-sf-form="<?php echo esc_attr( $id ); ?>" data-mode="<?php echo esc_attr( $settings['apply_mode'] ); ?>" data-grid-id="<?php echo esc_attr( $target ); ?>"
        data-pagination="<?php echo esc_attr( $settings['ajax_pagination'] ?? '' ); ?>"
        data-updated="<?php echo esc_attr( xw_t( 'Resultados actualizados.', 'Results updated.' ) ); ?>"
        data-error="<?php echo esc_attr( xw_t( 'No se pudo actualizar el listado. Vuelve a pulsar Aplicar.', 'The listing could not be updated. Press Apply again.' ) ); ?>"
        data-unlinked="<?php echo esc_attr( xw_t( 'No se encontró una cuadrícula conectada. Revisa el ID del Loop Grid en Elementor.', 'A connected grid was not found. Check the Loop Grid ID in Elementor.' ) ); ?>"
        data-empty="<?php echo esc_attr( xw_t( 'No hay productos que coincidan con estos filtros.', 'No products match these filters.' ) ); ?>">
        <?php echo wc_query_string_form_fields( $query, array(), '', true ); ?>
        <?php if ( $settings['filter_heading'] ) : ?><div class="xw-sf__heading"><?php echo esc_html( $settings['filter_heading'] ); ?></div><?php endif; ?>
        <?php if ( 'yes' === ( $settings['result_count'] ?? '' ) ) : ?>
            <p class="xw-sf__count" hidden data-one="<?php echo esc_attr( xw_t( '%s producto', '%s product' ) ); ?>"
                data-many="<?php echo esc_attr( xw_t( '%s productos', '%s products' ) ); ?>"></p>
        <?php endif; ?>
        <?php if ( 'yes' === ( $settings['active_filters'] ?? '' ) ) : ?>
            <div class="xw-sf__chips" role="group" hidden aria-label="<?php echo esc_attr( xw_t( 'Filtros activos', 'Active filters' ) ); ?>"
                data-remove="<?php echo esc_attr( xw_t( 'Quitar filtro: %s', 'Remove filter: %s' ) ); ?>"></div>
        <?php endif; ?>
        <div class="xw-sf__fields">
            <?php foreach ( $rows as $key => $row ) :
                $source = $row['source'];
                $value = $values[ $key ] ?? '';
                $title = $row['label'] ?: $sources[ $source ];
                $name = 'xwsf[' . $id . '][' . $key . ']';
                $choices = xw_sf_choices( $source );
                if ( taxonomy_exists( $source ) && ! $choices ) {
                    $args = array( 'taxonomy' => $source, 'hide_empty' => true, 'orderby' => 'name' );
                    if ( $row['limit_terms'] ) {
                        if ( ! $row['terms'] ) { continue; }
                        $args = array( 'include' => $row['terms'], 'orderby' => 'include', 'hide_empty' => false ) + $args;
                    }
                    $terms = get_terms( $args );
                    if ( is_wp_error( $terms ) || ! $terms ) { continue; }
                    foreach ( $terms as $term ) {
                        $choices[ $term->term_id ] = ( $row['term_custom'][ $term->term_id ]['label'] ?? '' ) ?: $term->name;
                    }
                }
                ?>
                <fieldset class="xw-sf__field">
                    <legend><?php echo esc_html( $title ); ?></legend>
                    <?php xw_sf_render_field( $row, $name, $title, $value, $choices, $settings ); ?>
                </fieldset>
            <?php endforeach; ?>
        </div>
        <div class="xw-sf__actions">
            <button class="xw-sf__apply" type="submit"><?php echo esc_html( $settings['apply_label'] ); ?></button>
            <a class="xw-sf__reset" href="<?php echo esc_url( $reset ); ?>"><?php echo esc_html( $settings['reset_label'] ); ?></a>
        </div>
        <p class="xw-sf__status" role="status"></p>
    </form>
    <?php
}

function xw_sf_render_field( $row, $name, $title, $value, $choices, $settings ) {
    $mode = $row['mode'];
    $multiple = xw_sf_is_terms( $row['source'] ) && $row['multiple'];
    $selected = array_map( 'strval', (array) $value );
    if ( 'text' === $mode ) : ?>
        <input class="xw-sf__input" type="search" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>"
            aria-label="<?php echo esc_attr( $title ); ?>" placeholder="<?php echo esc_attr( $settings['search_placeholder'] ); ?>" maxlength="120">
    <?php elseif ( 'slider' === $mode ) :
        xw_sf_render_slider( $name, $value );
    elseif ( 'range' === $mode ) :
        ?>
        <div class="xw-sf__range">
                <?php foreach ( array( 'min' => xw_t( 'Desde', 'From' ), 'max' => xw_t( 'Hasta', 'To' ) ) as $bound => $label ) : ?>
                    <label>
                        <span><?php echo esc_html( $label . ' (' . get_woocommerce_currency_symbol() . ')' ); ?></span>
                        <input class="xw-sf__input" type="number" min="0" step="any" inputmode="decimal"
                            name="<?php echo esc_attr( $name . '[' . $bound . ']' ); ?>" value="<?php echo esc_attr( $value[ $bound ] ?? '' ); ?>">
                    </label>
                <?php endforeach; ?>
        </div>
    <?php elseif ( 'select' === $mode ) : ?>
        <select class="xw-sf__input" name="<?php echo esc_attr( $name . ( $multiple ? '[]' : '' ) ); ?>" aria-label="<?php echo esc_attr( $title ); ?>" <?php if ( $multiple ) : ?>multiple size="<?php echo esc_attr( min( 5, count( $choices ) ) ); ?>"<?php endif; ?>>
            <?php if ( ! $multiple ) : ?><option value=""><?php echo esc_html( $settings['all_label'] ); ?></option><?php endif; ?>
            <?php foreach ( $choices as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( in_array( (string) $key, $selected, true ) ); ?>><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
    <?php else :
        $type = $multiple || 'toggle' === $mode ? 'checkbox' : 'radio';
        $swatches = 'swatches' === $mode;
        if ( 'toggle' === $mode ) { $choices = array( 1 => $title ); }
        if ( 'radio' === $type && in_array( $mode, array( 'radio', 'checkbox' ), true ) ) { $choices = array( '' => $settings['all_label'] ) + $choices; }
        $name .= $multiple ? '[]' : '';
        $layout = $row['term_layout'] ?: ( in_array( $mode, array( 'swatches', 'buttons' ), true ) ? 'horizontal' : '' );
        ?>
        <div class="xw-sf__choices <?php echo esc_attr( 'is-' . $mode . ( $layout ? ' is-' . $layout : '' ) ); ?>"
            <?php if ( $swatches && $row['columns'] ) : ?>style="--xw-sf-columns:<?php echo esc_attr( $row['columns'] ); ?>"<?php endif; ?>>
            <?php foreach ( $choices as $key => $label ) : ?>
                <label class="xw-sf__choice<?php echo $swatches ? ' is-label-' . esc_attr( $row['label_position'] ) : ''; ?>"
                    <?php if ( $swatches ) : ?>style="--xw-sf-swatch:<?php echo esc_attr( ( $row['term_custom'][ $key ]['color'] ?? '' ) ?: '#d7dce5' ); ?>"<?php else : ?>title="<?php echo esc_attr( $label ); ?>"<?php endif; ?>>
                    <input type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $key ); ?>"
                        <?php checked( in_array( (string) $key, $selected, true ) || ( '' === $key && ! $selected ) ); ?>>
                    <?php if ( $swatches ) : ?><span class="xw-sf__swatch" aria-hidden="true"></span><?php endif; ?>
                    <span class="xw-sf__choice-text"><?php echo esc_html( $label ); ?></span>
                    <?php if ( $swatches ) : ?><span class="xw-sf__tooltip" aria-hidden="true"><?php echo esc_html( $label ); ?></span><?php endif; ?>
                </label>
            <?php endforeach; ?>
        </div>
    <?php endif;
}

function xw_sf_render_slider( $name, $value ) {
    $bounds = xw_sf_price_bounds();
    if ( ! $bounds ) { return; }
    if ( (float) $bounds['min'] === (float) $bounds['max'] ) {
        echo wp_kses_post( wc_price( $bounds['min'] ) );
        return;
    }
    $precision = max( wc_get_price_decimals(), 2, strlen( explode( '.', $bounds['min'], 2 )[1] ?? '' ), strlen( explode( '.', $bounds['max'], 2 )[1] ?? '' ) );
    $step = wc_format_decimal( pow( 10, -min( 6, $precision ) ), 6, true );
    ?>
    <div class="xw-sf__slider" data-xw-sf-price dir="ltr" hidden data-currency="<?php echo esc_attr( html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) ); ?>"
        data-price-format="<?php echo esc_attr( html_entity_decode( wp_strip_all_tags( get_woocommerce_price_format() ), ENT_QUOTES, 'UTF-8' ) ); ?>"
        data-decimals="<?php echo esc_attr( min( 6, wc_get_price_decimals() ) ); ?>" data-decimal="<?php echo esc_attr( wc_get_price_decimal_separator() ); ?>"
        data-thousands="<?php echo esc_attr( wc_get_price_thousand_separator() ); ?>">
        <span class="xw-sf__track" aria-hidden="true"></span>
        <?php foreach ( array( 'min' => xw_t( 'Precio mínimo', 'Minimum price' ), 'max' => xw_t( 'Precio máximo', 'Maximum price' ) ) as $bound => $label ) : ?>
            <input type="range" aria-label="<?php echo esc_attr( $label ); ?>" data-bound="<?php echo esc_attr( $bound ); ?>"
                min="<?php echo esc_attr( $bounds['min'] ); ?>" max="<?php echo esc_attr( $bounds['max'] ); ?>"
                step="<?php echo esc_attr( $step ); ?>" value="<?php echo esc_attr( ( $value[ $bound ] ?? '' ) !== '' ? $value[ $bound ] : $bounds[ $bound ] ); ?>">
            <input type="hidden" name="<?php echo esc_attr( $name . '[' . $bound . ']' ); ?>" data-price-bound="<?php echo esc_attr( $label ); ?>" value="<?php echo esc_attr( $value[ $bound ] ?? '' ); ?>">
            <span class="xw-sf__slider-value is-<?php echo esc_attr( $bound ); ?>" aria-hidden="true"></span>
        <?php endforeach; ?>
    </div>
    <?php
}
