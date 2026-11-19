# Crear cuenta desde el correo del pedido

El correo de una compra invitada ofrece **Crear mi cuenta** solo si el correo
no existe en `usuario`, incluyendo cuentas pendientes de confirmar. Se comparan
correos con `LOWER(TRIM(correo))`. Si existe, se muestra **Iniciar sesión**.
La comprobación se repite al abrir el enlace y dentro de la transacción de alta,
con el mismo bloqueo por correo del registro habitual y su índice único.
Nunca se cambia la contraseña ni los datos de una cuenta existente.

El enlace abre `/crear-cuenta-pedido` y pide únicamente contraseña y confirmación.
Usa un token aleatorio separado del PDF, guardado como SHA-256, de un solo uso
y con vigencia de 7 días desde que se prepara el correo. Abrirlo o previsualizarlo
no crea usuarios. Solo enviar el formulario crea la cuenta y consume el token.
El enlace recibido por correo acredita el acceso a ese buzón: la cuenta queda
confirmada, pero no se inicia sesión automáticamente. El login conserva su
fusión habitual del carrito.

Los datos se copian de `web_pedidos_envio` del folio autorizado por el token.
No se aceptan correo, domicilio, identificadores ni privilegios del navegador.
El RFC queda vacío y VIP mantiene su valor predeterminado normal. La cuenta,
el consumo del enlace y la vinculación de ese único folio se confirman juntos;
un fallo revierte todo. Los demás pedidos del mismo correo no se vinculan.

La relación queda en `web_guest_registrations.usuario_id`: no se reescriben
folios, productos ni importes históricos. La cuenta puede consultar ese pedido
y su PDF; los enlaces originales de invitado continúan funcionando. Conservar
las filas utilizadas de esta tabla: también son la relación de propiedad.

## Publicación

Antes de subir PHP, ejecutar `sql/guest_registration.sql` en la base de StoreApi.
También deben existir `web_pedidos_envio`, `web_order_emails` y el índice único
`uk_usuario_correo` descrito en `REGISTRO.md`. No se ejecuta SQL automáticamente.
La nueva tabla es necesaria también para las consultas de pedidos de cuentas.

Subir estos archivos actualizados dentro de StoreApi:

```text
src/Controllers/RegistrationController.php
src/Repositories/RegistrationRepository.php
src/Repositories/UserRepository.php
src/Repositories/OrderPdfRepository.php
src/Services/RegistrationService.php
src/Services/GuestOrderEmailService.php
src/Services/OrderPdfService.php
```

Agregar estas dos rutas al bloque de autenticación de `routes/api.php`, usando
su factoría `$registrationController` existente:

```php
$router->post('/v1/auth/validar-registro-pedido', function () use ($registrationController): void {
    $registrationController()->validateGuest();
});
$router->post('/v1/auth/registro-pedido', function () use ($registrationController): void {
    $registrationController()->registerGuest();
});
```

Publicar también el contenido compilado de `dist/spa/` con la nueva página.
Los correos ya enviados o preparados conservan su contenido y enlaces originales.
La invitación se añade a nuevas confirmaciones; no reenvía correos anteriores.
Un envío pendiente conserva la misma invitación al reintentar. Los cuerpos
pendientes contienen enlaces privados y deben protegerse igual que el PDF.

## Verificación local sin correos reales

```text
php -d extension=pdo_sqlite apis/tests/guest-registration.php
php -d extension=pdo_sqlite apis/tests/guest-order-email.php
php apis/tests/order-pdf.php
php -d extension=pdo_sqlite apis/tests/guest-order-pdf.php
node apis/tests/account-orders-sql.mjs
node apis/tests/account-summary-sql.mjs
node apis/tests/order-pdf-sql.mjs
npm run build
```

La integración prueba enlaces inválidos, vencidos y usados, datos falsificados,
rollback después del alta, diferencias de mayúsculas y espacios, una cuenta
creada después de enviar la invitación, propiedad del pedido y ambos PDF.
SQLite sustituye los bloqueos MySQL en las pruebas; no simula concurrencia real.
