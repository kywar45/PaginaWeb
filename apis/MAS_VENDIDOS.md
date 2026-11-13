# Más vendidos

La página `/mas-vendidos` usa `GET /StoreApi/v1/productos?coleccion=mas_vendidos&limit=30`.

Consulta `registro_usuario`: suma `cantidad` por `id_producto`, únicamente con
`estatus = 'Pagado'` y `fecha_procesado` entre hace tres meses y ahora, según la
hora del servidor de base de datos. Une `productos.id` con `id_producto` y obtiene
la imagen de `img` con `posicion = 1`, igual que los otros listados.

Devuelve hasta 30 productos publicados (`active = 1`) ordenados por cantidad
vendida descendente. Los empates se resuelven por ID ascendente. Los agotados
se conservan en su posición y la interfaz los identifica. No usa `oculto`.
La paginación se aplica sobre este grupo de hasta 30 productos.

## Subir al servidor

Reemplazar, en las carpetas equivalentes de StoreApi:

- `src/Controllers/ProductController.php`
- `src/Services/ProductService.php`
- `src/Repositories/ProductRepository.php`

Publicar también el frontend generado con `npm run build` (`dist/spa`).
No se necesitan tablas ni columnas nuevas.

## Verificación

`php tests/best_sellers.php` verifica paginación, orden, precios, imágenes y
agotados sin conexión a la base de datos. La consulta real debe verificarse en
el servidor que contiene `registro_usuario`, comparando los IDs con el SQL de
ventas; los productos no publicados se excluyen del listado público.
