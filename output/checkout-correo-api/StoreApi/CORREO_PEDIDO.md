# Confirmación por correo para pedidos invitados

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
