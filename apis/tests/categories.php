<?php

// Standalone contract tests; no database, network or production writes.
namespace App\Http {
    class Response
    {
        public static function success($data, $status = 200)
        {
            return array('status' => $status, 'data' => $data);
        }
        public static function error($code, $message, $status)
        {
            return array('status' => $status, 'code' => $code);
        }
    }
}

namespace {
    require __DIR__ . '/../src/Exceptions/ApiException.php';
    require __DIR__ . '/../src/Repositories/CategoryRepository.php';
    require __DIR__ . '/../src/Services/CategoryService.php';
    require __DIR__ . '/../src/Controllers/CategoryController.php';

    class FakeCategories extends \App\Repositories\CategoryRepository
    {
        public $rows = array();
        public $arguments;
        public function __construct() {}
        public function listWithSubcategories($categoryStatus, $subcategoryStatus)
        {
            $this->arguments = array($categoryStatus, $subcategoryStatus);
            return $this->rows;
        }
    }

    function check($condition, $label)
    {
        if (!$condition) {
            throw new \RuntimeException($label);
        }
    }

    $repo = new FakeCategories();
    $service = new \App\Services\CategoryService($repo);
    $controller = new \App\Controllers\CategoryController($service);

    $_GET = array();
    $result = $controller->index();
    check($result['status'] === 200 && $result['data']['categorias'] === array(), 'Empty list');
    check($repo->arguments === array(null, null), 'Default filters');

    $repo->rows = array(
        array(
            'id_categoria' => '1',
            'id_depa' => '2',
            'nombre_categoria' => 'Unas',
            'estatus_categoria' => 'Activo',
            'categoria_position' => '1',
            'icon' => 'unas #1.webp',
            'emoji' => null,
            'id_subcategoria' => '10',
            'nombre_subcategoria' => 'Gel',
            'estatus_subcategoria' => 'Activo',
            'subcategoria_position' => '2',
        ),
        array(
            'id_categoria' => '1',
            'id_depa' => '2',
            'nombre_categoria' => 'Unas',
            'estatus_categoria' => 'Activo',
            'categoria_position' => '1',
            'icon' => 'unas #1.webp',
            'emoji' => null,
            'id_subcategoria' => '9',
            'nombre_subcategoria' => 'Acrilico',
            'estatus_subcategoria' => 'Activo',
            'subcategoria_position' => '1',
        ),
        array(
            'id_categoria' => '2',
            'id_depa' => '2',
            'nombre_categoria' => 'Pestanas',
            'estatus_categoria' => 'Activo',
            'categoria_position' => null,
            'icon' => null,
            'emoji' => '*',
            'id_subcategoria' => null,
            'nombre_subcategoria' => null,
            'estatus_subcategoria' => null,
            'subcategoria_position' => null,
        ),
    );

    $_GET = array('estatus_categoria' => 'Activo', 'estatus_subcategoria' => 'Activo');
    $result = $controller->index();
    $data = $result['data'];
    check($repo->arguments === array('Activo', 'Activo'), 'Status filters');
    check(count($data['categorias']) === 2, 'Category grouping');
    check($data['categorias'][0]['id_categoria'] === 1, 'Category numeric id');
    check($data['categorias'][0]['icon_url'] === 'iconos/unas%20%231.webp', 'Icon URL encoding');
    check(count($data['categorias'][0]['subcategorias']) === 2, 'Subcategories preserved');
    check($data['categorias'][0]['subcategorias'][0]['id_subcategoria'] === 10, 'Repository order preserved');
    check($data['categorias'][1]['subcategorias'] === array(), 'Category without subcategories');

    foreach (array(
        array('estatus_categoria' => ''),
        array('estatus_subcategoria' => array('Activo')),
        array('estatus_categoria' => str_repeat('a', 256)),
    ) as $query) {
        $_GET = $query;
        check($controller->index()['status'] === 422, 'Invalid query rejected');
    }

    echo "Category contract tests passed.\n";
}
