# Validar enlaces antes de mostrar acciones

Los enlaces de recuperación y confirmación se consultan en la API al abrirlos.
Durante esa consulta se muestra `LoadingSkeleton`; no se renderizan los campos
de contraseña ni el botón de confirmar hasta recibir `valid: true`.
La consulta es de solo lectura: no consume tokens ni confirma cuentas.

- Recuperación usada, vencida o reemplazada: «Enlace no disponible», sin campos,
  con opción de solicitar otro enlace.
- Correo ya confirmado: «Tu correo ya fue confirmado», solo acceso al login.
- Confirmación vencida o reemplazada: aviso y opción de solicitar otro correo.
- Si no se puede consultar la API: aviso y reintento, sin habilitar el formulario.

Las operaciones reales vuelven a comprobar el estado dentro de la transacción.
Así, si otro navegador consume el token después de la comprobación inicial,
la segunda operación se rechaza. Un enlace de contraseña ya usado se rechaza
antes de evaluar el límite de espera; no muestra el mensaje de «otro correo».

## Archivos a actualizar dentro de StoreApi

```text
src/Controllers/PasswordResetController.php
src/Controllers/RegistrationController.php
src/Repositories/PasswordResetRepository.php
src/Repositories/RegistrationRepository.php
src/Services/PasswordResetService.php
src/Services/RegistrationService.php
```

Dentro de `routes/api.php`, después de las dos funciones existentes
`$passwordResetController` y `$registrationController`, agregar:

```php
$router->post('/v1/auth/validar-restablecimiento', function () use ($passwordResetController): void {
    $passwordResetController()->validate();
});
$router->post('/v1/auth/validar-confirmacion', function () use ($registrationController): void {
    $registrationController()->validate();
});
```

No reemplazar el archivo completo ni duplicar rutas. Actualizar API y después
publicar el contenido actualizado de `dist/spa/`. No necesita SQL ni cambiar `.env`.

Estas nuevas rutas reciben POST JSON `{ "token": "..." }`, no parámetros GET.
Sus respuestas y consultas de frontend usan `no-store`, y el frontend cancela
consultas anteriores cuando cambia de enlace o se abandona la página.

## Comprobación

```powershell
php -d extension=pdo_sqlite apis/tests/password-reset.php
php -d extension=pdo_sqlite apis/tests/registration.php
npm run build
```

Los tests verifican consultas sin efectos, caducidad, consumo único, rechazo
antes del cooldown y validación repetida bajo bloqueo antes de modificar datos.
