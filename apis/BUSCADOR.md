# Buscador de productos

Actualizar en StoreApi, conservando las rutas:

- `src/Controllers/ProductController.php`
- `src/Services/ProductService.php`
- `src/Repositories/ProductRepository.php`

No requiere tablas nuevas ni cambios de rutas: utiliza el endpoint existente `/v1/productos` con el parámetro `q`.

Ejemplo: `GET /StoreApi/v1/productos?q=gel%20rosa&page=1&limit=30`.

Filtros opcionales de la búsqueda:

- `orden`: `relevancia` (por defecto), `precio_asc`, `precio_desc` o `recientes`.
- `solo_disponibles=1`: muestra únicamente `estatus = 1`.
- `solo_ofertas=1`: descuento marcado `si` y precio de descuento positivo, igual que el precio efectivo del servicio.

Los filtros se aplican tanto al conteo como a los resultados antes de paginar. El orden por precio usa el precio final por pieza (con descuento cuando corresponde) como valor numérico; los disponibles no se agrupan antes del precio en esos dos órdenes. La respuesta incluye `search_filters` con las opciones efectivas. Se deben actualizar los mismos tres archivos PHP para habilitar estos filtros.

Busca todas las palabras por nombre, código, código de barras, descripción, nombre de categoría o subcategoría. Los códigos exactos se priorizan dentro de cada estado de disponibilidad. La sensibilidad a acentos/mayúsculas depende de la collation de MySQL existente. Muestra todos los productos `active = 1`, incluidos los agotados, con disponibles primero. No consulta `oculto`.

`q` admite entre 1 y 80 caracteres y normaliza espacios; formatos inválidos devuelven 422. `%` y `_` se buscan literalmente. Las consultas usan parámetros preparados. La respuesta incluye `search_query` y `pagination.total`, `total_pages`, `has_more`, `next_page`.

Publicar también el frontend actualizado (`dist/spa`). La ruta de la página es `/buscar?q=...`; Enter y las lupas del encabezado abren esa ruta. Si la API anterior ignora `q`, el frontend muestra un mensaje en lugar de enseñar productos sin filtrar.

Verificación: `php tests/search.php`, `php tests/products.php`, `npm.cmd run lint` y `npm.cmd run build`. Las pruebas locales no sustituyen comprobar en el servidor la búsqueda real y el rendimiento con el catálogo completo. Esta búsqueda utiliza LIKE; evaluar índices FULLTEXT y EXPLAIN si el volumen exige optimizarla.
