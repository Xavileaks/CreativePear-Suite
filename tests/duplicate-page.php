<?php
/** Isolated local WordPress integration test; only test-owned content is removed. */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { exit( "Supply the local WordPress directory.\n" ); }
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { exit( "Local environment required.\n" ); }
require_once ABSPATH . 'wp-admin/includes/user.php';
function duplicate_check( $ok, $label ) { if ( ! $ok ) { throw new RuntimeException( $label ); } echo "PASS: $label\n"; }
$original = get_option( 'xw_settings' ); $ids = array(); $users = array(); $terms = array();
$admin = (int) get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) )[0];
wp_set_current_user( $admin );
try {
    $settings = xw_get_settings(); $settings['features']['duplicate_page'] = 0; update_option( 'xw_settings', $settings );
    duplicate_check( ! xw_duplicate_enabled() && xw_get_default_settings()['features']['duplicate_page'] === 0 && xw_duplicate_defaults()['status'] === 'draft', 'module defaults off and copies default to draft' );
    duplicate_check( xw_duplicate_sanitize( array( 'status' => array( 'publish' ), 'editor' => 'bad', 'redirect' => 'https://evil.invalid', 'suffix' => '<b>Copy</b>' ) ) === array( 'editor' => 'all', 'status' => 'draft', 'redirect' => 'list', 'suffix' => 'Copy' ), 'malformed settings and external redirects are rejected' );
    $source = wp_insert_post( wp_slash( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Local duplication QA', 'post_content' => '<!-- wp:paragraph --><p>Quotes " and slash \\ preserved</p><!-- /wp:paragraph -->', 'post_excerpt' => 'Saved excerpt', 'post_author' => $admin, 'comment_status' => 'open', 'menu_order' => 7 ) ), true );
    if ( is_wp_error( $source ) ) { throw new RuntimeException( $source->get_error_message() ); } $ids[] = $source;
    $elementor = wp_json_encode( array( array( 'id' => 'abcdef', 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => array( 'title' => 'Quotes " and slash \\ preserved' ), 'elements' => array() ) ) );
    update_post_meta( $source, '_elementor_data', wp_slash( $elementor ) );
    update_post_meta( $source, '_elementor_edit_mode', 'builder' );
    update_post_meta( $source, '_elementor_page_settings', array( 'hide_title' => 'yes' ) );
    update_post_meta( $source, '_wp_page_template', 'elementor_canvas' );
    update_post_meta( $source, '_thumbnail_id', 1234 );
    add_post_meta( $source, 'qa_multi', 'first' ); add_post_meta( $source, 'qa_multi', 'second' );
    update_post_meta( $source, '_edit_lock', '123:1' ); update_post_meta( $source, '_elementor_css', array( 'time' => 1 ) ); update_post_meta( $source, '_elementor_conditions', array( 'include/general' ) );
    update_post_meta( $source, 'qa_nested', array( 'plain' => array( 'text' => 'Escaped " \\ value' ) ) );
    update_post_meta( $source, 'qa_object', (object) array( 'not' => 'copied' ) );
    $comment = wp_insert_comment( array( 'comment_post_ID' => $source, 'comment_author' => 'Local QA', 'comment_content' => 'Original discussion', 'comment_approved' => 1 ) );
    $before = get_post( $source )->to_array(); $before_meta = get_post_meta( $source );
    duplicate_check( is_wp_error( xw_duplicate_post( $source ) ) && ! isset( xw_duplicate_row_action( array(), get_post( $source ) )['xw_duplicate_page'] ), 'disabled module has no duplication action' );
    $settings['features']['duplicate_page'] = 1; $settings['duplicate_page'] = array( 'editor' => 'all', 'status' => 'draft', 'redirect' => 'edit', 'suffix' => 'Copy' ); update_option( 'xw_settings', $settings );
    duplicate_check( is_wp_error( xw_duplicate_request( $source, 'invalid' ) ) && is_wp_error( xw_duplicate_request( array( $source ), 'invalid' ) ) && is_wp_error( xw_duplicate_post( 99999999 ) ), 'invalid nonce, malformed ID and missing source cannot create content' );
    $copy = xw_duplicate_request( $source, wp_create_nonce( 'xw_duplicate_page_' . $source ) );
    if ( is_wp_error( $copy ) ) { throw new RuntimeException( $copy->get_error_message() ); } $ids[] = $copy;
    duplicate_check( get_post_status( $copy ) === 'draft' && get_the_title( $copy ) === 'Local duplication QA — Copy', 'valid authenticated request creates a draft with suffix' );
    duplicate_check( get_post( $copy )->post_content === $before['post_content'] && get_post( $copy )->post_excerpt === $before['post_excerpt'] && get_post( $copy )->menu_order === 7, 'block content, quotes, slashes, excerpt and order preserved' );
    duplicate_check( get_post_meta( $copy, '_elementor_data', true ) === $elementor && get_post_meta( $copy, '_elementor_page_settings', true ) === array( 'hide_title' => 'yes' ) && get_post_meta( $copy, '_wp_page_template', true ) === 'elementor_canvas', 'Elementor JSON, page settings and template preserved' );
    duplicate_check( get_post_meta( $copy, 'qa_multi' ) === array( 'first', 'second' ) && get_post_meta( $copy, 'qa_nested', true ) === get_post_meta( $source, 'qa_nested', true ) && (int) get_post_meta( $copy, '_thumbnail_id', true ) === 1234, 'repeated/nested metadata and linked featured image preserved' );
    foreach ( array( '_edit_lock', '_elementor_css', '_elementor_conditions', 'qa_object' ) as $key ) { duplicate_check( ! metadata_exists( 'post', $copy, $key ), "$key is not copied" ); }
    duplicate_check( get_post( $source )->to_array() === $before && get_post_meta( $source ) === $before_meta && get_comment( $comment ) !== null && (int) get_comments_number( $copy ) === 0, 'original remains unchanged and comments are not duplicated' );
    $post = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Local taxonomy QA', 'post_author' => $admin ) ); $ids[] = $post;
    $term = wp_insert_term( 'Local duplicate QA ' . $post, 'category' ); if ( is_wp_error( $term ) ) { throw new RuntimeException( $term->get_error_message() ); } $terms[] = $term['term_id'];
    wp_set_object_terms( $post, array( (int) $term['term_id'] ), 'category' );
    $post_copy = xw_duplicate_post( $post ); if ( is_wp_error( $post_copy ) ) { throw new RuntimeException( $post_copy->get_error_message() ); } $ids[] = $post_copy;
    duplicate_check( wp_get_object_terms( $post_copy, 'category', array( 'fields' => 'ids' ) ) === wp_get_object_terms( $post, 'category', array( 'fields' => 'ids' ) ), 'taxonomies preserved by native APIs' );
    foreach ( array( 'editor', 'contributor', 'subscriber' ) as $role ) { $id = wp_insert_user( array( 'user_login' => 'duplicate_qa_' . $role . '_' . $source, 'user_pass' => wp_generate_password(), 'role' => $role ) ); if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); } $users[ $role ] = $id; }
    wp_set_current_user( $users['subscriber'] ); duplicate_check( is_wp_error( xw_duplicate_post( $source ) ), 'subscriber cannot duplicate' );
    wp_set_current_user( $users['contributor'] ); duplicate_check( is_wp_error( xw_duplicate_post( $post ) ) && is_wp_error( xw_duplicate_post( $source ) ), 'contributor cannot copy another author or pages' );
    wp_set_current_user( $admin ); $own = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'draft', 'post_title' => 'Contributor local QA', 'post_author' => $users['contributor'] ) ); $ids[] = $own;
    $settings['duplicate_page']['status'] = 'publish'; update_option( 'xw_settings', $settings );
    wp_set_current_user( $users['contributor'] ); $own_copy = xw_duplicate_post( $own ); if ( is_wp_error( $own_copy ) ) { throw new RuntimeException( $own_copy->get_error_message() ); } $ids[] = $own_copy;
    duplicate_check( get_post_status( $own_copy ) === 'draft' && (int) get_post( $own_copy )->post_author === $users['contributor'], 'contributor copy cannot bypass publish capability' );
    wp_set_current_user( $users['editor'] ); $editor_copy = xw_duplicate_post( $source ); if ( is_wp_error( $editor_copy ) ) { throw new RuntimeException( $editor_copy->get_error_message() ); } $ids[] = $editor_copy;
    duplicate_check( get_post_status( $editor_copy ) === 'publish' && get_post( $editor_copy )->post_name !== get_post( $source )->post_name, 'editor may publish with a distinct slug' );
    wp_set_current_user( $admin );
    foreach ( array( 'pending', 'private' ) as $status ) {
        $settings['duplicate_page']['status'] = $status; update_option( 'xw_settings', $settings );
        $status_copy = xw_duplicate_post( $source ); if ( is_wp_error( $status_copy ) ) { throw new RuntimeException( $status_copy->get_error_message() ); } $ids[] = $status_copy;
        duplicate_check( get_post_status( $status_copy ) === $status, "$status status is applied to the finished copy" );
    }
    duplicate_check( ! xw_duplicate_allowed( new WP_Post( (object) array( 'ID' => $source, 'post_type' => 'shop_order', 'post_status' => 'publish', 'filter' => 'raw' ) ) ) && ! xw_duplicate_allowed( new WP_Post( (object) array( 'ID' => $source, 'post_type' => 'product', 'post_status' => 'publish', 'filter' => 'raw' ) ) ), 'internal orders excluded; products keep native WooCommerce duplication' );
    ob_start(); xw_render_duplicate_settings( xw_duplicate_defaults() ); $html = ob_get_clean(); duplicate_check( substr_count( $html, '<select' ) === 3 && strpos( $html, 'maxlength="80"' ) !== false, 'card has three labelled selects and suffix field' );
    eval( 'class duplicate_page {}' ); duplicate_check( ! xw_duplicate_enabled() && is_wp_error( xw_duplicate_post( $source ) ), 'original Duplicate Page plugin avoids double actions' );
} finally {
    wp_set_current_user( $admin ); update_option( 'xw_settings', $original );
    foreach ( $ids as $id ) { wp_delete_post( $id, true ); }
    foreach ( $terms as $id ) { wp_delete_term( $id, 'category' ); }
    foreach ( $users as $id ) { wp_delete_user( $id ); }
}
