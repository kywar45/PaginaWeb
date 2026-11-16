<?php

namespace App\Services;

use App\Exceptions\ApiException;

class GuestOrderPdfLink
{
    private $links;
    public function __construct($token = null)
    {
        $token = $token === null ? ($_COOKIE['sn_guest_cart'] ?? null) : $token;
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
            throw new ApiException('GUEST_SESSION_REQUIRED', 'Abre el pedido desde el navegador donde realizaste la compra.', 401);
        }
        // Domain-separated from registered-account links, keyed by the HttpOnly guest cookie.
        $this->links = new OrderPdfLink('guest-order-pdf:' . $token);
    }
    public function issue($orderId, $customerId) { return $this->links->issue($orderId, $customerId); }
    public function resolve($token, $customerId) { return $this->links->resolve($token, $customerId); }
}
