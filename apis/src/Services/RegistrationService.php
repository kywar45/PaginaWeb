<?php
namespace App\Services;

use App\Exceptions\ApiException;
use App\Repositories\RegistrationRepository;

class RegistrationService
{
    private $users;
    private $email;
    private $limiter;
    public function __construct(RegistrationRepository $users, RegistrationEmailService $email, RegistrationLimiter $limiter = null)
    {
        $this->users = $users; $this->email = $email; $this->limiter = $limiter ?: new RegistrationLimiter();
    }
    public function register(array $input)
    {
        $data = RegistrationData::validate($input);
        $this->limiter->consume($data['correo']);
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $id = $this->users->create($data, password_hash($data['password'], PASSWORD_DEFAULT), $hash, date('Y-m-d H:i:s', time() + 86400));
        return $this->send($id, $data['nombre'], $data['correo'], $token, $hash);
    }
    public function resend(array $input)
    {
        $email = RegistrationData::email($input['correo'] ?? null);
        $this->limiter->consume($email);
        $password = $input['password'] ?? null;
        $user = $this->users->findEmail($email);
        $legacy = false;
        if (!$user || !is_string($password) || strlen($password) > 1024 || !AuthService::checkPassword($password, (string) $user['pass'], $legacy, GuestOrderEmailService::enabled('AUTH_ALLOW_LEGACY_PASSWORDS', true))) {
            throw new ApiException('INVALID_CREDENTIALS', 'Revisa tu correo y contraseña para reenviar la confirmación.', 401);
        }
        if (trim((string) $user['token_registro']) !== '') return array('already_verified' => true);
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        if (!$this->users->renew((int) $user['id'], $hash, date('Y-m-d H:i:s', time() + 86400))) return array('already_verified' => true);
        return $this->send((int) $user['id'], $user['nombre'], $user['correo'], $token, $hash);
    }
    public function validateGuest(array $input)
    {
        $data = $this->users->guestData($this->tokenHash($input));
        if ($this->users->findEmail($data['correo'])) throw new ApiException('EMAIL_EXISTS', 'Ya tienes una cuenta con este correo. Inicia sesión o recupera tu contraseña.', 409);
        return array('valid' => true);
    }
    public function registerGuest(array $input)
    {
        $hash = $this->tokenHash($input);
        $password = RegistrationData::password($input);
        $data = $this->users->guestData($hash);
        RegistrationData::validate(array_merge($data, array('password' => $password, 'password_confirmation' => $password)));
        $this->limiter->consume($data['correo']);
        $this->users->create(array(), password_hash($password, PASSWORD_DEFAULT), null, null, $hash);
        return array('created' => true, 'verified' => true);
    }
    private function send($id, $name, $email, $token, $hash)
    {
        // The account is committed; a hosting error must not cause a duplicate signup.
        try { $accepted = $this->email->send($name, $email, $token); }
        catch (\Throwable $error) { $accepted = false; GuestOrderEmailService::log('Registration mail failure for user ' . $id . ' ' . get_class($error)); }
        try { $this->users->markSent($id, $hash, $accepted); }
        catch (\Throwable $error) { GuestOrderEmailService::log('Registration mail status unavailable for user ' . $id); }
        return array('verification_required' => true, 'email_status' => $accepted ? 'accepted' : 'pending', 'expires_in_hours' => 24);
    }
    private function tokenHash(array $input)
    {
        $token = $input['token'] ?? null;
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) throw new ApiException('CONFIRMATION_INVALID', 'El enlace de confirmación no es válido.', 422);
        return hash('sha256', $token);
    }
    public function validate(array $input)
    {
        $this->users->validate($this->tokenHash($input));
        return array('valid' => true);
    }
    public function confirm(array $input)
    {
        $this->users->confirm($this->tokenHash($input));
        return array('verified' => true);
    }
}
