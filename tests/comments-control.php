<?php
/** CLI only: scoped/non-destructive discussion policy in an isolated WordPress. */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { exit( "Supply the local WordPress directory.\n" ); }
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { exit( "Local environment required.\n" ); }
function comments_check( $ok, $label ) { if ( ! $ok ) { throw new RuntimeException( $label ); } echo "PASS: $label\n"; }
$original = get_option( 'xw_settings' );
$ids = array();
$comments = array();
register_post_type( 'xw_comments_qa', array( 'public' => true, 'supports' => array( 'title', 'comments' ) ) );
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
wp_set_current_user( (int) $admins[0] );
try {
    $settings = xw_get_settings();
    $off = array( 'global' => 0, 'posts' => 0, 'products' => 0, 'pages' => 0 );
    $settings['comments'] = $off; update_option( 'xw_settings', $settings );
    comments_check( xw_get_default_settings()['comments'] === $off && ! isset( xw_get_default_settings()['features']['comments_control'] ), 'four flags default off, with no fifth master switch' );
    $dirty = $settings; $dirty['comments'] = array( 'global' => '1', 'posts' => 1, 'products' => array( 'evil' ), 'pages' => '0' );
    comments_check( xw_sanitize_settings( $dirty )['comments'] === array( 'global' => 1, 'posts' => 1, 'products' => 0, 'pages' => 0 ), 'known flags normalize safely, including nested/malformed values' );
    foreach ( array( 'post', 'page', 'product', 'xw_comments_qa' ) as $type ) {
        $ids[ $type ] = wp_insert_post( array( 'post_type' => $type, 'post_title' => 'Local comments QA', 'post_status' => 'publish', 'comment_status' => 'open', 'ping_status' => 'open' ), true );
        if ( is_wp_error( $ids[ $type ] ) ) { throw new RuntimeException( $ids[ $type ]->get_error_message() ); }
        $comments[ $type ] = wp_insert_comment( array( 'comment_post_ID' => $ids[ $type ], 'comment_type' => 'product' === $type ? 'review' : 'comment', 'comment_author' => 'Local QA', 'comment_author_email' => 'qa@example.invalid', 'comment_content' => 'Existing QA discussion', 'comment_approved' => 1 ) );
        wp_update_comment_count_now( $ids[ $type ] );
    }
    foreach ( array( 'posts' => 'post', 'pages' => 'page', 'products' => 'product' ) as $scope => $type ) {
        $settings['comments'] = $off; $settings['comments'][ $scope ] = 1; update_option( 'xw_settings', $settings );
        foreach ( $ids as $other_type => $id ) {
            comments_check( comments_open( $id ) === ( $other_type !== $type ) && pings_open( $id ) === ( $other_type !== $type ), "$scope affects only its own type ($other_type)" );
        }
        comments_check( get_post( $ids[ $type ] )->comment_status === 'open' && get_comment( $comments[ $type ] ) !== null, "$scope preserves stored statuses and existing comments" );
        $existing = array( get_comment( $comments[ $type ] ) );
        comments_check( apply_filters( 'comments_array', $existing, $ids[ $type ] ) === array() && get_comments_number( $ids[ $type ] ) === 0, "$scope hides public list/count without deleting records" );
        $block = new WP_Block( array( 'blockName' => 'core/post-comments-form', 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ), array( 'postId' => $ids[ $type ] ) );
        comments_check( apply_filters( 'render_block', 'comment form', array( 'blockName' => 'core/post-comments-form' ), $block ) === '', "$scope suppresses block-theme comment forms" );
        comments_check( apply_filters( 'render_block', 'paragraph', array( 'blockName' => 'core/paragraph' ), $block ) === 'paragraph', "$scope leaves unrelated blocks intact" );
        $request = new WP_REST_Request( 'POST', '/wp/v2/comments' ); $request->set_param( 'post', $ids[ $type ] );
        $error = ( new WP_REST_Comments_Controller() )->create_item_permissions_check( $request );
        comments_check( is_wp_error( $error ) && $error->get_error_code() === 'rest_comment_closed', "$scope blocks native REST comment submission" );
    }
    $tabs = array( 'description' => array(), 'reviews' => array() );
    $prior_post = $GLOBALS['post'] ?? null;
    $GLOBALS['post'] = get_post( $ids['product'] );
    comments_check( ! isset( apply_filters( 'woocommerce_product_tabs', $tabs )['reviews'] ) && ! wc_reviews_enabled(), 'products closes WooCommerce reviews and hides its review tab' );
    $GLOBALS['post'] = $prior_post;
    $settings['comments'] = $off; $settings['comments']['global'] = 1; $settings['comments']['posts'] = 1; update_option( 'xw_settings', $settings );
    foreach ( $ids as $id ) { comments_check( ! comments_open( $id ) && ! pings_open( $id ), 'global overrides all public content types, including custom types' ); }
    $note = new WP_Post( (object) array( 'ID' => 0, 'post_type' => 'shop_order', 'filter' => 'raw' ) );
    comments_check( ! xw_comments_disabled( $note ) && apply_filters( 'pre_comment_approved', 1, array( 'comment_type' => 'order_note', 'comment_post_ID' => 0 ) ) === 1, 'global does not affect internal WooCommerce order notes' );
    $error = wp_new_comment( array( 'comment_post_ID' => $ids['post'], 'comment_author' => 'Blocked QA', 'comment_author_email' => 'blocked@example.invalid', 'comment_content' => 'Must not be inserted', 'user_id' => (int) $admins[0], 'comment_author_IP' => '127.0.0.1' ), true );
    comments_check( is_wp_error( $error ) && $error->get_error_code() === 'xw_comments_disabled', 'custom wp_new_comment forms cannot bypass the policy' );
    $settings['comments']['global'] = 0; update_option( 'xw_settings', $settings );
    comments_check( ! comments_open( $ids['post'] ) && comments_open( $ids['page'] ), 'turning global off restores saved per-type choices' );
    $settings['comments'] = $off; update_option( 'xw_settings', $settings );
    foreach ( $ids as $id ) { comments_check( comments_open( $id ) && (int) get_comments_number( $id ) === 1, 'turning options off restores original comments and counts' ); }
    wp_update_post( array( 'ID' => $ids['post'], 'comment_status' => 'closed' ) );
    comments_check( ! comments_open( $ids['post'] ), 'does not reopen content already closed in WordPress' );
    ob_start(); xw_render_comments_card( $off ); $html = ob_get_clean();
    comments_check( substr_count( $html, 'type="checkbox"' ) === 4 && strpos( $html, 'xw_settings[features][comments_control]' ) === false, 'one card exposes exactly four accessible controls' );
} finally {
    update_option( 'xw_settings', $original );
    foreach ( $ids as $id ) { if ( ! is_wp_error( $id ) ) { wp_delete_post( $id, true ); } }
    unregister_post_type( 'xw_comments_qa' );
}
