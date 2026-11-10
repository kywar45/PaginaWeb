<?php

namespace App\Services;

use App\Repositories\CategoryRepository;

class CategoryService
{
    private const ICON_BASE_PATH = 'iconos/';

    private $categories;

    public function __construct(CategoryRepository $categories)
    {
        $this->categories = $categories;
    }

    public function listCategories($categoryStatus, $subcategoryStatus)
    {
        $rows = $this->categories->listWithSubcategories($categoryStatus, $subcategoryStatus);
        $indexed = array();
        $items = array();

        foreach ($rows as $row) {
            $categoryId = (int) $row['id_categoria'];

            if (!array_key_exists($categoryId, $indexed)) {
                $indexed[$categoryId] = count($items);
                $items[] = array(
                    'id_categoria' => $categoryId,
                    'id_depa' => (int) $row['id_depa'],
                    'nombre_categoria' => $row['nombre_categoria'],
                    'estatus_categoria' => $row['estatus_categoria'],
                    'position' => $row['categoria_position'] === null ? null : (int) $row['categoria_position'],
                    'icon' => $row['icon'],
                    'icon_url' => $this->iconUrl($row['icon']),
                    'emoji' => $row['emoji'],
                    'subcategorias' => array(),
                );
            }

            if ($row['id_subcategoria'] !== null) {
                $items[$indexed[$categoryId]]['subcategorias'][] = array(
                    'id_subcategoria' => (int) $row['id_subcategoria'],
                    'id_categoria' => $categoryId,
                    'nombre_subcategoria' => $row['nombre_subcategoria'],
                    'estatus_subcategoria' => $row['estatus_subcategoria'],
                    'position' => $row['subcategoria_position'] === null ? null : (int) $row['subcategoria_position'],
                );
            }
        }

        return array(
            'categorias' => $items,
        );
    }

    private function iconUrl($filename)
    {
        if ($filename === null || trim((string) $filename) === '') {
            return null;
        }

        return self::ICON_BASE_PATH . rawurlencode((string) $filename);
    }
}
