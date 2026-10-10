# Creative Pear Suite

Plugin de WordPress de Creative Pear Agency con funciones reutilizables y una pantalla para activar, desactivar y configurar cada módulo.

Sitio web: [creativepearagency.com](https://creativepearagency.com)

## Versión 1.0.45

- WooCommerce Cart CSS: imágenes cuadradas `1:1` con `object-fit: contain` en carrito y mini carrito, incluido Menu Cart de Elementor. Conserva el ancho configurado.
- Las imágenes del checkout usan el mismo encuadre, tanto con la caja «Mostrar imágenes» sola como junto a Checkout CSS, conservando sus tamaños responsive.

## Versión 1.0.44

- Wishlist: muestra las opciones debajo del nombre, con etiquetas en negrita y separadores verticales `|`, siguiendo el orden de atributos del producto.
- En **Tabla > Estilo > Product name > Variation options** solo se editan tamaño de fuente por dispositivo y color; el formato de etiquetas y valores queda fijo.
- Conserva los tamaños y colores guardados. Los valores con comas, barras o símbolos se tratan como texto, sin dividirlos ni interpretar HTML.

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

No coloca widgets automáticamente, no cambia las plantillas existentes y no importa ni reemplaza listas de otros plugins. La lista de visitantes usa una cookie HttpOnly de un año; al iniciar sesión se fusiona con la lista de la cuenta. Guarda IDs/fechas y atributos de las variaciones elegidas, un identificador de propietario y, únicamente al compartir, un token aleatorio. Cada lista admite hasta 200 productos o combinaciones. Los enlaces compartidos son públicos de solo lectura y no incluyen datos de la cuenta. Los visitantes del enlace no pueden modificar la lista del propietario. Los datos se conservan al apagar el módulo; no se eliminan automáticamente.

Los botones de carrito admiten productos simples y variaciones disponibles y respetan la validación de WooCommerce. Al pulsar **Añadir** con una variación elegida se guarda esa combinación exacta (también si WooCommerce usa atributos «Cualquiera»). La tabla muestra sus opciones, precio e imagen, y permite añadirla al carrito sin volver a elegirlas. Sin una selección completa se conserva el producto general y hay que abrir su ficha para seleccionar opciones. Las listas existentes mantienen sus productos generales; no se sustituyen automáticamente. Los botones de otros productos en loops no heredan la selección de la ficha. En el editor/preview de Elementor se muestran productos públicos de ejemplo y no se modifican la lista ni el carrito. No se habilitan permisos extra para subir SVG.

En **Tabla > Estilo > Product name > Variation options** (en español, **Nombre del producto > Opciones de la variación**) se editan únicamente el tamaño de fuente por dispositivo y el color. Las etiquetas llevan peso 600, los valores peso 400 y se separan con `|`, en el orden definido en el producto. El nombre conserva sus controles anteriores. Las opciones aparecen debajo, solo para variaciones; los productos generales no reservan espacio vacío. Cambiar estos estilos no modifica las combinaciones guardadas.

Al añadir al carrito desde la tabla (un producto, seleccionados o todos), se quitan de la wishlist únicamente los productos que WooCommerce confirmó como añadidos. El cambio se guarda para futuras visitas y actualiza tabla, contador y botones de añadir. Los productos omitidos permanecen; abrir «Seleccionar opciones» no elimina nada. Una confirmación flotante accesible, sin desplazar la tabla, se cierra a los 5 segundos o mediante la ×. Si falla el guardado de la lista, el aviso explica que el carrito ya se actualizó y no hay que añadirlo otra vez.

En **Tabla > Estilo > Tabla y tarjetas** se configura el redondeo del contorno (0 para esquinas cuadradas), además del padding y separación de las tarjetas de tablet/móvil. Hasta 1024 px, cada producto muestra foto/nombre juntos, precio/disponibilidad en dos columnas y fecha/botón debajo. Los anchos de columna del escritorio no limitan estas tarjetas; tipografía, alineación y padding siguen siendo editables por dispositivo.

El padding de contador, eliminar y botones determina su tamaño visible sin un mínimo fijo de 44 px. Los controles de tamaño mínimo son opcionales. En pantallas táctiles se amplía el área de pulsación sin agrandar el fondo visible. Tras esta revisión, la primera visita de un administrador con Wishlist activo regenera una sola vez la caché CSS/HTML de Elementor; no cambia plantillas, ajustes ni listas.

El número del contador usa un contenido cuadrado que crece con su tipografía y número de cifras. Con padding uniforme y el redondeo circular predeterminado conserva ancho y alto iguales. Padding, tipografía, borde y radio siguen siendo editables; los valores antiguos de «Minimum width» dejan de aplicarse al regenerar el CSS.

Al quitar el icono de un botón no se genera un contenedor vacío ni se reserva su separación. Al volver a elegir un icono, se conserva la separación entre icono y texto. El padding y el borde configurados siguen aplicándose en ambos casos.

En tablet y móvil (hasta 1024 px), precio/disponibilidad y fecha/botón se separan con líneas interiores en cruz, sin cuadros alrededor de las celdas. Usan el color de borde de **Filas y celdas**. Al ocultar fecha o disponibilidad se omiten los segmentos que separarían una celda ausente. Escritorio conserva su presentación.

En **Tabla > Contenido > Compartir > Servicios e iconos** están disponibles Facebook, X/Twitter, Pinterest, WhatsApp, Telegram, LinkedIn, Reddit, email y copiar enlace. Los widgets existentes conservan sus servicios e iconos guardados: añade los nuevos con **Añadir elemento**. Primero se genera el enlace público de la lista y después se abre el destino; si el navegador bloquea la nueva pestaña, se navega al servicio en la actual. La app o web seleccionada pide al usuario confirmar el envío; no se publican mensajes automáticamente. [Telegram documenta este flujo de compartir](https://core.telegram.org/widgets/share).

### Pruebas locales de Wishlist

Requieren un WordPress aislado con `WP_ENVIRONMENT_TYPE=local`, WooCommerce, Elementor y esta suite activos. Nunca ejecutar el generador de fixtures contra producción: crea productos/página de prueba y activa el módulo únicamente en ese entorno.

```powershell
php tests/wishlist-integration.php C:/ruta/wordpress-local
php tests/wishlist-cart.php C:/ruta/wordpress-local
php tests/wishlist-variations.php C:/ruta/wordpress-local
# Sirve esa instalación en localhost antes de ejecutar las pruebas HTTP.
node tests/wishlist-http.mjs http://127.0.0.1:8097 11,12,13,14,15
node tests/wishlist-sharing.mjs
node tests/wishlist-toast.mjs
node tests/wishlist-selection.mjs
# CSS real de Elementor: compact, spacious, no-image, minimal, no-icon o variations.
php tests/wishlist-style-fixture.php C:/ruta/wordpress-local compact
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
