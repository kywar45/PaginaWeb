<?php

namespace App\Services;

class CartIdentity
{
    public function existingGuest()
    {
        $token = $_COOKIE['sn_guest_cart'] ?? null;
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) return null;
        return array('user_id' => 0, 'guest_hash' => hash('sha256', $token));
    }

    public function resolve($user)
    {
        if (is_array($user) && (int) $user['id'] > 0) {
            return array('user_id' => (int) $user['id'], 'guest_hash' => null);
        }

        $name = 'sn_guest_cart';
        $token = isset($_COOKIE[$name]) && is_string($_COOKIE[$name]) ? $_COOKIE[$name] : '';
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) {
            $token = bin2hex(random_bytes(32));
        }
        $options = array(
            'expires' => time() + 60 * 60 * 24 * 365,
            'path' => $this->env('SESSION_COOKIE_PATH', '/'),
            'secure' => in_array(strtolower((string) $this->env('SESSION_SECURE', 'true')), array('1', 'true', 'yes', 'on'), true),
            'httponly' => true,
            'samesite' => $this->env('SESSION_SAMESITE', 'None'),
        );
        $domain = $this->env('SESSION_COOKIE_DOMAIN', '');
        if ($domain !== '') $options['domain'] = $domain;
        setcookie($name, $token, $options);
        $_COOKIE[$name] = $token;
        return array('user_id' => 0, 'guest_hash' => hash('sha256', $token));
    }

    private function env($key, $default)
    {
        if (function_exists('api_env')) return \api_env($key, $default);
        $value = getenv($key);
        if ($value !== false) return $value;
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}
