<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager as Controls;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

class XW_Smart_Filter_Widget extends Widget_Base {
    public function get_name() { return 'xw-smart-filter'; }
    public function get_title() { return 'Smart Filter'; }
    public function get_icon() { return 'eicon-filter'; }
    public function get_categories() { return array( 'xw-suite' ); }
    public function get_keywords() { return array( 'woocommerce', 'filter', 'filtro', 'loop', 'products' ); }
    public function get_style_depends() { return array( 'xw-sf-filter.css' ); }
    public function get_script_depends() { return array( 'xw-sf-filter.js' ); }
    protected function is_dynamic_content(): bool { return true; }

    protected function register_controls() {
        $this->start_controls_section( 'filter_settings', array( 'label' => 'Smart Filter' ) );
        $this->add_control( 'group_id', array(
            'label' => xw_t( 'Grupo de filtros', 'Filter group' ),
            'type' => Controls::SELECT,
            'options' => xw_sf_group_options(),
        ) );
        $this->add_control( 'grid_id', array(
            'label' => xw_t( 'ID del Loop Grid', 'Loop Grid ID' ),
            'type' => Controls::TEXT,
            'placeholder' => 'shop-products',
            'description' => xw_t( 'Escribe el ID de CSS de Loop Grid → Avanzado, sin #. Si lo dejas vacío, se usa el grupo conectado en la consulta del Loop Grid.', 'Enter the CSS ID from Loop Grid → Advanced, without #. If empty, the group connected in the Loop Grid query is used.' ),
        ) );
        $this->add_control( 'apply_mode', array(
            'label' => xw_t( 'Aplicar filtros', 'Apply filters' ),
            'type' => Controls::SELECT,
            'default' => 'live',
            'options' => array( 'live' => xw_t( 'Automáticamente (AJAX)', 'Automatically (AJAX)' ), 'manual' => xw_t( 'Con el botón Aplicar (AJAX)', 'With the Apply button (AJAX)' ) ),
        ) );
        foreach ( array(
            'active_filters' => xw_t( 'Mostrar filtros activos', 'Show active filters' ),
            'result_count' => xw_t( 'Mostrar contador de resultados', 'Show results count' ),
            'ajax_pagination' => xw_t( 'Paginar el Loop Grid con AJAX', 'Paginate the Loop Grid with AJAX' ),
        ) as $key => $label ) {
            $this->add_control( $key, array( 'label' => $label, 'type' => Controls::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
        }
        foreach ( array(
            'filter_heading' => array( xw_t( 'Título del panel', 'Panel heading' ), xw_t( 'Filtrar productos', 'Filter products' ) ),
            'all_label' => array( xw_t( 'Etiqueta sin selección', 'No selection label' ), xw_t( 'Todas las opciones', 'All options' ) ),
            'search_placeholder' => array( xw_t( 'Texto del buscador', 'Search placeholder' ), xw_t( 'Buscar productos…', 'Search products…' ) ),
            'apply_label' => array( xw_t( 'Botón Aplicar', 'Apply button' ), xw_t( 'Aplicar', 'Apply' ) ),
            'reset_label' => array( xw_t( 'Botón Limpiar', 'Reset button' ), xw_t( 'Limpiar', 'Clear' ) ),
        ) as $key => list( $label, $default ) ) {
            $this->add_control( $key, array( 'label' => $label, 'type' => Controls::TEXT, 'default' => $default ) );
        }
        $this->end_controls_section();

        $this->style_section( 'panel', xw_t( 'Panel y distribución', 'Panel and layout' ) );
        $this->add_responsive_control( 'field_columns', array(
            'label' => xw_t( 'Columnas de filtros', 'Filter columns' ), 'type' => Controls::SELECT,
            'options' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ), 'default' => '1', 'mobile_default' => '1',
            'selectors' => array( '{{WRAPPER}} .xw-sf__fields' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ),
        ) );
        $this->size_control( 'field_gap', xw_t( 'Espacio entre filtros', 'Space between filters' ), '.xw-sf__fields', 'gap' );
        $this->size_control( 'action_gap', xw_t( 'Espacio entre botones', 'Space between buttons' ), '.xw-sf__actions', 'gap' );
        $this->dimensions( 'panel_padding', xw_t( 'Relleno', 'Padding' ), '.xw-sf', 'padding' );
        $this->surface( 'panel', '.xw-sf' );
        $this->end_controls_section();

        $this->style_section( 'labels', xw_t( 'Título y etiquetas', 'Heading and labels' ) );
        $this->text_style( 'heading', xw_t( 'Título del panel', 'Panel heading' ), '.xw-sf__heading' );
        $this->size_control( 'heading_spacing', xw_t( 'Espacio debajo del título', 'Space below heading' ), '.xw-sf__heading', 'margin-block-end' );
        $this->text_style( 'legend', xw_t( 'Etiquetas de filtros', 'Filter labels' ), '.xw-sf__field legend' );
        $this->size_control( 'legend_spacing', xw_t( 'Espacio debajo de la etiqueta', 'Space below label' ), '.xw-sf__field legend', 'margin-block-end' );
        $this->end_controls_section();

        $this->style_section( 'fields', xw_t( 'Campos y desplegables', 'Inputs and dropdowns' ) );
        $this->text_style( 'input', xw_t( 'Texto del campo', 'Field text' ), '.xw-sf__input' );
        $this->color( 'placeholder', xw_t( 'Texto de ayuda', 'Placeholder text' ), '.xw-sf__input::placeholder, {{WRAPPER}} .xw-sf__range label', 'color' );
        $this->color( 'focus', xw_t( 'Borde al enfocar', 'Focus outline' ), '.xw-sf__input:focus-visible', 'outline-color' );
        $this->size_control( 'input_height', xw_t( 'Altura mínima', 'Minimum height' ), '.xw-sf__input', 'min-height' );
        $this->dimensions( 'input_padding', xw_t( 'Relleno', 'Padding' ), '.xw-sf__input', 'padding' );
        $this->surface( 'input', '.xw-sf__input' );
        $this->end_controls_section();

        $this->style_section( 'slider', xw_t( 'Barra de precio', 'Price slider' ) );
        $this->color( 'slider_track', xw_t( 'Color del carril', 'Track color' ), '.xw-sf__slider', '--xw-sf-track' );
        $this->color( 'slider_active', xw_t( 'Color del tramo seleccionado', 'Selected range color' ), '.xw-sf__slider', '--xw-sf-track-active' );
        $this->color( 'slider_thumb', xw_t( 'Color de los tiradores', 'Thumb color' ), '.xw-sf__slider', '--xw-sf-thumb' );
        $this->color( 'slider_thumb_border', xw_t( 'Borde de los tiradores', 'Thumb border' ), '.xw-sf__slider', '--xw-sf-thumb-border' );
        $this->end_controls_section();

        $this->style_section( 'choices', xw_t( 'Casillas y selección única', 'Checkboxes and radio buttons' ) );
        $this->text_style( 'choice', xw_t( 'Texto de la opción', 'Option text' ), '.xw-sf__choice' );
        $this->color( 'choice_active', xw_t( 'Texto seleccionado', 'Selected text' ), '.xw-sf__choice input:checked + span', 'color' );
        $this->color( 'choice_accent', xw_t( 'Color de selección', 'Selection color' ), '.xw-sf__choice input', 'accent-color' );
        $this->size_control( 'choice_gap', xw_t( 'Espacio entre opciones', 'Space between options' ), '.xw-sf__choices', 'gap' );
        $this->add_responsive_control( 'choice_layout', array(
            'label' => xw_t( 'Distribución', 'Layout' ), 'type' => Controls::SELECT,
            'options' => array( 'column' => xw_t( 'Vertical', 'Vertical' ), 'row' => xw_t( 'En línea', 'Inline' ) ),
            'selectors' => array( '{{WRAPPER}} .xw-sf__choices' => 'flex-direction: {{VALUE}};' ),
        ) );
        $this->end_controls_section();

        $this->button_style( 'apply', xw_t( 'Botón Aplicar', 'Apply button' ) );
        $this->button_style( 'reset', xw_t( 'Botón Limpiar', 'Reset button' ) );

        $this->style_section( 'status', xw_t( 'Avisos', 'Notices' ) );
        $this->text_style( 'status', xw_t( 'Texto del aviso', 'Notice text' ), '.xw-sf__status' );
        $this->text_style( 'count', xw_t( 'Texto del contador', 'Results count text' ), '.xw-sf__count' );
        $this->text_style( 'chips', xw_t( 'Texto de filtros activos', 'Active filter text' ), '.xw-sf__chip' );
        $this->color( 'chips_bg', xw_t( 'Fondo de filtros activos', 'Active filter background' ), '.xw-sf__chip', 'background-color' );
        $this->end_controls_section();
    }

    private function style_section( $key, $title ) {
        $this->start_controls_section( 'style_' . $key, array( 'label' => $title, 'tab' => Controls::TAB_STYLE ) );
    }

    private function color( $key, $label, $selector, $property ) {
        $this->add_control( $key, array(
            'label' => $label, 'type' => Controls::COLOR, 'selectors' => array( '{{WRAPPER}} ' . $selector => $property . ': {{VALUE}};' ),
        ) );
    }

    private function text_style( $key, $label, $selector ) {
        $this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => $key . '_typography', 'selector' => '{{WRAPPER}} ' . $selector ) );
        $this->color( $key . '_color', $label, $selector, 'color' );
    }

    private function size_control( $key, $label, $selector, $property ) {
        $this->add_responsive_control( $key, array(
            'label' => $label, 'type' => Controls::SLIDER, 'size_units' => array( 'px', 'em', 'rem' ),
            'range' => array( 'px' => array( 'min' => 0, 'max' => 100 ), 'em' => array( 'min' => 0, 'max' => 6, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 6, 'step' => 0.1 ) ),
            'selectors' => array( '{{WRAPPER}} ' . $selector => $property . ': {{SIZE}}{{UNIT}};' ),
        ) );
    }

    private function dimensions( $key, $label, $selector, $property ) {
        $this->add_responsive_control( $key, array(
            'label' => $label, 'type' => Controls::DIMENSIONS, 'size_units' => array( 'px', 'em', 'rem', '%' ),
            'selectors' => array( '{{WRAPPER}} ' . $selector => $property . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
        ) );
    }

    private function surface( $key, $selector ) {
        $args = array( 'selector' => '{{WRAPPER}} ' . $selector );
        $this->add_group_control( Group_Control_Background::get_type(), $args + array( 'name' => $key . '_background', 'types' => array( 'classic', 'gradient' ) ) );
        $this->add_group_control( Group_Control_Border::get_type(), $args + array( 'name' => $key . '_border' ) );
        $this->dimensions( $key . '_radius', xw_t( 'Radio del borde', 'Border radius' ), $selector, 'border-radius' );
        $this->add_group_control( Group_Control_Box_Shadow::get_type(), $args + array( 'name' => $key . '_shadow' ) );
    }

    private function button_style( $key, $title ) {
        $selector = '.xw-sf__' . $key;
        $this->style_section( $key, $title );
        $this->text_style( $key, xw_t( 'Color del texto', 'Text color' ), $selector );
        $this->dimensions( $key . '_padding', xw_t( 'Relleno', 'Padding' ), $selector, 'padding' );
        $this->surface( $key, $selector );
        $this->color( $key . '_hover_text', xw_t( 'Texto al pasar el cursor', 'Hover text' ), $selector . ':hover', 'color' );
        $this->color( $key . '_hover_background', xw_t( 'Fondo al pasar el cursor', 'Hover background' ), $selector . ':hover', 'background' );
        $this->color( $key . '_hover_border', xw_t( 'Borde al pasar el cursor', 'Hover border' ), $selector . ':hover', 'border-color' );
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $id = absint( $settings['group_id'] ?? 0 );
        $rows = xw_sf_group( $id );
        if ( $rows ) {
            xw_sf_form( $id, $rows, $settings );
        } elseif ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            echo '<p>' . esc_html( xw_t( 'Publica un grupo con filtros en Smart Filter y selecciónalo aquí.', 'Publish a group with filters in Smart Filter and select it here.' ) ) . '</p>';
        }
    }
}
