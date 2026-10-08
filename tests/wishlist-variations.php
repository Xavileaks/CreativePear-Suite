<?php
/** Native WooCommerce variation fixture and AJAX regressions. Local QA only. */
if (PHP_SAPI !== 'cli' || empty($argv[1])) { exit("CLI: supply isolated WordPress.\n"); }
define('DOING_AJAX', true);
require rtrim($argv[1], '/\\') . '/wp-load.php';
if ('local' !== wp_get_environment_type()) { exit("Refusing a non-local installation.\n"); }
function wlv_check($value, $label) { if (!$value) { throw new RuntimeException($label); } echo "PASS: $label\n"; }
$fixture = get_option('wl_qa_variations');
if (!$fixture) {
    $parent = new WC_Product_Variable(); $parent->set_name('Glitter Greek Letters T-Shirt QA'); $parent->set_status('publish');
    $attributes = array();
    foreach (array('Style'=>array('Script','Plain'), 'Size'=>array('Small','X-Large'), 'Color'=>array('Black','Burgundy')) as $name=>$options) {
        $attribute = new WC_Product_Attribute(); $attribute->set_name($name); $attribute->set_options($options); $attribute->set_visible(true); $attribute->set_variation(true); $attributes[]=$attribute;
    }
    $parent->set_attributes($attributes); $parent_id = $parent->save();
    $fixture = array('parent'=>$parent_id,'variations'=>array());
    foreach (array(array('Script','X-Large','Burgundy',36),array('Plain','Small','Black',26),array('Script','','Black',29)) as $case) {
        $variation = new WC_Product_Variation(); $variation->set_parent_id($parent_id); $variation->set_status('publish');
        $variation->set_attributes(array('style'=>$case[0],'size'=>$case[1],'color'=>$case[2])); $variation->set_regular_price($case[3]); $fixture['variations'][]=$variation->save();
    }
    WC_Product_Variable::sync($parent_id); update_option('wl_qa_variations',$fixture);
}
$admins = get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID')); wp_set_current_user((int)$admins[0]);
if (!WC()->cart) { wc_load_cart(); }
$owner = xw_wishlist_owner(); $original=xw_wishlist_get_row($owner); $original_items=$original ? json_decode($original['items'],true) : array();
add_filter('wp_die_ajax_handler', static function() { return static function() { throw new RuntimeException('qa-json-end'); }; });
function wlv_ajax($operation, $data=array()) {
    $_POST=array_merge(array('operation'=>$operation,'nonce'=>wp_create_nonce('xw_wishlist_write')),$data); $_REQUEST=$_POST;
    ob_start(); try { xw_wishlist_ajax(); } catch (RuntimeException $e) { if ($e->getMessage() !== 'qa-json-end') { ob_end_clean(); throw $e; } }
    $response=json_decode(ob_get_clean(),true); http_response_code(200); return $response;
}
try {
    WC()->cart->empty_cart(); xw_wishlist_change($owner,static function(){return array();});
    $choice=array('attribute_style'=>'Script','attribute_size'=>'X-Large','attribute_color'=>'Burgundy');
    $fields=array('product_id'=>$fixture['parent'],'variation_id'=>$fixture['variations'][0],'attributes'=>wp_json_encode($choice));
    $saved=wlv_ajax('add',$fields);
    wlv_check($saved['success'] && $saved['data']['count']===1,'selected variation saved');
    $item=$saved['data']['items'][0];
    wlv_check($item['id']===$fixture['variations'][0] && $item['parent_id']===$fixture['parent'] && strpos($item['name'],'Size: X-Large')!==false && strpos($item['name'],'Color: Burgundy')!==false,'variation name and exact choices returned');
    wlv_check(strpos($item['price'],'36.00')!==false && $item['cartable'] && strpos($item['url'],'attribute_size=X-Large')!==false,'variation price and preselected link returned');
    wlv_check(wlv_ajax('add',$fields)['data']['count']===1,'same combination does not duplicate');
    $wrong=$fields; $wrong['product_id']=get_option('wl_qa_product_ids')[0];
    wlv_check(!wlv_ajax('add',$wrong)['success'],'mismatched parent rejected');
    $wrong=$fields; $wrong['attributes']=wp_json_encode(array_merge($choice,array('attribute_color'=>'Black')));
    wlv_check(!wlv_ajax('add',$wrong)['success'],'forged fixed attributes rejected');
    $fields['variation_id']=$fixture['variations'][2]; $choice['attribute_color']='Black'; $choice['attribute_size']='Small'; $fields['attributes']=wp_json_encode($choice);
    $wild1=wlv_ajax('add',$fields)['data']; $choice['attribute_size']='X-Large'; $fields['attributes']=wp_json_encode($choice);
    $wild2=wlv_ajax('add',$fields)['data'];
    wlv_check($wild2['count']===3 && $wild2['items'][1]['key']!==$wild2['items'][2]['key'],'Any-size variation retains two distinct selected combinations');
    $partial=$fields; $partial['attributes']=wp_json_encode(array('attribute_color'=>'Black','attribute_style'=>'Script'));
    wlv_check(!wlv_ajax('add',$partial)['success'],'incomplete Any-size selection rejected');
    $cart=wlv_ajax('cart',array('ids'=>array($item['key'])))['data'];
    $cart_item=array_values(WC()->cart->get_cart())[0];
    wlv_check($cart['added']===1 && $cart['count']===2 && $cart_item['variation_id']===$fixture['variations'][0] && $cart_item['variation']['attribute_color']==='Burgundy','exact variation added to WooCommerce cart and removed from wishlist');
    $cart=wlv_ajax('cart',array('ids'=>array($wild2['items'][1]['key'])))['data'];
    wlv_check($cart['count']===1 && $cart['items'][0]['attributes']['attribute_size']==='X-Large','adding one Any-size choice preserves the other');
    $parent=wc_get_product($fixture['parent']); $parent->set_status('draft'); $parent->save();
    wlv_check(xw_wishlist_product($fixture['variations'][0],0,$item['attributes'])===null,'private/unpublished parent variations never exposed');
    $parent->set_status('publish'); $parent->save();
} finally {
    $parent=wc_get_product($fixture['parent']); $parent->set_status('publish'); $parent->save();
    xw_wishlist_change($owner,static function()use($original_items){return $original_items;}); WC()->cart->empty_cart();
}
echo wp_json_encode($fixture)."\n";
$page=get_option('wl_qa_variation_page');
if (!$page) { $page=wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'Variation Wishlist QA','post_content'=>'[product_page id="'.$fixture['parent'].'"]')); update_option('wl_qa_variation_page',$page); }
update_post_meta($page,'_wp_page_template','elementor_canvas');
echo get_permalink($page)."\n";
