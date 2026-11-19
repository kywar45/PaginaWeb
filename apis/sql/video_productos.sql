CREATE TABLE IF NOT EXISTS web_video_productos (
  video_id INT UNSIGNED NOT NULL,
  producto_id INT NOT NULL,
  posicion INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (video_id, producto_id),
  KEY idx_video_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
