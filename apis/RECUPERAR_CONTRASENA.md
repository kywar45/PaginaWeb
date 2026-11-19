# Recuperar contraseña

Para la actualización que comprueba enlaces antes de mostrar el formulario,
consultar `ENLACES_CUENTA.md` (dos rutas nuevas y seis archivos de API).

Usa los campos existentes `usuario.token_reset` y `usuario.expira_reset`.
No necesita nuevas tablas ni ejecutar SQL. `token_reset` guarda `reset1:` más
el hash SHA-256 de un token aleatorio de 32 bytes; nunca el enlace original.
`expira_reset` se establece a 30 minutos desde la solicitud.

## Archivos para subir a StoreApi

```text
src/Controllers/PasswordResetController.php
src/Repositories/PasswordResetRepository.php
src/Repositories/UserRepository.php
src/Services/PasswordResetService.php
src/Services/PasswordResetEmailService.php
src/Services/PasswordResetLimiter.php
src/Services/RegistrationData.php
src/Services/AuthService.php
```

`RegistrationLimiter.php` y `GuestOrderEmailService.php` se reutilizan del registro
que ya funciona. Mantener `ORDER_MAIL_ENABLED=1` y el mismo remitente.
El envío automático de pedidos y el modo de pruebas no controlan esta recuperación.

Dentro del bloque que registra las rutas en tu `routes/api.php`, agregar solamente:

```php
$passwordResetController = function () {
    $pdo = Connection::get();
    return new \App\Controllers\PasswordResetController(
        new \App\Services\PasswordResetService(
            new \App\Repositories\PasswordResetRepository($pdo),
            new \App\Services\PasswordResetEmailService(
                new \App\Services\GuestOrderEmailService($pdo)
            )
        )
    );
};
$router->post('/v1/auth/olvide-contrasena', function () use ($passwordResetController): void {
    $passwordResetController()->request();
});
$router->post('/v1/auth/restablecer-contrasena', function () use ($passwordResetController): void {
    $passwordResetController()->reset();
});
```

No necesita nuevos `use`. No reemplazar todas las rutas del servidor.
Publicar el contenido actualizado de `dist/spa/`. Desde el login, «Olvidé mi
contraseña» abre `/olvide-contrasena`; el email enlaza a `/restablecer-contrasena`.

## Comportamiento

- La respuesta de solicitud es la misma para un correo existente, desconocido
  o con fallo de transporte; no revela cuáles cuentas están registradas.
- Solicitar el enlace no cambia la contraseña ni cierra la sesión actual.
- Un nuevo envío invalida el enlace anterior. Si el envío falla, se elimina solo
  el token correspondiente, sin borrar uno más nuevo de otra solicitud concurrente.
- Abrir el enlace por GET muestra el formulario; no cambia datos ni consume el token.
- El POST valida la contraseña y consume el enlace en una transacción con bloqueo.
  Un enlace vencido, usado o reemplazado no cambia la cuenta.
- Al guardar, se protege la contraseña con `password_hash()`, se ponen ambos
  campos de recuperación en NULL y se vacía `token_sesion` para cerrar sesiones.
- El login comprueba que el hash de contraseña no cambió durante la operación;
  un login validado con la contraseña anterior no puede crear una sesión después
  de un reset concurrente.
- No modifica `token_registro` ni concede VIP. Una cuenta pendiente sigue
  necesitando confirmar su correo antes de iniciar sesión.
- Tras el cambio no hay login automático. Se elimina el token de la URL del sitio.

El correo reutiliza logo, fondo rosa y transporte de Supernova. Los fallos quedan
en `storage/logs/order-mail.log` o en el registro de PHP del hosting, con referencia,
sin contraseñas ni enlaces privados. Nunca se envía la contraseña en el correo.

`storage/password-reset/` debe ser escribible; se crea automáticamente.
Mantiene límites persistentes equivalentes al registro: un minuto entre solicitudes
para el mismo correo/enlace, tres por identificador y diez por IP cada hora,
con máximo de quinientas solicitudes globales por hora. Se guardan hashes y tiempos.
Estos límites incluyen solicitudes de correo e intentos de guardar una contraseña.

## Verificación

```powershell
php -d extension=pdo_sqlite apis/tests/password-reset.php
php -d extension=pdo_sqlite apis/tests/registration.php
php apis/tests/auth-cart-merge.php
npm run build
```

Las pruebas usan una base aislada y correo simulado. Para comprobar producción,
solicitar el correo de una cuenta propia, guardar una contraseña nueva y verificar
que la anterior y una sesión abierta previamente ya no permitan entrar.
