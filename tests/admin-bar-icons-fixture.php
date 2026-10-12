<?php
/** Local browser fixture: native toolbar plus the Rank Math markup observed in production. */
require_once dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { wp_die( 'Local environment required.' ); }
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
wp_set_current_user( (int) $admins[0] );
show_admin_bar( true );
_wp_admin_bar_init();
add_filter( 'option_xw_settings', function ( $settings ) {
    $settings['features']['admin_bar_style'] = 1;
    return $settings;
} );
add_action( 'wp_enqueue_scripts', 'xl_adminbar_font_12_and_align', 30 );
add_action( 'admin_bar_menu', function ( $bar ) {
    $bar->add_node( array(
        'id' => 'rank-math',
        'title' => '<span class="rank-math-icon"><svg viewBox="0 0 462.03 462.03" xmlns="http://www.w3.org/2000/svg" width="20"><g><path d="m462 234.84-76.17 3.43 13.43 21-127 81.18-126-52.93-146.26 60.97 10.14 24.34 136.1-56.71 128.57 54 138.69-88.61 13.43 21z"></path><path d="m54.1 312.78 92.18-38.41 4.49 1.89v-54.58h-96.67zm210.9-223.57v235.05l7.26 3 89.43-57.05v-181zm-105.44 190.79 96.67 40.62v-165.19h-96.67z"></path></g></svg></span><span class="rank-math-text">Rank Math SEO</span>',
        'href' => '#rank-math-qa',
    ) );
    $bar->add_node( array( 'id' => 'rank-math-qa', 'parent' => 'rank-math', 'title' => 'QA submenu', 'href' => '#rank-math-qa' ) );
}, 100 );
add_action( 'wp_head', function () {
    // Preserve the plugin's real conflict, including its late inline style order.
    echo '<style>#wp-admin-bar-rank-math .rank-math-icon{display:inline-block;top:6px;position:relative;padding-right:10px;max-width:20px}#wp-admin-bar-rank-math .rank-math-icon svg{fill-rule:evenodd;fill:#dedede}#wp-admin-bar-rank-math:hover .rank-math-icon svg{fill:#00b9eb}</style>';
}, 99 );
?><!doctype html>
<html <?php language_attributes(); ?>><head><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<main style="padding:40px 24px;font-family:system-ui;max-width:1000px;margin:auto">
<h1>Compact admin bar — icon alignment</h1>
<p>Local regression fixture: native WordPress icons and Rank Math’s actual SVG wrapper and 6 px offset.</p>
<p>The desktop icon is centered in the 32 px toolbar. Native mobile toolbar height and keyboard menus are preserved.</p>
</main><?php wp_footer(); ?></body></html>
