# Confirmación por correo para pedidos invitados

## Prueba manual y diagnóstico

El checkout ofrece una prueba independiente al abrir:

`https://www.importadorasupernova.com/ImportadoraSupernova/checkout?prueba_correo=1`

Agregar un producto al carrito, continuar como invitado y capturar nombre y correo.
El botón **Enviar correo de prueba** usa ese correo; no crea clientes, folios,
productos ni confirmaciones en cola. No requiere completar la entrega.
El panel se muestra solamente con `prueba_correo=1`.

En la configuración privada del API, cargada por su bootstrap, activar:

```dotenv
ORDER_MAIL_TEST_ENABLED=1
ORDER_MAIL_ENABLED=1
ORDER_MAIL_AUTO_ENABLED=0
ORDER_MAIL_FROM=no-reply@importadorasupernova.com
```

Las variables se leen con `api_env()` cuando está disponible; de lo contrario
se consultan `getenv()`, `$_ENV` y `$_SERVER`. No ponerlas en variables `VITE_`.
`ORDER_MAIL_AUTO_ENABLED=0` suspende el envío al procesar y también el worker CLI.
No borra las confirmaciones pendientes ni modifica pedidos.
El modo de prueba viene desactivado por defecto en el servidor.

Subir estos archivos dentro de StoreApi:

```text
src/Services/GuestOrderEmailService.php
src/Controllers/OrderMailTestController.php
src/Controllers/GuestOrderController.php
tools/send-order-emails.php
```

Agregar únicamente esta ruta al `return function (Router $router): void { ... }`
del `routes/api.php` que funciona en el servidor:

```php
$router->post('/v1/checkout/invitado/correo/prueba', function (): void {
    $pdo = Connection::get();
    (new \App\Controllers\OrderMailTestController(
        new \App\Services\GuestOrderEmailService($pdo)
    ))->send();
});
```

No necesita nuevos `use` ni SQL para la prueba. Publicar también el contenido
actualizado de `dist/spa/`. El servidor necesita permisos de escritura en
`storage/mail-test/` (se crea automáticamente) para limitar las pruebas:
una por minuto por cookie, tres por hora por destino, cinco por IP y veinte
en total por hora. Se guardan hashes y tiempos, sin el correo ni los datos del pedido.

El resultado incluye código, referencia, remitente y estado del envío automático:

- `MAIL_ACCEPTED`: PHP aceptó el mensaje. No acredita entrega en el buzón.
- `MAIL_DISABLED`: el envío está desactivado por configuración.
- `MAIL_FROM_INVALID`: revisar el remitente configurado.
- `MAIL_UNAVAILABLE`: `mail()` no está disponible en PHP.
- `MAIL_REJECTED` / `MAIL_EXCEPTION`: consultar el registro del servidor.

Los detalles internos y advertencias de PHP quedan en
`StoreApi/storage/logs/order-mail.log` si esa carpeta existe y es escribible;
en caso contrario, en el registro de errores de PHP del hosting. El frontend
solo recibe el diagnóstico resumido y su referencia; nunca rutas del servidor,
credenciales, cuerpo del mensaje ni enlaces privados de otros pedidos.
Si PHP acepta el correo y no llega, revisar spam y las trazas de entrega del
hosting antes de cambiar a SMTP. Este código utiliza `mail()`, no SMTP autenticado.

Al confirmar la recepción, configurar `ORDER_MAIL_TEST_ENABLED=0` y
`ORDER_MAIL_AUTO_ENABLED=1` para que la confirmación vuelva al procesamiento normal.
Un worker activado puede recuperar pendientes recientes; revisar la ventana
de recuperación descrita abajo antes de reanudarlo.

Pruebas locales sin entrega real:

```text
php apis/tests/order-email-test.php
php apis/tests/order-email-transport.php
php -d extension=pdo_sqlite apis/tests/guest-order-email.php
node apis/tests/guest-checkout.mjs
```

## Confirmación automática existente

Ejecutar `sql/guest_order_email.sql` en la base de StoreApi. Subir `routes/api.php`,
`src/Controllers/GuestOrderController.php`, `src/Controllers/GuestOrderPdfController.php`,
`src/Services/GuestOrderEmailService.php` y `tools/send-order-emails.php`.

La implementación usa el mismo transporte PHP `mail()` y el mismo remitente del
sistema existente de recuperación: `no-reply@importadorasupernova.com`.
El envío queda habilitado por defecto al desplegar los archivos y ejecutar el SQL.
No necesita credenciales SMTP ni nuevas variables de entorno.

Opcionalmente, el bootstrap del API puede cargar estas variables para cambiar
el remitente o desactivar temporalmente el envío (`ORDER_MAIL_ENABLED=0`):

```
ORDER_MAIL_ENABLED=1
ORDER_MAIL_FROM=no-reply@importadorasupernova.com
```

Si se usará SMTP externo, sustituir el transporte mediante el callable del constructor
de `GuestOrderEmailService`; no colocar contraseñas en archivos públicos o en Vue.
No se ha enviado ningún correo desde el entorno local.

Al confirmar el pedido se crea una sola notificación por folio. Los errores de correo
no invalidan la compra. `email_status` distingue `sent` (aceptado por el transporte,
no confirmación de entrega al buzón) y `pending`. El enlace abre el PDF en cualquier
dispositivo sin sesión, vence a los 30 días y permite acceder solamente al pedido
invitado asociado. No usa identificadores predecibles como autorización.
La URL usa el dominio de producción fijo, nunca el Host enviado por el navegador.
El contenido pendiente contiene el enlace privado: proteger base y respaldos.
Después de enviarlo se elimina el cuerpo de la cola, conservando el hash del token.

El envío inicial ocurre al registrar el pedido, sin necesitar cron. Para reintentar
automáticamente los envíos pendientes, programar cada cinco minutos con el bootstrap real de StoreApi que carga el autoloader,
variables privadas y conexión. Este repositorio no incluye ese bootstrap; no usar el
front controller HTTP porque despacha rutas al incluirlo:

```
php /ruta/StoreApi/tools/send-order-emails.php /ruta/StoreApi/bootstrap.php
```

El proceso reintenta pendientes hasta diez veces y recupera confirmaciones faltantes
de pedidos de las últimas 24 horas que ya tienen productos. Activarlo al desplegar
puede enviar confirmaciones de esos pedidos recientes. Revisar antes esa ventana.
El bloqueo evita envíos concurrentes; un cierre del proceso justo después de la
aceptación del proveedor y antes de guardar `sent_at` puede ocasionar un duplicado.
No existe confirmación de pago: el texto informa recepción y seguimiento por ventas.

Verificación local sin envío real:
`php -d extension=pdo_sqlite apis/tests/guest-order-email.php`.
