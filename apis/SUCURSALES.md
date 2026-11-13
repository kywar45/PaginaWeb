# Sucursales

La página `/sucursales` consume `GET /StoreApi/v1/sucursales`.
Consulta `web_sucursales` con `estatus = 1`, ordenando por `posicion ASC, id ASC`.
Devuelve nombre, dirección, código postal, teléfono, horario, enlace de mapa y
coordenadas. No necesita cambios adicionales al esquema proporcionado.

El botón de mapa usa `google_maps_url` si contiene una URL HTTP/HTTPS válida;
si está vacío, usa latitud y longitud, y si no hay coordenadas válidas busca por
dirección y código postal. Los campos vacíos de teléfono y horario no se muestran.
El teléfono genera un enlace `tel:` y el horario admite saltos de línea.

## Archivos nuevos para subir a StoreApi

- `src/Controllers/BranchController.php`
- `src/Services/BranchService.php`
- `src/Repositories/BranchRepository.php`

## Agregar a `routes/api.php` del servidor

Junto a los demás `use`:

```php
use App\Controllers\BranchController;
use App\Repositories\BranchRepository;
use App\Services\BranchService;
```

Dentro de `return function (Router $router): void {`, junto a las otras rutas:

```php
$router->get('/v1/sucursales', function (): void {
    $repository = new BranchRepository(Connection::get());
    $service = new BranchService($repository);
    $controller = new BranchController($service);
    $controller->index();
});
```

`Connection` ya está importado en el archivo actual del proyecto.
El `apis/routes/api.php` local ya incluye estos cambios. Conservar cualquier ruta
propia del servidor si se reemplaza el archivo completo.

Publicar también el contenido de `dist/spa`, generado con `npm run build`.

## Validación

`php tests/branches.php` comprueba la respuesta, teléfonos, campos opcionales,
código postal y alternativas de mapa sin conectarse a la BD. Verificar después
`/StoreApi/v1/sucursales` en el servidor con los datos reales; debe devolver JSON
con `data.sucursales`.
