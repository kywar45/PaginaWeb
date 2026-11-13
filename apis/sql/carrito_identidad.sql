-- Ejecutar una sola vez antes de subir la API de carrito.
-- Las filas existentes conservan guest_hash = NULL.
ALTER TABLE carrito
    ADD COLUMN guest_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL DEFAULT NULL,
    ADD INDEX idx_carrito_invitado_activo (guest_hash, activo);
