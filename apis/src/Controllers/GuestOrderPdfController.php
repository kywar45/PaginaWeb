<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Http\Response;
use App\Pdf\OrderPdf;
use App\Services\GuestOrderPdfLink;
use App\Services\OrderPdfService;

class GuestOrderPdfController
{
    private $orders;
    public function __construct(OrderPdfService $orders) { $this->orders = $orders; }
    public function showEmail(\App\Services\GuestOrderEmailService $email)
    {
        header('Cache-Control: private, no-store');
        header('Referrer-Policy: no-referrer');
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        try {
            $link = $email->resolve($_GET['token'] ?? null);
            $order = $this->orders->prepareGuest($link['folio_id'], $link['cliente_id']);
            $bytes = (new OrderPdf($order))->render();
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="pedido-' . $link['folio_id'] . '.pdf"');
            header('X-Content-Type-Options: nosniff');
            header('Content-Length: ' . strlen($bytes));
            echo $bytes;
        } catch (ApiException $error) {
            return Response::error($error->getErrorCodeName(), $error->getMessage(), $error->getStatus());
        } catch (\Throwable $error) {
            error_log('Guest email PDF unavailable');
            return Response::error('GUEST_PDF_UNAVAILABLE', 'No pudimos abrir el pedido. Intenta de nuevo.', 503);
        }
    }
    public function show()
    {
        header('Cache-Control: private, no-store');
        header('Referrer-Policy: no-referrer');
        try {
            $customer = $_GET['cliente'] ?? null;
            if (!is_string($customer) || !preg_match('/^[1-9][0-9]{0,9}$/D', $customer)) {
                throw new ApiException('ORDER_NOT_FOUND', 'El enlace del pedido no es válido.', 404);
            }
            $customer = filter_var($customer, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => 2147483647)));
            if ($customer === false) throw new ApiException('ORDER_NOT_FOUND', 'El enlace del pedido no es válido.', 404);
            $id = (new GuestOrderPdfLink())->resolve($_GET['token'] ?? null, $customer);
            $order = $this->orders->prepareGuest($id, $customer);
            $bytes = (new OrderPdf($order))->render();
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="pedido-' . $id . '.pdf"');
            header('X-Content-Type-Options: nosniff');
            header('Content-Length: ' . strlen($bytes));
            echo $bytes;
        } catch (ApiException $error) {
            return Response::error($error->getErrorCodeName(), $error->getMessage(), $error->getStatus());
        } catch (\Throwable $error) {
            error_log('GuestOrderPdfController: ' . get_class($error) . ' [' . $error->getCode() . '] ' . $error->getMessage());
            return Response::error('GUEST_PDF_UNAVAILABLE', 'El pedido está guardado, pero no pudimos preparar su PDF. Intenta de nuevo.', 503);
        }
    }
}
