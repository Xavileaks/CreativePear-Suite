<?php
/**
 * Pantalla y utilidades de configuración de Creative Pear Suite.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Devuelve el idioma de interfaz compatible con el plugin.
 *
 * @return string
 */
function xw_interface_language() {
    $locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();

    return 0 === strpos( strtolower( (string) $locale ), 'es' ) ? 'es' : 'en';
}

/**
 * Selecciona un texto en español o inglés según el idioma de WordPress.
 *
 * @param string $spanish Texto en español.
 * @param string $english Texto en inglés.
 * @return string
 */
function xw_t( $spanish, $english ) {
    return 'es' === xw_interface_language() ? $spanish : $english;
}

function xw_get_feature_definitions() {
    return array(
        'assets_styles' => array(
            'title'       => xw_t( 'Estilos CSS globales', 'Global CSS styles' ),
            'description' => xw_t( 'Carga automáticamente los archivos CSS de assets/css en la web.', 'Automatically loads the CSS files from assets/css on the website.' ),
            'settings'    => true,
        ),
        'classic_editor' => array(
            'title'       => xw_t( 'Editor clásico', 'Classic editor' ),
            'description' => xw_t( 'Desactiva el editor de bloques para entradas, páginas y widgets.', 'Disables the block editor for posts, pages, and widgets.' ),
        ),
        'hide_wp_version' => array(
            'title'       => xw_t( 'Ocultar versión de WordPress', 'Hide WordPress version' ),
            'description' => xw_t( 'Elimina la etiqueta generator del código fuente público.', 'Removes the generator tag from the public source code.' ),
        ),
        'login_customization' => array(
            'title'       => xw_t( 'Personalizar acceso', 'Customize login' ),
            'description' => xw_t( 'Aplica el logotipo, los colores y el estilo personalizado a wp-login.php.', 'Applies the logo, colors, and custom styling to wp-login.php.' ),
            'settings'    => true,
        ),
        'hide_admin_bar' => array(
            'title'       => xw_t( 'Ocultar barra a no administradores', 'Hide admin bar for non-administrators' ),
            'description' => xw_t( 'Oculta la barra superior en la web para usuarios que no son administradores.', 'Hides the top admin bar on the website for non-administrator users.' ),
        ),
        'admin_bar_style' => array(
            'title'       => xw_t( 'Estilo compacto de la barra superior', 'Compact admin bar style' ),
            'description' => xw_t( 'Reduce el texto y alinea los elementos de la barra de administración.', 'Shortens text and aligns items in the admin bar.' ),
        ),
        'admin_branding' => array(
            'title'       => xw_t( 'Marca del administrador', 'Admin branding' ),
            'description' => xw_t( 'Oculta el logo de WordPress y personaliza el texto enlazado del pie del administrador.', 'Hides the WordPress logo and customizes the linked admin footer text.' ),
            'settings'    => true,
        ),
        'back_to_top' => array(
            'title'       => xw_t( 'Volver arriba', 'Back to top' ),
            'description' => xw_t( 'Muestra un botón con progreso de lectura que regresa suavemente al inicio de la página.', 'Displays a reading-progress button that smoothly returns to the top of the page.' ),
            'settings'    => true,
        ),
        'elementor_messages' => array(
            'title'       => xw_t( 'Form Elementor: mensajes flotantes', 'Form Elementor: floating messages' ),
            'description' => xw_t( 'Muestra las respuestas de formularios como avisos flotantes temporales.', 'Displays form responses as temporary floating notices.' ),
        ),
        'elementor_phone_mask' => array(
            'title'       => xw_t( 'Form Elementor: máscara de teléfono', 'Form Elementor: phone mask' ),
            'description' => xw_t( 'Formatea y valida números telefónicos en formularios de Elementor.', 'Formats and validates phone numbers in Elementor forms.' ),
            'settings'    => true,
        ),
        'elementor_email_mask' => array(
            'title'       => xw_t( 'Form Elementor: limpieza de correo', 'Form Elementor: email cleanup' ),
            'description' => xw_t( 'Evita espacios y caracteres no válidos en campos de correo de Elementor.', 'Prevents spaces and invalid characters in Elementor email fields.' ),
        ),
        'elementor_message_mask' => array(
            'title'       => xw_t( 'Form Elementor: validación de mensajes', 'Form Elementor: message validation' ),
            'description' => xw_t( 'Limita los caracteres admitidos en áreas de texto de Elementor.', 'Limits the characters allowed in Elementor text areas.' ),
        ),
        'woocommerce_auto_cart' => array(
            'title'       => xw_t( 'WooCommerce: actualizar carrito automáticamente', 'WooCommerce: update cart automatically' ),
            'description' => xw_t( 'Actualiza el importe del carrito después de cambiar la cantidad de un producto.', 'Updates the cart total after changing a product quantity.' ),
            'settings'    => true,
        ),
        'woocommerce_hide_success_messages' => array(
            'title'       => xw_t( 'WooCommerce: ocultar mensajes de confirmación', 'WooCommerce: hide confirmation messages' ),
            'description' => xw_t( 'Oculta únicamente los avisos de éxito como “producto añadido” o “carrito actualizado”; mantiene visibles los errores y la información.', 'Hides only success notices such as “product added” or “cart updated”; errors and information remain visible.' ),
        ),
        'woocommerce_delete_product_images' => array(
            'title'       => xw_t( 'WooCommerce: eliminar imágenes con el producto', 'WooCommerce: delete images with product' ),
            'description' => xw_t( 'Elimina permanentemente sus imágenes al borrar un producto y conserva las usadas por otro producto.', 'Permanently deletes its images with the product while preserving images used by another product.' ),
        ),
        'preloader' => array(
            'title'       => xw_t( 'Transición de carga', 'Loading transition' ),
            'description' => xw_t( 'Añade una aparición suave del contenido cuando termina de cargar la página.', 'Adds a smooth content fade-in when the page finishes loading.' ),
        ),
        'admin_notices' => array(
            'title'       => xw_t( 'Organizar anuncios del escritorio', 'Organize dashboard notices' ),
            'description' => xw_t( 'Oculta avisos fuera del escritorio y los agrupa allí en un acordeón.', 'Hides notices outside the dashboard and groups them there in an accordion.' ),
        ),
        'plugin_export' => array(
            'title'       => xw_t( 'Exportar plugin', 'Export plugin' ),
            'description' => xw_t( 'Añade un botón para descargar este plugin desde el editor de archivos.', 'Adds a button to download this plugin from the file editor.' ),
        ),
    );
}

/**
 * Organiza las funciones en las secciones visibles de la pantalla de ajustes.
 *
 * @return array
 */
function xw_get_feature_groups() {
    return array(
        'wordpress' => array(
            'title'       => 'WordPress',
            'description' => xw_t( 'Funciones generales del sitio y del administrador.', 'General website and administrator features.' ),
            'features'    => array(
                'assets_styles',
                'classic_editor',
                'hide_wp_version',
                'login_customization',
                'hide_admin_bar',
                'admin_bar_style',
                'admin_branding',
                'back_to_top',
                'preloader',
                'admin_notices',
                'plugin_export',
            ),
        ),
        'elementor' => array(
            'title'       => 'Elementor',
            'description' => xw_t( 'Funciones que actúan sobre los formularios de Elementor.', 'Features that apply to Elementor forms.' ),
            'features'    => array(
                'elementor_messages',
                'elementor_phone_mask',
                'elementor_email_mask',
                'elementor_message_mask',
            ),
        ),
        'woocommerce' => array(
            'title'       => 'WooCommerce',
            'description' => xw_t( 'Funciones específicas para tiendas WooCommerce.', 'Features specifically for WooCommerce stores.' ),
            'features'    => array(
                'woocommerce_auto_cart',
                'woocommerce_hide_success_messages',
                'woocommerce_delete_product_images',
            ),
        ),
    );
}

function xw_get_default_settings() {
    $features = array();

    foreach ( xw_get_feature_definitions() as $key => $definition ) {
        $features[ $key ] = 0;
    }

    return array(
        'features' => $features,
        'login'    => array(
            'primary_color'     => '',
            'button_text_color' => '',
            'logo_url'          => '',
            'logo_width'       => 200,
        ),
        'styles'   => array(
            'primary_color'         => '',
            'scrollbar_width'       => 10,
            'scrollbar_track_color' => '',
            'scrollbar_thumb_color' => '',
            'scrollbar_radius'      => 0,
        ),
        'admin_branding' => array(
            'footer_html' => xw_t(
                'Sitio creado por <a href="https://creativepearagency.com" target="_blank" rel="noopener noreferrer">Creative Pear Agency</a>.',
                'Website created by <a href="https://creativepearagency.com" target="_blank" rel="noopener noreferrer">Creative Pear Agency</a>.'
            ),
        ),
        'back_to_top' => array(
            'icon'              => 'arrow-up',
            'position'          => 'bottom-right',
            'shape'             => 'circle',
            'vertical_margin'   => 20,
            'horizontal_margin' => 20,
            'offset'            => 300,
            'duration'          => 500,
            'button_size'       => 46,
            'border_size'       => 1,
            'icon_size'         => 20,
            'progress_size'     => 3,
            'background_color'  => '',
            'border_color'      => '',
            'icon_color'        => '',
            'progress_color'    => '',
            'hide_mobile'       => 0,
            'hide_tablet'       => 0,
            'hide_desktop'      => 0,
        ),
        'phone' => array(
            'mode'              => 'area_code',
            'language'          => xw_interface_language(),
            'allowed_countries' => array(),
            'primary_countries' => array(),
        ),
        'woocommerce' => array(
            'cart_update_delay' => 1,
        ),
    );
}

function xw_get_settings() {
    $saved = get_option( 'xw_settings', array() );

    if ( ! is_array( $saved ) ) {
        $saved = array();
    }

    // Convierte una configuración anterior de tres campos al nuevo editor visual.
    if ( isset( $saved['admin_branding']['footer_text'] ) && ! isset( $saved['admin_branding']['footer_html'] ) ) {
        $text      = (string) $saved['admin_branding']['footer_text'];
        $link_text = isset( $saved['admin_branding']['link_text'] ) ? (string) $saved['admin_branding']['link_text'] : '';
        $url       = isset( $saved['admin_branding']['footer_url'] ) ? (string) $saved['admin_branding']['footer_url'] : '';
        $position  = '' !== $link_text ? strpos( $text, $link_text ) : false;

        if ( false !== $position && '' !== $url ) {
            $saved['admin_branding']['footer_html'] = esc_html( substr( $text, 0, $position ) )
                . '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">'
                . esc_html( $link_text )
                . '</a>'
                . esc_html( substr( $text, $position + strlen( $link_text ) ) );
        } else {
            $saved['admin_branding']['footer_html'] = esc_html( $text );
        }
    }

    return array_replace_recursive( xw_get_default_settings(), $saved );
}

/**
 * Crea los ajustes iniciales sin sobrescribir una configuración existente.
 *
 * @return void
 */
function xw_activate_plugin() {
    if ( null === get_option( 'xw_settings', null ) ) {
        add_option( 'xw_settings', xw_get_default_settings() );
    }
}

function xw_feature_enabled( $feature ) {
    $settings = xw_get_settings();

    return ! empty( $settings['features'][ $feature ] );
}

function xw_sanitize_settings( $input ) {
    $defaults  = xw_get_default_settings();
    $sanitized = $defaults;
    $input     = is_array( $input ) ? $input : array();
    $features  = isset( $input['features'] ) && is_array( $input['features'] ) ? $input['features'] : array();
    $login     = isset( $input['login'] ) && is_array( $input['login'] ) ? $input['login'] : array();
    $styles    = isset( $input['styles'] ) && is_array( $input['styles'] ) ? $input['styles'] : array();
    $branding  = isset( $input['admin_branding'] ) && is_array( $input['admin_branding'] ) ? $input['admin_branding'] : array();
    $back_to_top = isset( $input['back_to_top'] ) && is_array( $input['back_to_top'] ) ? $input['back_to_top'] : array();
    $phone     = isset( $input['phone'] ) && is_array( $input['phone'] ) ? $input['phone'] : array();
    $woocommerce = isset( $input['woocommerce'] ) && is_array( $input['woocommerce'] ) ? $input['woocommerce'] : array();

    foreach ( xw_get_feature_definitions() as $key => $definition ) {
        $sanitized['features'][ $key ] = empty( $features[ $key ] ) ? 0 : 1;
    }

    $primary_color = isset( $login['primary_color'] ) ? sanitize_hex_color( $login['primary_color'] ) : '';
    $button_text_color = isset( $login['button_text_color'] ) ? sanitize_hex_color( $login['button_text_color'] ) : '';
    $logo_width       = isset( $login['logo_width'] ) ? absint( $login['logo_width'] ) : $defaults['login']['logo_width'];

    $sanitized['login']['primary_color']     = $primary_color ? $primary_color : '';
    $sanitized['login']['button_text_color'] = $button_text_color ? $button_text_color : '';
    $sanitized['login']['logo_url']         = isset( $login['logo_url'] ) ? esc_url_raw( trim( $login['logo_url'] ) ) : '';
    $sanitized['login']['logo_width']       = max( 50, min( 600, $logo_width ) );

    $styles_primary = isset( $styles['primary_color'] ) ? sanitize_hex_color( $styles['primary_color'] ) : '';
    $track_color   = isset( $styles['scrollbar_track_color'] ) ? sanitize_hex_color( $styles['scrollbar_track_color'] ) : '';
    $thumb_color   = isset( $styles['scrollbar_thumb_color'] ) ? sanitize_hex_color( $styles['scrollbar_thumb_color'] ) : '';
    $scroll_width  = isset( $styles['scrollbar_width'] ) ? absint( $styles['scrollbar_width'] ) : $defaults['styles']['scrollbar_width'];
    $scroll_radius = isset( $styles['scrollbar_radius'] ) ? absint( $styles['scrollbar_radius'] ) : $defaults['styles']['scrollbar_radius'];

    $sanitized['styles']['primary_color']         = $styles_primary ? $styles_primary : '';
    $sanitized['styles']['scrollbar_width']       = max( 0, min( 40, $scroll_width ) );
    $sanitized['styles']['scrollbar_track_color'] = $track_color ? $track_color : '';
    $sanitized['styles']['scrollbar_thumb_color'] = $thumb_color ? $thumb_color : '';
    $sanitized['styles']['scrollbar_radius']      = max( 0, min( 50, $scroll_radius ) );

    $allowed_footer_html = array(
        'a' => array(
            'href'   => true,
            'target' => true,
            'rel'    => true,
        ),
    );
    $footer_html = isset( $branding['footer_html'] ) ? $branding['footer_html'] : $defaults['admin_branding']['footer_html'];
    $sanitized['admin_branding']['footer_html'] = trim( wp_kses( $footer_html, $allowed_footer_html ) );

    $back_to_top_icons = function_exists( 'xw_get_back_to_top_icon_options' )
        ? array_keys( xw_get_back_to_top_icon_options() )
        : array( 'arrow-up' );
    $back_to_top_icon = isset( $back_to_top['icon'] ) ? sanitize_key( $back_to_top['icon'] ) : $defaults['back_to_top']['icon'];
    $back_to_top_position = isset( $back_to_top['position'] ) ? sanitize_key( $back_to_top['position'] ) : $defaults['back_to_top']['position'];
    $back_to_top_shape = isset( $back_to_top['shape'] ) ? sanitize_key( $back_to_top['shape'] ) : $defaults['back_to_top']['shape'];
    $back_to_top_button_size = isset( $back_to_top['button_size'] ) ? absint( $back_to_top['button_size'] ) : $defaults['back_to_top']['button_size'];

    $sanitized['back_to_top']['icon'] = in_array( $back_to_top_icon, $back_to_top_icons, true )
        ? $back_to_top_icon
        : $defaults['back_to_top']['icon'];
    $sanitized['back_to_top']['position'] = in_array( $back_to_top_position, array( 'bottom-right', 'bottom-left' ), true )
        ? $back_to_top_position
        : $defaults['back_to_top']['position'];
    $sanitized['back_to_top']['shape'] = in_array( $back_to_top_shape, array( 'circle', 'rounded', 'square' ), true )
        ? $back_to_top_shape
        : $defaults['back_to_top']['shape'];
    $sanitized['back_to_top']['vertical_margin'] = max( 0, min( 200, isset( $back_to_top['vertical_margin'] ) ? absint( $back_to_top['vertical_margin'] ) : $defaults['back_to_top']['vertical_margin'] ) );
    $sanitized['back_to_top']['horizontal_margin'] = max( 0, min( 200, isset( $back_to_top['horizontal_margin'] ) ? absint( $back_to_top['horizontal_margin'] ) : $defaults['back_to_top']['horizontal_margin'] ) );
    $sanitized['back_to_top']['offset'] = max( 0, min( 5000, isset( $back_to_top['offset'] ) ? absint( $back_to_top['offset'] ) : $defaults['back_to_top']['offset'] ) );
    $sanitized['back_to_top']['duration'] = max( 0, min( 3000, isset( $back_to_top['duration'] ) ? absint( $back_to_top['duration'] ) : $defaults['back_to_top']['duration'] ) );
    $sanitized['back_to_top']['button_size'] = max( 32, min( 100, $back_to_top_button_size ) );
    $sanitized['back_to_top']['border_size'] = max( 0, min( 10, isset( $back_to_top['border_size'] ) ? absint( $back_to_top['border_size'] ) : $defaults['back_to_top']['border_size'] ) );
    $sanitized['back_to_top']['icon_size'] = max( 12, min( $sanitized['back_to_top']['button_size'] - 8, isset( $back_to_top['icon_size'] ) ? absint( $back_to_top['icon_size'] ) : $defaults['back_to_top']['icon_size'] ) );
    $sanitized['back_to_top']['progress_size'] = max( 1, min( 10, isset( $back_to_top['progress_size'] ) ? absint( $back_to_top['progress_size'] ) : $defaults['back_to_top']['progress_size'] ) );

    foreach ( array( 'background_color', 'border_color', 'icon_color', 'progress_color' ) as $color_key ) {
        $color = isset( $back_to_top[ $color_key ] ) ? sanitize_hex_color( $back_to_top[ $color_key ] ) : '';
        $sanitized['back_to_top'][ $color_key ] = $color ? $color : '';
    }

    foreach ( array( 'hide_mobile', 'hide_tablet', 'hide_desktop' ) as $visibility_key ) {
        $sanitized['back_to_top'][ $visibility_key ] = empty( $back_to_top[ $visibility_key ] ) ? 0 : 1;
    }

    $phone_mode = isset( $phone['mode'] ) && 'international' === $phone['mode'] ? 'international' : 'area_code';
    $phone_language = isset( $phone['language'] ) && 'en' === $phone['language'] ? 'en' : 'es';
    $allowed_countries = isset( $phone['allowed_countries'] ) && is_array( $phone['allowed_countries'] ) ? $phone['allowed_countries'] : array();
    $primary_countries = isset( $phone['primary_countries'] ) && is_array( $phone['primary_countries'] ) ? $phone['primary_countries'] : array();

    $allowed_countries = array_values(
        array_unique(
            array_filter(
                array_map( 'sanitize_key', $allowed_countries ),
                function ( $country ) {
                    return 1 === preg_match( '/^[a-z]{2}$/', $country );
                }
            )
        )
    );
    $primary_countries = array_values(
        array_unique(
            array_filter(
                array_map( 'sanitize_key', $primary_countries ),
                function ( $country ) use ( $allowed_countries ) {
                    return 1 === preg_match( '/^[a-z]{2}$/', $country )
                        && ( empty( $allowed_countries ) || in_array( $country, $allowed_countries, true ) );
                }
            )
        )
    );

    $sanitized['phone']['mode']              = $phone_mode;
    $sanitized['phone']['language']          = $phone_language;
    $sanitized['phone']['allowed_countries'] = $allowed_countries;
    $sanitized['phone']['primary_countries'] = array_slice( $primary_countries, 0, 2 );

    $cart_update_delay = isset( $woocommerce['cart_update_delay'] ) && is_scalar( $woocommerce['cart_update_delay'] )
        ? (float) str_replace( ',', '.', (string) $woocommerce['cart_update_delay'] )
        : $defaults['woocommerce']['cart_update_delay'];
    $sanitized['woocommerce']['cart_update_delay'] = max( 0, min( 30, round( $cart_update_delay, 1 ) ) );

    return $sanitized;
}

add_action( 'admin_init', 'xw_register_settings' );
function xw_register_settings() {
    register_setting(
        'xw_settings_group',
        'xw_settings',
        array(
            'type'              => 'array',
            'sanitize_callback' => 'xw_sanitize_settings',
            'default'           => xw_get_default_settings(),
        )
    );
}

add_action( 'admin_menu', 'xw_add_settings_page' );
function xw_add_settings_page() {
    add_options_page(
        'Creative Pear Suite',
        'Creative Pear Suite',
        'manage_options',
        'creativepear-suite',
        'xw_render_settings_page'
    );
}

add_action( 'admin_enqueue_scripts', 'xw_settings_assets' );
function xw_settings_assets( $hook_suffix ) {
    if ( 'settings_page_creativepear-suite' !== $hook_suffix ) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script(
        'xw-country-data',
        plugin_dir_url( XW_FUNCTIONS_FILE ) . 'assets/vendor/intl-tel-input/js/data.min.js',
        array(),
        '29.1.2',
        true
    );
    wp_enqueue_style(
        'xw-admin-settings',
        plugin_dir_url( XW_FUNCTIONS_FILE ) . 'assets/css/admin-settings.css',
        array(),
        filemtime( plugin_dir_path( XW_FUNCTIONS_FILE ) . 'assets/css/admin-settings.css' )
    );
    wp_enqueue_script(
        'xw-admin-settings',
        plugin_dir_url( XW_FUNCTIONS_FILE ) . 'assets/js/admin-settings.js',
        array( 'jquery', 'xw-country-data' ),
        filemtime( plugin_dir_path( XW_FUNCTIONS_FILE ) . 'assets/js/admin-settings.js' ),
        true
    );
    wp_localize_script(
        'xw-admin-settings',
        'xwAdminSettings',
        array(
            'phone' => xw_get_settings()['phone'],
            'i18n'  => array(
                'chooseLogo' => xw_t( 'Elegir logotipo', 'Choose logo' ),
                'useLogo'    => xw_t( 'Usar este logotipo', 'Use this logo' ),
            ),
        )
    );
}

function xw_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $settings = xw_get_settings();
    ?>
    <div class="wrap xw-settings-wrap">
        <div class="xw-settings-header">
            <div>
                <h1>Creative Pear Suite</h1>
                <p><?php echo esc_html( xw_t( 'Activa solo las funciones que necesita este sitio y ajusta las que admiten personalización.', 'Enable only the features this site needs and configure the ones that support customization.' ) ); ?></p>
            </div>
            <span class="xw-version">v<?php echo esc_html( XW_FUNCTIONS_VERSION ); ?></span>
        </div>

        <?php settings_errors(); ?>

        <form action="options.php" method="post">
            <?php settings_fields( 'xw_settings_group' ); ?>

            <?php $feature_definitions = xw_get_feature_definitions(); ?>
            <div class="xw-feature-sections">
                <?php foreach ( xw_get_feature_groups() as $group_key => $group ) : ?>
                    <section class="xw-feature-section" aria-labelledby="xw-section-<?php echo esc_attr( $group_key ); ?>">
                        <header class="xw-feature-section-header">
                            <h2 id="xw-section-<?php echo esc_attr( $group_key ); ?>"><?php echo esc_html( $group['title'] ); ?></h2>
                            <p><?php echo esc_html( $group['description'] ); ?></p>
                        </header>

                        <?php if ( empty( $group['features'] ) ) : ?>
                            <div class="xw-feature-empty">
                                <?php echo esc_html( xw_t( 'Todavía no hay funciones disponibles en esta sección.', 'There are no features available in this section yet.' ) ); ?>
                            </div>
                        <?php else : ?>
                            <div class="xw-feature-grid">
                                <?php foreach ( $group['features'] as $key ) : ?>
                                    <?php
                                    if ( empty( $feature_definitions[ $key ] ) ) {
                                        continue;
                                    }

                                    $feature = $feature_definitions[ $key ];
                                    ?>
                    <?php $enabled = ! empty( $settings['features'][ $key ] ); ?>
                    <section class="xw-feature-card<?php echo $enabled ? ' is-enabled' : ''; ?><?php echo ! empty( $feature['wide'] ) ? ' xw-feature-card--wide' : ''; ?>" data-xw-feature>
                        <div class="xw-feature-summary">
                            <div class="xw-feature-copy">
                                <h3><?php echo esc_html( $feature['title'] ); ?></h3>
                                <p><?php echo esc_html( $feature['description'] ); ?></p>
                            </div>
                            <label class="xw-switch">
                                <span class="screen-reader-text">
                                    <?php echo esc_html( sprintf( xw_t( 'Activar %s', 'Enable %s' ), $feature['title'] ) ); ?>
                                </span>
                                <input
                                    type="checkbox"
                                    name="xw_settings[features][<?php echo esc_attr( $key ); ?>]"
                                    value="1"
                                    <?php checked( $enabled ); ?>
                                    data-xw-toggle
                                >
                                <span class="xw-switch-track" aria-hidden="true"><span></span></span>
                            </label>
                        </div>

                        <?php if ( ! empty( $feature['settings'] ) ) : ?>
                            <button
                                type="button"
                                class="xw-options-toggle"
                                aria-expanded="false"
                                aria-controls="xw-options-<?php echo esc_attr( $key ); ?>"
                                data-xw-options-toggle
                            >
                                <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                                <span class="screen-reader-text"><?php echo esc_html( sprintf( xw_t( 'Mostrar u ocultar ajustes de %s', 'Show or hide %s settings' ), $feature['title'] ) ); ?></span>
                            </button>
                            <div
                                id="xw-options-<?php echo esc_attr( $key ); ?>"
                                class="xw-feature-options"
                                data-xw-options
                                hidden
                            >
                                <?php if ( 'assets_styles' === $key ) : ?>
                                    <div class="xw-field-row xw-field-full">
                                        <label for="xw-styles-primary-color"><?php echo esc_html( xw_t( 'Color global', 'Global color' ) ); ?> <code>--color-web</code></label>
                                        <div class="xw-color-control">
                                            <input id="xw-styles-primary-color" type="color" class="<?php echo empty( $settings['styles']['primary_color'] ) ? 'is-empty' : ''; ?>" value="<?php echo esc_attr( $settings['styles']['primary_color'] ?: '#000000' ); ?>" data-xw-color-picker>
                                            <input type="text" name="xw_settings[styles][primary_color]" class="xw-color-text" value="<?php echo esc_attr( $settings['styles']['primary_color'] ); ?>" maxlength="7" spellcheck="false" placeholder="#RRGGBB" aria-label="<?php echo esc_attr( xw_t( 'Código hexadecimal del color global', 'Global color hexadecimal code' ) ); ?>" data-xw-color-value>
                                        </div>
                                        <p><?php echo esc_html( xw_t( 'Cambia el color de selección, checkboxes, radios y cualquier estilo que utilice esta variable.', 'Changes selection colors, checkboxes, radio buttons, and any style that uses this variable.' ) ); ?></p>
                                    </div>

                                    <h3 class="xw-options-heading"><?php echo esc_html( xw_t( 'Barra de desplazamiento', 'Scrollbar' ) ); ?></h3>
                                    <div class="xw-field-row">
                                        <label for="xw-scrollbar-width"><?php echo esc_html( xw_t( 'Ancho', 'Width' ) ); ?></label>
                                        <div class="xw-number-control">
                                            <input id="xw-scrollbar-width" type="number" name="xw_settings[styles][scrollbar_width]" value="<?php echo esc_attr( $settings['styles']['scrollbar_width'] ); ?>" min="0" max="40" step="1">
                                            <span>px</span>
                                        </div>
                                        <p><?php echo esc_html( xw_t( 'Use 0 px si quiere ocultar visualmente la barra.', 'Use 0 px to visually hide the scrollbar.' ) ); ?></p>
                                    </div>
                                    <div class="xw-field-row">
                                        <label for="xw-scrollbar-track-color"><?php echo esc_html( xw_t( 'Color del carril', 'Track color' ) ); ?></label>
                                        <div class="xw-color-control">
                                            <input id="xw-scrollbar-track-color" type="color" class="<?php echo empty( $settings['styles']['scrollbar_track_color'] ) ? 'is-empty' : ''; ?>" value="<?php echo esc_attr( $settings['styles']['scrollbar_track_color'] ?: '#000000' ); ?>" data-xw-color-picker>
                                            <input type="text" name="xw_settings[styles][scrollbar_track_color]" class="xw-color-text" value="<?php echo esc_attr( $settings['styles']['scrollbar_track_color'] ); ?>" maxlength="7" spellcheck="false" placeholder="#RRGGBB" aria-label="<?php echo esc_attr( xw_t( 'Código hexadecimal del color del carril', 'Track color hexadecimal code' ) ); ?>" data-xw-color-value>
                                        </div>
                                    </div>
                                    <div class="xw-field-row">
                                        <label for="xw-scrollbar-thumb-color"><?php echo esc_html( xw_t( 'Color de la barra', 'Thumb color' ) ); ?></label>
                                        <div class="xw-color-control">
                                            <input id="xw-scrollbar-thumb-color" type="color" class="<?php echo empty( $settings['styles']['scrollbar_thumb_color'] ) ? 'is-empty' : ''; ?>" value="<?php echo esc_attr( $settings['styles']['scrollbar_thumb_color'] ?: '#000000' ); ?>" data-xw-color-picker>
                                            <input type="text" name="xw_settings[styles][scrollbar_thumb_color]" class="xw-color-text" value="<?php echo esc_attr( $settings['styles']['scrollbar_thumb_color'] ); ?>" maxlength="7" spellcheck="false" placeholder="#RRGGBB" aria-label="<?php echo esc_attr( xw_t( 'Código hexadecimal del color de la barra', 'Thumb color hexadecimal code' ) ); ?>" data-xw-color-value>
                                        </div>
                                    </div>
                                    <div class="xw-field-row">
                                        <label for="xw-scrollbar-radius"><?php echo esc_html( xw_t( 'Redondeado', 'Corner radius' ) ); ?></label>
                                        <div class="xw-number-control">
                                            <input id="xw-scrollbar-radius" type="number" name="xw_settings[styles][scrollbar_radius]" value="<?php echo esc_attr( $settings['styles']['scrollbar_radius'] ); ?>" min="0" max="50" step="1">
                                            <span>px</span>
                                        </div>
                                    </div>
                                <?php elseif ( 'back_to_top' === $key ) : ?>
                                    <fieldset class="xw-btt-icon-picker xw-field-full">
                                        <legend><?php echo esc_html( xw_t( 'Tipo de icono', 'Icon type' ) ); ?></legend>
                                        <div class="xw-btt-icon-grid">
                                            <?php foreach ( xw_get_back_to_top_icon_options() as $icon_key => $icon_label ) : ?>
                                                <label>
                                                    <input type="radio" name="xw_settings[back_to_top][icon]" value="<?php echo esc_attr( $icon_key ); ?>" <?php checked( $settings['back_to_top']['icon'], $icon_key ); ?>>
                                                    <span>
                                                        <?php echo xw_back_to_top_icon_svg( $icon_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fijo de la lista permitida. ?>
                                                        <small><?php echo esc_html( $icon_label ); ?></small>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </fieldset>

                                    <h3 class="xw-options-heading"><?php echo esc_html( xw_t( 'Posición y movimiento', 'Position and movement' ) ); ?></h3>
                                    <div class="xw-field-row">
                                        <label for="xw-btt-position"><?php echo esc_html( xw_t( 'Posición del botón', 'Button position' ) ); ?></label>
                                        <select id="xw-btt-position" name="xw_settings[back_to_top][position]">
                                            <option value="bottom-right" <?php selected( $settings['back_to_top']['position'], 'bottom-right' ); ?>><?php echo esc_html( xw_t( 'Abajo a la derecha', 'Bottom right' ) ); ?></option>
                                            <option value="bottom-left" <?php selected( $settings['back_to_top']['position'], 'bottom-left' ); ?>><?php echo esc_html( xw_t( 'Abajo a la izquierda', 'Bottom left' ) ); ?></option>
                                        </select>
                                    </div>
                                    <div class="xw-field-row">
                                        <label for="xw-btt-shape"><?php echo esc_html( xw_t( 'Forma del botón', 'Button shape' ) ); ?></label>
                                        <select id="xw-btt-shape" name="xw_settings[back_to_top][shape]">
                                            <option value="circle" <?php selected( $settings['back_to_top']['shape'], 'circle' ); ?>><?php echo esc_html( xw_t( 'Circular', 'Circle' ) ); ?></option>
                                            <option value="rounded" <?php selected( $settings['back_to_top']['shape'], 'rounded' ); ?>><?php echo esc_html( xw_t( 'Redondeado', 'Rounded' ) ); ?></option>
                                            <option value="square" <?php selected( $settings['back_to_top']['shape'], 'square' ); ?>><?php echo esc_html( xw_t( 'Cuadrado', 'Square' ) ); ?></option>
                                        </select>
                                    </div>
                                    <div class="xw-field-row xw-btt-compact-field">
                                        <label for="xw-btt-vertical-margin"><?php echo esc_html( xw_t( 'Margen vertical', 'Vertical margin' ) ); ?></label>
                                        <div class="xw-number-control">
                                            <input id="xw-btt-vertical-margin" type="number" name="xw_settings[back_to_top][vertical_margin]" value="<?php echo esc_attr( $settings['back_to_top']['vertical_margin'] ); ?>" min="0" max="200" step="1">
                                            <span>px</span>
                                        </div>
                                    </div>
                                    <div class="xw-field-row xw-btt-compact-field">
                                        <label for="xw-btt-horizontal-margin"><?php echo esc_html( xw_t( 'Margen horizontal', 'Horizontal margin' ) ); ?></label>
                                        <div class="xw-number-control">
                                            <input id="xw-btt-horizontal-margin" type="number" name="xw_settings[back_to_top][horizontal_margin]" value="<?php echo esc_attr( $settings['back_to_top']['horizontal_margin'] ); ?>" min="0" max="200" step="1">
                                            <span>px</span>
                                        </div>
                                    </div>
                                    <div class="xw-field-row xw-btt-compact-field">
                                        <label for="xw-btt-offset"><?php echo esc_html( xw_t( 'Aparecer después', 'Show after' ) ); ?></label>
                                        <div class="xw-number-control">
                                            <input id="xw-btt-offset" type="number" name="xw_settings[back_to_top][offset]" value="<?php echo esc_attr( $settings['back_to_top']['offset'] ); ?>" min="0" max="5000" step="1">
                                            <span>px</span>
                                        </div>
                                    </div>
                                    <div class="xw-field-row xw-btt-compact-field">
                                        <label for="xw-btt-duration"><?php echo esc_html( xw_t( 'Duración', 'Duration' ) ); ?></label>
                                        <div class="xw-number-control">
                                            <input id="xw-btt-duration" type="number" name="xw_settings[back_to_top][duration]" value="<?php echo esc_attr( $settings['back_to_top']['duration'] ); ?>" min="0" max="3000" step="50">
                                            <span>ms</span>
                                        </div>
                                    </div>

                                    <div class="xw-field-row xw-btt-compact-field">
                                        <label for="xw-btt-button-size"><?php echo esc_html( xw_t( 'Botón', 'Button' ) ); ?></label>
                                        <div class="xw-number-control">
                                            <input id="xw-btt-button-size" type="number" name="xw_settings[back_to_top][button_size]" value="<?php echo esc_attr( $settings['back_to_top']['button_size'] ); ?>" min="32" max="100" step="1">
                                            <span>px</span>
                                        </div>
                                    </div>
                                    <div class="xw-field-row xw-btt-compact-field">
                                        <label for="xw-btt-icon-size"><?php echo esc_html( xw_t( 'Icono', 'Icon' ) ); ?></label>
                                        <div class="xw-number-control">
                                            <input id="xw-btt-icon-size" type="number" name="xw_settings[back_to_top][icon_size]" value="<?php echo esc_attr( $settings['back_to_top']['icon_size'] ); ?>" min="12" max="92" step="1">
                                            <span>px</span>
                                        </div>
                                    </div>
                                    <div class="xw-field-row xw-btt-compact-field">
                                        <label for="xw-btt-border-size"><?php echo esc_html( xw_t( 'Borde', 'Border' ) ); ?></label>
                                        <div class="xw-number-control">
                                            <input id="xw-btt-border-size" type="number" name="xw_settings[back_to_top][border_size]" value="<?php echo esc_attr( $settings['back_to_top']['border_size'] ); ?>" min="0" max="10" step="1">
                                            <span>px</span>
                                        </div>
                                    </div>
                                    <div class="xw-field-row xw-btt-compact-field">
                                        <label for="xw-btt-progress-size"><?php echo esc_html( xw_t( 'Progreso', 'Progress' ) ); ?></label>
                                        <div class="xw-number-control">
                                            <input id="xw-btt-progress-size" type="number" name="xw_settings[back_to_top][progress_size]" value="<?php echo esc_attr( $settings['back_to_top']['progress_size'] ); ?>" min="1" max="10" step="1">
                                            <span>px</span>
                                        </div>
                                    </div>

                                    <?php
                                    $back_to_top_colors = array(
                                        'background_color' => array( xw_t( 'Color de fondo', 'Background color' ), '#FFFFFF' ),
                                        'border_color'     => array( xw_t( 'Color del borde', 'Border color' ), '#DCDCDE' ),
                                        'icon_color'       => array( xw_t( 'Color del icono', 'Icon color' ), '#1D2327' ),
                                        'progress_color'   => array( xw_t( 'Color del progreso', 'Progress color' ), '#3858E9' ),
                                    );
                                    ?>
                                    <?php foreach ( $back_to_top_colors as $color_key => $color_data ) : ?>
                                        <div class="xw-field-row">
                                            <label for="xw-btt-<?php echo esc_attr( str_replace( '_', '-', $color_key ) ); ?>"><?php echo esc_html( $color_data[0] ); ?></label>
                                            <div class="xw-color-control">
                                                <input id="xw-btt-<?php echo esc_attr( str_replace( '_', '-', $color_key ) ); ?>" type="color" class="<?php echo empty( $settings['back_to_top'][ $color_key ] ) ? 'is-empty' : ''; ?>" value="<?php echo esc_attr( $settings['back_to_top'][ $color_key ] ?: $color_data[1] ); ?>" data-xw-color-picker>
                                                <input type="text" name="xw_settings[back_to_top][<?php echo esc_attr( $color_key ); ?>]" class="xw-color-text" value="<?php echo esc_attr( $settings['back_to_top'][ $color_key ] ); ?>" maxlength="7" spellcheck="false" placeholder="#RRGGBB" aria-label="<?php echo esc_attr( $color_data[0] ); ?>" data-xw-color-value>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <fieldset class="xw-btt-visibility xw-field-full">
                                        <legend><?php echo esc_html( xw_t( 'Ocultar visibilidad por dispositivo', 'Hide visibility by device' ) ); ?></legend>
                                        <div class="xw-btt-visibility-grid">
                                            <label class="xw-check-control">
                                                <input type="checkbox" name="xw_settings[back_to_top][hide_mobile]" value="1" <?php checked( ! empty( $settings['back_to_top']['hide_mobile'] ) ); ?>>
                                                <span><?php echo esc_html( xw_t( 'Móvil', 'Mobile' ) ); ?></span>
                                            </label>
                                            <label class="xw-check-control">
                                                <input type="checkbox" name="xw_settings[back_to_top][hide_tablet]" value="1" <?php checked( ! empty( $settings['back_to_top']['hide_tablet'] ) ); ?>>
                                                <span>Tablet</span>
                                            </label>
                                            <label class="xw-check-control">
                                                <input type="checkbox" name="xw_settings[back_to_top][hide_desktop]" value="1" <?php checked( ! empty( $settings['back_to_top']['hide_desktop'] ) ); ?>>
                                                <span><?php echo esc_html( xw_t( 'Escritorio', 'Desktop' ) ); ?></span>
                                            </label>
                                        </div>
                                    </fieldset>
                                <?php elseif ( 'admin_branding' === $key ) : ?>
                                    <div class="xw-field-row xw-field-full xw-footer-editor">
                                        <label for="xw-admin-footer-html"><?php echo esc_html( xw_t( 'Texto del pie', 'Footer text' ) ); ?></label>
                                        <?php
                                        wp_editor(
                                            $settings['admin_branding']['footer_html'],
                                            'xw-admin-footer-html',
                                            array(
                                                'textarea_name' => 'xw_settings[admin_branding][footer_html]',
                                                'textarea_rows' => 5,
                                                'editor_height' => 120,
                                                'media_buttons' => false,
                                                'quicktags'     => false,
                                                'tinymce'       => array(
                                                    'menubar'  => false,
                                                    'toolbar1' => 'link',
                                                    'toolbar2' => '',
                                                    'toolbar3' => '',
                                                    'toolbar4' => '',
                                                ),
                                            )
                                        );
                                        ?>
                                        <p><?php echo esc_html( xw_t( 'Escriba el mensaje, seleccione una palabra o frase y pulse el botón de enlace.', 'Write the message, select a word or phrase, and press the link button.' ) ); ?></p>
                                    </div>
                                <?php elseif ( 'elementor_phone_mask' === $key ) : ?>
                                    <div class="xw-field-row xw-field-full">
                                        <fieldset class="xw-phone-modes">
                                            <legend><?php echo esc_html( xw_t( 'Tipo de campo telefónico', 'Phone field type' ) ); ?></legend>
                                            <label>
                                                <input type="radio" name="xw_settings[phone][mode]" value="area_code" <?php checked( $settings['phone']['mode'], 'area_code' ); ?> data-xw-phone-mode>
                                                <span>
                                                    <strong><?php echo esc_html( xw_t( 'Sin banderas', 'Without flags' ) ); ?></strong>
                                                    <small><?php echo esc_html( xw_t( 'Código de área y formato actual.', 'Area code with the current format.' ) ); ?></small>
                                                </span>
                                            </label>
                                            <label>
                                                <input type="radio" name="xw_settings[phone][mode]" value="international" <?php checked( $settings['phone']['mode'], 'international' ); ?> data-xw-phone-mode>
                                                <span>
                                                    <strong><?php echo esc_html( xw_t( 'Con bandera', 'With flag' ) ); ?></strong>
                                                    <small><?php echo esc_html( xw_t( 'Selector internacional con prefijo de país.', 'International selector with country calling code.' ) ); ?></small>
                                                </span>
                                            </label>
                                        </fieldset>
                                    </div>
                                    <div class="xw-phone-international xw-field-full" data-xw-phone-international<?php echo 'international' === $settings['phone']['mode'] ? '' : ' hidden'; ?>>
                                        <div class="xw-field-row xw-field-full">
                                            <label for="xw-phone-language"><?php echo esc_html( xw_t( 'Idioma predeterminado de países y buscador', 'Default language for countries and search' ) ); ?></label>
                                            <select id="xw-phone-language" name="xw_settings[phone][language]" data-xw-country-language>
                                                <option value="es" <?php selected( $settings['phone']['language'], 'es' ); ?>><?php echo esc_html( xw_t( 'Español', 'Spanish' ) ); ?></option>
                                                <option value="en" <?php selected( $settings['phone']['language'], 'en' ); ?>>English</option>
                                            </select>
                                            <p><?php echo esc_html( xw_t( 'Se usa como respaldo. Si la página declara español o inglés, el selector adopta automáticamente ese idioma.', 'Used as a fallback. If the page declares Spanish or English, the selector automatically adopts that language.' ) ); ?></p>
                                        </div>
                                        <div class="xw-primary-countries">
                                            <div class="xw-field-row">
                                                <label for="xw-primary-country-1"><?php echo esc_html( xw_t( 'País principal 1', 'Primary country 1' ) ); ?></label>
                                                <select id="xw-primary-country-1" name="xw_settings[phone][primary_countries][]" data-xw-primary-country data-selected="<?php echo esc_attr( $settings['phone']['primary_countries'][0] ?? '' ); ?>"></select>
                                            </div>
                                            <div class="xw-field-row">
                                                <label for="xw-primary-country-2"><?php echo esc_html( xw_t( 'País principal 2', 'Primary country 2' ) ); ?></label>
                                                <select id="xw-primary-country-2" name="xw_settings[phone][primary_countries][]" data-xw-primary-country data-selected="<?php echo esc_attr( $settings['phone']['primary_countries'][1] ?? '' ); ?>"></select>
                                            </div>
                                        </div>
                                        <div class="xw-country-selector-header">
                                            <div>
                                                <strong><?php echo esc_html( xw_t( 'Países disponibles', 'Available countries' ) ); ?></strong>
                                                <p><?php echo esc_html( xw_t( 'Los códigos seleccionados aparecerán en el selector del formulario.', 'The selected codes will appear in the form selector.' ) ); ?></p>
                                            </div>
                                            <label class="xw-select-all-countries">
                                                <input type="checkbox" data-xw-select-all-countries>
                                                <span><?php echo esc_html( xw_t( 'Seleccionar todos', 'Select all' ) ); ?></span>
                                            </label>
                                        </div>
                                        <div class="xw-country-code-grid" data-xw-country-grid></div>
                                    </div>
                                <?php elseif ( 'woocommerce_auto_cart' === $key ) : ?>
                                    <div class="xw-field-row xw-field-full">
                                        <label for="xw-cart-update-delay"><?php echo esc_html( xw_t( 'Espera antes de actualizar', 'Delay before updating' ) ); ?></label>
                                        <div class="xw-number-control">
                                            <input id="xw-cart-update-delay" type="number" name="xw_settings[woocommerce][cart_update_delay]" value="<?php echo esc_attr( $settings['woocommerce']['cart_update_delay'] ); ?>" min="0" max="30" step="0.1" inputmode="decimal">
                                            <span><?php echo esc_html( xw_t( 'segundos', 'seconds' ) ); ?></span>
                                        </div>
                                        <p><?php echo esc_html( xw_t( 'La espera comienza después del último cambio de cantidad. Use 0 para actualizar inmediatamente.', 'The delay starts after the last quantity change. Use 0 to update immediately.' ) ); ?></p>
                                    </div>
                                <?php elseif ( 'login_customization' === $key ) : ?>
                                <div class="xw-field-row">
                                    <label for="xw-primary-color"><?php echo esc_html( xw_t( 'Color principal', 'Primary color' ) ); ?></label>
                                    <div class="xw-color-control">
                                        <input id="xw-primary-color" type="color" class="<?php echo empty( $settings['login']['primary_color'] ) ? 'is-empty' : ''; ?>" value="<?php echo esc_attr( $settings['login']['primary_color'] ?: '#000000' ); ?>" data-xw-color-picker>
                                        <input type="text" name="xw_settings[login][primary_color]" class="xw-color-text" value="<?php echo esc_attr( $settings['login']['primary_color'] ); ?>" maxlength="7" spellcheck="false" placeholder="#RRGGBB" aria-label="<?php echo esc_attr( xw_t( 'Código hexadecimal del color principal', 'Primary color hexadecimal code' ) ); ?>" data-xw-color-value>
                                    </div>
                                    <p><?php echo esc_html( xw_t( 'Se usa en el botón, el foco de los campos y los controles seleccionados.', 'Used for the button, field focus, and selected controls.' ) ); ?></p>
                                </div>
                                <div class="xw-field-row">
                                    <label for="xw-button-text-color"><?php echo esc_html( xw_t( 'Color del texto del botón', 'Button text color' ) ); ?></label>
                                    <div class="xw-color-control">
                                        <input id="xw-button-text-color" type="color" class="<?php echo empty( $settings['login']['button_text_color'] ) ? 'is-empty' : ''; ?>" value="<?php echo esc_attr( $settings['login']['button_text_color'] ?: '#000000' ); ?>" data-xw-color-picker>
                                        <input type="text" name="xw_settings[login][button_text_color]" class="xw-color-text" value="<?php echo esc_attr( $settings['login']['button_text_color'] ); ?>" maxlength="7" spellcheck="false" placeholder="#RRGGBB" aria-label="<?php echo esc_attr( xw_t( 'Código hexadecimal del texto del botón', 'Button text color hexadecimal code' ) ); ?>" data-xw-color-value>
                                    </div>
                                    <p><?php echo esc_html( xw_t( 'Se aplica al texto “Log In” del botón de acceso.', 'Applied to the “Log In” button text.' ) ); ?></p>
                                </div>
                                <div class="xw-field-row xw-logo-field">
                                    <label for="xw-login-logo"><?php echo esc_html( xw_t( 'Logotipo', 'Logo' ) ); ?></label>
                                    <div class="xw-media-control">
                                        <input id="xw-login-logo" type="url" name="xw_settings[login][logo_url]" value="<?php echo esc_attr( $settings['login']['logo_url'] ); ?>" placeholder="https://...">
                                        <button type="button" class="button" data-xw-media><?php echo esc_html( xw_t( 'Elegir imagen', 'Choose image' ) ); ?></button>
                                        <button type="button" class="button xw-remove-image" data-xw-media-remove aria-label="<?php echo esc_attr( xw_t( 'Quitar logotipo', 'Remove logo' ) ); ?>" title="<?php echo esc_attr( xw_t( 'Quitar logotipo', 'Remove logo' ) ); ?>">
                                            <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                        </button>
                                    </div>
                                    <div class="xw-logo-preview" data-xw-logo-preview<?php echo empty( $settings['login']['logo_url'] ) ? ' hidden' : ''; ?>>
                                        <img src="<?php echo esc_url( $settings['login']['logo_url'] ); ?>" alt="<?php echo esc_attr( xw_t( 'Vista previa del logotipo', 'Logo preview' ) ); ?>">
                                    </div>
                                </div>
                                <div class="xw-field-row">
                                    <label for="xw-logo-width"><?php echo esc_html( xw_t( 'Ancho del logotipo', 'Logo width' ) ); ?></label>
                                    <div class="xw-number-control">
                                        <input id="xw-logo-width" type="number" name="xw_settings[login][logo_width]" value="<?php echo esc_attr( $settings['login']['logo_width'] ); ?>" min="50" max="600" step="1">
                                        <span>px</span>
                                    </div>
                                    <p><?php echo esc_html( xw_t( 'Puede ajustarse entre 50 y 600 píxeles.', 'It can be adjusted between 50 and 600 pixels.' ) ); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>

            <div class="xw-save-bar">
                <p><?php echo esc_html( xw_t( 'Los cambios se aplican al guardar.', 'Changes are applied when saved.' ) ); ?></p>
                <?php submit_button( xw_t( 'Guardar cambios', 'Save changes' ), 'primary', 'submit', false ); ?>
            </div>
        </form>
    </div>
    <?php
}
