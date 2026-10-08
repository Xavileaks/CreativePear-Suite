<?php
/** Controles nativos de Elementor, aislados por instancia del widget. */
if ( ! defined( 'ABSPATH' ) || ! class_exists( '\Elementor\Widget_Base' ) ) { exit; }

abstract class XW_Wishlist_Widget extends \Elementor\Widget_Base {
    public function get_categories() { return array( 'xw-suite' ); }
    public function get_keywords() { return array( 'wishlist', 'desires', 'deseos', 'heart', 'woocommerce', 'creative pear' ); }
    public function get_style_depends() { return array( 'xw-wishlist' ); }
    public function get_script_depends() { return array( 'xw-wishlist' ); }
    protected function is_dynamic_content(): bool { return true; }
    protected function editing() { return \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode(); }
    protected function section( $id, $label, $style = false ) {
        $this->start_controls_section( $id, array( 'label' => $label, 'tab' => $style ? \Elementor\Controls_Manager::TAB_STYLE : \Elementor\Controls_Manager::TAB_CONTENT ) );
    }
    protected function text( $id, $label, $default ) {
        $this->add_control( $id, array( 'label' => $label, 'type' => \Elementor\Controls_Manager::TEXT, 'default' => $default, 'label_block' => true, 'dynamic' => array( 'active' => true ) ) );
    }
    protected function toggle( $id, $label, $default = 'yes' ) {
        $this->add_control( $id, array( 'label' => $label, 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => $default, 'return_value' => 'yes' ) );
    }
    protected function icon_control( $id, $label, $icon = 'fas fa-heart', $library = 'fa-solid' ) {
        $this->add_control( $id, array( 'label' => $label, 'type' => \Elementor\Controls_Manager::ICONS, 'default' => array( 'value' => $icon, 'library' => $library ) ) );
    }
    protected function alignment( $id, $label, $selector, $property = 'text-align' ) {
        $choices = array(
            'left' => array( 'title' => xw_t( 'Izquierda', 'Left' ), 'icon' => 'eicon-text-align-left' ),
            'center' => array( 'title' => xw_t( 'Centro', 'Center' ), 'icon' => 'eicon-text-align-center' ),
            'right' => array( 'title' => xw_t( 'Derecha', 'Right' ), 'icon' => 'eicon-text-align-right' ),
        );
        $control = array( 'label' => $label, 'type' => \Elementor\Controls_Manager::CHOOSE, 'options' => $choices, 'selectors' => array( $selector => $property . ': {{VALUE}};' ) );
        if ( 'justify-content' === $property ) { $control['selectors_dictionary'] = array( 'left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end' ); }
        $this->add_responsive_control( $id, $control );
    }
    protected function color( $id, $label, $selector, $property = 'color' ) {
        $this->add_control( $id, array( 'label' => $label, 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( $selector => $property . ': {{VALUE}};' ) ) );
    }
    protected function dimensions( $id, $label, $selector, $property = 'padding', $units = array( 'px', 'em', '%' ) ) {
        $this->add_responsive_control( $id, array( 'label' => $label, 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => $units,
            'selectors' => array( $selector => $property . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
    }
    protected function slider( $id, $label, $selector, $css, $min = 0, $max = 200, $default = null, $units = array( 'px' ) ) {
        $control = array( 'label' => $label, 'type' => \Elementor\Controls_Manager::SLIDER, 'size_units' => $units,
            'range' => array( 'px' => array( 'min' => $min, 'max' => $max ), '%' => array( 'min' => $min, 'max' => $max ) ), 'selectors' => array( $selector => $css ) );
        if ( null !== $default ) { $control['default'] = array( 'size' => $default, 'unit' => 'px' ); }
        $this->add_responsive_control( $id, $control );
    }
    protected function typography( $id, $selector ) {
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => $id, 'selector' => $selector ) );
    }
    protected function border( $id, $selector ) {
        $this->add_group_control( \Elementor\Group_Control_Border::get_type(), array( 'name' => $id, 'selector' => $selector ) );
        $this->dimensions( $id . '_radius', xw_t( 'Redondeo', 'Border radius' ), $selector, 'border-radius' );
    }
    protected function surface( $id, $selector, $type = true ) {
        if ( $type ) { $this->typography( $id . '_typography', $selector ); }
        $this->color( $id . '_color', xw_t( 'Color', 'Color' ), $selector );
        $this->color( $id . '_background', xw_t( 'Fondo', 'Background' ), $selector, 'background-color' );
        $this->dimensions( $id . '_padding', 'Padding', $selector );
        $this->border( $id . '_border', $selector );
    }
    protected function states( $id, $selector, $added = false ) {
        $this->start_controls_tabs( $id . '_states' );
        foreach ( array( 'normal' => xw_t( 'Normal', 'Normal' ), 'hover' => 'Hover' ) + ( $added ? array( 'added' => xw_t( 'Añadido', 'Added' ) ) : array() ) as $state => $label ) {
            $this->start_controls_tab( $id . '_' . $state . '_tab', array( 'label' => $label ) );
            $target = 'normal' === $state ? $selector : $selector . ( 'hover' === $state ? ':hover' : '.is-added' );
            $this->color( $id . '_' . $state . '_color', xw_t( 'Color del texto / icono', 'Text / icon color' ), $target );
            $this->color( $id . '_' . $state . '_bg', xw_t( 'Fondo', 'Background' ), $target, 'background-color' );
            $this->color( $id . '_' . $state . '_border', xw_t( 'Color del borde', 'Border color' ), $target, 'border-color' );
            $this->end_controls_tab();
        }
        $this->end_controls_tabs();
    }
    protected function icon_markup( $icon, $class = 'xw-wl-glyph' ) {
        echo '<span class="' . esc_attr( $class ) . '" aria-hidden="true">';
        if ( ! empty( $icon['value'] ) ) { \Elementor\Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) ); }
        echo '</span>';
    }
    protected function root_attributes( $settings, $type ) {
        echo ' class="xw-wl xw-wl-' . esc_attr( $type ) . '" data-xw-wl="' . esc_attr( $type ) . '"';
        if ( $this->editing() ) { echo ' data-xw-wl-editor="1"'; }
    }
}

class XW_Wishlist_Table_Widget extends XW_Wishlist_Widget {
    public function get_name() { return 'xw-wishlist-table'; }
    public function get_title() { return 'Wishlist — ' . xw_t( 'Tabla', 'Table' ); }
    public function get_icon() { return 'eicon-table'; }
    protected function register_controls() {
        $this->section( 'content', xw_t( 'Lista y columnas', 'List and columns' ) );
        $this->text( 'title', xw_t( 'Título', 'Title' ), xw_t( 'Mi lista de deseos', 'My wishlist' ) );
        $this->add_control( 'title_tag', array( 'label' => xw_t( 'Etiqueta HTML', 'HTML tag' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'h2', 'options' => array_combine( array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div' ), array( 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'div' ) ) ) );
        $this->toggle( 'show_heading', xw_t( 'Encabezados de tabla', 'Table headings' ) );
        foreach ( array( 'image' => xw_t( 'Foto', 'Image' ), 'select' => xw_t( 'Selección', 'Selection' ), 'date' => xw_t( 'Fecha', 'Date' ), 'stock' => xw_t( 'Disponibilidad', 'Stock' ) ) as $id => $label ) { $this->toggle( 'show_' . $id, $label ); }
        foreach ( array( 'name_heading' => array( 'Producto', 'Product name' ), 'price_heading' => array( 'Precio', 'Unit price' ), 'date_heading' => array( 'Fecha añadida', 'Date added' ), 'stock_heading' => array( 'Disponibilidad', 'Stock status' ), 'actions_heading' => array( 'Acciones', 'Actions' ) ) as $id => $label ) { $this->text( $id, xw_t( $label[0], $label[1] ), xw_t( $label[0], $label[1] ) ); }
        $this->end_controls_section();
        $this->section( 'content_actions', xw_t( 'Botones y eliminación', 'Buttons and removal' ) );
        $this->text( 'cart_text', xw_t( 'Añadir al carrito', 'Add to cart' ), xw_t( 'Añadir al carrito', 'Add to cart' ) );
        $this->text( 'options_text', xw_t( 'Elegir opciones', 'Select options' ), xw_t( 'Elegir opciones', 'Select options' ) );
        $this->text( 'view_text', xw_t( 'Ver producto', 'View product' ), xw_t( 'Ver producto', 'View product' ) );
        $this->toggle( 'show_bulk', xw_t( 'Botones de selección / añadir todos', 'Selected / add-all buttons' ) );
        $this->text( 'selected_text', xw_t( 'Añadir seleccionados', 'Add selected' ), xw_t( 'Añadir seleccionados al carrito', 'Add selected to cart' ) );
        $this->text( 'all_text', xw_t( 'Añadir todos', 'Add all' ), xw_t( 'Añadir todos al carrito', 'Add all to cart' ) );
        $this->icon_control( 'remove_icon', xw_t( 'Icono de eliminar', 'Remove icon' ), 'fas fa-times' );
        $this->icon_control( 'cart_icon', xw_t( 'Icono del botón', 'Button icon' ), 'fas fa-shopping-cart' );
        $this->icon_control( 'stock_icon', xw_t( 'Icono disponible', 'In-stock icon' ), 'fas fa-check' );
        $this->icon_control( 'unavailable_icon', xw_t( 'Icono agotado', 'Out-of-stock icon' ), 'fas fa-times' );
        $this->text( 'stock_text', xw_t( 'Texto disponible', 'In-stock text' ), xw_t( 'Disponible', 'In stock' ) );
        $this->text( 'unavailable_text', xw_t( 'Texto agotado', 'Out-of-stock text' ), xw_t( 'Agotado', 'Out of stock' ) );
        $this->end_controls_section();
        $this->section( 'content_sharing', xw_t( 'Compartir', 'Sharing' ) );
        $this->toggle( 'show_share', xw_t( 'Mostrar compartir', 'Show sharing' ) );
        $this->text( 'share_text', xw_t( 'Texto', 'Text' ), xw_t( 'Compartir en', 'Share on' ) );
        $this->add_control( 'share_help', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => esc_html( xw_t( 'Compartir crea un enlace público de solo lectura. No muestra datos de la cuenta. Configura la página de Wishlist en la suite.', 'Sharing creates a public read-only link. Account details are not exposed. Configure the Wishlist page in the suite.' ) ) ) );
        $repeater = new \Elementor\Repeater();
        $networks = array( 'facebook' => 'Facebook', 'x' => 'X / Twitter', 'pinterest' => 'Pinterest', 'whatsapp' => 'WhatsApp', 'email' => 'Email', 'copy' => xw_t( 'Copiar enlace', 'Copy link' ) );
        $repeater->add_control( 'network', array( 'label' => xw_t( 'Servicio', 'Service' ), 'type' => \Elementor\Controls_Manager::SELECT, 'options' => $networks, 'default' => 'copy' ) );
        $repeater->add_control( 'label', array( 'label' => xw_t( 'Nombre accesible', 'Accessible name' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => xw_t( 'Copiar enlace', 'Copy link' ) ) );
        $repeater->add_control( 'icon', array( 'label' => xw_t( 'Icono', 'Icon' ), 'type' => \Elementor\Controls_Manager::ICONS, 'default' => array( 'value' => 'fas fa-copy', 'library' => 'fa-solid' ) ) );
        foreach ( array( 'color' => array( 'Color', 'Color', '', 'color' ), 'background' => array( 'Fondo', 'Background', '', 'background-color' ), 'hover_color' => array( 'Color hover', 'Hover color', ':hover', 'color' ), 'hover_background' => array( 'Fondo hover', 'Hover background', ':hover', 'background-color' ) ) as $key => $style ) {
            $repeater->add_control( $key, array( 'label' => xw_t( $style[0], $style[1] ), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .xw-wl-share-button{{CURRENT_ITEM}}' . $style[2] => $style[3] . ': {{VALUE}};' ) ) );
        }
        $default = array();
        foreach ( array( 'facebook' => 'facebook-f', 'x' => 'twitter', 'pinterest' => 'pinterest-p', 'whatsapp' => 'whatsapp', 'copy' => 'copy', 'email' => 'envelope' ) as $network => $icon ) {
            $brand = ! in_array( $network, array( 'copy', 'email' ), true );
            $default[] = array( 'network' => $network, 'label' => $networks[ $network ], 'icon' => array( 'value' => ( $brand ? 'fab' : 'fas' ) . ' fa-' . $icon, 'library' => $brand ? 'fa-brands' : 'fa-solid' ) );
        }
        $this->add_control( 'share_icons', array( 'label' => xw_t( 'Servicios e iconos', 'Services and icons' ), 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'default' => $default, 'title_field' => '{{{ label }}}' ) );
        $this->end_controls_section();
        $this->section( 'content_empty', xw_t( 'Lista vacía', 'Empty list' ) );
        $this->text( 'empty_text', xw_t( 'Mensaje', 'Message' ), xw_t( 'Aún no has guardado productos.', 'You have not saved any products yet.' ) );
        $this->text( 'shop_text', xw_t( 'Botón de tienda', 'Shop button' ), xw_t( 'Explorar la tienda', 'Explore the shop' ) );
        $this->end_controls_section();

        $this->section( 'style_container', xw_t( 'Caja', 'Container' ), true );
        $this->surface( 'container', '{{WRAPPER}} .xw-wl-table' );
        $this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array( 'name' => 'container_shadow', 'selector' => '{{WRAPPER}} .xw-wl-table' ) );
        $this->end_controls_section();
        $this->section( 'style_title', xw_t( 'Título', 'Title' ), true );
        $this->typography( 'title_typography', '{{WRAPPER}} .xw-wl-title' );
        $this->color( 'title_color', xw_t( 'Color', 'Color' ), '{{WRAPPER}} .xw-wl-title' );
        $this->alignment( 'title_align', xw_t( 'Alineación', 'Alignment' ), '{{WRAPPER}} .xw-wl-title' );
        $this->dimensions( 'title_margin', xw_t( 'Margen', 'Margin' ), '{{WRAPPER}} .xw-wl-title', 'margin' );
        $this->end_controls_section();
        $this->section( 'style_headers', xw_t( 'Encabezados', 'Headings' ), true );
        $this->surface( 'header', '{{WRAPPER}} .xw-wl-grid th' );
        $this->end_controls_section();
        $this->section( 'style_rows', xw_t( 'Filas y celdas', 'Rows and cells' ), true );
        $this->surface( 'cells', '{{WRAPPER}} .xw-wl-grid td' );
        $this->color( 'row_alt', xw_t( 'Fondo alterno', 'Alternate background' ), '{{WRAPPER}} .xw-wl-grid tbody tr:nth-child(even) td', 'background-color' );
        $this->color( 'row_hover', xw_t( 'Fondo hover', 'Hover background' ), '{{WRAPPER}} .xw-wl-grid tbody tr:hover td', 'background-color' );
        $this->slider( 'row_height', xw_t( 'Altura mínima de filas', 'Minimum row height' ), '{{WRAPPER}} .xw-wl-grid tbody tr', 'height: {{SIZE}}{{UNIT}};', 0, 250 );
        $this->color( 'check_color', xw_t( 'Color de selección', 'Checkbox color' ), '{{WRAPPER}} .xw-wl-grid input[type="checkbox"]', 'accent-color' );
        $this->end_controls_section();
        foreach ( array( 'name' => xw_t( 'Nombre del producto', 'Product name' ), 'price' => xw_t( 'Precio', 'Price' ), 'date' => xw_t( 'Fecha', 'Date' ), 'stock' => xw_t( 'Disponibilidad', 'Stock' ), 'actions' => xw_t( 'Columna de botones', 'Button column' ) ) as $column => $label ) {
            $selector = '{{WRAPPER}} .xw-wl-col-' . $column;
            $this->section( 'style_col_' . $column, $label, true );
            $this->alignment( $column . '_align', xw_t( 'Alineación', 'Alignment' ), $selector );
            $this->typography( $column . '_typography', $selector . ' [data-xw-wl-field]' );
            $this->color( $column . '_color', xw_t( 'Color', 'Color' ), $selector . ' [data-xw-wl-field]' );
            $this->dimensions( $column . '_padding', 'Padding', $selector );
            $this->slider( $column . '_width', xw_t( 'Ancho', 'Width' ), $selector, 'width: {{SIZE}}{{UNIT}};', 0, 800, null, array( 'px', '%' ) );
            if ( 'name' === $column ) { $this->color( 'name_hover', xw_t( 'Color hover', 'Hover color' ), $selector . ' a:hover' ); }
            if ( 'stock' === $column ) {
                $this->color( 'stock_available', xw_t( 'Disponible', 'Available' ), '{{WRAPPER}} .xw-wl-stock.is-available' );
                $this->color( 'stock_unavailable', xw_t( 'Agotado', 'Unavailable' ), '{{WRAPPER}} .xw-wl-stock.is-unavailable' );
            }
            $this->end_controls_section();
        }
        $this->section( 'style_image', xw_t( 'Foto', 'Image' ), true );
        $this->slider( 'image_size', xw_t( 'Tamaño', 'Size' ), '{{WRAPPER}} .xw-wl-image', 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};', 20, 300, 74 );
        $this->add_control( 'image_shape', array( 'label' => xw_t( 'Forma', 'Shape' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'square', 'options' => array( 'square' => xw_t( 'Cuadrada', 'Square' ), 'rounded' => xw_t( 'Bordes redondeados', 'Rounded corners' ), 'circle' => xw_t( 'Redonda', 'Circle' ) ), 'selectors_dictionary' => array( 'square' => '0', 'rounded' => '12px', 'circle' => '50%' ), 'selectors' => array( '{{WRAPPER}} .xw-wl-image' => 'border-radius: {{VALUE}};' ) ) );
        $this->add_control( 'image_fit', array( 'label' => xw_t( 'Ajuste', 'Fit' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'contain', 'options' => array( 'contain' => xw_t( 'Contener', 'Contain' ), 'cover' => xw_t( 'Cubrir', 'Cover' ) ), 'selectors' => array( '{{WRAPPER}} .xw-wl-image' => 'object-fit: {{VALUE}};' ) ) );
        $this->surface( 'image', '{{WRAPPER}} .xw-wl-image', false );
        $this->end_controls_section();
        $this->section( 'style_remove', xw_t( 'Eliminar', 'Remove' ), true );
        $this->slider( 'remove_size', xw_t( 'Tamaño del icono', 'Icon size' ), '{{WRAPPER}} .xw-wl-remove', 'font-size: {{SIZE}}{{UNIT}};', 8, 80, 16 );
        $this->dimensions( 'remove_padding', 'Padding', '{{WRAPPER}} .xw-wl-remove' );
        $this->border( 'remove_border', '{{WRAPPER}} .xw-wl-remove' );
        $this->states( 'remove', '{{WRAPPER}} .xw-wl-remove' );
        $this->end_controls_section();
        foreach ( array( 'row_button' => array( xw_t( 'Botones de producto', 'Product buttons' ), '{{WRAPPER}} .xw-wl-row-action' ), 'bulk' => array( xw_t( 'Botones inferiores', 'Bottom buttons' ), '{{WRAPPER}} .xw-wl-bulk-button' ), 'share' => array( xw_t( 'Iconos de compartir', 'Sharing icons' ), '{{WRAPPER}} .xw-wl-share-button' ) ) as $id => $style ) {
            $this->section( 'style_' . $id, $style[0], true );
            $this->typography( $id . '_typography', $style[1] );
            $this->dimensions( $id . '_padding', 'Padding', $style[1] );
            $this->border( $id . '_border', $style[1] );
            $this->slider( $id . '_icon_size', xw_t( 'Tamaño del icono', 'Icon size' ), $style[1] . ' .xw-wl-glyph', 'font-size: {{SIZE}}{{UNIT}};', 8, 80 );
            $this->states( $id, $style[1] );
            $this->end_controls_section();
        }
        $this->section( 'style_footer', xw_t( 'Espacios y alineación inferiores', 'Bottom spacing and alignment' ), true );
        foreach ( array( 'bulk' => '.xw-wl-bulk', 'share' => '.xw-wl-share' ) as $id => $selector ) {
            $this->alignment( $id . '_align', ucfirst( $id ), '{{WRAPPER}} ' . $selector, 'justify-content' );
            $this->dimensions( $id . '_area_padding', 'Padding ' . $id, '{{WRAPPER}} ' . $selector );
            $this->slider( $id . '_gap', xw_t( 'Separación', 'Gap' ) . ' ' . $id, '{{WRAPPER}} ' . $selector, 'gap: {{SIZE}}{{UNIT}};', 0, 80 );
        }
        $this->typography( 'share_label_typography', '{{WRAPPER}} .xw-wl-share-label' );
        $this->color( 'share_label_color', xw_t( 'Color del texto compartir', 'Share label color' ), '{{WRAPPER}} .xw-wl-share-label' );
        $this->end_controls_section();
        $this->section( 'style_empty', xw_t( 'Lista vacía y mensajes', 'Empty list and messages' ), true );
        $this->typography( 'empty_typography', '{{WRAPPER}} .xw-wl-empty' );
        $this->color( 'empty_color', xw_t( 'Color', 'Color' ), '{{WRAPPER}} .xw-wl-empty' );
        $this->dimensions( 'empty_padding', 'Padding', '{{WRAPPER}} .xw-wl-empty' );
        $this->typography( 'status_typography', '{{WRAPPER}} .xw-wl-status' );
        $this->color( 'status_color', xw_t( 'Color de mensajes', 'Message color' ), '{{WRAPPER}} .xw-wl-status' );
        $this->end_controls_section();
    }
    protected function render() {
        $s = $this->get_settings_for_display();
        $columns = array();
        if ( 'yes' === $s['show_select'] ) { $columns['select'] = ''; }
        $columns['remove'] = '';
        if ( 'yes' === $s['show_image'] ) { $columns['image'] = ''; }
        $columns['name'] = $s['name_heading']; $columns['price'] = $s['price_heading'];
        foreach ( array( 'date', 'stock' ) as $key ) { if ( 'yes' === $s[ 'show_' . $key ] ) { $columns[ $key ] = $s[ $key . '_heading' ]; } }
        $columns['actions'] = $s['actions_heading'];
        $labels = array();
        foreach ( array( 'cart', 'options', 'view', 'stock', 'unavailable' ) as $key ) { $labels[ $key ] = $s[ $key . '_text' ]; }
        echo '<section'; $this->root_attributes( $s, 'table' );
        echo ' data-xw-wl-labels="' . esc_attr( wp_json_encode( $labels ) ) . '"';
        if ( $this->editing() ) {
            $preview = array();
            foreach ( wc_get_products( array( 'limit' => 3, 'status' => 'publish' ) ) as $product ) { $preview[] = array( 'id' => $product->get_id(), 'added' => time() ); }
            echo ' data-xw-wl-preview="' . esc_attr( wp_json_encode( xw_wishlist_payload( $preview ) ) ) . '"';
        }
        echo '>';
        if ( $s['title'] ) { $tag = in_array( $s['title_tag'], array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div' ), true ) ? $s['title_tag'] : 'h2'; echo '<' . $tag . ' class="xw-wl-title">' . esc_html( $s['title'] ) . '</' . $tag . '>'; }
        echo '<p class="xw-wl-status" role="status" aria-live="polite"></p>';
        echo '<div class="xw-wl-empty" hidden><p>' . esc_html( $s['empty_text'] ) . '</p><a class="xw-wl-row-action" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html( $s['shop_text'] ) . '</a></div>';
        echo '<div class="xw-wl-data" aria-busy="true"><table class="xw-wl-grid"><caption class="xw-wl-sr">' . esc_html( $s['title'] ?: xw_t( 'Lista de deseos', 'Wishlist' ) ) . '</caption>';
        if ( 'yes' === $s['show_heading'] ) {
            echo '<thead><tr>';
            foreach ( $columns as $key => $label ) {
                echo '<th scope="col" class="xw-wl-col-' . esc_attr( $key ) . '">';
                if ( 'select' === $key ) { echo '<label class="xw-wl-check"><input type="checkbox" data-xw-wl-select-all><span class="xw-wl-sr">' . esc_html( xw_t( 'Seleccionar todos', 'Select all' ) ) . '</span></label>'; }
                else { echo $label ? esc_html( $label ) : '<span class="xw-wl-sr">' . esc_html( 'image' === $key ? xw_t( 'Foto', 'Image' ) : xw_t( 'Eliminar', 'Remove' ) ) . '</span>'; }
                echo '</th>';
            }
            echo '</tr></thead>';
        }
        echo '<tbody></tbody></table></div><template data-xw-wl-row><tr>';
        foreach ( $columns as $key => $label ) {
            echo '<td class="xw-wl-col-' . esc_attr( $key ) . '" data-label="' . esc_attr( $label ) . '">';
            switch ( $key ) {
                case 'select': echo '<label class="xw-wl-check"><input type="checkbox" data-xw-wl-select><span class="xw-wl-sr" data-xw-wl-select-label></span></label>'; break;
                case 'remove': echo '<button type="button" class="xw-wl-remove" data-xw-wl-remove>'; $this->icon_markup( $s['remove_icon'] ); echo '</button>'; break;
                case 'image': echo '<a data-xw-wl-link><img class="xw-wl-image" width="74" height="74" loading="lazy" decoding="async" alt=""></a>'; break;
                case 'name': echo '<a class="xw-wl-product-name" data-xw-wl-field="name" data-xw-wl-link></a>'; break;
                case 'stock': echo '<span class="xw-wl-stock" data-xw-wl-field="stock">'; $this->icon_markup( $s['stock_icon'], 'xw-wl-glyph xw-wl-stock-in' ); $this->icon_markup( $s['unavailable_icon'], 'xw-wl-glyph xw-wl-stock-out' ); echo '<span data-xw-wl-stock-text></span></span>'; break;
                case 'actions': echo '<button class="xw-wl-row-action" type="button" data-xw-wl-cart-row>'; $this->icon_markup( $s['cart_icon'] ); echo '<span data-xw-wl-action-text></span></button>'; break;
                default: echo '<span data-xw-wl-field="' . esc_attr( $key ) . '"></span>';
            }
            echo '</td>';
        }
        echo '</tr></template>';
        if ( 'yes' === $s['show_bulk'] ) { echo '<div class="xw-wl-bulk"><button type="button" class="xw-wl-bulk-button" data-xw-wl-cart-selected>' . esc_html( $s['selected_text'] ) . '</button><button type="button" class="xw-wl-bulk-button" data-xw-wl-cart-all>' . esc_html( $s['all_text'] ) . '</button></div>'; }
        if ( 'yes' === $s['show_share'] ) {
            echo '<div class="xw-wl-share"><span class="xw-wl-share-label">' . esc_html( $s['share_text'] ) . '</span>';
            foreach ( (array) $s['share_icons'] as $share ) {
                if ( ! in_array( $share['network'] ?? '', array( 'facebook', 'x', 'pinterest', 'whatsapp', 'email', 'copy' ), true ) ) { continue; }
                echo '<button type="button" class="xw-wl-share-button elementor-repeater-item-' . esc_attr( $share['_id'] ?? '' ) . '" data-xw-wl-share="' . esc_attr( $share['network'] ) . '" aria-label="' . esc_attr( $share['label'] ) . '" title="' . esc_attr( $share['label'] ) . '">'; $this->icon_markup( $share['icon'] ); echo '</button>';
            }
            echo '</div>';
        }
        echo '<noscript>' . esc_html( xw_t( 'Activa JavaScript para ver tu lista de deseos.', 'Enable JavaScript to view your wishlist.' ) ) . '</noscript></section>';
    }
}

class XW_Wishlist_Counter_Widget extends XW_Wishlist_Widget {
    public function get_name() { return 'xw-wishlist-counter'; }
    public function get_title() { return 'Wishlist — ' . xw_t( 'Contador', 'Counter' ); }
    public function get_icon() { return 'eicon-counter'; }
    protected function register_controls() {
        $this->section( 'content', xw_t( 'Icono y enlace', 'Icon and link' ) );
        $this->icon_control( 'icon', xw_t( 'Icono', 'Icon' ) );
        $this->text( 'accessible_label', xw_t( 'Nombre accesible', 'Accessible name' ), xw_t( 'Ver lista de deseos', 'View wishlist' ) );
        $this->add_control( 'link', array( 'label' => xw_t( 'Enlace personalizado', 'Custom link' ), 'type' => \Elementor\Controls_Manager::URL, 'dynamic' => array( 'active' => true ), 'description' => xw_t( 'Vacío: usa la página elegida en la suite.', 'Empty: use the page selected in the suite.' ), 'options' => array( 'url' ) ) );
        $this->toggle( 'show_zero', xw_t( 'Mostrar contador en cero', 'Show zero count' ), '' );
        $this->add_control( 'preview_count', array( 'label' => xw_t( 'Cantidad de ejemplo en editor', 'Editor preview count' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 4, 'min' => 0, 'max' => 200, 'description' => xw_t( 'Fuera del editor se usa siempre la cantidad real.', 'The frontend always uses the actual count.' ) ) );
        $this->end_controls_section();
        $this->section( 'style_icon', xw_t( 'Icono', 'Icon' ), true );
        $this->alignment( 'align', xw_t( 'Alineación', 'Alignment' ), '{{WRAPPER}} .xw-wl-counter', 'justify-content' );
        $this->slider( 'icon_size', xw_t( 'Tamaño', 'Size' ), '{{WRAPPER}} .xw-wl-counter-link', 'font-size: {{SIZE}}{{UNIT}};', 8, 150, 24 );
        $this->dimensions( 'icon_padding', 'Padding', '{{WRAPPER}} .xw-wl-counter-link' );
        $this->border( 'icon_border', '{{WRAPPER}} .xw-wl-counter-link' );
        $this->states( 'icon', '{{WRAPPER}} .xw-wl-counter-link' );
        $this->end_controls_section();
        $this->section( 'style_badge', xw_t( 'Contador', 'Counter' ), true );
        $this->typography( 'badge_typography', '{{WRAPPER}} .xw-wl-badge' );
        $this->color( 'badge_color', xw_t( 'Color del número', 'Number color' ), '{{WRAPPER}} .xw-wl-badge' );
        $this->color( 'badge_bg', xw_t( 'Fondo', 'Background' ), '{{WRAPPER}} .xw-wl-badge', 'background-color' );
        $this->slider( 'badge_x', xw_t( 'Desplazamiento horizontal', 'Horizontal offset' ), '{{WRAPPER}} .xw-wl-badge', '--xw-wl-badge-x: {{SIZE}}{{UNIT}};', -150, 150, 6 );
        $this->slider( 'badge_y', xw_t( 'Desplazamiento vertical', 'Vertical offset' ), '{{WRAPPER}} .xw-wl-badge', '--xw-wl-badge-y: {{SIZE}}{{UNIT}};', -150, 150, -6 );
        $this->slider( 'badge_min_width', xw_t( 'Ancho mínimo', 'Minimum width' ), '{{WRAPPER}} .xw-wl-badge', 'min-width: {{SIZE}}{{UNIT}};', 0, 100, 20 );
        $this->dimensions( 'badge_padding', 'Padding', '{{WRAPPER}} .xw-wl-badge' );
        $this->border( 'badge_border', '{{WRAPPER}} .xw-wl-badge' );
        $this->end_controls_section();
    }
    protected function render() {
        $s = $this->get_settings_for_display();
        $url = $s['link']['url'] ?? '';
        if ( ! $url ) { $url = xw_wishlist_page_url(); }
        echo '<div'; $this->root_attributes( $s, 'counter' ); echo ' data-xw-wl-show-zero="' . esc_attr( $s['show_zero'] ) . '" data-xw-wl-preview-count="' . absint( $s['preview_count'] ) . '">';
        echo '<a class="xw-wl-counter-link"' . ( $url ? ' href="' . esc_url( $url ) . '"' : ' aria-disabled="true"' ) . ' data-xw-wl-counter-label="' . esc_attr( $s['accessible_label'] ) . '" aria-label="' . esc_attr( $s['accessible_label'] ) . '">';
        $this->icon_markup( $s['icon'] );
        echo '<span class="xw-wl-badge" data-xw-wl-count aria-hidden="true" hidden>0</span><span class="xw-wl-sr" data-xw-wl-count-label></span></a>';
        if ( $this->editing() && ! $url ) { echo '<small class="xw-wl-setup">' . esc_html( xw_t( 'Elige la página de Wishlist en la suite o introduce un enlace.', 'Choose the Wishlist page in the suite or enter a link.' ) ) . '</small>'; }
        echo '<span class="xw-wl-sr xw-wl-status" role="status" aria-live="polite"></span></div>';
    }
}

class XW_Wishlist_Add_Widget extends XW_Wishlist_Widget {
    public function get_name() { return 'xw-wishlist-add'; }
    public function get_title() { return 'Wishlist — ' . xw_t( 'Añadir', 'Add' ); }
    public function get_icon() { return 'eicon-heart'; }
    protected function register_controls() {
        $this->section( 'content', xw_t( 'Producto y contenido', 'Product and content' ) );
        $this->add_control( 'product_id', array( 'label' => xw_t( 'ID de producto (opcional)', 'Product ID (optional)' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 0, 'default' => 0, 'description' => xw_t( 'Deja 0 para detectar el producto del loop o de la ficha individual.', 'Leave 0 to detect the loop or single product.' ) ) );
        $this->add_control( 'display', array( 'label' => xw_t( 'Mostrar', 'Display' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'both', 'options' => array( 'icon' => xw_t( 'Solo icono', 'Icon only' ), 'text' => xw_t( 'Solo texto', 'Text only' ), 'both' => xw_t( 'Icono y texto', 'Icon and text' ) ) ) );
        $this->icon_control( 'icon', xw_t( 'Icono normal', 'Normal icon' ), 'far fa-heart', 'fa-regular' );
        $this->icon_control( 'added_icon', xw_t( 'Icono añadido', 'Added icon' ) );
        $this->text( 'label', xw_t( 'Texto normal', 'Normal text' ), xw_t( 'Añadir a Wishlist', 'Add to wishlist' ) );
        $this->text( 'added_label', xw_t( 'Texto añadido', 'Added text' ), xw_t( 'Ya está en Wishlist', 'Already in wishlist' ) );
        $this->add_control( 'icon_position', array( 'label' => xw_t( 'Posición del icono', 'Icon position' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'before', 'options' => array( 'before' => xw_t( 'Antes', 'Before' ), 'after' => xw_t( 'Después', 'After' ) ), 'selectors_dictionary' => array( 'before' => 'row', 'after' => 'row-reverse' ), 'selectors' => array( '{{WRAPPER}} .xw-wl-add-button' => 'flex-direction: {{VALUE}};' ) ) );
        $this->add_control( 'added_action', array( 'label' => xw_t( 'Al pulsar un producto añadido', 'When clicking an added product' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'remove', 'options' => array( 'remove' => xw_t( 'Quitar de Wishlist', 'Remove from wishlist' ), 'view' => xw_t( 'Abrir Wishlist', 'Open wishlist' ) ) ) );
        $this->end_controls_section();
        $this->section( 'style_button', xw_t( 'Botón / caja del icono', 'Button / icon box' ), true );
        $this->alignment( 'align', xw_t( 'Alineación', 'Alignment' ), '{{WRAPPER}} .xw-wl-add', 'justify-content' );
        $this->typography( 'button_typography', '{{WRAPPER}} .xw-wl-add-button' );
        $this->slider( 'icon_size', xw_t( 'Tamaño del icono', 'Icon size' ), '{{WRAPPER}} .xw-wl-add-button .xw-wl-glyph', 'font-size: {{SIZE}}{{UNIT}};', 8, 120, 20 );
        $this->slider( 'button_min_width', xw_t( 'Ancho mínimo', 'Minimum width' ), '{{WRAPPER}} .xw-wl-add-button', 'min-width: {{SIZE}}{{UNIT}};', 0, 600 );
        $this->slider( 'button_min_height', xw_t( 'Altura mínima', 'Minimum height' ), '{{WRAPPER}} .xw-wl-add-button', 'min-height: {{SIZE}}{{UNIT}};', 0, 180 );
        $this->slider( 'icon_gap', xw_t( 'Separación de icono y texto', 'Icon and text gap' ), '{{WRAPPER}} .xw-wl-add-button', 'gap: {{SIZE}}{{UNIT}};', 0, 60, 8 );
        $this->dimensions( 'button_padding', 'Padding', '{{WRAPPER}} .xw-wl-add-button' );
        $this->add_control( 'button_shape', array( 'label' => xw_t( 'Forma', 'Shape' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'rounded', 'options' => array( 'square' => xw_t( 'Cuadrada', 'Square' ), 'rounded' => xw_t( 'Redondeada', 'Rounded' ), 'circle' => xw_t( 'Circular / píldora', 'Circle / pill' ) ), 'selectors_dictionary' => array( 'square' => '0', 'rounded' => '6px', 'circle' => '999px' ), 'selectors' => array( '{{WRAPPER}} .xw-wl-add-button' => 'border-radius: {{VALUE}};' ) ) );
        $this->border( 'button_border', '{{WRAPPER}} .xw-wl-add-button' );
        $this->states( 'button', '{{WRAPPER}} .xw-wl-add-button', true );
        $this->end_controls_section();
    }
    protected function render() {
        $s = $this->get_settings_for_display();
        $product = absint( $s['product_id'] ) ? wc_get_product( absint( $s['product_id'] ) ) : wc_get_product( get_the_ID() );
        if ( ! $product && ! absint( $s['product_id'] ) && isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof WC_Product ) { $product = $GLOBALS['product']; }
        if ( ! $product && $this->editing() ) { $products = wc_get_products( array( 'limit' => 1, 'status' => 'publish' ) ); $product = $products ? $products[0] : false; }
        if ( ! $product ) { if ( $this->editing() ) { echo '<p class="xw-wl-setup">' . esc_html( xw_t( 'Elige un producto de vista previa para el loop o la plantilla.', 'Choose a preview product for the loop or template.' ) ) . '</p>'; } return; }
        echo '<div'; $this->root_attributes( $s, 'add' ); echo '>';
        echo '<button type="button" class="xw-wl-add-button xw-wl-display-' . esc_attr( $s['display'] ) . '" data-xw-wl-add="' . absint( $product->get_id() ) . '" data-xw-wl-label="' . esc_attr( $s['label'] ) . '" data-xw-wl-added-label="' . esc_attr( $s['added_label'] ) . '" data-xw-wl-product-name="' . esc_attr( $product->get_name() ) . '" data-xw-wl-added-action="' . esc_attr( $s['added_action'] ) . '" data-xw-wl-url="' . esc_url( xw_wishlist_page_url() ) . '" aria-pressed="false" aria-label="' . esc_attr( $s['label'] . ': ' . $product->get_name() ) . '">';
        $this->icon_markup( $s['icon'], 'xw-wl-glyph xw-wl-icon-normal' ); $this->icon_markup( $s['added_icon'], 'xw-wl-glyph xw-wl-icon-added' );
        echo '<span class="xw-wl-add-label">' . esc_html( $s['label'] ) . '</span></button><span class="xw-wl-sr xw-wl-status" role="status" aria-live="polite"></span></div>';
    }
}
