<?php
/*
Plugin Name: Creative Pear Suite
Plugin URI: https://github.com/Xavileaks/CreativePear-Suite
Description: Modular WordPress features and global assets for Creative Pear Agency.
Version: 1.0.11
Author: Creative Pear Agency
Author URI: https://creativepearagency.com
Update URI: https://github.com/Xavileaks/CreativePear-Suite
Text Domain: creativepear-suite
*/

// SECURITY CHECK

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'XW_FUNCTIONS_VERSION', '1.0.11' );
define( 'XW_FUNCTIONS_FILE', __FILE__ );

require_once plugin_dir_path( __FILE__ ) . 'includes/admin-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/github-updater.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/back-to-top.php';

register_activation_hook( XW_FUNCTIONS_FILE, 'xw_activate_plugin' );

add_filter( 'all_plugins', 'xw_localize_plugin_metadata' );
function xw_localize_plugin_metadata( $plugins ) {
    $plugin_file = plugin_basename( XW_FUNCTIONS_FILE );

    if ( isset( $plugins[ $plugin_file ] ) ) {
        $plugins[ $plugin_file ]['Description'] = xw_t(
            'Funciones modulares y recursos globales para WordPress.',
            'Modular WordPress features and global assets.'
        );
    }

    return $plugins;
}


// AÑADIR ENLACE "AJUSTES" EN LA LISTA DE PLUGINS

add_filter(
    'plugin_action_links_' . plugin_basename(__FILE__),
    'xleon_suite_add_settings_link'
);

function xleon_suite_add_settings_link($links) {
    $url = admin_url('options-general.php?page=creativepear-suite');

    $settings_link = '<a href="' . esc_url($url) . '">' . esc_html( xw_t( 'Ajustes', 'Settings' ) ) . '</a>';

    // Lo ponemos al principio, antes de "Deactivate"
    array_unshift($links, $settings_link);

    return $links;
}


// EXPORTAR PLUGIN

if ( xw_feature_enabled( 'plugin_export' ) ) {
    add_action('admin_post_xw_download_plugin','xw_download_plugin_zip');
    add_action('admin_footer','xw_place_export_button');
}

function xw_download_plugin_zip(){
    if(!current_user_can('manage_options')){wp_die(esc_html(xw_t('No tiene permiso para realizar esta acción.','You are not allowed to perform this action.')));}
    check_admin_referer('xw_download_plugin');
    $dir=plugin_dir_path(__FILE__);
    $zip_path=$dir.'xw-temp.zip';
    $zip=new ZipArchive();
    if($zip->open($zip_path,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true){wp_die(esc_html(xw_t('No se pudo crear el archivo ZIP.','The ZIP file could not be created.')));}
    $files=new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach($files as $f){
        if(!$f->isDir()){
            $path=$f->getRealPath();
            $rel=str_replace($dir,'',$path);
            if($path===$zip_path){continue;}
            $rel_normalized=str_replace('\\','/',$rel);
            if(
                0 === strpos($rel_normalized,'.git/') ||
                0 === strpos($rel_normalized,'.github/') ||
                0 === strpos($rel_normalized,'build/')
            ){
                continue;
            }
            $zip->addFile($path,$rel);
        }
    }
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="creativepear-suite.zip"');
    header('Content-Length: '.filesize($zip_path));
    readfile($zip_path);
    unlink($zip_path);
    exit;
}

function xw_place_export_button(){
    $screen=get_current_screen();
    if(!$screen||$screen->id!=='plugin-editor'){return;}
    $url=add_query_arg(
        array(
            'action'   => 'xw_download_plugin',
            '_wpnonce' => wp_create_nonce('xw_download_plugin'),
        ),
        admin_url('admin-post.php')
    );
    $url=esc_js($url);
    echo "<script>
    document.addEventListener('DOMContentLoaded',function(){
        var row=document.querySelector('.fileedit-sub .alignleft');
        if(!row)return;
        if(row.textContent.indexOf('creativepear-suite/')===-1)return;
        var br=document.createElement('br');
        br.style.display='block';
        br.style.marginTop='6px';
        row.appendChild(br);
        var btn=document.createElement('a');
        btn.href='".$url."';
        btn.className='button button-primary';
        btn.textContent='" . esc_js( xw_t( 'Exportar plugin', 'Export plugin' ) ) . "';
        row.appendChild(btn);
    });
    </script>";
}


// LLAMADO A CSS

function xleon_suite_enqueue_styles() {
    $css_dir = plugin_dir_path(__FILE__) . 'assets/css/';
    $last_handle = '';

    foreach (glob($css_dir . '*.css') as $file) {
        if ( 'admin-settings.css' === basename( $file ) ) {
            continue;
        }

        $handle = 'xleon-' . sanitize_key( str_replace( ' ', '-', basename( $file, '.css' ) ) );

        wp_enqueue_style(
            $handle,
            plugin_dir_url(__FILE__) . 'assets/css/' . basename($file),
            array(),
            filemtime($file)
        );

        $last_handle = $handle;
    }

    if ( $last_handle ) {
        $settings    = xw_get_settings();
        $styles      = $settings['styles'];
        $primary     = sanitize_hex_color( $styles['primary_color'] );
        $track       = sanitize_hex_color( $styles['scrollbar_track_color'] );
        $thumb       = sanitize_hex_color( $styles['scrollbar_thumb_color'] );
        $width       = absint( $styles['scrollbar_width'] );
        $radius      = absint( $styles['scrollbar_radius'] );
        $dynamic_css = '';

        if ( $primary ) {
            $dynamic_css .= ':root{--color-web:' . $primary . ';}';
        }

        $dynamic_css .= '::-webkit-scrollbar{width:' . $width . 'px;height:' . $width . 'px;}';
        $dynamic_css .= '::-webkit-scrollbar-track{border-radius:' . $radius . 'px;';
        $dynamic_css .= $track ? 'background:' . $track . ';}' : '}';
        $dynamic_css .= '::-webkit-scrollbar-thumb{border-radius:' . $radius . 'px;';
        $dynamic_css .= $thumb ? 'background:' . $thumb . ';}' : '}';

        wp_add_inline_style( $last_handle, $dynamic_css );
    }
}
if ( xw_feature_enabled( 'assets_styles' ) ) {
    add_action( 'wp_enqueue_scripts', 'xleon_suite_enqueue_styles', 20 );
}


// ACTIVAR EDITOR CLÁSICO DE WORDPRESS

if ( xw_feature_enabled( 'classic_editor' ) ) {
    add_filter('use_block_editor_for_post', '__return_false', 10);
    add_filter('use_block_editor_for_post_type', '__return_false', 10);
    add_filter('wp_use_widgets_block_editor', '__return_false');
}


// DESACTIVAR WP VERSION

if ( xw_feature_enabled( 'hide_wp_version' ) ) {
    add_action( 'init', function() {
        remove_action( 'wp_head', 'wp_generator' );
    } );
}


// ESTILO LOGIN WORDPRESS

function mi_login_logo_one() {
$xw_settings = xw_get_settings();
$primary_color = sanitize_hex_color( $xw_settings['login']['primary_color'] );
$button_text_color = sanitize_hex_color( $xw_settings['login']['button_text_color'] );
$logo_url = $xw_settings['login']['logo_url'];
$logo_width = $xw_settings['login']['logo_width'];
?>
<style type="text/css">
:root {
  --color-principal: <?php echo esc_html( $primary_color ?: '#2271B1' ); ?>;
  --color-texto-boton: <?php echo esc_html( $button_text_color ?: '#FFFFFF' ); ?>;
}
#login {
    width: 340px !important;
    display: grid;
    place-content: center !important;
    padding: 40px 0px !important;
}
body.login div#login h1 a {
	background-image: <?php echo $logo_url ? 'url("' . esc_url( $logo_url ) . '")' : 'none'; ?>;
	z-index: 999;
	padding-bottom: 40px;
    margin-bottom: 10px;
    background-size: contain;
    background-position: center bottom;
    width: <?php echo absint( $logo_width ); ?>px;
    height: auto;
    pointer-events: none;
    font-size: inherit;
}
body.login {
    background: #FFFFFF;
    min-height: 100vh;
    place-content: center !important;
}
#loginform {
	padding: 36px 34px !important;
	border-radius: 5px !important;
	box-shadow: 1px 1px 13px -2px rgba(0, 0, 0, 0.3) !important;
}
.wp-core-ui .button-primary {
	border-radius: 100px !important;
	background: var(--color-principal) !important;
	border-color: var(--color-principal) !important;
	color: var(--color-texto-boton) !important;
}
.wp-core-ui .button-primary.focus, .wp-core-ui .button-primary:focus {
    box-shadow: 0 0 0 1px #fff, 0 0 0 3px #fff !important;
}
a, a:hover {
  color: #222222 !important;
}
.login .button.wp-hide-pw .dashicons {
  color: #222222 !important;
}
.login .button.wp-hide-pw:focus {
    border-color: #3582c400 !important;
    box-shadow: 0 0 0 1px #3582c400 !important;
}
input[type="checkbox"]:focus,
input[type="password"]:focus,
input[type="text"]:focus {
  border-color: var(--color-principal) !important;
  box-shadow: 0 0 0 1px var(--color-principal) !important;
}
input[type="text"],
input[type="password"] {
  border-radius: 100px !important;
  padding: 0 15px !important;
  font-size: 18px !important;
}
input[type=checkbox]:checked::before {
	content: url(<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'assets/images/checkbox.svg' ); ?>) !important;
}
.language-switcher, .privacy-policy-link {
    display: none;
}
</style>
<?php
}
if ( xw_feature_enabled( 'login_customization' ) ) {
    add_action( 'login_enqueue_scripts', 'mi_login_logo_one' );
}


// ELIMINAR BARRA ADMINISTRACION PARA NO ADMIN

if ( xw_feature_enabled( 'hide_admin_bar' ) ) {
    add_action('after_setup_theme', 'xw_remove_admin_bar');
}
function xw_remove_admin_bar() {
if (!current_user_can('administrator') && !is_admin()) {
show_admin_bar(false);
}
}


// BARRA SUPERIOR

if ( xw_feature_enabled( 'admin_bar_style' ) ) {
    add_action('admin_head','xl_adminbar_font_12_and_align');
    add_action('wp_head','xl_adminbar_font_12_and_align');
}
function xl_adminbar_font_12_and_align() {
    if (!is_user_logged_in()) return;
    echo '<style>
    #wpadminbar,
    #wpadminbar *{
        font-size:11px!important;
        top:0!important;
    }
    .ab-sub-wrapper{
        margin-top:32px!important;
    }
    #wpadminbar .ab-item,
    #wpadminbar .ab-label,
    #wpadminbar .ab-sub-wrapper a{
        display:flex!important;
        align-items:center!important;
    }
    .ab-item:before{
        font-size:16px!important;
        inset-block-start:0!important;
    }
    #wpadminbar .ab-icon:before{
        font-size:16px!important;
        top:0!important;
    }
    </style>';
}


// MARCA DEL ADMINISTRADOR

if ( xw_feature_enabled( 'admin_branding' ) ) {
    add_action( 'admin_bar_menu', 'xw_remove_wordpress_admin_logo', 999 );
    add_filter( 'admin_footer_text', 'xw_custom_admin_footer_text', 20 );
}

function xw_remove_wordpress_admin_logo( $wp_admin_bar ) {
    $wp_admin_bar->remove_node( 'wp-logo' );
}

function xw_custom_admin_footer_text( $default_text ) {
    $settings = xw_get_settings();
    $html     = $settings['admin_branding']['footer_html'];

    if ( '' === $html ) {
        return '';
    }

    return wp_kses(
        $html,
        array(
            'a' => array(
                'href'   => true,
                'target' => true,
                'rel'    => true,
            ),
        )
    );
}


// MENSAJE FLOTANTE DE ELEMENTOR FORM

add_action('wp_footer', function() {
    if ( ! xw_feature_enabled( 'elementor_messages' ) ) return;
    ?>
    <style>
    .elementor-message {
        position: fixed !important;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        background: #f5f5f5;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: 2px solid transparent;
        opacity: 0;
        transition: opacity 0.5s ease;
        width: 700px;
        max-width: 90%;
        text-align: center;
        overflow: hidden;
        box-sizing: border-box;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .elementor-message > *{
        margin: 0 !important;
        display: inline;
    }
    @media (max-width: 767px) {
        .elementor-message {
            width: 80%;
        }
    }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.classList && node.classList.contains('elementor-message')) {
                        if (node.classList.contains('elementor-message-success')) {
                            node.style.borderColor = 'green';
                        } else if (node.classList.contains('elementor-message-danger')) {
                            node.style.borderColor = 'red';
                        } else if (node.classList.contains('elementor-message-info')) {
                            node.style.borderColor = 'yellow';
                        }
                        let firstChild = node.firstChild;
                        while (firstChild && firstChild.nodeType !== 1 && firstChild.nodeType !== 3) {
                            firstChild = firstChild.nextSibling;
                        }
                        if (firstChild) {
                            node.innerHTML = '';
                            node.appendChild(firstChild);
                        }
                        requestAnimationFrame(() => {
                            node.style.opacity = '1';
                        });
                        let hideTimeout;
                        const startHideTimer = () => {
                            hideTimeout = setTimeout(() => {
                                node.style.opacity = '0';
                                setTimeout(() => {
                                    node.style.display = 'none';
                                }, 500);
                            }, 5000);
                        };
                        const clearHideTimer = () => {
                            clearTimeout(hideTimeout);
                        };
                        startHideTimer();
                        node.addEventListener('mouseenter', clearHideTimer);
                        node.addEventListener('mouseleave', startHideTimer);
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    });
    </script>
    <?php
});


// MASCARA DE TELEFONO PARA ELEMENTOR FORM

if ( xw_feature_enabled( 'elementor_phone_mask' ) ) {
    add_action( 'wp_enqueue_scripts', 'xw_enqueue_international_phone_assets', 30 );
}

function xw_enqueue_international_phone_assets() {
    $settings = xw_get_settings();

    if ( 'international' !== $settings['phone']['mode'] ) {
        return;
    }

    $vendor_url  = plugin_dir_url( __FILE__ ) . 'assets/vendor/intl-tel-input/';
    $vendor_path = plugin_dir_path( __FILE__ ) . 'assets/vendor/intl-tel-input/';

    wp_enqueue_style(
        'xw-intl-tel-input',
        $vendor_url . 'css/intlTelInput.min.css',
        array(),
        '29.1.2'
    );
    wp_enqueue_style(
        'xw-phone-field',
        $vendor_url . 'xleon-phone.css',
        array( 'xw-intl-tel-input' ),
        filemtime( $vendor_path . 'xleon-phone.css' )
    );
    wp_enqueue_script(
        'xw-intl-tel-input',
        $vendor_url . 'js/intlTelInputWithUtils.min.js',
        array(),
        '29.1.2',
        true
    );
    wp_enqueue_script(
        'xw-phone-field',
        plugin_dir_url( __FILE__ ) . 'assets/js/phone-field.js',
        array( 'xw-intl-tel-input' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/phone-field.js' ),
        true
    );
    wp_localize_script(
        'xw-phone-field',
        'xwPhoneSettings',
        array(
            'allowedCountries' => $settings['phone']['allowed_countries'],
            'primaryCountries' => $settings['phone']['primary_countries'],
            'language'         => $settings['phone']['language'],
        )
    );
}

add_action('wp_footer',function(){
if ( ! xw_feature_enabled( 'elementor_phone_mask' ) ) return;
$xw_phone_settings = xw_get_settings();
if ( 'area_code' !== $xw_phone_settings['phone']['mode'] ) return;
?>
<script>
jQuery(function($){
    function getDigits(v){
        return v.replace(/\D/g,'');
    }
    function formatUSPhone(d){
        if(!d.length){return '';}
        if(d.length>6){return '('+d.slice(0,3)+')'+d.slice(3,6)+'-'+d.slice(6);}
        if(d.length>3){return '('+d.slice(0,3)+')'+d.slice(3);}
        return '('+d;
    }
    function cleanPhoneConstraints(scope){
        $(scope).find('form.elementor-form input[type="tel"],form.elementor-form .elementor-field-group-phone input').each(function(){
            this.removeAttribute('pattern');
            this.removeAttribute('minlength');
            this.removeAttribute('maxlength');
        });
    }
    function setInvalid(f){
        f.css('border-color','red');
    }
    function setValid(f){
        f.css('border-color','');
    }
    function toggleSubmitState(f,isValid){
        var form=f.closest('form');
        var submit=form.find('button.elementor-button[type="submit"]');
        if(!isValid){
            submit.prop('disabled',true).addClass('xl-submit-disabled').css('pointer-events','none');
        }else{
            submit.prop('disabled',false).removeClass('xl-submit-disabled').css('pointer-events','auto');
        }
    }
    cleanPhoneConstraints(document);
    $(document).on('focus','form.elementor-form input[type="tel"],form.elementor-form .elementor-field-group-phone input',function(){
        cleanPhoneConstraints($(this).closest('form.elementor-form'));
    });
    $(document).on('input','form.elementor-form input[type="tel"],form.elementor-form .elementor-field-group-phone input',function(){
        var f=$(this),d=getDigits(f.val()).slice(0,10);
        f.val(formatUSPhone(d));
        if(d.length===0){
            setValid(f);
            toggleSubmitState(f,true);
            return;
        }
        if(d.length<9){
            setInvalid(f);
            toggleSubmitState(f,false);
        }else{
            setValid(f);
            toggleSubmitState(f,true);
        }
    });
});
</script>
<style>
button.elementor-button.xl-submit-disabled{
    opacity:.5 !important;
    cursor:not-allowed !important;
}
</style>
<?php });


// MASCARA DE CORREO PARA ELEMENTOR FORM

add_action('wp_footer', function() {
if ( ! xw_feature_enabled( 'elementor_email_mask' ) ) return;
?>
<script>
jQuery(function($){
    $(document).on('input','form.elementor-form input[type="email"], form.elementor-form .elementor-field-group-email input', function(){
        let v = $(this).val();
        v = v.replace(/\s+/g,'');
        v = v.replace(/[^a-zA-Z0-9@._-]/g,'');
        const parts = v.split('@');
        if(parts.length > 2){ v = parts[0] + '@' + parts.slice(1).join(''); }
        if(v.endsWith('@')){ v = v.slice(0,-1) + '@'; }
        $(this).val(v);
    });
});
</script>
<?php });


// MASCARA DE MENSAJE PARA ELEMENTOR FORM

add_action('wp_footer','xl_lock_message_invalid');
function xl_lock_message_invalid(){
if ( ! xw_feature_enabled( 'elementor_message_mask' ) ) return;
?>
<script>
jQuery(function($){
    var allowed=/^[a-zA-Z0-9 áéíóúÁÉÍÓÚñÑ¿?¡!.,:;()\-\n\r]*$/;
    var forbiddenUrl=/(?:\b(?:https?|ftp):|\bwww\.|\b(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9-]{2,59})\b|(?:^|\s)\.(?:[a-z]{2,63}|xn--[a-z0-9-]{2,59})\b)/i;
    var selector='.elementor-field-type-textarea textarea';

    function updateSubmitState(form){
        var invalid=form.find('[data-xw-message-valid="0"],[data-xw-phone-valid="0"]').length>0;
        form.find('button.elementor-button[type="submit"]')
            .prop('disabled',invalid)
            .toggleClass('xl-submit-disabled',invalid);
    }

    function validateMessageField(field){
        var val=field.val()||'';
        var valid=allowed.test(val)&&!forbiddenUrl.test(val);
        var form=field.closest('form.elementor-form');

        field.toggleClass('xl-invalid',!valid).attr('data-xw-message-valid',valid?'1':'0');
        updateSubmitState(form);
        return valid;
    }

    $(document).on('input',selector,function(){
        validateMessageField($(this));
    });

    $(document).on('submit','form.elementor-form',function(event){
        var valid=true;
        $(this).find(selector).each(function(){
            if(!validateMessageField($(this))){valid=false;}
        });
        if(!valid){
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    });

    $(selector).each(function(){validateMessageField($(this));});
});
</script>
<style>
.elementor-field-type-textarea textarea.xl-invalid{
    border-color: red !important;
}
button.elementor-button.xl-submit-disabled{
    opacity:.5 !important;
    cursor:not-allowed !important;
    pointer-events:none !important;
}
</style>
<?php }




// WOOCOMMERCE: CAMBIAR A LA PRIMERA IMAGEN DE LA GALERÍA EN LOS LISTADOS

add_filter( 'wp_get_attachment_image_attributes', 'xw_add_product_loop_hover_image_attributes', 20, 3 );
function xw_add_product_loop_hover_image_attributes( $attr, $attachment, $size ) {
    if (
        ! xw_feature_enabled( 'woocommerce_product_hover_image' ) ||
        ! function_exists( 'wc_get_product' )
    ) {
        return $attr;
    }

    global $product;

    $loop_product = $product instanceof WC_Product ? $product : null;

    if ( ! $loop_product ) {
        $product_id = get_the_ID();

        if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
            return $attr;
        }

        $loop_product = wc_get_product( $product_id );
    }

    if ( ! $loop_product instanceof WC_Product || ! isset( $attachment->ID ) ) {
        return $attr;
    }

    $featured_id = (int) $loop_product->get_image_id();

    if ( ! $featured_id || (int) $attachment->ID !== $featured_id ) {
        return $attr;
    }

    $gallery_ids = $loop_product->get_gallery_image_ids();

    if ( empty( $gallery_ids ) ) {
        return $attr;
    }

    $hover_id  = (int) reset( $gallery_ids );
    $hover_src = wp_get_attachment_image_url( $hover_id, $size );

    if ( ! $hover_id || ! $hover_src ) {
        return $attr;
    }

    $attr['data-xw-wc-hover-src'] = esc_url( $hover_src );

    $hover_srcset = wp_get_attachment_image_srcset( $hover_id, $size );

    if ( $hover_srcset ) {
        $attr['data-xw-wc-hover-srcset'] = $hover_srcset;
    }

    return $attr;
}

add_action( 'wp_footer', 'xw_render_product_loop_hover_image', 99 );
function xw_render_product_loop_hover_image() {
    if ( is_admin() || ! xw_feature_enabled( 'woocommerce_product_hover_image' ) ) {
        return;
    }

    $settings     = xw_get_settings();
    $fade_seconds = isset( $settings['woocommerce']['product_hover_fade_seconds'] )
        ? (float) $settings['woocommerce']['product_hover_fade_seconds']
        : 0.4;
    $fade_ms      = (int) round( max( 0, min( 5, $fade_seconds ) ) * 1000 );
    ?>
    <style id="xw-woocommerce-product-hover-image">
    img.xw-wc-product-hover-base {
        opacity: 1 !important;
        transition-property: opacity !important;
        transition-timing-function: ease-in-out !important;
        transition-delay: 0ms !important;
    }

    img.xw-wc-product-hover-layer {
        display: block !important;
        position: absolute !important;
        z-index: 2 !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
        pointer-events: none !important;
        opacity: 0 !important;
        transition-property: opacity !important;
        transition-duration: <?php echo (int) $fade_ms; ?>ms !important;
        transition-timing-function: ease-in-out !important;
        transition-delay: 0ms !important;
    }

    @media (prefers-reduced-motion: reduce) {
        img.xw-wc-product-hover-layer {
            transition-duration: 0ms !important;
        }
    }
    </style>
    <script id="xw-woocommerce-product-hover-image-script">
    (() => {
        'use strict';

        const selector = 'img[data-xw-wc-hover-src]';
        const fadeDuration = window.matchMedia('(prefers-reduced-motion: reduce)').matches
            ? 0
            : <?php echo (int) $fade_ms; ?>;
        const fadeOutDuration = fadeDuration > 0 ? fadeDuration + 100 : 0;
        const states = new WeakMap();

        function isProductLoop(image) {
            return !!image.closest(
                '.elementor-loop-container,' +
                '.e-loop-item,' +
                'ul.products,' +
                '.products,' +
                '.wc-block-product,' +
                '.wc-block-product-template'
            );
        }

        function syncLayer(image, state) {
            const imageRect = image.getBoundingClientRect();
            const hostRect = state.host.getBoundingClientRect();
            const hostStyle = window.getComputedStyle(state.host);
            const imageStyle = window.getComputedStyle(image);
            const borderLeft = parseFloat(hostStyle.borderLeftWidth) || 0;
            const borderTop = parseFloat(hostStyle.borderTopWidth) || 0;

            state.layer.style.setProperty('left', `${imageRect.left - hostRect.left - borderLeft + state.host.scrollLeft}px`, 'important');
            state.layer.style.setProperty('top', `${imageRect.top - hostRect.top - borderTop + state.host.scrollTop}px`, 'important');
            state.layer.style.setProperty('width', `${imageRect.width}px`, 'important');
            state.layer.style.setProperty('height', `${imageRect.height}px`, 'important');
            state.layer.style.setProperty('border-radius', imageStyle.borderRadius, 'important');
            state.layer.style.setProperty('object-fit', imageStyle.objectFit || 'fill', 'important');
            state.layer.style.setProperty('object-position', imageStyle.objectPosition || '50% 50%', 'important');
        }

        function prepareImage(image) {
            if (!isProductLoop(image)) {
                return null;
            }

            let state = states.get(image);

            if (state) {
                syncLayer(image, state);
                return state;
            }

            const picture = image.closest('picture');
            const host = picture && picture.contains(image)
                ? picture.parentElement
                : image.parentElement;

            if (!host) {
                return null;
            }

            if ('static' === window.getComputedStyle(host).position) {
                host.style.setProperty('position', 'relative', 'important');
            }

            const layer = new Image();
            const hoverSrcset = image.dataset.xwWcHoverSrcset || '';

            layer.className = 'xw-wc-product-hover-layer';
            layer.alt = '';
            layer.setAttribute('aria-hidden', 'true');
            layer.decoding = 'async';
            layer.loading = 'eager';

            if (hoverSrcset) {
                layer.srcset = hoverSrcset;
                layer.sizes = image.getAttribute('sizes') || '100vw';
            }

            layer.src = image.dataset.xwWcHoverSrc;
            layer.style.setProperty('opacity', '0', 'important');
            image.classList.add('xw-wc-product-hover-base');
            host.appendChild(layer);

            state = {
                host,
                layer,
                resizeObserver: null,
                active: false
            };

            states.set(image, state);
            syncLayer(image, state);

            if ('ResizeObserver' in window) {
                state.resizeObserver = new ResizeObserver(() => syncLayer(image, state));
                state.resizeObserver.observe(image);
            }

            image.addEventListener('load', () => syncLayer(image, state));
            layer.addEventListener('load', () => {
                syncLayer(image, state);

                if (state.active) {
                    revealHoverImage(image, state);
                }
            });

            return state;
        }

        function revealHoverImage(image, state) {
            if (!state.layer.complete || !state.layer.naturalWidth) {
                return;
            }

            image.style.setProperty('transition-duration', `${fadeOutDuration}ms`, 'important');
            state.layer.style.setProperty('transition-duration', `${fadeDuration}ms`, 'important');
            void state.layer.offsetWidth;
            image.style.setProperty('opacity', '0', 'important');
            state.layer.style.setProperty('opacity', '1', 'important');
        }

        function setHoverState(image, active) {
            const state = prepareImage(image);

            if (!state) {
                return;
            }

            syncLayer(image, state);

            if (state.active === active) {
                return;
            }

            state.active = active;

            if (active) {
                revealHoverImage(image, state);
                return;
            }

            image.style.setProperty('transition-duration', `${fadeDuration}ms`, 'important');
            state.layer.style.setProperty('transition-duration', `${fadeOutDuration}ms`, 'important');
            void state.layer.offsetWidth;
            image.style.setProperty('opacity', '1', 'important');
            state.layer.style.setProperty('opacity', '0', 'important');
        }

        document.addEventListener('mouseover', (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            const image = event.target.closest(selector);

            if (image) {
                setHoverState(image, true);
            }
        });

        document.addEventListener('mouseout', (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            const image = event.target.closest(selector);

            if (!image || (event.relatedTarget instanceof Node && image.contains(event.relatedTarget))) {
                return;
            }

            setHoverState(image, false);
        });

        function scanImages(root) {
            if (!(root instanceof Element) && root !== document) {
                return;
            }

            if (root instanceof Element && root.matches(selector)) {
                prepareImage(root);
            }

            root.querySelectorAll(selector).forEach(prepareImage);
        }

        if ('loading' === document.readyState) {
            document.addEventListener('DOMContentLoaded', () => scanImages(document), { once: true });
        } else {
            scanImages(document);
        }

        window.addEventListener('resize', () => scanImages(document), { passive: true });

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node instanceof Element) {
                        scanImages(node);
                    }
                });
            });
        });

        observer.observe(document.documentElement, {
            childList: true,
            subtree: true
        });
    })();
    </script>
    <?php
}


// WOOCOMMERCE: MOSTRAR IMÁGENES DE PRODUCTOS EN EL CHECKOUT

add_filter( 'woocommerce_cart_item_name', 'xw_add_checkout_product_thumbnail', 9999, 3 );
function xw_add_checkout_product_thumbnail( $name, $cart_item, $cart_item_key ) {
    if (
        ! xw_feature_enabled( 'woocommerce_checkout_product_images' ) ||
        ! function_exists( 'is_checkout' ) ||
        ! is_checkout()
    ) {
        return $name;
    }

    $product = isset( $cart_item['data'] ) && is_object( $cart_item['data'] )
        ? $cart_item['data']
        : null;

    if ( ! $product || ! is_a( $product, 'WC_Product' ) || ! is_callable( array( $product, 'get_image' ) ) ) {
        return $name;
    }

    $thumbnail = $product->get_image(
        array( 55, 55 ),
        array(
            'class'   => 'xw-checkout-product-thumbnail',
            'loading' => 'lazy',
        )
    );

    if ( '' === trim( (string) $thumbnail ) ) {
        return $name;
    }

    return '<span class="xw-checkout-product-line">'
        . $thumbnail
        . '<span class="xw-checkout-product-name">' . $name . '</span>'
        . '</span>';
}

add_action( 'wp_head', 'xw_checkout_product_thumbnail_styles', 99 );
function xw_checkout_product_thumbnail_styles() {
    if (
        is_admin() ||
        ! xw_feature_enabled( 'woocommerce_checkout_product_images' ) ||
        ! function_exists( 'is_checkout' ) ||
        ! is_checkout()
    ) {
        return;
    }
    ?>
    <style id="xw-checkout-product-images">
    .woocommerce-checkout-review-order-table .xw-checkout-product-line {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        max-width: 100%;
        vertical-align: middle;
    }

    .woocommerce-checkout-review-order-table .xw-checkout-product-thumbnail {
        width: 55px;
        height: 55px;
        max-width: 55px;
        margin: 0;
        object-fit: cover;
        flex: 0 0 55px;
    }

    .woocommerce-checkout-review-order-table .xw-checkout-product-name {
        min-width: 0;
    }
    </style>
    <?php
}


// WOOCOMMERCE: OCULTAR EL ENVÍO ÚNICAMENTE EN EL CARRITO

add_filter( 'woocommerce_cart_needs_shipping', 'xw_hide_shipping_in_cart', 9999 );
function xw_hide_shipping_in_cart( $needs_shipping ) {
    if (
        xw_feature_enabled( 'woocommerce_hide_cart_shipping' ) &&
        function_exists( 'is_cart' ) &&
        is_cart()
    ) {
        return false;
    }

    return $needs_shipping;
}


// WOOCOMMERCE: EXIGIR EL CORREO DE LA CUENTA EN EL CHECKOUT

function xw_get_logged_in_account_email() {
    if ( ! is_user_logged_in() ) {
        return '';
    }

    $current_user = wp_get_current_user();

    return isset( $current_user->user_email )
        ? sanitize_email( $current_user->user_email )
        : '';
}

add_filter( 'woocommerce_checkout_fields', 'xw_lock_logged_in_checkout_email', 9999 );
function xw_lock_logged_in_checkout_email( $fields ) {
    if (
        ! xw_feature_enabled( 'woocommerce_require_account_email' ) ||
        ! is_user_logged_in() ||
        ! isset( $fields['billing']['billing_email'] ) ||
        ! is_array( $fields['billing']['billing_email'] )
    ) {
        return $fields;
    }

    $account_email = xw_get_logged_in_account_email();

    if ( '' === $account_email ) {
        return $fields;
    }

    $attributes = isset( $fields['billing']['billing_email']['custom_attributes'] ) &&
        is_array( $fields['billing']['billing_email']['custom_attributes'] )
        ? $fields['billing']['billing_email']['custom_attributes']
        : array();

    $attributes['readonly'] = 'readonly';
    $fields['billing']['billing_email']['default']           = $account_email;
    $fields['billing']['billing_email']['required']          = true;
    $fields['billing']['billing_email']['custom_attributes'] = $attributes;

    return $fields;
}

add_filter( 'woocommerce_checkout_get_value', 'xw_force_logged_in_checkout_email_value', 9999, 2 );
function xw_force_logged_in_checkout_email_value( $value, $input ) {
    if (
        'billing_email' !== $input ||
        ! xw_feature_enabled( 'woocommerce_require_account_email' ) ||
        ! is_user_logged_in()
    ) {
        return $value;
    }

    $account_email = xw_get_logged_in_account_email();

    return '' !== $account_email ? $account_email : $value;
}

add_action( 'woocommerce_after_checkout_validation', 'xw_validate_logged_in_checkout_email', 10, 2 );
function xw_validate_logged_in_checkout_email( $data, $errors ) {
    if (
        ! xw_feature_enabled( 'woocommerce_require_account_email' ) ||
        ! is_user_logged_in() ||
        ! $errors instanceof WP_Error
    ) {
        return;
    }

    $account_email  = xw_get_logged_in_account_email();
    $checkout_email = isset( $data['billing_email'] )
        ? sanitize_email( $data['billing_email'] )
        : '';

    if ( '' !== $account_email && 0 !== strcasecmp( $checkout_email, $account_email ) ) {
        $errors->add(
            'xw_checkout_account_email',
            xw_t(
                'No está permitido cambiar el correo electrónico de su cuenta durante el checkout.',
                'Changing your account email during checkout is not allowed.'
            )
        );
    }
}


// WOOCOMMERCE: ACTUALIZAR EL CARRITO AL CAMBIAR LA CANTIDAD

add_action( 'wp_enqueue_scripts', 'xw_enqueue_woocommerce_auto_cart_dependencies' );
function xw_enqueue_woocommerce_auto_cart_dependencies() {
    if (
        ! xw_feature_enabled( 'woocommerce_auto_cart' ) ||
        ! function_exists( 'is_cart' ) ||
        ! is_cart()
    ) {
        return;
    }

    wp_enqueue_script( 'jquery' );
}

add_action( 'wp_footer', 'xw_woocommerce_auto_update_cart', 40 );
function xw_woocommerce_auto_update_cart() {
    if (
        ! xw_feature_enabled( 'woocommerce_auto_cart' ) ||
        ! function_exists( 'is_cart' ) ||
        ! is_cart()
    ) {
        return;
    }

    $settings = xw_get_settings();
    $seconds  = isset( $settings['woocommerce']['cart_update_delay'] )
        ? (float) $settings['woocommerce']['cart_update_delay']
        : 1;
    $delay    = (int) round( max( 0, min( 30, $seconds ) ) * 1000 );
    ?>
    <script>
    (function ($) {
        'use strict';

        var updateTimer = null;
        var delay = <?php echo (int) $delay; ?>;

        $(document.body).on('input change', 'div.woocommerce form.woocommerce-cart-form input.qty', function () {
            window.clearTimeout(updateTimer);
            updateTimer = window.setTimeout(function () {
                var button = $('div.woocommerce [name="update_cart"]').first();

                if (!button.length || button.hasClass('loading')) {
                    return;
                }

                button.prop('disabled', false).removeAttr('disabled').trigger('click');
            }, delay);
        });

        $(document.body).on('updated_wc_div', function () {
            window.clearTimeout(updateTimer);
            updateTimer = null;
        });
    })(jQuery);
    </script>
    <?php
}


// WOOCOMMERCE: OCULTAR LOS MENSAJES DE CONFIRMACIÓN DEL FRONTEND

add_filter( 'woocommerce_add_message', 'xw_hide_woocommerce_success_message', PHP_INT_MAX );
function xw_hide_woocommerce_success_message( $message ) {
    if (
        ! xw_feature_enabled( 'woocommerce_hide_success_messages' ) ||
        ( is_admin() && ! wp_doing_ajax() )
    ) {
        return $message;
    }

    return '';
}

add_action( 'wp_head', 'xw_hide_woocommerce_success_message_styles', 99 );
function xw_hide_woocommerce_success_message_styles() {
    if ( ! xw_feature_enabled( 'woocommerce_hide_success_messages' ) || is_admin() ) {
        return;
    }
    ?>
    <style id="xw-hide-woocommerce-success-messages">
    .woocommerce-message {
        display: none !important;
    }
    </style>
    <?php
}


// WOOCOMMERCE: ELIMINAR LAS IMÁGENES AL BORRAR DEFINITIVAMENTE UN PRODUCTO

add_action( 'before_delete_post', 'xw_delete_product_images', 10, 2 );

/**
 * Comprueba si otro producto o una de sus variaciones utiliza una imagen.
 *
 * @param int $attachment_id ID de la imagen.
 * @param int $product_id    Producto que se está eliminando.
 * @return bool
 */
function xw_product_image_is_shared( $attachment_id, $product_id ) {
    $other_uses = get_posts(
        array(
            'post_type'          => array( 'product', 'product_variation' ),
            'post_status'        => 'any',
            'post__not_in'       => array( $product_id ),
            'post_parent__not_in' => array( $product_id ),
            'posts_per_page'     => 1,
            'fields'             => 'ids',
            'no_found_rows'      => true,
            'suppress_filters'   => true,
            'meta_query'         => array(
                'relation' => 'OR',
                array(
                    'key'     => '_thumbnail_id',
                    'value'   => (string) $attachment_id,
                    'compare' => '=',
                ),
                array(
                    'key'     => '_product_image_gallery',
                    'value'   => '(^|,)' . absint( $attachment_id ) . '(,|$)',
                    'compare' => 'REGEXP',
                ),
            ),
        )
    );

    return ! empty( $other_uses );
}

function xw_delete_product_images( $post_id, $post = null ) {
    if ( ! xw_feature_enabled( 'woocommerce_delete_product_images' ) ) {
        return;
    }

    if ( ! $post instanceof WP_Post ) {
        $post = get_post( $post_id );
    }

    if ( ! $post || 'product' !== $post->post_type ) {
        return;
    }

    $image_ids = get_posts(
        array(
            'post_type'      => 'attachment',
            'post_status'    => 'any',
            'post_parent'    => $post_id,
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        )
    );

    $thumbnail_id = get_post_thumbnail_id( $post_id );
    $gallery_ids  = array_filter(
        array_map(
            'absint',
            explode( ',', (string) get_post_meta( $post_id, '_product_image_gallery', true ) )
        )
    );

    if ( $thumbnail_id ) {
        $image_ids[] = (int) $thumbnail_id;
    }

    $image_ids = array_values( array_unique( array_merge( $image_ids, $gallery_ids ) ) );

    foreach ( $image_ids as $attachment_id ) {
        $attachment_id = absint( $attachment_id );

        if (
            ! $attachment_id ||
            ! wp_attachment_is_image( $attachment_id ) ||
            xw_product_image_is_shared( $attachment_id, $post_id )
        ) {
            continue;
        }

        wp_delete_attachment( $attachment_id, true );
    }
}


// PRELOADER

add_action('wp_head', function () {
    if ( ! xw_feature_enabled( 'preloader' ) ) return;
    if (is_admin()) return;
    echo "<script>document.documentElement.classList.add('js');</script>";
}, 0);
add_action('wp_head', function () {
    if ( ! xw_feature_enabled( 'preloader' ) ) return;
    if (is_admin()) return;
    echo "<style>body{opacity:1}html.js body{opacity:0;transition:opacity .35s ease-in}html.js body.loaded{opacity:1}</style>";
}, 1);
add_action('wp_footer', function () {
    if ( ! xw_feature_enabled( 'preloader' ) ) return;
    if (is_admin()) return;
    echo "<script>window.addEventListener('load',function(){document.body.classList.add('loaded');});</script>";
}, 100);


// OCULTAR ANUNCIOS 1.0, MOSTRAR SOLO EN ESCRITORIO

add_action('current_screen', function ($screen) {
    if ( ! xw_feature_enabled( 'admin_notices' ) ) return;
    if (!is_object($screen)) return;
    $permitidos = ['dashboard', 'dashboard-network'];
    $id   = isset($screen->id)   ? $screen->id   : '';
    $base = isset($screen->base) ? $screen->base : '';
    $es_permitido = in_array($id, $permitidos, true) || in_array($base, $permitidos, true);
    if ($es_permitido) return;
    foreach (['admin_notices','all_admin_notices','network_admin_notices','user_admin_notices','update_nag'] as $hook) {
        if (has_action($hook)) remove_all_actions($hook);
    }
    add_action('admin_head', function () {
        echo '<style>
            .notice,
            .update-nag,
            .updated,
            .error {
                display: none !important;
            }
        </style>';
    }, 999);
}, 20);

// OCULTAR ANUNCIOS 2.0, ACORDEON PARA ANUNCIOS

add_action('admin_footer-index.php', function () {
    if ( ! xw_feature_enabled( 'admin_notices' ) ) return;
    $title = xw_t( 'Anuncios', 'Announcements' );
    $title = esc_js($title);
    echo "<script>(function(){
window.addEventListener('load',function(){
var selectors='.update-nag,.notice,.error,.updated,.update-message,.plugin-message';
var nodes=document.querySelectorAll(selectors);
var notices=[];
nodes.forEach(function(n){
    if(!n.closest('#xl-announcements-accordion')) notices.push(n);
});
if(!notices.length)return;
var wrap=document.querySelector('div.wrap')||document.querySelector('#wpbody-content')||document.body;
var acc=document.createElement('div');
acc.id='xl-announcements-accordion';
acc.style.margin='20px 0';
var header=document.createElement('button');
header.type='button';
header.className='button button-secondary';
header.setAttribute('aria-expanded','false');
header.style.display='flex';
header.style.alignItems='center';
header.style.gap='8px';
header.style.marginBottom='10px';
var caret=document.createElement('span');
caret.textContent='▸';
var text=document.createElement('span');
text.textContent='".$title."';
header.appendChild(caret);
header.appendChild(text);
var body=document.createElement('div');
body.style.display='none';
body.style.border='1px solid #c3c4c7';
body.style.borderRadius='4px';
body.style.padding='10px';
body.style.background='#ffffff';
body.style.maxHeight='70vh';
body.style.overflow='auto';
notices.forEach(function(n){
    body.appendChild(n);
});
header.addEventListener('click',function(){
    var expanded=header.getAttribute('aria-expanded')==='true';
    header.setAttribute('aria-expanded',expanded?'false':'true');
    body.style.display=expanded?'none':'block';
    caret.textContent=expanded?'▸':'▾';
});
acc.appendChild(header);
acc.appendChild(body);
var firstChild=wrap.firstElementChild;
if(firstChild) wrap.insertBefore(acc,firstChild.nextSibling);
else wrap.appendChild(acc);
});
})();</script>";
});


// -----------------------------------------------------------------------------------
