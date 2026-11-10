# Instrucciones para Codex - Cómo crear APIs en StoreApi

Este archivo sirve como contrato de desarrollo para cualquier endpoint nuevo de `StoreApi`.

## Contexto del proyecto

La API nueva vive en:

```text
/StoreApi/
```

URL pública base:

```text
https://www.importadorasupernova.com/StoreApi/v1/
```

El servidor web del dominio actualmente ejecuta **PHP 7.4.33**, aunque el CLI por SSH tenga una versión superior. Por lo tanto, todo el código de `StoreApi` debe ser compatible con PHP 7.4 hasta que se complete la migración del sistema viejo.

## Regla principal

**No crear archivos PHP públicos por endpoint.**

Incorrecto:

```text
/StoreApi/login.php
/StoreApi/products.php
/StoreApi/createOrder.php
```

Correcto:

```text
Petición HTTP
   ↓
public/index.php
   ↓
Router
   ↓
routes/api.php
   ↓
Controller
   ↓
Service
   ↓
Repository
   ↓
PDO / MySQL
```

---

# Estructura actual

```text
StoreApi/
├── public/
│   ├── index.php
│   └── .htaccess
│
├── routes/
│   └── api.php
│
├── src/
│   ├── Database/
│   │   └── Connection.php
│   │
│   ├── Http/
│   │   ├── Request.php
│   │   ├── Response.php
│   │   └── Router.php
│   │
│   ├── Middleware/
│   │   ├── SecurityMiddleware.php
│   │   ├── RequestMiddleware.php
│   │   └── CorsMiddleware.php
│   │
│   └── Logging/
│       └── Logger.php
│
├── config/
├── storage/
│   └── logs/
│       ├── app.log
│       └── error.log
│
├── bootstrap.php
├── .env
├── .env.example
├── composer.json
├── composer.lock
└── vendor/
```

Los nuevos endpoints deben agregar, cuando aplique:

```text
src/Controllers/
src/Services/
src/Repositories/
```

---

# Flujo obligatorio de cada request

```text
Cloudflare
   ↓
.htaccess raíz
   ↓
public/index.php
   ↓
RequestMiddleware
   ↓
SecurityMiddleware
   ↓
CorsMiddleware
   ↓
Router
   ↓
routes/api.php
   ↓
Controller
   ↓
Service
   ↓
Repository
   ↓
Connection::get()
   ↓
MySQL
   ↓
Response::success() / Response::error()
   ↓
RequestMiddleware::finish()
```

No saltarse estas capas.

---

# Compatibilidad PHP 7.4

NO utilizar características exclusivas de PHP 8+, por ejemplo:

```text
mixed
never
str_starts_with()
match
union types: int|string
nullsafe operator: ?->
named arguments
attributes #[...]
constructor property promotion
```

Utilizar alternativas compatibles con PHP 7.4.

Ejemplo:

```php
if (strpos($uri, $basePath) === 0) {
    // ...
}
```

No:

```php
if (str_starts_with($uri, $basePath)) {
    // PHP 8+
}
```

---

# Conexión a la base de datos

Usar exclusivamente:

```php
use App\Database\Connection;

$pdo = Connection::get();
```

No crear nuevas conexiones PDO dentro de Controllers, Services o Repositories.

La configuración actual utiliza:

```text
DB_HOST=127.0.0.1
```

No cambiarlo a `localhost` sin una razón comprobada. En este hosting `localhost` produjo:

```text
SQLSTATE[HY000] [2002] Operation not permitted
```

PDO debe permanecer con:

```text
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
PDO::ATTR_EMULATE_PREPARES => false
PDO::ATTR_PERSISTENT => false
```

---

# Consultas SQL

Todas las consultas con datos variables deben utilizar prepared statements.

Correcto:

```php
$stmt = $pdo->prepare(
    'SELECT id, nombre, correo
     FROM web_usuarios
     WHERE correo = ?
     LIMIT 1'
);

$stmt->execute([$correo]);
$usuario = $stmt->fetch();
```

Incorrecto:

```php
$pdo->query("SELECT * FROM web_usuarios WHERE correo = '$correo'");
```

No concatenar input del usuario en SQL.

---

# Separación de responsabilidades

## Controller

Responsabilidades:

- Recibir la petición.
- Obtener parámetros/path/body.
- Validar formato básico.
- Llamar al Service.
- Regresar `Response`.

No debe contener consultas SQL.

## Service

Responsabilidades:

- Reglas de negocio.
- Validaciones de negocio.
- Transacciones multi-repository.
- Decidir qué operaciones ejecutar.

No debe construir respuestas HTTP directamente salvo que el diseño existente lo requiera expresamente.

## Repository

Responsabilidades:

- SQL.
- Prepared statements.
- Lectura/escritura en BD.
- Devolver arrays/valores al Service.

No debe decidir permisos o reglas comerciales.

---

# Registro de rutas

Toda ruta se registra en:

```text
routes/api.php
```

Ejemplo conceptual:

```php
$router->post('/v1/auth/login', function () {
    $controller = new AuthController();
    $controller->login();
});
```

No modificar `public/index.php` para agregar endpoints individuales.

---

# Entrada JSON

Para endpoints con body JSON utilizar:

```php
use App\Http\Request;

$data = Request::json();
```

No leer y decodificar `php://input` repetidamente en cada controller.

La API debe exigir:

```text
Content-Type: application/json
```

para este tipo de endpoint.

---

# Respuestas estándar

Nunca hacer:

```php
echo '1';
echo 'ok';
echo json_encode($data);
```

Usar:

```php
Response::success($data, 200);
```

o:

```php
Response::error(
    'INVALID_CREDENTIALS',
    'Credenciales inválidas',
    401
);
```

Formato de éxito:

```json
{
  "success": true,
  "data": {}
}
```

Formato de error:

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Mensaje seguro"
  }
}
```

Usar códigos HTTP reales: 200, 201, 400, 401, 403, 404, 409, 413, 415, 422, 429, 500, 503 según corresponda.

---

# Seguridad global ya existente

El endpoint debe asumir que antes del Router pasan:

- `RequestMiddleware`.
- `SecurityMiddleware`.
- `CorsMiddleware`.

Estos proporcionan actualmente:

- `X-Request-ID` único.
- Logs de inicio/fin y duración.
- `X-Content-Type-Options: nosniff`.
- `X-Frame-Options: DENY`.
- `Referrer-Policy: no-referrer`.
- `Cache-Control: no-store` por defecto.
- Límite de body de 1 MB.
- Lista de métodos HTTP permitidos.
- HTTPS obligatorio.
- CORS restringido a Importadora Supernova.

No desactivar estas protecciones desde un endpoint.

---

# CORS

Orígenes permitidos actualmente:

```text
https://www.importadorasupernova.com
https://importadorasupernova.com
```

No utilizar:

```text
Access-Control-Allow-Origin: *
```

especialmente cuando haya cookies/sesiones.

---

# Autenticación de clientes

Tabla principal:

```text
web_usuarios
```

Contraseña:

```php
password_hash($password, PASSWORD_DEFAULT);
password_verify($password, $hash);
```

Nunca almacenar contraseña en texto plano, MD5 o SHA1.

Sesiones:

```text
web_sesiones
```

Regla:

1. Generar token criptográficamente aleatorio.
2. Entregar token real mediante cookie segura.
3. Guardar únicamente SHA-256 del token en `web_sesiones.session_hash`.
4. Cookie: `HttpOnly`, `Secure`, `SameSite=Lax`.
5. Validar `expira_at` y `revocada_at`.

No almacenar el token de sesión directamente en `web_usuarios`.

---

# Autenticación interna

Usuarios administrativos/vendedores:

```text
web_usuarios_internos
```

Sesiones:

```text
web_sesiones_internas
```

No mezclar clientes y usuarios internos en la misma tabla ni en la misma lógica de autorización.

---

# CSRF

Cuando se implemente autenticación por cookie, todos los endpoints que cambien estado deben exigir protección CSRF:

```text
POST
PUT
PATCH
DELETE
```

Los GET no deben cambiar datos.

Nunca crear rutas como:

```text
GET /v1/delete-order?id=20
```

---

# Authorization / ownership

No basta con que el usuario esté autenticado.

Ejemplo:

```text
GET /v1/orders/9000
```

Antes de devolver el pedido, verificar que:

```text
web_pedidos.usuario_id == usuario autenticado
```

salvo que sea un usuario interno con permiso explícito.

Nunca aceptar un `usuario_id` enviado por Quasar para decidir qué cuenta está realizando una acción autenticada. El ID debe salir de la sesión validada.

---

# Precios

Regla crítica para carrito y pedidos:

**El navegador nunca decide el precio.**

Quasar puede enviar:

```json
{
  "producto_id": 25,
  "cantidad": 3
}
```

No confiar en:

```json
{
  "producto_id": 25,
  "cantidad": 3,
  "precio": 1.00
}
```

El Repository/Service obtiene `web_productos`, calcula el precio aplicable y ese es el valor que se guarda.

Al convertir carrito a pedido, volver a validar el precio antes de grabar `web_pedido_detalle`.

---

# Carrito invitado + login

Tablas:

```text
web_carritos
web_carrito_detalle
```

Para invitado:

- Generar `guest_token` aleatorio.
- Cookie guarda token real.
- BD guarda `SHA256(guest_token)` en `guest_token_hash`.
- `usuario_id = NULL`.

Cuando el usuario inicia sesión:

1. Buscar carrito de invitado.
2. Buscar carrito activo de usuario.
3. Iniciar transacción.
4. Fusionar productos.
5. Si un producto existe en ambos, sumar cantidades según las reglas del negocio.
6. Marcar/eliminar de forma controlada el carrito invitado.
7. Commit.
8. Rollback ante cualquier error.

---

# Transacciones

Usar transacciones para operaciones que deban ser atómicas.

Ejemplos:

- Fusionar carrito guest con usuario.
- Crear pedido + detalle + dirección histórica.
- Registrar pago y actualizar estado cuando ambas acciones deban ocurrir juntas.

Patrón:

```php
$pdo->beginTransaction();

try {
    // operaciones
    $pdo->commit();
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}
```

Compatible con PHP 7.4.

---

# Logging

Cada request ya recibe:

```text
X-Request-ID
```

Los logs se escriben en:

```text
storage/logs/app.log
storage/logs/error.log
```

No utilizar `echo`, `var_dump`, `print_r` ni stack traces para depuración en producción.

Si ocurre una excepción inesperada:

- Registrar internamente el error.
- Devolver un mensaje genérico y seguro al usuario.

No exponer:

- SQLSTATE.
- passwords.
- tokens.
- rutas del servidor.
- stack traces.
- contenido de `.env`.

---

# Rendimiento

Cada endpoint debe diseñarse para hacer las mínimas consultas razonables.

Evitar N+1:

```text
1 consulta de productos
+ 1 consulta por cada producto para precio
+ 1 consulta por cada producto para imagen
+ ...
```

Preferir JOINs/consultas agrupadas o consultas por lote.

Toda lista debe tener paginación y límites máximos definidos por el servidor.

Nunca confiar en un `limit` ilimitado enviado por el cliente.

---

# Índices

Antes de introducir una consulta frecuente nueva:

1. Revisar `WHERE`.
2. Revisar `JOIN`.
3. Revisar `ORDER BY`.
4. Confirmar que las columnas correspondientes tengan índices útiles.
5. Para consultas importantes, revisar con `EXPLAIN`.

No agregar índices a ciegas; cada índice también tiene costo de escritura.

---

# Plantilla para pedirle a Codex un endpoint nuevo

Copiar y completar:

```text
Trabaja sobre el proyecto StoreApi existente.

IMPORTANTE:
- El runtime web es PHP 7.4.33. No uses sintaxis ni funciones exclusivas de PHP 8+.
- Respeta la arquitectura existente: Front Controller -> Router -> routes/api.php -> Controller -> Service -> Repository -> PDO.
- No crees archivos PHP públicos independientes.
- Usa Connection::get() para PDO.
- Usa prepared statements siempre.
- Usa Request::json() para bodies JSON.
- Usa Response::success() y Response::error().
- No expongas errores SQL ni stack traces.
- No confíes en IDs de usuario, precios, permisos o datos sensibles enviados por el frontend.
- Mantén compatibilidad con RequestMiddleware, SecurityMiddleware y CorsMiddleware.
- Si la operación toca varias tablas y debe ser atómica, usa una transacción.
- Agrega índices únicamente si la consulta concreta lo requiere.

Necesito crear este endpoint:
[MÉTODO] /StoreApi/v1/[RUTA]

Objetivo:
[DESCRIBIR QUÉ DEBE HACER]

Entrada esperada:
[JSON / QUERY PARAMS / PATH PARAMS]

Tablas involucradas:
[TABLAS]

Reglas de negocio:
[REGLAS]

Respuesta esperada:
[DESCRIBIR]

Entrega:
1. Archivos nuevos a crear.
2. Archivos existentes a modificar.
3. Código completo de cada archivo.
4. SQL/índices necesarios, si aplica.
5. Ejemplos curl para probar éxito y errores.
6. Explica brevemente el flujo de la petición.
```

---

# Ejemplo: solicitud a Codex para login

```text
Trabaja sobre el proyecto StoreApi existente.

El runtime web es PHP 7.4.33.
Respeta Front Controller -> Router -> Controller -> Service -> Repository.
No crees login.php ni endpoints PHP aislados.

Crea:
POST /StoreApi/v1/auth/login

Body JSON:
{
  "correo": "cliente@correo.com",
  "password": "..."
}

Tablas:
- web_usuarios
- web_sesiones

Reglas:
- Normalizar/validar correo.
- Buscar usuario por correo con prepared statement y LIMIT 1.
- Verificar estado y bloqueado_hasta.
- Usar password_verify().
- Incrementar intentos_fallidos cuando corresponda.
- En éxito poner intentos_fallidos = 0 y actualizar ultimo_login_at.
- Crear token con random_bytes().
- Guardar SHA-256 del token en web_sesiones.
- Nunca devolver el hash.
- Crear cookie HttpOnly, Secure, SameSite=Lax.
- No revelar si el correo existe o si fue la contraseña lo incorrecto.
- Registrar fallos relevantes en logs sin almacenar password.
- Usar transacción si la creación de sesión y actualización del usuario deben ser atómicas.

Devuelve código completo compatible con PHP 7.4 para Controller, Service, Repository, registro de ruta y cualquier helper/middleware estrictamente necesario.
```

Este documento debe considerarse el contrato base de desarrollo de `StoreApi` hasta que se actualice el runtime del dominio a PHP moderno.
