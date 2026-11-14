<?php

namespace App\Services;

use App\Exceptions\ApiException;

class OrderPdfLink
{
    private const CONTEXT = 'supernova-order-pdf-v1';
    private const TTL = 3600;
    private $key;

    public function __construct($sessionToken)
    {
        if (!is_string($sessionToken) || strlen($sessionToken) < 32) {
            throw new ApiException('UNAUTHENTICATED', 'Vuelve a iniciar sesion para consultar tus pedidos.', 401);
        }
        if (!function_exists('openssl_encrypt') || !in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
            throw new ApiException('PDF_LINK_UNAVAILABLE', 'No pudimos preparar el enlace del pedido.', 503);
        }
        // Domain-separated key from the high-entropy HttpOnly session cookie.
        // No shared hard-coded encryption key or browser-provided identity.
        $this->key = hash_hkdf('sha256', $sessionToken, 32, self::CONTEXT);
    }

    public function issue($orderId, $userId, $now = null)
    {
        $now = $now === null ? time() : $now;
        if (!is_int($orderId) || $orderId < 1 || $orderId > 2147483647 || !is_int($userId) || $userId < 1 || $userId > 2147483647) {
            throw new ApiException('ORDER_NOT_FOUND', 'Pedido no disponible.', 404);
        }
        $nonce = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(pack('NNN', $orderId, $userId, $now + self::TTL), 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $nonce, $tag, self::CONTEXT, 16);
        if ($ciphertext === false || strlen($tag) !== 16) throw new ApiException('PDF_LINK_UNAVAILABLE', 'No pudimos preparar el enlace del pedido.', 503);
        return 'p1.' . $this->encode($nonce . $tag . $ciphertext);
    }

    public function resolve($token, $userId, $now = null)
    {
        if (!is_string($token) || !preg_match('/^p1\.[A-Za-z0-9_-]{54}$/D', $token)) $this->invalid();
        $encoded = substr($token, 3);
        $bytes = base64_decode(strtr($encoded, '-_', '+/') . '==', true);
        if ($bytes === false || strlen($bytes) !== 40 || !hash_equals($this->encode($bytes), $encoded)) $this->invalid();
        $payload = openssl_decrypt(substr($bytes, 28), 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, substr($bytes, 0, 12), substr($bytes, 12, 16), self::CONTEXT);
        if ($payload === false || strlen($payload) !== 12) $this->invalid();
        $data = unpack('Norder/Nuser/Nexpires', $payload);
        if ($data['user'] !== $userId || $data['order'] < 1 || $data['order'] > 2147483647) $this->invalid();
        $now = $now === null ? time() : $now;
        if ($data['expires'] <= $now) throw new ApiException('ORDER_LINK_EXPIRED', 'El enlace caduco. Vuelve a Mis pedidos y abre el pedido nuevamente.', 410);
        if ($data['expires'] > $now + self::TTL) $this->invalid();
        return $data['order'];
    }

    private function encode($bytes) { return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='); }
    private function invalid() { throw new ApiException('ORDER_LINK_INVALID', 'Enlace de pedido no valido. Abre el documento desde Mis pedidos.', 404); }
}
