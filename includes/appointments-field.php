<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class XW_Appointment_Field extends \ElementorPro\Modules\Forms\Fields\Field_Base {
    public function get_type() { return 'xw_appointment'; }
    public function get_name() { return xw_t( 'Cita', 'Appointment' ); }
    public function get_script_depends(): array { return array( 'xw-appointments' ); }
    public function get_style_depends(): array { return array( 'xw-appointments' ); }

    public function render( $item, $item_index, $form ) {
        xw_appt_assets();
        wp_enqueue_style( 'xw-appointments' ); wp_enqueue_script( 'xw-appointments' );
        $id = $form->get_attribute_id( $item );
        $size = $form->get_settings_for_display( 'input_size' );
        $label = $item['field_label'] ?: xw_t( 'Cita', 'Appointment' );
        echo '<div class="xw-appointment" data-xw-appointment>';
        echo '<div class="xw-appointment-date"><input type="text" readonly autocomplete="off" inputmode="none" class="elementor-field elementor-size-' . esc_attr( $size ) . ' elementor-field-textual xw-appointment-date-input" id="' . esc_attr( $id ) . '" aria-label="' . esc_attr( $label ) . '" placeholder="' . esc_attr( $item['placeholder'] ?: xw_t( 'Selecciona fecha y hora', 'Select date and time' ) ) . '">';
        echo '<button type="button" class="xw-appointment-open" aria-label="' . esc_attr( xw_t( 'Abrir calendario de citas', 'Open appointment calendar' ) ) . '"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18M8 15h2M14 15h2"/></svg></button></div>';
        echo '<label class="xw-appointment-time-label" for="' . esc_attr( $id . '-time' ) . '">' . esc_html( xw_t( 'Hora de la cita', 'Appointment time' ) ) . '</label><select id="' . esc_attr( $id . '-time' ) . '" class="elementor-field elementor-field-textual xw-appointment-time" disabled><option value="">' . esc_html( xw_t( 'Selecciona primero una fecha', 'Select a date first' ) ) . '</option></select>';
        echo '<input type="hidden" class="xw-appointment-value" name="' . esc_attr( $form->get_attribute_name( $item ) ) . '" value="">';
        echo '<p class="xw-appointment-status" role="status" aria-live="polite"></p><button type="button" class="xw-appointment-retry" hidden>' . esc_html( xw_t( 'Reintentar', 'Retry' ) ) . '</button>';
        echo '<small class="xw-appointment-zone">' . esc_html( sprintf( xw_t( 'Zona horaria: %s. Cita sujeta a confirmación.', 'Timezone: %s. Appointment subject to confirmation.' ), wp_timezone_string() ) ) . '</small></div>';
    }

    public function update_controls( $widget ) {
        $data = \ElementorPro\Plugin::elementor()->controls_manager->get_control_from_stack( $widget->get_unique_name(), 'form_fields' );
        if ( is_wp_error( $data ) ) { return; }
        $controls = array();
        foreach ( array( 'name' => xw_t( 'ID del campo de nombre', 'Name field ID' ), 'email' => xw_t( 'ID del campo de correo', 'Email field ID' ), 'phone' => xw_t( 'ID del campo de teléfono', 'Phone field ID' ) ) as $key => $label ) {
            $name = 'xw_appt_' . $key;
            $controls[ $name ] = array( 'name' => $name, 'label' => $label, 'type' => \Elementor\Controls_Manager::TEXT,
                'description' => xw_t( 'Opcional. Vacío: detecta el primer campo del tipo correspondiente. Usa el ID sin #.', 'Optional. Empty: detects the first matching field type. Enter the ID without #.' ),
                'condition' => array( 'field_type' => $this->get_type() ), 'tab' => 'content', 'inner_tab' => 'form_fields_content_tab', 'tabs_wrapper' => 'form_fields_tabs' );
        }
        $data['fields'] = $this->inject_field_controls( $data['fields'], $controls );
        $widget->update_control( 'form_fields', $data );
    }
}
