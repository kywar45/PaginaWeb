-- Ejecutar después de usuario_registro_auditoria.sql, con CERO correos duplicados.
-- Una sola vez. Si hay duplicados, resolverlos primero; no elimina usuarios.
UPDATE usuario SET correo = LOWER(TRIM(correo));
CREATE UNIQUE INDEX uk_usuario_correo ON usuario (correo);

-- token_registro permanece vacío hasta confirmar. El token pendiente vive aquí
-- únicamente como hash, con caducidad; no reutiliza los campos de recuperar contraseña.
CREATE TABLE IF NOT EXISTS web_registro_confirmaciones (
    usuario_id INT NOT NULL PRIMARY KEY,
    token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at DATETIME NOT NULL,
    requested_at DATETIME NOT NULL,
    confirmed_at DATETIME DEFAULT NULL,
    mail_accepted_at DATETIME DEFAULT NULL,
    mail_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uk_registro_token (token_hash),
    CONSTRAINT fk_registro_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
