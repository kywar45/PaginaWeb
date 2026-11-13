<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Repositories\UserRepository;

class AuthService
{
    private $pdo;
    private $users;

    public function __construct(\PDO $pdo, UserRepository $users)
    {
        $this->pdo = $pdo;
        $this->users = $users;
    }

    public function login($correo, $password, $remember)
    {
        $correo = strtolower(trim((string) $correo));
        $password = (string) $password;

        if ($correo === '' || $password === '') {
            throw new ApiException('VALIDATION_ERROR', 'Correo y contrasena son obligatorios.', 422);
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new ApiException('VALIDATION_ERROR', 'Correo invalido.', 422);
        }

        $usuario = $this->users->findByCorreo($correo);
        $legacyPassword = false;

        if (!$usuario || !$this->passwordMatches($password, (string) $usuario['pass'], $legacyPassword)) {
            $this->logInfo('auth.login_failed', array(
                'correo_hash' => hash('sha256', $correo),
            ));

            throw new ApiException('INVALID_CREDENTIALS', 'Credenciales invalidas.', 401);
        }

        $token = bin2hex(random_bytes(32));
        $sessionHash = hash('sha256', $token);

        $this->pdo->beginTransaction();

        try {
            $this->users->updateSessionHash((int) $usuario['id'], $sessionHash);

            if ($legacyPassword && $this->envBool('AUTH_REHASH_LEGACY_PASSWORDS', false)) {
                $this->users->updatePasswordHash((int) $usuario['id'], password_hash($password, PASSWORD_DEFAULT));
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }

        $expiresAt = $this->queueSessionCookie($token, (bool) $remember);

        $this->logInfo('auth.login_success', array(
            'usuario_id' => (int) $usuario['id'],
        ));

        return array(
            'usuario' => $this->publicUser($usuario),
            'expires_at' => $expiresAt->format('c'),
            'carrito_fusion' => $this->mergeGuestCart($this->publicUser($usuario)),
        );
    }

    private function mergeGuestCart($user)
    {
        try {
            $identity = new CartIdentity();
            $guest = $identity->existingGuest();
            if ($guest === null) return array('fusionado' => false);
            $cart = new CartService(
                new \App\Repositories\CartRepository($this->pdo),
                new \App\Repositories\ProductRepository($this->pdo),
                $identity->resolve($user), $user, $guest
            );
            return $cart->execute('fusionar')['fusion'];
        } catch (\Throwable $error) {
            // La sesion ya es valida; un fallo de carrito no invalida el login ni borra las piezas.
            $this->logInfo('auth.cart_merge_failed', array('usuario_id' => $user['id']));
            return array('fusionado' => false, 'pendiente' => true,
                'mensaje' => $error instanceof ApiException ? $error->getMessage()
                    : 'Iniciaste sesion, pero no pudimos combinar los carritos. Tus productos de visitante siguen guardados.');
        }
    }

    public function currentUser()
    {
        return array(
            'usuario' => $this->publicUser($this->sessionUserOrFail()),
        );
    }

    public function optionalUser()
    {
        try {
            return $this->publicUser($this->sessionUserOrFail());
        } catch (ApiException $error) {
            if ($error->getStatus() === 401) return null;
            throw $error;
        }
    }

    public function logout()
    {
        $token = $this->sessionToken();

        if ($token !== '') {
            $this->users->clearSessionHash(hash('sha256', $token));
        }

        $this->clearSessionCookie();

        return array(
            'logged_out' => true,
        );
    }

    private function sessionUserOrFail()
    {
        $token = $this->sessionToken();

        if ($token === '') {
            throw new ApiException('UNAUTHENTICATED', 'Sesion no valida.', 401);
        }

        $usuario = $this->users->findBySessionHash(hash('sha256', $token));

        if (!$usuario) {
            $this->clearSessionCookie();
            throw new ApiException('UNAUTHENTICATED', 'Sesion no valida.', 401);
        }

        return $usuario;
    }

    private function sessionToken()
    {
        $cookieName = $this->cookieConfig()['name'];

        if (!isset($_COOKIE[$cookieName])) {
            return '';
        }

        return trim((string) $_COOKIE[$cookieName]);
    }

    private function publicUser($usuario)
    {
        return array(
            'id' => (int) $usuario['id'],
            'nombre' => (string) $usuario['nombre'],
            'apellido' => (string) $usuario['apellido'],
            'correo' => (string) $usuario['correo'],
            'tipo' => (string) $usuario['tipo'],
            'nivel' => (string) $usuario['nivel'],
            'user_vip' => (int) $usuario['user_vip'],
        );
    }

    private function passwordMatches($password, $storedPassword, &$legacyPassword)
    {
        $legacyPassword = false;

        if ($storedPassword === '') {
            return false;
        }

        $info = password_get_info($storedPassword);

        if (isset($info['algo']) && (int) $info['algo'] !== 0) {
            return password_verify($password, $storedPassword);
        }

        if ($this->envBool('AUTH_ALLOW_LEGACY_PASSWORDS', true) && hash_equals($storedPassword, $password)) {
            $legacyPassword = true;
            return true;
        }

        return false;
    }

    private function queueSessionCookie($token, $remember)
    {
        $cookie = $this->cookieConfig();
        $minutes = $remember
            ? (int) $this->env('SESSION_REMEMBER_MINUTES', 43200)
            : (int) $this->env('SESSION_LIFETIME_MINUTES', 1440);

        if ($minutes < 1) {
            $minutes = 1440;
        }

        $expires = time() + ($minutes * 60);
        $options = array(
            'expires' => $expires,
            'path' => $cookie['path'],
            'secure' => $cookie['secure'],
            'httponly' => true,
            'samesite' => $cookie['sameSite'],
        );

        if ($cookie['domain'] !== '') {
            $options['domain'] = $cookie['domain'];
        }

        setcookie($cookie['name'], $token, $options);

        $expiresAt = new \DateTimeImmutable('@' . $expires);
        return $expiresAt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    }

    private function clearSessionCookie()
    {
        $cookie = $this->cookieConfig();
        $options = array(
            'expires' => time() - 3600,
            'path' => $cookie['path'],
            'secure' => $cookie['secure'],
            'httponly' => true,
            'samesite' => $cookie['sameSite'],
        );

        if ($cookie['domain'] !== '') {
            $options['domain'] = $cookie['domain'];
        }

        setcookie($cookie['name'], '', $options);
        unset($_COOKIE[$cookie['name']]);
    }

    private function cookieConfig()
    {
        return array(
            'name' => $this->env('SESSION_COOKIE', 'sn_session'),
            'path' => $this->env('SESSION_COOKIE_PATH', '/'),
            'domain' => $this->env('SESSION_COOKIE_DOMAIN', ''),
            'sameSite' => $this->env('SESSION_SAMESITE', 'None'),
            'secure' => $this->envBool('SESSION_SECURE', true),
        );
    }

    private function env($key, $default)
    {
        if (function_exists('api_env')) {
            return \api_env($key, $default);
        }

        $value = getenv($key);

        if ($value !== false) {
            return $value;
        }

        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        return $default;
    }

    private function envBool($key, $default)
    {
        $value = $this->env($key, null);

        if ($value === null) {
            return $default;
        }

        return in_array(strtolower(trim((string) $value)), array('1', 'true', 'yes', 'on'), true);
    }

    private function logInfo($message, $context)
    {
        if (class_exists('App\\Logging\\Logger') && method_exists('App\\Logging\\Logger', 'info')) {
            \App\Logging\Logger::info($message, $context);
        }
    }
}
