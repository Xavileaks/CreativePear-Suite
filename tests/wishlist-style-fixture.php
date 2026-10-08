<?php
/** Prepare real Elementor CSS in the isolated local QA installation. Never run against production. */
if (PHP_SAPI !== 'cli' || empty($argv[1])) { exit("CLI: supply an isolated WordPress directory.\n"); }
require rtrim($argv[1], '/\\') . '/wp-load.php';
if ('local' !== wp_get_environment_type()) { exit("Refusing to change a non-local installation.\n"); }
$admins = get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
if (!$admins) { throw new RuntimeException('A local administrator is required for cache QA.'); }
wp_set_current_user((int) $admins[0]);
$refreshes = 0;
add_action('elementor/core/files/clear_cache', static function() use (&$refreshes) { ++$refreshes; });
delete_option('xw_wishlist_style_revision');
xw_wishlist_refresh_elementor_styles();
xw_wishlist_refresh_elementor_styles();
if ($refreshes !== 1 || get_option('xw_wishlist_style_revision') !== '2') { throw new RuntimeException('Cache revision must refresh once only'); }
echo "PASS: generated Elementor markup/CSS refreshed once only\n";
$page = (int) get_option('wl_qa_page_id');
if (!$page) { throw new RuntimeException('Run wishlist-integration.php first.'); }
$mode = $argv[2] ?? 'compact';
if (!in_array($mode, array('compact', 'spacious', 'no-image', 'minimal'), true)) { throw new RuntimeException('Unknown fixture mode.'); }
$wide = $mode === 'spacious';
$dimensions = static function($size) { return array('top'=>$size,'right'=>$size,'bottom'=>$size,'left'=>$size,'unit'=>'px','isLinked'=>true); };
$data = json_decode(get_post_meta($page, '_elementor_data', true), true);
foreach ($data[0]['elements'] as &$element) {
    if (($element['widgetType'] ?? '') === 'xw-wishlist-counter') {
        $element['settings']['icon_size'] = array('size'=>16,'unit'=>'px');
        $element['settings']['icon_padding'] = $dimensions($wide ? 12 : 2);
    }
    if (($element['widgetType'] ?? '') === 'xw-wishlist-table') {
        $s = &$element['settings'];
        $s['table_radius'] = $dimensions($wide ? 0 : 18);
        $s['header_background'] = '#711704'; $s['header_color'] = '#ffffff';
        $s['row_alt'] = '#ffffff'; $s['row_hover'] = '#fff4df';
        $s['row_button_padding'] = $dimensions($wide ? 12 : 0);
        $s['row_button_typography_typography'] = 'custom';
        $s['row_button_typography_font_size'] = array('size'=>12,'unit'=>'px');
        $s['remove_padding'] = $dimensions($wide ? 12 : 0);
        $s['remove_size'] = array('size'=>11,'unit'=>'px');
        $s['image_size'] = array('size'=>74,'unit'=>'px');
        $s['image_shape'] = 'circle';
        $s['show_image'] = in_array($mode, array('no-image', 'minimal'), true) ? '' : 'yes';
        $s['show_select'] = $mode === 'minimal' ? '' : 'yes';
        $s['show_date'] = $mode === 'minimal' ? '' : 'yes';
        $s['show_stock'] = $mode === 'minimal' ? '' : 'yes';
        // Reproduce user-defined desktop widths that previously left narrow mobile cells.
        foreach (array('name'=>400,'price'=>180,'date'=>160,'stock'=>140,'actions'=>170) as $column=>$width) {
            $s[$column.'_width'] = array('size'=>$width,'unit'=>'px');
        }
        unset($s);
    }
}
unset($element);
update_post_meta($page, '_elementor_data', wp_slash(wp_json_encode($data)));
delete_post_meta($page, '_elementor_element_cache');
Elementor\Core\Files\CSS\Post::create($page)->update();
$widgets = Elementor\Plugin::$instance->widgets_manager->get_widget_types();
foreach (array('table_radius','card_padding','card_gap','remove_min_size','row_button_min_height') as $id) {
    if (!$widgets['xw-wishlist-table']->get_controls($id)) { throw new RuntimeException('Missing control: '.$id); }
    echo "PASS: $id registered in real Elementor\n";
}
$html = Elementor\Plugin::$instance->frontend->get_builder_content_for_display($page);
if (strpos($html, 'xw-wl-table-frame') === false || strpos($html, 'role="table"') === false) { throw new RuntimeException('Table frame/semantics missing'); }
if (in_array($mode, array('no-image', 'minimal'), true) && strpos($html, 'data-xw-wl-image=""') === false) { throw new RuntimeException('Hidden image setting not rendered'); }
echo "PASS: table frame rendered; fixture $mode ready at ".get_permalink($page)."\n";
