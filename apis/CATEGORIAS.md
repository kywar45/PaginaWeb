# API de categorias

Endpoint publico para traer categorias con sus subcategorias desde `admin_categorias` y `admin_subcategorias`.

## Archivos

Subir estos archivos al StoreApi:

- `src/Controllers/CategoryController.php`
- `src/Services/CategoryService.php`
- `src/Repositories/CategoryRepository.php`

## Imports en `routes/api.php`

```php
use App\Controllers\CategoryController;
use App\Repositories\CategoryRepository;
use App\Services\CategoryService;
```

## Ruta

```php
$router->get('/v1/categorias', function (): void {
    $pdo = Connection::get();
    $repository = new CategoryRepository($pdo);
    $service = new CategoryService($repository);
    $controller = new CategoryController($service);

    $controller->index();
});
```

## Uso

Sin filtros:

```text
GET /StoreApi/v1/categorias
```

Filtrando categorias y subcategorias por estatus:

```text
GET /StoreApi/v1/categorias?estatus_categoria=Activo&estatus_subcategoria=Activo
```

## Respuesta

```json
{
  "success": true,
  "data": {
    "categorias": [
      {
        "id_categoria": 1,
        "id_depa": 1,
        "nombre_categoria": "Unas",
        "estatus_categoria": "Activo",
        "position": 1,
        "icon": "unas.webp",
        "icon_url": "iconos/unas.webp",
        "emoji": null,
        "subcategorias": [
          {
            "id_subcategoria": 10,
            "id_categoria": 1,
            "nombre_subcategoria": "Gel",
            "estatus_subcategoria": "Activo",
            "position": 1
          }
        ]
      }
    ]
  }
}
```

## Notas

- La consulta usa `LEFT JOIN`, asi una categoria sin subcategorias tambien aparece.
- El orden sale por `position` y despues por ID.
- Si `icon` viene vacio, `icon_url` sale como `null`.
- La ruta actual para iconos quedo como carpeta relativa `iconos/`.
