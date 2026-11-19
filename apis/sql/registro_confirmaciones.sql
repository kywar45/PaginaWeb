-- Reparación idempotente de la tabla auxiliar de confirmaciones.
-- Ejecutar en la misma base de datos que usuario. No modifica ni borra cuentas,
-- no confirma correos y no vuelve a crear el índice único de usuario.
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
