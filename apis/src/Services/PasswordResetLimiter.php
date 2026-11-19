<?php
namespace App\Services;
use App\Exceptions\ApiException;
class PasswordResetLimiter extends RegistrationLimiter
{
    protected function directory() { return dirname(__DIR__, 2) . '/storage/password-reset'; }
    public function consume($email)
    {
        try { parent::consume($email); }
        catch (ApiException $error) {
            if ($error->getStatus() === 503) throw new ApiException('RESET_UNAVAILABLE', 'No pudimos preparar la solicitud. Intenta más tarde.', 503);
            throw new ApiException('RESET_RATE_LIMIT', $error->getMessage(), $error->getStatus());
        }
    }
}
