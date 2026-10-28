<?php
if ( ! defined( 'ABSPATH' ) || ! class_exists( '\Elementor\Widget_Base' ) ) { exit; }

class XW_Product_Price_Widget extends \Elementor\Widget_Base {
    public function get_name() { return 'xw-product-price'; }
    public function get_title() { return xw_t( 'Precio en oferta', 'Sale price' ); }
    public function get_icon() { return 'eicon-product-price'; }
    public function get_categories() { return array( 'xw-suite' ); }
    public function get_keywords() { return array( 'precio', 'oferta', 'original', 'venta', 'price', 'sale', 'woocommerce' ); }
    public function get_style_depends() { xw_product_price_register_assets(); return array( 'xw-product-price' ); }
    public function get_script_depends() { xw_product_price_register_assets(); return array( 'xw-product-price' ); }
    protected function is_dynamic_content(): bool { return true; }

    protected function register_controls() {
        $root = '{{WRAPPER}} .xw-product-price';
        $this->start_controls_section( 'price_layout', array( 'label' => xw_t( 'Precios del producto', 'Product prices' ) ) );
        $this->add_responsive_control( 'vertical_alignment', array( 'label' => xw_t( 'Alineación vertical', 'Vertical alignment' ), 'type' => \Elementor\Controls_Manager::CHOOSE, 'default' => 'center', 'options' => array(
            'flex-start' => array( 'title' => xw_t( 'Arriba', 'Top' ), 'icon' => 'eicon-v-align-top' ),
            'center' => array( 'title' => xw_t( 'Medio', 'Middle' ), 'icon' => 'eicon-v-align-middle' ),
            'flex-end' => array( 'title' => xw_t( 'Abajo', 'Bottom' ), 'icon' => 'eicon-v-align-bottom' ),
        ), 'selectors' => array( $root => 'align-items: {{VALUE}};' ) ) );
        $this->add_responsive_control( 'alignment', array( 'label' => xw_t( 'Alineación horizontal', 'Horizontal alignment' ), 'type' => \Elementor\Controls_Manager::CHOOSE, 'options' => array(
            'flex-start' => array( 'title' => xw_t( 'Izquierda', 'Left' ), 'icon' => 'eicon-text-align-left' ),
            'center' => array( 'title' => xw_t( 'Centro', 'Center' ), 'icon' => 'eicon-text-align-center' ),
            'flex-end' => array( 'title' => xw_t( 'Derecha', 'Right' ), 'icon' => 'eicon-text-align-right' ),
        ), 'selectors' => array( $root => 'justify-content: {{VALUE}};' ) ) );
        $this->add_responsive_control( 'gap', array( 'label' => xw_t( 'Separación', 'Gap' ), 'type' => \Elementor\Controls_Manager::SLIDER, 'size_units' => array( 'px', 'em' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 100 ), 'em' => array( 'min' => 0, 'max' => 5, 'step' => 0.1 ) ), 'selectors' => array( $root => 'gap: {{SIZE}}{{UNIT}};' ) ) );
        $this->add_control( 'usage', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => esc_html( xw_t( 'Lee los precios de WooCommerce. Con oferta muestra el original tachado y el precio actual. Sin oferta muestra solo el precio normal con el estilo del precio actual. Los productos variables se actualizan al elegir una variación.', 'Reads WooCommerce prices. On sale, shows the crossed-out original and current price. Without a sale, the regular price uses the current-price style. Variable products update when a variation is selected.' ) ) ) );
        $this->end_controls_section();
        $this->price_style( 'current', xw_t( 'Precio actual / en oferta', 'Current / sale price' ) );
        $this->price_style( 'original', xw_t( 'Precio original tachado', 'Crossed-out original price' ) );
    }

    private function price_style( $role, $label ) {
        $selector = '{{WRAPPER}} .xw-product-price > .xw-product-price__' . $role;
        $this->start_controls_section( $role . '_style', array( 'label' => $label, 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
        $this->add_control( $role . '_color', array( 'label' => xw_t( 'Color del texto', 'Text color' ), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( $selector => 'color: {{VALUE}};' ) ) );
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => $role . '_typography', 'selector' => $selector ) );
        $this->add_group_control( \Elementor\Group_Control_Text_Stroke::get_type(), array( 'name' => $role . '_stroke', 'selector' => $selector ) );
        $this->add_group_control( \Elementor\Group_Control_Text_Shadow::get_type(), array( 'name' => $role . '_shadow', 'selector' => $selector ) );
        $this->add_control( $role . '_blend_mode', array( 'label' => xw_t( 'Modo de fusión', 'Blend mode' ), 'type' => \Elementor\Controls_Manager::SELECT, 'options' => array(
            'normal' => xw_t( 'Normal', 'Normal' ), 'multiply' => xw_t( 'Multiplicar', 'Multiply' ), 'screen' => xw_t( 'Trama', 'Screen' ), 'overlay' => xw_t( 'Superponer', 'Overlay' ), 'darken' => xw_t( 'Oscurecer', 'Darken' ), 'lighten' => xw_t( 'Aclarar', 'Lighten' ), 'color-dodge' => xw_t( 'Sobreexponer color', 'Color dodge' ), 'color-burn' => xw_t( 'Subexponer color', 'Color burn' ), 'hard-light' => xw_t( 'Luz fuerte', 'Hard light' ), 'soft-light' => xw_t( 'Luz suave', 'Soft light' ), 'difference' => xw_t( 'Diferencia', 'Difference' ), 'exclusion' => xw_t( 'Exclusión', 'Exclusion' ), 'hue' => xw_t( 'Tono', 'Hue' ), 'saturation' => xw_t( 'Saturación', 'Saturation' ), 'color' => 'Color', 'luminosity' => xw_t( 'Luminosidad', 'Luminosity' ),
        ), 'selectors' => array( $selector => 'mix-blend-mode: {{VALUE}};' ) ) );
        $this->end_controls_section();
    }

    protected function render() {
        if ( ! xw_product_price_enabled() ) { return; }
        $plugin = \Elementor\Plugin::$instance;
        echo xw_product_price_markup( xw_product_savings_current_product(), $plugin->editor->is_edit_mode() || $plugin->preview->is_preview_mode() ); // Sanitized by the renderer.
    }
}
