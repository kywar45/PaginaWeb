<?php
namespace App\Http {
    class Router {
        public $routes = array();
        public function get($path, $callback) { $this->add('GET', $path, $callback); }
        public function post($path, $callback) { $this->add('POST', $path, $callback); }
        private function add($method, $path, $callback) {
            $key = $method . ' ' . $path;
            if (isset($this->routes[$key])) throw new \RuntimeException('Duplicate route: ' . $key);
            if (!is_callable($callback)) throw new \RuntimeException('Invalid callback');
            $this->routes[$key] = $callback;
        }
    }
}
namespace {
    $router = new \App\Http\Router();
    $register = require __DIR__ . '/../routes/api.php';
    $register($router);
    foreach (array('GET /v1/auth/direcciones', 'POST /v1/auth/direcciones') as $route) {
        if (!isset($router->routes[$route])) throw new \RuntimeException('Missing address route');
    }
    // Optionally compare registration against the user's last working route file.
    if (isset($argv[1])) {
        $baseline = new \App\Http\Router();
        $registerBaseline = require $argv[1];
        $registerBaseline($baseline);
        $removed = array_diff_key($baseline->routes, $router->routes);
        $added = array_keys(array_diff_key($router->routes, $baseline->routes));
        sort($added);
        if ($removed || $added !== array('GET /v1/auth/direcciones', 'POST /v1/auth/direcciones')) {
            throw new \RuntimeException('Unexpected changes to working routes');
        }
        echo count($baseline->routes) . " existing routes preserved; only two address routes added.\n";
    }
    echo "Route registration passed without opening database connections or loading controllers.\n";
}
