<?php
/** Local-only, read-only frontend toolbar fixture with a site icon and long title. */
require rtrim( $_SERVER['DOCUMENT_ROOT'], '/\\' ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { http_response_code( 403 ); exit; }
_wp_admin_bar_init(); // This standalone fixture does not run template_redirect.
add_action( 'admin_bar_menu', static function ( $bar ) {
    $node = $bar->get_node( 'site-name' );
    if ( ! $node ) { return; }
    $icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"><circle cx="14" cy="14" r="14" fill="white"/><text x="14" y="19" text-anchor="middle" fill="#3858e9" font-family="sans-serif" font-size="16">P</text></svg>' );
    $bar->add_node( array( 'id' => 'site-name', 'title' => '<img class="site-icon" src="' . esc_attr( $icon ) . '" alt="">A very long site name for mobile toolbar QA', 'href' => $node->href, 'meta' => array( 'class' => 'has-site-icon' ) ) );
}, 1001 );
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Compact toolbar — local QA</title><?php wp_head(); ?><style>body{margin:0;padding:24px;font-family:system-ui;background:#f0f0f1;color:#1d2327}main{max-width:720px;margin:40px auto;padding:24px;background:#fff;border:1px solid #dcdcde;border-radius:10px}</style></head><body><main><h1>Compact admin bar</h1><p>Local frontend test with a long site name and site icon. Toolbar menus remain native WordPress menus.</p></main><?php wp_footer(); ?></body></html>
