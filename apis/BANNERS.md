# API de banners

Endpoint público de lectura: `GET https://www.importadorasupernova.com/StoreApi/v1/banners`.
Compatible con PHP 7.4. Usa la conexión, respuestas y middlewares del StoreApi existente.

## Archivos para subir

Copiar dentro de la carpeta StoreApi del servidor, respetando estas rutas:

- `src/Controllers/BannerController.php`
- `src/Services/BannerService.php`
- `src/Repositories/BannerRepository.php`

También se necesita `src/Exceptions/ApiException.php`, ya incluido en el paquete de auth.

En el **routes/api.php existente del servidor**, agregar los imports y la ruta siguientes.
No reemplazar el archivo completo si tiene otras rutas: la copia local solo conoce health y auth.

```php
use App\Controllers\BannerController;
use App\Repositories\BannerRepository;
use App\Services\BannerService;
```

Dentro de `return function (Router $router): void { ... }`:

```php
$router->get('/v1/banners', function (): void {
    $pdo = Connection::get();
    $repository = new BannerRepository($pdo);
    $service = new BannerService($repository);
    $controller = new BannerController($service);

    $controller->index();
});
```

`Connection` y `Router` ya están importados en las rutas locales. El autoloader del
servidor debe resolver las clases `App\` como lo hace con auth. Si utiliza un classmap
autoritativo, regenerarlo mediante el proceso de despliegue existente.

## Parámetros opcionales

| Parámetro | Por defecto | Valores |
| --- | --- | --- |
| `page` | `1` | Entero de 1 a 1000000 |
| `limit` | `50` | Entero de 1 a 100 |
| `tipo` | Sin filtro | Entero con signo compatible con la columna INT |

Se ordena por `posicion ASC, id ASC`. No se interpreta `tipo` como estado activo:
la tabla proporcionada no tiene estado ni fechas de vigencia. `tipo=1` corresponde al banner principal y `tipo=2` a los cintillos publicitarios intermedios. No se requiere login.
Los parámetros inválidos responden HTTP 422. No se requieren cambios de tablas o índices
para instalar este endpoint; evaluar índices con EXPLAIN si el volumen lo amerita.

## Respuesta de ejemplo

Los nombres, IDs y destinos siguientes son ilustrativos.

```json
{
  "success": true,
  "data": {
    "banners": [
      {
        "id": 281,
        "posicion": 1,
        "tipo": 1,
        "nombre_web": "coleccion-web.jpg",
        "nombre_movil": "coleccion-movil.jpg",
        "imagen_web": "https://www.importadorasupernova.com/Banners/coleccion-web.jpg",
        "imagen_movil": "https://www.importadorasupernova.com/Banners/coleccion-movil.jpg",
        "redireccion_web": "/ofertas",
        "redireccion_movil": "/ofertas"
      }
    ],
    "pagination": { "page": 1, "limit": 50, "has_more": false, "next_page": null }
  }
}
```

- `nombre_web` y `nombre_movil` son nombres de archivo, no títulos ni URLs completas.
- Se codifican los caracteres especiales del nombre al construir la URL de imagen.
- Si el nombre es NULL, vacío o solo espacios, su `imagen_*` será `null`.
- Se conservan los nombres y las redirecciones originales, incluidos NULL y cadenas vacías.
- No se comprueba por HTTP la existencia de cada imagen, para evitar una petición extra por banner.
- Sin resultados se devuelve HTTP 200, `banners: []` y `has_more: false`.
- Los errores inesperados pasan al manejo global existente de StoreApi; ese manejo debe
  registrar el error internamente y responder un mensaje seguro, como exige CODEX_STOREAPI.md.

## Consumo desde la portada

Leer `payload.data.banners`. En móvil se puede usar `imagen_movil || imagen_web`;
en escritorio, `imagen_web`. Omitir visualmente un banner que no tenga imagen utilizable.
Las redirecciones se entregan como datos: el frontend debe validar el destino antes de
navegar (rutas internas o URLs HTTP/HTTPS), sin ejecutar esquemas como `javascript:`.
Si `has_more` es true, solicitar `next_page` conservando `limit` y `tipo`.
La portada consume este endpoint mediante `src/services/bannerApi.js`: muestra tipo 1 arriba y tipo 2 entre ofertas y las siguientes secciones. El carrusel usa la imagen móvil bajo 600px y conserva la proporción original. Si falta la redirección móvil usa la web. Los GIF se muestran sin conversión.

## Pruebas después de subir

```bash
curl -i "https://www.importadorasupernova.com/StoreApi/v1/banners"
curl -i "https://www.importadorasupernova.com/StoreApi/v1/banners?tipo=1&page=1&limit=10"
curl -i "https://www.importadorasupernova.com/StoreApi/v1/banners?limit=101"
curl -i "https://www.importadorasupernova.com/StoreApi/v1/banners?page=0"
curl -i "https://www.importadorasupernova.com/StoreApi/v1/banners?tipo=texto"
```

Las primeras dos deben responder 200; las otras tres, 422. Verificar también que
una URL `imagen_web` y otra `imagen_movil` devueltas abran correctamente.
Las pruebas locales no sustituyen la verificación de conexión MySQL y middlewares del servidor.

Flujo: middleware existente → Router → BannerController → BannerService → BannerRepository
→ PDO/MySQL → Response estándar → finalización del request existente.

