<?php
/** Non-destructive comment controls. Never change post statuses or delete comments. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function xw_comments_settings() {
    $settings = xw_get_settings();
    return ! empty( $settings['features']['comments_control'] ) ? $settings['comments'] : array( 'global' => 0, 'posts' => 0, 'products' => 0, 'pages' => 0 );
}

function xw_comments_disabled( $post_id = 0 ) {
    $post = get_post( $post_id );
    // WooCommerce order notes are internal records, not public discussion.
    if ( $post && in_array( $post->post_type, array( 'shop_order', 'shop_order_refund' ), true ) ) { return false; }
    $settings = xw_comments_settings();
    if ( ! empty( $settings['global'] ) ) { return true; }
    $types = array( 'post' => 'posts', 'page' => 'pages', 'product' => 'products', 'product_variation' => 'products' );
    return $post && isset( $types[ $post->post_type ] ) && ! empty( $settings[ $types[ $post->post_type ] ] );
}

function xw_comments_open_filter( $open, $post_id ) {
    return xw_comments_disabled( $post_id ) ? false : $open;
}
add_filter( 'comments_open', 'xw_comments_open_filter', PHP_INT_MAX, 2 );
add_filter( 'pings_open', 'xw_comments_open_filter', PHP_INT_MAX, 2 );

add_filter( 'comments_array', static function ( $comments, $post_id ) {
    return xw_comments_disabled( $post_id ) ? array() : $comments;
}, PHP_INT_MAX, 2 );
add_filter( 'get_comments_number', static function ( $count, $post_id ) {
    return xw_comments_disabled( $post_id ) ? 0 : $count;
}, PHP_INT_MAX, 2 );
add_filter( 'comments_template', static function ( $template ) {
    return xw_comments_disabled( get_the_ID() ) ? __DIR__ . '/comments-empty.php' : $template;
}, PHP_INT_MAX );

// Block themes can render comments without calling comments_template().
add_filter( 'render_block', static function ( $content, $block, $instance = null ) {
    $names = array( 'core/comments', 'core/comment-template', 'core/post-comments', 'core/post-comments-form', 'core/comments-title', 'core/post-comments-count', 'core/post-comments-link' );
    if ( ! in_array( $block['blockName'] ?? '', $names, true ) ) { return $content; }
    $post_id = $instance->context['postId'] ?? get_the_ID();
    return xw_comments_disabled( $post_id ) ? '' : $content;
}, PHP_INT_MAX, 3 );

// Also reject wp_new_comment() calls from custom forms that skip comments_open().
add_filter( 'pre_comment_approved', static function ( $approved, $data ) {
    $type = $data['comment_type'] ?? '';
    if ( ! in_array( $type, array( '', 'comment', 'review', 'pingback', 'trackback' ), true ) ) { return $approved; }
    if ( xw_comments_disabled( absint( $data['comment_post_ID'] ?? 0 ) ) ) {
        return new WP_Error( 'xw_comments_disabled', xw_t( 'Los comentarios están desactivados para este contenido.', 'Comments are disabled for this content.' ), 403 );
    }
    return $approved;
}, PHP_INT_MAX, 2 );

function xw_comments_product_reviews_disabled() {
    $settings = xw_comments_settings();
    return ! empty( $settings['global'] ) || ! empty( $settings['products'] );
}
// Runtime option filter only: preserve the merchant's original WooCommerce option.
function xw_comments_reviews_option( $enabled ) {
    // Do not make the WooCommerce settings screen save an overridden value.
    return ! is_admin() && xw_comments_product_reviews_disabled() ? 'no' : $enabled;
}
add_filter( 'option_woocommerce_enable_reviews', 'xw_comments_reviews_option', PHP_INT_MAX );
add_filter( 'default_option_woocommerce_enable_reviews', 'xw_comments_reviews_option', PHP_INT_MAX );
add_filter( 'woocommerce_product_tabs', static function ( $tabs ) {
    if ( xw_comments_product_reviews_disabled() ) { unset( $tabs['reviews'] ); }
    return $tabs;
}, PHP_INT_MAX );

function xw_render_comments_settings( $settings ) {
    $fields = array(
        'global' => array( xw_t( 'Desactivar comentarios globalmente', 'Disable comments globally' ), xw_t( 'Tiene prioridad sobre las opciones por tipo de contenido.', 'Overrides the options for individual content types.' ) ),
        'posts' => array( xw_t( 'Desactivar comentarios en entradas', 'Disable comments on posts' ), xw_t( 'Solo afecta a las entradas del blog.', 'Only affects blog posts.' ) ),
        'products' => array( xw_t( 'Desactivar comentarios en productos', 'Disable comments on products' ), xw_t( 'Desactiva las reseñas de WooCommerce.', 'Disables WooCommerce product reviews.' ) ),
        'pages' => array( xw_t( 'Desactivar comentarios en páginas', 'Disable comments on pages' ), xw_t( 'Solo afecta a las páginas.', 'Only affects pages.' ) ),
    );
    ?>
        <div class="xw-comments-options xw-field-full" data-xw-comments-options>
            <?php foreach ( $fields as $key => $field ) : ?>
                <div class="xw-comments-option">
                    <div><strong><?php echo esc_html( $field[0] ); ?></strong><p><?php echo esc_html( $field[1] ); ?></p></div>
                    <label class="xw-switch">
                        <span class="screen-reader-text"><?php echo esc_html( $field[0] ); ?></span>
                        <input type="checkbox" name="xw_settings[comments][<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?> data-xw-comment-scope="<?php echo esc_attr( $key ); ?>">
                        <span class="xw-switch-track" aria-hidden="true"><span></span></span>
                    </label>
                </div>
            <?php endforeach; ?>
            <p class="xw-comments-priority" role="status" data-xw-comments-priority<?php echo empty( $settings['global'] ) ? ' hidden' : ''; ?>><?php echo esc_html( xw_t( 'Global está activo. Se conservan tus selecciones por tipo para cuando lo desactives.', 'Global is active. Your selections by content type are kept for when you turn it off.' ) ); ?></p>
        </div>
    <?php
}
