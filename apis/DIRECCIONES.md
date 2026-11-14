# Mis direcciones

La sección de la cuenta utiliza `direccion_envio`. No requiere cambiar la tabla.

Copiar al servidor, respetando las rutas dentro de `StoreApi`:

```text
src/Controllers/AddressController.php
src/Services/AddressService.php
src/Repositories/AddressRepository.php
```

Agregar las rutas de direcciones incluidas en `apis/routes/api.php` al archivo
de rutas del servidor (o subirlo completo si se mantiene sincronizado).
Publicar también el contenido actualizado de `dist/spa/` en el sitio.

```text
GET  /StoreApi/v1/auth/direcciones
POST /StoreApi/v1/auth/direcciones
```

Ambas requieren la sesión actual. El servidor toma `id_usuario` de esa sesión;
nunca acepta la identidad enviada desde el navegador. La lista contiene solamente
direcciones activas del cliente. Las escrituras comprueban también la propiedad
de la dirección, y responden 404 si no pertenece a la cuenta o ya está desactivada.

El POST recibe JSON con `accion`: `guardar`, `eliminar` o `predeterminada`.
Guardar admite `id` para editar; sin `id` crea una nueva. Campos:
`nombre`, `apellido`, `direccion`, `colonia`, `ciudad`, `estado`, `cp`, `telefono`
y el booleano `defecto`. Apellido es opcional; CP debe contener cinco dígitos.
Eliminar y predeterminada solo necesitan acción e ID.

Eliminar marca `activo = 0` y `defecto = 0`, conservando el registro.
La primera dirección se convierte automáticamente en predeterminada.
Las modificaciones normalizan a una única predeterminada activa, y eligen un
reemplazo si se elimina la anterior. Una transacción con bloqueo del usuario
serializa operaciones concurrentes y revierte la operación completa si falla.
La tabla debe conservar su motor InnoDB.

Estas direcciones administran la libreta del cliente; no cambian direcciones
ni documentos de pedidos anteriores.

Validación local (SQLite solo para la prueba, no para producción):

```powershell
php -d extension=pdo_sqlite apis/tests/account-addresses.php
```
