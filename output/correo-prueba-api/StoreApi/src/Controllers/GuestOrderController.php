<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Http\Request;
use App\Http\Response;
use App\Services\GuestOrderService;
use App\Services\GuestOrderPdfLink;

class GuestOrderController
{
    private $service;
    private $pdfLinks;
    private $email;
    public function __construct(GuestOrderService $service, GuestOrderPdfLink $pdfLinks, \App\Services\GuestOrderEmailService $email = null) { $this->service = $service; $this->pdfLinks = $pdfLinks; $this->email = $email; }
    public function create()
    {
        header('Cache-Control: private, no-store');
        try {
            if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
                throw new ApiException('UNSUPPORTED_MEDIA_TYPE', 'Envía los datos como JSON.', 415);
            }
            $order = $this->service->create(Request::json());
            $order['pdf_token'] = $this->pdfLinks->issue($order['folio_id'], $order['cliente_invitado_id']);
            if ($this->email && \App\Services\GuestOrderEmailService::enabled('ORDER_MAIL_AUTO_ENABLED', true)) {
                try { $order['email_status'] = $this->email->confirm($order); }
                catch (\Throwable $error) {
                    // The purchase is committed. Never turn a mail failure into an order failure.
                    \App\Services\GuestOrderEmailService::log('Queue failure for folio ' . (int) $order['folio_id'] . ' ' . get_class($error) . ' [' . $error->getCode() . '] ' . $error->getMessage());
                    $order['email_status'] = 'pending';
                }
            } else {
                $order['email_status'] = 'disabled';
            }
            return Response::success($order, 200);
        } catch (ApiException $error) {
            return Response::error($error->getErrorCodeName(), $error->getMessage(), $error->getStatus());
        } catch (\Throwable $error) {
            $message = '[' . date('Y-m-d H:i:s') . '] GuestOrderController: ' . get_class($error) . ' [' . $error->getCode() . '] '
                . str_replace(array("\r", "\n"), ' ', $error->getMessage());
            $directory = dirname(__DIR__, 2) . '/storage/logs';
            if (is_dir($directory) && is_writable($directory)) error_log($message . PHP_EOL, 3, $directory . '/error.log');
            else error_log($message);
            return Response::error('GUEST_ORDER_UNAVAILABLE', 'No pudimos registrar el folio. Intenta de nuevo.', 503);
        }
    }
}
