<?php

namespace App\Services;

use App\Repositories\BranchRepository;

class BranchService
{
    private $branches;

    public function __construct(BranchRepository $branches) { $this->branches = $branches; }

    public function listBranches()
    {
        $items = array();
        foreach ($this->branches->listActive() as $row) {
            $phone = trim((string) $row['telefono']);
            $digits = preg_replace('/[^0-9]/', '', $phone);
            $latitude = $this->coordinate($row['latitud'], 90);
            $longitude = $this->coordinate($row['longitud'], 180);
            $map = trim((string) $row['google_maps_url']);
            if (!filter_var($map, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $map)) {
                $query = $latitude !== null && $longitude !== null
                    ? $latitude . ',' . $longitude
                    : trim($row['direccion'] . ' ' . $row['codigo_postal']);
                $map = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($query);
            }
            $items[] = array(
                'id' => (int) $row['id'], 'nombre' => trim((string) $row['nombre']),
                'direccion' => trim((string) $row['direccion']), 'codigo_postal' => (string) $row['codigo_postal'],
                'telefono' => $phone !== '' ? $phone : null,
                'telefono_url' => $digits !== '' ? 'tel:' . (substr($phone, 0, 1) === '+' ? '+' : '') . $digits : null,
                'horario_atencion' => trim((string) $row['horario_atencion']) ?: null,
                'google_maps_url' => $map, 'latitud' => $latitude, 'longitud' => $longitude,
            );
        }
        return array('sucursales' => $items);
    }

    private function coordinate($value, $bound)
    {
        if (!is_numeric($value)) return null;
        $number = (float) $value;
        return $number >= -$bound && $number <= $bound ? $number : null;
    }
}
