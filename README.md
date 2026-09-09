# Creative Pear Suite

Plugin de WordPress de Creative Pear Agency con funciones reutilizables y una pantalla para activar, desactivar y configurar cada módulo.

Sitio web: [creativepearagency.com](https://creativepearagency.com)

## Actualizaciones desde GitHub

El plugin consulta cada diez minutos la última publicación estable de este repositorio mediante WP-Cron. Si la versión publicada es mayor que la instalada, la actualización aparece en **Plugins > Plugins instalados** y puede aplicarse con **Actualizar ahora** o mediante las actualizaciones automáticas de WordPress. Como WP-Cron depende de la actividad del sitio, la comprobación se ejecuta durante la primera visita recibida después de cumplirse el intervalo.

La instalación inicial del plugin sigue haciéndose una sola vez con el ZIP. A partir de ese momento, las versiones nuevas llegan desde GitHub Releases.

## Publicar una versión

1. Cambiar `Version:` y `XW_FUNCTIONS_VERSION` en `creativepear-suite.php` al mismo número.
2. Guardar y subir los cambios a la rama `main`.
3. Crear y subir una etiqueta con esa versión, por ejemplo:

```powershell
git tag v1.0.6
git push origin main --tags
```

La acción **Publicar plugin** valida el PHP, comprueba que la etiqueta coincide con la versión, genera `creativepear-suite.zip` y crea la publicación de GitHub.

## Requisitos para el ZIP

No se debe subir a WordPress el ZIP automático de “Source code” que muestra GitHub. El actualizador utiliza el archivo `creativepear-suite.zip` adjunto a cada publicación.
