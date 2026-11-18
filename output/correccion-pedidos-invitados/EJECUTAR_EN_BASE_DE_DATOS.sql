-- Ejecutar en la base de StoreApi antes de subir los PHP.
-- Se puede ejecutar nuevamente si ya se aplicó: no borra ni cambia registros.
SET @sn_guest_column_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'web_clientes_invitados'
          AND COLUMN_NAME = 'checkout_request_hash'),
    'SELECT 1',
    'ALTER TABLE web_clientes_invitados ADD COLUMN checkout_request_hash CHAR(64) DEFAULT NULL'
);
PREPARE sn_guest_column_stmt FROM @sn_guest_column_sql;
EXECUTE sn_guest_column_stmt;
DEALLOCATE PREPARE sn_guest_column_stmt;

SET @sn_guest_index_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'web_clientes_invitados'
          AND INDEX_NAME = 'uk_guest_checkout_request'),
    'SELECT 1',
    'ALTER TABLE web_clientes_invitados ADD UNIQUE KEY uk_guest_checkout_request (checkout_request_hash)'
);
PREPARE sn_guest_index_stmt FROM @sn_guest_index_sql;
EXECUTE sn_guest_index_stmt;
DEALLOCATE PREPARE sn_guest_index_stmt;
