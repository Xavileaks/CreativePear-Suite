# Historial de cambios

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
