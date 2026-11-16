-- Required before registering guest folios: a guest has no usuario record.
ALTER TABLE folios MODIFY COLUMN id_usuario INT(250) NULL DEFAULT NULL;
