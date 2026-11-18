<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Http\Request;
use App\Http\Response;
use App\Services\GuestOrderEmailService;

class OrderMailTestController
{
    private $email;
    public function __construct(GuestOrderEmailService $email) { $this->email = $email; }
    public function send()
    {
        header('Cache-Control: private, no-store');
        try {
            if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
                throw new ApiException('UNSUPPORTED_MEDIA_TYPE', 'Envía los datos como JSON.', 415);
            }
            return Response::success($this->email->test(Request::json()), 200);
        } catch (ApiException $error) {
            return Response::error($error->getErrorCodeName(), $error->getMessage(), $error->getStatus());
        } catch (\Throwable $error) {
            $reference = bin2hex(random_bytes(8));
            GuestOrderEmailService::log($reference . ' Test failure ' . get_class($error) . ' [' . $error->getCode() . ']');
            return Response::error('MAIL_TEST_UNAVAILABLE', 'No se pudo ejecutar la prueba. Referencia: ' . $reference, 503);
        }
    }
}
