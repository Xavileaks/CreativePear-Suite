<?php
if ( ! defined( 'ABSPATH' ) || ! class_exists( '\Elementor\Widget_Base' ) ) { exit; }

class XW_Product_Savings_Widget extends \Elementor\Widget_Base {
    public function get_name() { return 'xw-product-savings'; }
    public function get_title() { return xw_t( 'Valor relativo y absoluto', 'Relative and absolute savings' ); }
    public function get_icon() { return 'eicon-product-price'; }
    public function get_categories() { return array( 'xw-suite' ); }
    public function get_keywords() { return array( 'ahorro', 'ahorra', 'descuento', 'porcentaje', 'relativo', 'absoluto', 'savings', 'sale', 'discount', 'price', 'woocommerce' ); }
    public function get_style_depends() { xw_product_savings_register_assets(); return array( 'xw-product-savings' ); }
    public function get_script_depends() { xw_product_savings_register_assets(); return array( 'xw-product-savings' ); }
    protected function is_dynamic_content(): bool { return true; }

    protected function register_controls() {
        $this->start_controls_section( 'savings_content', array( 'label' => xw_t( 'Ahorro del producto', 'Product savings' ) ) );
        $this->add_control( 'value_mode', array( 'label' => xw_t( 'Mostrar valor', 'Show value' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'relative', 'options' => array( 'relative' => xw_t( 'Relativo · porcentaje (%)', 'Relative · percentage (%)' ), 'absolute' => xw_t( 'Absoluto · importe', 'Absolute · amount' ) ) ) );
        $this->add_control( 'label', array( 'label' => xw_t( 'Texto', 'Text' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => xw_t( 'Ahorra', 'Save' ), 'label_block' => true, 'dynamic' => array( 'active' => true ) ) );
        $this->add_control( 'usage', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => esc_html( xw_t( 'Lee el producto actual. Colócalo junto al widget de precio. Sin oferta se oculta; en productos variables aparece al elegir una variación. El importe es por unidad y sigue los impuestos y la moneda de la tienda.', 'Uses the current product. Place it beside the price widget. Hidden without an active sale; variable products show savings after a variation is selected. The amount is per unit and follows the shop tax display and currency.' ) ) ) );
        $this->end_controls_section();

        $selector = '{{WRAPPER}} .xw-product-savings__badge';
        $this->start_controls_section( 'savings_style', array( 'label' => xw_t( 'Etiqueta', 'Badge' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
        $this->add_control( 'text_color', array( 'label' => xw_t( 'Color del texto', 'Text color' ), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( $selector => 'color: {{VALUE}};' ) ) );
        $this->add_control( 'background_color', array( 'label' => xw_t( 'Fondo', 'Background' ), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( $selector => 'background-color: {{VALUE}};' ) ) );
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => 'text_typography', 'selector' => $selector ) );
        $this->add_group_control( \Elementor\Group_Control_Border::get_type(), array( 'name' => 'badge_border', 'selector' => $selector ) );
        foreach ( array( 'border_radius' => array( xw_t( 'Redondeado', 'Border radius' ), 'border-radius' ), 'padding' => array( xw_t( 'Espacio interior', 'Padding' ), 'padding' ) ) as $id => $control ) {
            $this->add_responsive_control( $id, array( 'label' => $control[0], 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em', '%' ), 'selectors' => array( $selector => $control[1] . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
        }
        $this->add_responsive_control( 'alignment', array( 'label' => xw_t( 'Alineación', 'Alignment' ), 'type' => \Elementor\Controls_Manager::CHOOSE, 'options' => array( 'left' => array( 'title' => xw_t( 'Izquierda', 'Left' ), 'icon' => 'eicon-text-align-left' ), 'center' => array( 'title' => xw_t( 'Centro', 'Center' ), 'icon' => 'eicon-text-align-center' ), 'right' => array( 'title' => xw_t( 'Derecha', 'Right' ), 'icon' => 'eicon-text-align-right' ) ), 'selectors' => array( '{{WRAPPER}} .xw-product-savings' => 'text-align: {{VALUE}};' ) ) );
        $this->end_controls_section();
    }

    protected function render() {
        if ( ! xw_product_savings_enabled() ) { return; }
        $plugin = \Elementor\Plugin::$instance;
        $editing = $plugin->editor->is_edit_mode() || $plugin->preview->is_preview_mode();
        echo xw_product_savings_markup( xw_product_savings_current_product(), $this->get_settings_for_display(), $editing ); // Values escaped by the renderer.
    }
}
