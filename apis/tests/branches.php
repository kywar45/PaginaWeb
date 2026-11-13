<?php
namespace App\Http {
    class Response {
        public static function success($data, $status = 200) { return array('status' => $status, 'data' => $data); }
    }
}
namespace {
    require __DIR__ . '/../src/Repositories/BranchRepository.php';
    require __DIR__ . '/../src/Services/BranchService.php';
    require __DIR__ . '/../src/Controllers/BranchController.php';

    class Branches extends \App\Repositories\BranchRepository {
        public $rows = array();
        public function __construct() {}
        public function listActive() { return $this->rows; }
    }
    function check($condition, $message) { if (!$condition) throw new \RuntimeException($message); }

    $repo = new Branches();
    $controller = new \App\Controllers\BranchController(new \App\Services\BranchService($repo));
    check($controller->index()['data']['sucursales'] === array(), 'Empty list');
    $row = array('id' => '2', 'nombre' => ' Centro ', 'direccion' => 'Calle Uno 123',
        'codigo_postal' => '06000', 'telefono' => ' +52 (55) 2495-4670 ',
        'horario_atencion' => "Lunes a viernes: 9:00–18:00\nSábado: 9:00–14:00",
        'google_maps_url' => 'https://maps.app.goo.gl/example',
        'latitud' => '19.43260800', 'longitud' => '-99.13320900');
    $repo->rows = array($row);
    $response = $controller->index();
    $branch = $response['data']['sucursales'][0];
    check($response['status'] === 200 && $branch['id'] === 2 && $branch['nombre'] === 'Centro', 'Branch response');
    check($branch['codigo_postal'] === '06000', 'Leading zero in postal code preserved');
    check($branch['telefono_url'] === 'tel:+525524954670', 'Clickable phone without formatting');
    check($branch['telefono'] === '+52 (55) 2495-4670', 'Display phone preserved');
    check($branch['google_maps_url'] === $row['google_maps_url'], 'Stored map URL preferred');
    check($branch['horario_atencion'] === $row['horario_atencion'], 'Multiline hours preserved');

    $repo->rows[0]['google_maps_url'] = null;
    $branch = $controller->index()['data']['sucursales'][0];
    check(strpos($branch['google_maps_url'], 'query=19.432608%2C-99.133209') !== false, 'Coordinates used without stored map');
    $repo->rows[0]['latitud'] = '0';
    $repo->rows[0]['longitud'] = '0';
    check(strpos($controller->index()['data']['sucursales'][0]['google_maps_url'], 'query=0%2C0') !== false, 'Zero coordinates are valid');

    $repo->rows[0]['latitud'] = '91';
    $repo->rows[0]['longitud'] = null;
    $repo->rows[0]['telefono'] = null;
    $repo->rows[0]['horario_atencion'] = '  ';
    $repo->rows[0]['google_maps_url'] = 'javascript:alert(1)';
    $branch = $controller->index()['data']['sucursales'][0];
    check($branch['latitud'] === null && $branch['longitud'] === null, 'Invalid and missing coordinates');
    check($branch['telefono'] === null && $branch['telefono_url'] === null && $branch['horario_atencion'] === null, 'Optional empty fields');
    check($branch['google_maps_url'] === 'https://www.google.com/maps/search/?api=1&query=Calle%20Uno%20123%2006000', 'Address and postal code fallback; unsafe URL rejected');

    $repo->rows[0]['id'] = '3';
    $repo->rows[] = $row;
    check(array_column($controller->index()['data']['sucursales'], 'id') === array(3, 2), 'Repository display order preserved');
    echo "Branch contract tests passed.\n";
}
