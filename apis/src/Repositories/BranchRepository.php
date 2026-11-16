<?php

namespace App\Repositories;

class BranchRepository
{
    private $pdo;

    public function __construct(\PDO $pdo) { $this->pdo = $pdo; }

    public function listActive()
    {
        return $this->pdo->query('SELECT id, nombre, direccion, NULL AS codigo_postal, phone AS telefono,
            horario_atencion, google_maps_url, NULL AS latitud, NULL AS longitud
            FROM admin_tiendas
            WHERE store = 1 AND estatus = 1
            ORDER BY id ASC')->fetchAll();
    }
}
