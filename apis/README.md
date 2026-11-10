# Endpoints Auth Para StoreApi

Esta carpeta no reemplaza tu StoreApi completo. Solo contiene los archivos de auth para copiarlos al StoreApi que ya tienes arriba en el servidor.

## Archivos para subir

Copiar estos archivos respetando la misma ruta dentro de tu StoreApi:

```text
src/Controllers/AuthController.php
src/Services/AuthService.php
src/Repositories/UserRepository.php
src/Exceptions/ApiException.php
```

El `routes/api.php` de esta carpeta ya trae tus rutas base (`/` y `/v1/health`) mas las rutas de auth:

```text
routes/api.php
```

Si tu `routes/api.php` real solo tenia esas rutas base, puedes reemplazarlo completo por este.

## Endpoints

```text
POST /StoreApi/v1/auth/login
GET  /StoreApi/v1/auth/me
POST /StoreApi/v1/auth/logout
```

Body JSON:

```json
{
  "correo": "cliente@correo.com",
  "password": "contrasena",
  "remember": true
}
```

Tambien acepta `pass`:

```json
{
  "correo": "cliente@correo.com",
  "pass": "contrasena"
}
```

## Tabla usada

```sql
SELECT id, nombre, apellido, correo, pass, tipo, nivel, user_vip
FROM usuario
WHERE correo = ?
LIMIT 1
```

Al iniciar sesion:

```sql
UPDATE usuario
SET token_sesion = ?
WHERE id = ?
LIMIT 1
```

Para recuperar la sesion:

```sql
SELECT id, nombre, apellido, correo, tipo, nivel, user_vip
FROM usuario
WHERE token_sesion = ?
LIMIT 1
```

Para cerrar sesion:

```sql
UPDATE usuario
SET token_sesion = ''
WHERE token_sesion = ?
LIMIT 1
```

`token_sesion` guarda el SHA-256 del token real. El token real se manda por cookie `HttpOnly`.

## Variables opcionales

Si ya tienes helper de env, el servicio lo usa. Si no existe, lee `getenv`, `$_ENV` o `$_SERVER`.

```text
SESSION_COOKIE=sn_session
SESSION_COOKIE_PATH=/
SESSION_COOKIE_DOMAIN=
SESSION_SECURE=true
SESSION_SAMESITE=None
SESSION_LIFETIME_MINUTES=1440
SESSION_REMEMBER_MINUTES=43200
AUTH_ALLOW_LEGACY_PASSWORDS=true
AUTH_REHASH_LEGACY_PASSWORDS=false
```

`AUTH_ALLOW_LEGACY_PASSWORDS=true` permite validar `usuario.pass` en texto exacto si el sistema viejo lo trae asi.

`AUTH_REHASH_LEGACY_PASSWORDS=false` queda apagado para no cambiar passwords viejos sin querer.

## Curl

```bash
curl -i -c cookies.txt -X POST "https://www.importadorasupernova.com/StoreApi/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"correo\":\"cliente@correo.com\",\"password\":\"contrasena\"}"

curl -i -b cookies.txt "https://www.importadorasupernova.com/StoreApi/v1/auth/me"

curl -i -b cookies.txt -X POST "https://www.importadorasupernova.com/StoreApi/v1/auth/logout"
```
