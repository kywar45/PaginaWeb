<?php

namespace App\Services;

use App\Repositories\BannerRepository;

class BannerService
{
    private const IMAGE_BASE_URL = 'https://www.importadorasupernova.com/Banners/';

    private $banners;

    public function __construct(BannerRepository $banners)
    {
        $this->banners = $banners;
    }

    public function listBanners($tipo, $page, $limit)
    {
        // One extra row determines whether another page exists without COUNT(*).
        $rows = $this->banners->listPage($tipo, $limit + 1, ($page - 1) * $limit);
        $hasMore = count($rows) > $limit;
        $items = array();

        foreach (array_slice($rows, 0, $limit) as $row) {
            $items[] = array(
                'id' => (int) $row['id'],
                'posicion' => (int) $row['posicion'],
                'tipo' => (int) $row['tipo'],
                'nombre_web' => $row['nombre_web'],
                'nombre_movil' => $row['nombre_movil'],
                'imagen_web' => $this->imageUrl($row['nombre_web']),
                'imagen_movil' => $this->imageUrl($row['nombre_movil']),
                'redireccion_web' => $row['redireccion_web'],
                'redireccion_movil' => $row['redireccion_movil'],
            );
        }

        return array(
            'banners' => $items,
            'pagination' => array(
                'page' => $page,
                'limit' => $limit,
                'has_more' => $hasMore,
                'next_page' => $hasMore ? $page + 1 : null,
            ),
        );
    }

    private function imageUrl($filename)
    {
        if ($filename === null || trim((string) $filename) === '') {
            return null;
        }

        // The DB field is a filename, not a URL. Encode spaces, accents and #.
        return self::IMAGE_BASE_URL . rawurlencode((string) $filename);
    }
}
