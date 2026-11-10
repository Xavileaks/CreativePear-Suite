<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'elementor/widgets/register', static function( $manager ) {
    if ( ! xw_sf_enabled() ) { return; }
    require_once __DIR__ . '/widget.php';
    $manager->register( new XW_Smart_Filter_Widget() );
} );

function xw_sf_register_assets() {
    if ( ! xw_sf_enabled() ) { return; }
    xw_sf_asset( 'filter.css' );
    xw_sf_asset( 'filter.js', true );
}
add_action( 'wp_enqueue_scripts', 'xw_sf_register_assets' );
add_action( 'elementor/frontend/after_register_styles', 'xw_sf_register_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'xw_sf_register_assets' );

function xw_sf_form( $id, $rows, $settings ) {
    $sources = xw_sf_sources();
    $values = xw_sf_values( $id, $rows );
    $first_page = get_pagenum_link( 1, false );
    $base = explode( '?', $first_page, 2 );
    if ( is_singular() && ! is_front_page() && (int) get_query_var( 'page' ) > 1 ) {
        $base[0] = explode( '?', get_permalink( get_queried_object_id() ), 2 )[0];
    }
    parse_str( $base[1] ?? '', $query );
    unset( $query['page'], $query['paged'] );
    if ( isset( $query['xwsf'] ) && ! is_array( $query['xwsf'] ) ) { unset( $query['xwsf'] ); }
    unset( $query['xwsf'][ $id ] );
    foreach ( array_keys( $query ) as $key ) {
        if ( 0 === strpos( (string) $key, 'e-page-' ) ) { unset( $query[ $key ] ); }
    }
    $reset = $base[0] . ( $query ? '?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 ) : '' );
    $label = $settings['filter_heading'] ?: xw_t( 'Filtrar productos', 'Filter products' );
    ?>
    <form method="get" action="<?php echo esc_url( $base[0] ); ?>" class="xw-sf" aria-label="<?php echo esc_attr( $label ); ?>"
        data-xw-sf-form="<?php echo esc_attr( $id ); ?>" data-mode="<?php echo esc_attr( $settings['apply_mode'] ); ?>"
        data-updated="<?php echo esc_attr( xw_t( 'Resultados actualizados.', 'Results updated.' ) ); ?>"
        data-error="<?php echo esc_attr( xw_t( 'No se pudo actualizar el listado. Vuelve a pulsar Aplicar.', 'The listing could not be updated. Press Apply again.' ) ); ?>"
        data-empty="<?php echo esc_attr( xw_t( 'No hay productos que coincidan con estos filtros.', 'No products match these filters.' ) ); ?>">
        <?php echo wc_query_string_form_fields( $query, array(), '', true ); ?>
        <?php if ( $settings['filter_heading'] ) : ?><div class="xw-sf__heading"><?php echo esc_html( $settings['filter_heading'] ); ?></div><?php endif; ?>
        <div class="xw-sf__fields">
            <?php foreach ( $rows as $source => $row ) :
                $value = $values[ $source ] ?? '';
                $title = $row['label'] ?: $sources[ $source ];
                $name = 'xwsf[' . $id . '][' . $source . ']';
                $choices = xw_sf_choices( $source );
                if ( taxonomy_exists( $source ) && ! $choices ) {
                    $terms = get_terms( array( 'taxonomy' => $source, 'hide_empty' => true, 'orderby' => 'name' ) );
                    if ( is_wp_error( $terms ) || ! $terms ) { continue; }
                    foreach ( $terms as $term ) { $choices[ $term->term_id ] = $term->name; }
                }
                ?>
                <fieldset class="xw-sf__field">
                    <legend><?php echo esc_html( $title ); ?></legend>
                    <?php xw_sf_render_field( $row['mode'], $name, $title, $value, $choices, $settings ); ?>
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

function xw_sf_render_field( $mode, $name, $title, $value, $choices, $settings ) {
    if ( 'text' === $mode ) : ?>
        <input class="xw-sf__input" type="search" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>"
            aria-label="<?php echo esc_attr( $title ); ?>" placeholder="<?php echo esc_attr( $settings['search_placeholder'] ); ?>" maxlength="120">
    <?php elseif ( 'range' === $mode ) : ?>
        <div class="xw-sf__range">
            <?php foreach ( array( 'min' => xw_t( 'Desde', 'From' ), 'max' => xw_t( 'Hasta', 'To' ) ) as $bound => $label ) : ?>
                <label>
                    <span><?php echo esc_html( $label . ' (' . get_woocommerce_currency_symbol() . ')' ); ?></span>
                    <input class="xw-sf__input" type="number" min="0" max="99999999.999999" step="any" inputmode="decimal"
                        name="<?php echo esc_attr( $name . '[' . $bound . ']' ); ?>" value="<?php echo esc_attr( $value[ $bound ] ?? '' ); ?>">
                </label>
            <?php endforeach; ?>
        </div>
    <?php elseif ( 'select' === $mode ) : ?>
        <select class="xw-sf__input" name="<?php echo esc_attr( $name ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
            <option value=""><?php echo esc_html( $settings['all_label'] ); ?></option>
            <?php foreach ( $choices as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( (string) ( is_array( $value ) ? ( $value[0] ?? '' ) : $value ), (string) $key ); ?>><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
    <?php else :
        $type = 'radio' === $mode ? 'radio' : 'checkbox';
        if ( 'toggle' === $mode ) { $choices = array( 1 => $title ); }
        if ( 'radio' === $mode ) { $choices = array( '' => $settings['all_label'] ) + $choices; }
        $name .= 'checkbox' === $mode ? '[]' : '';
        $selected = array_map( 'strval', (array) $value );
        ?>
        <div class="xw-sf__choices">
            <?php foreach ( $choices as $key => $label ) : ?>
                <label class="xw-sf__choice">
                    <input type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $key ); ?>"
                        <?php checked( in_array( (string) $key, $selected, true ) || ( '' === $key && ! $selected ) ); ?>>
                    <span><?php echo esc_html( $label ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    <?php endif;
}
