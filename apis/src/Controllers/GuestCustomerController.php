<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Http\Request;
use App\Http\Response;
use App\Services\GuestCustomerService;

class GuestCustomerController
{
    private $service;
    public function __construct(GuestCustomerService $service) { $this->service = $service; }

    public function register()
    {
        header('Cache-Control: private, no-store');
        try {
            $type = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
            if ($type !== 'application/json') throw new ApiException('UNSUPPORTED_MEDIA_TYPE', 'Envía los datos como JSON.', 415);
            return Response::success($this->service->register(Request::json()), 200);
        } catch (ApiException $error) {
            return Response::error($error->getErrorCodeName(), $error->getMessage(), $error->getStatus());
        } catch (\Throwable $error) {
            $message = '[' . date('Y-m-d H:i:s') . '] GuestCustomerController: '
                . get_class($error) . ' [' . $error->getCode() . '] '
                . str_replace(array("\r", "\n"), ' ', $error->getMessage())
                . ' in ' . basename($error->getFile()) . ':' . $error->getLine();
            $directory = dirname(__DIR__, 2) . '/storage/logs';
            if (is_dir($directory) && is_writable($directory)) {
                error_log($message . PHP_EOL, 3, $directory . '/error.log');
            } else {
                error_log($message);
            }
            return Response::error('GUEST_CUSTOMER_UNAVAILABLE', 'No pudimos guardar tus datos. Intenta de nuevo.', 503);
        }
    }
}
