<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\BannerController;
use App\Controllers\CategoryController;
use App\Controllers\BrandController;
use App\Controllers\BranchController;
use App\Controllers\ProductController;
use App\Controllers\TikTokVideoController;
use App\Controllers\InstagramReelController;
use App\Database\Connection;
use App\Http\Response;
use App\Http\Router;
use App\Repositories\BannerRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\BrandRepository;
use App\Repositories\BranchRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Repositories\TikTokVideoRepository;
use App\Repositories\InstagramReelRepository;
use App\Services\AuthService;
use App\Services\BannerService;
use App\Services\CategoryService;
use App\Services\BrandService;
use App\Services\BranchService;
use App\Services\ProductService;
use App\Services\TikTokVideoService;
use App\Services\InstagramReelService;

return function (Router $router): void {

    $router->get('/v1/sucursales', function (): void {
        $controller = new BranchController(new BranchService(new BranchRepository(Connection::get())));
        $controller->index();
    });

    $router->get('/v1/marcas', function (): void {
        $controller = new BrandController(new BrandService(new BrandRepository(Connection::get())));
        $controller->index();
    });

    $router->get('/v1/instagram-reels', function (): void {
        $repository = new InstagramReelRepository(Connection::get());
        $controller = new InstagramReelController(new InstagramReelService($repository));
        $controller->index();
    });

    $router->get('/v1/tiktok-videos', function (): void {
        $repository = new TikTokVideoRepository(Connection::get());
        $controller = new TikTokVideoController(new TikTokVideoService($repository));
        $controller->index();
    });

    $router->get('/v1/banners', function (): void {
        $pdo = Connection::get();
        $repository = new BannerRepository($pdo);
        $service = new BannerService($repository);
        $controller = new BannerController($service);

        $controller->index();
    });

    $router->get('/v1/categorias', function (): void {
        $pdo = Connection::get();
        $repository = new CategoryRepository($pdo);
        $service = new CategoryService($repository);
        $controller = new CategoryController($service);

        $controller->index();
    });

    $router->get('/v1/productos', function (): void {
        $pdo = Connection::get();
        $repository = new ProductRepository($pdo);
        $service = new ProductService($repository);
        $controller = new ProductController($service);

        $controller->index();
    });

    $router->get('/v1/productos/subcategorias-aleatorias', function (): void {
        $pdo = Connection::get();
        $repository = new ProductRepository($pdo);
        $service = new ProductService($repository);
        $controller = new ProductController($service);

        $controller->randomSubcategories();
    });

    $router->get('/', function (): void {
        Response::success([
            'service' => 'StoreApi',
            'version' => 'v1',
        ]);
    });

    $router->get('/v1/health', function (): void {
        try {
            $pdo = Connection::get();

            $stmt = $pdo->query('SELECT 1');
            $stmt->fetch();

            Response::success([
                'service' => 'StoreApi',
                'database' => 'connected',
            ]);
        } catch (Throwable $e) {
            error_log(
                '[' . date('Y-m-d H:i:s') . '] HEALTH ERROR: '
                . $e->getMessage()
                . PHP_EOL,
                3,
                dirname(__DIR__) . '/storage/logs/error.log'
            );

            Response::error(
                'SERVICE_UNAVAILABLE',
                'Servicio temporalmente no disponible',
                503
            );
        }
    });

    $router->post('/v1/auth/login', function (): void {
        $pdo = Connection::get();
        $repository = new UserRepository($pdo);
        $service = new AuthService($pdo, $repository);
        $controller = new AuthController($service);

        $controller->login();
    });

    $router->get('/v1/auth/me', function (): void {
        $pdo = Connection::get();
        $repository = new UserRepository($pdo);
        $service = new AuthService($pdo, $repository);
        $controller = new AuthController($service);

        $controller->me();
    });

    $router->post('/v1/auth/logout', function (): void {
        $pdo = Connection::get();
        $repository = new UserRepository($pdo);
        $service = new AuthService($pdo, $repository);
        $controller = new AuthController($service);

        $controller->logout();
    });
};
