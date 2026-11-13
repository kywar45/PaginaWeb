# Marcas

## Consultas

- `GET /StoreApi/v1/marcas`: marcas con `web_marcas.estatus = 1`, ordenadas por nombre, con el total de productos publicados de cada una. Incluye marcas sin productos.
- `GET /StoreApi/v1/productos?id_marca=2&limit=30&page=1`: productos de esa marca, con imágenes, precios, descuentos y paginación. La respuesta incluye `marca_id`.

La relación es `productos.marca = web_marcas.id`. Se muestran únicamente productos
con `active = 1`, independientemente de `oculto`. Los agotados permanecen visibles
después de los disponibles, identificados por `ProductAvailability.vue` dentro de
`ProductCardInfo.vue`. Las marcas desactivadas no devuelven productos en este listado.

El campo opcional `web_marcas.logo` acepta una URL completa HTTP/HTTPS o una ruta
desde la raíz del servidor, por ejemplo `Logo/wing 500x500.png`. La API la convierte
en `https://www.importadorasupernova.com/Logo/wing%20500x500.png`, respetando mayúsculas
y espacios. Si no tiene logo o falla su carga, la tarjeta muestra la inicial.
Para este cambio subir `BrandRepository.php`, `BrandService.php` y el frontend compilado.
Para asignar un producto a una marca existente:

```sql
UPDATE productos SET marca = 2 WHERE id = 123;
```

## Subir a StoreApi

Archivos nuevos:

- `src/Controllers/BrandController.php`
- `src/Services/BrandService.php`
- `src/Repositories/BrandRepository.php`

Archivos actualizados:

- `routes/api.php` (añade las importaciones y la ruta `/v1/marcas`; conserva cualquier ruta propia del servidor)
- `src/Controllers/ProductController.php`
- `src/Services/ProductService.php`
- `src/Repositories/ProductRepository.php`

Publicar también el contenido de `dist/spa`, generado con `npm run build`.
No hay cambios adicionales al esquema proporcionado de `web_marcas` y `productos.marca`.

## Validación

`php tests/brands.php` verifica el contrato, validación del ID, normalización de
precios e imágenes, paginación y presencia de agotados sin acceder a una BD.
Comprobar ambos endpoints en el servidor para verificar los datos reales.
