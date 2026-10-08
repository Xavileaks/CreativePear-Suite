# Historial de cambios

## 1.0.35

- Añade Wishlist, apagado por defecto, en la sección Elementor de Creative Pear Suite.
- Registra tres widgets nativos y editables: tabla, icono con contador y añadir a Wishlist para loops/producto único.
- Incluye listas independientes de visitantes/cuentas, sincronización de widgets, carrito validado y enlaces compartidos de solo lectura.
- Conserva las dos columnas de cajas en escritorio y una columna en tablet/móvil; no modifica plantillas ni listas de otros plugins.
- Corrige la calidad WebP para aplicar Low (50), Medium (60), High (75) y Extra High (90) durante la conversión y aislar los filtros de otras operaciones.

## 1.0.34

- Mantiene todas las cajas, incluida WebP - Compress, en dos columnas en escritorio y a ancho completo en tablet y móvil.
- Adapta los controles WebP al ancho de su caja y guarda esta preferencia para futuras funciones.

## 1.0.33

- Añade el módulo opcional “WebP - Compress” para convertir y comprimir imágenes nuevas exclusivamente a WebP, sin AVIF ni servicios externos.
- Incorpora cuatro niveles de calidad, opción de conservar originales, omisión de WebP existentes, redimensionado por ancho máximo y limpieza de nombres de archivo con vista previa.
- Cuando se conservan originales, crea copias WebP para cada tamaño y las entrega en el frontend; también elimina esas copias al borrar el adjunto.
- Detecta soporte WebP del servidor y pausa el módulo si QuickWebP continúa activo para evitar un procesamiento duplicado.

## 1.0.32

- Evita que Variation Swatches se renderice incorrectamente dentro del editor de Elementor; allí permanecen los selectores nativos de WooCommerce y el frontend conserva los swatches configurados.

## 1.0.31

- Añade un nombre personalizado de hasta 60 caracteres para mostrar el cargo adicional en el frontend y en el resumen del pedido.

## 1.0.30

- Corrige los swatches de imagen en productos con muchas combinaciones de variaciones.
- Normaliza los valores de atributos locales para asociar correctamente imágenes aunque WooCommerce cambie mayúsculas, espacios o caracteres especiales.

## 1.0.29

- Finaliza Variation Swatches para producto individual con presentación compacta compatible con Elementor.
- Añade tooltips configurables como texto, imagen o texto e imagen, con detección automática de imágenes de variaciones locales.
- Permite alinear y ajustar el padding de las etiquetas de atributos sin alterar la alineación de los swatches.
- Añade colores normal y hover configurables para el enlace “Clear / Limpiar”.

## 1.0.28

- Nueva función “Variation Swatches” enfocada exclusivamente en la página de producto individual.
- Convierte atributos en etiquetas, colores o imágenes manteniendo el selector nativo de WooCommerce para precio, stock, galería y compra.
- Añade seis pestañas de configuración: General, Diseño, Etiquetas, Colores, Imágenes y Tooltip.
- Incorpora tipos de atributo propios y campos para asignar colores, etiquetas o imágenes desde Productos → Atributos.
- Reutiliza los metadatos de color e imagen del plugin gratuito de referencia para facilitar la migración.
- Evita ejecutar dos interfaces de swatches a la vez si el plugin de referencia continúa activo.

## 1.0.27

- Nueva función independiente “Botón flotante de WhatsApp”, desactivada por defecto.
- Permite indicar el número internacional y configurar posición, forma, márgenes, aparición, duración, tamaños, borde, colores y visibilidad por dispositivo.
- Abre la conversación mediante el enlace oficial `wa.me`, omite los controles de progreso y evita superponerse con “Volver arriba” cuando ambos botones comparten el mismo lado.

## 1.0.26

- “WooCommerce Shipping” vuelve al ancho normal de las demás funciones y deja de ocupar toda la fila.

## 1.0.25

- “WooCommerce Shipping” incorpora el submódulo opcional “Extra Fees”, que se activa por separado dentro de sus ajustes.
- Permite elegir entre importe fijo o porcentaje y añadir, eliminar y ordenar automáticamente hasta 50 rangos.
- El porcentaje se calcula únicamente sobre el subtotal; se aplica un solo rango y el cargo se recalcula con WooCommerce.
- La fila “Extra Fees” aparece antes de Total y copia la tipografía, color, alineación, espaciado y bordes calculados de Total, incluidos los cambios de Elementor y del checkout AJAX.

## 1.0.24

- Nueva opción independiente “WooCommerce Shipping”.
- Ordena las tarifas de cualquier proveedor de menor a mayor y selecciona la más económica en cada nueva carga del checkout.
- Conserva la elección manual del cliente durante las actualizaciones AJAX.
- Oculta el indicador de carga de WooCommerce que podía superponerse al encabezado fijo, sin eliminar el bloqueo temporal del checkout.

## 1.0.23

- Elementor Menu Cart: se añade espacio superior al listado para mostrar completa la insignia de cantidad del primer producto.
- El botón de eliminar queda separado 10 px del carril de desplazamiento sin modificar la distribución del producto.

## 1.0.22

- Elementor Menu Cart: la altura de línea del título responde exactamente al valor configurado en el control Typography de Elementor.
- Se elimina la caja de línea heredada del contenedor de WooCommerce sin fijar tamaños ni alturas desde el plugin.

## 1.0.21

- WooCommerce: al separar las variaciones, el título conserva su enlace original en el carrito.
- Elementor Menu Cart: la cantidad aparece como una insignia negra sobre la miniatura, igual que en el checkout.
- Elementor Menu Cart: la insignia se restaura automáticamente después de cada actualización AJAX y la cantidad original sigue disponible para lectores de pantalla.
- WooCommerce: la separación de variaciones también cubre el renderizado propio del Menu Cart de Elementor.

## 1.0.20

- WooCommerce: nueva opción “CSS del carrito” para separar todas las variaciones del nombre base en el carrito y mini carrito.
- WooCommerce: las variaciones aparecen debajo del título y se separan mediante `|`, igual que en el checkout.
- Elementor conserva el control completo sobre la tipografía y el color del título de producto del Menu Cart.

## 1.0.19

- WooCommerce: conserva en una sola actualización AJAX el envío elegido cuando EasyPost u otro proveedor refresca las tarifas disponibles.
- WooCommerce: evita que el recálculo de tarifas sustituya la selección válida por el primer método de la lista.

## 1.0.18

- WooCommerce: evita selecciones simultáneas mientras el plugin de envío calcula, sin generar peticiones AJAX adicionales desde la suite.
- WooCommerce: iguala la altura de los campos Select2 con los campos configurados desde Elementor.
- WooCommerce: en móvil coloca el encabezado de envío encima de los métodos para evitar solapamientos.
- WooCommerce: mantiene el color de los nombres de producto heredado de Elementor, sin imponer un color desde el plugin.

## 1.0.17

- WooCommerce: después de cada actualización AJAX, el radio visible se sincroniza con el método que el servidor usó para calcular el total.
- WooCommerce: los productos variables muestran el nombre base y colocan `Size`, `Color`, `Design` y demás atributos en una línea separada.

## 1.0.16

- WooCommerce: la selección del envío y el total proceden ahora de la misma respuesta AJAX, evitando que muestren métodos distintos.
- Se conserva el orden visual de las tarifas sin sobrescribir el método confirmado por WooCommerce.
- Mientras se recalcula el envío, el importe total muestra un indicador gris y el resto del checkout permanece visualmente estable.

## 1.0.15

- WooCommerce: cambiar el método de envío conserva su posición y actualiza el total por AJAX sin saltos visuales.
- WooCommerce: el método pulsado permanece seleccionado aunque el proveedor reordene las tarifas en la respuesta.

## 1.0.14

- WooCommerce: los métodos de envío quedan alineados a la derecha y el selector aparece después del texto.
- WooCommerce: subtotal, envío y total comparten el mismo borde derecho, incluso cuando el nombre del método ocupa varias líneas.
- WooCommerce: las variaciones aparecen debajo del título del producto y se separan mediante el símbolo `|`.
- WooCommerce: la distribución se restaura automáticamente después de las actualizaciones AJAX del checkout.

## 1.0.13

- WooCommerce: se amplía la columna de producto y se reduce a 10 px el espacio junto al subtotal para evitar que los importes se partan.
- WooCommerce: los selectores y métodos de envío quedan alineados hacia la derecha y conservan más espacio útil.
- WooCommerce: ciudad, estado y código postal comparten una fila en tablet y escritorio; teléfono y correo comparten otra.
- WooCommerce: el selector de país queda centrado verticalmente y la opción seleccionada mantiene texto legible al abrir la lista.

## 1.0.12

- WooCommerce: nueva opción “CSS checkout” para ampliar el espacio del producto y mantener el subtotal compacto.
- WooCommerce: la cantidad aparece como una insignia negra sobre la miniatura cuando también está activa la opción de imágenes en el checkout.
- WooCommerce: los métodos de envío usan una distribución independiente y más amplia, sin quedar comprimidos por la columna de subtotales.
- Diseño responsive verificado en escritorio y móvil.

## 1.0.11

- WooCommerce: el cambio de imagen usa ahora dos transiciones de opacidad simultáneas para crear un fundido cruzado real.
- La imagen que aparece utiliza la duración configurada y la que desaparece tarda 0,1 segundos adicionales; los tiempos se invierten al retirar el cursor.

## 1.0.10

- WooCommerce: la imagen principal del listado se oculta completamente cuando termina de aparecer la imagen secundaria.
- Al retirar el cursor, la imagen principal se restaura antes de desvanecer la secundaria para evitar espacios en blanco.

## 1.0.9

- WooCommerce: el cambio de imagen en los listados ahora utiliza un fundido cruzado real, manteniendo siempre visible la imagen principal mientras aparece la imagen de la galería.
- Se elimina el instante en blanco entre imágenes y la capa secundaria conserva el tamaño, encuadre y bordes de la imagen original.

## 1.0.8

- WooCommerce: nueva opción para mostrar la primera imagen de la galería al pasar el cursor sobre la imagen principal en los listados de productos.
- El único ajuste de esta función es la duración del fade, configurable entre 0 y 5 segundos.
- Compatible con listados clásicos de WooCommerce, Elementor Loop, bloques de productos y contenido cargado mediante AJAX.

## 1.0.7

- WooCommerce: opción para mostrar miniaturas de producto de 55 × 55 píxeles junto al nombre en el resumen del checkout.
- WooCommerce: opción para ocultar el cálculo y los costes de envío únicamente en el carrito, conservándolos en el checkout.
- WooCommerce: opción para exigir a usuarios conectados el correo de su cuenta como campo obligatorio y de solo lectura en el checkout, con validación del servidor.
- Las tres funciones nuevas están desactivadas por defecto y sus textos se adaptan al español o inglés de WordPress.

## 1.0.6

- El progreso SVG uniforme ahora se aplica a las formas circular, redondeada y cuadrada.
- Los trazados redondeado y cuadrado comienzan en el centro superior y siguen exactamente el contorno correspondiente.
- Eliminado el degradado circular anterior de las variantes redondeada y cuadrada.

## 1.0.5

- Botón «Volver arriba»: el progreso circular se dibuja con un trazo SVG centrado para mantener el mismo grosor arriba, abajo y a los lados.
- Se conserva una única sombra exterior para todo el botón y el mismo aspecto en los estados interactivos.

## 1.0.4

- Botón «Volver arriba»: el anillo de progreso y el círculo ahora forman una sola pieza visual.
- La sombra se aplica al contorno completo del botón y se elimina la sombra interior que deformaba visualmente el progreso.

## 1.0.3

- El componente queda aislado del CSS de temas, constructores y otros plugins para conservar siempre sus dimensiones y su forma.
- Botón «Volver arriba»: el indicador de progreso mantiene visible el anillo completo con el color de borde configurado.
- Eliminada la apariencia de raya flotante al comienzo del recorrido, sin introducir cambios al pasar el cursor.

## 1.0.2

- WordPress: nuevo botón «Volver arriba» con desplazamiento suave e indicador de progreso permanente.
- Personalización de icono, posición, forma, márgenes, umbral, duración, tamaños, colores y visibilidad por dispositivo.
- Ajustes compactos en dos columnas en escritorio y una columna en móvil; el botón nunca se carga en el administrador.
- El botón conserva el mismo aspecto al pasar el cursor, incluso frente a estilos globales del tema o constructor.
- Actualizador: comprobación independiente de nuevas publicaciones cada diez minutos mediante WP-Cron.

## 1.0.1

- Nombre visible corregido a Creative Pear Suite.

## 1.0.0

- Primera versión de Creative Pear Suite.
- Funciones modulares para WordPress, Elementor y WooCommerce.
