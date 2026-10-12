<?php
/** Optional quantity controls. Uses WooCommerce's input, not a template override. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_quantity_defaults() {
    return array(
        'button_background' => '#FFFFFF', 'button_border' => '#DCDCDE', 'button_color' => '#1D2327',
        'hover_background' => '#F0F0F1', 'hover_border' => '#8C8F94', 'hover_color' => '#1D2327',
        'border_width' => 1, 'border_radius' => 4, 'button_width' => 44,
        'input_width' => 60, 'input_height' => 44, 'input_border_radius' => 4, 'gap' => 4, 'button_shape' => 'normal',
        'input_background' => '#FFFFFF', 'input_border' => '#DCDCDE', 'input_color' => '#1D2327',
    );
}

function xw_quantity_sanitize( $input ) {
    $defaults = xw_quantity_defaults();
    $input = is_array( $input ) ? $input : array();
    $result = $defaults;
    foreach ( array( 'button_background', 'button_border', 'button_color', 'hover_background', 'hover_border', 'hover_color', 'input_background', 'input_border', 'input_color' ) as $key ) {
        $result[ $key ] = isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? ( sanitize_hex_color( $input[ $key ] ) ?: '' ) : $defaults[ $key ];
    }
    foreach ( array( 'border_width' => array( 0, 10 ), 'border_radius' => array( 0, 100 ), 'button_width' => array( 44, 120 ), 'input_width' => array( 40, 200 ), 'input_height' => array( 44, 100 ), 'input_border_radius' => array( 0, 100 ), 'gap' => array( 0, 40 ) ) as $key => $range ) {
        $result[ $key ] = isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) && is_numeric( $input[ $key ] )
            ? max( $range[0], min( $range[1], (int) $input[ $key ] ) ) : $defaults[ $key ];
    }
    $result['button_shape'] = isset( $input['button_shape'] ) && in_array( $input['button_shape'], array( 'normal', 'circle' ), true )
        ? $input['button_shape'] : ( ! array_key_exists( 'button_shape', $input ) && 100 === $result['border_radius'] ? 'circle' : 'normal' );
    return $result;
}

function xw_quantity_conflict() {
    return defined( 'WQPMB_VERSION' ) || class_exists( 'WQPMB_Button', false );
}

function xw_quantity_enabled() {
    return xw_feature_enabled( 'woocommerce_quantity_buttons' ) && class_exists( 'WooCommerce' ) && ! xw_quantity_conflict();
}

function xw_quantity_assets() {
    if ( is_admin() || ! xw_quantity_enabled() ) { return; }
    $base = plugin_dir_path( XW_FUNCTIONS_FILE );
    $url = plugin_dir_url( XW_FUNCTIONS_FILE );
    $settings = xw_get_settings();
    $settings = xw_quantity_sanitize( $settings['quantity_buttons'] ?? array() );
    wp_enqueue_style( 'xw-quantity-buttons', $url . 'assets/quantity-buttons/quantity-buttons.css', array(), filemtime( $base . 'assets/quantity-buttons/quantity-buttons.css' ) );
    wp_enqueue_script( 'xw-quantity-buttons', $url . 'assets/quantity-buttons/quantity-buttons.js', array( 'jquery' ), filemtime( $base . 'assets/quantity-buttons/quantity-buttons.js' ), true );
    $css = ':root{';
    foreach ( $settings as $key => $value ) {
        if ( '' === $value || 'button_shape' === $key ) { continue; }
        $css .= '--xw-qty-' . str_replace( '_', '-', $key ) . ':' . ( is_int( $value ) ? $value . 'px' : $value ) . ';';
    }
    $circle = 'circle' === $settings['button_shape'];
    $css .= '--xw-qty-button-height:' . ( $circle ? $settings['button_width'] : $settings['input_height'] ) . 'px;';
    $css .= '--xw-qty-button-radius:' . ( $circle ? '50%' : $settings['border_radius'] . 'px' ) . ';';
    wp_add_inline_style( 'xw-quantity-buttons', $css . '}' );
    wp_localize_script( 'xw-quantity-buttons', 'xwQuantityButtons', array(
        'minus' => xw_t( 'Disminuir cantidad', 'Decrease quantity' ),
        'plus' => xw_t( 'Aumentar cantidad', 'Increase quantity' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'xw_quantity_assets', 35 );

function xw_quantity_color_field( $settings, $key, $label ) {
    $id = 'xw-qty-' . str_replace( '_', '-', $key );
    ?>
    <div class="xw-field-row">
        <label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
        <div class="xw-color-control">
            <input id="<?php echo esc_attr( $id ); ?>" type="color" class="<?php echo empty( $settings[ $key ] ) ? 'is-empty' : ''; ?>" value="<?php echo esc_attr( $settings[ $key ] ?: '#000000' ); ?>" data-xw-color-picker>
            <input type="text" name="xw_settings[quantity_buttons][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings[ $key ] ); ?>" class="xw-color-text" placeholder="#RRGGBB" maxlength="7" spellcheck="false" aria-label="<?php echo esc_attr( $label . ' — HEX' ); ?>" data-xw-color-value>
        </div>
    </div>
    <?php
}

function xw_render_quantity_settings( $settings ) {
    $settings = xw_quantity_sanitize( $settings );
    ?>
    <p class="xw-field-full"><?php echo esc_html( xw_t( 'Respeta el mínimo, máximo y paso del producto. Los bloques de WooCommerce mantienen sus botones nativos.', 'Respects the product’s minimum, maximum and step. WooCommerce blocks keep their native buttons.' ) ); ?></p>
    <?php if ( ! class_exists( 'WooCommerce' ) || xw_quantity_conflict() ) : ?>
        <p class="xw-field-full" role="status"><?php echo esc_html( ! class_exists( 'WooCommerce' )
            ? xw_t( 'Activa WooCommerce para utilizar este módulo.', 'Activate WooCommerce to use this module.' )
            : xw_t( 'El plugin Quantity Plus Minus Button está activo. Desactívalo para que la suite pueda controlar las cantidades sin duplicar botones.', 'The Quantity Plus Minus Button plugin is active. Deactivate it so the suite can control quantities without duplicate buttons.' ) ); ?></p>
    <?php endif; ?>
    <?php
    foreach ( array(
        'button' => array( xw_t( 'Color de los botones', 'Button colors' ), array( 'button_background' => xw_t( 'Fondo', 'Background' ), 'button_border' => xw_t( 'Borde', 'Border' ), 'button_color' => xw_t( 'Texto', 'Text' ) ) ),
        'hover' => array( xw_t( 'Color al pasar el cursor', 'Button hover colors' ), array( 'hover_background' => xw_t( 'Fondo hover', 'Hover background' ), 'hover_border' => xw_t( 'Borde hover', 'Hover border' ), 'hover_color' => xw_t( 'Texto hover', 'Hover text' ) ) ),
        'input' => array( xw_t( 'Colores del campo', 'Input colors' ), array( 'input_background' => xw_t( 'Fondo del campo', 'Input background' ), 'input_border' => xw_t( 'Borde del campo', 'Input border' ), 'input_color' => xw_t( 'Texto del campo', 'Input text' ) ) ),
    ) as $group ) {
        echo '<h4 class="xw-options-heading">' . esc_html( $group[0] ) . '</h4>';
        foreach ( $group[1] as $key => $label ) { xw_quantity_color_field( $settings, $key, $label ); }
    }
    echo '<h4 class="xw-options-heading">' . esc_html( xw_t( 'Bordes y dimensiones', 'Borders and dimensions' ) ) . '</h4>';
    ?>
    <div class="xw-field-row">
        <label for="xw-qty-button-shape"><?php echo esc_html( xw_t( 'Forma de los botones', 'Button shape' ) ); ?></label>
        <select id="xw-qty-button-shape" name="xw_settings[quantity_buttons][button_shape]">
            <option value="normal" <?php selected( $settings['button_shape'], 'normal' ); ?>><?php echo esc_html( xw_t( 'Normal', 'Normal' ) ); ?></option>
            <option value="circle" <?php selected( $settings['button_shape'], 'circle' ); ?>><?php echo esc_html( xw_t( 'Circular', 'Circular' ) ); ?></option>
        </select>
        <p><?php echo esc_html( xw_t( 'Circular usa el ancho del botón como diámetro. El radio del botón se aplica a la forma Normal.', 'Circular uses button width as its diameter. Button border radius applies to the Normal shape.' ) ); ?></p>
    </div>
    <?php
    $fields = array(
        'border_width' => array( xw_t( 'Ancho del borde del botón', 'Button border width' ), 0, 10 ),
        'border_radius' => array( xw_t( 'Radio del borde del botón', 'Button border radius' ), 0, 100 ),
        'button_width' => array( xw_t( 'Ancho del botón', 'Button width' ), 44, 120 ),
        'input_width' => array( xw_t( 'Ancho del campo', 'Input width' ), 40, 200 ),
        'input_height' => array( xw_t( 'Alto del campo y botones normales', 'Input and normal button height' ), 44, 100 ),
        'input_border_radius' => array( xw_t( 'Radio del borde del campo', 'Input border radius' ), 0, 100 ),
        'gap' => array( xw_t( 'Separación entre controles', 'Gap between controls' ), 0, 40 ),
    );
    foreach ( $fields as $key => $field ) : ?>
        <div class="xw-field-row">
            <label for="xw-qty-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label>
            <div class="xw-number-control">
                <input id="xw-qty-<?php echo esc_attr( $key ); ?>" type="number" name="xw_settings[quantity_buttons][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings[ $key ] ); ?>" min="<?php echo esc_attr( $field[1] ); ?>" max="<?php echo esc_attr( $field[2] ); ?>" step="1" inputmode="numeric"><span>px</span>
            </div>
        </div>
    <?php endforeach;
}
