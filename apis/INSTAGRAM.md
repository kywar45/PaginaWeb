# Reels de Instagram

## Tabla

Ejecuta `sql/instagram_reels.sql` en la base de datos de StoreApi. Para agregar el Reel probado:

```sql
INSERT INTO web_instagram_reels (url, titulo, posicion, activo)
VALUES ('https://www.instagram.com/reel/DffWdm_DBgV/', 'Descubre Supernova', 1, 1);
```

Pega la URL completa de cada Reel público. Se aceptan URLs HTTPS de `instagram.com` o `www.instagram.com` con formato `/reel/CODIGO/`; los parámetros de la URL se ignoran. `posicion` menor se muestra primero. Usa `activo = 0` para ocultar un Reel. El endpoint devuelve hasta 50 activos con URLs válidas.

## API

Sube a StoreApi los siguientes archivos conservando sus rutas:

- `src/Controllers/InstagramReelController.php`
- `src/Services/InstagramReelService.php`
- `src/Repositories/InstagramReelRepository.php`

En el `routes/api.php` existente del servidor agrega:

```php
use App\Controllers\InstagramReelController;
use App\Repositories\InstagramReelRepository;
use App\Services\InstagramReelService;
```

Dentro de `return function (Router $router): void { ... }` agrega, sin reemplazar las rutas existentes:

```php
$router->get('/v1/instagram-reels', function (): void {
    $repository = new InstagramReelRepository(Connection::get());
    $controller = new InstagramReelController(new InstagramReelService($repository));
    $controller->index();
});
```

`Connection` debe estar importado. Comprueba `GET /StoreApi/v1/instagram-reels`: debe responder con `success: true` y una lista `data.videos`. La página usa Instagram cuando hay Reels activos válidos; si la API no está disponible o no devuelve Reels, mantiene TikTok como respaldo. Esto requiere publicar el frontend actualizado después de instalar la API. Un Reel debe ser público y permitir la inserción; su disponibilidad depende de Instagram.
