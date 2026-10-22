<?php
/** Read-only local fixture; flatpickr vendor files are QA-only, excluded from release. */
if ( ! in_array( $_SERVER['REMOTE_ADDR'] ?? '', array( '127.0.0.1', '::1' ), true ) ) { http_response_code( 403 ); exit; }
$enabled = ! isset( $_GET['enabled'] ) || '0' !== $_GET['enabled'];
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Fecha Elementor · QA local</title>
<link rel="stylesheet" href="../build/date-qa/flatpickr.min.css">
<style>body{font:16px/1.5 system-ui;color:#17263a;background:#fff7fb;margin:24px}main{max-width:900px;margin:auto;background:white;padding:24px;border-radius:20px}h1{font-size:25px}h2{font-size:18px}form{display:grid;grid-template-columns:1fr 1fr;gap:18px}label{display:block}input{font:inherit;color:#17263a;box-sizing:border-box;width:100%;padding:12px;border:0;border-bottom:1px solid #566172;background:white;min-height:46px}button{font:inherit;padding:10px 16px;margin:10px 0;border-radius:8px;border:1px solid #ab3762;background:white;color:#62233b}input:focus{outline:2px solid #ab3762;outline-offset:2px}.help{font-size:14px}.flatpickr-mobile{width:100%}@media(max-width:600px){body{margin:12px}main{padding:18px}form{grid-template-columns:1fr}}</style>
<?php if ( $enabled ) : ?><link rel="stylesheet" href="../assets/css/elementor-date.css"><?php endif; ?>
</head><body><main><h1>Form Elementor: fecha</h1><p>Prueba local sin envíos. Escritura dd/mm/aaaa y calendario original. Teclado numérico solicitado con inputmode; su apariencia requiere un teléfono real.</p>
<section class="elementor-widget-form"><h2>Formulario A — fecha nativa</h2><form id="native-form" class="elementor-form">
<div class="elementor-field-type-date elementor-field-group"><label for="native-date">Fecha nativa</label><input id="native-date" name="form_fields[fecha]" type="date" required min="2020-01-01" max="2030-12-31" value="2026-10-22" class="elementor-field elementor-field-textual elementor-date-field elementor-use-native" aria-describedby="date-hint"><div class="help" id="date-hint">Entre 2020 y 2030.</div></div>
<div class="elementor-field-type-date elementor-field-group"><label for="optional-date">Fecha opcional</label><input id="optional-date" name="form_fields[opcional]" type="date" class="elementor-field elementor-field-textual elementor-date-field elementor-use-native"></div>
<button type="reset">Restablecer</button><button type="submit">Comprobar valor enviado</button><output id="qa-result"></output></form><button type="button" id="add-date">Añadir campo dinámico</button></section>
<section class="elementor-widget-form"><h2>Formulario B — Flatpickr móvil</h2><p class="help">Mismo camino de inicialización móvil de Flatpickr; simulado localmente para esta prueba de escritorio.</p><form id="fp-form" class="elementor-form"><div class="elementor-field-type-date elementor-field-group"><label for="fp-date">Fecha Flatpickr</label><input type="date" id="fp-date" name="form_fields[otra_fecha]" placeholder="dd/mm/aaaa" class="elementor-field elementor-field-textual elementor-date-field" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}"></div><button type="submit">Comprobar Flatpickr</button><button type="reset">Restablecer Flatpickr</button></form><button type="button" id="rebuild-picker">Reiniciar Flatpickr</button></section>
<section><h2>Fuera de Elementor (sin cambios)</h2><label for="outside-date">Otra fecha</label><input type="date" id="outside-date"></section>
<script src="../build/date-qa/flatpickr.min.js"></script><script>
var mobilePicker;
function createMobilePicker(){
 const previous=Object.getOwnPropertyDescriptor(navigator,'userAgent');
 Object.defineProperty(navigator,'userAgent',{configurable:true,value:'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile Safari/604.1'});
 try {mobilePicker=flatpickr('#fp-date',{allowInput:true,minDate:'2020-01-01',maxDate:'2030-12-31',defaultDate:'2026-11-05'});} finally {if(previous)Object.defineProperty(navigator,'userAgent',previous);else delete navigator.userAgent;}
}
createMobilePicker();
document.querySelector('#rebuild-picker').onclick=function(){mobilePicker.destroy();createMobilePicker();};
document.querySelector('#add-date').onclick=function(){if(document.querySelector('#dynamic-date'))return;var group=document.createElement('div');group.className='elementor-field-type-date';group.innerHTML='<label for="dynamic-date">Fecha dinámica</label><input type="date" id="dynamic-date" name="form_fields[dinamica]" class="elementor-field elementor-field-textual elementor-date-field elementor-use-native">';document.querySelector('#native-form').append(group);};
document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',event=>{event.preventDefault();document.querySelector('#qa-result').textContent=JSON.stringify(Array.from(new FormData(form)));}));
window.xwDateSettings={placeholder:'dd/mm/aaaa',invalid:'Escribe una fecha válida en formato dd/mm/aaaa.',calendar:'Calendario'};
</script><?php if ( $enabled ) : ?><script src="../assets/js/elementor-date.js"></script><?php endif; ?></main></body></html>
