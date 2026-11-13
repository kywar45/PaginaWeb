<?php

namespace App\Repositories;

class BrandRepository
{
    private $pdo;

    public function __construct(\PDO $pdo) { $this->pdo = $pdo; }

    public function listActive()
    {
        return $this->pdo->query('SELECT m.id, m.nombre, m.logo, COUNT(p.id) AS total_productos
            FROM web_marcas m
            LEFT JOIN productos p ON p.marca = m.id AND p.active = 1
            WHERE m.estatus = 1
            GROUP BY m.id, m.nombre, m.logo
            ORDER BY m.nombre ASC, m.id ASC')->fetchAll();
    }
}
