# Creative Pear Suite

Plugin de WordPress de Creative Pear Agency con funciones reutilizables y una pantalla para activar, desactivar y configurar cada módulo.

Sitio web: [creativepearagency.com](https://creativepearagency.com)

## Extra Fees: carritos digitales

En **WooCommerce Shipping > Extra Fees**, el interruptor **No cobrar si solo hay productos digitales** excluye el cargo cuando todos los productos del carrito están marcados como **Virtual**, incluidas las variaciones. Un descargable que también se envía físicamente no se excluye. Los carritos mixtos siguen las reglas habituales. El interruptor está apagado por defecto para conservar el comportamiento anterior.

El filtro `xw_should_apply_subtotal_extra_fee` recibe un booleano y el carrito antes de calcular este cargo. Un snippet externo puede devolver `false` sin eliminar otros cargos ni desactivar el módulo. La lógica específica de donaciones no está incluida en el plugin.

## Wishlist de Elementor

Módulo opcional e independiente: **Ajustes > Creative Pear Suite > Elementor > Wishlist**.
Está apagado por defecto y requiere WooCommerce y Elementor. Al encenderlo registra exactamente:

- **Wishlist — Tabla**: títulos/columnas, tipografías, alineación por columna, foto cuadrada/redondeada/circular, eliminar, disponibilidad, botones y servicios de compartir.
- **Wishlist — Contador**: icono nativo de Elementor (incluido SVG admitido por el sitio), enlace y contador real; colores, tipografía, padding y desplazamientos X/Y del número.
- **Wishlist — Añadir**: producto automático del loop/ficha o ID manual; solo icono, solo texto o ambos; estados normal, hover y añadido.

Selecciona una página publicada en los ajustes y coloca **Tabla** en ella; el contador enlaza a esa página, salvo que tenga un enlace personalizado. Los controles visuales están en cada widget. Las cajas de ajustes conservan dos columnas por encima de 1024 px y una en tablet/móvil. La tabla frontal se adapta a tarjetas en tablet/móvil.

No coloca widgets automáticamente, no cambia las plantillas existentes y no importa ni reemplaza listas de otros plugins. La lista de visitantes usa una cookie HttpOnly de un año; al iniciar sesión se fusiona con la lista de la cuenta. Guarda IDs/fechas, un identificador de propietario y, únicamente al compartir, un token aleatorio. Cada lista admite hasta 200 productos. Los enlaces compartidos son públicos de solo lectura y no incluyen datos de la cuenta. Los visitantes del enlace no pueden modificar la lista del propietario. Los datos se conservan al apagar el módulo; no se eliminan automáticamente.

Los botones de carrito admiten productos simples disponibles y respetan la validación de WooCommerce. Los variables requieren abrir la ficha y seleccionar sus opciones. En el editor/preview de Elementor se muestran productos públicos de ejemplo y no se modifican la lista ni el carrito. No se habilitan permisos extra para subir SVG.

### Pruebas locales de Wishlist

Requieren un WordPress aislado con `WP_ENVIRONMENT_TYPE=local`, WooCommerce, Elementor y esta suite activos. Nunca ejecutar el generador de fixtures contra producción: crea productos/página de prueba y activa el módulo únicamente en ese entorno.

```powershell
php tests/wishlist-integration.php C:/ruta/wordpress-local
# Sirve esa instalación en localhost antes de ejecutar las pruebas HTTP.
node tests/wishlist-http.mjs http://127.0.0.1:8097 11,12,13,14,15
```

Usa los IDs que devuelve la primera prueba, en el mismo orden. Cubre registro/controles nativos, render/contexto de producto, transacciones/rollback, límite, aislamiento de visitantes, nonces, productos privados, compartir, eliminación y carrito. La revisión visual adicional debe hacerse en el editor y en escritorio/tablet/móvil.

## Actualizaciones desde GitHub

El plugin consulta cada diez minutos la última publicación estable de este repositorio mediante WP-Cron. Si la versión publicada es mayor que la instalada, la actualización aparece en **Plugins > Plugins instalados** y puede aplicarse con **Actualizar ahora** o mediante las actualizaciones automáticas de WordPress. Como WP-Cron depende de la actividad del sitio, la comprobación se ejecuta durante la primera visita recibida después de cumplirse el intervalo.

La instalación inicial del plugin sigue haciéndose una sola vez con el ZIP. A partir de ese momento, las versiones nuevas llegan desde GitHub Releases.

## Publicar una versión

1. Cambiar `Version:` y `XW_FUNCTIONS_VERSION` en `creativepear-suite.php` al mismo número.
2. Guardar y subir los cambios a la rama `main`.
3. Crear y subir una etiqueta con esa versión, por ejemplo:

```powershell
git tag v1.0.11
git push origin main --tags
```

La acción **Publicar plugin** valida el PHP, comprueba que la etiqueta coincide con la versión, genera `creativepear-suite.zip` y crea la publicación de GitHub.

## Requisitos para el ZIP

No se debe subir a WordPress el ZIP automático de “Source code” que muestra GitHub. El actualizador utiliza el archivo `creativepear-suite.zip` adjunto a cada publicación.
