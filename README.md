# Creative Pear Suite

Plugin de WordPress de Creative Pear Agency con funciones reutilizables y una pantalla para activar, desactivar y configurar cada módulo.

Sitio web: [creativepearagency.com](https://creativepearagency.com)


## Versión 1.0.78 — Citas: acceso, edición, mensajes y reagendado

Citas incorpora acceso por correo para cuentas existentes de cualquier rol, incluidas administradoras. Las cuentas listadas solo pueden gestionar Citas: se ocultan los menús y la barra superior, se restringen rutas, capacidades y API, y se añade Cerrar sesión/Log out según el idioma del sitio. La lista se guarda aparte; solo un administrador sin restricción puede editarla y debe quedar al menos uno fuera. No se crean usuarios ni se alteran sus roles. Desactivar Citas deja de aplicar esta restricción.

Las tarjetas muestran todos los datos ordinarios del formulario, sin desplegable, y permiten editarlos y elegir qué campo usar como nombre. Los campos técnicos de cita se cambian mediante disponibilidad. WhatsApp y Correo desaparecen cuando su dato es inválido y vuelven al corregirlo. WhatsApp valida formato internacional con metadatos locales de libphonenumber-js 1.13.15 (MIT, licencia incluida); esto no comprueba que el número tenga una cuenta de WhatsApp.

Hay mensajes editables para pendiente, confirmada, cancelada y reagendar, con saludo opcional por nombre y marcadores {fecha}, {hora}, {consultorio}, {sitio} y {campo:ID}. WhatsApp abre el texto preparado; el correo tiene un editor con asunto, mensaje y vista previa HTML. Solo se envía al pulsar Enviar correo, al destinatario guardado; los reintentos de una misma solicitud se deduplican. Cambiar estados, fechas o datos no envía avisos automáticamente. WordPress debe tener configurado el envío de correo.

Próximas citas, Listas para reagendar e Historial son apartados separados. El plazo configurable (15 días por defecto) se cuenta por días del calendario desde el cambio de estado, con filtro por fecha de corte. Se incluyen canceladas y confirmadas/completadas que ya terminaron; se excluyen pendientes, confirmadas futuras y las ya reagendadas. Las antiguas sin fecha de cambio no reciben una fecha inventada: se puede indicar la fecha conocida en Editar datos. Reagendar comprueba disponibilidad, crea una nueva cita pendiente y conserva la original enlazada en el historial. La migración añade dos columnas sin reescribir fechas originales.

Cajas de ajustes en dos columnas de escritorio y una hasta 1024 px; tarjetas de citas en tres columnas, dos en tablet y una en móvil. Verificado con WordPress local, Elementor, cuentas administradora/suscriptora restringidas, edición, conflictos, mensajes interceptados (sin entregas reales), calendario y reagendado. Publicar no instala la actualización en los sitios.

## Versión 1.0.77 — WooCommerce: Valor relativo y absoluto

Nueva caja al principio de **WooCommerce**, apagada por defecto. Actívala, guarda y busca **Valor relativo y absoluto** en Elementor, dentro de la categoría de la suite. El widget muestra el ahorro de la oferta como **porcentaje** (`Ahorra 11%`) o **importe** (`Ahorra 60€`, con el formato monetario de la tienda). Colócalo junto al widget de precio en una plantilla de producto o Loop.

En el widget puedes editar texto, color, fondo, tipografía, borde, redondeado, espacio interior y alineación; los estilos se aplican por instancia. Lee el producto actual, no cambia sus precios ni aplica descuentos. El importe es por unidad y respeta moneda e impuestos de visualización de WooCommerce. El porcentaje entero se calcula a la baja para no exagerar el ahorro; los inferiores al 1% se muestran como `< 1%`.

Sin oferta activa, con precios inválidos o fuera de las fechas de oferta, se oculta. En productos variables espera a la selección y usa el ahorro de **esa variación**, sin mezclar precios mínimos/máximos de variantes distintas; se oculta al reiniciar o elegir una variante sin oferta. Los eventos de WooCommerce actualizan cada instancia y conservan separados otros Loops. No necesita Elementor Pro para registrar el widget, aunque el editor de plantillas/Loops depende de las funciones disponibles en tu Elementor.

Activos cargados por el widget, independientes de Estilos CSS globales. Sin WooCommerce o Elementor no se registra; no se añade una tabla ni se guardan copias de precios. Diseño basado en la etiqueta de la referencia y convertido con 12ui. Probado localmente con WooCommerce 11.2.1, Elementor 4.3.4 y sus controles nativos; cálculo, estados, exclusiones, estilos por instancia y vista móvil. Publicar no lo instala ni activa automáticamente en los sitios.

## Versión 1.0.76 — Cantidad: respetar la altura configurada

El input del módulo Quantity Plus Minus Button fija `height`, `min-height` y `max-height` al valor de su ajuste. Así los límites de altura del tema no encogen ni agrandan el campo. Se limita al control mejorado `.quantity.xw-quantity` y al selector nativo de bloques WooCommerce que ya estiliza el módulo; no afecta otros inputs ni los controles de otros plugins que el módulo omite. Los botones circulares mantienen su diámetro independiente. La carga sigue dependiendo del interruptor existente.

## Versión 1.0.75 — Estilos globales: evitar el doble scroll

Se elimina el ancho forzado y `overflow-x: hidden !important` sobre `html` y `body`. El recorte horizontal usa `overflow-x: clip`, sin `!important`, y únicamente si el navegador lo admite. No convierte `body` en un contenedor adicional de scroll vertical ni impone anchos, alturas o `overflow-y`. Se aplica en ambos elementos para evitar también la ampliación del viewport móvil cuando un hijo sobresale horizontalmente. En navegadores sin `clip` se mantiene el comportamiento del tema. Sigue dependiendo de la caja Estilos CSS globales.

## Versión 1.0.74 — Estilos globales: respetar el ancho de Elementor

Se retiran las reglas que imponían `--container-max-width: 90%` entre 1025 y 1400 px y `70%` desde 1700 px. Cada sitio conserva el ancho de sus contenedores configurado en Elementor.

## Versión 1.0.73 — Elementor: ancho del mensaje de confirmación

El campo Mensaje de confirmación ocupa todo el ancho interior de su caja, también en móvil, y permite ajustar su altura. Se mantienen las dos columnas de funciones en escritorio.

## Versión 1.0.72 — Elementor: iconos verdes en los correos

Los iconos de mensaje y recepción usan verde con fondo verde suave. La URL incluye la versión del plugin para renovar la imagen almacenada en caché.

## Versión 1.0.71 — Elementor: estilos de correo para administrador y cliente

Dos cajas nuevas al principio de **Elementor**, apagadas por defecto e independientes:

- **Correo del administrador**: da estilo a la acción Email existente. Nombre/logo del sitio, aviso de nuevo mensaje, campos ordenados y metadatos seleccionados. Omite campos vacíos y controles de seguridad como CAPTCHA/contraseñas; conserva campos ordinarios, incluido el valor 0, y el manejo nativo de adjuntos.
- **Correo del cliente**: da estilo únicamente a **Email 2 ya configurado**. Confirmación breve con icono, sin repetir los datos enviados. En su caja puedes cambiar el texto; vacío usa el mensaje genérico en español/inglés según el idioma de WordPress. Configura en Elementor el destinatario de Email 2, por ejemplo `[field id="email"]`; la suite no añade envíos ni cambia destinatarios, remitentes, copias o adjuntos.

Se aplican solo si **Mensaje** está vacío o es exactamente `[all-fields]`, y **Enviar como** es HTML. Si el contenido tiene texto, HTML, códigos de campo personalizados o contenido dinámico, se respeta el correo de Elementor. El modo texto plano también se conserva. Los metadatos se eligen en cada acción y desaparecen cuando no están activados. Los asuntos genéricos se adaptan al idioma de WordPress; los asuntos personalizados, las etiquetas y los valores del formulario se mantienen.

No requiere Global CSS ni guarda copias de los correos. Transporte y errores siguen siendo de Elementor/WordPress. HTML con tablas, estilos en línea, fuentes del sistema e iconos PNG; vista previa de navegador, sin afirmar entrega comprobada en cada cliente de correo. Diseño revisado con 12ui y pruebas locales con todas las llamadas a `wp_mail` interceptadas. Dos columnas de cajas en escritorio, una en tablet/móvil. No instala ni activa la utilidad en sitios automáticamente.

## Versión 1.0.70 — Fecha: flechas alineadas con mes y año

- La fila de mes/año se convierte en la referencia de posición de sus flechas, evitando que aparezcan sobre el título superior. Ambas quedan centradas verticalmente, con área de 44 px, también en móvil. Conserva la ocultación al alcanzar los límites mínimo/máximo y la navegación existente.

## Versión 1.0.69 — Phone mask: color del campo de Elementor

- El prefijo internacional y la flecha del selector con bandera usan el color calculado del input, es decir, **Field → Text Color** de ese formulario. No toman el color del contenedor ni el gris fijo del icono; cada campo mantiene su propio color.
- Sigue los cambios de estilos y clases del editor, hojas de estilo, foco y tamaño de pantalla, agrupando las actualizaciones por frame. Mantiene países, banderas, formato, validación y la paleta del desplegable. Integrado en Phone mask, sin opciones adicionales.

## Versión 1.0.68 — Fecha: selector conjunto de mes y año

- El diseño aprobado con 12ui para Cita se aplica también al campo Date dentro del interruptor **Form Elementor: fecha**, sin nuevas opciones. Encabezado de mes y año juntos; dos ruedas desplazables sin barras visibles; × para volver al calendario del período centrado. Acento del color principal de Elementor con franja al 10 % y ancho alineado con el input en móvil/tablet.
- Usa el Flatpickr de Elementor también para las fechas nativas/móviles, mediante un control auxiliar sin nombre: conserva el input original, su validación y un único valor ISO enviado. No requiere activar Citas ni Global CSS; el cargador global excluye ahora el CSS de Fecha para respetar su interruptor.
- Mantiene escritura numérica, máscara y validación por dígitos, icono permanente, límites mínimo/máximo, paso entre fechas, fechas desactivadas, restablecimiento y campos dinámicos. El selector de Fecha usa los límites de ese campo, permite años 1000–3999 si no hay otros límites y no usa la disponibilidad de Citas. Limpia los calendarios auxiliares al reconstruir el formulario.

## Versión 1.0.67 — Citas: selector conjunto de mes y año

- Encabezado único «Octubre 2026» en lugar del mes desplegable y el año numérico separados. Abre una vista con dos ruedas desplazables de meses y años, selección central y un único botón ×. Sin días ni barras de scroll en esa vista; admite gesto táctil, rueda de ratón, clic y teclado.
- La × lleva al calendario del período centrado, listo para elegir día y después hora. Enter/Escape también vuelven al calendario. Solo ofrece meses/años dentro del rango de reserva del sitio, adapta los meses al cambiar de año y conserva una cita ya elegida mientras navegas. Texto en español/inglés y ancho móvil alineado con el input.
- Diseño revisado con 12ui e integrado en el campo Cita existente, sin opciones adicionales ni cambios en los campos Date ordinarios.

## Versión 1.0.66 — Citas: desplegable alineado en móvil

- En móvil y tablet (hasta 1024 px), el calendario y las horas toman el ancho real del input de Cita. Se mide antes de que Flatpickr calcule su posición para mantener ambos bordes alineados, sin desplazarse a la derecha.
- Recalcula el ancho al abrir, redimensionar o cambiar el tamaño del campo. Incluye el borde en el ancho y desconecta la observación al retirar el formulario. Conserva el ancho de escritorio y la selección de día y hora existente.

## Versión 1.0.65 — Fecha con formato persistente y foco de select

- Global CSS styles elimina también la sombra de foco del `select` dentro de `.elementor-select-wrapper`: Elementor sitúa la clase `.elementor-field` en el contenedor, no en el select. Solo cambia `box-shadow`, sin quitar bordes ni añadir opciones.
- Form Elementor: fecha cubre ahora tanto fechas nativas/móviles como el campo Date de Flatpickr en escritorio. Mientras escribes conserva la guía restante (`01/mm/aaaa`), inserta las barras y limita a ocho dígitos. Solo entrega al calendario fechas completas y válidas: ya no interpreta texto parcial como un año. Mantiene validación, límites, campos dinámicos, restablecimiento y un único valor original enviado.
- Impide insertar dígitos inválidos: día 01–31 (primero 0–3), mes 01–12 (primero 0–1) y año 1000–3999 (primero 1–3). Rechaza también días imposibles para el mes y años no bisiestos; conserva el texto anterior, marca el campo y muestra el aviso del navegador. Borrar y corregir sigue siendo posible. Los límites configurados del calendario se mantienen.
- Icono de calendario siempre visible, igual al del campo Cita, con botón accesible de 44 px. Abre el calendario nativo o Flatpickr existente; no lo sustituye. Escritura numérica al tocar el texto y calendario al pulsar el icono. No modifica los campos Cita ni formularios ajenos a Elementor. Depende del interruptor Form Elementor: fecha, independiente de Global CSS styles.

## Versión 1.0.64 — Tarjetas de citas, contacto y cambio de fecha

- Reservas en tarjetas revisadas con 12ui: tres por fila en escritorio, dos en tablet y una en móvil. Nombre, consultorio, fecha/hora, zona, estado y datos originales. Pendientes primero, confirmadas después, luego canceladas y completadas al final. Conserva filtros, paginación y guardado de estados.
- WhatsApp y correo abren sus enlaces, sin enviar mensajes automáticamente. Código de país configurable por sitio para teléfonos locales, sin asumir un valor. Los números internacionales con `+` o `00` funcionan sin ese ajuste. Contactos sin datos válidos quedan desactivados. Consultorio detectado por etiqueta o por ID explícito en el campo Cita de Elementor para nuevos envíos; si falta, «No indicado».
- Cambiar fecha usa el mismo desplegable de día y hora, solo para pendientes o confirmadas. La consulta privada requiere administrador y nonce, excluye la propia reserva y comprueba de nuevo disponibilidad bajo el bloqueo de reservas al guardar. Conserva estado y datos originales, libera el horario anterior y no envía avisos.
- «Minutos» y «días en adelante» debajo de sus inputs. Calendario de días libres al ancho interior, siete columnas y círculos de hasta 44 px para reducir su altura. Selector de color, texto y restablecer comparten 44 px de altura. Las cajas de ajustes permanecen en dos columnas de escritorio y una en tablet/móvil.

## Versión 1.0.63 — Distribución de Citas y duración vigente

- Reservas, filtros y tabla debajo de la introducción, antes de la guía y los ajustes. Las cajas siguen en dos columnas en escritorio y una en tablet/móvil.
- Tres reglas en una fila de tres columnas y apiladas en móvil; controles de turnos, eliminar y añadir con altura común de **44 px**, con alineación revisada usando 12ui. Calendario de días libres al ancho completo interior de su caja, sin recorte en tablet.
- Cambiar la duración recalcula la ocupación de citas pendientes, confirmadas y temporales activas: una reserva de las 10:00 creada con 30 minutos bloqueará hasta las 11:00 al cambiar a 60. Respeta también reducciones y duraciones mayores sin reescribir inicios ni registros históricos. Completadas mantienen su duración registrada; canceladas no bloquean.
- Disponibilidad pública y validación final coherentes, incluidas extensiones desde el día/mes anterior. No cancela ni desplaza citas anteriores: revisa solapamientos al ampliar la duración. Pruebas locales de distribución y duración, excluidas del ZIP. No instala actualizaciones en los sitios.

## Versión 1.0.62 — Calendario y hora en un solo desplegable

- Cita muestra un solo campo y desplegable: calendario → horas disponibles, con desvanecimiento y botón para volver. Fecha y hora juntas en el campo; instrucciones y zona horaria dentro del desplegable, sin selector de hora externo.
- Inicios cada **15 minutos**, respetando duración, descansos y reservas. Pendientes, confirmadas y reservas temporales activas bloquean los intervalos que ocupen y sus solapamientos; comprobación final en servidor.
- Desplegables semanales cada 15 minutos, un turno inicial y hasta **cuatro turnos por día**. Días vacíos cerrados, validación de turnos incompletos/solapados y conservación de horarios anteriores sin redondearlos.
- Calendario de días libres: desde hoy seleccionables en rojo, pasados desactivados; excepciones sobre la semana sin cancelar citas existentes. Colores configurables de círculo, background y número de fecha disponible, y background del calendario, con vista previa y restablecimiento.
- Dos columnas en escritorio, una en tablet/móvil y guardado AJAX con botón gris sin cambios. No instala en sitios ni modifica formularios publicados; elimina el Date antiguo en Elementor si lo tienes además del campo Cita.

## Versión 1.0.61 — Form Elementor: citas

- Nueva caja **Form Elementor: citas / appointments**, apagada por defecto. Menú **Citas / Appointments** debajo de Comentarios, solo para administradores. Una agenda por sitio, sin dominio, página ni ID fijo.
- En **Citas** define horarios por día (`09:00-13:00, 15:00-18:00`; vacío = cerrado), duración de 5–240 min, antelación de 0–10080 min, hasta 1–180 días de reserva y excepciones `AAAA-MM-DD`, una por línea. Inicialmente todos los días están cerrados. Se usa la zona horaria de WordPress, no la del navegador.
- En el widget Formulario añade **un** campo **Cita / Appointment**. Requiere Elementor Pro Forms o una implementación compatible. Solo esos formularios registran reservas; los Date normales y la opción de fecha numérica no cambian. Marca los campos de contacto como obligatorios en el formulario. Detecta nombre, email y teléfono automáticamente o mediante IDs opcionales del campo Cita.
- Reutiliza el Flatpickr incluido en Elementor en escritorio y móvil: tocar campo o icono abre el calendario, sin escritura manual ni sustitución HTML5 nativa. Días con huecos rodeados por círculos, pasados/bloqueados/sin huecos en gris, y selección de hora disponible. Compatible con contenido dinámico y limpieza de controles retirados.
- El envío AJAX correcto crea una solicitud **pendiente**. Tabla privada con datos ordinarios del formulario (no contraseñas, uploads, ocultos ni CAPTCHA), búsqueda, filtros, páginas y estados pendiente/confirmada/completada/cancelada. Cancelar libera el horario; no permite reactivar una cancelada sin comprobar una nueva reserva. Cambiar horarios no cancela citas existentes.
- Validación de fecha/hora en servidor, antelación, descansos, UTC/zona original y DST; mutex atómico para evitar dobles reservas y solapamientos incluso al cambiar duración. Reserva temporal durante las acciones de Elementor, que se libera si fallan o se interrumpe la petición y caduca a los 10 min. Límite complementario de 30 intentos por IP anonimizada cada 10 min; configura CAPTCHA/honeypot y consentimiento en Elementor.
- El endpoint público solo muestra disponibilidad, nunca datos de personas. Gestión con `manage_options`, nonce, consultas preparadas y salida escapada. Exportación y anonimización por email mediante privacidad de WordPress, también apagado. No cifra la base de datos ni sustituye políticas de conservación/copias/seguridad; conserva horarios anónimos y no cancela citas al borrar datos personales.
- Guardado AJAX sin recargar ni perder posición; gris sin cambios y azul con cambios. Dos cajas por fila >1024 px y una en tablet/móvil. No introduce notificaciones al cambiar estados, pagos, calendarios externos ni múltiples profesionales: los correos son las acciones que configures en Elementor.
- Pruebas aisladas en `tests/appointments.php`, concurrencia real con dos procesos y fixtures con Elementor Forms real; excluidos del ZIP. Publicar no instala ni configura producción automáticamente.

## Versión 1.0.60

- Nueva caja **Elementor > Form Elementor: fecha**, apagada por defecto y sin controles adicionales. General para todos los formularios Elementor, sin dominio/página/ID fijo. Escritura `dd/mm/aaaa`, separadores automáticos e `inputmode="numeric"`; mantiene el input date, el icono y el selector nativo original.
- Conserva una única fecha ISO enviada (`aaaa-mm-dd`), también en el modo nativo móvil de Flatpickr. No convierte Flatpickr de escritorio ni otros calendarios personalizados, hora/datetime o campos de otros formularios. Respeta obligatoriedad, límites, paso, desactivados y solo lectura; rechaza fechas parciales/imposibles.
- Sincroniza calendario/reset, campos dinámicos y reinicios sin duplicar controles. Calendario accesible, subrayado de foco sin añadir sombra, independiente de Global CSS y guardado AJAX habitual. Dos cajas por fila en escritorio y una en tablet/móvil. Apagar vuelve al comportamiento original tras cargar de nuevo la página.
- Pruebas locales sin envíos con Flatpickr 4.6.13 y serialización verificada; confirmar el teclado del sistema en un teléfono real. No instala actualizaciones en sitios.

## Versión 1.0.59

- **Global CSS styles** elimina el `box-shadow` de foco de los campos de Elementor Form, sin crear otra opción. Solo afecta a inputs, selects y textarea con `.elementor-field` dentro del formulario y widget nativos.
- Conserva bordes, colores y outline configurados; no cambia campos ajenos a Elementor. La regla depende del check existente. Prueba local sin envíos en `tests/elementor-form-focus.php`, excluida de la publicación.

## Versión 1.0.58

- Ancho predeterminado de scrollbar de **5 px** para configuraciones nuevas o sin ancho definido; conserva los anchos ya guardados, incluido 0.
- Comprobación de actualizaciones, caché de GitHub y bloqueo de consultas cada **5 minutos**. Migra el cron anterior de 10 minutos en instalaciones existentes, sin duplicados ni cambios en eventos ajenos, e invalida caché/bloqueo antiguos. Al desactivar limpia ambos eventos de la suite.
- WP-Cron sigue dependiendo de las visitas; el intervalo no garantiza una instalación automática ni una ejecución en un minuto exacto.

## Versión 1.0.57

- El acceso independiente de **Pedidos** muestra el carrito de compras nativo de WordPress (`dashicons-cart`) en lugar del portapapeles. No cambia ubicación, contador, permisos ni navegación de HPOS y pedidos clásicos.

## Versión 1.0.56

- Nueva caja **WooCommerce > Pedidos en el menú principal**, apagada por defecto. Mueve el acceso nativo justo debajo de WooCommerce y antes de Productos, fuera del submenú, conservando su idioma, contador y permisos. No modifica pedidos ni crea una pantalla diferente.
- Compatible con HPOS y almacenamiento clásico, listado y editor. La entrada original se retira visualmente al renderizar el encabezado, después de la autorización y resolución de los hooks de HPOS. Sin WooCommerce, menú nativo o permisos no cambia nada; respeta accesos independientes de terceros.
- Al apagar y guardar vuelve al submenú. Ajustes por AJAX; el menú refleja el cambio al navegar o recargar. Mantiene dos cajas por fila en escritorio y una en tablet/móvil. Pruebas locales en `tests/orders-menu.php`, excluidas del ZIP.

## Versión 1.0.55

- **Compact admin bar style** corrige el icono de Rank Math: anula los 6 px de desplazamiento de su contenedor propio, que no usa `.ab-icon`, y centra el SVG en escritorio. Conserva separación del texto, hover y menús; no aplica la corrección al móvil.
- Verificado con el marcado y estilo inline observados en el sitio, incluso cargados después del CSS de la suite. Barra de 32 px en escritorio, 46 px en móvil y menús con Enter/Escape. Fixture local en `tests/admin-bar-icons-fixture.php`, excluido del ZIP.

## Versión 1.0.54

- Cantidades: forma Normal/Circular, **Input border radius** y **Gap between controls** de 0–40 px. Circular fija ancho y alto iguales y radio de 50 %, sin cambiar la altura independiente del campo; Normal conserva alto y radio configurados. El radio anterior de 100 px migra a Circular, salvo selección explícita Normal. Gap inicial de 4 px, configurable a 0.
- Barra de escritorio: elimina padding nativo y desplazamientos de los iconos superiores; centra los glifos en 32 px y los SVG. Conserva las reglas móviles separadas de 46 px y los menús nativos.
- Eliminados **Loading transition** y **Export plugin**, incluyendo cajas, activación por ajustes antiguos, código de fade, botón del editor, generador ZIP y endpoint de descarga. El código anterior puede recuperarse desde Git. No modifica archivos de exportación ajenos ni instala actualizaciones en sitios.
- Ajustes por AJAX, cantidad centrada, foco de teclado conservado y dos cajas por fila en escritorio, una en tablet/móvil.

## Versión 1.0.53

- **WordPress > Comentarios** usa el interruptor principal y desplegable habitual de la suite. Primero se enciende la caja y luego se abre la flecha para configurar las cuatro opciones.
- Opciones interiores en dos columnas en escritorio (>1024 px) y una en tablet/móvil. La caja mantiene su mitad de fila en escritorio, sin excepciones de ancho completo.
- Apagar el módulo suspende sus efectos y cierra el panel, conservando todas las selecciones. Actualizar no desactiva restricciones que ya estaban activas; las configuraciones inactivas permanecen apagadas. Mantiene el guardado AJAX y no borra comentarios.

## Versión 1.0.52

- **Quantity Plus Minus Button** respeta por separado `Input width` y `Button width`, aunque el tema o Elementor fije el ancho/flex del contenedor. El campo ya no reparte ni pierde su ancho con los botones.
- Cantidad centrada en producto, carrito y selectores nativos de bloques, con padding simétrico y sin sangría heredada del tema.
- Clic/tap sin contorno ni sombra negra de foco; conserva un indicador accesible para Tab/flechas con color del sistema o `--xw-qty-focus-color`. No cambia mínimos/máximos/pasos ni eventos del carrito.
- Verificado con campo de 100/140 px y botones de 50 px, reglas conflictivas de Elementor/tema y móvil de 320/390 px. La distribución de cajas de la suite no cambia.

## Versión 1.0.51

- Nueva caja **WordPress > Duplicate page**, apagada por defecto. Duplica páginas, entradas, tipos públicos compatibles y plantillas Elementor desde sus listados, editor clásico o menú Opciones de Gutenberg, usando la versión guardada.
- Configura editor, estado de la copia (borrador por defecto), destino (listado/editor) y sufijo del título. Guarda por AJAX y mantiene dos cajas por fila en escritorio, una en tablet/móvil.
- Conserva contenido, extracto, orden, taxonomías, campos repetidos, referencias a imágenes y datos/ajustes de Elementor. No modifica el original ni duplica archivos, comentarios, bloqueos, cachés o condiciones globales de plantillas.
- Valida nonce y permisos de lectura/edición/creación; privado/publicado requiere permiso de publicación. WooCommerce conserva su duplicación nativa de productos. Si el plugin Duplicate Page original está activo, no añade acciones duplicadas ni modifica sus ajustes; desactívalo para usar el módulo de la suite.

## Versión 1.0.50

- Nueva caja **WordPress > Comentarios**, con exactamente cuatro interruptores apagados por defecto: desactivar globalmente, en entradas, en productos y en páginas. Productos incluye las reseñas de WooCommerce.
- Global tiene prioridad, pero conserva las selecciones individuales para cuando se desactive. Cierra comentarios/pingbacks nuevos y oculta la discusión en las plantillas y bloques públicos nativos.
- No borra comentarios existentes ni cambia los estados originales de las publicaciones o el ajuste guardado de reseñas de WooCommerce. Conserva la moderación administrativa y las notas internas de pedidos; al apagar las opciones vuelve a respetar la configuración original.
- Guardado AJAX y controles accesibles, con dos cajas por fila en escritorio y una en tablet/móvil.

## Versión 1.0.49

- **Compact admin bar style** adapta también la barra móvil: iconos y avatar centrados en controles de 46 px, títulos ocultos con las reglas nativas de WordPress y desplegables debajo de la barra sin desbordar.
- Sustituye los cambios globales de posición/flex por reglas separadas para escritorio y móvil. Mantiene la altura nativa (32/46 px), las etiquetas accesibles, el teclado y los menús del sitio y de la cuenta, tanto en administración como en frontend.
- Solo carga sus estilos si la opción está activa, hay un usuario conectado y la barra está visible. No cambia la distribución de las cajas de la suite.

## Versión 1.0.48

- Nuevo módulo opcional **WooCommerce > Quantity Plus Minus Button**, apagado por defecto. Añade controles accesibles +/− a los campos de cantidad existentes sin reemplazar plantillas de WooCommerce.
- Colores de fondo, borde y texto en estado normal y hover; ancho y radio del borde, ancho del botón, ancho/alto del campo y sus colores. Se guardan por AJAX desde la suite.
- Respeta cantidades mínimas, máximas y pasos decimales; actualiza controles tras AJAX y cambios de variación. El cambio dispara los eventos normales del carrito y funciona con «Actualizar carrito automáticamente».
- Mantiene los botones nativos de bloques y evita duplicar controles del tema. Si el plugin de referencia está activo, desactívalo antes de usar este módulo; la suite no lo desinstala ni cambia sus ajustes.
- Cajas en dos columnas de escritorio y una en tablet/móvil; botones táctiles de al menos 44 px. No añade campos de cantidad donde WooCommerce no los tiene ni cambia el mini carrito de texto en un formulario editable.

## Versión 1.0.47

- Corrige el aviso de cambios sin guardar después de un guardado AJAX confirmado: la reescritura HTML del editor visual al salir no cuenta como una edición nueva.
- Sincroniza el texto del pie con el guardado AJAX y refleja su contenido saneado. Las ediciones nuevas, las realizadas durante el guardado y los fallos siguen protegidos.

## Versión 1.0.46

- Ajustes: Guardar cambios queda gris y desactivado sin cambios pendientes; vuelve a azul al editar y a gris al guardar o deshacer la edición.
- Guardado AJAX sin recarga ni salto de posición, con estados accesibles de guardando, guardado y error. Conserva las ediciones durante fallos y las realizadas mientras se guarda.
- Mantiene permisos, nonce y sanitización de WordPress; rechaza peticiones incompletas para evitar que límites del servidor borren ajustes.

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

El plugin consulta cada cinco minutos la última publicación estable de este repositorio mediante WP-Cron. Si la versión publicada es mayor que la instalada, la actualización aparece en **Plugins > Plugins instalados** y puede aplicarse con **Actualizar ahora** o mediante las actualizaciones automáticas de WordPress. Como WP-Cron depende de la actividad del sitio, la comprobación se ejecuta durante la primera visita recibida después de cumplirse el intervalo.

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
