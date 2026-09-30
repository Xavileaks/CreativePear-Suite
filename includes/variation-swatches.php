<?php
/**
 * Variation swatches configurables para la página de producto individual.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sanea todos los ajustes del módulo.
 *
 * @param array $input    Valores enviados.
 * @param array $defaults Valores predeterminados.
 * @return array
 */
function xw_sanitize_variation_swatches_settings( $input, $defaults ) {
    $input  = is_array( $input ) ? $input : array();
    $result = $defaults;

    foreach ( array( 'auto_label', 'variation_images', 'clear_on_reselect', 'hide_reset', 'show_selected_label', 'label_flex', 'tooltip_enabled' ) as $key ) {
        $result[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
    }

    $enums = array(
        'auto_select'       => array( 'none', 'page_load', 'after_first' ),
        'alignment'         => array( 'left', 'center', 'right' ),
        'attribute_label_alignment' => array( 'left', 'center', 'right' ),
        'label_position'    => array( 'inherit', 'above', 'hidden' ),
        'disabled_behavior' => array( 'hide', 'blur', 'cross', 'blur-cross' ),
        'label_shape'       => array( 'square', 'circle', 'rounded' ),
        'color_shape'       => array( 'square', 'circle', 'rounded' ),
        'image_shape'       => array( 'square', 'circle', 'rounded' ),
        'tooltip_content'   => array( 'text', 'image', 'text_image' ),
    );

    foreach ( $enums as $key => $allowed ) {
        $value = isset( $input[ $key ] ) ? sanitize_key( $input[ $key ] ) : $defaults[ $key ];
        $result[ $key ] = in_array( $value, $allowed, true ) ? $value : $defaults[ $key ];
    }

    $numbers = array(
        'container_padding_top'    => array( 0, 100 ),
        'container_padding_right'  => array( 0, 100 ),
        'container_padding_bottom' => array( 0, 100 ),
        'container_padding_left'   => array( 0, 100 ),
        'horizontal_gap'           => array( 0, 60 ),
        'vertical_gap'             => array( 0, 60 ),
        'attribute_gap'            => array( 0, 100 ),
        'attribute_label_padding_left'  => array( 0, 100 ),
        'attribute_label_padding_right' => array( 0, 100 ),
        'label_min_width'          => array( 20, 200 ),
        'label_height'             => array( 20, 120 ),
        'label_font_size'          => array( 8, 40 ),
        'color_width'              => array( 20, 120 ),
        'color_height'             => array( 20, 120 ),
        'color_padding'            => array( 0, 20 ),
        'image_size'               => array( 20, 160 ),
        'image_padding'            => array( 0, 20 ),
        'tooltip_image_size'       => array( 20, 200 ),
        'tooltip_padding'          => array( 0, 40 ),
        'tooltip_radius'           => array( 0, 40 ),
    );

    foreach ( $numbers as $key => $limits ) {
        $value          = isset( $input[ $key ] ) ? absint( $input[ $key ] ) : $defaults[ $key ];
        $result[ $key ] = max( $limits[0], min( $limits[1], $value ) );
    }

    $colors = array(
        'label_text_color',
        'label_hover_text_color',
        'label_selected_text_color',
        'label_background',
        'label_hover_background',
        'label_selected_background',
        'label_border_color',
        'label_hover_border_color',
        'label_selected_border_color',
        'color_border_color',
        'color_hover_border_color',
        'color_selected_border_color',
        'image_border_color',
        'image_hover_border_color',
        'image_selected_border_color',
        'tooltip_background',
        'tooltip_text_color',
        'clear_color',
        'clear_hover_color',
    );

    foreach ( $colors as $key ) {
        $color          = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
        $result[ $key ] = $color ? strtoupper( $color ) : $defaults[ $key ];
    }

    return $result;
}

/**
 * Campo interruptor para el panel con pestañas.
 */
function xw_vs_admin_switch( $settings, $key, $label, $description = '' ) {
    ?>
    <div class="xw-vs-setting xw-vs-setting--switch">
        <div>
            <strong><?php echo esc_html( $label ); ?></strong>
            <?php if ( $description ) : ?>
                <p><?php echo esc_html( $description ); ?></p>
            <?php endif; ?>
        </div>
        <label class="xw-switch">
            <span class="screen-reader-text"><?php echo esc_html( $label ); ?></span>
            <input type="checkbox" name="xw_settings[variation_swatches][<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?>>
            <span class="xw-switch-track" aria-hidden="true"><span></span></span>
        </label>
    </div>
    <?php
}

/**
 * Campo select para el panel con pestañas.
 */
function xw_vs_admin_select( $settings, $key, $label, $options, $description = '' ) {
    $id = 'xw-vs-' . str_replace( '_', '-', $key );
    ?>
    <div class="xw-vs-setting">
        <label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
        <select id="<?php echo esc_attr( $id ); ?>" name="xw_settings[variation_swatches][<?php echo esc_attr( $key ); ?>]">
            <?php foreach ( $options as $value => $option_label ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings[ $key ], $value ); ?>><?php echo esc_html( $option_label ); ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ( $description ) : ?>
            <p><?php echo esc_html( $description ); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Campo numérico para el panel con pestañas.
 */
function xw_vs_admin_number( $settings, $key, $label, $min, $max, $unit = 'px' ) {
    $id = 'xw-vs-' . str_replace( '_', '-', $key );
    ?>
    <div class="xw-vs-setting xw-vs-setting--compact">
        <label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
        <div class="xw-number-control">
            <input id="<?php echo esc_attr( $id ); ?>" type="number" name="xw_settings[variation_swatches][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings[ $key ] ); ?>" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="1">
            <span><?php echo esc_html( $unit ); ?></span>
        </div>
    </div>
    <?php
}

/**
 * Campo de color para el panel con pestañas.
 */
function xw_vs_admin_color( $settings, $key, $label ) {
    $id    = 'xw-vs-' . str_replace( '_', '-', $key );
    $value = $settings[ $key ];
    ?>
    <div class="xw-vs-setting">
        <label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
        <div class="xw-color-control">
            <input id="<?php echo esc_attr( $id ); ?>" type="color" value="<?php echo esc_attr( $value ); ?>" data-xw-color-picker>
            <input type="text" name="xw_settings[variation_swatches][<?php echo esc_attr( $key ); ?>]" class="xw-color-text" value="<?php echo esc_attr( $value ); ?>" maxlength="7" spellcheck="false" aria-label="<?php echo esc_attr( $label ); ?>" data-xw-color-value>
        </div>
    </div>
    <?php
}

/**
 * Renderiza el panel de ajustes del módulo.
 */
function xw_render_variation_swatches_settings( $settings ) {
    $tabs = array(
        'general' => xw_t( 'General', 'General' ),
        'design'  => xw_t( 'Diseño', 'Design' ),
        'label'   => xw_t( 'Etiquetas', 'Labels' ),
        'color'   => xw_t( 'Colores', 'Colors' ),
        'image'   => xw_t( 'Imágenes', 'Images' ),
        'tooltip' => 'Tooltip',
    );
    ?>
    <div class="xw-vs-admin xw-field-full" data-xw-vs-tabs>
        <div class="xw-vs-scope-note">
            <span class="dashicons dashicons-products" aria-hidden="true"></span>
            <div>
                <strong><?php echo esc_html( xw_t( 'Solo producto individual', 'Single product only' ) ); ?></strong>
                <p><?php echo esc_html( xw_t( 'No modifica archivos, categorías ni widgets de Elementor.', 'Does not modify archives, categories, or Elementor widgets.' ) ); ?></p>
            </div>
        </div>
        <div class="xw-vs-tablist" role="tablist" aria-label="<?php echo esc_attr( xw_t( 'Ajustes de Variation Swatches', 'Variation Swatches settings' ) ); ?>">
            <?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
                <button type="button" role="tab" id="xw-vs-tab-<?php echo esc_attr( $tab_key ); ?>" aria-controls="xw-vs-panel-<?php echo esc_attr( $tab_key ); ?>" aria-selected="<?php echo 'general' === $tab_key ? 'true' : 'false'; ?>" tabindex="<?php echo 'general' === $tab_key ? '0' : '-1'; ?>" data-xw-vs-tab="<?php echo esc_attr( $tab_key ); ?>"><?php echo esc_html( $tab_label ); ?></button>
            <?php endforeach; ?>
        </div>

        <section id="xw-vs-panel-general" class="xw-vs-panel" role="tabpanel" aria-labelledby="xw-vs-tab-general" data-xw-vs-panel="general">
            <p class="xw-vs-panel-help"><?php echo esc_html( xw_t( 'En Productos → Atributos elija Etiqueta, Color o Imagen para cada atributo global. Los atributos locales se convierten en etiquetas.', 'Under Products → Attributes choose Label, Color, or Image for each global attribute. Local attributes convert to labels.' ) ); ?></p>
            <div class="xw-vs-settings-grid">
                <?php xw_vs_admin_switch( $settings, 'auto_label', xw_t( 'Convertir Select en etiquetas', 'Convert Select to labels' ), xw_t( 'Los atributos sin tipo visual se muestran como botones de texto.', 'Attributes without a visual type display as text buttons.' ) ); ?>
                <?php xw_vs_admin_switch( $settings, 'variation_images', xw_t( 'Usar imágenes de variación', 'Use variation images' ), xw_t( 'Completa los atributos tipo Imagen con la imagen de la variación cuando el término no tiene una propia.', 'Fills Image attributes with the variation image when the term has no image of its own.' ) ); ?>
                <?php xw_vs_admin_switch( $settings, 'clear_on_reselect', xw_t( 'Deseleccionar al repetir clic', 'Deselect on second click' ) ); ?>
                <?php xw_vs_admin_switch( $settings, 'hide_reset', xw_t( 'Ocultar enlace Limpiar', 'Hide Clear link' ) ); ?>
                <?php xw_vs_admin_switch( $settings, 'show_selected_label', xw_t( 'Mostrar valor junto a la etiqueta', 'Show value beside attribute label' ) ); ?>
                <?php
                xw_vs_admin_select(
                    $settings,
                    'auto_select',
                    xw_t( 'Selección automática', 'Automatic selection' ),
                    array(
                        'none'        => xw_t( 'Desactivada', 'Disabled' ),
                        'page_load'   => xw_t( 'Primera opción al cargar', 'First option on page load' ),
                        'after_first' => xw_t( 'Completar después de la primera elección', 'Complete after the first choice' ),
                    )
                );
                ?>
            </div>
        </section>

        <section id="xw-vs-panel-design" class="xw-vs-panel" role="tabpanel" aria-labelledby="xw-vs-tab-design" data-xw-vs-panel="design" hidden>
            <div class="xw-vs-settings-grid">
                <?php xw_vs_admin_select( $settings, 'alignment', xw_t( 'Alineación', 'Alignment' ), array( 'left' => xw_t( 'Izquierda', 'Left' ), 'center' => xw_t( 'Centro', 'Center' ), 'right' => xw_t( 'Derecha', 'Right' ) ) ); ?>
                <?php xw_vs_admin_select( $settings, 'attribute_label_alignment', xw_t( 'Alineación de etiquetas', 'Labels alignment' ), array( 'left' => xw_t( 'Izquierda', 'Left' ), 'center' => xw_t( 'Centro', 'Center' ), 'right' => xw_t( 'Derecha', 'Right' ) ), xw_t( 'Alinea los nombres de los atributos de forma independiente a los swatches.', 'Aligns the attribute names independently from the swatches.' ) ); ?>
                <?php xw_vs_admin_number( $settings, 'attribute_label_padding_left', xw_t( 'Padding izquierdo de etiquetas', 'Labels left padding' ), 0, 100 ); ?>
                <?php xw_vs_admin_number( $settings, 'attribute_label_padding_right', xw_t( 'Padding derecho de etiquetas', 'Labels right padding' ), 0, 100 ); ?>
                <?php xw_vs_admin_select( $settings, 'label_position', xw_t( 'Posición de la etiqueta', 'Attribute label position' ), array( 'inherit' => xw_t( 'Heredar tema', 'Inherit theme' ), 'above' => xw_t( 'Encima de los swatches', 'Above swatches' ), 'hidden' => xw_t( 'Oculta', 'Hidden' ) ) ); ?>
                <?php xw_vs_admin_select( $settings, 'disabled_behavior', xw_t( 'Opciones no disponibles', 'Unavailable options' ), array( 'hide' => xw_t( 'Ocultar', 'Hide' ), 'blur' => xw_t( 'Atenuar', 'Blur' ), 'cross' => xw_t( 'Tachar', 'Cross' ), 'blur-cross' => xw_t( 'Atenuar y tachar', 'Blur and cross' ) ) ); ?>
                <?php xw_vs_admin_number( $settings, 'container_padding_top', 'Padding top', 0, 100 ); ?>
                <?php xw_vs_admin_number( $settings, 'container_padding_right', 'Padding right', 0, 100 ); ?>
                <?php xw_vs_admin_number( $settings, 'container_padding_bottom', 'Padding bottom', 0, 100 ); ?>
                <?php xw_vs_admin_number( $settings, 'container_padding_left', 'Padding left', 0, 100 ); ?>
                <?php xw_vs_admin_number( $settings, 'horizontal_gap', xw_t( 'Espacio horizontal', 'Horizontal gap' ), 0, 60 ); ?>
                <?php xw_vs_admin_number( $settings, 'vertical_gap', xw_t( 'Espacio vertical', 'Vertical gap' ), 0, 60 ); ?>
                <?php xw_vs_admin_number( $settings, 'attribute_gap', xw_t( 'Espacio entre atributos', 'Gap between attributes' ), 0, 100 ); ?>
                <?php xw_vs_admin_color( $settings, 'clear_color', xw_t( 'Color de Limpiar', 'Clear color' ) ); ?>
                <?php xw_vs_admin_color( $settings, 'clear_hover_color', xw_t( 'Color hover de Limpiar', 'Clear hover color' ) ); ?>
            </div>
        </section>

        <section id="xw-vs-panel-label" class="xw-vs-panel" role="tabpanel" aria-labelledby="xw-vs-tab-label" data-xw-vs-panel="label" hidden>
            <div class="xw-vs-settings-grid">
                <?php xw_vs_admin_select( $settings, 'label_shape', xw_t( 'Forma', 'Shape' ), array( 'square' => xw_t( 'Cuadrada', 'Square' ), 'circle' => xw_t( 'Circular', 'Circle' ), 'rounded' => xw_t( 'Redondeada', 'Rounded' ) ) ); ?>
                <?php xw_vs_admin_switch( $settings, 'label_flex', xw_t( 'Ancho flexible', 'Flexible width' ), xw_t( 'Permite que cada etiqueta crezca según su texto.', 'Allows each label to grow with its text.' ) ); ?>
                <?php xw_vs_admin_number( $settings, 'label_min_width', xw_t( 'Ancho mínimo', 'Minimum width' ), 20, 200 ); ?>
                <?php xw_vs_admin_number( $settings, 'label_height', xw_t( 'Altura', 'Height' ), 20, 120 ); ?>
                <?php xw_vs_admin_number( $settings, 'label_font_size', xw_t( 'Tamaño de fuente', 'Font size' ), 8, 40 ); ?>
                <?php xw_vs_admin_color( $settings, 'label_text_color', xw_t( 'Texto', 'Text' ) ); ?>
                <?php xw_vs_admin_color( $settings, 'label_hover_text_color', 'Texto hover' ); ?>
                <?php xw_vs_admin_color( $settings, 'label_selected_text_color', xw_t( 'Texto seleccionado', 'Selected text' ) ); ?>
                <?php xw_vs_admin_color( $settings, 'label_background', xw_t( 'Fondo', 'Background' ) ); ?>
                <?php xw_vs_admin_color( $settings, 'label_hover_background', 'Fondo hover' ); ?>
                <?php xw_vs_admin_color( $settings, 'label_selected_background', xw_t( 'Fondo seleccionado', 'Selected background' ) ); ?>
                <?php xw_vs_admin_color( $settings, 'label_border_color', xw_t( 'Borde', 'Border' ) ); ?>
                <?php xw_vs_admin_color( $settings, 'label_hover_border_color', 'Borde hover' ); ?>
                <?php xw_vs_admin_color( $settings, 'label_selected_border_color', xw_t( 'Borde seleccionado', 'Selected border' ) ); ?>
            </div>
        </section>

        <section id="xw-vs-panel-color" class="xw-vs-panel" role="tabpanel" aria-labelledby="xw-vs-tab-color" data-xw-vs-panel="color" hidden>
            <div class="xw-vs-settings-grid">
                <?php xw_vs_admin_select( $settings, 'color_shape', xw_t( 'Forma', 'Shape' ), array( 'square' => xw_t( 'Cuadrada', 'Square' ), 'circle' => xw_t( 'Circular', 'Circle' ), 'rounded' => xw_t( 'Redondeada', 'Rounded' ) ) ); ?>
                <?php xw_vs_admin_number( $settings, 'color_width', xw_t( 'Ancho', 'Width' ), 20, 120 ); ?>
                <?php xw_vs_admin_number( $settings, 'color_height', xw_t( 'Altura', 'Height' ), 20, 120 ); ?>
                <?php xw_vs_admin_number( $settings, 'color_padding', xw_t( 'Relleno interior', 'Inner padding' ), 0, 20 ); ?>
                <?php xw_vs_admin_color( $settings, 'color_border_color', xw_t( 'Borde', 'Border' ) ); ?>
                <?php xw_vs_admin_color( $settings, 'color_hover_border_color', 'Borde hover' ); ?>
                <?php xw_vs_admin_color( $settings, 'color_selected_border_color', xw_t( 'Borde seleccionado', 'Selected border' ) ); ?>
            </div>
        </section>

        <section id="xw-vs-panel-image" class="xw-vs-panel" role="tabpanel" aria-labelledby="xw-vs-tab-image" data-xw-vs-panel="image" hidden>
            <div class="xw-vs-settings-grid">
                <?php xw_vs_admin_select( $settings, 'image_shape', xw_t( 'Forma', 'Shape' ), array( 'square' => xw_t( 'Cuadrada', 'Square' ), 'circle' => xw_t( 'Circular', 'Circle' ), 'rounded' => xw_t( 'Redondeada', 'Rounded' ) ) ); ?>
                <?php xw_vs_admin_number( $settings, 'image_size', xw_t( 'Tamaño', 'Size' ), 20, 160 ); ?>
                <?php xw_vs_admin_number( $settings, 'image_padding', xw_t( 'Relleno interior', 'Inner padding' ), 0, 20 ); ?>
                <?php xw_vs_admin_color( $settings, 'image_border_color', xw_t( 'Borde', 'Border' ) ); ?>
                <?php xw_vs_admin_color( $settings, 'image_hover_border_color', 'Borde hover' ); ?>
                <?php xw_vs_admin_color( $settings, 'image_selected_border_color', xw_t( 'Borde seleccionado', 'Selected border' ) ); ?>
            </div>
        </section>

        <section id="xw-vs-panel-tooltip" class="xw-vs-panel" role="tabpanel" aria-labelledby="xw-vs-tab-tooltip" data-xw-vs-panel="tooltip" hidden>
            <div class="xw-vs-settings-grid">
                <?php xw_vs_admin_switch( $settings, 'tooltip_enabled', xw_t( 'Activar tooltip', 'Enable tooltip' ), xw_t( 'Muestra el nombre y, para swatches de imagen, una vista previa.', 'Shows the name and, for image swatches, a preview.' ) ); ?>
                <?php
                xw_vs_admin_select(
                    $settings,
                    'tooltip_content',
                    xw_t( 'Contenido del tooltip', 'Tooltip content' ),
                    array(
                        'text'       => xw_t( 'Solo texto', 'Text only' ),
                        'image'      => xw_t( 'Solo imagen', 'Image only' ),
                        'text_image' => xw_t( 'Texto e imagen', 'Text and image' ),
                    ),
                    xw_t( 'En swatches sin imagen se mostrará el texto para evitar un tooltip vacío.', 'Text is shown for swatches without an image so the tooltip is never empty.' )
                );
                ?>
                <?php xw_vs_admin_number( $settings, 'tooltip_image_size', xw_t( 'Tamaño de imagen', 'Image size' ), 20, 200 ); ?>
                <?php xw_vs_admin_number( $settings, 'tooltip_padding', xw_t( 'Relleno', 'Padding' ), 0, 40 ); ?>
                <?php xw_vs_admin_number( $settings, 'tooltip_radius', xw_t( 'Radio del borde', 'Border radius' ), 0, 40 ); ?>
                <?php xw_vs_admin_color( $settings, 'tooltip_background', xw_t( 'Fondo', 'Background' ) ); ?>
                <?php xw_vs_admin_color( $settings, 'tooltip_text_color', xw_t( 'Texto', 'Text' ) ); ?>
            </div>
        </section>
    </div>
    <?php
}

add_action( 'plugins_loaded', 'xw_bootstrap_variation_swatches', 25 );

/**
 * Registra la función solo cuando está activada y WooCommerce está disponible.
 */
function xw_bootstrap_variation_swatches() {
    if ( ! xw_feature_enabled( 'variation_swatches' ) ) {
        return;
    }

    if ( ! class_exists( 'WooCommerce' ) ) {
        if ( is_admin() ) {
            add_action( 'admin_notices', 'xw_vs_missing_woocommerce_notice' );
        }
        return;
    }

    if ( xw_vs_has_external_conflict() ) {
        add_action( 'admin_notices', 'xw_vs_conflict_notice' );
        return;
    }

    add_filter( 'product_attributes_type_selector', 'xw_vs_attribute_types' );
    add_action( 'admin_init', 'xw_vs_register_attribute_term_fields' );
    add_action( 'created_term', 'xw_vs_save_term_meta', 10, 3 );
    add_action( 'edited_term', 'xw_vs_save_term_meta', 10, 3 );
    add_action( 'admin_enqueue_scripts', 'xw_vs_enqueue_admin_assets' );

    add_filter( 'woocommerce_dropdown_variation_attribute_options_html', 'xw_vs_dropdown_html', 35, 2 );
    add_filter( 'body_class', 'xw_vs_body_classes' );
    add_action( 'wp_enqueue_scripts', 'xw_vs_enqueue_frontend_assets', 32 );
}

function xw_vs_missing_woocommerce_notice() {
    echo '<div class="notice notice-warning"><p>' . esc_html( xw_t( 'Variation Swatches necesita WooCommerce activo.', 'Variation Swatches requires WooCommerce to be active.' ) ) . '</p></div>';
}

function xw_vs_has_external_conflict() {
    return defined( 'WOO_VARIATION_SWATCHES_PLUGIN_VERSION' ) || class_exists( 'Woo_Variation_Swatches', false );
}

function xw_vs_conflict_notice() {
    echo '<div class="notice notice-warning"><p>' . esc_html( xw_t( 'Variation Swatches de Creative Pear está activo, pero la interfaz pública permanece en pausa mientras “Variation Swatches for WooCommerce” siga activado. Desactive el otro plugin para evitar controles duplicados.', 'Creative Pear Variation Swatches is enabled, but its public interface remains paused while “Variation Swatches for WooCommerce” is active. Disable the other plugin to avoid duplicate controls.' ) ) . '</p></div>';
}

/**
 * Tipos propios de atributos globales. También se leen los tipos heredados del plugin de referencia.
 */
function xw_vs_attribute_types( $types ) {
    if ( ! isset( $types['button'] ) ) {
        $types['button'] = xw_t( 'Etiqueta (compatible)', 'Label (compatible)' );
    }
    if ( ! isset( $types['color'] ) ) {
        $types['color'] = xw_t( 'Color (compatible)', 'Color (compatible)' );
    }
    if ( ! isset( $types['image'] ) ) {
        $types['image'] = xw_t( 'Imagen (compatible)', 'Image (compatible)' );
    }
    $types['xw_label'] = xw_t( 'Etiqueta (Creative Pear)', 'Label (Creative Pear)' );
    $types['xw_color'] = xw_t( 'Color (Creative Pear)', 'Color (Creative Pear)' );
    $types['xw_image'] = xw_t( 'Imagen (Creative Pear)', 'Image (Creative Pear)' );
    return $types;
}

function xw_vs_normalize_type( $type ) {
    if ( in_array( $type, array( 'xw_color', 'color' ), true ) ) {
        return 'color';
    }
    if ( in_array( $type, array( 'xw_image', 'image' ), true ) ) {
        return 'image';
    }
    if ( in_array( $type, array( 'xw_label', 'button' ), true ) ) {
        return 'label';
    }
    return 'select';
}

function xw_vs_get_attribute_object( $attribute ) {
    if ( ! function_exists( 'wc_attribute_taxonomy_id_by_name' ) || ! taxonomy_exists( $attribute ) ) {
        return false;
    }
    $attribute_id = wc_attribute_taxonomy_id_by_name( $attribute );
    return $attribute_id ? wc_get_attribute( $attribute_id ) : false;
}

function xw_vs_get_taxonomy_type( $taxonomy ) {
    $attribute = xw_vs_get_attribute_object( $taxonomy );
    return $attribute && isset( $attribute->type ) ? xw_vs_normalize_type( $attribute->type ) : 'select';
}

/**
 * Añade campos de color/imagen a los términos de los atributos globales.
 */
function xw_vs_register_attribute_term_fields() {
    if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
        return;
    }

    foreach ( wc_get_attribute_taxonomies() as $attribute ) {
        $type = xw_vs_normalize_type( $attribute->attribute_type );
        if ( ! in_array( $type, array( 'color', 'image', 'label' ), true ) ) {
            continue;
        }
        $taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
        add_action( "{$taxonomy}_add_form_fields", 'xw_vs_add_term_fields' );
        add_action( "{$taxonomy}_edit_form_fields", 'xw_vs_edit_term_fields', 10, 2 );
    }
}

function xw_vs_term_nonce_field() {
    wp_nonce_field( 'xw_vs_save_term', 'xw_vs_term_nonce' );
}

function xw_vs_add_term_fields( $taxonomy ) {
    $type = xw_vs_get_taxonomy_type( $taxonomy );
    xw_vs_term_nonce_field();
    if ( 'color' === $type ) :
        ?>
        <div class="form-field xw-vs-term-field">
            <label for="xw-vs-term-color"><?php echo esc_html( xw_t( 'Color del swatch', 'Swatch color' ) ); ?></label>
            <input id="xw-vs-term-color" type="color" name="xw_vs_color" value="#CCCCCC">
        </div>
        <?php
    elseif ( 'image' === $type ) :
        xw_vs_render_term_image_field( 0, false );
    else :
        ?>
        <div class="form-field xw-vs-term-field">
            <label for="xw-vs-term-label"><?php echo esc_html( xw_t( 'Etiqueta personalizada', 'Custom label' ) ); ?></label>
            <input id="xw-vs-term-label" type="text" name="xw_vs_label" value="" maxlength="80">
            <p><?php echo esc_html( xw_t( 'Déjelo vacío para usar el nombre del término.', 'Leave empty to use the term name.' ) ); ?></p>
        </div>
        <?php
    endif;
}

function xw_vs_edit_term_fields( $term, $taxonomy ) {
    $type = xw_vs_get_taxonomy_type( $taxonomy );
    ?>
    <tr class="form-field"><th></th><td><?php xw_vs_term_nonce_field(); ?></td></tr>
    <?php if ( 'color' === $type ) :
        $color = sanitize_hex_color( get_term_meta( $term->term_id, '_xw_vs_color', true ) );
        if ( ! $color ) {
            $color = sanitize_hex_color( get_term_meta( $term->term_id, 'product_attribute_color', true ) ) ?: '#CCCCCC';
        }
        ?>
        <tr class="form-field xw-vs-term-field">
            <th scope="row"><label for="xw-vs-term-color"><?php echo esc_html( xw_t( 'Color del swatch', 'Swatch color' ) ); ?></label></th>
            <td><input id="xw-vs-term-color" type="color" name="xw_vs_color" value="<?php echo esc_attr( $color ); ?>"></td>
        </tr>
        <?php
    elseif ( 'image' === $type ) :
        $image_id = absint( get_term_meta( $term->term_id, '_xw_vs_image_id', true ) );
        if ( ! $image_id ) {
            $image_id = absint( get_term_meta( $term->term_id, 'product_attribute_image', true ) );
        }
        xw_vs_render_term_image_field( $image_id, true );
    else :
        $label = get_term_meta( $term->term_id, '_xw_vs_label', true );
        ?>
        <tr class="form-field xw-vs-term-field">
            <th scope="row"><label for="xw-vs-term-label"><?php echo esc_html( xw_t( 'Etiqueta personalizada', 'Custom label' ) ); ?></label></th>
            <td><input id="xw-vs-term-label" type="text" name="xw_vs_label" value="<?php echo esc_attr( $label ); ?>" maxlength="80"><p class="description"><?php echo esc_html( xw_t( 'Déjelo vacío para usar el nombre del término.', 'Leave empty to use the term name.' ) ); ?></p></td>
        </tr>
        <?php
    endif;
}

function xw_vs_render_term_image_field( $image_id, $table_row ) {
    $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
    $open      = $table_row ? '<tr class="form-field xw-vs-term-field"><th scope="row"><label>' . esc_html( xw_t( 'Imagen del swatch', 'Swatch image' ) ) . '</label></th><td>' : '<div class="form-field xw-vs-term-field"><label>' . esc_html( xw_t( 'Imagen del swatch', 'Swatch image' ) ) . '</label>';
    $close     = $table_row ? '</td></tr>' : '</div>';
    echo $open; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML fijo creado arriba.
    ?>
    <div class="xw-vs-term-image" data-xw-vs-term-image>
        <input type="hidden" name="xw_vs_image_id" value="<?php echo esc_attr( $image_id ); ?>" data-xw-vs-image-id>
        <div class="xw-vs-term-image__preview" data-xw-vs-image-preview<?php echo $image_url ? '' : ' hidden'; ?>>
            <img src="<?php echo esc_url( $image_url ); ?>" alt="">
        </div>
        <button type="button" class="button" data-xw-vs-image-choose><?php echo esc_html( xw_t( 'Elegir imagen', 'Choose image' ) ); ?></button>
        <button type="button" class="button-link-delete" data-xw-vs-image-remove<?php echo $image_url ? '' : ' hidden'; ?>><?php echo esc_html( xw_t( 'Quitar', 'Remove' ) ); ?></button>
    </div>
    <?php
    echo $close; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML fijo creado arriba.
}

function xw_vs_save_term_meta( $term_id, $tt_id, $taxonomy ) {
    unset( $tt_id );
    if ( 0 !== strpos( $taxonomy, 'pa_' ) || ! isset( $_POST['xw_vs_term_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['xw_vs_term_nonce'] ) ), 'xw_vs_save_term' ) ) {
        return;
    }
    $taxonomy_object = get_taxonomy( $taxonomy );
    if ( ! $taxonomy_object || ! current_user_can( $taxonomy_object->cap->manage_terms ) ) {
        return;
    }
    $type = xw_vs_get_taxonomy_type( $taxonomy );
    if ( 'color' === $type ) {
        $color = isset( $_POST['xw_vs_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['xw_vs_color'] ) ) : '';
        update_term_meta( $term_id, '_xw_vs_color', $color ?: '#CCCCCC' );
    } elseif ( 'image' === $type ) {
        update_term_meta( $term_id, '_xw_vs_image_id', isset( $_POST['xw_vs_image_id'] ) ? absint( $_POST['xw_vs_image_id'] ) : 0 );
    } else {
        update_term_meta( $term_id, '_xw_vs_label', isset( $_POST['xw_vs_label'] ) ? sanitize_text_field( wp_unslash( $_POST['xw_vs_label'] ) ) : '' );
    }
}

function xw_vs_enqueue_admin_assets() {
    $screen = get_current_screen();
    if ( ! $screen || empty( $screen->taxonomy ) || 0 !== strpos( $screen->taxonomy, 'pa_' ) ) {
        return;
    }
    $css_path = plugin_dir_path( XW_FUNCTIONS_FILE ) . 'assets/css/variation-swatches-admin.css';
    $js_path  = plugin_dir_path( XW_FUNCTIONS_FILE ) . 'assets/js/variation-swatches-admin.js';
    wp_enqueue_media();
    wp_enqueue_style( 'xw-variation-swatches-admin', plugin_dir_url( XW_FUNCTIONS_FILE ) . 'assets/css/variation-swatches-admin.css', array(), filemtime( $css_path ) );
    wp_enqueue_script( 'xw-variation-swatches-admin', plugin_dir_url( XW_FUNCTIONS_FILE ) . 'assets/js/variation-swatches-admin.js', array( 'jquery' ), filemtime( $js_path ), true );
    wp_localize_script(
        'xw-variation-swatches-admin',
        'xwVariationSwatchesAdmin',
        array(
            'chooseImage' => xw_t( 'Elegir imagen del swatch', 'Choose swatch image' ),
            'useImage'    => xw_t( 'Usar esta imagen', 'Use this image' ),
        )
    );
}

function xw_vs_get_settings() {
    $settings = xw_get_settings();
    return $settings['variation_swatches'];
}

function xw_vs_body_classes( $classes ) {
    if ( ! is_product() ) {
        return $classes;
    }
    $settings  = xw_vs_get_settings();
    $classes[] = 'xw-vs-enabled';
    $classes[] = 'xw-vs-align-' . sanitize_html_class( $settings['alignment'] );
    $classes[] = 'xw-vs-label-' . sanitize_html_class( $settings['label_position'] );
    $classes[] = 'xw-vs-disabled-' . sanitize_html_class( $settings['disabled_behavior'] );
    if ( ! empty( $settings['hide_reset'] ) ) {
        $classes[] = 'xw-vs-hide-reset';
    }
    return $classes;
}

function xw_vs_enqueue_frontend_assets() {
    if ( ! is_product() ) {
        return;
    }
    $settings = xw_vs_get_settings();
    $css_path = plugin_dir_path( XW_FUNCTIONS_FILE ) . 'assets/css/variation-swatches.css';
    $js_path  = plugin_dir_path( XW_FUNCTIONS_FILE ) . 'assets/js/variation-swatches.js';

    wp_enqueue_style( 'xw-variation-swatches', plugin_dir_url( XW_FUNCTIONS_FILE ) . 'assets/css/variation-swatches.css', array(), filemtime( $css_path ) );
    wp_enqueue_script( 'wc-add-to-cart-variation' );
    wp_enqueue_script( 'xw-variation-swatches', plugin_dir_url( XW_FUNCTIONS_FILE ) . 'assets/js/variation-swatches.js', array( 'jquery', 'wc-add-to-cart-variation' ), filemtime( $js_path ), true );
    wp_localize_script(
        'xw-variation-swatches',
        'xwVariationSwatches',
        array(
            'clearOnReselect'  => ! empty( $settings['clear_on_reselect'] ),
            'autoSelect'       => $settings['auto_select'],
            'showSelectedLabel'=> ! empty( $settings['show_selected_label'] ),
            'tooltipContent'   => $settings['tooltip_content'],
        )
    );
    wp_add_inline_style( 'xw-variation-swatches', xw_vs_inline_css( $settings ) );
}

function xw_vs_inline_css( $settings ) {
    $variables = array(
        '--xw-vs-pad-top'              => absint( $settings['container_padding_top'] ) . 'px',
        '--xw-vs-pad-right'            => absint( $settings['container_padding_right'] ) . 'px',
        '--xw-vs-pad-bottom'           => absint( $settings['container_padding_bottom'] ) . 'px',
        '--xw-vs-pad-left'             => absint( $settings['container_padding_left'] ) . 'px',
        '--xw-vs-gap-x'                => absint( $settings['horizontal_gap'] ) . 'px',
        '--xw-vs-gap-y'                => absint( $settings['vertical_gap'] ) . 'px',
        '--xw-vs-attribute-gap'        => absint( $settings['attribute_gap'] ) . 'px',
        '--xw-vs-attribute-label-align'=> $settings['attribute_label_alignment'],
        '--xw-vs-attribute-label-pad-left' => absint( $settings['attribute_label_padding_left'] ) . 'px',
        '--xw-vs-attribute-label-pad-right'=> absint( $settings['attribute_label_padding_right'] ) . 'px',
        '--xw-vs-label-min-width'      => absint( $settings['label_min_width'] ) . 'px',
        '--xw-vs-label-height'         => absint( $settings['label_height'] ) . 'px',
        '--xw-vs-label-font-size'      => absint( $settings['label_font_size'] ) . 'px',
        '--xw-vs-label-color'          => sanitize_hex_color( $settings['label_text_color'] ),
        '--xw-vs-label-hover-color'    => sanitize_hex_color( $settings['label_hover_text_color'] ),
        '--xw-vs-label-selected-color' => sanitize_hex_color( $settings['label_selected_text_color'] ),
        '--xw-vs-label-bg'             => sanitize_hex_color( $settings['label_background'] ),
        '--xw-vs-label-hover-bg'       => sanitize_hex_color( $settings['label_hover_background'] ),
        '--xw-vs-label-selected-bg'    => sanitize_hex_color( $settings['label_selected_background'] ),
        '--xw-vs-label-border'         => sanitize_hex_color( $settings['label_border_color'] ),
        '--xw-vs-label-hover-border'   => sanitize_hex_color( $settings['label_hover_border_color'] ),
        '--xw-vs-label-selected-border'=> sanitize_hex_color( $settings['label_selected_border_color'] ),
        '--xw-vs-color-width'          => absint( $settings['color_width'] ) . 'px',
        '--xw-vs-color-height'         => absint( $settings['color_height'] ) . 'px',
        '--xw-vs-color-padding'        => absint( $settings['color_padding'] ) . 'px',
        '--xw-vs-color-border'         => sanitize_hex_color( $settings['color_border_color'] ),
        '--xw-vs-color-hover-border'   => sanitize_hex_color( $settings['color_hover_border_color'] ),
        '--xw-vs-color-selected-border'=> sanitize_hex_color( $settings['color_selected_border_color'] ),
        '--xw-vs-image-size'           => absint( $settings['image_size'] ) . 'px',
        '--xw-vs-image-padding'        => absint( $settings['image_padding'] ) . 'px',
        '--xw-vs-image-border'         => sanitize_hex_color( $settings['image_border_color'] ),
        '--xw-vs-image-hover-border'   => sanitize_hex_color( $settings['image_hover_border_color'] ),
        '--xw-vs-image-selected-border'=> sanitize_hex_color( $settings['image_selected_border_color'] ),
        '--xw-vs-tooltip-image'        => absint( $settings['tooltip_image_size'] ) . 'px',
        '--xw-vs-tooltip-padding'      => absint( $settings['tooltip_padding'] ) . 'px',
        '--xw-vs-tooltip-radius'       => absint( $settings['tooltip_radius'] ) . 'px',
        '--xw-vs-tooltip-bg'           => sanitize_hex_color( $settings['tooltip_background'] ),
        '--xw-vs-tooltip-color'        => sanitize_hex_color( $settings['tooltip_text_color'] ),
        '--xw-vs-clear-color'          => sanitize_hex_color( $settings['clear_color'] ),
        '--xw-vs-clear-hover-color'    => sanitize_hex_color( $settings['clear_hover_color'] ),
    );
    $declarations = '';
    foreach ( $variables as $property => $value ) {
        $declarations .= $property . ':' . esc_attr( $value ) . ';';
    }
    $css = '.single-product .variations_form{' . $declarations . '}';
    $css .= '.single-product .xw-vs-item--label{--xw-vs-label-radius:' . xw_vs_shape_radius( $settings['label_shape'] ) . ';}';
    $css .= '.single-product .xw-vs-item--color{--xw-vs-color-radius:' . xw_vs_shape_radius( $settings['color_shape'] ) . ';}';
    $css .= '.single-product .xw-vs-item--image{--xw-vs-image-radius:' . xw_vs_shape_radius( $settings['image_shape'] ) . ';}';
    if ( ! empty( $settings['label_flex'] ) ) {
        $css .= '.single-product .xw-vs-item--label{width:auto;flex:1 1 var(--xw-vs-label-min-width);}';
    }
    if ( empty( $settings['tooltip_enabled'] ) ) {
        $css .= '.single-product .xw-vs-tooltip{display:none!important;}';
    }
    return $css;
}

function xw_vs_shape_radius( $shape ) {
    if ( 'circle' === $shape ) {
        return '999px';
    }
    if ( 'rounded' === $shape ) {
        return '8px';
    }
    return '0px';
}

/**
 * Devuelve la primera imagen encontrada por valor para un atributo de variación.
 */
function xw_vs_variation_image_map( $product, $attribute ) {
    static $cache = array();
    $key = $product->get_id() . '|' . $attribute;
    if ( isset( $cache[ $key ] ) ) {
        return $cache[ $key ];
    }
    $cache[ $key ] = array();
    foreach ( $product->get_children() as $variation_id ) {
        $variation = wc_get_product( $variation_id );
        if ( ! $variation instanceof WC_Product_Variation || ! $variation->exists() ) {
            continue;
        }
        $attributes    = $variation->get_attributes();
        $attribute_key = 0 === strpos( $attribute, 'attribute_' ) ? substr( $attribute, 10 ) : $attribute;
        $value         = isset( $attributes[ $attribute ] ) ? (string) $attributes[ $attribute ] : ( isset( $attributes[ $attribute_key ] ) ? (string) $attributes[ $attribute_key ] : '' );
        $image_id      = $variation->get_image_id();
        if ( '' !== $value && $image_id && ! isset( $cache[ $key ][ $value ] ) ) {
            $cache[ $key ][ $value ] = $image_id;
        }
    }
    return $cache[ $key ];
}

function xw_vs_term_color( $term_id ) {
    $color = sanitize_hex_color( get_term_meta( $term_id, '_xw_vs_color', true ) );
    if ( ! $color ) {
        $color = sanitize_hex_color( get_term_meta( $term_id, 'product_attribute_color', true ) );
    }
    return $color ?: '#CCCCCC';
}

function xw_vs_term_image_id( $term_id ) {
    $image_id = absint( get_term_meta( $term_id, '_xw_vs_image_id', true ) );
    return $image_id ?: absint( get_term_meta( $term_id, 'product_attribute_image', true ) );
}

/**
 * Añade la interfaz visual después del select original de WooCommerce.
 */
function xw_vs_dropdown_html( $html, $args ) {
    if ( ! is_product() ) {
        return $html;
    }

    $product = isset( $args['product'] ) && $args['product'] instanceof WC_Product ? $args['product'] : wc_get_product();
    if ( ! $product || ! $product->is_type( 'variable' ) || empty( $args['attribute'] ) ) {
        return $html;
    }

    $settings  = xw_vs_get_settings();
    $attribute = $args['attribute'];
    $options   = isset( $args['options'] ) && is_array( $args['options'] ) ? $args['options'] : array();
    if ( empty( $options ) ) {
        $variation_attributes = $product->get_variation_attributes();
        $options = isset( $variation_attributes[ $attribute ] ) ? $variation_attributes[ $attribute ] : array();
    }
    if ( empty( $options ) ) {
        return $html;
    }

    $attribute_object = xw_vs_get_attribute_object( $attribute );
    $type = $attribute_object && isset( $attribute_object->type ) ? xw_vs_normalize_type( $attribute_object->type ) : 'select';
    if ( 'select' === $type ) {
        if ( empty( $settings['auto_label'] ) ) {
            return $html;
        }
        $type = 'label';
    }

    $terms = array();
    if ( taxonomy_exists( $attribute ) ) {
        foreach ( wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) ) as $term ) {
            $terms[ $term->slug ] = $term;
        }
    }
    $image_map = array();
    if ( ! empty( $settings['variation_images'] ) ) {
        if ( 'image' === $type ) {
            $image_map = xw_vs_variation_image_map( $product, $attribute );
        } elseif ( 'label' === $type && ! taxonomy_exists( $attribute ) ) {
            $local_attribute  = sanitize_title( str_replace( array( 'attribute_', 'pa_' ), '', $attribute ) );
            $local_label      = sanitize_title( wc_attribute_label( $attribute, $product ) );
            $image_attributes = array( 'color', 'colour', 'colores', 'imagen', 'imagenes', 'image', 'images' );
            if ( in_array( $local_attribute, $image_attributes, true ) || in_array( $local_label, $image_attributes, true ) ) {
                $image_map = xw_vs_variation_image_map( $product, $attribute );
                if ( ! empty( $image_map ) ) {
                    $type = 'image';
                }
            }
        }
    }
    $selected  = isset( $args['selected'] ) ? (string) $args['selected'] : '';
    $items     = '';

    foreach ( $options as $option ) {
        $slug = (string) $option;
        $term = isset( $terms[ $slug ] ) ? $terms[ $slug ] : false;
        $name = $term ? apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) : apply_filters( 'woocommerce_variation_option_name', $slug, null, $attribute, $product );
        $item_type = $type;
        $content = '';
        $tooltip_image = '';

        if ( 'color' === $type ) {
            $content = '<span class="xw-vs-color-chip" style="background-color:' . esc_attr( xw_vs_term_color( $term ? $term->term_id : 0 ) ) . '"></span>';
        } elseif ( 'image' === $type ) {
            $image_id = $term ? xw_vs_term_image_id( $term->term_id ) : 0;
            if ( ! $image_id && isset( $image_map[ $slug ] ) ) {
                $image_id = absint( $image_map[ $slug ] );
            }
            $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_gallery_thumbnail' ) : '';
            if ( $image_url ) {
                $content = '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( wp_strip_all_tags( $name ) ) . '" loading="lazy" decoding="async">';
                $tooltip_image = '<img src="' . esc_url( $image_url ) . '" alt="" loading="lazy" decoding="async">';
            } else {
                $item_type = 'label';
            }
        }
        if ( 'label' === $item_type ) {
            $custom_label = $term ? trim( (string) get_term_meta( $term->term_id, '_xw_vs_label', true ) ) : '';
            $content = '<span class="xw-vs-label-text">' . esc_html( $custom_label ?: $name ) . '</span>';
        }

        $is_selected = sanitize_title( $selected ) === sanitize_title( $slug );
        $items .= '<button type="button" class="xw-vs-item xw-vs-item--' . esc_attr( $item_type ) . ( $is_selected ? ' is-selected' : '' ) . '" data-value="' . esc_attr( $slug ) . '" data-label="' . esc_attr( wp_strip_all_tags( $name ) ) . '" role="radio" aria-checked="' . ( $is_selected ? 'true' : 'false' ) . '">';
        $items .= '<span class="xw-vs-item__content">' . $content . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Contenido construido y escapado arriba.
        $tooltip_text    = '<span class="xw-vs-tooltip__text">' . esc_html( $name ) . '</span>';
        $tooltip_content = $tooltip_image . $tooltip_text;
        if ( 'text' === $settings['tooltip_content'] ) {
            $tooltip_content = $tooltip_text;
        } elseif ( 'image' === $settings['tooltip_content'] && '' !== $tooltip_image ) {
            $tooltip_content = $tooltip_image;
        }
        $items .= '<span class="xw-vs-tooltip" role="tooltip">' . $tooltip_content . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Contenido construido y escapado arriba.
        $items .= '</button>';
    }

    if ( '' === $items ) {
        return $html;
    }

    $attribute_name = wc_variation_attribute_name( $attribute );
    return '<div class="xw-vs-control" data-attribute-name="' . esc_attr( $attribute_name ) . '"><span class="xw-vs-native-select">' . $html . '</span><div class="xw-vs-list" role="radiogroup" aria-label="' . esc_attr( wc_attribute_label( $attribute, $product ) ) . '">' . $items . '</div></div>';
}
