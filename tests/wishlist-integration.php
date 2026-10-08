<?php
/** php tests/wishlist-integration.php /absolute/path/to/isolated/wordpress */
if (PHP_SAPI !== 'cli' || empty($argv[1])) { exit("CLI: supply a local WordPress directory.\n"); }
require rtrim($argv[1], '/\\') . '/wp-load.php';
if ('local' !== wp_get_environment_type()) { exit("Refusing to modify a non-local WordPress installation.\n"); }
function wl_assert($condition, $description) {
    if (!$condition) { throw new RuntimeException($description); }
    echo "PASS: $description\n";
}
$settings = xw_get_settings();
wl_assert(!xw_get_default_settings()['features']['elementor_wishlist'], 'new installations default to off');
$settings['features']['elementor_wishlist'] = 1;
update_option('xw_settings', $settings);
xw_wishlist_install();
wl_assert(get_option('xw_wishlist_schema') === '1', 'wishlist schema installed');
$widgets = Elementor\Plugin::$instance->widgets_manager->get_widget_types();
$names = array_filter(array_keys($widgets), static function($name) { return strpos($name, 'xw-wishlist-') === 0; });
wl_assert(count($names) === 3, 'exactly three native Elementor widgets');
foreach ($names as $name) {
    $controls = $widgets[$name]->get_controls();
    wl_assert(count($controls) > 30, "$name controls initialized with real Elementor");
}
$normal = xw_wishlist_normalize(array(array('id'=>1,'added'=>1), array('id'=>1,'added'=>2), array('id'=>0), null));
wl_assert(count($normal) === 1 && $normal[0]['added'] === 1, 'normalization deduplicates and preserves date');
$many = array();
for ($i=1; $i<=230; $i++) $many[] = array('id'=>$i, 'added'=>1);
wl_assert(count(xw_wishlist_normalize($many)) === 200, '200-product limit');
$owner = 'g:qa-storage-integration';
$result = xw_wishlist_change($owner, static function($items) { return array(array('id'=>1,'added'=>1)); });
wl_assert(!is_wp_error($result) && count($result) === 1, 'transactional storage');
$failure = xw_wishlist_change($owner, static function($items) { return new WP_Error('qa', 'rollback'); });
wl_assert(is_wp_error($failure) && json_decode(xw_wishlist_get_row($owner)['items'], true)[0]['id'] === 1, 'rollback preserves list');
global $wpdb;
$wpdb->delete(xw_wishlist_table_name(), array('owner_key'=>$owner));
$ids = get_option('wl_qa_product_ids');
if (!$ids) {
    $ids = array();
    foreach (array('Infant Jersey Tee'=>20, 'Membership Certificate'=>35, 'Personalized Toddler Tee'=>23, 'Unavailable Test Product'=>12) as $name=>$price) {
        $product = $name === 'Personalized Toddler Tee' ? new WC_Product_Variable() : new WC_Product_Simple();
        $product->set_name($name); $product->set_status('publish'); $product->set_regular_price($price);
        $product->set_stock_status($name === 'Unavailable Test Product' ? 'outofstock' : 'instock');
        $ids[] = $product->save();
    }
    $hidden = new WC_Product_Simple(); $hidden->set_name('Private QA item'); $hidden->set_status('private'); $hidden->set_regular_price(10); $ids[] = $hidden->save();
    update_option('wl_qa_product_ids', $ids);
}
wl_assert(xw_wishlist_product($ids[0])['simple'], 'simple product is cartable');
wl_assert(!xw_wishlist_product($ids[2])['simple'], 'variable product requires options');
wl_assert(!xw_wishlist_product($ids[3])['purchasable'], 'out-of-stock product cannot be bought');
wl_assert(xw_wishlist_product($ids[4]) === null, 'private products are never exposed');
$merge_user = username_exists('wl-qa-merge');
if (!$merge_user) $merge_user = wp_create_user('wl-qa-merge', wp_generate_password(32), 'wl-qa-merge@example.invalid');
wl_assert(!is_wp_error($merge_user), 'local merge fixture account');
$old_cookie = $_COOKIE['xw_wishlist_v1'] ?? null;
$_COOKIE['xw_wishlist_v1'] = str_repeat('a', 64);
$guest_owner = 'g:' . hash('sha256', $_COOKIE['xw_wishlist_v1']);
xw_wishlist_change($guest_owner, static function($items) use($ids) { return array(array('id'=>$ids[0],'added'=>100), array('id'=>$ids[1],'added'=>200)); });
wp_set_current_user($merge_user);
xw_wishlist_change(xw_wishlist_owner(), static function($items) use($ids) { return array(array('id'=>$ids[1],'added'=>50)); });
xw_wishlist_merge_guest();
$merged = json_decode(xw_wishlist_get_row(xw_wishlist_owner())['items'], true);
wl_assert(count($merged) === 2 && $merged[0]['added'] === 50 && !xw_wishlist_get_row($guest_owner), 'guest list merges into account without duplicates');
$wpdb->delete(xw_wishlist_table_name(), array('owner_key'=>xw_wishlist_owner()));
wp_set_current_user(0);
if ($old_cookie === null) unset($_COOKIE['xw_wishlist_v1']); else $_COOKIE['xw_wishlist_v1'] = $old_cookie;
$page_id = get_option('wl_qa_page_id');
if (!$page_id) {
    $page_id = wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'Wishlist QA'));
    update_option('wl_qa_page_id', $page_id);
}
$settings = xw_get_settings(); $settings['wishlist']['page_id'] = $page_id; update_option('xw_settings', $settings);
$elements = array(
    array('id'=>'qaouter','elType'=>'container','settings'=>array('content_width'=>'boxed','boxed_width'=>array('size'=>1200,'unit'=>'px'),'flex_direction'=>'column','padding'=>array('top'=>32,'right'=>24,'bottom'=>32,'left'=>24,'unit'=>'px','isLinked'=>false)), 'elements'=>array(
        array('id'=>'qacount','elType'=>'widget','widgetType'=>'xw-wishlist-counter','settings'=>array('align'=>'right','badge_x'=>array('size'=>8,'unit'=>'px'),'badge_y'=>array('size'=>-8,'unit'=>'px')), 'elements'=>array()),
        array('id'=>'qaadds','elType'=>'container','settings'=>array('flex_direction'=>'row','flex_wrap'=>'wrap'), 'elements'=>array(
            array('id'=>'qaadd1','elType'=>'widget','widgetType'=>'xw-wishlist-add','settings'=>array('product_id'=>$ids[0],'display'=>'icon','button_shape'=>'circle'),'elements'=>array()),
            array('id'=>'qaadd2','elType'=>'widget','widgetType'=>'xw-wishlist-add','settings'=>array('product_id'=>$ids[1],'display'=>'text','label'=>'Save certificate'),'elements'=>array()),
            array('id'=>'qaadd3','elType'=>'widget','widgetType'=>'xw-wishlist-add','settings'=>array('product_id'=>$ids[2],'display'=>'both','label'=>'Save toddler tee'),'elements'=>array()),
        )),
        array('id'=>'qatable','elType'=>'widget','widgetType'=>'xw-wishlist-table','settings'=>array('title'=>'My wishlist','image_shape'=>'rounded','price_align'=>'center'),'elements'=>array()),
    ))
);
update_post_meta($page_id, '_elementor_edit_mode', 'builder');
update_post_meta($page_id, '_elementor_template_type', 'wp-page');
update_post_meta($page_id, '_elementor_version', ELEMENTOR_VERSION);
update_post_meta($page_id, '_wp_page_template', 'elementor_canvas');
update_post_meta($page_id, '_elementor_data', wp_slash(wp_json_encode($elements)));
Elementor\Core\Files\CSS\Post::create($page_id)->update();
$html = Elementor\Plugin::$instance->frontend->get_builder_content_for_display($page_id);
wl_assert(substr_count($html, 'data-xw-wl="add"') === 3 && strpos($html, 'data-xw-wl="table"') !== false, 'native document renders all widget modes');
$original_post = $GLOBALS['post'] ?? null;
foreach (array($ids[0], $ids[2]) as $id) {
    $GLOBALS['post'] = get_post($id); setup_postdata($GLOBALS['post']);
    $instance = Elementor\Plugin::$instance->elements_manager->create_element_instance(array('id'=>'qaloo'. $id,'elType'=>'widget','widgetType'=>'xw-wishlist-add','settings'=>array('product_id'=>0)));
    ob_start(); $instance->print_element(); $loop_html = ob_get_clean();
    wl_assert(strpos($loop_html, 'data-xw-wl-add="'.$id.'"') !== false, 'automatic product context '. $id);
}
$GLOBALS['post'] = $original_post; wp_reset_postdata();
echo wp_json_encode(array('page_id'=>$page_id, 'url'=>get_permalink($page_id),'ids'=>$ids,'wordpress'=>get_bloginfo('version'),'woocommerce'=>WC_VERSION,'elementor'=>ELEMENTOR_VERSION), JSON_PRETTY_PRINT) . "\n";
