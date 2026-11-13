# Carrito persistente

## Instalacion

1. Ejecutar una sola vez `sql/carrito_identidad.sql` en la base que contiene `carrito`.
2. Subir estos archivos, respetando las rutas dentro de StoreApi:

```text
routes/api.php
src/Controllers/CartController.php
src/Repositories/CartRepository.php
src/Repositories/ProductRepository.php
src/Services/CartIdentity.php
src/Services/CartPricing.php
src/Services/CartService.php
src/Services/ProductService.php
src/Services/AuthService.php
```

Se requiere el `AuthService.php` actualizado que fusiona el carrito al iniciar sesion. Subir despues el contenido de `dist/spa` al frontend. Si ya se instalo `guest_hash`, esta actualizacion no requiere mas SQL.

La tabla existente se conserva. Solo se agrega `guest_hash` y su indice. Las filas antiguas quedan con `guest_hash = NULL`. Los campos heredados `huella` y `color` se conservan; las nuevas filas usan `huella = 0`, `color = ''`, ya que `id_producto` identifica la variante. No se cambia el color de filas existentes.

## Propiedad y persistencia

- Registrado: `id_usuario` procede exclusivamente de la sesion validada por AuthService; `guest_hash IS NULL`.
- Invitado: `id_usuario = 0` y SHA-256 del token aleatorio de 256 bits de la cookie `sn_guest_cart`. No se usa la IP ni una huella del dispositivo. Cada navegador tiene un carrito distinto. Cookie HttpOnly, renovada por un ano al acceder; borrar cookies elimina el acceso al carrito del visitante.
- Cada identidad mantiene su carrito. Al iniciar sesion se fusiona el carrito de la cookie de visitante con el de la cuenta: se suman cantidades por `id_producto`, se conservan colores distintos y se recalculan todos los precios segun la cuenta. Solo se vacia el visitante en la misma transaccion que guarda la cuenta. Al cerrar sesion el visitante empieza vacio, sin copiar el carrito privado de la cuenta.
- La fusion usa bloqueos de ambos propietarios en orden estable; repetirla con el visitante ya vacio no duplica productos. Si falla o excede 9999 piezas de un producto/300 productos diferentes, se revierten ambos carritos completos. La sesion sigue iniciada y el aviso permite reintentar; no se recortan cantidades silenciosamente.
- Los agotados/inactivos fusionados siguen visibles, excluidos del total para normales; VIP puede comprar agotados activos. El aviso recomienda revisar los productos no disponibles.
- El viejo carrito de localStorage ya no es la fuente de datos y no se importa automaticamente, pues no identifica a su propietario.
- Las cookies usan los mismos ajustes `SESSION_COOKIE_PATH`, `SESSION_COOKIE_DOMAIN`, `SESSION_SECURE` y `SESSION_SAMESITE` que auth. Las solicitudes usan credenciales. El servidor debe conservar su configuracion CORS de origenes permitidos y credenciales, ya usada para login; POST exige JSON.

## API

`GET /v1/carrito`: recupera y recalcula el carrito completo con precios actuales.

`POST /v1/carrito`, Content-Type `application/json`:

```json
{"accion":"agregar","id_producto":123,"cantidad":2}
```

Acciones: `agregar` suma piezas, `cantidad` fija la cantidad absoluta (1–9999), `eliminar` desactiva la fila. No se aceptan precios ni identidades del navegador. Maximo 300 productos distintos nuevos por carrito. Las filas heredadas duplicadas del mismo producto se consolidan al recuperar el carrito.

`{"accion":"fusionar"}` reintenta la fusion para una sesion autenticada (401 para visitantes). Los propietarios se resuelven solo con las cookies del servidor. Respuesta adicional `fusion`: `fusionado`, `piezas`, `no_disponibles`. Login incluye `carrito_fusion` con estos datos, o `pendiente` y `mensaje` cuando el login tuvo exito pero la fusion no pudo completarse.

Respuesta: `items`, `total`, `cantidad_total`, `tipo_cliente`, `usuario_id`. Cada item incluye `quantity`, `precio_final`, `subtotal`, `tipo_precio`, `piezas_codigo`, `puede_comprar` y sus datos de producto. `precio_costo` nunca sale al navegador. Cada consulta o cambio guarda `precio` y `cantidad` actuales en la BD; el total se calcula en centavos. Quitar una linea usa `activo = 0`.

El resumen incluye `ahorro_total` y `total_sin_descuentos`. El ahorro se calcula por linea: `max(0, precio_normal - precio_final) * cantidad`, en centavos, y solo suma productos comprables. Incluye ofertas, mayoreo, caja y VIP sin acumular descuentos dos veces. Cada item incluye tambien su `ahorro_total`. No se requiere otro cambio SQL. Subir `src/Services/CartService.php` actualizado y el frontend para mostrarlo.

El boton del resumen movil abre `/checkout`, la pantalla de revision del pedido (productos, cantidades, total y ahorro). Esta pantalla no crea pedidos ni procesa pagos; la integracion de envio y pago se implementara cuando se definan esos pasos.

## Precios

- Sumar las cantidades guardadas de productos comprables con el mismo codigo (sin distinguir mayusculas y espacios exteriores). Los codigos vacios no se agrupan entre productos distintos.
- Cada variante conserva su `id_producto`, cantidad y precio propios. Su suma por codigo determina si alcanza su `topem`/`preciom` o `topec`/`precioc`. Caja tiene prioridad cuando se alcanza su umbral; precios o umbrales inexistentes/no positivos no se aplican.
- Los agotados/inactivos no comprables no suman para umbrales ni total; se conservan visibles para quitarlos. No se puede aumentar su cantidad. Ignorar `oculto`.
- Normal/invitado con oferta valida conserva oferta, sin mayoreo/caja. Las piezas en oferta comprables tambien suman al codigo.
- VIP conserva costo por 1.15, o la oferta menor, y puede agregar agotados activos. No usa escalones de mayoreo/caja.
- Al agregar, editar o eliminar se recalculan todas las variantes del carrito, tambien cuando se baja de un umbral.

Los cambios del mismo propietario se serializan con `GET_LOCK` de MySQL y una transaccion InnoDB. La vista previa de producto suma las piezas seleccionadas a las existentes del codigo; la API valida y aplica el precio definitivo al guardar.

## Verificacion

```text
php apis/tests/cart.php
node apis/tests/cart.mjs
```

Estas pruebas usan repositorios en memoria para los casos de negocio; no conectan a la BD de produccion. Comprobar tras subir: dos navegadores invitados independientes, recuperar carrito registrado, agregar colores hasta alcanzar ambos umbrales, quitar piezas y verificar reajuste de todas las filas. Los futuros endpoints de pedido/pago deben volver a validar los precios y permisos al confirmar.
