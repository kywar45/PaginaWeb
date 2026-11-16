-- Guest checkout has no usuario record. Required before saving guest order lines.
ALTER TABLE registro_usuario MODIFY COLUMN id_usuario INT(250) NULL DEFAULT NULL;
