CREATE TABLE IF NOT EXISTS web_guest_registrations (
  folio_id INT NOT NULL PRIMARY KEY,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  usuario_id INT DEFAULT NULL,
  UNIQUE KEY uk_guest_registration_token (token_hash),
  KEY idx_guest_registration_user (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
