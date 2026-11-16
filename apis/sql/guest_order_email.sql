CREATE TABLE IF NOT EXISTS web_order_emails (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  folio_id INT NOT NULL,
  cliente_id INT UNSIGNED NOT NULL,
  recipient VARCHAR(150) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  message_html MEDIUMTEXT NOT NULL,
  subject VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  sent_at DATETIME DEFAULT NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_order_confirmation (folio_id),
  UNIQUE KEY uk_order_email_token (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
