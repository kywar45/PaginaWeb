# Carrusel de TikTok

## 1. Crear tabla

Ejecutar `sql/tiktok_videos.sql` en la base de datos de StoreApi. Crear la tabla no agrega videos. Después se pueden registrar manualmente:

```sql
INSERT INTO web_tiktok_videos (url, titulo, portada_url, posicion, activo)
VALUES ('https://www.tiktok.com/@TU_USUARIO/video/1234567890123456789',
        'Conoce nuestras novedades', NULL, 1, 1);
```

La URL y el ID anteriores son ejemplos: reemplazarlos por una publicación real pública. Se admite la URL completa `https://www.tiktok.com/@usuario/video/ID`; los enlaces cortos `vm.tiktok.com` no se resuelven. Si recibes un enlace corto, ábrelo en el navegador y copia la URL completa. La consulta y sus parámetros se descartan. El enlace debe permitir ser insertado; la disponibilidad depende de TikTok.

`portada_url` es opcional y queda reservado para otros usos; el carrusel actual muestra directamente el reproductor oficial de TikTok. `posicion` menor aparece primero; para la misma posición, primero el ID más reciente. El endpoint devuelve hasta 50 videos activos. Para ocultar uno:

```sql
UPDATE web_tiktok_videos SET activo = 0 WHERE id = 1;
```

## 2. Instalar API

Copiar estos archivos a las mismas rutas dentro de StoreApi:

- `src/Controllers/TikTokVideoController.php`
- `src/Services/TikTokVideoService.php`
- `src/Repositories/TikTokVideoRepository.php`

En el `routes/api.php` real del servidor, agregar los imports y la ruta sin reemplazar las rutas existentes:

```php
use App\Controllers\TikTokVideoController;
use App\Repositories\TikTokVideoRepository;
use App\Services\TikTokVideoService;
```

Dentro de `return function (Router $router): void { ... }`:

```php
$router->get('/v1/tiktok-videos', function (): void {
    $repository = new TikTokVideoRepository(Connection::get());
    $controller = new TikTokVideoController(new TikTokVideoService($repository));
    $controller->index();
});
```

`Connection` debe estar importado, como en las rutas existentes. Probar con `GET /StoreApi/v1/tiktok-videos`: debe devolver `success: true` y `data.videos`. Las URLs inválidas se omiten. La sección de la portada solo aparece cuando la API devuelve videos válidos; el reproductor oficial de TikTok se muestra en cada tarjeta, con enlace alternativo a la publicación.

## 3. Publicar frontend

Desplegar la compilación actual de la aplicación tras instalar la API. La base de la API se toma de `VITE_STORE_API_BASE_URL`, igual que banners y productos. La creación de la tabla y el despliegue al servidor no se realizan automáticamente desde este proyecto.
