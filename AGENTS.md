# Convenciones de interfaz

- Todas las páginas interiores deben usar `src/components/PageBreadcrumbs.vue` para la navegación superior. No crear otras variantes de breadcrumbs o botones «Volver» en sus cabeceras.
- Usar una fila compacta sin recuadro ni subtítulo: flecha rosa sin círculo, enlace Inicio, padres opcionales y página actual. Pasar `title`, `backTo` y `parents` cuando corresponda.
- La portada no necesita breadcrumbs. Los títulos de formularios, contenido y diálogos pueden conservarse; no son cabeceras de navegación.
- Conservar la vista de escritorio al hacer cambios solicitados exclusivamente para móvil.
- Productos: ignorar `oculto`. `active = 1` determina si se publica; `estatus = 1` significa disponible y `estatus = 0` agotado. El inicio solicita únicamente activos disponibles (`solo_disponibles=1`); las páginas interiores muestran todos los activos y usan `ProductAvailability.vue` para identificar agotados.
