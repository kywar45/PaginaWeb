# Resumen de mi cuenta

`GET /StoreApi/v1/auth/resumen` devuelve `data.pedidos_pagados`.
Requiere la cookie de sesión; el ID se obtiene del usuario autenticado. No acepta
un ID del navegador para consultar otra cuenta. La respuesta no se almacena en caché.

Cada fila de `folios` representa un pedido. Se usa `COUNT(*)`, no `SUM(cantidad)`:

```sql
SELECT COUNT(*)
FROM folios
WHERE id_usuario = :user_id
  AND fecha_pago IS NOT NULL
  AND fecha_pago <> '0000-00-00 00:00:00';
```

Las dos condiciones de fecha se unen con `AND`. `!= NULL` no comprueba ausencia
en SQL. La consulta admite tanto fechas cero heredadas como `NULL`, y no depende
de `cantidad`, `total`, `estatus` ni otros campos que podrían desaparecer.
No se requieren cambios en la tabla ni índices nuevos para este conteo inicial.

## Subir al servidor

- `src/Controllers/AuthController.php`
- `src/Services/AuthService.php`
- `src/Repositories/UserRepository.php`

En `routes/api.php`, dentro de la función que registra las rutas, agregar:

```php
$router->get('/v1/auth/resumen', function (): void {
    header('Cache-Control: private, no-store');
    $pdo = Connection::get();
    $service = new AuthService($pdo, new UserRepository($pdo));
    $controller = new AuthController($service);
    $controller->summary();
});
```

Las clases ya están importadas para las rutas de autenticación existentes.
La ruta ya fue agregada en el archivo local. Publicar también `dist/spa` tras
compilar el frontend. El resumen muestra carga, error con reintento o el conteo
confirmado por la API (incluido cero), conservando nombre y correo.

## Verificación

- `php tests/account-summary.php`: sesión obligatoria y aislamiento por usuario.
- `node tests/account-summary-sql.mjs`: ejecuta el SELECT real en SQLite con fechas
  válidas, fechas cero, `NULL`, otras cuentas y eliminación de campos ajenos.

Verificar el endpoint en el servidor con una sesión iniciada para comprobar los
datos reales de MySQL. Invitados reciben HTTP 401.

## Mis pedidos: listado completo

`GET /StoreApi/v1/auth/pedidos?page=1&limit=10` devuelve todos los pedidos de
`folios` cuyo `id_usuario` corresponde a la sesión. No filtra por pago ni estatus.
Ordena por `fecha DESC, id DESC`. Admite 1–50 pedidos por página.
Cada pedido incluye `pdf_token`, un enlace cifrado ligado a esa sesión y cuenta,
válido durante una hora. Ver `PEDIDO_PDF.md` para los archivos necesarios.
El contador del resumen conserva su significado de **pedidos pagados**.

El listado selecciona únicamente `id`, `orden`, `fecha`, `fecha_pago`, `total` y
`estatus`. No expone notas internas ni información de otras cuentas. Fechas cero
y `NULL` se normalizan como ausencia de fecha; los importes sin valor válido
se muestran como «Por confirmar».

Subir las versiones actualizadas de `AuthController.php`, `AuthService.php` y
`UserRepository.php`, además del frontend de `dist/spa`. Agregar también esta ruta
al `routes/api.php` del servidor, sin quitar la ruta de resumen:

```php
$router->get('/v1/auth/pedidos', function (): void {
    header('Cache-Control: private, no-store');
    $pdo = Connection::get();
    $service = new AuthService($pdo, new UserRepository($pdo));
    $controller = new AuthController($service);
    $controller->orders();
});
```

Pruebas: `php tests/account-orders.php` y `node tests/account-orders-sql.mjs`.
