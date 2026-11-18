<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Repositories\GuestOrderRepository;

class GuestOrderService
{
    private $customers;
    private $orders;
    private $cart;
    public function __construct(GuestCustomerService $customers, GuestOrderRepository $orders, CartService $cart)
    {
        $this->customers = $customers;
        $this->orders = $orders;
        $this->cart = $cart;
    }

    public function create(array $data)
    {
        $requestHash = null;
        if (isset($data['request_id'])) {
            $requestId = $data['request_id'];
            $cookie = $_COOKIE['sn_guest_cart'] ?? null;
            if (!is_string($requestId) || !preg_match('/^[a-f0-9]{32}$/D', $requestId)) {
                throw new ApiException('VALIDATION_ERROR', 'Actualiza la página e intenta procesar tu pedido nuevamente.', 422);
            }
            if (!is_string($cookie) || !preg_match('/^[a-f0-9]{64}$/D', $cookie)) {
                throw new ApiException('GUEST_SESSION_REQUIRED', 'Abre tu carrito antes de procesar el pedido.', 401);
            }
            // A purchase attempt belongs to this guest browser, never to contact details alone.
            $requestHash = hash_hmac('sha256', $requestId, $cookie);
        }
        $envio = $data['tipo_entrega'] ?? null;
        if (!in_array($envio, array(0, 1), true)) throw new ApiException('VALIDATION_ERROR', 'Selecciona un tipo de entrega.', 422);
        $carrier = $envio === 0 ? 21 : ($data['shipping_carrier_id'] ?? null);
        if (!is_int($carrier) || $carrier < 1) throw new ApiException('VALIDATION_ERROR', 'Selecciona una paquetería válida.', 422);
        $branch = $data['sucursal_id'] ?? null;
        if ($envio === 0 && (!is_int($branch) || $branch < 1)) throw new ApiException('VALIDATION_ERROR', 'Selecciona una sucursal.', 422);
        // Resolve from validated contact details; never trust a browser-supplied customer ID.
        $customer = $this->customers->register($data, $requestHash);
        $id = $customer['cliente_invitado_id'];
        return $this->orders->withCustomerLock($id, function () use ($id, $carrier, $envio, $branch) {
            return $this->cart->execute('leer', null, null, function ($snapshot) use ($id, $carrier, $envio, $branch) {
                if (!$snapshot['items']) throw new ApiException('CART_EMPTY', 'Tu carrito está vacío.', 409);
                $quantity = 0;
                $cents = 0;
                foreach ($snapshot['items'] as $item) {
                    if (!$item['puede_comprar']) throw new ApiException('CART_UNAVAILABLE', 'Quita los productos no disponibles antes de continuar.', 409);
                    $quantity += $item['quantity'];
                    $cents += (int) round($item['subtotal'] * 100);
                }
                if ($quantity < 1 || $quantity > 2147483647 || $cents < 1 || $cents > 9999999999) {
                    throw new ApiException('ORDER_AMOUNT_INVALID', 'Revisa las cantidades y el importe de tu carrito.', 422);
                }
                if ($envio === 0) $this->orders->validateBranch($branch);
                $folio = $this->orders->create($id, $carrier, $envio, $quantity, $cents, $envio === 0 ? $branch : null);
                $folio['productos_registrados'] = $this->orders->saveItems($id, $folio['orden'], $snapshot['items']);
                return $folio;
            });
        });
    }
}
