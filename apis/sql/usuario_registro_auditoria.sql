-- Solo consulta. Resolver estos duplicados antes de agregar el índice único.
-- No borrar ni fusionar cuentas automáticamente: pueden tener pedidos y carritos.
SELECT LOWER(TRIM(correo)) AS correo_normalizado, COUNT(*) AS cuentas,
       GROUP_CONCAT(id ORDER BY id) AS ids
FROM usuario
GROUP BY LOWER(TRIM(correo))
HAVING COUNT(*) > 1;

-- Estas cuentas seguirán bloqueadas por la regla solicitada, sin inventar confirmaciones.
SELECT COUNT(*) AS cuentas_sin_confirmacion
FROM usuario WHERE token_registro IS NULL OR TRIM(token_registro) = '';
