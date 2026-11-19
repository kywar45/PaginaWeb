# Registro y confirmación de correo

## Regla de acceso

Una cuenta con `token_registro` vacío o NULL no puede iniciar sesión ni usar una
sesión anterior. Las cuentas existentes con un token no vacío siguen funcionando.
El registro nuevo deja ese campo vacío. Al confirmar el enlace se llena con el
hash de confirmación y se permite iniciar sesión; confirmar no inicia sesión
automáticamente. Se conserva la fusión del carrito al iniciar sesión.

Solo se insertan los campos indicados de `usuario`, con contraseña protegida por
`password_hash()`, `orden = '0'`, `token_sesion = ''` y la fecha actual del servidor.
`tipo`, `nvp` y `obsequio` no se leen ni se escriben. Deben admitir NULL por defecto,
como se indicó, antes de activar el registro. El usuario VIP lo decide el campo
del servidor `user_vip`, nunca el navegador; las cuentas nuevas usan su default 0.
El RFC es opcional. El resto de datos personales y de domicilio es obligatorio.

## Base de datos: antes de subir la API

1. Ejecutar `sql/usuario_registro_auditoria.sql` (solo lectura). La primera consulta
   debe devolver cero filas. Si encuentra correos duplicados, revisar esas cuentas
   antes de continuar; no borrarlas ni fusionarlas automáticamente porque tienen
   pedidos, sesiones y carritos asociados.
2. Con cero duplicados, ejecutar una sola vez `sql/usuario_registro.sql`.
   Normaliza correos y crea `uk_usuario_correo` para impedir duplicados en la BD,
   incluso entre solicitudes concurrentes.
3. Ese mismo script crea `web_registro_confirmaciones`, la tabla auxiliar de
   tokens pendientes. No reutiliza `token_reset` ni `expira_reset`.

La tabla auxiliar guarda únicamente el hash del token y fechas de caducidad,
confirmación y aceptación del correo. El token original solo se entrega por email,
no en la respuesta del registro. Los enlaces vencen en 24 horas. Reenviar genera
uno nuevo e invalida el anterior. Un enlace ya utilizado se rechaza antes de
mostrar el botón; no puede volver a confirmar la cuenta.

Si el registro devuelve 503 y el log indica `driver=1146 missing_table`, comprobar
que `web_registro_confirmaciones` existe en la misma base de datos de `usuario`.
Si falta, ejecutar solo `sql/registro_confirmaciones.sql`. Es idempotente y no
recrea el índice único. Esto permite completar la tabla si la ejecución del script
general se interrumpió, por ejemplo porque el índice ya existía. Si ese SQL falla,
conservar el error exacto de MySQL para diagnosticar la creación de la tabla.

## Archivos a subir a StoreApi

```text
src/Controllers/RegistrationController.php
src/Repositories/RegistrationRepository.php
src/Repositories/UserRepository.php
src/Services/RegistrationData.php
src/Services/RegistrationLimiter.php
src/Services/RegistrationService.php
src/Services/RegistrationEmailService.php
src/Services/AuthService.php
src/Services/GuestOrderEmailService.php
```

Agregar solo este bloque dentro del `return function (Router $router): void { ... }`
de `routes/api.php`, junto a las rutas de autenticación. No necesita nuevos `use`:

```php
$registrationController = function () {
    $pdo = Connection::get();
    return new \App\Controllers\RegistrationController(
        new \App\Services\RegistrationService(
            new \App\Repositories\RegistrationRepository($pdo),
            new \App\Services\RegistrationEmailService(
                new \App\Services\GuestOrderEmailService($pdo)
            )
        )
    );
};
$router->post('/v1/auth/register', function () use ($registrationController): void {
    $registrationController()->register();
});
$router->post('/v1/auth/confirmar-correo', function () use ($registrationController): void {
    $registrationController()->confirm();
});
$router->post('/v1/auth/reenviar-confirmacion', function () use ($registrationController): void {
    $registrationController()->resend();
});
```

Publicar también el contenido de `dist/spa/`, que incluye el registro renovado,
la pantalla `/confirmar-correo` y el reenvío desde registro, login y confirmación.
La página de confirmación requiere pulsar el botón; abrir el enlace por GET no
modifica la cuenta. Así una previsualización del correo no confirma por sí sola.

## Correo y recuperación

Reutiliza el transporte de correo y los assets de marca que ya funcionan en pedidos.
Mantener `ORDER_MAIL_ENABLED=1` y el remitente válido en `ORDER_MAIL_FROM`.
`ORDER_MAIL_AUTO_ENABLED` controla los pedidos y no desactiva la confirmación de
cuentas. No hacen falta contraseñas SMTP nuevas ni activar el correo de prueba.

Un fallo de correo no elimina la cuenta: el formulario muestra el aviso y permite
reenviar indicando correo y contraseña. El servidor valida esa contraseña antes
de enviar, tanto para cuentas nuevas como para cuentas antiguas pendientes.
Los errores internos se registran con referencia en `storage/logs/order-mail.log`
o en el log de PHP, sin publicar contraseñas ni enlaces privados.

`storage/registration/` debe ser escribible; se crea automáticamente para limitar
solicitudes: un minuto entre solicitudes al mismo correo, tres por correo/hora,
diez por IP/hora y quinientas totales/hora. Solo se guardan hashes y tiempos.

El domicilio se guarda en `usuario` según el esquema solicitado; no se copian ni
modifican automáticamente registros históricos de `direccion_envio` o pedidos.

## Comprobación

```powershell
php -d extension=pdo_sqlite apis/tests/registration.php
php apis/tests/vip-session.php
php apis/tests/auth-cart-merge.php
npm run build
```

No se envían correos reales en estas pruebas. Tras publicar: crear una cuenta con
un correo propio, comprobar que el login queda bloqueado, confirmar desde el email
y luego iniciar sesión. Un segundo registro con ese correo debe ser rechazado.
