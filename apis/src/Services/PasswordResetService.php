<?php
namespace App\Services;
use App\Exceptions\ApiException;
use App\Repositories\PasswordResetRepository;

class PasswordResetService
{
    private $users;
    private $email;
    private $limiter;
    public function __construct(PasswordResetRepository $users, PasswordResetEmailService $email, RegistrationLimiter $limiter = null)
    {
        $this->users = $users; $this->email = $email; $this->limiter = $limiter ?: new PasswordResetLimiter();
    }
    public function request(array $input)
    {
        $email = RegistrationData::email($input['correo'] ?? null);
        $this->limiter->consume($email);
        $token = bin2hex(random_bytes(32));
        $hash = 'reset1:' . hash('sha256', $token);
        $user = $this->users->issue($email, $hash, date('Y-m-d H:i:s', time() + 1800));
        if ($user) {
            try { $accepted = $this->email->send($user['nombre'], $user['correo'], $token); }
            catch (\Throwable $error) { $accepted = false; GuestOrderEmailService::log('Password reset mail failure for user ' . (int) $user['id'] . ' ' . get_class($error)); }
            if (!$accepted) {
                try { $this->users->clearFailed((int) $user['id'], $hash); }
                catch (\Throwable $error) { GuestOrderEmailService::log('Password reset cleanup unavailable for user ' . (int) $user['id']); }
                GuestOrderEmailService::log('Password reset mail not accepted for user ' . (int) $user['id']);
            }
        }
        // Identical response for unknown addresses and accounts with a mail failure.
        return array('requested' => true, 'message' => 'Si existe una cuenta con ese correo, recibirás un enlace para cambiar tu contraseña. Revisa también spam.');
    }
    private function tokenHash(array $input)
    {
        $token = $input['token'] ?? null;
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) throw new ApiException('RESET_LINK_INVALID', 'El enlace no es válido. Solicita uno nuevo.', 410);
        return 'reset1:' . hash('sha256', $token);
    }
    public function validate(array $input)
    {
        $this->users->validate($this->tokenHash($input));
        return array('valid' => true);
    }
    public function reset(array $input)
    {
        $hash = $this->tokenHash($input);
        // Check used/expired links before the cooldown so an old link never asks
        // a customer to wait or enter a new password again.
        $this->users->validate($hash);
        $password = RegistrationData::password($input);
        try { $this->limiter->consume('reset-token:' . $hash); }
        catch (ApiException $error) {
            if ($error->getStatus() === 429) throw new ApiException('RESET_RATE_LIMIT', 'Espera un minuto antes de intentar guardar la contraseña nuevamente.', 429);
            throw $error;
        }
        // Only perform expensive password hashing after the locked token is valid.
        $this->users->reset($hash, function () use ($password) {
            return password_hash($password, PASSWORD_DEFAULT);
        });
        return array('reset' => true);
    }
}
