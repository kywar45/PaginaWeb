<?php

namespace App\Repositories;

class BranchRepository
{
    private $pdo;

    public function __construct(\PDO $pdo) { $this->pdo = $pdo; }

    public function listActive()
    {
        return $this->pdo->query('SELECT id, nombre, direccion, codigo_postal, telefono,
            horario_atencion, google_maps_url, latitud, longitud
            FROM web_sucursales
            WHERE estatus = 1
            ORDER BY posicion ASC, id ASC')->fetchAll();
    }
}
