<?php

namespace App\Repositories;

class ShippingCarrierRepository
{
    private $pdo;

    public function __construct(\PDO $pdo) { $this->pdo = $pdo; }

    public function listActive()
    {
        return $this->pdo->query('SELECT id, nombre, envio FROM shipping_carriers
            WHERE estatus = 1 AND envio IN (0, 1)
            ORDER BY envio ASC, nombre ASC, id ASC')->fetchAll();
    }
}
