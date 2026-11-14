<?php

namespace App\Services;

use App\Repositories\ShippingCarrierRepository;

class ShippingCarrierService
{
    private $carriers;

    public function __construct(ShippingCarrierRepository $carriers) { $this->carriers = $carriers; }

    public function listing()
    {
        $items = array();
        foreach ($this->carriers->listActive() as $row) {
            $items[] = array('id' => (int) $row['id'], 'nombre' => trim((string) $row['nombre']), 'envio' => (int) $row['envio']);
        }
        return array('metodos_entrega' => $items);
    }
}
