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
    require __DIR__ . '/../src/Repositories/BannerRepository.php';
    require __DIR__ . '/../src/Services/BannerService.php';
    require __DIR__ . '/../src/Controllers/BannerController.php';

    class FakeBanners extends \App\Repositories\BannerRepository
    {
        public $rows = array();
        public $arguments;
        public function __construct() {}
        public function listPage($tipo, $limit, $offset)
        {
            $this->arguments = array($tipo, $limit, $offset);
            return $this->rows;
        }
    }

    function check($condition, $label)
    {
        if (!$condition) {
            throw new \RuntimeException($label);
        }
    }

    $repo = new FakeBanners();
    $service = new \App\Services\BannerService($repo);
    $controller = new \App\Controllers\BannerController($service);
    $_GET = array();
    $result = $controller->index();
    check($result['status'] === 200 && $result['data']['banners'] === array(), 'Empty list');
    check($repo->arguments === array(null, 51, 0), 'Defaults and bounded lookahead');

    $row = array('id' => '281', 'posicion' => '2', 'tipo' => '1',
        'nombre_web' => 'promoción #1.jpg', 'nombre_movil' => null,
        'redireccion_web' => '/ofertas', 'redireccion_movil' => null);
    $repo->rows = array($row, $row);
    $_GET = array('page' => '2', 'limit' => '1', 'tipo' => '1');
    $result = $controller->index();
    $data = $result['data'];
    check($repo->arguments === array(1, 2, 1), 'Filter and offset');
    check(count($data['banners']) === 1 && $data['pagination']['next_page'] === 3, 'Pagination');
    check($data['banners'][0]['imagen_web'] === 'https://www.importadorasupernova.com/Banners/promoci%C3%B3n%20%231.jpg', 'Filename encoding');
    check($data['banners'][0]['imagen_movil'] === null, 'Missing mobile image');
    check($data['banners'][0]['id'] === 281, 'Numeric response types');
    check($data['banners'][0]['redireccion_web'] === '/ofertas', 'Preserve destination');

    $row['nombre_movil'] = 'movil.webp';
    $repo->rows = array($row);
    $data = $service->listBanners(null, 1, 50);
    check($data['banners'][0]['imagen_movil'] === 'https://www.importadorasupernova.com/Banners/movil.webp', 'Mobile image');
    check(!$data['pagination']['has_more'] && $data['pagination']['next_page'] === null, 'Last page');

    foreach (array(array('limit' => '101'), array('page' => '0'), array('tipo' => '1 OR 1=1'),
        array('tipo' => array('1')), array('limit' => '1.5'), array('page' => '999999999999999999999')) as $query) {
        $_GET = $query;
        check($controller->index()['status'] === 422, 'Invalid query rejected');
    }
    echo "Banner contract tests passed.\n";
}
