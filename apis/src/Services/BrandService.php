<?php

namespace App\Services;

use App\Repositories\BrandRepository;

class BrandService
{
    private $brands;

    public function __construct(BrandRepository $brands) { $this->brands = $brands; }

    public function listBrands()
    {
        $items = array();
        foreach ($this->brands->listActive() as $row) {
            $items[] = array('id' => (int) $row['id'], 'nombre' => (string) $row['nombre'],
                'logo' => $this->logoUrl(isset($row['logo']) ? $row['logo'] : null),
                'total_productos' => (int) $row['total_productos']);
        }
        return array('marcas' => $items);
    }

    private function logoUrl($value)
    {
        $path = trim((string) $value);
        if ($path === '') return null;
        if (preg_match('/^https?:\/\//i', $path)) return $path;
        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $path) || substr($path, 0, 2) === '//') return null;
        $parts = array_map(function ($part) { return rawurlencode(rawurldecode($part)); },
            explode('/', ltrim($path, '/')));
        return 'https://www.importadorasupernova.com/' . implode('/', $parts);
    }
}
