<?php
/** CLI-only concurrent writer test. Uses the isolated setup's synthetic form; no emails/actions. */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) || empty( $argv[2] ) ) { exit(1); }
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() || ! get_option( 'xw_appt_qa_backup' ) ) { exit(1); }
$post_id = (int) get_option( 'xw_appt_qa_post' );
$data = json_decode( get_post_meta( $post_id, '_elementor_data', true ), true )[0];
$widget = \Elementor\Plugin::$instance->elements_manager->create_element_instance( $data );
$data['settings'] = $widget->get_settings_for_display(); $data['settings']['id'] = $data['id'];
$record = new \ElementorPro\Modules\Forms\Classes\Form_Record( array( 'name' => 'Concurrency QA', 'email' => 'appointment-race@example.invalid', 'appointment' => $argv[2] ), $data );
$handler = new \ElementorPro\Modules\Forms\Classes\Ajax_Handler();
if ( $record->validate( $handler ) ) { $record->process_fields( $handler ); }
xw_appt_complete( $record, $handler );
echo wp_json_encode( array( 'success' => $handler->is_success, 'errors' => $handler->errors ) );
