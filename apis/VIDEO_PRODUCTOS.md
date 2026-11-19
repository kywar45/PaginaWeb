# Productos usados en videos de Inspírate

Cada tarjeta tiene el enlace **Ver productos del video**, que abre
`/inspirate/ID/productos`. Comparte la cuadrícula de Ofertas y sus tarjetas,
precios, insignias VIP/oferta, disponibilidad y paginación. La navegación superior
usa `PageBreadcrumbs` y permite regresar a Inspírate.

## Tabla

Ejecutar `sql/video_productos.sql` en la base de StoreApi **antes de publicar**.
Crea `web_video_productos` sin modificar videos ni productos existentes:

| Campo | Uso |
| --- | --- |
| `video_id` | ID de `web_tiktok_videos` |
| `producto_id` | ID de `productos` |
| `posicion` | Orden dentro de la selección |

La clave primaria `(video_id, producto_id)` impide repetir un producto dentro
del mismo video. Un producto puede aparecer en varios videos. No se agregan
claves foráneas para mantener compatibilidad con las tablas heredadas; la API
solo devuelve relaciones cuyos video y producto existen y están publicados.

Ejemplo: asociar los productos 101, 102 y 103 al video 12. Sustituir los números
por los IDs reales. El SELECT evita insertar identificadores inexistentes y
`ON DUPLICATE KEY UPDATE` permite repetir la carga sin duplicar relaciones:

```sql
INSERT INTO web_video_productos (video_id, producto_id, posicion)
SELECT v.id, p.id, FIELD(p.id, 101, 102, 103)
FROM web_tiktok_videos v
JOIN productos p ON p.id IN (101, 102, 103)
WHERE v.id = 12
ON DUPLICATE KEY UPDATE posicion = VALUES(posicion);
```

Quitar una relación no borra el producto ni el video:

```sql
DELETE FROM web_video_productos WHERE video_id = 12 AND producto_id = 102;
```

Los cambios se capturan manualmente en la tabla; no se incluye un administrador.

## API y visibilidad

`GET /v1/productos?id_video=12&page=1&limit=30` devuelve `video`, `productos`
y `pagination`. No hay endpoint nuevo. El video debe tener `activo = 1`;
si no existe o está inactivo responde 404. Un video activo sin productos
publicados devuelve lista vacía. No filtra ofertas ni colecciones ajenas.

Los productos deben tener `active = 1`; se ignora `oculto`. Se conservan agotados
(`estatus = 0`), con `ProductAvailability` en la tarjeta. Los disponibles aparecen
primero, seguidos de agotados; dentro de cada grupo se ordena por `posicion` e ID.
Los precios, ofertas y VIP se calculan con la sesión en el servidor. No se
expone `precio_costo`, y no se modifican reglas de compra ni carrito.

## Publicar

Después del SQL, subir estos archivos a sus mismas carpetas de StoreApi:

```text
src/Controllers/ProductController.php
src/Repositories/ProductRepository.php
src/Services/ProductService.php
```

Finalmente subir el contenido compilado de `dist/spa/`. Las rutas de API no
cambian. Publicar PHP primero: el frontend verifica que la respuesta corresponde
al video solicitado para evitar mostrar todo el catálogo si la API es antigua.

## Comprobar

```text
php -d extension=pdo_sqlite apis/tests/video-products.php
php apis/tests/products.php
php apis/tests/tiktok.php
node apis/tests/video-products-client.mjs
npm run build
```

No se ejecuta SQL en producción ni se asignan IDs de ejemplo automáticamente.
