# Inspírate: videos propios y miniaturas

Se muestran miniaturas elegidas por ustedes. El MP4 se solicita únicamente
al pulsar reproducir. Usa controles nativos, reproducción dentro de la tarjeta
y pantalla completa. Abrir otro video detiene el anterior.

Se reutilizan `web_tiktok_videos` y `/v1/tiktok-videos` como nombres internos
para evitar migraciones. Ya no se cargan iframes ni reproductores de TikTok.
Los registros que todavía tengan enlaces de TikTok se omiten.

## Subir archivos y registrar contenido

Subir videos MP4 y miniaturas JPG, PNG, WebP o AVIF a una carpeta pública del
servidor, por ejemplo `/videos/inspirate/`. Deben abrir directamente por HTTPS
en `www.importadorasupernova.com` o `importadorasupernova.com`, sin autenticación.
Se recomienda MP4 con video H.264 y audio AAC para compatibilidad.
El servidor debe entregar `.mp4` como `video/mp4` y permitir peticiones
de rango para adelantar la reproducción. Preparar el MP4 con `faststart`
ayuda a reproducirlo sin descargar el archivo completo.
Las miniaturas se muestran en formato vertical 9:16; los videos conservan su
proporción completa dentro del reproductor.

Si la tabla ya existe, **no se necesita ALTER TABLE ni SQL de migración**.
Si no existe, crearla con `sql/tiktok_videos.sql`.
Ejemplo para agregar un video (sustituir las rutas por archivos ya subidos):

```sql
INSERT INTO web_tiktok_videos (url, titulo, portada_url, posicion, activo)
VALUES (
  'https://www.importadorasupernova.com/videos/inspirate/tutorial-unas.mp4',
  'Ideas para tus uñas',
  'https://www.importadorasupernova.com/videos/inspirate/tutorial-unas.webp',
  1,
  1
);
```

Para reutilizar una fila anterior, cambiar `url`, `portada_url` y `titulo`
mediante un `UPDATE ... WHERE id = ...` con el identificador de esa fila.
`posicion` menor aparece primero; `activo = 0` lo oculta. La API devuelve hasta
50 filas activas y omite URLs inválidas o contenido sin miniatura.
Se admiten únicamente archivos propios del dominio, no enlaces de plataformas.

## Publicar

Subir `src/Services/TikTokVideoService.php` actualizado a StoreApi.
Después subir el contenido compilado de `dist/spa/` al sitio.
El repositorio, controlador y ruta actuales no cambian. Publicar el servicio
antes del frontend: el cliente nuevo espera URLs de MP4 y miniaturas.
La subida de archivos y la captura en la tabla se hacen manualmente.

## Comprobar

```text
php apis/tests/tiktok.php
node apis/tests/inspiration-media.mjs
npm run build
```

Tras publicar un MP4 real, comprobar en escritorio y móvil: se muestra la
miniatura, no se descarga el video antes de pulsar, comienza la reproducción,
funcionan controles/pantalla completa y abrir otro video detiene el anterior.
Una miniatura fallida conserva el botón de reproducir. Un MP4 fallido o
un error al consultar la lista muestra reintento; una lista vacía se informa.
