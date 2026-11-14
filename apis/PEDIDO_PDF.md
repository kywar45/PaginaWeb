# PDF del pedido

Cada tarjeta de «Mis pedidos» abre una página del sitio en una pestaña nueva:

`/pedido/p1.<token-cifrado>/pdf` (dentro del `publicPath` del frontend).

La página carga el PDF por dentro, con la sesión del usuario, desde:

`GET /StoreApi/v1/auth/pedido-pdf?token=p1.<token-cifrado>`

El ID de `folios` se recupera del token autenticado, nunca de `?id=`.
La sesión es obligatoria y el folio debe pertenecer al usuario autenticado.
El navegador recibe el documento
con `Content-Type: application/pdf`, disposición `inline` y sin caché.

La dirección visible pertenece al frontend; no navega a `StoreApi`. El visor usa
una URL Blob en un iframe que ocupa toda la pantalla, sin cabecera propia;
los controles de impresión y descarga los proporciona el visor del navegador. Si caduca la sesión,
muestra la opción de iniciar sesión; otros errores permiten reintentar.
Las URL Blob se liberan al cambiar de pedido o salir del visor.

Se conserva el `.htaccess` existente que sirve las rutas
del frontend en modo history. Las llamadas a la API siguen siendo observables
en las herramientas de red del navegador; la autorización depende de la sesión.

## Enlaces cifrados y no modificables

**Para activar esta protección, actualizar juntos API y frontend:**

```text
src/Services/OrderPdfLink.php            (nuevo)
src/Services/AuthService.php            (actualizado)
src/Controllers/OrderPdfController.php  (actualizado)
dist/spa/                              (frontend actualizado)
```

No cambia la ruta registrada en `api.php`, ni necesita columnas nuevas.
Los enlaces numéricos anteriores dejan de funcionar. Volver a «Mis pedidos»
para obtener los enlaces nuevos.

`GET /v1/auth/pedidos` entrega `pdf_token` por cada pedido perteneciente a la
cuenta. El enlace cifra ID, usuario y caducidad mediante AES-256-GCM, con nonce
aleatorio de 12 bytes y etiqueta completa de 16 bytes. La clave se deriva con
HKDF y un contexto específico de la cookie HttpOnly de sesión, que el login
genera con 32 bytes aleatorios. No se incorpora una clave fija al código ni al
frontend. Requiere la extensión PHP OpenSSL con AES-256-GCM habilitada.
Referencia: [cifrado autenticado de PHP](https://www.php.net/manual/en/function.openssl-encrypt.php).

Los tokens duran una hora y están vinculados a la sesión y cuenta que los
solicitaron. Al volver a iniciar sesión o caducar el enlace, abrir el pedido
desde el listado nuevamente. Formato inválido, manipulación o cuenta diferente
devuelven 404; caducidad devuelve 410; ausencia de sesión devuelve 401.

El token no reemplaza la autorización: después de descifrarlo se mantiene
`folios.id = :id AND folios.id_usuario = :user_id`, y las líneas conservan
`registro_usuario.orden = :orden AND registro_usuario.id_usuario = :user_id`.
Enviar un ID distinto junto al token no cambia el pedido resuelto.

Publicar los tres archivos de API indicados y el frontend actualizado para activar
los enlaces cifrados.

## Archivos nuevos para subir a StoreApi

```text
src/Controllers/OrderPdfController.php
src/Repositories/OrderPdfRepository.php
src/Services/OrderPdfService.php
src/Services/OrderPdfLink.php
src/Services/AuthService.php
src/Pdf/OrderPdf.php
lib/fpdf/                 (carpeta completa, incluidos font/ y license.txt)
assets/logo-white.png
assets/header-store.png
```

La dependencia FPDF está incluida: no se necesita Composer ni reutilizar la
carpeta `Pdf_new` del sistema anterior. `App\Pdf` debe usar la misma carga PSR-4
de `src/` que el resto de las clases de StoreApi.

En `routes/api.php`, agregar junto a los demás `use`:

```php
use App\Controllers\OrderPdfController;
use App\Repositories\OrderPdfRepository;
use App\Services\OrderPdfService;
```

Dentro de la función que registra las rutas:

```php
$router->get('/v1/auth/pedido-pdf', function (): void {
    $pdo = Connection::get();
    $auth = new AuthService($pdo, new UserRepository($pdo));
    $orders = new OrderPdfService(new OrderPdfRepository($pdo));
    $controller = new OrderPdfController($auth, $orders);
    $controller->show();
});
```

`Connection`, `AuthService` y `UserRepository` ya están importados para las rutas
de autenticación. La ruta y los `use` ya están agregados en el archivo local.
Conservar las rutas propias del servidor al integrar los cambios.

Publicar también el frontend de `dist/spa`. No necesita cambios de esquema.

## Datos y reglas

- El documento se identifica por `folios.id` y comprueba `folios.id_usuario`
  contra la sesión, antes de leer las líneas del pedido.
- Los productos históricos salen de `registro_usuario`, enlazados
  por `orden` e `id_usuario`. No consulta precios actuales de
  `productos` ni recalcula promociones, VIP o mayoreo actuales.
- Solo se usan estos ocho campos de `registro_usuario`: `id`, `id_usuario`,
  `id_producto`, `nombre`, `codigo`, `precio`, `cantidad`, `orden`. La dirección,
  teléfono y otros campos antiguos no se consultan. El cliente se identifica con
  `folios.nombres` y el correo de la sesión. No se muestra un domicilio inventado.
- `GEL-01`, `GEL-70`, `PU-06` y `PU-08` se resumen por código en la tabla principal,
  con sus cantidades e importes totales. Las variantes se desglosan al costado:
  una columna para geles y otra para puntas, solo cuando existen en el pedido.
  Sin familias especiales, la tabla principal usa todo el ancho disponible.
- Cada variante conserva cantidad y precio propios. Si un código incluye
  precios diferentes, la fila principal indica «Varios*» y el desglose muestra
  el precio de cada variante. No usa el último precio como precio de todo el grupo.
- Filas compactas, encabezado con cliente/logo/orden y total en una franja al
  terminar la lista. Las tablas laterales continúan en otra página si es necesario,
  repitiendo la identificación del código sin duplicar cantidades.
- El encabezado completo utiliza `assets/header-store.png`, generado directamente
  desde las reglas `.sn-header--premium` de `src/css/app.scss`, incluyendo sus
  pseudo-elementos, curvas y desenfoques. Conserva el logo y los datos blancos
  sobre el fondo y la altura compacta. Subir la imagen junto con `src/Pdf/OrderPdf.php`.
  Para regenerarla al cambiar el header: `node apis/tools/render-store-header.mjs`
  (requiere Chrome o Edge en Windows; admite `CHROME_PATH`). El servidor PHP
  utiliza la imagen incluida y no necesita navegador ni Node.
- La entrega depende exclusivamente de `folios.shipping_carrier_id` unido a
  `shipping_carriers.id`. `envio = 1` indica paquetería; `envio = 0`, entrega en
  punto. Se permite consultar transportistas inactivos para pedidos históricos.
  Sin relación válida muestra «Método por confirmar». No usa los campos antiguos
  `folios.envio` ni `folios.paqueteria`.
- El importe de productos es la suma de cantidades por precios históricos,
  calculada con los cuatro decimales históricos de `precio`, redondeada al final.
  Si el precio tiene fracciones de centavo, se muestran sus cuatro decimales.
  Un ajuste explícito de redondeo reconcilia las líneas si hace falta.
  El total principal usa `folios.total`; si difiere,
  muestra la diferencia como «Ajustes registrados», sin inventar que sea un
  costo de envío o IVA. Si no hay total válido guardado, usa el de productos.
  `shipping_carriers.costo` no se trata como un cargo histórico del pedido.
- Fechas cero y `NULL` se admiten. El PDF no se presenta como factura fiscal ni
  comprobante de pago; identifica si el pedido tiene fecha de pago registrada.
- Tablas con nombres largos, acentos, páginas de continuación, cabeceras y pies
  repetidos. Archivo A4 y descarga desde el visor nativo del navegador.

## Pruebas y vista previa

```text
php tests/order-pdf.php
php tests/order-pdf-layout.php
node tests/order-pdf-sql.mjs
node tests/order-pdf-route.mjs
php tests/order-pdf-link.php
php tests/render-order-pdf.php
```

Las pruebas verifican propietario, sesiones, referencias compartidas, consultas,
transportistas, importes, precios distintos del mismo código y respuesta binaria.
Las vistas previas contienen datos ficticios identificados como demostración;
el sitio obtiene únicamente pedidos reales de la API.

Probar en el servidor con un enlace propio (PDF), un token modificado o copiado
a otra cuenta (404), el antiguo `?id=42` sin token (404) y sin sesión (401).
Confirmar también un envío y una entrega en punto con los registros
reales de `shipping_carriers`.

Para pasar del primer diseño al diseño compacto solo es necesario actualizar
`src/Pdf/OrderPdf.php`. Las consultas y la ruta siguen siendo las mismas.

## Dependencia

[FPDF 1.86 / Setasign](https://github.com/Setasign/FPDF/tree/1.8.6), distribución
oficial con licencia permisiva conservada en `lib/fpdf/license.txt`. Solo se
incluyen el generador y sus fuentes estándar. Referencia de descarga:
[fpdf.org](https://www.fpdf.org/en/download.php).
