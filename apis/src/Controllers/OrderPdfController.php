<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Http\Response;
use App\Pdf\OrderPdf;
use App\Services\AuthService;
use App\Services\OrderPdfService;

class OrderPdfController
{
    private $auth;
    private $orders;
    public function __construct(AuthService $auth, OrderPdfService $orders)
    {
        $this->auth = $auth;
        $this->orders = $orders;
    }

    public function show()
    {
        header('Cache-Control: private, no-store');
        try {
            $resolved = $this->auth->resolveOrderPdfToken(isset($_GET['token']) ? $_GET['token'] : null);
            $user = $resolved['usuario'];
            $id = $resolved['id'];
            $order = $this->orders->prepare($id, (int) $user['id'], isset($user['correo']) ? $user['correo'] : null);
            $pdf = new OrderPdf($order);
            $bytes = $pdf->render();
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="pedido-' . $id . '.pdf"');
            header('X-Content-Type-Options: nosniff');
            header('Content-Length: ' . strlen($bytes));
            echo $bytes;
        } catch (ApiException $error) {
            return Response::error($error->getErrorCodeName(), $error->getMessage(), $error->getStatus());
        }
    }
}
